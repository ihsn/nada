<?php
/**
 * Admin public-use requests — Vue 3 SPA shell.
 *
 * List + detail UI: frontend/admin/public_requests
 * JSON / CSV: api/admin/public_requests
 *
 * URLs:
 *   /admin/public_requests          list
 *   /admin/public_requests/{id}     detail
 */
class Public_requests extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->lang->load('general');
		$this->lang->load('public_request');
		$this->lang->load('reports');
	}

	public function _remap($method = 'index')
	{
		$this->acl_manager->has_access_or_die('reports', 'view');

		if ($method === 'index' || $method === '') {
			$this->_render_vue_shell(null, t('public_requests'));
			return;
		}

		if (is_numeric($method) && (int) $method > 0) {
			$this->_render_vue_shell((int) $method, t('public_request'));
			return;
		}

		show_404();
	}

	/**
	 * @param int|null    $request_id
	 * @param string|null $html_title
	 */
	private function _render_vue_shell($request_id = null, $html_title = null)
	{
		$this->load->helper('vite_helper');

		$pu    = parse_url(site_url('admin/public_requests'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/public_requests';

		$view_data = array(
			'api_base_url'     => site_url('api/admin/public_requests'),
			'request_id'       => $request_id ? (int) $request_id : 0,
			'site_url'         => site_url(),
			'base_url'         => base_url(),
			'csrf_token'       => $this->security->get_csrf_hash(),
			'csrf_token_name'  => $this->security->get_csrf_token_name(),
			'assets_base'      => base_url('frontend/dist/'),
			'translations'     => $this->lang->language,
			'router_path_base' => $rpath,
		);

		$page = array(
			'title'           => $html_title !== null ? $html_title : t('public_requests'),
			'content'         => $this->load->view('admin/public_requests/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
}
