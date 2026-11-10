<?php
/**
 * Admin regions — Vue 3 SPA shell (same app as admin/countries).
 *
 * UI: frontend/admin/countries
 * JSON: api/admin/regions
 */
class Regions extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->acl_manager->has_access_or_die('regions', 'edit');
	}

	public function index()
	{
		$this->load->helper('vite_helper');

		$view_data = array(
			'view'              => 'regions',
			'countries_api_url' => site_url('api/admin/countries'),
			'regions_api_url'   => site_url('api/admin/regions'),
			'site_url'          => site_url(),
			'base_url'          => base_url(),
			'csrf_token'        => $this->security->get_csrf_hash(),
			'csrf_token_name'   => $this->security->get_csrf_token_name(),
			'assets_base'       => base_url('frontend/dist/'),
			'translations'      => $this->lang->language,
		);

		$page = array(
			'title'           => t('Regions'),
			'content'         => $this->load->view('admin/countries/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
