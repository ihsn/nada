<?php
/**
 * Admin permissions / roles — Vue 3 SPA shell.
 *
 * UI: frontend/admin/permissions
 * JSON: api/admin/permissions
 */
class Permissions extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url'));
		$this->load->library('Acl_manager');
		$this->lang->load('general');
		$this->lang->load('user_groups');
		$this->lang->load('permissions');

		if (! $this->acl_manager->user_is_admin()) {
			$this->acl_manager->show_access_denied('feature');
		}
	}

	public function index()
	{
		$this->_render_permissions_vue_shell(t('Manage roles'));
	}

	/**
	 * Same SPA shell (hash router handles in-app routes).
	 */
	public function roles()
	{
		$this->_render_permissions_vue_shell(t('Manage roles'));
	}

	/**
	 * @return array
	 */
	private function _permissions_vue_view_data()
	{
		$this->load->helper('vite_helper');

		return array(
			'api_base_url'    => site_url('api/admin/permissions/'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
		);
	}

	/**
	 * @param string|null $html_title
	 * @return void
	 */
	private function _render_permissions_vue_shell($html_title = null)
	{
		$view_data = $this->_permissions_vue_view_data();
		$page = array(
			'title'           => $html_title !== null ? $html_title : t('Manage roles'),
			'content'         => $this->load->view('admin/permissions/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
