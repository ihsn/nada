<?php

class Semantic extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->template->set_template('admin5');
	}

	function index()
	{
		$this->acl_manager->has_access_or_die('semantic_search', 'view');
		$this->load->helper('vite_helper');

		$view_data = array(
			'site_url'     => site_url(),
			'base_url'     => base_url(),
			'api_base_url' => site_url('api/admin/semantic/'),
			'assets_base'  => base_url('frontend/dist/'),
			'csrf_token'   => $this->security->get_csrf_hash(),
			'can_edit'     => $this->acl_manager->user_has_access('semantic_search', 'edit'),
			'can_delete'   => $this->acl_manager->user_has_access('semantic_search', 'delete'),
		);

		$page = array(
			'title'           => t('Semantic search'),
			'content'         => $this->load->view('admin/semantic/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
