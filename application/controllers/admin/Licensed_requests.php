<?php
/**
 * Admin licensed survey requests — Vue 3 SPA shell.
 *
 * List/edit/monitor UI: frontend/admin/licensed_requests
 * JSON: api/admin/licensed_requests
 */
class Licensed_requests extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url'));
		$this->load->model('Licensed_model');
		$this->load->model('Repository_model');
		$this->template->set_template('admin');
		$this->lang->load('general');
		$this->lang->load('licensed_request');
		$this->lang->load('catalog_admin');
	}

	public function index()
	{
		$this->acl_manager->require_licensed_requests_access();
		$this->_render_licensed_requests_vue_shell(t('licensed_requests'));
	}

	/**
	 * @return array
	 */
	private function _licensed_requests_vue_view_data()
	{
		$this->load->helper('vite_helper');
		$pu    = parse_url(site_url('admin/licensed_requests'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/licensed_requests';

		return array(
			'api_base_url'     => site_url('api/admin/licensed_requests/'),
			'site_url'         => site_url(),
			'base_url'         => base_url(),
			'csrf_token'       => $this->security->get_csrf_hash(),
			'csrf_token_name'  => $this->security->get_csrf_token_name(),
			'assets_base'      => base_url('frontend/dist/'),
			'translations'     => $this->lang->language,
			'router_path_base' => $rpath,
		);
	}

	/**
	 * @param string|null $html_title
	 * @return void
	 */
	private function _render_licensed_requests_vue_shell($html_title = null)
	{
		$view_data = $this->_licensed_requests_vue_view_data();
		$page      = array(
			'title'           => $html_title !== null ? $html_title : t('title_licensed_request'),
			'content'         => $this->load->view('admin/licensed_requests/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * CSV export (same data as GET api/admin/licensed_requests/export).
	 */
	function export()
	{
		$this->acl_manager->require_licensed_requests_access();

		$user  = $this->ion_auth->current_user();
		$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
		if ($scope === false) {
			$this->acl_manager->show_access_denied('feature');
		}

		if ($scope === null) {
			$rows = $this->Licensed_model->select_all();
		}
		else {
			$rows = $this->Licensed_model->admin_search_requests(50000, 0, array(), 'created', 'desc', $scope, null);
		}

		return $this->Licensed_model->export_to_csv($rows);
	}

	/**
	 * Edit SPA — also serves /edit/{id}/{tab} for history-mode Vue routes.
	 *
	 * @param int         $id
	 * @param string|null $tab ignored by PHP; Vue router uses the URL path
	 */
	function edit($id, $tab = null)
	{
		if (! is_numeric($id)) {
			show_404();
		}

		if (! $this->_admin_user_can_licensed_request((int) $id, 'view')) {
			$this->acl_manager->show_access_denied('feature');
		}

		$this->_render_licensed_requests_vue_shell();
	}

	/**
	 * Legacy monitor URL → Vue edit Monitor tab.
	 *
	 * @param int $requestid
	 */
	function monitor($requestid)
	{
		if (! is_numeric($requestid) || ! $this->_admin_user_can_licensed_request((int) $requestid, 'view')) {
			$this->acl_manager->show_access_denied('feature');
		}

		redirect(site_url('admin/licensed_requests/edit/' . (int) $requestid . '/mon'));
	}

	/**
	 * @param int    $request_id
	 * @param string $privilege  view|edit|delete
	 * @return bool
	 */
	private function _admin_user_can_licensed_request($request_id, $privilege)
	{
		$user = $this->ion_auth->current_user();
		if (! $user) {
			return false;
		}

		if ($this->acl_manager->user_is_admin($user)) {
			return true;
		}

		$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
		if ($scope === false) {
			return false;
		}

		if (! $this->Licensed_model->request_matches_repository_scope($request_id, $scope)) {
			return false;
		}

		try {
			$this->acl_manager->has_access('licensed_request', $privilege, $user);

			return true;
		}
		catch (Exception $e) {
			// fall through to per-repo check
		}

		return $this->acl_manager->has_access_on_any_repository(
			'licensed_request',
			$privilege,
			$user,
			$this->Licensed_model->get_request_repository_ids($request_id)
		);
	}
}
