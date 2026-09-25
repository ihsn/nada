<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — catalog search metadata extract for external indexers.
 *
 * Study ref is IDNO by default; pass query `id_format=id` for numeric surveys.id.
 *
 * Routes:
 *   GET /api/admin/search-metadata-extract/status
 *   GET /api/admin/search-metadata-extract/studies/{idno}?include_metadata=1&include_admin_metadata=1
 *   GET /api/admin/search-metadata-extract/studies?offset=0&limit=15&type=timeseries,survey
 *   GET /api/admin/search-metadata-extract/citations/{id}
 *   GET /api/admin/search-metadata-extract/citations?limit=500&after_id= — the whole catalog of citations, paged
 *   GET /api/admin/search-metadata-extract/variables/{idno} — every variable of one study
 *   GET /api/admin/search-metadata-extract/variables?offset=0&limit=200&type=survey,timeseries
 */
class Search_metadata_extract extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->library('catalog_search_metadata_extract');
	}

	/**
	 * GET /api/admin/search-metadata-extract/status
	 */
	public function status_get()
	{
		try {
			$this->_require_admin_catalog_access();

			$total = (int) $this->db->count_all('surveys');
			$published = (int) $this->db->where('published', 1)->count_all_results('surveys');

			$this->set_response(
				array(
					'status' => 'success',
					'counts' => array(
						'studies'           => $total,
						'studies_published' => $published,
						'citations'         => (int) $this->db->count_all('citations'),
						'variables'         => (int) $this->db->count_all('variables'),
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-metadata-extract/studies/{idno}
	 * GET /api/admin/search-metadata-extract/studies?offset=&limit=
	 */
	public function studies_get($idno = null)
	{
		try {
			if ($idno === null || $idno === '') {
				$this->_studies_batch_get();
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$study = $this->catalog_search_metadata_extract->build_study_document(
				(int) $sid,
				$this->_study_extract_options()
			);
			if ($study === null) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$this->set_response(
				$this->_study_response($study),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$code = ($e->getMessage() === 'STUDY_NOT_FOUND')
				? REST_Controller::HTTP_NOT_FOUND
				: REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * GET /api/admin/search-metadata-extract/citations/{id}
	 */
	public function citations_get($id = null)
	{
		if ($id === null || $id === '') {
			return $this->_citations_batch_get();
		}

		try {
			$this->_require_admin_catalog_access();

			$citation_id = (int) $id;
			if ($citation_id <= 0) {
				throw new Exception('CITATION_ID_REQUIRED');
			}

			$citation = $this->catalog_search_metadata_extract->build_citation_document($citation_id);
			if ($citation === null) {
				throw new Exception('CITATION_NOT_FOUND');
			}

			$this->set_response(
				array(
					'status'   => 'success',
					'citation' => $citation,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$code = ($e->getMessage() === 'CITATION_NOT_FOUND')
				? REST_Controller::HTTP_NOT_FOUND
				: REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * GET /api/admin/search-metadata-extract/variables/{idno}?limit=1000&after_uid=  — one study's variables, paged
	 * GET /api/admin/search-metadata-extract/variables?limit=1000&after_uid=&type=survey — the whole catalog, paged
	 *
	 * Both are paged the same way: `limit` (capped by search_metadata_extract_variables_max_limit, which is also the
	 * default), and either `after_uid` (keyset cursor: pass the previous page's `next_after_uid`) or `offset`. A page
	 * says whether another follows (`has_more`) and where it starts (`next_after_uid`). `total` is only computed on the
	 * first page of a walk (no cursor, offset 0) and is null after that.
	 */
	public function variables_get($idno = null)
	{
		try {
			if ($idno === null || $idno === '') {
				$this->_variables_batch_get();
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			list($limit, $offset, $after_uid) = $this->_variables_page_params();
			$page = $this->catalog_search_metadata_extract->build_variables_by_survey((int) $sid, $limit, $offset, $after_uid);

			$this->set_response(
				array(
					'status'         => 'success',
					'found'          => count($page['variables']),
					'total'          => $page['total'],
					'limit'          => $page['limit'],
					'offset'         => $page['offset'],
					'has_more'       => $page['has_more'],
					'next_after_uid' => $page['next_after_uid'],
					'variables'      => $page['variables'],
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-metadata-extract/citations?limit=500&after_id=
	 *
	 * Every citation, a page at a time: `limit` (capped by search_metadata_extract_citations_max_limit, also the
	 * default), and either `after_id` (keyset cursor: the previous page's `next_after_id`) or `offset`. `total` is only
	 * counted on the first page.
	 */
	private function _citations_batch_get()
	{
		try {
			$this->_require_admin_catalog_access();

			$this->config->load('search_metadata_extract');
			$max_limit = (int) $this->config->item('search_metadata_extract_citations_max_limit') ?: 500;
			$limit = (int) $this->input->get('limit');
			$limit = ($limit <= 0) ? $max_limit : min($limit, $max_limit);
			$offset = max(0, (int) $this->input->get('offset'));
			$after_raw = $this->input->get('after_id');
			$after_id = ($after_raw !== false && $after_raw !== null && $after_raw !== '') ? max(0, (int) $after_raw) : null;

			$batch = $this->catalog_search_metadata_extract->build_citation_batch($limit, $offset, $after_id);

			$this->set_response(
				array(
					'status'        => 'success',
					'offset'        => $batch['offset'],
					'limit'         => $batch['limit'],
					'total'         => $batch['total'],
					'has_more'      => $batch['has_more'],
					'next_after_id' => $batch['next_after_id'],
					'citations'     => $batch['citations'],
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * limit / offset / after_uid of a variables page. Variables are small flat rows, so they have their own, larger
	 * page-size cap than the study documents (see config/search_metadata_extract.php).
	 *
	 * @return array{0: int, 1: int, 2: int|null}
	 */
	private function _variables_page_params()
	{
		$this->config->load('search_metadata_extract');
		$max_limit = (int) $this->config->item('search_metadata_extract_variables_max_limit') ?: 1000;

		$limit = (int) $this->input->get('limit');
		$limit = ($limit <= 0) ? $max_limit : min($limit, $max_limit);

		$offset    = max(0, (int) $this->input->get('offset'));
		$after_raw = $this->input->get('after_uid');
		$after_uid = ($after_raw !== false && $after_raw !== null && $after_raw !== '') ? max(0, (int) $after_raw) : null;

		return array($limit, $offset, $after_uid);
	}

	/**
	 * @return void
	 */
	private function _variables_batch_get()
	{
		$this->_require_admin_catalog_access();

		list($limit, $offset, $after_uid) = $this->_variables_page_params();

		$options = $this->_study_batch_filters();
		if ($after_uid !== null) {
			$options['after_uid'] = $after_uid;
		}
		$batch = $this->catalog_search_metadata_extract->build_variable_batch($offset, $limit, $options);

		$this->set_response(
			array(
				'status'         => 'success',
				'offset'         => $batch['offset'],
				'limit'          => $batch['limit'],
				'total'          => $batch['total'],
				'has_more'       => $batch['has_more'],
				'next_after_uid' => $batch['next_after_uid'],
				'variables'      => $batch['variables'],
			),
			REST_Controller::HTTP_OK
		);
	}

	private function _studies_batch_get()
	{
		$this->_require_admin_catalog_access();

		$this->config->load('search_metadata_extract');
		$default_limit = (int) $this->config->item('search_metadata_extract_default_limit') ?: 50;
		$max_limit     = (int) $this->config->item('search_metadata_extract_max_limit') ?: 100;

		$offset = max(0, (int) $this->input->get('offset'));
		$limit  = (int) $this->input->get('limit');
		if ($limit <= 0) {
			$limit = $default_limit;
		}
		$limit = min($limit, $max_limit);

		$batch = $this->catalog_search_metadata_extract->build_study_batch(
			$offset,
			$limit,
			$this->_study_extract_options() + $this->_study_batch_filters()
		);

		$this->set_response(
			array(
				'status'   => 'success',
				'offset'   => $batch['offset'],
				'limit'    => $batch['limit'],
				'total'    => $batch['total'],
				'has_more' => $batch['has_more'],
				'studies'  => $batch['studies'],
			),
			REST_Controller::HTTP_OK
		);
	}

	/**
	 * @param array $study
	 * @return array
	 */
	private function _study_response(array $study)
	{
		return array(
			'status' => 'success',
			'study'  => $study,
		);
	}

	/**
	 * @throws AclAccessDeniedException
	 */
	private function _require_admin_catalog_access()
	{
		$user = $this->api_user();
		if (!$user) {
			throw new AclAccessDeniedException('ACCESS_DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS_DENIED');
		}
	}

	/**
	 * @return array
	 */
	private function _study_batch_filters()
	{
		$filters = array();

		$raw = $this->input->get('type');
		if ($raw !== false && $raw !== null && $raw !== '') {
			$types = array_filter(array_map('trim', explode(',', (string) $raw)));
			if (!empty($types)) {
				$filters['types'] = $types;
			}
		}

		return $filters;
	}

	/**
	 * @return array
	 */
	private function _study_extract_options()
	{
		$options = array();

		$raw = $this->input->get('include_metadata');
		if ($raw !== false && $raw !== null && $raw !== '') {
			$options['include_metadata'] = $raw;
		}

		$raw = $this->input->get('include_admin_metadata');
		if ($raw !== false && $raw !== null && $raw !== '') {
			$options['include_admin_metadata'] = $raw;
		}

		$raw = $this->input->get('include_resources');
		if ($raw !== false && $raw !== null && $raw !== '') {
			$options['include_resources'] = $raw;
		}

		return $options;
	}
}
