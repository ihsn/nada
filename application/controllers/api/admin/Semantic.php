<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Semantic search (nada-ai) admin/indexing proxy API.
 *
 * Base URL: /api/admin/semantic
 *
 * Thin server-side proxy in front of the nada-ai FastAPI admin routes
 * (POST /admin/ingest/from-catalog(/all), GET /jobs, GET /admin/qdrant/collection,
 * GET /admin/search-index/status, POST /admin/ingest/reconcile, ...). Holds the
 * nada-ai admin credential (`semantic_search_admin_api_key`, sent as
 * X-NADA-Admin-Key) server-side so the browser never sees it — same pattern as
 * `semantic_search_api_key` for /search, kept as a *separate* secret since it
 * authenticates a different, more privileged surface (see the note on that
 * setting in Site configurations > Search > Semantic search settings).
 */
class Semantic extends MY_REST_Controller
{
	/** @var Client|null lazily built in _client() once config is loaded */
	private $client = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('acl_manager');
		$this->is_authenticated_or_die();
		$this->config->load('semantic_search');
	}

	// =====================================================================
	// nada-ai HTTP client
	// =====================================================================

	private function _client()
	{
		if ($this->client === null)
		{
			$base_url = rtrim((string) $this->config->item('semantic_search_url'), '/');
			if ($base_url === '')
			{
				throw new Exception('Semantic search is not configured: set semantic_search_url in Site configurations > Search > Semantic search settings.');
			}

			$admin_key = (string) $this->config->item('semantic_search_admin_api_key');

			$headers = array('Accept' => 'application/json');
			if ($admin_key !== '')
			{
				$headers['X-NADA-Admin-Key'] = $admin_key;
			}

			$this->client = new Client(array(
				'base_uri' => $base_url . '/',
				'timeout'  => 15,
				'headers'  => $headers,
			));
		}

		return $this->client;
	}

	/**
	 * Forward one call to nada-ai and relay its JSON response + status code as-is.
	 *
	 * @param string $method  GET|POST|DELETE
	 * @param string $path    path relative to the nada-ai base URL, no leading slash
	 * @param array  $options Guzzle request options (e.g. ['json' => [...]])
	 */
	private function _forward($method, $path, array $options = array())
	{
		try
		{
			$response = $this->_client()->request($method, $path, $options);
			$body = json_decode((string) $response->getBody(), true);
			$this->set_response(
				$body === null ? array() : $body,
				$response->getStatusCode()
			);
		}
		catch (RequestException $e)
		{
			// nada-ai reachable but returned a non-2xx (400/404/409/501/503/...) — relay its
			// own JSON error body + status code so the dashboard can show the real reason
			// (e.g. 409 already_running, 501 backend mismatch) rather than a generic failure.
			if ($e->hasResponse())
			{
				$resp = $e->getResponse();
				$body = json_decode((string) $resp->getBody(), true);
				$this->set_response(
					$body === null ? array('status' => 'error', 'message' => $e->getMessage()) : $body,
					$resp->getStatusCode()
				);
				return;
			}
			$this->set_response(
				array('status' => 'error', 'message' => 'nada-ai request failed: ' . $e->getMessage()),
				REST_Controller::HTTP_BAD_GATEWAY
			);
		}
		catch (ConnectException $e)
		{
			$this->set_response(
				array('status' => 'error', 'message' => 'Could not reach nada-ai at the configured semantic_search_url.'),
				REST_Controller::HTTP_SERVICE_UNAVAILABLE
			);
		}
		catch (Exception $e)
		{
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	private function _require($privilege)
	{
		// Throws AclAccessDeniedException on denial — caught by each action below,
		// same convention as api/admin/Configurations.php.
		$this->has_access('semantic_search', $privilege);
	}

	/**
	 * nada-ai's active engine ('opensearch' | 'qdrant'), or null if it could not be determined (nada-ai
	 * unreachable, or an old nada-ai build whose /health has no backend field). Used only to pick which
	 * of two engine-specific nada-ai routes to forward a call to (see collection_delete()) — a caller that
	 * only wants to *display* the engine should call GET /api/admin/semantic/health itself instead of adding
	 * another use of this.
	 */
	private function _engine()
	{
		try
		{
			$response = $this->_client()->request('GET', 'health');
			$body = json_decode((string) $response->getBody(), true);
			return is_array($body) ? ($body['backend'] ?? null) : null;
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * GET /api/admin/semantic/health
	 *
	 * A bare forward of nada-ai's own GET /health — just {status, backend, ...},
	 * no collection/embeddings/drift probes attached. Exists so a page can learn
	 * which engine is running (backend: 'opensearch' | 'qdrant') with one cheap
	 * call, instead of the four-probe overview aggregate below, when all it
	 * needs is the engine — e.g. to decide whether a Qdrant-only action
	 * (Collection tab, Danger Zone's drop collection) even applies.
	 */
	public function health_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'health');
	}

	// =====================================================================
	// Overview
	// =====================================================================

	/**
	 * GET /api/admin/semantic/overview
	 *
	 * Aggregates nada-ai health + collection + embedding-drift into one call
	 * so the Overview tab doesn't need four round trips. Best-effort: any one
	 * piece failing (e.g. drift check needs the model warmed up first) is
	 * reported inline rather than failing the whole response.
	 */
	public function overview_get()
	{
		try
		{
			$this->_require('view');
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$result = array('status' => 'success');
		$client = null;
		try
		{
			$client = $this->_client();
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		$result['health']     = $this->_probe($client, 'GET', 'health');
		$result['embeddings'] = $this->_probe($client, 'GET', 'health/embeddings');
		$result['collection'] = $this->_probe($client, 'GET', 'admin/qdrant/collection');
		$result['drift']      = $this->_probe($client, 'GET', 'admin/embeddings/drift');

		$this->set_response($result, REST_Controller::HTTP_OK);
	}

	/** One best-effort GET for overview_get() — never throws, always returns a shape the UI can render. */
	private function _probe(Client $client, $method, $path)
	{
		try
		{
			$response = $client->request($method, $path);
			return array(
				'ok'   => true,
				'data' => json_decode((string) $response->getBody(), true),
			);
		}
		catch (RequestException $e)
		{
			$detail = $e->hasResponse() ? json_decode((string) $e->getResponse()->getBody(), true) : null;
			return array(
				'ok'    => false,
				'error' => ($detail['detail'] ?? null) ?: $e->getMessage(),
			);
		}
		catch (Exception $e)
		{
			return array('ok' => false, 'error' => $e->getMessage());
		}
	}

	/**
	 * POST /api/admin/semantic/warmup
	 *
	 * Loads the embedding model into memory now instead of on the first search
	 * or ingest call. The model is lazy-loaded, so this reads "not_initialized"
	 * on the Overview tab after every nada-ai restart until either this is
	 * called or a real search/ingest happens.
	 */
	public function warmup_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('POST', 'health/embeddings/warmup');
	}

	// =====================================================================
	// Search test
	// =====================================================================

	/**
	 * POST /api/admin/semantic/search
	 * body: {query, mode?, filters?, size?, include_facets?, facet_fields?}
	 *
	 * Forwards to nada-ai's own public POST /search — same endpoint the live
	 * catalog search uses — so an admin can try a query/filters combination
	 * and see real results without leaving the dashboard. Gated on 'view'
	 * like the rest of the read-only dashboard, not on nada-ai's own (open)
	 * auth for that route.
	 */
	public function search_post()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$this->_forward('POST', 'search', array('json' => $body));
	}

	// =====================================================================
	// Collection info
	// =====================================================================

	/**
	 * GET /api/admin/semantic/type_counts
	 *
	 * Per catalog_type: how many entries NADA's catalog has vs. how many
	 * documents are indexed for that type — for the "is my catalog actually
	 * searchable" question the dashboard otherwise has no answer to.
	 */
	public function type_counts_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/catalog/type-counts');
	}

	/** GET /api/admin/semantic/collection */
	public function collection_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/qdrant/collection');
	}

	/**
	 * DELETE /api/admin/semantic/collection?confirm=true
	 *
	 * Drops the search store with no reindex attached — distinct from
	 * index_all_post's recreate_index=true, which always drops-then-reingests
	 * every catalog type in one call. Requires the same 'delete' privilege as
	 * recreate.
	 *
	 * Picks the matching nada-ai route for whichever engine is actually running (Qdrant's collection and
	 * OpenSearch's index are two different routes there — see docs on GET/DELETE /admin/qdrant/collection
	 * vs. DELETE /admin/index) so this one dashboard action works under either, rather than the dashboard
	 * needing to know or ask. Falls back to the Qdrant route when the engine can't be determined, matching
	 * this action's behavior before OpenSearch was ever an option here.
	 */
	public function collection_delete()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$confirm = $this->input->get('confirm');
		$confirmed = ($confirm === 'true' || $confirm === '1') ? 'true' : 'false';
		if ($this->_engine() === 'opensearch')
		{
			$this->_forward('DELETE', 'admin/index', array('query' => array('confirm' => $confirmed)));
			return;
		}
		$this->_forward('DELETE', 'admin/qdrant/collection', array(
			'query' => array('confirm' => $confirmed),
		));
	}

	// =====================================================================
	// Index catalog
	// =====================================================================

	/**
	 * POST /api/admin/semantic/index
	 * body: {catalog_type, ps?, limit?, force?, recreate_index?}
	 */
	public function index_post()
	{
		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$recreate = !empty($body['recreate_index']);

		try { $this->_require($recreate ? 'delete' : 'edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$this->_forward('POST', 'admin/ingest/from-catalog', array('json' => $body));
	}

	/**
	 * POST /api/admin/semantic/index_all
	 * body: {ps?, limit?, force?, recreate_index?}
	 */
	public function index_all_post()
	{
		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$recreate = !empty($body['recreate_index']);

		try { $this->_require($recreate ? 'delete' : 'edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$this->_forward('POST', 'admin/ingest/from-catalog/all', array('json' => $body));
	}

	/**
	 * POST /api/admin/semantic/index_idno
	 * body: {idno, type?, metadata_type?, force?}
	 *
	 * ``type`` is NADA's surveys.type (survey/document/timeseries/geospatial);
	 * mapped here to nada-ai's metadata_type. ``force`` re-fetches even if
	 * cached — use it when the row already has a last_error.
	 */
	public function index_idno_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$idno = isset($body['idno']) ? trim((string) $body['idno']) : '';
		if ($idno === '')
		{
			$this->set_response(array('status' => 'error', 'message' => 'idno required'), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		$metadata_type = isset($body['metadata_type']) ? trim((string) $body['metadata_type']) : '';
		if ($metadata_type === '')
		{
			$metadata_type = $this->_metadata_type(isset($body['type']) ? $body['type'] : null);
		}
		if ($metadata_type === null || $metadata_type === '')
		{
			$this->set_response(
				array('status' => 'error', 'message' => 'Unsupported catalog type for single-idno ingest'),
				REST_Controller::HTTP_BAD_REQUEST
			);
			return;
		}

		$this->_forward('POST', 'admin/catalog/' . rawurlencode($idno) . '/index', array(
			'json' => array(
				'metadata_type' => $metadata_type,
				'force'         => !empty($body['force']),
			),
		));
	}

	/** NADA surveys.type -> nada-ai metadata_type (same map as nada-ai search_index_sync). */
	private function _metadata_type($data_type)
	{
		$map = array(
			'timeseries'   => 'indicator',
			'timeseriesdb' => 'indicator',
			'indicator'    => 'indicator',
			'document'     => 'document',
			'geospatial'   => 'geospatial',
			'survey'       => 'microdata',
			'microdata'    => 'microdata',
		);
		$key = strtolower(trim((string) $data_type));
		return ($key !== '' && isset($map[$key])) ? $map[$key] : null;
	}

	// =====================================================================
	// Jobs
	// =====================================================================

	/** GET /api/admin/semantic/jobs[?status=running&limit=50] */
	public function jobs_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$query = array();
		$status = $this->input->get('status');
		$limit  = $this->input->get('limit');
		if ($status !== null && $status !== '') { $query['status'] = $status; }
		if ($limit  !== null && $limit  !== '') { $query['limit']  = $limit; }

		$this->_forward('GET', 'jobs', array('query' => $query));
	}

	/** GET /api/admin/semantic/job/{id} */
	public function job_get($id = null)
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		if (empty($id))
		{
			$this->set_response(array('status' => 'error', 'message' => 'job id required'), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}
		$this->_forward('GET', 'jobs/' . rawurlencode($id));
	}

	/** DELETE /api/admin/semantic/job/{id} — cancel a running/pending job */
	public function job_delete($id = null)
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		if (empty($id))
		{
			$this->set_response(array('status' => 'error', 'message' => 'job id required'), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}
		$this->_forward('DELETE', 'jobs/' . rawurlencode($id));
	}

	// =====================================================================
	// Sync status (NADA search-index change queue)
	// =====================================================================

	/** GET /api/admin/semantic/search_index_status */
	public function search_index_status_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/search-index/status');
	}

	/** POST /api/admin/semantic/search_index_reconcile — poll NADA's change queue now */
	public function search_index_reconcile_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('POST', 'admin/ingest/reconcile');
	}

	/**
	 * GET /api/admin/semantic/search_index_queue?status=pending|failed&limit=
	 *
	 * Live change-queue rows from NADA (search_index_queue), not nada-ai.
	 * Process pending still goes through search_index_reconcile_post above.
	 */
	public function search_index_queue_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		try
		{
			$this->load->library('Search_index_manager');
			$status = $this->input->get('status');
			$limit  = $this->input->get('limit');
			$page   = $this->search_index_manager->list_queue(
				($status !== false && $status !== null && $status !== '') ? (string) $status : 'pending',
				(int) $limit
			);
			$this->set_response(array('status' => 'success') + $page, REST_Controller::HTTP_OK);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/semantic/search_index_requeue
	 * body: {status: "failed"} to reset every failed row, or {object_type, object_id} for one.
	 */
	public function search_index_requeue_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		try
		{
			$this->load->library('Search_index_manager');
			$raw  = trim((string) $this->input->raw_input_stream);
			$body = $raw === '' ? array() : json_decode($raw, true);
			if (!is_array($body))
			{
				$body = array();
			}

			if (isset($body['status']) && $body['status'] === 'failed')
			{
				$count = $this->search_index_manager->requeue_failed();
				$this->set_response(array('status' => 'success', 'reset' => $count), REST_Controller::HTTP_OK);
				return;
			}

			$object_type = isset($body['object_type']) ? $body['object_type'] : null;
			$object_id   = isset($body['object_id']) ? $body['object_id'] : null;
			if ($object_type === null || $object_id === null)
			{
				throw new Search_index_exception('OBJECT_ID_REQUIRED', 400);
			}

			$item = $this->search_index_manager->requeue_object($object_type, $object_id);
			$this->set_response(array('status' => 'success', 'item' => $item), REST_Controller::HTTP_OK);
		}
		catch (Search_index_exception $e)
		{
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				$e->http_code ?: REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/semantic/search_index_diff_summary?object_type=survey
	 *
	 * Cheap missing/stale counts — how many catalog entries aren't indexed,
	 * how many indexed entries are no longer in the catalog. Distinct from
	 * search_index_reconcile_diff_post below, which actually resolves it.
	 */
	public function search_index_diff_summary_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$object_type = $this->input->get('object_type');
		$this->_forward('GET', 'admin/search-index/diff-summary', array(
			'query' => array('object_type' => ($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey'),
		));
	}

	/**
	 * GET /api/admin/semantic/search_index_type_breakdown?object_type=survey
	 *
	 * Per-data-type (microdata/geospatial/document/timeseries/...) catalog vs.
	 * index coverage — what search_index_diff_summary_get() above can't show
	 * since it lumps every type under one 'survey' total.
	 */
	public function search_index_type_breakdown_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$object_type = $this->input->get('object_type');
		$this->_forward('GET', 'admin/search-index/type-breakdown', array(
			'query' => array('object_type' => ($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey'),
		));
	}

	/** GET /api/admin/semantic/search_index_diff_missing?object_type=survey&limit=&offset= */
	public function search_index_diff_missing_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/search-index/diff/missing', array('query' => $this->_diff_list_query()));
	}

	/** GET /api/admin/semantic/search_index_diff_stale?object_type=survey&limit=&offset= */
	public function search_index_diff_stale_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/search-index/diff/stale', array('query' => $this->_diff_list_query()));
	}

	/**
	 * Shared object_type/limit/offset/data_type/has_error query resolution for
	 * the two diff-list actions above. ``has_error`` only affects the missing
	 * list (nada-ai's diff/stale route ignores it) but is harmless to forward
	 * either way.
	 */
	private function _diff_list_query()
	{
		$object_type = $this->input->get('object_type');
		$limit       = $this->input->get('limit');
		$offset      = $this->input->get('offset');
		$data_type   = $this->input->get('data_type');
		$has_error   = $this->input->get('has_error');
		$query = array(
			'object_type' => ($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey',
		);
		if ($limit !== false && $limit !== null && $limit !== '') { $query['limit'] = (int) $limit; }
		if ($offset !== false && $offset !== null && $offset !== '') { $query['offset'] = (int) $offset; }
		if ($data_type !== false && $data_type !== null && $data_type !== '') { $query['data_type'] = (string) $data_type; }
		if (in_array($has_error, array('1', 'true', 'yes'), true)) { $query['has_error'] = 'true'; }
		return $query;
	}

	/**
	 * POST /api/admin/semantic/search_index_reconcile_diff?object_type=survey&data_type=
	 *
	 * Index every catalog entry that isn't currently indexed, delete every
	 * indexed entry no longer in the catalog — same call whether this is a
	 * first-ever backfill or routine later reconciliation. ``data_type``,
	 * when given, narrows the run to one surveys.type value (microdata aka
	 * 'survey', geospatial, document, timeseries, ...) instead of all of
	 * object_type at once. Runs as a background job on the nada-ai side;
	 * watch it on the Jobs tab.
	 */
	public function search_index_reconcile_diff_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$object_type = $this->input->get('object_type');
		$data_type   = $this->input->get('data_type');
		$query = array('object_type' => ($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey');
		if ($data_type !== false && $data_type !== null && $data_type !== '') { $query['data_type'] = (string) $data_type; }
		$this->_forward('POST', 'admin/search-index/reconcile-diff', array('query' => $query));
	}
}
