<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Display Template Manager page controller (Vue 3 + Vite app)
 */
class Display_templates extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->acl_manager->has_access_or_die('display_template', 'view');

		$this->load->helper('vite_helper');
		$view_data = [
			'api_base_url' => site_url('api/admin/display_templates/'),
			'site_url' => site_url(),
			'base_url' => base_url(),
			'csrf_token' => $this->security->get_csrf_hash(),
			'assets_base' => base_url('frontend/dist/'),
		];

		$page = [
			'title' => 'Display template manager',
			'content' => $this->load->view('admin/display_templates/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder' => 'adminvue',
		];

		$this->load->view('layouts/admin_vue', $page);
	}
}
