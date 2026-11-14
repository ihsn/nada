<?php

/**
 * Admin > Settings > Site header — Vue 3 SPA shell.
 *
 * UI: frontend/admin/site_header
 * JSON: api/admin/site_header (ACL configurations/edit)
 * Preview: site_header_preview (public page that shows the draft to editors)
 */
class Site_header extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('site_header');
	}

	function index()
	{
		$this->acl_manager->has_access_or_die('configurations', 'edit');
		$this->load->helper('vite_helper');

		$view_data = array(
			'api_base_url'    => site_url('api/admin/site_header'),
			'preview_url'     => site_url('site_header_preview'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
		);

		$page = array(
			'title'           => t('site_header'),
			'content'         => $this->load->view('admin/site_header/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
