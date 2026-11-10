<?php
class Menu extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->lang->load('menu');
		$this->template->set_template('admin5');
	}

	function index()
	{
		$this->acl_manager->has_access_or_die('menu', 'view');
		$this->load->helper('vite_helper');

		$use_html = $this->config->item('use_html_editor');
		$view_data = array(
			'site_url'          => site_url(),
			'base_url'          => base_url(),
			'api_base_url'      => site_url('api/admin/menu/'),
			'filestore_api_url' => site_url('api/admin/filestore'),
			'files_public_url'  => base_url('files/public'),
			'assets_base'       => base_url('frontend/dist/'),
			'csrf_token'        => $this->security->get_csrf_hash(),
			'csrf_token_name'   => $this->security->get_csrf_token_name(),
			'use_html_editor'   => ($use_html === 'no') ? 'no' : 'yes',
		);

		$page = array(
			'title'           => t('menu_management'),
			'content'         => $this->load->view('admin/menu/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}

/* End of file menu.php */
/* Location: ./system/application/controllers/menu.php */
