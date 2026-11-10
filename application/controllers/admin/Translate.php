<?php
/**
 * Admin site translations — Vue 3 SPA shell.
 *
 * UI: frontend/admin/translate
 * JSON: api/admin/translate
 */
class Translate extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('translate');
		$this->acl_manager->has_access_or_die('translate', 'edit');
	}

	public function index()
	{
		$this->load->helper('vite_helper');

		$view_data = array(
			'translate_api_url' => site_url('api/admin/translate'),
			'site_url'          => site_url(),
			'base_url'          => base_url(),
			'csrf_token'        => $this->security->get_csrf_hash(),
			'csrf_token_name'   => $this->security->get_csrf_token_name(),
			'assets_base'       => base_url('frontend/dist/'),
			'translations'      => $this->lang->language,
		);

		$page = array(
			'title'           => t('translate'),
			'content'         => $this->load->view('admin/translate/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
