<?php

/**
 * Admin Solr — Vue 3 SPA shell.
 *
 * UI: frontend/admin/solr
 * JSON: api/solr (top-level; ACL configurations/edit)
 */
class Solr extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
	}

	function index()
	{
		$this->acl_manager->has_access_or_die('configurations', 'edit');
		$this->load->helper('vite_helper');

		$view_data = array(
			'api_base_url'    => site_url('api/solr'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
		);

		$page = array(
			'title'           => t('solr') ?: 'Solr',
			'content'         => $this->load->view('admin/solr/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
