<?php
/**
 * Admin public-use request detail — Vue 3 SPA shell.
 *
 * UI: frontend/admin/public_requests
 * JSON: api/admin/public_requests
 *
 * Opened from classic reports (“details”). Full list/export stays with reports
 * until that module is migrated.
 */
class Public_requests extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('public_request');
	}

	public function _remap($requestid = null)
	{
		if (! is_numeric($requestid)) {
			show_404();
		}

		$this->acl_manager->has_access_or_die('reports', 'view');
		$this->load->helper('vite_helper');

		$view_data = array(
			'api_base_url'    => site_url('api/admin/public_requests'),
			'request_id'      => (int) $requestid,
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
		);

		$page = array(
			'title'           => t('public_request'),
			'content'         => $this->load->view('admin/public_requests/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
