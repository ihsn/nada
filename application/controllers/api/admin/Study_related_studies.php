<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — related studies / related data for catalog edit tab.
 *
 * Study ref is IDNO by default; pass query `id_format=id` for numeric `surveys.id`.
 *
 * Routes:
 *   GET    /api/admin/catalog/{idno}/related-studies
 *   GET    /api/admin/catalog/{idno}/related-studies/search
 *   POST   /api/admin/catalog/{idno}/related-studies
 *   PATCH  /api/admin/catalog/{idno}/related-studies/{related_sid}
 *   DELETE /api/admin/catalog/{idno}/related-studies/{related_sid}
 */
class Study_related_studies extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->model('Related_study_model');
		$this->load->model('Catalog_admin_search');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}

		return parent::_auth_override_check();
	}

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

			$types_map = $this->Related_study_model->get_relationship_types_array();
			$relationship_types = array();
			foreach ((array) $types_map as $tid => $label) {
				$relationship_types[] = array(
					'id'    => (int) $tid,
					'label' => (string) $label,
				);
			}

			$rows = $this->Related_study_model->get_relationships($sid);
			if (! is_array($rows)) {
				$rows = array();
			}

			$related_studies = array();
			foreach ($rows as $row) {
				if (! is_array($row)) {
					continue;
				}
				$rel_sid = isset($row['sid']) ? (int) $row['sid'] : (isset($row['sid_2']) ? (int) $row['sid_2'] : 0);
				if ($rel_sid < 1) {
					continue;
				}
				$related_studies[] = array(
					'related_sid'       => $rel_sid,
					'idno'              => isset($row['idno']) ? $row['idno'] : '',
					'title'             => isset($row['title']) ? $row['title'] : '',
					'nation'            => isset($row['nation']) ? $row['nation'] : '',
					'year_start'        => isset($row['year_start']) ? $row['year_start'] : '',
					'relationship_id'   => isset($row['relationship_id']) ? (int) $row['relationship_id'] : 0,
				);
			}

			$this->set_response(
				array(
					'status'             => 'success',
					'total'              => count($related_studies),
					'related_studies'    => $related_studies,
					'relationship_types' => $relationship_types,
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
	 * Search catalog studies to attach; marks rows already related to this study.
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

			$allowed_fields = array('title', 'nation', 'idno', 'year_start', 'authoring_entity');
			$field = $this->input->get('field');
			$field = is_string($field) ? trim($field) : '';
			if ($field === '' || ! in_array($field, $allowed_fields, true)) {
				$field = 'title';
			}

			$keywords = $this->input->get('keywords');
			$keywords = is_string($keywords) ? trim($keywords) : '';

			$search_options = array();
			if ($keywords !== '') {
				$search_options[$field] = $keywords;
			}

			$attached_ids = $this->Related_study_model->get_related_studies_id_list($sid);
			$search_options['exclude_survey_ids'] = array((int) $sid);

			$this->Catalog_admin_search->apply_session_user_acl_scope();

			$rows = $this->Catalog_admin_search->search($search_options, $limit, $offset);
			if (! is_array($rows)) {
				$rows = array();
			}

			$attached_map = array();
			foreach ((array) $attached_ids as $aid) {
				$attached_map[(int) $aid] = true;
			}

			$rel_by_related_sid = array();
			$rel_rows = $this->Related_study_model->get_relationships($sid);
			foreach ((array) $rel_rows as $rr) {
				if (! is_array($rr)) {
					continue;
				}
				$child_sid = isset($rr['sid']) ? (int) $rr['sid'] : 0;
				if ($child_sid < 1) {
					continue;
				}
				$rel_by_related_sid[$child_sid] = isset($rr['relationship_id']) ? (int) $rr['relationship_id'] : 0;
			}

			$studies = array();
			foreach ($rows as $row) {
				if (! is_array($row)) {
					continue;
				}
				$rid = isset($row['id']) ? (int) $row['id'] : 0;
				if ($rid < 1) {
					continue;
				}
				$is_attached = isset($attached_map[$rid]);
				$studies[] = array(
					'id'               => $rid,
					'idno'             => isset($row['idno']) ? $row['idno'] : '',
					'title'            => isset($row['title']) ? $row['title'] : '',
					'nation'           => isset($row['nation']) ? $row['nation'] : '',
					'year_start'       => isset($row['year_start']) ? $row['year_start'] : '',
					'is_attached'      => $is_attached,
					'relationship_id'  => $is_attached && isset($rel_by_related_sid[$rid]) ? $rel_by_related_sid[$rid] : 0,
				);
			}

			$total = (int) $this->Catalog_admin_search->get_search_count();

			$this->set_response(
				array(
					'status'  => 'success',
					'total'   => $total,
					'found'   => count($studies),
					'offset'  => $offset,
					'limit'   => $limit,
					'studies' => $studies,
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
	 * Attach one related study (JSON: related_sid | related_idno, relationship_id).
	 */
	public function index_post($idno = null)
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

			$options = (array) $this->raw_json_input();
			if (empty($options)) {
				$options = (array) $this->input->post(null, true);
			}

			$rel_id = isset($options['relationship_id']) ? (int) $options['relationship_id'] : 0;

			$related_sid = null;
			if (isset($options['related_sid'])) {
				$related_sid = (int) $options['related_sid'];
			} elseif (isset($options['related_idno'])) {
				$this->load->library('Dataset_manager');
				$related_sid = (int) $this->dataset_manager->find_by_idno(trim((string) $options['related_idno']));
			}

			if ($related_sid === null || $related_sid < 1) {
				throw new Exception('RELATED_STUDY_REQUIRED');
			}
			if ($related_sid === (int) $sid) {
				throw new Exception('CANNOT_RELATE_SELF');
			}

			$this->has_dataset_access('view', $related_sid);

			$this->Related_study_model->update_relationship($sid, array($related_sid), $rel_id);

			$this->set_response(
				array(
					'status'       => 'success',
					'survey_id'    => (int) $sid,
					'related_sid'  => $related_sid,
					'relationship_id' => $rel_id,
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
	 * Update relationship type for one related study (JSON: relationship_id).
	 */
	public function index_patch($idno = null, $related_sid = null)
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

			$target = (int) $related_sid;
			if ($target < 1) {
				throw new Exception('INVALID_RELATED_SID');
			}

			$options = $this->patch();
			if (! is_array($options)) {
				$options = array();
			}
			if (! array_key_exists('relationship_id', $options)) {
				throw new Exception('RELATIONSHIP_ID_REQUIRED');
			}
			$rel_id = (int) $options['relationship_id'];

			$this->Related_study_model->update_relationship($sid, array($target), $rel_id);

			$this->set_response(
				array(
					'status'          => 'success',
					'survey_id'       => (int) $sid,
					'related_sid'     => $target,
					'relationship_id' => $rel_id,
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

	public function index_delete($idno = null, $related_sid = null)
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

			$target = (int) $related_sid;
			if ($target < 1) {
				throw new Exception('INVALID_RELATED_SID');
			}

			$this->Related_study_model->delete_relationship($sid, $target, null);

			$this->set_response(
				array(
					'status'      => 'success',
					'survey_id'   => (int) $sid,
					'related_sid' => $target,
					'deleted'     => true,
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
}
