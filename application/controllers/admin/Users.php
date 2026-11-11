<?php
/**
 * Admin users — Vue 3 SPA shell.
 *
 * UI: frontend/admin/users
 * JSON: api/admin/users
 *
 * URLs:
 *   /admin/users                    list
 *   /admin/users/add                create
 *   /admin/users/edit/{id}          edit
 *   /admin/users/edit/{id}/api_keys that user's API keys
 *   /admin/users/api_keys           all API keys
 *   /admin/users/export/{csv|json}  download all users
 */
class Users extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('User_model');
		$this->lang->load('general');
		$this->lang->load('users');
	}

	public function _remap($method = 'index', $params = array())
	{
		$id = isset($params[0]) ? $params[0] : null;

		switch ($method) {
			case 'index':
			case '':
				$this->acl_manager->has_access_or_die('user', 'view');
				$this->_render_vue_shell(t('title_user_management'));
				return;

			case 'add':
				$this->acl_manager->has_access_or_die('user', 'create');
				$this->_render_vue_shell(t('create_user_account'));
				return;

			case 'edit':
				$sub = isset($params[1]) ? $params[1] : null;
				if ( ! is_numeric($id) || (int) $id < 1 || ($sub !== null && $sub !== 'api_keys') || count($params) > 2) {
					show_404();
				}
				$this->acl_manager->has_access_or_die('user', 'view');
				$this->_render_vue_shell($sub === 'api_keys' ? t('api_keys') : t('edit_user_account'));
				return;

			case 'api_keys':
				$this->acl_manager->has_access_or_die('user', 'view');
				$this->_render_vue_shell(t('api_keys_management'));
				return;

			case 'manage_api_key':
				redirect('admin/users/api_keys');
				return;

			case 'export':
				$this->export($id);
				return;
		}

		show_404();
	}

	/**
	 * @param string $html_title
	 */
	private function _render_vue_shell($html_title)
	{
		$this->load->helper('vite_helper');

		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

		$pu    = parse_url(site_url('admin/users'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/users';

		$view_data = array(
			'api_base_url'     => site_url('api/admin/users'),
			'site_url'         => site_url(),
			'base_url'         => base_url(),
			'csrf_token'       => $this->security->get_csrf_hash(),
			'csrf_token_name'  => $this->security->get_csrf_token_name(),
			'assets_base'      => base_url('frontend/dist/'),
			'translations'     => $this->lang->language,
			'router_path_base' => $rpath,
		);

		$page = array(
			'title'           => $html_title,
			'content'         => $this->load->view('admin/users/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * Export all users as CSV or JSON.
	 *
	 * @param string|null $format csv|json
	 * @return void
	 */
	private function export($format = null)
	{
		$this->acl_manager->has_access_or_die('user', 'view');

		$format = strtolower(trim((string) $format));
		if ( ! in_array($format, array('csv', 'json'), true)) {
			show_404();
		}

		$rows = $this->User_model->get_export_rows();
		$export = array();
		foreach ($rows as $row) {
			$created_on = isset($row['created_on']) ? (int) $row['created_on'] : 0;
			$last_login = isset($row['last_login']) ? (int) $row['last_login'] : 0;

			$export[] = array(
				'id'          => (int) $row['id'],
				'username'    => isset($row['username']) ? (string) $row['username'] : '',
				'email'       => isset($row['email']) ? (string) $row['email'] : '',
				'first_name'  => isset($row['first_name']) ? (string) $row['first_name'] : '',
				'last_name'   => isset($row['last_name']) ? (string) $row['last_name'] : '',
				'status'      => ((int) (isset($row['active']) ? $row['active'] : 0) === 1) ? 'ACTIVE' : 'DISABLED',
				'organization'=> isset($row['company']) ? (string) $row['company'] : '',
				'country'     => isset($row['country']) ? (string) $row['country'] : '',
				'phone'       => isset($row['phone']) ? (string) $row['phone'] : '',
				'date_joined' => $created_on > 0 ? date('Y-m-d', $created_on) : '',
				'last_login'  => ($last_login > $created_on) ? date('Y-m-d', $last_login) : '',
			);
		}

		$headers = array(
			'id',
			'username',
			'email',
			'first_name',
			'last_name',
			'status',
			'organization',
			'country',
			'phone',
			'date_joined',
			'last_login',
		);

		if ($format === 'json') {
			$filename = 'users-' . date('Y-m-d-His') . '.json';
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			return;
		}

		$filename = 'users-' . date('Y-m-d-His') . '.csv';
		header('Expires: Sat, 01 Jan 1980 00:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$outstream = fopen('php://output', 'w');
		// UTF-8 BOM for Excel compatibility
		fwrite($outstream, "\xEF\xBB\xBF");
		fputcsv($outstream, $headers);
		foreach ($export as $row) {
			$line = array();
			foreach ($headers as $key) {
				$line[] = $this->_csv_safe($row[$key]);
			}
			fputcsv($outstream, $line);
		}
		fclose($outstream);
	}

	/**
	 * Self-service fields (names, organization) can start with a spreadsheet formula character.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function _csv_safe($value)
	{
		if (is_string($value) && $value !== '' && strpos('=+-@', $value[0]) !== false) {
			return "'" . $value;
		}

		return $value;
	}
}
