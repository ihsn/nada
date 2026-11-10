<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Admin database migrations — Vue 3 SPA shell.
 *
 * UI: frontend/admin/database_migration
 * JSON: api/admin/database_migration
 */
class Database_migration extends MY_Controller {

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
			'database_migration_api_url' => site_url('api/admin/database_migration'),
			'site_url'                   => site_url(),
			'base_url'                   => base_url(),
			'csrf_token'                 => $this->security->get_csrf_hash(),
			'csrf_token_name'            => $this->security->get_csrf_token_name(),
			'assets_base'                => base_url('frontend/dist/'),
			'translations'               => $this->lang->language,
		);

		$page = array(
			'title'           => 'Database Migrations',
			'content'         => $this->load->view('admin/database_migration/index', $view_data, TRUE),
			'hide_breadcrumb' => TRUE,
			'theme_folder'    => 'adminvue',
		);

		if (! headers_sent()) {
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Cache-Control: post-check=0, pre-check=0', FALSE);
			header('Pragma: no-cache');
		}

		$this->load->view('layouts/admin_vue', $page);
	}
}
