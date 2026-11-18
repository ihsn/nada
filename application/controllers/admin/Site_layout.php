<?php

/**
 * Admin > Settings > Site layout — Vue 3 SPA shell (header, footer, styles tabs).
 *
 * UI: frontend/admin/site_layout
 * URL: admin/site-layout (see routes.php)
 * JSON: api/admin/site_layout (all parts, export/import; ACL configurations/edit)
 * Preview: site_layout_preview/{header|footer|styles}
 */
class Site_layout extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('site_layout');
		$this->lang->load('site_header');
		$this->lang->load('site_footer');
		$this->lang->load('site_styles');
	}

	function index()
	{
		$this->acl_manager->has_access_or_die('configurations', 'edit');
		$this->load->helper('vite_helper');

		$view_data = array(
			'layout_api_base_url' => site_url('api/admin/site_layout'),
			'preview_base_url'    => site_url('site_layout_preview'),
			'site_url'            => site_url(),
			'base_url'            => base_url(),
			'csrf_token'          => $this->security->get_csrf_hash(),
			'csrf_token_name'     => $this->security->get_csrf_token_name(),
			'assets_base'         => base_url('frontend/dist/'),
			'translations'        => $this->lang->language,
		);

		$page = array(
			'title'           => t('site_layout'),
			'content'         => $this->load->view('admin/site_layout/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
