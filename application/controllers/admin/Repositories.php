<?php
/**
 * Legacy admin/repositories URLs — collection management UI is admin/collections (Vue).
 * This controller redirects to the hash routes and keeps minimal compatibility endpoints.
 */
class Repositories extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('repository_model');
	}

	/** Base URL for the Vue collections app (hash history). */
	private function vue_collections_base()
	{
		return rtrim(site_url('admin/collections'), '/');
	}

	private function ajax_requested()
	{
		return $this->input->get('ajax') || $this->input->get_post('ajax');
	}

	function index()
	{
		redirect($this->vue_collections_base() . '#/');
	}

	function add()
	{
		redirect($this->vue_collections_base() . '#/new');
	}

	function edit($id = NULL)
	{
		if ($id === NULL || $id === '') {
			show_error('Invalid ID provided');
		}
		$row = $this->repository_model->select_single($id);
		if (!$row) {
			show_error('ID was not found');
		}
		$rid = rawurlencode($row['repositoryid']);
		redirect($this->vue_collections_base() . '#/edit/' . $rid);
	}

	function delete($id = NULL)
	{
		redirect($this->vue_collections_base() . '#/');
	}

	function users($id = NULL)
	{
		show_error('This feature is no longer supported.', 404);
	}

	function select()
	{
		redirect($this->vue_collections_base() . '#/');
	}

	function history($repositoryid = NULL)
	{
		if (!$repositoryid) {
			show_error('Invalid ID');
		}
		$row = $this->repository_model->select_single($repositoryid);
		if (!$row) {
			show_error('ID was not found');
		}
		$rid = rawurlencode($row['repositoryid']);
		redirect($this->vue_collections_base() . '#/history/' . $rid);
	}

	function active($repositoryid = NULL)
	{
		if (!is_numeric($repositoryid)) {
			show_error('INVALID_ID');
		}
		$dest = $this->input->get('destination');
		$url = 'admin/collections/active/' . $repositoryid;
		if ($dest) {
			$url .= '?destination=' . rawurlencode($dest);
		}
		redirect($url);
	}

	function reset_repo()
	{
		$this->repository_model->clear_active_repo();
	}

	function publish($id = NULL, $status = NULL)
	{
		$this->acl_manager->has_access_or_die('collection', 'publish');
		if (!is_numeric($id) || !in_array((int) $status, array(0, 1), TRUE)) {
			if ($this->ajax_requested()) {
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('error' => 'INVALID-PARAMS')));
				return;
			}
			show_error('INVALID-PARAMS');
		}
		$this->repository_model->update($id, array('ispublished' => (int) $status));
		if ($this->ajax_requested()) {
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(array('success' => 'true')));
			return;
		}
		redirect($this->vue_collections_base() . '#/');
	}

	function weight($id = NULL, $weight = NULL)
	{
		$this->acl_manager->has_access_or_die('collection', 'edit');
		if (!is_numeric($id) || !is_numeric($weight)) {
			if ($this->ajax_requested()) {
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('error' => 'INVALID-PARAMS')));
				return;
			}
			show_error('INVALID-PARAMS');
		}
		$this->repository_model->update($id, array('weight' => (int) $weight));
		if ($this->ajax_requested()) {
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(array('success' => 'true')));
			return;
		}
		redirect($this->vue_collections_base() . '#/');
	}

	function assign_role($repositoryid = NULL, $userid = NULL, $roleid = NULL)
	{
		show_error('This feature is no longer supported.', 404);
	}
}
