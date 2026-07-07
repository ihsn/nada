<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Data structures (DSD catalogue) — Vue 3 + Vite app.
 */
class Data_structures extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->template->set_template('admin5');
	}

	public function index()
	{
		$this->acl_manager->has_access_or_die('data_structure', 'view');

		$this->load->helper('vite_helper');
		$view_data = [
			'api_base_url'           => site_url('api/admin/data_structures/'),
			'codelists_api_base_url' => site_url('api/admin/codelists/'),
			'site_url'               => site_url(),
			'base_url'               => base_url(),
			'csrf_token'             => $this->security->get_csrf_hash(),
			'assets_base'            => base_url('frontend/dist/'),
		];

		$page = [
			'title'           => 'Data structures',
			'content'         => $this->load->view('admin/data_structures/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		];
		$this->load->view('layouts/admin_vue', $page);
	}
}
