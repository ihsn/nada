<?php

/**
 * Bulk data access collections — Vue 3 admin UI.
 */
class Da_Collections extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'vite_helper'));
		$this->lang->load('general');
		$this->lang->load('da_collection');
		$this->acl_manager->has_access_or_die('bulk_data_access', 'view');
	}

	public function index()
	{
		$this->_render_da_collections_vue_shell(t('bulk_da_collections'));
	}

	public function add()
	{
		$this->acl_manager->has_access_or_die('bulk_data_access', 'edit');
		$this->_render_da_collections_vue_shell(t('da_collection_add'));
	}

	public function edit($id = null)
	{
		if (! is_numeric($id)) {
			show_404();
		}
		$this->acl_manager->has_access_or_die('bulk_data_access', 'edit');
		$this->_render_da_collections_vue_shell(t('da_collection_edit'));
	}

	public function attach_studies($collection_id = null)
	{
		if (! is_numeric($collection_id)) {
			show_404();
		}
		$this->acl_manager->has_access_or_die('bulk_data_access', 'edit');
		$this->_render_da_collections_vue_shell(t('attach_studies_to_da_collection'));
	}

	/**
	 * @param string|null $html_title
	 */
	private function _render_da_collections_vue_shell($html_title = null)
	{
		$view_data = $this->_da_collections_vue_view_data();
		$page      = array(
			'title'           => $html_title !== null ? $html_title : t('bulk_da_collections'),
			'content'         => $this->load->view('admin/da_collections/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * @return array
	 */
	private function _da_collections_vue_view_data()
	{
		$pu    = parse_url(site_url('admin/da_collections'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/da_collections';

		return array(
			'api_base_url'    => site_url('api/admin/bulk-data-access/'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
			'router_path_base'=> $rpath,
			'can_edit'        => $this->acl_manager->user_has_access('bulk_data_access', 'edit'),
			'can_delete'      => $this->acl_manager->user_has_access('bulk_data_access', 'delete'),
		);
	}
}
