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
 * (POST /admin/ingest/from-catalog(/all), GET /jobs, DELETE /admin/index,
 * GET /admin/search-index/status, POST /admin/ingest/reconcile, ...). Holds the
 * nada-ai key (`nada_ai_api_key`, sent as X-NADA-Admin-Key) server-side so the
 * browser never sees it.
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
			$base_url = rtrim((string) $this->config->item('nada_ai_url'), '/');
			if ($base_url === '')
			{
				throw new Exception('Semantic search is not configured: set the NADA-AI API URL in Site configurations > Search.');
			}

			$api_key = (string) $this->config->item('nada_ai_api_key');

			$headers = array('Accept' => 'application/json');
			if ($api_key !== '')
			{
				$headers['X-NADA-Admin-Key'] = $api_key;
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
				array('status' => 'error', 'message' => 'NADA-AI request failed: ' . $e->getMessage()),
				REST_Controller::HTTP_BAD_GATEWAY
			);
		}
		catch (ConnectException $e)
		{
			$this->set_response(
				array('status' => 'error', 'message' => 'Could not reach NADA-AI at the configured nada_ai_url.'),
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
	 * GET /api/admin/semantic/health
	 *
	 * A bare forward of nada-ai's own GET /health — just {status, backend, ...},
	 * without the embeddings/drift probes of the overview aggregate below.
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

	/**
	 * GET /api/admin/semantic/engine_status
	 *
	 * Which engine serves studies, variables and citations on this site, what nada-ai reports it can serve, and whether
	 * nada-ai is being called (the outage breaker and the fallbacks counted this hour). See Search_engine_resolver.
	 */
	public function engine_status_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$this->load->library('search_engine_resolver');
		$this->set_response(
			array_merge(array('status' => 'success', 'now' => time()), $this->search_engine_resolver->summary()),
			REST_Controller::HTTP_OK
		);
	}

	/**
	 * GET /api/admin/semantic/variables_stats
	 *
	 * Published variables in this catalog's database against what nada-ai's variable index holds — totals only
	 * (a per-study breakdown is a different, paged question). Both sides count published variables of published
	 * studies, the same population search can return, so the two numbers are comparable.
	 *
	 * The database side is computed here; the index side is nada-ai's GET /admin/variables/stats, best-effort like
	 * the overview probes: when nada-ai is unreachable, or has no variable index (501), the
	 * database numbers still come back and `index` says why it is missing, so the page can show that instead of
	 * failing.
	 */
	public function variables_stats_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		// INNER JOIN + published = 1: the same population NADA's extract endpoint hands to the indexer.
		$row = $this->db->query(
			'SELECT COUNT(*) AS variables, COUNT(DISTINCT v.sid) AS studies
			 FROM variables v INNER JOIN surveys s ON s.id = v.sid
			 WHERE s.published = 1'
		)->row_array();

		try
		{
			$index = $this->_probe($this->_client(), 'GET', 'admin/variables/stats');
		}
		catch (Exception $e)
		{
			$index = array('ok' => false, 'error' => $e->getMessage(), 'status' => null);
		}

		$this->set_response(array(
			'status'   => 'success',
			'database' => array(
				'variables' => (int) ($row['variables'] ?? 0),
				'studies'   => (int) ($row['studies'] ?? 0),
			),
			'index'    => $index,
		), REST_Controller::HTTP_OK);
	}

	/**
	 * GET /api/admin/semantic/variables_coverage
	 *
	 * Which studies' variables the index is missing or holds a different number of: the per-study counterpart of
	 * variables_stats (published variables of published studies on both sides). One row per study on each side, never
	 * per variable, so it stays small at any catalog size; the listed gaps are capped at 200 (`gap_total` says how
	 * many there are), worst first. Best-effort on the index side, as in variables_stats.
	 */
	public function variables_coverage_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$rows = $this->db->query(
			'SELECT v.sid, s.idno, s.title, COUNT(*) AS variables
			 FROM variables v INNER JOIN surveys s ON s.id = v.sid
			 WHERE s.published = 1
			 GROUP BY v.sid, s.idno, s.title'
		)->result_array();

		try
		{
			$index = $this->_probe($this->_client(), 'GET', 'admin/variables/by-study');
		}
		catch (Exception $e)
		{
			$index = array('ok' => false, 'error' => $e->getMessage(), 'status' => null);
		}

		$result = array(
			'status'   => 'success',
			'database' => array('studies' => count($rows)),
			'index'    => array(
				'ok'     => $index['ok'],
				'error'  => $index['error'] ?? null,
				'status' => $index['status'] ?? null,
				'exists' => !empty($index['data']['exists']),
			),
			'gap_total'     => 0,
			'gaps'          => array(),
			'extra_studies' => 0,
		);

		if (!$index['ok'] || empty($index['data']['exists']))
		{
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}

		$indexed = (array) ($index['data']['studies'] ?? array());
		$result['index']['studies'] = count($indexed);
		$seen = array();
		$gaps = array();
		foreach ($rows as $row)
		{
			$sid = (string) $row['sid'];
			$seen[$sid] = true;
			$in_index = (int) ($indexed[$sid] ?? 0);
			$in_db = (int) $row['variables'];
			if ($in_index !== $in_db)
			{
				$gaps[] = array(
					'sid'      => (int) $row['sid'],
					'idno'     => $row['idno'],
					'title'    => $row['title'],
					'database' => $in_db,
					'indexed'  => $in_index,
				);
			}
		}
		usort($gaps, function ($a, $b) {
			return abs($b['database'] - $b['indexed']) <=> abs($a['database'] - $a['indexed']);
		});

		$result['gap_total'] = count($gaps);
		$result['gaps'] = array_slice($gaps, 0, 200);
		$result['extra_studies'] = count(array_diff_key($indexed, $seen));
		$this->set_response($result, REST_Controller::HTTP_OK);
	}

	/**
	 * GET /api/admin/semantic/citations_stats
	 *
	 * Citations in this catalog's database against what nada-ai's citation index holds. Both sides count all citations
	 * and the published ones, since only published citations are searchable. Best-effort on the index side, like
	 * variables_stats: when nada-ai is unreachable, or has no citation index (501), the database
	 * numbers still come back and `index` says why it is missing.
	 */
	public function citations_stats_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$row = $this->db->query(
			'SELECT COUNT(*) AS citations, COALESCE(SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END), 0) AS published FROM citations'
		)->row_array();

		try
		{
			$index = $this->_probe($this->_client(), 'GET', 'admin/citations/stats');
		}
		catch (Exception $e)
		{
			$index = array('ok' => false, 'error' => $e->getMessage(), 'status' => null);
		}

		$this->set_response(array(
			'status'   => 'success',
			'database' => array(
				'citations' => (int) ($row['citations'] ?? 0),
				'published' => (int) ($row['published'] ?? 0),
			),
			'index'    => $index,
		), REST_Controller::HTTP_OK);
	}

	/**
	 * POST /api/admin/semantic/citations_sync
	 * body: {ids?: int[]} — omit ids to sync every citation in the catalog. Runs as a background job in nada-ai; only
	 * the citation index is touched (lexical: nothing is embedded).
	 */
	public function citations_sync_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$payload = array();
		if (isset($body['ids'])) { $payload['ids'] = $body['ids']; }

		$this->_forward('POST', 'admin/citations/sync', array('json' => (object) $payload));
	}

	// =====================================================================
	// Overview
	// =====================================================================

	/**
	 * GET /api/admin/semantic/overview
	 *
	 * Aggregates nada-ai health + embeddings + embedding-drift into one call
	 * so the Overview tab doesn't need three round trips. Best-effort: any one
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
				'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
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
	 * body: {query, type?, from?, to?, limit?, offset?}
	 *
	 * Runs this site's catalog search through nada-ai's OpenSearch study search, in relevance order as the catalog
	 * lists a keyword search. So an admin sees what the catalog would show, including the database's part of it
	 * (fallbacks, dropped hits), with the driver's debug output on — also before nada-ai is the site's search engine.
	 */
	public function search_post()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$this->load->library('search_engine_resolver');
		$this->load->library('nada_ai_link');
		$backend = $this->nada_ai_link->backend();
		$nada_ai_engine = $this->search_engine_resolver->nada_ai_engine_for($backend);
		if ($nada_ai_engine === NULL)
		{
			$message = $backend === NULL
				? 'NADA-AI has not said which engine it runs (is it reachable at nada_ai_url?)'
				: sprintf('NADA-AI is running %s; NADA needs it to run OpenSearch.', $backend);
			$this->set_response(array('status' => 'error', 'message' => $message), REST_Controller::HTTP_BAD_GATEWAY);
			return;
		}
		$body  = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$query = isset($body['query']) ? trim((string) $body['query']) : '';
		if ($query === '')
		{
			$this->set_response(array('status' => 'error', 'message' => 'query required'), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		$limit  = min(100, max(1, (int) ($body['limit'] ?? 15)));
		$offset = max(0, (int) ($body['offset'] ?? 0));
		$params = array(
			'search_engine'   => $nada_ai_engine,
			'semantic_debug'  => true,
			'study_keywords'  => $query,
			'type'            => !empty($body['type']) ? array((string) $body['type']) : array(),
			'from'            => (int) ($body['from'] ?? 0),
			'to'              => (int) ($body['to'] ?? 0),
			'sort_by'         => 'rank',
			'sort_order'      => 'desc',
		);

		require_once APPPATH . 'libraries/Semantic_search_api_exception.php';
		try
		{
			$this->load->library('catalog_search', $params);
			$result = $this->catalog_search->search($limit, $offset);
		}
		catch (Semantic_search_api_exception $e)
		{
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage(), 'semantic_debug' => $e->debug_payload()),
				REST_Controller::HTTP_BAD_GATEWAY
			);
			return;
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		$fields = array('id', 'idno', 'title', 'type', 'nation', 'year_start', 'year_end', 'var_found', 'semantic_hit', 'semantic_document_pages');
		$rows = array();
		foreach ((array) ($result['rows'] ?? array()) as $row)
		{
			$rows[] = array_intersect_key($row, array_flip($fields));
		}

		$this->set_response(array(
			'status'                => 'success',
			'engine'                => $this->search_engine_resolver->expected_backend($nada_ai_engine),
			'found'                 => (int) ($result['found'] ?? 0),
			'limit'                 => $limit,
			'offset'                => $offset,
			'search_counts_by_type' => $result['search_counts_by_type'] ?? array(),
			'semantic_note'         => $result['semantic_note'] ?? null,
			'semantic_fallback'     => $result['semantic_fallback'] ?? null,
			'rows'                  => $rows,
			'debug'                 => $result['debug'] ?? null,
		), REST_Controller::HTTP_OK);
	}

	// =====================================================================
	// Index catalog
	// =====================================================================

	/**
	 * POST /api/admin/semantic/index_drop?confirm=true
	 *
	 * Drops nada-ai's search indexes with no reindex attached — distinct from
	 * index_all_post's recreate_index=true, which always drops-then-reingests
	 * every catalog type in one call. Requires the same 'delete' privilege as
	 * recreate. POST, not DELETE: some networks block DELETE from the browser.
	 */
	public function index_drop_post()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$confirm = $this->input->get('confirm');
		$confirmed = ($confirm === 'true' || $confirm === '1') ? 'true' : 'false';
		$this->_forward('DELETE', 'admin/index', array('query' => array('confirm' => $confirmed)));
	}

	/**
	 * POST /api/admin/semantic/studies_index_recreate?confirm=true
	 *
	 * Drops nada-ai's study index and creates it empty with the current mapping, keeping the chunk index (and its
	 * embeddings) and the variable index. Meant to be followed by search_index_rebuild, which queues every study: they
	 * are indexed again without embedding anything. For a change to what the study index holds.
	 */
	public function studies_index_recreate_post()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$confirm = $this->input->get('confirm');
		$confirmed = ($confirm === 'true' || $confirm === '1') ? 'true' : 'false';
		$this->_forward('POST', 'admin/index/studies/recreate', array('query' => array('confirm' => $confirmed)));
	}

	/**
	 * POST /api/admin/semantic/variables_index_recreate?confirm=true
	 *
	 * Drops nada-ai's variable index, creates it empty with the current mapping and indexes every variable again, as
	 * one nada-ai background job (Activity › Jobs). The chunk, study and citation indexes are kept. For a change to
	 * the variable mapping. Answers 409, dropping nothing, while a whole-catalog variable sync is running.
	 */
	public function variables_index_recreate_post()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$confirm = $this->input->get('confirm');
		$confirmed = ($confirm === 'true' || $confirm === '1') ? 'true' : 'false';
		$this->_forward('POST', 'admin/index/variables/recreate', array('query' => array('confirm' => $confirmed)));
	}

	/**
	 * POST /api/admin/semantic/citations_index_recreate?confirm=true
	 *
	 * Drops nada-ai's citation index, creates it empty with the current mapping and indexes every citation again, as
	 * one nada-ai background job (Activity › Jobs). The chunk, study and variable indexes are kept. Answers 409,
	 * dropping nothing, while a whole-catalog citation sync is running.
	 */
	public function citations_index_recreate_post()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$confirm = $this->input->get('confirm');
		$confirmed = ($confirm === 'true' || $confirm === '1') ? 'true' : 'false';
		$this->_forward('POST', 'admin/index/citations/recreate', array('query' => array('confirm' => $confirmed)));
	}

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
	 * POST /api/admin/semantic/variables_sync
	 * body: {idnos?: string[]} — omit idnos to sync every variable in the catalog. Runs as a background job in
	 * nada-ai; only the variable index is touched (no study document, no chunks, no embeddings).
	 */
	public function variables_sync_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		$body = json_decode($this->input->raw_input_stream ?: '{}', true) ?: array();
		$payload = array();
		if (isset($body['idnos'])) { $payload['idnos'] = $body['idnos']; }

		$this->_forward('POST', 'admin/variables/sync', array('json' => (object) $payload));
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
			'timeseriesdb' => 'indicator-db',
			'indicator'    => 'indicator',
			'document'     => 'document',
			'geospatial'   => 'geospatial',
			'survey'       => 'microdata',
			'microdata'    => 'microdata',
			'table'        => 'table',
			'script'       => 'script',
			'image'        => 'image',
			'video'        => 'video',
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

	/** POST /api/admin/semantic/job_cancel/{id} — cancel a running/pending job (POST: DELETE is not always allowed) */
	public function job_cancel_post($id = null)
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
		$this->_forward('POST', 'jobs/' . rawurlencode($id) . '/cancel');
	}

	// =====================================================================
	// Sync status (NADA search-index change queue)
	// =====================================================================

	/** GET /api/admin/semantic/search_index_status — NADA's tracking status, read directly */
	public function search_index_status_get()
	{
		$this->_search_index_read(function ($manager) {
			return $manager->status();
		});
	}

	/**
	 * GET /api/admin/semantic/scheduler_status
	 *
	 * NADA-AI's queue scheduler as it reports it (state, pause reason, current item, heartbeat). nada-ai answers it
	 * from memory, without calling NADA back.
	 */
	public function scheduler_status_get()
	{
		try { $this->_require('view'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('GET', 'admin/scheduler/status');
	}

	/** POST /api/admin/semantic/scheduler_pause — NADA-AI finishes the item in hand, then starts nothing until resumed */
	public function scheduler_pause_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('POST', 'admin/scheduler/pause');
	}

	/** POST /api/admin/semantic/scheduler_resume — NADA-AI carries on from the change queue */
	public function scheduler_resume_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('POST', 'admin/scheduler/resume');
	}

	/**
	 * POST /api/admin/semantic/search_index_sync?object_type=survey|citation|all&refresh_indexed=0|1&data_type=&remove_only=0|1
	 *
	 * Queue what NADA-AI's index needs to match the catalog (Search_index_manager::sync()), in NADA: NADA-AI then works
	 * through the queue.
	 */
	public function search_index_sync_post()
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
			$object_type = $this->input->get('object_type');
			$refresh     = $this->input->get('refresh_indexed');
			$data_type   = $this->input->get('data_type');
			$remove_only = $this->input->get('remove_only');
			$counts = $this->search_index_manager->sync(
				($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'all',
				in_array($refresh, array('1', 'true', 'yes'), true),
				($data_type !== false && $data_type !== null && $data_type !== '') ? (string) $data_type : null,
				in_array($remove_only, array('1', 'true', 'yes'), true)
			);
			$this->set_response(array('status' => 'success', 'queued' => $counts), REST_Controller::HTTP_OK);
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
			log_message('error', 'Semantic search_index_sync: ' . $e->getMessage());
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * POST /api/admin/semantic/search_index_rebuild?queue=1|0
	 *
	 * After NADA-AI's index was emptied: clear NADA's tracking, and with queue=1 (Delete and rebuild) queue every study
	 * and citation.
	 */
	public function search_index_rebuild_post()
	{
		try { $this->_require('delete'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		try
		{
			$this->load->library('Search_index_manager');
			$counts = $this->search_index_manager->rebuild($this->input->get('queue') !== '0');
			$this->set_response(array('status' => 'success', 'queued' => $counts), REST_Controller::HTTP_OK);
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
			log_message('error', 'Semantic search_index_rebuild: ' . $e->getMessage());
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * POST /api/admin/semantic/search_index_import
	 *
	 * NADA-AI reports what its index already holds (a job), then NADA queues what is left to do. For switching to a
	 * NADA-AI whose index exists, so it is not rebuilt.
	 */
	public function search_index_import_post()
	{
		try { $this->_require('edit'); }
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
			return;
		}
		$this->_forward('POST', 'admin/search-index/import');
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

	/** GET /api/admin/semantic/search_index_recent?limit=10 — catalog changes waiting, and the latest results */
	public function search_index_recent_get()
	{
		$this->_search_index_read(function ($manager) {
			$limit = $this->input->get('limit');
			return $manager->recent_changes(($limit !== false && $limit !== null && $limit !== '') ? (int) $limit : 10);
		});
	}

	/**
	 * POST /api/admin/semantic/search_index_queue_variables  body: {sids: [..]}
	 *
	 * Queue a variables-only update of these studies (the variables the index is missing).
	 */
	public function search_index_queue_variables_post()
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
			$sids = (is_array($body) && isset($body['sids']) && is_array($body['sids'])) ? $body['sids'] : array();
			$queued = $this->search_index_manager->queue_variables($sids);
			$this->set_response(array('status' => 'success', 'queued' => $queued), REST_Controller::HTTP_OK);
		}
		catch (Search_index_exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $e->http_code ?: REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e)
		{
			log_message('error', 'Semantic search_index_queue_variables: ' . $e->getMessage());
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * GET /api/admin/semantic/search_index_diff_summary?object_type=survey
	 *
	 * Cheap missing/stale counts — how many catalog entries aren't indexed, how many indexed entries are no longer in
	 * the catalog. Read from NADA's tracking tables directly (not through nada-ai, which would only ask NADA back).
	 */
	public function search_index_diff_summary_get()
	{
		$this->_search_index_read(function ($manager) {
			$q = $this->_diff_list_query();
			$summary = $manager->object_type_summary($q['object_type']);
			return $summary + array(
				'missing_total' => $manager->diff_missing($q['object_type'], 1)['total'],
				'stale_total'   => $manager->diff_stale($q['object_type'], 1)['total'],
			);
		});
	}

	/**
	 * GET /api/admin/semantic/search_index_type_breakdown?object_type=survey
	 *
	 * Per-data-type (microdata/geospatial/document/timeseries/...) catalog vs. index coverage, from NADA directly.
	 */
	public function search_index_type_breakdown_get()
	{
		$this->_search_index_read(function ($manager) {
			$q = $this->_diff_list_query();
			return array('object_type' => $q['object_type'], 'items' => $manager->type_breakdown($q['object_type']));
		});
	}

	/** GET /api/admin/semantic/search_index_diff_missing?object_type=survey&limit=&offset=&data_type=&has_error= */
	public function search_index_diff_missing_get()
	{
		$this->_search_index_read(function ($manager) {
			$q = $this->_diff_list_query();
			return $manager->diff_missing($q['object_type'], $q['limit'], $q['offset'], $q['data_type'], $q['has_error']);
		});
	}

	/** GET /api/admin/semantic/search_index_diff_stale?object_type=survey&limit=&offset=&data_type= */
	public function search_index_diff_stale_get()
	{
		$this->_search_index_read(function ($manager) {
			$q = $this->_diff_list_query();
			return $manager->diff_stale($q['object_type'], $q['limit'], $q['offset'], $q['data_type']);
		});
	}

	/**
	 * Run one read of NADA's tracking tables for the dashboard, with the access check and NADA's error codes
	 * (INDEX_ENGINE_NOT_NADA_AI, ...) passed on as they are.
	 */
	private function _search_index_read(callable $read)
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
			$this->set_response(array('status' => 'success') + $read($this->search_index_manager), REST_Controller::HTTP_OK);
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
			log_message('error', 'Semantic search index read: ' . $e->getMessage());
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/** The object_type/limit/offset/data_type/has_error of the diff and coverage reads above. */
	private function _diff_list_query()
	{
		$object_type = $this->input->get('object_type');
		$limit       = $this->input->get('limit');
		$offset      = $this->input->get('offset');
		$data_type   = $this->input->get('data_type');
		$has_error   = $this->input->get('has_error');
		return array(
			'object_type' => ($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey',
			'limit'       => ($limit !== false && $limit !== null && $limit !== '') ? (int) $limit : 100,
			'offset'      => ($offset !== false && $offset !== null && $offset !== '') ? (int) $offset : 0,
			'data_type'   => ($data_type !== false && $data_type !== null && $data_type !== '') ? (string) $data_type : null,
			'has_error'   => in_array($has_error, array('1', 'true', 'yes'), true),
		);
	}

	/**
	 * POST /api/admin/semantic/search_index_reconcile_diff?object_type=survey&data_type=&remove_only=
	 *
	 * Index every catalog entry that isn't currently indexed, delete every
	 * indexed entry no longer in the catalog — same call whether this is a
	 * first-ever backfill or routine later reconciliation. ``data_type``,
	 * when given, narrows the run to one surveys.type value (microdata aka
	 * 'survey', geospatial, document, timeseries, ...) instead of all of
	 * object_type at once. ``remove_only=1`` (the dashboard's Clean up) only
	 * deletes, and indexes nothing. Runs as a background job on the nada-ai
	 * side; watch it on the Jobs tab.
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
		if (in_array($this->input->get('remove_only'), array('1', 'true', 'yes'), true)) { $query['remove_only'] = 'true'; }
		$this->_forward('POST', 'admin/search-index/reconcile-diff', array('query' => $query));
	}
}
