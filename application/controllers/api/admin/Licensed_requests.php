<?php

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin API for licensed survey requests (Vue admin UI).
 *
 * Base: /api/admin/licensed_requests
 * Alias: /api/admin/licensed-requests (see routes)
 */
class Licensed_requests extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('acl_manager');
		$this->load->model('Licensed_model');
		$this->load->model('Repository_model');
		$this->lang->load('licensed_request');
		$this->lang->load('general');
		$this->is_authenticated_or_die();
	}

	/**
	 * Session auth and API key auth (same pattern as api/admin/catalog).
	 */

	/**
	 * GET /api/admin/licensed_requests/search
	 *
	 * Query: page, ps, keywords, status, sort_by (username|created|status|request_title),
	 *        sort_order (asc|desc), owner_repo (optional collection filter)
	 */
	public function search_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
			if ($scope === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$page_size = $this->_get_page_size(30, 1, 100);
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			$keywords   = $this->input->get('keywords', true);
			$status     = $this->input->get('status', true);
			$sort_by    = $this->input->get('sort_by', true);
			$sort_order = $this->input->get('sort_order', true);
			$owner_repo = trim((string) $this->input->get('owner_repo', true));

			if ($owner_repo !== '' && $scope !== null && !$this->_owner_repo_allowed($scope, $owner_repo)) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$search_options = array(
				'keywords' => $keywords,
				'status'   => $status,
			);

			$owner_filter = ($owner_repo !== '') ? $owner_repo : null;

			$total = $this->Licensed_model->admin_search_requests_count($scope, $search_options, $owner_filter);
			$rows  = $this->Licensed_model->admin_search_requests(
				$page_size,
				$offset,
				$search_options,
				$sort_by ? $sort_by : 'created',
				$sort_order ? $sort_order : 'desc',
				$scope,
				$owner_filter
			);

			if (! is_array($rows)) {
				$rows = array();
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'rows'               => $rows,
						'total'              => $total,
						'page'               => $page,
						'pages'              => $page_size > 0 ? (int) ceil($total / $page_size) : 1,
						'page_size'          => $page_size,
						'scope_unrestricted' => $scope === null,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/licensed_requests/bootstrap
	 */
	public function bootstrap_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
			if ($scope === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$collections = $this->_bootstrap_collections($scope);

			$this->set_response(
				array(
					'status'               => 'success',
					'collections'          => $collections,
					'scope_unrestricted'   => $scope === null,
					'scope_repository_ids' => $scope === null ? null : $scope,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/licensed_requests/item/{id}
	 *
	 * Full payload for the Vue edit UI.
	 */
	public function item_get($id = null)
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->_ensure_request_access($user, (int) $id, false);

			$data = $this->_build_edit_detail((int) $id);

			$this->set_response(
				array(
					'status' => 'success',
					'data'   => $data,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PATCH /api/admin/licensed_requests/item/{id}
	 *
	 * JSON: status, comments, ip_limit, notify (bool), files: [{ resource_id, selected, download_limit, expiry (YYYY-MM-DD) }]
	 */
	public function item_patch($id = null)
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->_ensure_request_access($user, (int) $id, true);

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$status   = isset($input['status']) ? trim((string) $input['status']) : '';
			$comments = isset($input['comments']) ? (string) $input['comments'] : '';
			$ip_limit = isset($input['ip_limit']) ? trim((string) $input['ip_limit']) : '';
			$notify   = ! empty($input['notify']);

			$allowed_status = array('PENDING', 'APPROVED', 'DENIED', 'MOREINFO', 'CANCELLED');
			if (! in_array($status, $allowed_status, true)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_STATUS'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			if ($ip_limit !== '' && ! $this->_valid_ip_list($ip_limit)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_IP'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->Licensed_model->update_request_status((int) $id, $user->username, $status, $comments, $ip_limit);

			$history_opts = array(
				'user_id'        => $user->email,
				'logtype'        => 'comment',
				'request_status' => $status,
				'description'    => $comments,
			);
			$this->Licensed_model->add_request_history((int) $id, $history_opts);

			if (array_key_exists('files', $input) && is_array($input['files'])) {
				$file_options = array();
				foreach ($input['files'] as $f) {
					if (empty($f['selected'])) {
						continue;
					}
					$fid = isset($f['resource_id']) ? $f['resource_id'] : '';
					if ($fid === '') {
						continue;
					}
					$dl = isset($f['download_limit']) ? (int) $f['download_limit'] : 3;
					$ex = isset($f['expiry']) ? $this->_expiry_end_of_day_from_input($f['expiry']) : 0;
					if ($ex < 1) {
						$this->set_response(array('status' => 'error', 'message' => 'INVALID_EXPIRY'), REST_Controller::HTTP_BAD_REQUEST);
						return;
					}
					$file_options[$fid] = array(
						'download_limit' => $dl,
						'expiry'         => $ex,
					);
				}

				if (count($file_options) > 0) {
					$this->Licensed_model->update_request_files((int) $id, $file_options);
					$this->Licensed_model->delete_request_files((int) $id, array_keys($file_options));
				}
				else {
					$this->Licensed_model->delete_request_files((int) $id);
				}
			}

			if ($notify) {
				$this->_notify_user_email((int) $id);
			}

			$data = $this->_build_edit_detail((int) $id);

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $this->lang->line('form_update_success') ? $this->lang->line('form_update_success') : 'Updated',
					'data'    => $data,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/licensed_requests/send_mail/{id}
	 *
	 * JSON: to, cc, subject, body
	 */
	public function send_mail_post($id = null)
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->_ensure_request_access($user, (int) $id, true);

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$to      = isset($input['to']) ? trim((string) $input['to']) : '';
			$cc      = isset($input['cc']) ? trim((string) $input['cc']) : '';
			$subject = isset($input['subject']) ? trim((string) $input['subject']) : '';
			$body    = isset($input['body']) ? (string) $input['body'] : '';

			if ($to === '' || $subject === '' || $body === '') {
				$this->set_response(array('status' => 'error', 'message' => 'MISSING_FIELDS'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->load->library('email');
			$this->email->clear();
			$this->email->initialize();
			$this->email->to($to);
			$this->email->cc($cc);
			$this->email->subject($subject);
			$this->email->message($body);

			$request = $this->Licensed_model->get_request_by_id((int) $id);
			$email   = array(
				'to'      => $to,
				'cc'      => $cc,
				'subject' => $subject,
				'body'    => $body,
			);

			$sent = $this->email->send();

			if ($sent) {
				$options = array(
					'user_id'        => $user->email,
					'logtype'        => 'email',
					'request_status' => isset($request['status']) ? $request['status'] : '',
					'description'    => serialize($email),
				);
				$this->Licensed_model->add_request_history((int) $id, $options);
			}

			$data = $this->_build_edit_detail((int) $id);

			$this->set_response(
				array(
					'status'  => $sent ? 'success' : 'error',
					'message' => $sent ? ( $this->lang->line('email_sent') ? $this->lang->line('email_sent') : 'Sent' ) : ( $this->lang->line('email_not_sent') ? $this->lang->line('email_not_sent') : 'Not sent' ),
					'data'    => $data,
				),
				$sent ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/licensed_requests/forward/{id}
	 *
	 * JSON: to, cc, subject, body (body may be replaced by formatted request email view)
	 */
	public function forward_post($id = null)
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->_ensure_request_access($user, (int) $id, true);

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$to      = isset($input['to']) ? trim((string) $input['to']) : '';
			$cc      = isset($input['cc']) ? trim((string) $input['cc']) : '';
			$subject = isset($input['subject']) ? trim((string) $input['subject']) : '';
			$body    = isset($input['body']) ? (string) $input['body'] : '';

			if ($to === '' || $subject === '') {
				$this->set_response(array('status' => 'error', 'message' => 'MISSING_FIELDS'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$request_data = $this->Licensed_model->get_request_by_id((int) $id);
			if (! $request_data) {
				$this->set_response(array('status' => 'error', 'message' => 'NOT_FOUND'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$request_formatted = $this->load->view('access_licensed/forward_request_email', $request_data, true);

			$this->load->library('email');
			$this->email->clear();
			$this->email->initialize();
			$this->email->to($to);
			$this->email->cc($cc);
			$this->email->subject($subject);
			$this->email->message($request_formatted);

			$sent = $this->email->send();

			if ($sent) {
				$request = $this->Licensed_model->get_request_by_id((int) $id);
				$email   = array(
					'to'      => $to,
					'cc'      => $cc,
					'subject' => $subject,
					'body'    => $body,
				);
				$options = array(
					'user_id'        => $user->email,
					'logtype'        => 'forward',
					'request_status' => isset($request['status']) ? $request['status'] : '',
					'description'    => serialize($email),
				);
				$this->Licensed_model->add_request_history((int) $id, $options);
			}

			$data = $this->_build_edit_detail((int) $id);

			$this->set_response(
				array(
					'status'  => $sent ? 'success' : 'error',
					'message' => $sent ? ( $this->lang->line('email_sent') ? $this->lang->line('email_sent') : 'Sent' ) : ( $this->lang->line('email_not_sent') ? $this->lang->line('email_not_sent') : 'Not sent' ),
					'data'    => $data,
				),
				$sent ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * @param object $user
	 * @param int    $request_id
	 * @param bool   $need_edit  true = licensed_request edit
	 */
	private function _ensure_request_access($user, $request_id, $need_edit)
	{
		$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
		if ($scope === false) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}

		if (! $this->Licensed_model->request_matches_repository_scope($request_id, $scope)) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}

		$priv = $need_edit ? 'edit' : 'view';
		if (! $this->_licensed_request_privilege_on_request($user, $request_id, $priv)) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
	}

	/**
	 * @param object $user
	 * @param int    $request_id
	 * @param string $privilege view|edit
	 */
	private function _licensed_request_privilege_on_request($user, $request_id, $privilege)
	{
		if ($this->acl_manager->user_is_admin($user)) {
			return true;
		}

		try {
			$this->acl_manager->has_access('licensed_request', $privilege, $user);

			return true;
		}
		catch (Exception $e) {
			// continue — try per-repo Zend roles + collection ACL in one pass
		}

		return $this->acl_manager->has_access_on_any_repository(
			'licensed_request',
			$privilege,
			$user,
			$this->Licensed_model->get_request_repository_ids($request_id)
		);
	}

	/**
	 * Full detail for Vue (edit screen).
	 *
	 * @param int $id
	 * @return array
	 */
	private function _build_edit_detail($id)
	{
		$user = $this->api_user();

		$row = $this->Licensed_model->get_request_by_id($id);
		if (! $row) {
			throw new Exception('NOT_FOUND');
		}

		$can_edit = $this->_licensed_request_privilege_on_request($user, $id, 'edit');

		$files_by_survey = array();
		$survey_rows     = isset($row['surveys']) && is_array($row['surveys']) ? $row['surveys'] : array();
		foreach ($survey_rows as $survey) {
			$sid = isset($survey['id']) ? (int) $survey['id'] : 0;
			if ($sid < 1) {
				continue;
			}

			$raw_files = $this->Licensed_model->get_request_files($sid, $id);
			$file_rows = array();
			if (is_array($raw_files)) {
				foreach ($raw_files as $file) {
					$file       = (array) $file;
					$rid        = isset($file['resource_id']) ? $file['resource_id'] : '';
					$exp_unix   = isset($file['download']['expiry']) ? (int) $file['download']['expiry'] : 0;
					$file_rows[] = array(
						'resource_id'    => $rid,
						'filename'       => isset($file['filename']) ? basename($file['filename']) : '',
						'title'          => isset($file['title']) ? $file['title'] : '',
						'selected'       => isset($file['download']),
						'download_limit' => isset($file['download']['download_limit']) ? (int) $file['download']['download_limit'] : 3,
						'expiry'         => $exp_unix,
						'expiry_date'    => $exp_unix > 0 ? gmdate('Y-m-d', $exp_unix) : '',
					);
				}
			}

			$files_by_survey[] = array(
				'survey_id'    => $sid,
				'nation'       => isset($survey['nation']) ? $survey['nation'] : '',
				'title'        => isset($survey['title']) ? $survey['title'] : '',
				'year_start'   => isset($survey['year_start']) ? $survey['year_start'] : '',
				'idno'         => isset($survey['idno']) ? $survey['idno'] : '',
				'files'        => $file_rows,
			);
		}

		$comments_history = $this->Licensed_model->get_request_history($id, 'comment');
		$email_history    = $this->Licensed_model->get_request_history($id, 'email');
		$forward_history  = $this->Licensed_model->get_request_history($id, 'forward');

		$summary_rows = $this->Licensed_model->get_request_summary($id);
		$log_rows     = $this->Licensed_model->get_request_log($id);

		$surveys_out = array();
		foreach ($survey_rows as $s) {
			$surveys_out[] = array(
				'id'         => isset($s['id']) ? (int) $s['id'] : null,
				'idno'       => isset($s['idno']) ? $s['idno'] : '',
				'title'      => isset($s['title']) ? $s['title'] : '',
				'nation'     => isset($s['nation']) ? $s['nation'] : '',
				'year_start' => isset($s['year_start']) ? $s['year_start'] : '',
				'year_end'   => isset($s['year_end']) ? $s['year_end'] : '',
			);
		}

		unset($row['surveys']);

		return array(
			'request'             => $row,
			'surveys'             => $surveys_out,
			'repository_ids'      => $this->Licensed_model->get_request_repository_ids($id),
			'files_by_survey'     => $files_by_survey,
			'comments_history'    => is_array($comments_history) ? $comments_history : array(),
			'email_history'       => is_array($email_history) ? $email_history : array(),
			'forward_history'     => is_array($forward_history) ? $forward_history : array(),
			'download_summary'    => is_array($summary_rows) ? $summary_rows : array(),
			'download_log'        => is_array($log_rows) ? $log_rows : array(),
			'can_edit'            => $can_edit,
			'list_url'            => site_url('admin/licensed_requests'),
			'default_email_to'    => isset($row['user']['email']) ? $row['user']['email'] : '',
		);
	}

	private function _notify_user_email($request_id)
	{
		$data = $this->Licensed_model->get_request_by_id($request_id);
		if (! $data) {
			return false;
		}

		$data        = (object) $data;
		$requestuser = $this->ion_auth->get_user($data->userid);
		if (! $requestuser) {
			return false;
		}

		$data->user_id = $requestuser->id;
		$data->fname   = $requestuser->first_name;
		$data->lname   = $requestuser->last_name;
		$data->email   = $requestuser->email;
		$data->title   = $data->request_title;
		$data->requestid = $request_id;

		$message = $this->load->view('access_licensed/user_notification_email', $data, true);

		$this->load->library('email');
		$this->email->clear();
		$this->email->initialize();
		$this->email->to($requestuser->email);
		$this->email->bcc($this->config->item('website_webmaster_email'), $this->config->item('website_webmaster_name'));
		$this->email->subject('[#' . $request_id . '] - Request status updated for ' . $data->title);
		$this->email->message($message);

		return $this->email->send();
	}

	private function _valid_ip_list($str)
	{
		if ($str === '') {
			return true;
		}

		$parts = explode(',', $str);
		foreach ($parts as $ip) {
			$ip = trim($ip);
			if ($ip === '') {
				continue;
			}

			if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param mixed $val ISO date or unix
	 * @return int end-of-day unix (legacy-aligned)
	 */
	private function _expiry_end_of_day_from_input($val)
	{
		if (is_numeric($val)) {
			$t = (int) $val;

			return $t > 0 ? $t + (24 * 3600) - 60 : 0;
		}

		$s = trim((string) $val);
		if ($s === '') {
			return 0;
		}

		$t = strtotime($s);

		return $t ? $t + (24 * 3600) - 60 : 0;
	}

	/**
	 * @param array|null $scope
	 * @return array list of [ 'repositoryid' => string, 'title' => string ]
	 */
	private function _bootstrap_collections($scope)
	{
		$output = array();

		$include_central = ($scope === null) || in_array('central', array_map('strtolower', $scope), true);
		if ($include_central) {
			$central = $this->Repository_model->get_central_catalog_array();
			if (! empty($central['repositoryid'])) {
				$output[] = array(
					'repositoryid' => $central['repositoryid'],
					'title'        => isset($central['title']) ? $central['title'] : 'Central',
				);
			}
		}

		$repos = $this->Repository_model->select_all(null, false);
		if (! is_array($repos)) {
			return $output;
		}

		$by_title = array();
		foreach ($repos as $row) {
			$rid = isset($row['repositoryid']) ? $row['repositoryid'] : '';
			if ($rid === '') {
				continue;
			}
			$lr = strtolower($rid);
			if ($scope !== null && ! in_array($lr, array_map('strtolower', $scope), true)) {
				continue;
			}
			$by_title[$row['title'] . "\0" . $rid] = array(
				'repositoryid' => $rid,
				'title'        => $row['title'],
			);
		}
		ksort($by_title, SORT_NATURAL);
		foreach ($by_title as $item) {
			$output[] = $item;
		}

		return $output;
	}

	/**
	 * @param array      $scope non-null allowlist
	 * @param string     $owner_repo
	 */
	private function _owner_repo_allowed($scope, $owner_repo)
	{
		$rid = strtolower(trim((string) $owner_repo));

		return in_array($rid, array_map('strtolower', $scope), true);
	}

	private function _get_page_size($default = 30, $min = 1, $max = 100)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= $min && $ps <= $max) {
			return $ps;
		}

		return $default;
	}
}
