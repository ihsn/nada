<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — study notes for catalog edit tab.
 *
 * Study ref is IDNO by default; pass query `id_format=id` for numeric `surveys.id`.
 *
 * Routes:
 *   GET    /api/admin/catalog/{idno}/notes
 *   POST   /api/admin/catalog/{idno}/notes
 *   PUT    /api/admin/catalog/{idno}/notes/{note_id}
 *   DELETE /api/admin/catalog/{idno}/notes/{note_id}
 */
class Study_notes extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper('date');
		$this->load->library('acl_manager');
		$this->load->model('Catalog_notes_model');
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

			$type = $this->input->get('type');
			$type = is_string($type) ? trim($type) : null;
			if ($type === '') {
				$type = null;
			}

			$rows = $this->Catalog_notes_model->get_notes_by_study($sid, $type);
			if (! is_array($rows)) {
				$rows = array();
			}

			$payload = $this->_format_notes($rows, $user);

			$this->set_response(
				array(
					'status' => 'success',
					'total'  => count($payload),
					'notes'  => $payload,
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

			$note = isset($options['note']) ? trim((string) $options['note']) : '';
			$type = isset($options['type']) ? trim((string) $options['type']) : '';
			if ($note === '' || $type === '') {
				throw new Exception('NOTE_AND_TYPE_REQUIRED');
			}
			if (! in_array($type, array('admin', 'reviewer', 'public'), true)) {
				throw new Exception('INVALID_NOTE_TYPE');
			}

			$insert = array(
				'sid'     => (int) $sid,
				'note'    => $note,
				'type'    => $type,
				'userid'  => (int) $this->session->userdata('user_id'),
				'created' => date('U'),
			);
			$this->Catalog_notes_model->insert($insert);

			$rows = $this->Catalog_notes_model->get_notes_by_study($sid);
			$payload = $this->_format_notes((array) $rows, $user);

			$this->set_response(
				array(
					'status' => 'success',
					'total'  => count($payload),
					'notes'  => $payload,
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

	public function index_put($idno = null, $note_id = null)
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

			$nid = (int) $note_id;
			if ($nid < 1) {
				throw new Exception('INVALID_NOTE_ID');
			}

			$existing = $this->Catalog_notes_model->single($nid);
			if (! is_array($existing)) {
				throw new Exception('NOTE_NOT_FOUND');
			}
			if ((int) $existing['sid'] !== (int) $sid) {
				throw new Exception('NOTE_NOT_IN_STUDY');
			}

			$options = (array) $this->raw_json_input();
			if (empty($options)) {
				$options = (array) $this->input->post(null, true);
			}

			$update = array(
				'changed' => date('U'),
				'userid'  => (int) $this->session->userdata('user_id'),
			);

			if (array_key_exists('note', $options)) {
				$note = trim((string) $options['note']);
				if ($note === '') {
					throw new Exception('NOTE_REQUIRED');
				}
				$update['note'] = $note;
			}
			if (array_key_exists('type', $options)) {
				$type = trim((string) $options['type']);
				if (! in_array($type, array('admin', 'reviewer', 'public'), true)) {
					throw new Exception('INVALID_NOTE_TYPE');
				}
				$update['type'] = $type;
			}

			$this->Catalog_notes_model->update($nid, $update);
			$row = $this->Catalog_notes_model->single($nid);
			$payload = $this->_format_notes(array($row), $user);

			$this->set_response(
				array('status' => 'success', 'note' => isset($payload[0]) ? $payload[0] : null),
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

	public function update_post($idno = null, $note_id = null)
	{
		return $this->index_put($idno, $note_id);
	}

	public function index_delete($idno = null, $note_id = null)
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

			$nid = (int) $note_id;
			if ($nid < 1) {
				throw new Exception('INVALID_NOTE_ID');
			}

			$existing = $this->Catalog_notes_model->single($nid);
			if (! is_array($existing)) {
				throw new Exception('NOTE_NOT_FOUND');
			}
			if ((int) $existing['sid'] !== (int) $sid) {
				throw new Exception('NOTE_NOT_IN_STUDY');
			}

			$is_owner = $this->Catalog_notes_model->is_note_owner($nid, (int) $this->session->userdata('user_id'));
			$is_admin = $this->ion_auth->is_admin($this->get_api_user_id());
			if (! $is_owner && ! $is_admin) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$this->Catalog_notes_model->delete($nid);
			$this->set_response(array('status' => 'success', 'deleted_id' => $nid), REST_Controller::HTTP_OK);
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
	 * @param array $rows
	 * @param object $viewer
	 * @return array
	 */
	private function _format_notes($rows, $viewer)
	{
		$out = array();
		foreach ((array) $rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			$uid = isset($row['userid']) ? (int) $row['userid'] : 0;
			$u = $uid > 0 ? $this->ion_auth->get_user($uid) : null;

			$out[] = array(
				'id'          => isset($row['id']) ? (int) $row['id'] : 0,
				'sid'         => isset($row['sid']) ? (int) $row['sid'] : null,
				'type'        => isset($row['type']) ? $row['type'] : '',
				'note'        => isset($row['note']) ? $row['note'] : '',
				'created'     => isset($row['created']) ? $row['created'] : null,
				'changed'     => isset($row['changed']) ? $row['changed'] : null,
				'userid'      => $uid,
				'username'    => ($u && isset($u->username)) ? $u->username : '',
				'is_owner'    => ($viewer && isset($viewer->id)) ? ((int) $viewer->id === $uid) : false,
			);
		}

		array_walk($out, 'unix_date_to_gmt', array('created'));
		array_walk($out, 'unix_date_to_gmt', array('changed'));
		return $out;
	}
}

