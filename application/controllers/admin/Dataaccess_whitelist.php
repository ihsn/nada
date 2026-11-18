<?php
/**
 * Admin data-access whitelist — Vue 3 SPA shell.
 *
 * UI: frontend/admin/dataaccess_whitelist
 * JSON: api/admin/dataaccess_whitelist
 */
class Dataaccess_whitelist extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');

		if (! $this->acl_manager->user_is_admin()) {
			$this->acl_manager->show_access_denied('feature');
		}
	}

	public function index()
	{
		$this->load->helper('vite_helper');

		$view_data = array(
			'api_base_url'    => site_url('api/admin/dataaccess_whitelist'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
		);

		$page = array(
			'title'           => t('Data access whitelist'),
			'content'         => $this->load->view('admin/dataaccess_whitelist/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * Legacy form POST — send callers to the SPA.
	 */
	public function create()
	{
		redirect('admin/dataaccess_whitelist', 'refresh');
	}

	/**
	 * Legacy delete URL — SPA handles deletes via API.
	 */
	public function delete($id = null)
	{
		unset($id);
		redirect('admin/dataaccess_whitelist', 'refresh');
	}
}
