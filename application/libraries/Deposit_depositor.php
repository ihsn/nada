<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Depositor-facing data-deposit operations (collaborator/owner auth).
 * Used by /api/datadeposit. Not staff review (/api/admin/datadeposit).
 */
class Deposit_depositor_exception extends Exception
{
	public $http;
	public $payload;

	public function __construct($message, $http = 400, $extra = array())
	{
		parent::__construct($message);
		$this->http = (int) $http;
		$body = array('status' => 'error', 'message' => $message);
		if (is_array($extra)) {
			$body = array_merge($body, $extra);
		}
		$this->payload = $body;
	}
}

class Deposit_depositor
{
	/** @var CI_Controller */
	protected $ci;

	protected $dctype_options_cache = null;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('DD_project_model');
		$this->ci->load->model('DD_resource_model');
		$this->ci->load->library('form_validation');
		$this->ci->lang->load('dd_projects');
		$this->ci->lang->load('catalog_search');
	}

	public function user_from_object($user)
	{
		if (!$user || !isset($user->id) || !isset($user->email)) {
			$this->fail('ACCESS_DENIED', 403);
		}
		return array(
			'id'         => (int) $user->id,
			'email'      => (string) $user->email,
			'username'   => isset($user->username) ? (string) $user->username : '',
			'first_name' => isset($user->first_name) ? (string) $user->first_name : '',
			'last_name'  => isset($user->last_name) ? (string) $user->last_name : '',
		);
	}

	public function user_from_session()
	{
		$email = (string) $this->ci->session->userdata('email');
		$uid = (int) $this->ci->session->userdata('user_id');
		if ($email === '' || $uid < 1) {
			$this->fail('ACCESS_DENIED', 403);
		}
		return array(
			'id'         => $uid,
			'email'      => $email,
			'username'   => (string) $this->ci->session->userdata('username'),
			'first_name' => '',
			'last_name'  => '',
		);
	}

	public function normalize_status($value)
	{
		$status = strtolower(trim((string) $value));
		$allowed = array('draft', 'submitted', 'accepted', 'processed', 'closed');
		if ($status === '' || !in_array($status, $allowed, true)) {
			return '';
		}
		return $status;
	}

	public function project_types()
	{
		return array(
			array('value' => 'survey', 'title' => t('dataset_type_survey'), 'icon' => 'mdi-database'),
			array('value' => 'timeseries', 'title' => t('tab_timeseries'), 'icon' => 'mdi-chart-line'),
			array('value' => 'timeseries-db', 'title' => t('tab_timeseriesdb'), 'icon' => 'mdi-chart-box-outline'),
			array('value' => 'geospatial', 'title' => t('dataset_type_geospatial'), 'icon' => 'mdi-map-outline'),
			array('value' => 'document', 'title' => t('dataset_type_document'), 'icon' => 'mdi-file-document-outline'),
			array('value' => 'table', 'title' => t('dataset_type_table'), 'icon' => 'mdi-table'),
			array('value' => 'image', 'title' => t('dataset_type_image'), 'icon' => 'mdi-image-outline'),
			array('value' => 'script', 'title' => t('tab_script'), 'icon' => 'mdi-code-braces'),
			array('value' => 'video', 'title' => t('tab_video'), 'icon' => 'mdi-video-outline'),
		);
	}

	public function normalize_data_type($value, $strict = false)
	{
		$type = strtolower(trim((string) $value));
		$aliases = array(
			'microdata'     => 'survey',
			'timeseriesdb'  => 'timeseries-db',
		);
		if (isset($aliases[$type])) {
			$type = $aliases[$type];
		}
		$allowed = array();
		foreach ($this->project_types() as $row) {
			$allowed[] = $row['value'];
		}
		if ($type === '') {
			return $strict ? null : 'survey';
		}
		if (!in_array($type, $allowed, true)) {
			return $strict ? null : 'survey';
		}
		return $type;
	}

	/**
	 * Deposit study + submit form templates for a project data type.
	 *
	 * @return array{form_template: array, submit_template: array}
	 */
	public function deposit_templates($data_type)
	{
		$this->ci->load->model('Editor_template_model');
		$type = $this->normalize_data_type($data_type);
		$form_tpl = $this->ci->Editor_template_model->get_default_core_template($type, 'deposit');
		if (!$form_tpl) {
			$form_tpl = $this->ci->Editor_template_model->get_default_core_template('survey', 'deposit');
		}
		$submit_tpl = $this->ci->Editor_template_model->get_default_core_template('submit', 'deposit');
		if (!$submit_tpl) {
			$submit_tpl = $this->ci->Editor_template_model->get_template_by_uid('deposit-submit-en');
		}
		$form = (isset($form_tpl['template']) && is_array($form_tpl['template'])) ? $form_tpl['template'] : null;
		$submit = (isset($submit_tpl['template']) && is_array($submit_tpl['template'])) ? $submit_tpl['template'] : null;
		if (!$form || !$submit) {
			$this->fail('TEMPLATE_NOT_FOUND', 500);
		}
		return array(
			'form_template'   => $form,
			'submit_template' => $submit,
		);
	}

	public function list_projects($uid, array $params)
	{
		$keywords = isset($params['keywords']) ? trim((string) $params['keywords']) : '';
		$status = $this->normalize_status(isset($params['status']) ? $params['status'] : '');
		$sort_by = isset($params['sort_by']) ? (string) $params['sort_by'] : 'created_on';
		$sort_order = isset($params['sort_order']) ? (string) $params['sort_order'] : 'desc';
		$page = isset($params['page']) ? (int) $params['page'] : 1;
		$page_size = isset($params['page_size']) ? (int) $params['page_size'] : 100;
		if ($page < 1) {
			$page = 1;
		}
		if ($page_size < 1) {
			$page_size = 100;
		}
		if ($page_size > 100) {
			$page_size = 100;
		}

		$total = $this->ci->DD_project_model->count_projects($uid, $keywords, $status);
		$total_pages = $total > 0 ? (int) ceil($total / $page_size) : 1;
		$page = min($page, $total_pages);
		if ($page < 1) {
			$page = 1;
		}
		$offset = ($page - 1) * $page_size;
		$projects = $this->ci->DD_project_model->projects(
			$uid,
			$sort_by,
			$sort_order,
			$page_size,
			$offset,
			$keywords,
			$status
		);

		return array(
			'status'      => 'success',
			'projects'    => $this->list_payload($projects),
			'total'       => $total,
			'page'        => $page,
			'page_size'   => $page_size,
			'total_pages' => $total_pages,
			'keywords'       => $keywords,
			'status_filter'  => $status,
		);
	}

	public function create_project(array $user, array $incoming)
	{
		$title = isset($incoming['title']) ? trim((string) $incoming['title']) : '';
		$shortname = isset($incoming['shortname']) ? trim((string) $incoming['shortname']) : '';
		$description = isset($incoming['description']) ? (string) $incoming['description'] : '';
		$data_type_raw = '';
		if (isset($incoming['data_type'])) {
			$data_type_raw = trim((string) $incoming['data_type']);
		} elseif (isset($incoming['dataType'])) {
			$data_type_raw = trim((string) $incoming['dataType']);
		}
		$data_type = $this->normalize_data_type($data_type_raw, true);
		$errors = array();

		if ($title === '') {
			$errors[] = array('property' => 'title', 'message' => 'Title is required');
		} elseif (strlen($title) > 255) {
			$errors[] = array('property' => 'title', 'message' => 'Title is too long');
		}
		if ($shortname === '') {
			$errors[] = array('property' => 'shortname', 'message' => 'Short name is required');
		} elseif (strlen($shortname) > 100) {
			$errors[] = array('property' => 'shortname', 'message' => 'Short name is too long');
		}
		if (strlen($description) > 1000) {
			$errors[] = array('property' => 'description', 'message' => 'Description is too long');
		}
		if ($data_type === null) {
			$errors[] = array('property' => 'data_type', 'message' => 'Invalid project type');
		}

		list($collaborators, $collab_errors) = $this->collaborators_from_input($incoming);
		$errors = array_merge($errors, $collab_errors);
		if ($errors) {
			$this->fail('VALIDATION_ERROR', 400, array('errors' => $errors));
		}

		$options = array(
			'title'         => $title,
			'shortname'     => $shortname,
			'description'   => $description,
			'collaborators' => $collaborators,
			'created_on'    => date('U'),
			'last_modified' => date('U'),
			'created_by'    => ucwords($user['username']),
			'status'        => 'draft',
			'data_type'     => $data_type ? $data_type : 'survey',
		);

		$new_id = $this->ci->DD_project_model->insert($options, $user['email']);
		if (!$new_id) {
			$this->fail('SAVE_FAILED', 500);
		}

		$this->write_history('Project created', $new_id, 'draft', $user['email']);
		$redirect = site_url('datadeposit/study/'.$new_id.'?step=info');

		return array(
			'status'       => 'success',
			'id'           => (int) $new_id,
			'redirect_url' => $redirect,
		);
	}

	public function get_project(array $user, $id)
	{
		$this->require_v2_access($user, $id);
		$row = $this->ci->DD_project_model->get_by_id($id);
		$status = isset($row['status']) ? strtolower((string) $row['status']) : 'draft';
		$data_type = $this->normalize_data_type(isset($row['data_type']) ? $row['data_type'] : 'survey');
		$type_labels = array();
		foreach ($this->project_types() as $type_row) {
			$type_labels[$type_row['value']] = $type_row['title'];
		}
		$templates = $this->deposit_templates($data_type);
		$email = isset($user['email']) ? $user['email'] : '';
		return array(
			'status'  => 'success',
			'project' => array(
				'id'               => (int) $id,
				'title'            => isset($row['title']) ? $row['title'] : '',
				'shortname'        => isset($row['shortname']) ? $row['shortname'] : '',
				'description'      => isset($row['description']) ? $row['description'] : '',
				'collaborators'    => isset($row['collaborators']) && is_array($row['collaborators'])
					? $row['collaborators']
					: array(),
				'status'           => $status,
				'created_by'       => isset($row['created_by']) ? (string) $row['created_by'] : '',
				'created_on'       => $this->format_date(isset($row['created_on']) ? $row['created_on'] : null),
				'last_modified'    => $this->format_date(isset($row['last_modified']) ? $row['last_modified'] : null),
				'data_type'        => $data_type,
				'data_type_label'  => isset($type_labels[$data_type]) ? $type_labels[$data_type] : $data_type,
				'schema_version'   => isset($row['schema_version']) ? (int) $row['schema_version'] : 1,
				'can_edit'         => $status === 'draft' && !$this->ci->DD_project_model->is_locked($id, $email),
				'can_export'       => true,
				'can_export_ddi'   => $data_type === 'survey',
				'files'            => $this->files_payload($id),
				'form_template'    => $templates['form_template'],
				'submit_template'  => $templates['submit_template'],
			),
		);
	}

	/**
	 * Project + v2 metadata/submission for the shared summary UI.
	 * No owner ACL — caller must authorize (staff view or depositor collaborator).
	 * File download_url is omitted; staff downloads land in 5.5.
	 *
	 * @return array{project: array, metadata: array, submission: array}|null
	 */
	public function summary_payload($id)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			return null;
		}

		$status = isset($row['status']) ? strtolower((string) $row['status']) : 'draft';
		$data_type = $this->normalize_data_type(isset($row['data_type']) ? $row['data_type'] : 'survey');
		$type_labels = array();
		foreach ($this->project_types() as $type_row) {
			$type_labels[$type_row['value']] = $type_row['title'];
		}

		$templates = array(
			'form_template'   => array('items' => array()),
			'submit_template' => array('items' => array()),
		);
		try {
			$templates = $this->deposit_templates($data_type);
		} catch (Deposit_depositor_exception $e) {
			// Summary still shows project info if templates are missing.
		}

		$files = $this->files_payload($id);
		foreach ($files as &$file) {
			$file['download_url'] = '';
		}
		unset($file);

		$schema_version = isset($row['schema_version']) ? (int) $row['schema_version'] : 1;
		$metadata = $this->normalize_metadata_for_form($this->ci->DD_project_model->get_metadata_json($id));
		$catalog_study_id = '';
		if ($schema_version >= 2) {
			if (isset($metadata['additional']['catalog_study_id'])) {
				$catalog_study_id = trim((string) $metadata['additional']['catalog_study_id']);
			}
		} else {
			$legacy_id = $this->ci->DD_project_model->get_study_id($id);
			$catalog_study_id = $legacy_id !== null ? trim((string) $legacy_id) : '';
		}

		$owners = $this->ci->DD_project_model->get_owner($id);
		if (!is_array($owners)) {
			$owners = array();
		}
		$collabs = (isset($row['collaborators']) && is_array($row['collaborators'])) ? $row['collaborators'] : array();
		$notify_recipients = array_values(array_unique(array_filter(array_merge($owners, $collabs))));
		$folder = $this->ci->DD_project_model->get_project_fullpath($id);
		$storage_path = $folder ? (string) $folder : '';

		return array(
			'project' => array(
				'id'              => (int) $row['id'],
				'title'           => isset($row['title']) ? $row['title'] : '',
				'shortname'       => isset($row['shortname']) ? $row['shortname'] : '',
				'description'     => isset($row['description']) ? $row['description'] : '',
				'owners'          => $owners,
				'collaborators'   => $collabs,
				'status'          => $status,
				'created_by'      => isset($row['created_by']) ? (string) $row['created_by'] : '',
				'created_on'      => $this->format_date(isset($row['created_on']) ? $row['created_on'] : null),
				'last_modified'   => $this->format_date(isset($row['last_modified']) ? $row['last_modified'] : null),
				'data_type'       => $data_type,
				'data_type_label' => isset($type_labels[$data_type]) ? $type_labels[$data_type] : $data_type,
				'schema_version'    => $schema_version,
				'admin_comments'    => isset($row['admin_comments']) ? (string) $row['admin_comments'] : '',
				'catalog_study_id'  => $catalog_study_id,
				'notify_recipients' => $notify_recipients,
				'storage_path'      => $storage_path,
				'files'             => $files,
				'form_template'     => $templates['form_template'],
				'submit_template'   => $templates['submit_template'],
			),
			'metadata'   => $metadata,
			'submission' => $this->normalize_submission_for_form($this->ci->DD_project_model->get_submission_json($id)),
		);
	}

	/**
	 * Staff process tab: status, catalog study id, admin comments, optional notify.
	 *
	 * @return array{status: string, message: string, notified: bool, result: array}
	 */
	public function admin_process_project($id, array $incoming, $actor_email)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}

		$status_in = strtolower(trim((string) (isset($incoming['status']) ? $incoming['status'] : '')));
		if ($status_in === 'reopen') {
			$status_in = 'draft';
		}
		$allowed = array('draft', 'submitted', 'accepted', 'processed', 'closed');
		if (!in_array($status_in, $allowed, true)) {
			$this->fail('INVALID_STATUS', 400);
		}

		$comments = isset($incoming['admin_comments']) ? trim((string) $incoming['admin_comments']) : '';
		$notify = !empty($incoming['notify']);
		$catalog = isset($incoming['catalog_study_id']) ? trim((string) $incoming['catalog_study_id']) : '';

		$prev_status = isset($row['status']) ? strtolower((string) $row['status']) : '';
		$prev_comments = isset($row['admin_comments']) ? (string) $row['admin_comments'] : '';
		$schema_version = isset($row['schema_version']) ? (int) $row['schema_version'] : 1;

		$update = array(
			'status'         => $status_in,
			'admin_comments' => $comments,
			'last_modified'  => date('U'),
		);
		if ($status_in === 'draft') {
			$update['requested_reopen'] = 0;
		}

		if (!$this->ci->DD_project_model->update($id, $update)) {
			$this->fail('SAVE_FAILED', 500);
		}

		if ($schema_version >= 2) {
			$this->set_catalog_study_id_v2($id, $catalog);
		} else {
			$this->ci->DD_project_model->set_study_id($id, $catalog);
		}

		if ($prev_status !== $status_in || $prev_comments !== $comments) {
			$this->ci->DD_project_model->write_history($id, $status_in, $comments, $actor_email);
		}

		$notified = false;
		if ($notify) {
			$notified = $this->admin_notify_status_change($id, $comments);
		}

		return array(
			'status'   => 'success',
			'message'  => 'Project status updated successfully!',
			'notified' => $notified,
			'result'   => $this->summary_payload($id),
		);
	}

	protected function set_catalog_study_id_v2($id, $catalog_study_id)
	{
		$metadata = $this->ci->DD_project_model->get_metadata_json($id);
		if (!isset($metadata['additional']) || !is_array($metadata['additional'])) {
			$metadata['additional'] = array();
		}
		$value = trim((string) $catalog_study_id);
		if ($value === '') {
			unset($metadata['additional']['catalog_study_id']);
		} else {
			$metadata['additional']['catalog_study_id'] = $value;
		}
		if (!$this->ci->DD_project_model->save_json_column($id, 'metadata', $metadata)) {
			$this->fail('SAVE_FAILED', 500);
		}
	}

	/**
	 * Notify owner + collaborators. Body uses v2 summary_html(), not get_project_summary().
	 */
	public function admin_notify_status_change($id, $comments = '')
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			return false;
		}

		$title = isset($row['title']) ? (string) $row['title'] : '';
		$project_url = site_url('datadeposit/summary/'.$id);
		$message = 'Project status was updated, to see the project visit: <a href="'.htmlspecialchars($project_url, ENT_QUOTES, 'UTF-8').'">'.$project_url.'</a>';
		if (trim((string) $comments) !== '') {
			$message .= '<div style="margin-top:15px;">Admin comments:</div>';
			$message .= '<div style="font-weight:bold;margin-top:10px;">'.nl2br(htmlspecialchars((string) $comments, ENT_QUOTES, 'UTF-8')).'</div>';
		}

		$to = $this->ci->DD_project_model->get_project_owner_email($id);
		$cc = implode(',', $this->ci->DD_project_model->get_collaborators($id));
		if ($to === '' && $cc === '') {
			return false;
		}

		return $this->send_project_html_email($id, $to, $cc, '[Status updated - #'.$id.'] - '.$title, $message);
	}

	/**
	 * Staff compose email. Attaches v2 summary_html(). Writes history on send.
	 *
	 * @return array{status: string, message: string}
	 */
	public function admin_send_compose_email($id, array $incoming, $actor_email)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}

		$to_list = $this->parse_email_list(isset($incoming['to']) ? $incoming['to'] : '');
		$cc_list = $this->parse_email_list(isset($incoming['cc']) ? $incoming['cc'] : '');
		$subject = trim((string) (isset($incoming['subject']) ? $incoming['subject'] : ''));
		$body = trim((string) (isset($incoming['body']) ? $incoming['body'] : ''));

		if (!$to_list) {
			$this->fail('Email recipient (TO) is required.', 400);
		}
		if ($subject === '') {
			$this->fail('Subject is required.', 400);
		}
		if ($body === '') {
			$this->fail('Message body is required.', 400);
		}

		$this->ci->load->library('form_validation');
		$invalid = array();
		foreach (array_merge($to_list, $cc_list) as $email) {
			if (!$this->ci->form_validation->valid_email($email)) {
				$invalid[] = $email;
			}
		}
		if ($invalid) {
			$this->fail('Invalid email: '.implode(', ', $invalid), 400);
		}

		$message = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
		$sent = $this->send_project_html_email($id, $to_list, $cc_list, $subject, $message);
		if (!$sent) {
			$this->fail('Failed to send email. Check all form fields and try again.', 500);
		}

		$status = isset($row['status']) ? (string) $row['status'] : '';
		$this->ci->DD_project_model->write_history($id, $status, '<i>Email:</i> '.$message, $actor_email);

		return array(
			'status'  => 'success',
			'message' => 'Email was sent!',
		);
	}

	/**
	 * Staff history tab. Same comment-only skip as tab_history.php.
	 *
	 * @return array{status: string, result: array{items: array}}
	 */
	public function admin_project_history($id)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}

		$raw = $this->ci->DD_project_model->history_id($id);
		if (!is_array($raw)) {
			$raw = array();
		}

		$items = array();
		foreach ($raw as $log) {
			$obj = is_object($log);
			$comments = $obj
				? (isset($log->comments) ? (string) $log->comments : '')
				: (isset($log['comments']) ? (string) $log['comments'] : '');
			if ($this->is_comment_only_history($comments)) {
				continue;
			}

			$created = $obj
				? (isset($log->created_on) ? $log->created_on : 0)
				: (isset($log['created_on']) ? $log['created_on'] : 0);
			$created = (int) $created;

			$items[] = array(
				'id'          => $obj
					? (isset($log->id) ? (int) $log->id : 0)
					: (int) (isset($log['id']) ? $log['id'] : 0),
				'identity'    => $obj
					? (isset($log->user_identity) ? (string) $log->user_identity : '')
					: (string) (isset($log['user_identity']) ? $log['user_identity'] : ''),
				'created_on'  => $created > 0 ? date('Y-m-d H:i:s', $created) : '',
				'status'      => $obj
					? (isset($log->project_status) ? (string) $log->project_status : '')
					: (string) (isset($log['project_status']) ? $log['project_status'] : ''),
				'description' => $comments,
			);
		}

		return array(
			'status' => 'success',
			'result' => array('items' => $items),
		);
	}

	/**
	 * Staff delete. ACL is enforced by the API. Logs sitelogs + audit_logs.
	 *
	 * @param array<int, int|string> $ids
	 * @return array{status: string, message: string, result: array}
	 */
	public function admin_delete_projects(array $ids, $actor_email, $actor_id = null)
	{
		$clean = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id > 0 && !in_array($id, $clean, true)) {
				$clean[] = $id;
			}
		}
		if (!$clean) {
			$this->fail('No projects selected.', 400);
		}

		if (!isset($this->ci->db_logger)) {
			$this->ci->load->library('db_logger');
		}
		$this->ci->load->model('Audit_log_model');

		$deleted = array();
		$missing = array();
		foreach ($clean as $id) {
			$row = $this->ci->DD_project_model->get_by_id($id);
			if (!$row || empty($row['id'])) {
				$missing[] = $id;
				continue;
			}

			$title = isset($row['title']) ? (string) $row['title'] : '';
			$who = $actor_email !== '' ? $actor_email : 'staff';

			$this->ci->db_logger->write_log(
				'data-deposit',
				$who.' deleted project '.$id.' - '.$title,
				'delete'
			);

			if ($this->ci->db->table_exists('audit_logs')) {
				try {
					$this->ci->Audit_log_model->insert(array(
						'log_type'   => 'delete',
						'category'   => 'data-deposit',
						'username'   => $who,
						'user_id'    => $actor_id !== null && $actor_id !== '' ? (int) $actor_id : null,
						'severity'   => 'info',
						'ip_address' => $this->ci->input->ip_address(),
						'details'    => array(
							'project_id' => $id,
							'title'      => $title,
						),
					));
				} catch (Exception $e) {
					log_message('error', 'audit_logs insert failed for data-deposit delete: '.$e->getMessage());
				}
			}

			$this->ci->DD_project_model->delete($id);
			$deleted[] = array(
				'id'    => $id,
				'title' => $title,
			);
		}

		if (!$deleted) {
			$this->fail('Project was not found', 404);
		}

		$count = count($deleted);
		return array(
			'status'  => 'success',
			'message' => $count === 1 ? 'Project deleted' : $count.' projects deleted',
			'result'  => array(
				'deleted' => $deleted,
				'missing' => $missing,
			),
		);
	}

	/**
	 * @return array{status: string, result: array}
	 */
	public function admin_assign_payload($id)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}

		$this->ci->load->model('DD_tasks_team_model');
		$team = $this->ci->DD_tasks_team_model->get_tasks_team_array();
		if (!is_array($team)) {
			$team = array();
		}

		$task = $this->ci->db->select('id, user_id, status')
			->from('dd_tasks')
			->where('project_id', (int) $id)
			->order_by('id', 'desc')
			->limit(1)
			->get()
			->row_array();

		$members = array();
		foreach ($team as $member) {
			$members[] = array(
				'id'         => isset($member['id']) ? (int) $member['id'] : 0,
				'first_name' => isset($member['first_name']) ? (string) $member['first_name'] : '',
				'last_name'  => isset($member['last_name']) ? (string) $member['last_name'] : '',
				'email'      => isset($member['email']) ? (string) $member['email'] : '',
			);
		}

		return array(
			'status' => 'success',
			'result' => array(
				'project' => array(
					'id'    => (int) $row['id'],
					'title' => isset($row['title']) ? $row['title'] : '',
				),
				'team'             => $members,
				'assigned_user_id' => ($task && !empty($task['user_id'])) ? (int) $task['user_id'] : null,
			),
		);
	}

	/**
	 * @return array{status: string, message: string}
	 */
	public function admin_assign_task($id, $user_id, $assigner_id)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}

		$user_id = (int) $user_id;
		$assigner_id = (int) $assigner_id;
		if ($user_id <= 0) {
			$this->fail('User is required.', 400);
		}
		if ($assigner_id <= 0) {
			$this->fail('ACCESS_DENIED', 403);
		}

		$this->ci->load->model('DD_tasks_team_model');
		$this->ci->load->model('DD_tasks_model');
		$allowed = false;
		foreach ($this->ci->DD_tasks_team_model->get_tasks_team_array() as $member) {
			if ((int) $member['id'] === $user_id) {
				$allowed = true;
				break;
			}
		}
		if (!$allowed) {
			$this->fail('User is required.', 400);
		}

		if (!$this->ci->DD_tasks_model->assign_task($id, $user_id, $assigner_id)) {
			$this->fail(t('form_update_fail'), 500);
		}

		ob_start();
		$this->ci->DD_tasks_model->send_status_notification(array(
			'assigned_by' => $assigner_id,
			'assigned_to' => $user_id,
			'project_id'  => $id,
			'status'      => 0,
		));
		ob_end_clean();

		return array(
			'status'  => 'success',
			'message' => t('form_update_success'),
		);
	}

	/**
	 * All-tasks list: pending + completed in the last 3 days.
	 *
	 * @return array{status: string, result: array}
	 */
	public function admin_tasks_list()
	{
		$this->ci->load->model('DD_tasks_model');
		$pending = $this->ci->DD_tasks_model->get_tasks_by_status(0);
		$completed = $this->ci->DD_tasks_model->get_tasks_by_status(1, 3);
		return array(
			'status' => 'success',
			'result' => array(
				'pending'   => $this->normalize_admin_tasks($pending),
				'completed' => $this->normalize_admin_tasks($completed),
			),
		);
	}

	/**
	 * My tasks: assigned to the current user + tasks they assigned.
	 *
	 * @param int $user_id
	 * @return array{status: string, result: array}
	 */
	public function admin_my_tasks_list($user_id)
	{
		$this->ci->load->model('DD_tasks_model');
		$uid = (int) $user_id;
		$mine = $this->ci->DD_tasks_model->get_tasks_by_user($uid);
		$assigned = $this->ci->DD_tasks_model->get_tasks_by_assigner($uid);
		return array(
			'status' => 'success',
			'result' => array(
				'assigned_to_me' => $this->normalize_admin_tasks($mine),
				'assigned_by_me' => $this->normalize_admin_tasks($assigned),
			),
		);
	}

	/**
	 * Single task for the staff detail page.
	 *
	 * @param int $task_id
	 * @return array{status: string, result: array}
	 */
	public function admin_task_detail($task_id)
	{
		$this->ci->load->model('DD_tasks_model');
		$row = $this->ci->DD_tasks_model->select_single($task_id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		$project = $this->ci->DD_project_model->get_by_id($row['project_id']);
		if ($project && !empty($project['title'])) {
			$row['project_title'] = $project['title'];
		}
		$normalized = $this->normalize_admin_tasks(array($row));
		if (empty($normalized[0])) {
			$this->fail('NOT_FOUND', 404);
		}
		return array(
			'status' => 'success',
			'result' => $normalized[0],
		);
	}

	/**
	 * Resolve (1) or re-open (0). Emails on change, same payload as PHP update().
	 *
	 * @param int $task_id
	 * @param int $status_code
	 * @return array{status: string, result: array}
	 */
	public function admin_update_task($task_id, $status_code)
	{
		$this->ci->load->model('DD_tasks_model');
		$status_code = (int) $status_code;
		if ($status_code !== 0 && $status_code !== 1) {
			$this->fail('INVALID_STATUS', 400);
		}
		$row = $this->ci->DD_tasks_model->select_single($task_id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		if (!$this->ci->DD_tasks_model->update_task($task_id, $status_code)) {
			$this->fail(t('form_update_fail'), 500);
		}
		$task = $this->ci->DD_tasks_model->select_single($task_id);
		if (!$task) {
			$this->fail('NOT_FOUND', 404);
		}
		ob_start();
		$this->ci->DD_tasks_model->send_status_notification(array(
			'assigned_by' => $task['user_id'],
			'assigned_to' => $task['assigner_id'],
			'project_id'  => $task['project_id'],
			'status'      => $task['status'],
		));
		ob_end_clean();
		return $this->admin_task_detail($task_id);
	}

	/**
	 * Unassign: delete the task row and write a sitelog.
	 *
	 * @param int $task_id
	 * @param string $actor
	 * @return array{status: string, message: string}
	 */
	public function admin_delete_task($task_id, $actor)
	{
		$this->ci->load->model('DD_tasks_model');
		$task = $this->ci->DD_tasks_model->select_single($task_id);
		if (!$task || empty($task['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		if (!isset($this->ci->db_logger)) {
			$this->ci->load->library('db_logger');
		}
		$who = $actor !== '' ? $actor : 'staff';
		$this->ci->db_logger->write_log(
			'data-deposit',
			$who.' deleted task '.$task['project_id'],
			'delete'
		);
		$this->ci->DD_tasks_model->delete($task_id);
		return array(
			'status'  => 'success',
			'message' => t('form_update_success'),
		);
	}

	/**
	 * @param mixed $rows
	 * @return array<int, array>
	 */
	protected function normalize_admin_tasks($rows)
	{
		if (!is_array($rows)) {
			return array();
		}
		$out = array();
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$out[] = array(
				'id'             => isset($row['id']) ? (int) $row['id'] : 0,
				'project_id'     => isset($row['project_id']) ? (int) $row['project_id'] : 0,
				'project_title'  => isset($row['project_title']) ? (string) $row['project_title'] : '',
				'status'         => isset($row['status']) ? (int) $row['status'] : 0,
				'assigned_to'    => isset($row['task_user']) ? (string) $row['task_user'] : '',
				'assigned_by'    => isset($row['assigner']) ? (string) $row['assigner'] : '',
				'date_assigned'  => isset($row['date_assigned']) ? (int) $row['date_assigned'] : 0,
				'date_completed' => !empty($row['date_completed']) ? (int) $row['date_completed'] : null,
			);
		}
		return $out;
	}

	/**
	 * Match tab_history.php: skip JSON comment blobs and Comment: prefixes.
	 */
	protected function is_comment_only_history($comments)
	{
		$decoded = json_decode((string) $comments);
		if (is_object($decoded)) {
			return true;
		}
		if (strpos((string) $comments, '<i>Comment:</i>') === 0) {
			return true;
		}
		if (strpos((string) $comments, 'Comment:') === 0) {
			return true;
		}
		return false;
	}

	/**
	 * @param string|array $to
	 * @param string|array $cc
	 */
	protected function send_project_html_email($id, $to, $cc, $subject, $message)
	{
		$this->ci->load->helper('email');
		$this->ci->load->library('email');

		$data = array(
			'content' => $this->summary_html($id),
			'message' => $message,
		);
		$css_path = APPPATH.'../themes/datadeposit/email.css';
		$css = is_file($css_path) ? file_get_contents($css_path) : '';
		$contents = $this->ci->load->view('datadeposit/emails/template', $data, true);
		if ($css !== '') {
			$this->ci->load->library('CssToInlineStyles');
			$this->ci->csstoinlinestyles->setCSS($css);
			$this->ci->csstoinlinestyles->setHTML($contents);
			$contents = $this->ci->csstoinlinestyles->convert();
		}

		$this->ci->email->clear();
		$this->ci->email->initialize();
		if ($to) {
			$this->ci->email->to($to);
		}
		if ($cc) {
			$this->ci->email->cc($cc);
		}
		$this->ci->email->subject($subject);
		$this->ci->email->message($contents);

		return (bool) @$this->ci->email->send();
	}

	/**
	 * @param string|array $value
	 * @return array<int, string>
	 */
	protected function parse_email_list($value)
	{
		if (is_array($value)) {
			$raw = implode(',', $value);
		} else {
			$raw = (string) $value;
		}
		$out = array();
		foreach (preg_split('/[,;]+/', $raw) as $part) {
			$email = strtolower(trim($part));
			if ($email !== '') {
				$out[] = $email;
			}
		}
		return array_values(array_unique($out));
	}

	/**
	 * POST /api/datadeposit/{id}/email
	 * Body: { "emails": ["a@b.c", ...] } or { "email": "a@b.c, d@e.f" }. Max 5.
	 */
	public function email_summary(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);

		$emails = $this->emails_from_share_input($incoming);
		if (count($emails) > 5) {
			$this->fail(t('email_max_recipients'), 400, array(
				'errors' => array(array('property' => 'emails', 'message' => t('email_max_recipients'))),
			));
		}
		if (!$emails) {
			$this->fail(t('provide_email'), 400, array(
				'errors' => array(array('property' => 'emails', 'message' => t('provide_email'))),
			));
		}

		$invalid = array();
		foreach ($emails as $email) {
			if (!$this->ci->form_validation->valid_email($email)) {
				$invalid[] = $email;
			}
		}
		if ($invalid) {
			$this->fail(t('invalid_email'), 400, array(
				'errors' => array(array(
					'property' => 'emails',
					'message'  => t('invalid_email').' '.implode(', ', $invalid),
				)),
			));
		}

		$row = $this->ci->DD_project_model->get_by_id($id);
		$title = isset($row['title']) ? (string) $row['title'] : '';
		$user_name = trim((isset($user['first_name']) ? $user['first_name'] : '').' '.(isset($user['last_name']) ? $user['last_name'] : ''));
		if ($user_name === '') {
			$user_name = isset($user['username']) ? (string) $user['username'] : '';
		}
		if ($user_name === '') {
			$user_name = isset($user['email']) ? (string) $user['email'] : '';
		}

		$this->ci->load->helper('email');
		$this->ci->load->library('email');

		$data = array(
			'content' => $this->summary_html($id),
			'message' => sprintf(t('email_user_shared_project'), $user_name, $title),
		);

		$css_path = APPPATH.'../themes/datadeposit/email.css';
		$css = is_file($css_path) ? file_get_contents($css_path) : '';
		$contents = $this->ci->load->view('datadeposit/emails/template', $data, true);

		if ($css !== '') {
			$this->ci->load->library('CssToInlineStyles');
			$this->ci->csstoinlinestyles->setCSS($css);
			$this->ci->csstoinlinestyles->setHTML($contents);
			$contents = $this->ci->csstoinlinestyles->convert();
		}

		$this->ci->email->clear();
		$this->ci->email->initialize();
		$this->ci->email->to($emails);
		$this->ci->email->subject($title !== '' ? $title : t('summary'));
		$this->ci->email->message($contents);

		if (!@$this->ci->email->send()) {
			$this->fail('EMAIL_FAILED', 500);
		}

		$this->write_history(
			'Summary emailed to '.implode(', ', $emails),
			$id,
			$this->project_status($user, $id),
			$user['email']
		);

		return array(
			'status'  => 'success',
			'message' => t('email_sent_successful'),
			'emails'  => $emails,
		);
	}

	/**
	 * HTML project summary from v2 metadata + submission JSON (not dd_study).
	 */
	public function summary_html($id)
	{
		$this->ci->lang->load('catalog_admin');
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row) {
			return '';
		}

		$data_type = $this->normalize_data_type(isset($row['data_type']) ? $row['data_type'] : 'survey');
		$type_labels = array();
		foreach ($this->project_types() as $type_row) {
			$type_labels[$type_row['value']] = $type_row['title'];
		}

		$templates = $this->deposit_templates($data_type);
		$form_items = (isset($templates['form_template']['items']) && is_array($templates['form_template']['items']))
			? $templates['form_template']['items']
			: array();
		$submit_items = (isset($templates['submit_template']['items']) && is_array($templates['submit_template']['items']))
			? $templates['submit_template']['items']
			: array();

		$metadata = $this->normalize_metadata_for_form($this->ci->DD_project_model->get_metadata_json($id));
		$submission = $this->normalize_submission_for_form($this->ci->DD_project_model->get_submission_json($id));
		$collaborators = (isset($row['collaborators']) && is_array($row['collaborators'])) ? $row['collaborators'] : array();
		$preview_labels = array(
			'trueLabel'  => t('metadata_true'),
			'falseLabel' => t('metadata_false'),
		);

		$info_rows = array(
			array('label' => t('title'), 'value' => isset($row['title']) ? $row['title'] : ''),
			array('label' => t('shortname'), 'value' => isset($row['shortname']) ? $row['shortname'] : ''),
			array('label' => t('description'), 'value' => isset($row['description']) ? $row['description'] : ''),
			array('label' => t('collaborator'), 'value' => implode(', ', $collaborators)),
			array('label' => t('type'), 'value' => isset($type_labels[$data_type]) ? $type_labels[$data_type] : $data_type),
			array('label' => t('created_by'), 'value' => isset($row['created_by']) ? $row['created_by'] : ''),
			array('label' => 'Date created', 'value' => $this->format_date(isset($row['created_on']) ? $row['created_on'] : null)),
			array('label' => 'Last modified', 'value' => $this->format_date(isset($row['last_modified']) ? $row['last_modified'] : null)),
			array('label' => 'Status', 'value' => isset($row['status']) ? $row['status'] : ''),
			array('label' => 'Project ID', 'value' => (string) $id),
		);
		$info_rows = array_values(array_filter($info_rows, function ($r) {
			return trim((string) $r['value']) !== '';
		}));

		return $this->ci->load->view('datadeposit/emails/summary_body', array(
			'info_rows'          => $info_rows,
			'metadata_sections'  => $this->summary_sections($form_items, $metadata, $preview_labels),
			'access_sections'    => $this->summary_sections($submit_items, $submission, $preview_labels),
			'files'              => $this->files_payload($id),
			'labels'             => array(
				'step_info'     => t('project_info'),
				'step_metadata' => t('study_desc'),
				'step_files'    => t('files_tab'),
				'step_access'   => 'Access and notes',
				'no_preview'    => t('metadata_no_preview'),
				'no_files'      => t('no_files'),
				'title'         => t('title'),
				'resource_type' => t('type'),
			),
		), true);
	}

	/**
 * GET /api/datadeposit/{id}/export/{format}
 * format: ddi | json | metadata | project | rdf | external_resources
 * json and metadata are the same (study/dataset JSON).
	 *
	 * @return array{filename: string, mime: string, body: string}
	 */
	public function export_project(array $user, $id, $format)
	{
		$this->require_v2_access($user, $id);
		$file = $this->export_project_file($id, $format);
		$row = $this->ci->DD_project_model->get_by_id($id);
		$title = isset($row['title']) ? (string) $row['title'] : '';
		$this->write_history(
			$this->export_history_comment($format, $title),
			$id,
			$this->project_status($user, $id),
			isset($user['email']) ? $user['email'] : ''
		);
		return $file;
	}

	/**
	 * Staff export (ACL already checked). Same v2 files as depositor export.
	 *
	 * @return array{filename: string, mime: string, body: string}
	 */
	public function admin_export_project($id, $format, $actor_email)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		$file = $this->export_project_file($id, $format);
		$title = isset($row['title']) ? (string) $row['title'] : '';
		$status = isset($row['status']) ? (string) $row['status'] : '';
		$this->write_history($this->export_history_comment($format, $title), $id, $status, $actor_email);
		return $file;
	}

	/**
	 * @return array{filename: string, mime: string, body: string}
	 */
	protected function export_project_file($id, $format)
	{
		$format = strtolower(trim((string) $format));
		if ($format === 'metadata') {
			$format = 'json';
		}
		$allowed = array('ddi', 'json', 'project', 'rdf', 'external_resources');
		if (!in_array($format, $allowed, true)) {
			$this->fail('INVALID_FORMAT', 400);
		}

		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		$data_type = $this->normalize_data_type(isset($row['data_type']) ? $row['data_type'] : 'survey');
		$stamp = date('Y_m_d');
		$prefix = ((int) $id).'_'.$stamp;

		if ($format === 'ddi') {
			if ($data_type !== 'survey') {
				$this->fail('DDI_SURVEY_ONLY', 400);
			}
			return array(
				'filename' => strtolower($prefix.'_ddi.xml'),
				'mime'     => 'application/xml',
				'body'     => $this->export_ddi_xml($id, $row),
			);
		}

		if ($format === 'json') {
			return array(
				'filename' => strtolower($prefix.'_metadata.json'),
				'mime'     => 'application/json',
				'body'     => $this->export_encode_json($this->export_metadata_json($id, $data_type)),
			);
		}

		if ($format === 'project') {
			return array(
				'filename' => strtolower($prefix.'_project.json'),
				'mime'     => 'application/json',
				'body'     => $this->export_encode_json($this->export_project_json($id, $row, $data_type)),
			);
		}

		if ($format === 'rdf') {
			return array(
				'filename' => strtolower($prefix.'_rdf.xml'),
				'mime'     => 'application/rdf+xml',
				'body'     => $this->export_resources_rdf_xml($id),
			);
		}

		return array(
			'filename' => strtolower($prefix.'_external_resources.json'),
			'mime'     => 'application/json',
			'body'     => $this->export_encode_json($this->export_external_resources($id)),
		);
	}

	protected function export_history_comment($format, $title)
	{
		$format = strtolower(trim((string) $format));
		if ($format === 'metadata') {
			$format = 'json';
		}
		$labels = array(
			'ddi'                => 'Study '.$title.' exported as DDI',
			'json'               => 'Study '.$title.' exported as metadata JSON',
			'project'            => 'Study '.$title.' exported as project JSON',
			'rdf'                => 'Study '.$title.' exported as RDF',
			'external_resources' => 'Study '.$title.' exported as external resources JSON',
		);
		return isset($labels[$format]) ? $labels[$format] : 'Study '.$title.' exported';
	}

	protected function export_ddi_xml($id, array $row)
	{
		$metadata = $this->ci->DD_project_model->get_metadata_json($id);
		if (!is_array($metadata)) {
			$metadata = array();
		}
		$idno = '';
		if (isset($metadata['study_desc']['title_statement']['idno'])) {
			$idno = trim((string) $metadata['study_desc']['title_statement']['idno']);
		}
		if ($idno === '') {
			$idno = 'P-'.(int) $id;
		}

		$this->ci->load->library('DDI_Writer');
		try {
			$xml = $this->ci->ddi_writer->generate_ddi_from_metadata($metadata, $idno, 'memory');
		} catch (Deposit_depositor_exception $e) {
			throw $e;
		} catch (Exception $e) {
			$this->fail('DDI_EXPORT_FAILED', 500);
		}
		if (!is_string($xml) || $xml === '') {
			$this->fail('DDI_EXPORT_FAILED', 500);
		}
		return $xml;
	}

	protected function export_metadata_json($id, $data_type)
	{
		$metadata = $this->ci->DD_project_model->get_metadata_json($id);
		if (!is_array($metadata)) {
			$metadata = array();
		}
		$out = $metadata;
		$out['type'] = $data_type;
		return $out;
	}

	protected function export_project_json($id, array $row, $data_type)
	{
		$collaborators = (isset($row['collaborators']) && is_array($row['collaborators'])) ? $row['collaborators'] : array();
		$metadata = $this->ci->DD_project_model->get_metadata_json($id);
		$submission = $this->ci->DD_project_model->get_submission_json($id);
		return array(
			'type'                 => 'datadeposit-project',
			'version'              => 2,
			'exported_at'          => $this->format_iso_timestamp(time()),
			'project'              => array(
				'id'            => (int) $id,
				'title'         => isset($row['title']) ? $row['title'] : '',
				'shortname'     => isset($row['shortname']) ? $row['shortname'] : '',
				'description'   => isset($row['description']) ? $row['description'] : '',
				'status'        => isset($row['status']) ? $row['status'] : '',
				'data_type'     => $data_type,
				'collaborators' => $collaborators,
				'created_by'    => isset($row['created_by']) ? $row['created_by'] : '',
				'created_on'    => $this->format_iso_timestamp(isset($row['created_on']) ? $row['created_on'] : null),
				'last_modified' => $this->format_iso_timestamp(isset($row['last_modified']) ? $row['last_modified'] : null),
			),
			'metadata'             => $this->export_json_map($metadata),
			'submission'           => $this->export_json_map($submission),
			'external_resources'   => $this->export_external_resources($id),
		);
	}

	protected function export_external_resources($id)
	{
		$keys = array(
			'filename', 'title', 'subtitle', 'author', 'dcdate', 'country', 'language',
			'contributor', 'publisher', 'rights', 'toc', 'abstract', 'description',
			'dctype', 'dcformat', 'filesize',
		);
		$out = array();
		foreach ($this->ci->DD_resource_model->get_project_resources($id) as $file) {
			$row = array();
			foreach ($keys as $key) {
				if (!isset($file[$key])) {
					continue;
				}
				$value = $file[$key];
				if ($value === null || $value === '') {
					continue;
				}
				$row[$key] = $value;
			}
			if ($row) {
				$out[] = $row;
			}
		}
		return $out;
	}

	protected function export_resources_rdf_xml($id)
	{
		$this->ci->load->model('Catalog_model');
		$rows = array();
		foreach ($this->ci->DD_resource_model->get_project_resources($id) as $file) {
			$file['rdf_about'] = isset($file['filename']) ? $file['filename'] : '';
			$rows[] = $file;
		}
		return $this->ci->Catalog_model->rdf_xml_from_resources($rows);
	}

	protected function export_encode_json($payload)
	{
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($json === false) {
			$this->fail('JSON_ENCODE_FAILED', 500);
		}
		return $json;
	}

	/**
	 * Encode an object map; empty metadata/submission must be {} not [].
	 */
	protected function export_json_map($value)
	{
		if (!is_array($value) || !$value) {
			return new stdClass();
		}
		return $value;
	}

	public function update_project(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$title = isset($incoming['title']) ? trim((string) $incoming['title']) : '';
		$shortname = isset($incoming['shortname']) ? trim((string) $incoming['shortname']) : '';
		$errors = array();
		if ($title === '') {
			$errors[] = array('property' => 'title', 'message' => 'Title is required');
		}
		if ($shortname === '') {
			$errors[] = array('property' => 'shortname', 'message' => 'Short name is required');
		}

		list($collaborators, $collab_errors) = $this->collaborators_from_input($incoming);
		$errors = array_merge($errors, $collab_errors);
		if ($errors) {
			$this->fail('VALIDATION_ERROR', 400, array('errors' => $errors));
		}

		$options = array(
			'title'         => $title,
			'shortname'     => $shortname,
			'description'   => isset($incoming['description']) ? (string) $incoming['description'] : '',
			'collaborators' => $collaborators,
			'last_modified' => date('U'),
		);

		if (!$this->ci->DD_project_model->update($id, $options)) {
			$this->fail('SAVE_FAILED', 500);
		}

		$this->write_history('Project updated', $id, $this->project_status($user, $id), $user['email']);
		$row = $this->ci->DD_project_model->get_by_id($id);
		return array(
			'status'  => 'success',
			'project' => array(
				'title'         => isset($row['title']) ? $row['title'] : $title,
				'shortname'     => isset($row['shortname']) ? $row['shortname'] : $shortname,
				'description'   => isset($row['description']) ? $row['description'] : '',
				'collaborators' => isset($row['collaborators']) && is_array($row['collaborators'])
					? $row['collaborators']
					: $collaborators,
			),
		);
	}

	public function delete_project(array $user, $id)
	{
		$project = $this->require_access($user, $id);
		if (!isset($project[0]->access) || $project[0]->access !== 'owner') {
			$this->fail('ACCESS_DENIED', 403);
		}
		if (!isset($project[0]->status) || strtolower((string) $project[0]->status) !== 'draft') {
			$this->fail('PROJECT_LOCKED', 403);
		}

		$this->ci->DD_project_model->delete($id);
		$this->write_history('Project deleted', $id, $project[0]->status, $user['email']);
		return array(
			'status'  => 'success',
			'message' => t('project_deleted'),
		);
	}

	public function reopen_project(array $user, $id, array $incoming)
	{
		$project = $this->require_access($user, $id);
		$status = isset($project[0]->status) ? strtolower((string) $project[0]->status) : '';
		if (!in_array($status, array('submitted', 'accepted', 'closed'), true)) {
			$this->fail(t('project_processed'), 400);
		}

		$reason = isset($incoming['reason']) ? trim((string) $incoming['reason']) : '';
		if ($reason === '') {
			$this->fail('VALIDATION_ERROR', 400, array(
				'errors' => array(array('property' => 'reason', 'message' => 'Reason is required')),
			));
		}

		$this->ci->load->library('email');
		$this->ci->load->helper('admin_notifications');
		$subject = '#'.$id.' '.sprintf(t('notice_reopen_request'), $project[0]->title);
		$email_options = array(
			'project_title'         => $project[0]->title,
			'project_admin_url'     => site_url('admin/datadeposit/projects/'.$id),
			'project_reopen_reason' => nl2br($reason),
			'user_name'             => trim($user['first_name'].' '.$user['last_name']),
		);
		if ($email_options['user_name'] === '') {
			$email_options['user_name'] = $user['username'];
		}
		$message = $this->ci->load->view('datadeposit/emails/email_project_reopen', $email_options, true);

		$this->ci->DD_project_model->update($id, array(
			'requested_reopen' => 1,
			'requested_when'   => date('U'),
		));
		notify_admin($subject, $message, false);
		$this->write_history('Requested reopen', $id, 'submitted/closed', $user['email']);

		return array(
			'status'  => 'success',
			'message' => t('reopen_requested'),
		);
	}

	public function get_metadata(array $user, $id)
	{
		$this->require_v2_access($user, $id);
		return array(
			'status'   => 'success',
			'metadata' => $this->normalize_metadata_for_form($this->ci->DD_project_model->get_metadata_json($id)),
		);
	}

	public function save_metadata(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$existing = $this->ci->DD_project_model->get_metadata_json($id);
		if (!isset($incoming['additional']) && isset($existing['additional'])) {
			$incoming['additional'] = $existing['additional'];
		}

		if (!$this->ci->DD_project_model->save_json_column($id, 'metadata', $incoming)) {
			$this->fail('SAVE_FAILED', 500);
		}

		$title = '';
		if (isset($incoming['study_desc']['title_statement']['title'])) {
			$title = trim((string) $incoming['study_desc']['title_statement']['title']);
		}
		if ($title !== '') {
			$this->ci->DD_project_model->update($id, array(
				'title'         => $title,
				'last_modified' => date('U'),
			));
		}

		$this->write_history('Study description updated', $id, $this->project_status($user, $id), $user['email']);
		return array(
			'status'   => 'success',
			'metadata' => $this->ci->DD_project_model->get_metadata_json($id),
		);
	}

	/**
	 * GET /api/datadeposit/{id}/import?q=
	 * Search same-type v2 projects. Empty q returns no rows (do not list everything).
	 */
	public function list_import_sources(array $user, $id, $q = '')
	{
		$this->require_v2_access($user, $id);
		$target = $this->ci->DD_project_model->get_by_id($id);
		$data_type = $this->normalize_data_type(isset($target['data_type']) ? $target['data_type'] : 'survey');
		$q = trim((string) $q);
		if (strlen($q) > 100) {
			$q = substr($q, 0, 100);
		}

		$out = array();
		$searchable = $q !== '' && (ctype_digit($q) || strlen($q) >= 2);
		if ($searchable) {
			$this->ci->db->select('dd_projects.id, dd_projects.title, dd_projects.shortname, dd_projects.status, dd_projects.data_type, dd_projects.last_modified');
			$this->ci->db->from('dd_projects');
			$this->ci->db->join('dd_collaborators', 'dd_collaborators.pid = dd_projects.id', 'inner');
			$this->ci->db->where('dd_collaborators.email', $user['email']);
			$this->ci->db->where('dd_projects.id !=', (int) $id);
			$this->ci->db->group_start();
			$this->ci->db->where('dd_projects.data_type', $data_type);
			if ($data_type === 'survey') {
				$this->ci->db->or_where('dd_projects.data_type', '');
				$this->ci->db->or_where('dd_projects.data_type IS NULL', null, false);
			}
			$this->ci->db->group_end();
			if ($this->ci->db->field_exists('schema_version', 'dd_projects')) {
				$this->ci->db->where('dd_projects.schema_version >=', 2);
			}
			$this->ci->db->group_start();
			$this->ci->db->like('dd_projects.title', $q);
			$this->ci->db->or_like('dd_projects.shortname', $q);
			if (ctype_digit($q)) {
				$this->ci->db->or_where('dd_projects.id', (int) $q);
			}
			$this->ci->db->group_end();
			$this->ci->db->order_by('dd_projects.title', 'asc');
			$this->ci->db->limit(25);
			$rows = $this->ci->db->get()->result_array();

			$seen = array();
			foreach ($rows as $row) {
				$pid = (int) $row['id'];
				if (isset($seen[$pid])) {
					continue;
				}
				$seen[$pid] = true;
				$out[] = array(
					'id'            => $pid,
					'title'         => isset($row['title']) ? (string) $row['title'] : '',
					'shortname'     => isset($row['shortname']) ? (string) $row['shortname'] : '',
					'status'        => isset($row['status']) ? strtolower((string) $row['status']) : '',
					'last_modified' => $this->format_date(isset($row['last_modified']) ? $row['last_modified'] : null),
				);
			}
		}

		return array(
			'status'    => 'success',
			'data_type' => $data_type,
			'q'         => $q,
			'projects'  => $out,
		);
	}

	/**
	 * POST /api/datadeposit/{id}/import
	 * Body: { "source_project_id": 12 } or { "json": { ... Metadata JSON or Project JSON ... } }
	 */
	public function import_metadata(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$source_id = 0;
		if (isset($incoming['source_project_id'])) {
			$source_id = (int) $incoming['source_project_id'];
		}
		$json = null;
		if (isset($incoming['json']) && is_array($incoming['json'])) {
			$json = $incoming['json'];
		}

		if ($source_id >= 1 && $json) {
			$this->fail('Provide source_project_id or json, not both', 400);
		}

		$target = $this->ci->DD_project_model->get_by_id($id);
		$target_type = $this->normalize_data_type(isset($target['data_type']) ? $target['data_type'] : 'survey');

		if ($json) {
			$metadata = $this->metadata_from_import_document($json, $target_type);
			$history = 'Imported metadata from JSON file';
		} elseif ($source_id >= 1) {
			if ($source_id === (int) $id) {
				$this->fail('Cannot import from the same project', 400);
			}
			$this->require_v2_access($user, $source_id);
			$source = $this->ci->DD_project_model->get_by_id($source_id);
			$source_type = $this->normalize_data_type(isset($source['data_type']) ? $source['data_type'] : 'survey');
			if ($target_type !== $source_type) {
				$this->fail('Source project must be the same type', 400);
			}
			$metadata = $this->ci->DD_project_model->get_metadata_json($source_id);
			if (!is_array($metadata)) {
				$metadata = array();
			}
			$source_title = isset($source['title']) ? (string) $source['title'] : ('#'.$source_id);
			$history = 'Imported metadata from project '.$source_title;
		} else {
			$this->fail('Select a source project or upload a JSON file', 400);
		}

		$metadata = $this->strip_import_catalog_id($metadata);
		if (!$this->ci->DD_project_model->save_json_column($id, 'metadata', $metadata)) {
			$this->fail('SAVE_FAILED', 500);
		}

		$this->write_history($history, $id, $this->project_status($user, $id), $user['email']);
		return array(
			'status'   => 'success',
			'metadata' => $this->normalize_metadata_for_form(
				$this->ci->DD_project_model->get_metadata_json($id)
			),
		);
	}

	/**
	 * Metadata JSON (type = survey|microdata|…), Metadata Editor JSON, or Project JSON.
	 */
	protected function metadata_from_import_document(array $doc, $target_type)
	{
		$doc_type = $this->import_document_type($doc);
		$is_project = ($doc_type === 'datadeposit-project');
		if (!$is_project && isset($doc['dump_type']) && $doc['dump_type'] === 'nada-datadeposit') {
			$is_project = true;
		}

		if ($is_project) {
			$project_type = '';
			if (isset($doc['project']['data_type'])) {
				$project_type = $this->normalize_data_type($doc['project']['data_type']);
			}
			if ($project_type !== $target_type) {
				$this->fail('JSON is not the same project type', 400);
			}
			$metadata = (isset($doc['metadata']) && is_array($doc['metadata'])) ? $doc['metadata'] : array();
			return $this->strip_import_envelope($metadata);
		}

		$doc = $this->unwrap_import_metadata($doc);
		$doc_type = $this->import_document_type($doc);
		if ($doc_type === '') {
			$doc_type = $this->infer_import_type_from_sections($doc);
		}
		if ($doc_type === '') {
			$this->fail('JSON must include type. Export Metadata (JSON) or Project (JSON) from a deposit.', 400);
		}
		$file_type = $this->normalize_data_type($doc_type, true);
		if ($file_type === null || $file_type !== $target_type) {
			$this->fail('JSON is not the same project type', 400);
		}
		unset($doc['type']);
		return $this->strip_import_envelope($doc);
	}

	protected function import_document_type(array $doc)
	{
		foreach (array('type', 'schema_type', 'schematype', 'datatype') as $key) {
			if (!isset($doc[$key]) || !is_string($doc[$key]) || trim($doc[$key]) === '') {
				continue;
			}
			return trim($doc[$key]);
		}
		return '';
	}

	protected function unwrap_import_metadata(array $doc)
	{
		if (!isset($doc['metadata']) || !is_array($doc['metadata'])) {
			return $doc;
		}
		$inner = $doc['metadata'];
		if (!$inner || isset($inner[0])) {
			return $doc;
		}
		if ($this->import_document_type($inner) === '' && $this->import_document_type($doc) !== '') {
			$inner['type'] = $this->import_document_type($doc);
		}
		if ($this->import_document_type($inner) !== '' || $this->infer_import_type_from_sections($inner) !== '') {
			return $inner;
		}
		return $doc;
	}

	protected function infer_import_type_from_sections(array $doc)
	{
		$section_map = array(
			'study_desc'            => 'survey',
			'database_description'  => 'timeseries-db',
			'series_description'    => 'timeseries',
			'document_description'  => 'document',
			'project_desc'          => 'script',
			'table_description'     => 'table',
			'image_description'     => 'image',
			'video_description'     => 'video',
		);
		foreach ($section_map as $section => $type) {
			if (!empty($doc[$section]) && is_array($doc[$section])) {
				return $type;
			}
		}
		if (!empty($doc['description']) && is_array($doc['description'])) {
			return 'geospatial';
		}
		return '';
	}

	protected function strip_import_envelope(array $metadata)
	{
		$drop = array(
			'schema', 'schema_version', 'schema_type', 'schematype', 'datatype',
			'idno', 'changed', 'changed_utc', 'created', 'created_utc',
			'created_by', 'changed_by', 'overwrite',
			'data_files', 'variables', 'variable_groups',
		);
		foreach ($drop as $key) {
			unset($metadata[$key]);
		}
		return $metadata;
	}

	protected function strip_import_catalog_id(array $metadata)
	{
		if (isset($metadata['additional']) && is_array($metadata['additional'])) {
			unset($metadata['additional']['catalog_study_id']);
			if (!$metadata['additional']) {
				unset($metadata['additional']);
			}
		}
		return $metadata;
	}

	public function get_submission(array $user, $id)
	{
		$this->require_v2_access($user, $id);
		return array(
			'status'     => 'success',
			'submission' => $this->normalize_submission_for_form($this->ci->DD_project_model->get_submission_json($id)),
		);
	}

	public function save_submission(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		if (!$this->ci->DD_project_model->save_json_column($id, 'submission', $incoming)) {
			$this->fail('SAVE_FAILED', 500);
		}

		$this->write_history('Submission form updated', $id, $this->project_status($user, $id), $user['email']);
		return array(
			'status'     => 'success',
			'submission' => $this->ci->DD_project_model->get_submission_json($id),
		);
	}

	/**
	 * GET/POST /api/datadeposit/{id}/validate
	 * Body (POST, optional): { "metadata": {}, "submission": {} }
	 */
	public function validate_project(array $user, $id, array $incoming = array())
	{
		$this->require_v2_access($user, $id);
		$target = $this->ci->DD_project_model->get_by_id($id);
		$data_type = $this->normalize_data_type(isset($target['data_type']) ? $target['data_type'] : 'survey');

		$metadata = (isset($incoming['metadata']) && is_array($incoming['metadata']))
			? $incoming['metadata']
			: $this->normalize_metadata_for_form($this->ci->DD_project_model->get_metadata_json($id));
		$submission = (isset($incoming['submission']) && is_array($incoming['submission']))
			? $incoming['submission']
			: $this->normalize_submission_for_form($this->ci->DD_project_model->get_submission_json($id));

		$issues = $this->collect_validation_issues($data_type, $metadata, $submission);
		return array(
			'status' => 'success',
			'valid'  => empty($issues),
			'issues' => $issues,
		);
	}

	public function submit_project(array $user, $id, array $submission)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$target = $this->ci->DD_project_model->get_by_id($id);
		$data_type = $this->normalize_data_type(isset($target['data_type']) ? $target['data_type'] : 'survey');
		$metadata = $this->normalize_metadata_for_form($this->ci->DD_project_model->get_metadata_json($id));
		$issues = $this->collect_validation_issues($data_type, $metadata, $submission);
		if (!empty($issues)) {
			$this->fail('VALIDATION_ERROR', 400, array('errors' => $issues));
		}

		$access_policy = isset($submission['access_policy']) ? trim((string) $submission['access_policy']) : '';

		if (!$this->ci->DD_project_model->save_json_column($id, 'submission', $submission)) {
			$this->fail('SAVE_FAILED', 500);
		}

		$update = array(
			'access_policy'       => $access_policy,
			'to_catalog'          => isset($submission['to_catalog']) ? $submission['to_catalog'] : null,
			'library_notes'       => isset($submission['library_notes']) ? $submission['library_notes'] : null,
			'cc'                  => isset($submission['cc']) ? $submission['cc'] : null,
			'submitted_on'        => date('U'),
			'is_embargoed'        => !empty($submission['is_embargoed']) ? 1 : 0,
			'embargoed'           => isset($submission['embargoed']) ? $submission['embargoed'] : null,
			'disclosure_risk'     => isset($submission['disclosure_risk']) ? $submission['disclosure_risk'] : null,
			'sensitive_variables' => isset($submission['sensitive_variables']) ? $submission['sensitive_variables'] : null,
			'key_variables'       => isset($submission['key_variables']) ? $submission['key_variables'] : null,
			'status'              => 'submitted',
		);

		$cc = isset($submission['cc']) ? $submission['cc'] : null;
		if (!$this->complete_submission($user, $id, $update, $cc)) {
			$this->fail('SUBMIT_FAILED', 500);
		}

		return array(
			'status'       => 'success',
			'redirect_url' => site_url('datadeposit/projects'),
		);
	}

	public function list_files(array $user, $id)
	{
		$this->require_v2_access($user, $id);
		return array(
			'status'  => 'success',
			'files'   => $this->files_payload($id),
			'dctypes' => $this->dctype_options(),
		);
	}

	/**
	 * Stream a project file. Bytes live on disk; row is dd_project_resources.
	 *
	 * @return array{path: string, filename: string}
	 */
	public function download_file(array $user, $id, $fid)
	{
		$this->require_v2_access($user, $id);
		$file = $this->file_disk_path($id, $fid);
		$this->write_history(
			sprintf('file %s downloaded', $file['filename']),
			$id,
			$this->project_status($user, $id),
			isset($user['email']) ? $user['email'] : ''
		);
		return $file;
	}

	/**
	 * Staff download (ACL already checked). Same disk path rules as depositor.
	 *
	 * @return array{path: string, filename: string}
	 */
	public function admin_download_file($id, $fid, $actor_email)
	{
		$row = $this->ci->DD_project_model->get_by_id($id);
		if (!$row || empty($row['id'])) {
			$this->fail('NOT_FOUND', 404);
		}
		$file = $this->file_disk_path($id, $fid);
		$status = isset($row['status']) ? (string) $row['status'] : '';
		$this->write_history(
			sprintf('file %s downloaded', $file['filename']),
			$id,
			$status,
			$actor_email
		);
		return $file;
	}

	public function commit_file(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$upload_id = isset($incoming['upload_id']) ? preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $incoming['upload_id']) : '';
		if ($upload_id === '') {
			$this->fail('UPLOAD_ID_REQUIRED', 400);
		}

		try {
			$file = $this->commit_resumable_file($user, $id, $upload_id);
		} catch (Exception $e) {
			$code = $e->getMessage();
			$http = ($code === 'ACCESS_DENIED') ? 403 : 400;
			$this->fail($code, $http);
		}

		return array(
			'status' => 'success',
			'file'   => $file,
			'files'  => $this->files_payload($id),
		);
	}

	public function save_file(array $user, $id, $fid, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$resource = $this->require_file($id, $fid);
		$allowed = array('dctype', 'title', 'description', 'author', 'dcformat');
		$record = array();
		foreach ($allowed as $key) {
			if (!array_key_exists($key, $incoming)) {
				continue;
			}
			$value = is_string($incoming[$key]) ? trim($incoming[$key]) : '';
			$record[$key] = $this->ci->security->xss_clean($value);
		}

		if (isset($record['dctype'])) {
			$record['dctype'] = $this->normalize_dctype_code($record['dctype']);
		}

		if ($record) {
			$this->ci->DD_resource_model->update_project_resource($resource['id'], $record);
			$this->write_history('File metadata updated: '.$resource['filename'], $id, $this->project_status($user, $id), $user['email']);
		}

		$updated = $this->require_file($id, $resource['id']);
		return array(
			'status' => 'success',
			'file'   => $this->file_row($id, $updated),
			'files'  => $this->files_payload($id),
		);
	}

	public function delete_files(array $user, $id, array $incoming)
	{
		$this->require_v2_access($user, $id);
		$this->require_draft($user, $id);

		$ids = array();
		if (isset($incoming['ids']) && is_array($incoming['ids'])) {
			$ids = $incoming['ids'];
		} elseif (isset($incoming['id'])) {
			$ids = array($incoming['id']);
		}

		$project_folder = $this->ci->DD_project_model->get_project_fullpath($id);
		$deleted = 0;
		foreach ($ids as $fid) {
			if (!is_numeric($fid)) {
				continue;
			}
			$row = $this->resource_array($fid);
			if (!$row || (int) $row['project_id'] !== (int) $id) {
				continue;
			}
			$filename = isset($row['filename']) ? $row['filename'] : '';
			$this->ci->DD_resource_model->delete_project_resource($fid);
			if ($project_folder && $filename !== '') {
				$path = unix_path($project_folder.'/'.$filename);
				if (is_file($path)) {
					@unlink($path);
				}
			}
			$this->write_history('File '.$filename.' deleted', $id, $this->project_status($user, $id), $user['email']);
			$deleted++;
		}

		return array(
			'status'  => 'success',
			'deleted' => $deleted,
			'files'   => $this->files_payload($id),
		);
	}

	protected function require_access(array $user, $id)
	{
		if (!is_numeric($id)) {
			$this->fail('INVALID_PROJECT', 400);
		}
		if (!$this->ci->DD_project_model->has_access($id, $user['email'])) {
			$this->fail('ACCESS_DENIED', 403);
		}
		return $this->ci->DD_project_model->project_id($id, $user['email']);
	}

	protected function require_v2_access(array $user, $id)
	{
		$this->require_access($user, $id);
		if ($this->ci->DD_project_model->get_schema_version($id) < 2) {
			$this->fail('PROJECT_NOT_V2', 400);
		}
	}

	protected function require_draft(array $user, $id)
	{
		if ($this->ci->DD_project_model->is_locked($id, $user['email'])) {
			$this->fail('PROJECT_LOCKED', 403);
		}
	}

	protected function project_status(array $user, $id)
	{
		$project = $this->ci->DD_project_model->project_id($id, $user['email']);
		if (isset($project[0]->status)) {
			return $project[0]->status;
		}
		return 'draft';
	}

	protected function write_history($comment, $project_id, $status, $email)
	{
		$this->ci->DD_project_model->log_history(array(
			'project_id'     => (int) $project_id,
			'user_identity'  => $email,
			'created_on'     => date('U'),
			'project_status' => $status,
			'comments'       => $comment,
		));
	}

	protected function collaborators_from_input(array $incoming)
	{
		$collaborators = array();
		$errors = array();
		if (!isset($incoming['collaborators']) || !is_array($incoming['collaborators'])) {
			return array($collaborators, $errors);
		}
		foreach ($incoming['collaborators'] as $email) {
			$email = trim((string) $email);
			if ($email === '') {
				continue;
			}
			if (!$this->ci->form_validation->valid_email($email)) {
				$errors[] = array('property' => 'collaborators', 'message' => 'Invalid email: '.$email);
				continue;
			}
			$collaborators[] = $email;
		}
		return array($collaborators, $errors);
	}

	protected function list_payload($projects)
	{
		$out = array();
		if (!is_array($projects) && !is_object($projects)) {
			return $out;
		}
		$type_labels = array();
		foreach ($this->project_types() as $row) {
			$type_labels[$row['value']] = $row['title'];
		}
		foreach ($projects as $project) {
			if (!isset($project->id)) {
				continue;
			}
			$status = isset($project->status) ? strtolower((string) $project->status) : 'draft';
			$access = isset($project->access) ? (string) $project->access : '';
			$requested = !empty($project->requested_reopen);
			$data_type = isset($project->data_type) ? $this->normalize_data_type($project->data_type) : 'survey';
			$out[] = array(
				'id'               => (int) $project->id,
				'title'            => isset($project->title) ? (string) $project->title : '',
				'description'      => isset($project->description) ? (string) $project->description : '',
				'created_by'       => isset($project->created_by) ? (string) $project->created_by : '',
				'status'           => $status,
				'access'           => $access,
				'data_type'        => $data_type,
				'data_type_label'  => isset($type_labels[$data_type]) ? $type_labels[$data_type] : $data_type,
				'shortname'        => isset($project->shortname) ? (string) $project->shortname : '',
				'schema_version'   => isset($project->schema_version) ? (int) $project->schema_version : 1,
				'requested_reopen' => $requested,
				'created_on'       => $this->format_date(isset($project->created_on) ? $project->created_on : null),
				'last_modified'    => $this->format_date(isset($project->last_modified) ? $project->last_modified : null),
				'can_edit'         => $status === 'draft',
				'can_delete'       => $status === 'draft' && $access === 'owner',
				'can_reopen'       => in_array($status, array('submitted', 'accepted', 'closed'), true) && !$requested,
				'can_summary'      => true,
				'can_email'        => isset($project->schema_version) && (int) $project->schema_version >= 2,
				'can_export'       => isset($project->schema_version) && (int) $project->schema_version >= 2,
				'can_export_ddi'   => $data_type === 'survey'
					&& isset($project->schema_version) && (int) $project->schema_version >= 2,
				'study_url'        => site_url('datadeposit/study/'.$project->id),
				'summary_url'      => site_url('datadeposit/summary/'.$project->id),
				'email_url'        => site_url('datadeposit/email/'.$project->id),
			);
		}
		return $out;
	}

	protected function format_date($value)
	{
		if ($value === null || $value === '') {
			return '';
		}
		$ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
		if (!$ts) {
			return '';
		}
		return date('M d, Y', $ts);
	}

	/**
	 * UTC ISO 8601 timestamp (YYYY-MM-DDTHH:MM:SSZ).
	 */
	protected function format_iso_timestamp($value)
	{
		if ($value === null || $value === '') {
			return '';
		}
		$ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
		if (!$ts) {
			return '';
		}
		return gmdate('Y-m-d\TH:i:s\Z', $ts);
	}

	protected function normalize_metadata_for_form(array $metadata)
	{
		$coll_mode = null;
		if (isset($metadata['study_desc']['method']['data_collection']['coll_mode'])) {
			$coll_mode = $metadata['study_desc']['method']['data_collection']['coll_mode'];
		}
		if (is_array($coll_mode)) {
			$parts = array();
			foreach ($coll_mode as $item) {
				if (is_string($item) || is_numeric($item)) {
					$parts[] = (string) $item;
				}
			}
			$metadata['study_desc']['method']['data_collection']['coll_mode'] = implode(', ', $parts);
		}
		return $metadata;
	}

	protected function normalize_submission_for_form(array $submission)
	{
		if (array_key_exists('is_embargoed', $submission)) {
			$val = $submission['is_embargoed'];
			$submission['is_embargoed'] = ($val === true || $val === 1 || $val === '1' || $val === 'true');
		}
		return $submission;
	}

	protected function complete_submission(array $user, $id, array $update, $cc)
	{
		$data['project'] = $this->ci->DD_project_model->project_id($id, $user['email']);

		if (!$this->ci->DD_project_model->submit_project($id, $update)) {
			return false;
		}

		$this->write_history('Project submitted', $id, 'submitted', $user['email']);

		$username = $user['username'];
		$project_url = site_url().'/datadeposit/summary/'.$id;
		$subject = '[Confirmation - #'.$id.'] - '.$data['project'][0]->title;
		$message = sprintf(t('msg_project_submitted'), ucwords($username), ucwords($data['project'][0]->title), $project_url);
		$to = $this->ci->DD_project_model->get_project_owner_email($id);

		$collabs = implode(',', $this->ci->DD_project_model->get_collaborators($id));
		if ($cc !== false && $cc !== null && $cc !== '') {
			$cc = implode(',', array_unique(explode(',', $cc.','.$collabs)));
		} else {
			$cc = $collabs;
		}

		$this->email_project($id, $to, $cc, null, $subject, $message);

		$admin_email = $this->ci->config->item('website_webmaster_email');
		$admin_subject = sprintf(t('notice_project_submitted'), $data['project'][0]->title, $username);
		$admin_message = sprintf(t('notify_admin_new_project_submitted'), $username, $data['project'][0]->title, site_url('admin/datadeposit/projects/'.$id));
		$this->email_project($id, $admin_email, null, null, $admin_subject, $admin_message);

		return true;
	}

	protected function email_project($id, $to, $cc, $bcc, $subject, $message)
	{
		$this->ci->load->helper('email');
		$this->ci->load->library('email');

		$data = array(
			'content' => $this->summary_html($id),
			'message' => $message,
		);

		$css_path = APPPATH.'../themes/datadeposit/email.css';
		$css = is_file($css_path) ? file_get_contents($css_path) : '';
		$contents = $this->ci->load->view('datadeposit/emails/template', $data, true);

		if ($css !== '') {
			$this->ci->load->library('CssToInlineStyles');
			$this->ci->csstoinlinestyles->setCSS($css);
			$this->ci->csstoinlinestyles->setHTML($contents);
			$contents = $this->ci->csstoinlinestyles->convert();
		}

		$this->ci->email->clear();
		$this->ci->email->initialize();
		$this->ci->email->to($to);
		if ($cc) {
			$this->ci->email->cc($cc);
		}
		if ($bcc) {
			$this->ci->email->bcc($bcc);
		}
		$this->ci->email->subject($subject);
		$this->ci->email->message($contents);
		return (bool) @$this->ci->email->send();
	}

	protected function dctype_options()
	{
		if (is_array($this->dctype_options_cache)) {
			return $this->dctype_options_cache;
		}
		$this->ci->load->model('Dctype_model');
		$map = $this->ci->Dctype_model->get_flat();
		$out = array();
		if (is_array($map)) {
			foreach ($map as $code => $title) {
				$out[] = array(
					'value' => (string) $code,
					'title' => (string) $title,
				);
			}
		}
		$this->dctype_options_cache = $out;
		return $out;
	}

	protected function dctype_title_map()
	{
		$out = array();
		foreach ($this->dctype_options() as $row) {
			$out[$row['value']] = $row['title'];
		}
		return $out;
	}

	protected function dctype_code_from_value($value)
	{
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}
		if (preg_match('/\[([^\]]+)\]\s*$/', $value, $m)) {
			return trim($m[1]);
		}
		return $value;
	}

	protected function normalize_dctype_code($value)
	{
		$code = $this->dctype_code_from_value($value);
		if ($code === '' || $code === '--') {
			return '';
		}
		$map = $this->dctype_title_map();
		if (isset($map[$code])) {
			return $code;
		}
		foreach ($map as $known => $title) {
			if (strcasecmp($title, $code) === 0) {
				return $known;
			}
		}
		return $code;
	}

	protected function resource_array($fid)
	{
		$rows = $this->ci->DD_resource_model->get_project_resource($fid);
		if (!$rows || !isset($rows[0])) {
			return null;
		}
		$row = $rows[0];
		if (is_object($row)) {
			return (array) $row;
		}
		return is_array($row) ? $row : null;
	}

	protected function require_file($id, $fid)
	{
		if (!is_numeric($fid)) {
			$this->fail('INVALID_FILE', 400);
		}
		$row = $this->resource_array($fid);
		if (!$row || (int) $row['project_id'] !== (int) $id) {
			$this->fail('FILE_NOT_FOUND', 404);
		}
		return $row;
	}

	/**
	 * @return array{path: string, filename: string}
	 */
	protected function file_disk_path($id, $fid)
	{
		$resource = $this->require_file($id, $fid);
		$filename = isset($resource['filename']) ? basename((string) $resource['filename']) : '';
		if ($filename === '' || $filename === '.' || $filename === '..') {
			$this->fail('FILE_NOT_FOUND', 404);
		}

		$folder = $this->ci->DD_project_model->get_project_fullpath($id);
		if (!$folder) {
			$this->fail('PROJECT_DATA_FOLDER_NOT_SET', 500);
		}
		$folder_real = unix_realpath($folder);
		if ($folder_real === false) {
			$this->fail('PROJECT_DATA_FOLDER_NOT_SET', 500);
		}

		$path = unix_path($folder.'/'.$filename);
		if (!is_file($path)) {
			$this->fail('FILE_NOT_FOUND', 404);
		}
		$path_real = unix_realpath($path);
		if ($path_real === false || !$this->path_is_within($path_real, $folder_real)) {
			$this->fail('FILE_NOT_FOUND', 404);
		}

		return array(
			'path'     => $path_real,
			'filename' => $filename,
		);
	}

	protected function file_row($project_id, array $file)
	{
		$titles = $this->dctype_title_map();
		$filename = isset($file['filename']) ? (string) $file['filename'] : '';
		$dctype_raw = isset($file['dctype']) ? (string) $file['dctype'] : '';
		$dctype = $this->dctype_code_from_value($dctype_raw);
		$dctype_title = '';
		if ($dctype !== '' && isset($titles[$dctype])) {
			$dctype_title = $titles[$dctype];
		} elseif ($dctype_raw !== '') {
			$dctype_title = preg_replace('/\s*\[[^\]]*\]\s*$/', '', $dctype_raw);
		}

		$folder = $this->ci->DD_project_model->get_project_fullpath($project_id);
		$disk_size = 0;
		if ($folder && $filename !== '') {
			$path = unix_path($folder.'/'.$filename);
			if (is_file($path)) {
				$disk_size = (int) filesize($path);
			}
		}
		$stored_size = isset($file['filesize']) ? (float) $file['filesize'] : 0;
		$size = $disk_size > 0 ? $disk_size : (int) $stored_size;

		$title = isset($file['title']) ? trim((string) $file['title']) : '';
		$description = isset($file['description']) ? trim((string) $file['description']) : '';
		$author = isset($file['author']) ? trim((string) $file['author']) : '';
		$fid = isset($file['id']) ? (int) $file['id'] : 0;

		return array(
			'id'           => $fid,
			'filename'     => $filename,
			'title'        => $title,
			'description'  => $description,
			'author'       => $author,
			'dctype'       => $dctype,
			'dctype_title' => $dctype_title,
			'dcformat'     => isset($file['dcformat']) ? (string) $file['dcformat'] : '',
			'filesize'     => $size,
			'has_metadata' => ($dctype !== '' || $title !== '' || $description !== ''),
			'download_url' => $this->file_download_url($project_id, $fid),
		);
	}

	protected function files_payload($project_id)
	{
		$out = array();
		foreach ($this->ci->DD_resource_model->get_project_resources($project_id) as $file) {
			$out[] = $this->file_row($project_id, $file);
		}
		return $out;
	}

	protected function file_download_url($project_id, $fid)
	{
		if (!(int) $fid || !(int) $project_id) {
			return '';
		}
		return site_url('api/datadeposit/'.(int) $project_id.'/files/'.(int) $fid.'/download');
	}

	protected function extension_allowed($filename)
	{
		$this->ci->config->load('config');
		$allowed = (string) $this->ci->config->item('allowed_resource_types');
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ($ext === '' || $allowed === '') {
			return false;
		}
		$list = array();
		foreach (explode(',', $allowed) as $item) {
			$item = strtolower(trim($item));
			if ($item !== '') {
				$list[] = $item;
			}
		}
		return in_array($ext, $list, true);
	}

	protected function path_is_within($path_real, $root_real)
	{
		$path_real = rtrim(unix_path($path_real), '/');
		$root_real = rtrim(unix_path($root_real), '/');
		if ($path_real === $root_real) {
			return true;
		}
		$current = $path_real;
		while ($current !== '' && $current !== dirname($current)) {
			if ($current === $root_real) {
				return true;
			}
			$current = dirname($current);
		}
		return false;
	}

	protected function commit_resumable_file(array $user, $id, $upload_id)
	{
		$this->ci->load->library('Resumable_upload', null, 'uploader');
		$info = $this->ci->uploader->get_completed_upload($upload_id);
		if (!is_array($info) || empty($info['file_path'])) {
			throw new Exception('UPLOAD_NOT_COMPLETE_OR_MISSING');
		}

		$meta = isset($info['metadata']) && is_array($info['metadata']) ? $info['metadata'] : array();
		$owner = isset($meta['_upload_owner_user_id']) ? (int) $meta['_upload_owner_user_id'] : 0;
		if ($owner <= 0 || $owner !== (int) $user['id']) {
			throw new Exception('ACCESS_DENIED');
		}

		$basename = basename((string) $info['filename']);
		if ($basename === '' || $basename === '.' || $basename === '..') {
			throw new Exception('INVALID_FILENAME');
		}
		if (!$this->extension_allowed($basename)) {
			throw new Exception('FILE_TYPE_NOT_ALLOWED');
		}

		$this->ci->load->helper('datadeposit');
		$max_bytes = datadeposit_max_upload_bytes();
		$file_size = isset($info['file_size']) ? (int) $info['file_size'] : 0;
		if ($file_size < 1 && !empty($info['file_path']) && is_file($info['file_path'])) {
			$file_size = (int) filesize($info['file_path']);
		}
		if ($max_bytes > 0 && $file_size > $max_bytes) {
			throw new Exception('FILE_TOO_LARGE');
		}

		$project_folder = $this->ci->DD_project_model->get_project_fullpath($id);
		if (!$project_folder) {
			throw new Exception('PROJECT_FOLDER_NOT_SET');
		}
		if (!is_dir($project_folder) && !@mkdir($project_folder, 0755, true)) {
			throw new Exception('FOLDER_CREATE_FAILED');
		}
		$folder_real = unix_realpath($project_folder);
		if ($folder_real === false) {
			throw new Exception('PROJECT_FOLDER_NOT_SET');
		}

		$dest = unix_path($project_folder.'/'.$basename);
		$dest_dir = unix_realpath(dirname($dest));
		if ($dest_dir === false || !$this->path_is_within($dest_dir, $folder_real)) {
			throw new Exception('PATH_OUTSIDE_PROJECT_FOLDER');
		}

		$src = (string) $info['file_path'];
		if (!is_file($src)) {
			throw new Exception('SOURCE_FILE_NOT_FOUND');
		}
		if (is_file($dest) && !@unlink($dest)) {
			throw new Exception('REPLACE_EXISTING_FAILED');
		}

		$this->ci->uploader->relocate_file($src, $dest);
		$this->ci->uploader->delete_upload($upload_id);

		$filesize = is_file($dest) ? filesize($dest) : (isset($info['file_size']) ? $info['file_size'] : 0);
		$resource = array(
			'filename'   => $basename,
			'project_id' => $id,
			'created'    => date('U'),
			'filesize'   => $filesize,
		);

		$fid = $this->ci->DD_resource_model->insert_project_resource($id, $resource);
		if (!$fid) {
			throw new Exception('SAVE_FAILED');
		}
		$this->write_history('File '.$basename.' uploaded', $id, $this->project_status($user, $id), $user['email']);

		$row = $this->resource_array($fid);
		if (!$row) {
			$row = $resource;
			$row['id'] = $fid;
		}
		return $this->file_row($id, $row);
	}

	protected function emails_from_share_input(array $incoming)
	{
		$raw = array();
		if (isset($incoming['emails']) && is_array($incoming['emails'])) {
			$raw = $incoming['emails'];
		} elseif (isset($incoming['email']) && is_array($incoming['email'])) {
			$raw = $incoming['email'];
		} elseif (isset($incoming['email'])) {
			$raw = array($incoming['email']);
		}
		$out = array();
		$seen = array();
		foreach ($raw as $item) {
			$parts = preg_split('/[,;\s]+/', (string) $item);
			if (!is_array($parts)) {
				continue;
			}
			foreach ($parts as $email) {
				$email = strtolower(trim($email));
				if ($email === '' || isset($seen[$email])) {
					continue;
				}
				$seen[$email] = true;
				$out[] = $email;
			}
		}
		return $out;
	}

	protected function summary_sections($items, array $data, array $labels)
	{
		$sections = array();

		$section_for = function ($title) use (&$sections) {
			$key = ($title !== '' && $title !== null) ? (string) $title : 'Details';
			foreach ($sections as $i => $section) {
				if ($section['title'] === $key) {
					return $i;
				}
			}
			$sections[] = array('title' => $key, 'rows' => array());
			return count($sections) - 1;
		};

		$walk = function ($nodes, $inherited_title) use (&$walk, &$sections, $section_for, $data, $labels) {
			if (!is_array($nodes)) {
				return;
			}
			foreach ($nodes as $item) {
				if (!is_array($item)) {
					continue;
				}
				$type = isset($item['type']) ? (string) $item['type'] : '';
				if ($type === 'section' || $type === 'section_container') {
					$child_title = (isset($item['title']) && $item['title'] !== '') ? $item['title'] : $inherited_title;
					$walk(isset($item['items']) ? $item['items'] : array(), $child_title);
					continue;
				}
				if (empty($item['key'])) {
					continue;
				}
				$value = $this->summary_field_display($item, $data, $labels);
				if ($value === '') {
					continue;
				}
				$idx = $section_for($inherited_title);
				$sections[$idx]['rows'][] = array(
					'key'   => $item['key'],
					'title' => isset($item['title']) && $item['title'] !== '' ? $item['title'] : $item['key'],
					'value' => $value,
				);
			}
		};

		$walk($items, '');
		return array_values(array_filter($sections, function ($section) {
			return !empty($section['rows']);
		}));
	}

	protected function summary_field_display(array $field, array $data, array $labels)
	{
		$raw = $this->nested_get($data, isset($field['key']) ? $field['key'] : '');
		if ($this->summary_value_empty($raw)) {
			return '';
		}
		$type = isset($field['type']) ? (string) $field['type'] : '';
		if ($type === 'array' || $type === 'nested_array') {
			$lines = array();
			foreach ($this->summary_array_rows($raw, isset($field['props']) ? $field['props'] : null, $labels) as $row) {
				if (isset($row['text']) && $row['text'] !== '') {
					$lines[] = $row['text'];
				}
			}
			return implode("\n", $lines);
		}
		if ($type === 'simple_array' && is_array($raw)) {
			$lines = array();
			foreach ($raw as $value) {
				$text = $this->summary_scalar($value, $labels);
				if ($text !== '') {
					$lines[] = $text;
				}
			}
			return implode("\n", $lines);
		}
		return $this->summary_scalar($raw, $labels);
	}

	protected function summary_array_rows($value, $props, array $labels)
	{
		if (!is_array($value) || !$value) {
			return array();
		}
		$cols = $this->normalize_template_props($props);
		$out = array();
		foreach ($value as $row) {
			if ($row === null || !is_array($row)) {
				$out[] = array('text' => $this->summary_scalar($row, $labels));
				continue;
			}
			if (!$cols) {
				$out[] = array('text' => $this->summary_scalar($row, $labels));
				continue;
			}
			$parts = array();
			foreach ($cols as $col) {
				$key = isset($col['key']) ? $col['key'] : '';
				if ($key === '' || !array_key_exists($key, $row) || $this->summary_value_empty($row[$key])) {
					continue;
				}
				$label = (isset($col['title']) && $col['title'] !== '') ? $col['title'] : $key;
				$parts[] = $label.': '.$this->summary_scalar($row[$key], $labels);
			}
			$out[] = array('text' => $parts ? implode(' · ', $parts) : $this->summary_scalar($row, $labels));
		}
		return $out;
	}

	protected function summary_scalar($value, array $labels)
	{
		if ($value === null) {
			return '';
		}
		if (is_bool($value)) {
			return $value
				? (isset($labels['trueLabel']) ? $labels['trueLabel'] : 'True')
				: (isset($labels['falseLabel']) ? $labels['falseLabel'] : 'False');
		}
		if (is_array($value)) {
			$encoded = json_encode($value);
			return $encoded !== false ? $encoded : '';
		}
		return (string) $value;
	}

	protected function summary_value_empty($value)
	{
		if ($value === '' || $value === null) {
			return true;
		}
		if (is_array($value) && count($value) === 0) {
			return true;
		}
		if (is_array($value) && count($value) === 1) {
			$only = reset($value);
			if (is_array($only) && count($only) === 0) {
				return true;
			}
		}
		if (is_array($value) && !$this->is_list_array($value) && count($value) === 0) {
			return true;
		}
		return false;
	}

	protected function nested_get(array $data, $path)
	{
		if ($path === null || $path === '') {
			return null;
		}
		$cur = $data;
		foreach (explode('.', (string) $path) as $seg) {
			if (!is_array($cur) || !array_key_exists($seg, $cur)) {
				return null;
			}
			$cur = $cur[$seg];
		}
		return $cur;
	}

	protected function normalize_template_props($props)
	{
		if (!$props) {
			return array();
		}
		if ($this->is_list_array($props)) {
			$out = array();
			foreach ($props as $p) {
				if (is_array($p)) {
					$out[] = $p;
				}
			}
			return $out;
		}
		if (is_array($props)) {
			$out = array();
			foreach ($props as $key => $p) {
				if (!is_array($p)) {
					$out[] = array('key' => $key, 'title' => $key, 'type' => 'string');
					continue;
				}
				if (empty($p['key'])) {
					$p['key'] = $key;
				}
				$out[] = $p;
			}
			return $out;
		}
		return array();
	}

	protected function is_list_array($value)
	{
		if (!is_array($value)) {
			return false;
		}
		$i = 0;
		foreach ($value as $k => $_) {
			if ($k !== $i) {
				return false;
			}
			$i++;
		}
		return true;
	}

	protected function fail($message, $http = 400, $extra = array())
	{
		throw new Deposit_depositor_exception($message, $http, $extra);
	}

	/**
	 * Template required + filtered JSON Schema (study) + submit template.
	 *
	 * @return array<int, array{step: string, key: string, property: string, title: string, message: string, source: string}>
	 */
	protected function collect_validation_issues($data_type, array $metadata, array $submission)
	{
		$templates = $this->deposit_templates($data_type);
		$form_items = (isset($templates['form_template']['items']) && is_array($templates['form_template']['items']))
			? $templates['form_template']['items']
			: array();
		$submit_items = (isset($templates['submit_template']['items']) && is_array($templates['submit_template']['items']))
			? $templates['submit_template']['items']
			: array();
		$form_fields = $this->template_leaf_fields($form_items);
		$submit_fields = $this->template_leaf_fields($submit_items);

		$issues = array();
		$issues = array_merge($issues, $this->template_required_issues('metadata', $form_fields, $metadata));
		$issues = array_merge($issues, $this->schema_issues($data_type, $metadata, $form_fields));
		$issues = array_merge($issues, $this->template_required_issues('access', $submit_fields, $submission));
		$issues = array_merge($issues, $this->submission_format_issues($submission));
		return $this->dedupe_validation_issues($issues);
	}

	protected function template_leaf_fields(array $items)
	{
		$out = array();
		$this->collect_template_fields($items, $out);
		return $out;
	}

	protected function collect_template_fields(array $items, array &$out)
	{
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$type = isset($item['type']) ? $item['type'] : '';
			if ($type === 'section' || $type === 'section_container') {
				if (!empty($item['items']) && is_array($item['items'])) {
					$this->collect_template_fields($item['items'], $out);
				}
				continue;
			}
			if (!empty($item['key'])) {
				$out[] = $item;
			}
		}
	}

	protected function template_required_issues($step, array $fields, array $data)
	{
		$issues = array();
		if ($step === 'access' && empty($fields)) {
			$access_policy = isset($data['access_policy']) ? trim((string) $data['access_policy']) : '';
			if ($access_policy === '') {
				$issues[] = $this->validation_issue(
					$step,
					'access_policy',
					'Access policy',
					'Access policy is required',
					'template'
				);
			}
			return $issues;
		}

		foreach ($fields as $field) {
			$key = isset($field['key']) ? (string) $field['key'] : '';
			if ($key === '') {
				continue;
			}
			$title = (isset($field['title']) && $field['title'] !== '') ? (string) $field['title'] : $key;
			if ($this->field_is_required_now($field, $data)) {
				$value = $this->nested_get($data, $key);
				if ($this->submit_value_is_empty($value) || $this->summary_value_empty($value)) {
					$issues[] = $this->validation_issue(
						$step,
						$key,
						$title,
						$title.' is required',
						'template'
					);
				}
			}

			$value = $this->nested_get($data, $key);
			$props = $this->normalize_template_props(isset($field['props']) ? $field['props'] : array());
			if (!is_array($value) || !$this->is_list_array($value) || !$props) {
				continue;
			}
			foreach ($value as $i => $row) {
				if (!is_array($row)) {
					continue;
				}
				foreach ($props as $prop) {
					if (empty($prop['is_required']) && empty($prop['required'])) {
						continue;
					}
					$pk = isset($prop['key']) ? (string) $prop['key'] : '';
					if ($pk === '') {
						continue;
					}
					$pv = array_key_exists($pk, $row) ? $row[$pk] : null;
					if ($this->submit_value_is_empty($pv) || $this->summary_value_empty($pv)) {
						$ptitle = (isset($prop['title']) && $prop['title'] !== '') ? (string) $prop['title'] : $pk;
						$issues[] = $this->validation_issue(
							$step,
							$key.'.'.$i.'.'.$pk,
							$title.': '.$ptitle,
							$ptitle.' is required',
							'template'
						);
					}
				}
			}
		}
		return $issues;
	}

	protected function field_is_required_now(array $field, array $data)
	{
		if (!empty($field['is_required']) || !empty($field['required'])) {
			return true;
		}
		$when = isset($field['required_when']) ? (string) $field['required_when'] : '';
		if ($when === '') {
			return false;
		}
		$flag = $this->nested_get($data, $when);
		if ($flag === null && array_key_exists($when, $data)) {
			$flag = $data[$when];
		}
		return ($flag === true || $flag === 1 || $flag === '1' || $flag === 'true');
	}

	protected function schema_issues($data_type, array $metadata, array $form_fields)
	{
		$schema_type = ($data_type === 'timeseries-db') ? 'timeseries-db' : $data_type;
		$schema_path = APPPATH.'schemas/'.$schema_type.'-schema.json';
		if (!is_file($schema_path)) {
			return array();
		}

		$payload = json_decode(json_encode($this->metadata_for_schema($metadata, $schema_type)));
		$this->ci->load->library('Schema_validator');
		$errors = $this->ci->schema_validator->collect_errors($schema_path, $payload);
		if (!$errors) {
			return array();
		}

		$key_index = array();
		foreach ($form_fields as $field) {
			$key = isset($field['key']) ? (string) $field['key'] : '';
			if ($key !== '') {
				$key_index[$key] = $field;
			}
		}

		$issues = array();
		foreach ($errors as $err) {
			if (!is_array($err)) {
				continue;
			}
			$prop = $this->normalize_schema_property(isset($err['property']) ? $err['property'] : '');
			$matched = $this->schema_property_template_key($prop, $key_index);
			if ($matched === null) {
				continue;
			}
			$field = $key_index[$matched];
			$title = (isset($field['title']) && $field['title'] !== '') ? (string) $field['title'] : $matched;
			$message = isset($err['message']) ? trim((string) $err['message']) : '';
			if ($message === '') {
				$message = 'Invalid value';
			}
			$issues[] = $this->validation_issue(
				'metadata',
				$prop !== '' ? $prop : $matched,
				$title,
				$message,
				'schema'
			);
		}
		return $issues;
	}

	protected function metadata_for_schema(array $metadata, $schema_type)
	{
		$drop = array(
			'data_files', 'variables', 'variable_groups',
			'schema', 'schema_version', 'schema_type', 'schematype', 'datatype',
			'changed', 'changed_utc', 'created', 'created_utc',
			'created_by', 'changed_by', 'overwrite',
		);
		$payload = $metadata;
		foreach ($drop as $key) {
			unset($payload[$key]);
		}
		if (!isset($payload['type']) || trim((string) $payload['type']) === '') {
			$payload['type'] = $schema_type;
		}
		return $payload;
	}

	protected function normalize_schema_property($prop)
	{
		$prop = str_replace(array('[', ']'), array('.', ''), (string) $prop);
		$prop = preg_replace('/\.+/', '.', $prop);
		return trim($prop, '.');
	}

	protected function schema_property_template_key($prop, array $key_index)
	{
		if ($prop === '') {
			return null;
		}
		$stripped = preg_replace('/\.\d+(?=\.|$)/', '', $prop);
		$best = null;
		$best_len = -1;
		foreach ($key_index as $key => $_) {
			if ($prop === $key || $stripped === $key
				|| strpos($prop, $key.'.') === 0
				|| strpos($stripped, $key.'.') === 0) {
				$len = strlen($key);
				if ($len > $best_len) {
					$best = $key;
					$best_len = $len;
				}
			}
		}
		return $best;
	}

	protected function submission_format_issues(array $submission)
	{
		$issues = array();
		$end = isset($submission['embargo_end']) ? trim((string) $submission['embargo_end']) : '';
		if ($end !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
			$issues[] = $this->validation_issue(
				'access',
				'embargo_end',
				'Embargo expiry date',
				'Embargo expiry date must be YYYY-MM-DD',
				'template'
			);
		}
		return $issues;
	}

	protected function validation_issue($step, $key, $title, $message, $source)
	{
		return array(
			'step'     => $step,
			'key'      => $key,
			'property' => $key,
			'title'    => $title,
			'message'  => $message,
			'source'   => $source,
		);
	}

	protected function dedupe_validation_issues(array $issues)
	{
		$seen = array();
		$out = array();
		foreach ($issues as $issue) {
			$sig = $issue['step'].'|'.$issue['key'];
			if (isset($seen[$sig])) {
				continue;
			}
			$seen[$sig] = true;
			$out[] = $issue;
		}
		return $out;
	}

	private function submit_value_is_empty($value)
	{
		if ($value === null || $value === '' || $value === array()) {
			return true;
		}
		if (is_string($value) && trim($value) === '') {
			return true;
		}
		return false;
	}
}
