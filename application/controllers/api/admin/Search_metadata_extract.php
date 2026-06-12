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
 *   GET /api/admin/search-metadata-extract/studies?offset=0&limit=15
 *   GET /api/admin/search-metadata-extract/citations/{id}
 *   GET /api/admin/search-metadata-extract/variables/{idno} — not implemented
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
	 * GET /api/admin/search-metadata-extract/variables/{idno}
	 */
	public function variables_get($idno = null)
	{
		$this->set_response(
			array(
				'status'  => 'failed',
				'message' => 'NOT_IMPLEMENTED',
			),
			REST_Controller::HTTP_NOT_IMPLEMENTED
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
			$this->_study_extract_options()
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

		return $options;
	}
}
