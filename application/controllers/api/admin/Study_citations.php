<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — citations attached to a single study (catalog edit tab).
 *
 * Study ref is IDNO by default; pass query `id_format=id` for numeric `surveys.id`.
 *
 * Routes:
 *   GET    /api/admin/catalog/{idno}/citations
 *   GET    /api/admin/catalog/{idno}/citations/search
 *   POST   /api/admin/catalog/{idno}/citations/{citation_id}
 *   DELETE /api/admin/catalog/{idno}/citations/{citation_id}
 */
class Study_citations extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper('date');
		$this->load->library('acl_manager');
		$this->load->library('chicago_citation');
		$this->load->model('Citation_model');
	}

	/**
	 * List attached citations for one study.
	 */
	public function index_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$sort_by = $this->input->get('sort_by');
			$sort_order = $this->input->get('sort_order');
			$sort_by = is_string($sort_by) ? trim($sort_by) : null;
			$sort_order = is_string($sort_order) ? trim($sort_order) : null;

			$rows = $this->Citation_model->get_citations_by_survey($sid, $sort_by, $sort_order);
			if (! is_array($rows)) {
				$rows = array();
			}

			$payload = $this->_format_citation_rows($rows, true);

			$this->set_response(
				array(
					'status'    => 'success',
					'total'     => count($payload),
					'citations' => $payload,
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
	 * Search citations and mark whether each citation is attached to this study.
	 */
	public function search_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$limit = (int) $this->input->get('limit');
			$offset = (int) $this->input->get('offset');
			$limit = ($limit >= 1 && $limit <= 300) ? $limit : 30;
			$offset = $offset > 0 ? $offset : 0;

			$sort_by = $this->input->get('sort_by');
			$sort_order = $this->input->get('sort_order');
			$sort_by = is_string($sort_by) ? trim($sort_by) : null;
			$sort_order = is_string($sort_order) ? trim($sort_order) : null;

			$filter = array(
				'keywords' => $this->input->get('keywords'),
			);

			$rows = $this->Citation_model->search($limit, $offset, $filter, $sort_by, $sort_order, $published = null);
			if (! is_array($rows)) {
				$rows = array();
			}

			$selected_ids = $this->Citation_model->get_citations_id_array_by_survey($sid);
			$selected_map = array();
			foreach ((array) $selected_ids as $cid) {
				$selected_map[(int) $cid] = true;
			}

			$payload = $this->_format_citation_rows($rows, false, $selected_map);
			$total = (int) $this->Citation_model->search_count();

			$this->set_response(
				array(
					'status'    => 'success',
					'total'     => $total,
					'found'     => count($payload),
					'offset'    => $offset,
					'limit'     => $limit,
					'citations' => $payload,
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
	 * Attach one citation to study.
	 */
	public function index_post($idno = null, $citation_id = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$cid = (int) $citation_id;
			if ($cid < 1) {
				throw new Exception('INVALID_CITATION_ID');
			}

			$row = $this->Citation_model->select_single($cid);
			if (! is_array($row)) {
				throw new Exception('CITATION_NOT_FOUND');
			}

			$this->Citation_model->attach_related_surveys($cid, array($sid));

			$this->set_response(
				array(
					'status'      => 'success',
					'survey_id'   => (int) $sid,
					'citation_id' => $cid,
					'attached'    => true,
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
	 * Detach one citation from study.
	 */
	public function index_delete($idno = null, $citation_id = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$cid = (int) $citation_id;
			if ($cid < 1) {
				throw new Exception('INVALID_CITATION_ID');
			}

			$this->Citation_model->delete_related_survey($cid, $sid);

			$this->set_response(
				array(
					'status'      => 'success',
					'survey_id'   => (int) $sid,
					'citation_id' => $cid,
					'attached'    => false,
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
	 * @param array $rows citation rows
	 * @param bool  $force_attached true for attached-list endpoint
	 * @param array $attached_map citation id => true
	 * @return array
	 */
	private function _format_citation_rows($rows, $force_attached = false, $attached_map = array())
	{
		$out = array();
		foreach ((array) $rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			$cid = isset($row['id']) ? (int) $row['id'] : 0;
			$fmt = '';
			try {
				$fmt = $this->chicago_citation->format($row, 'journal', false);
			}
			catch (Exception $e) {
				unset($e);
				$fmt = '';
			}

			$attached = $force_attached ? true : isset($attached_map[$cid]);
			$out[] = array(
				'id'                 => $cid,
				'uuid'               => isset($row['uuid']) ? $row['uuid'] : null,
				'title'              => isset($row['title']) ? $row['title'] : '',
				'authors'            => isset($row['authors']) ? $row['authors'] : '',
				'pub_year'           => isset($row['pub_year']) ? $row['pub_year'] : null,
				'doi'                => isset($row['doi']) ? $row['doi'] : null,
				'published'          => isset($row['published']) ? (int) $row['published'] : null,
				'changed'            => isset($row['changed']) ? $row['changed'] : null,
				'formatted_citation' => $fmt,
				'is_attached'        => $attached,
			);
		}

		array_walk($out, 'unix_date_to_gmt', array('changed'));
		return $out;
	}
}

