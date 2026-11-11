<?php
/**
 * Admin repository sections (collection groups) — Vue 3 SPA shell.
 *
 * UI: frontend/admin/repository_sections
 * JSON: api/admin/repository_sections
 */
class Repository_Sections extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('collection');
	}

	public function index()
	{
		$this->acl_manager->has_access_or_die('collection_type', 'view');
		$this->_render_shell(t('repository_sections_management'));
	}

	/**
	 * Legacy add/edit URLs — same SPA shell (hash routes).
	 */
	public function add()
	{
		$this->acl_manager->has_access_or_die('collection_type', 'edit');
		redirect(site_url('admin/repository_sections') . '#/new');
	}

	public function edit($id = null)
	{
		$this->acl_manager->has_access_or_die('collection_type', 'edit');
		if (is_numeric($id)) {
			redirect(site_url('admin/repository_sections') . '#/edit/' . (int) $id);
		}
		redirect(site_url('admin/repository_sections') . '#/new');
	}

	/**
	 * Legacy delete confirmation page — SPA handles deletes.
	 */
	public function delete($id = null)
	{
		$this->acl_manager->has_access_or_die('collection_type', 'delete');
		unset($id);
		redirect('admin/repository_sections', 'refresh');
	}

	/**
	 * @param string $title
	 * @return void
	 */
	private function _render_shell($title)
	{
		$this->load->helper('vite_helper');

		$view_data = array(
			'api_base_url'    => site_url('api/admin/repository_sections'),
			'site_url'        => site_url(),
			'base_url'        => base_url(),
			'csrf_token'      => $this->security->get_csrf_hash(),
			'csrf_token_name' => $this->security->get_csrf_token_name(),
			'assets_base'     => base_url('frontend/dist/'),
			'translations'    => $this->lang->language,
			'can_edit'        => $this->acl_manager->user_has_access('collection_type', 'edit'),
			'can_delete'      => $this->acl_manager->user_has_access('collection_type', 'delete'),
		);

		$page = array(
			'title'           => $title,
			'content'         => $this->load->view('admin/repository_sections/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
