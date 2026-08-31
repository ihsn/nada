<?php

/**
 * Data deposit — Vue SPA mounts only. Writes go through /api/datadeposit.
 */
class Datadeposit extends MY_Controller
{
	public function __construct($SKIP = FALSE, $is_admin = FALSE)
	{
		parent::__construct($SKIP, $is_admin);

		$this->load->model('DD_project_model');
		$this->load->helper('date');
		$this->load->language('dd_projects');
		$this->load->language('dd_help');
		$this->template->add_css('themes/datadeposit/data-deposit.css');
	}

	protected function before_auth()
	{
		$this->load->helper('datadeposit');
		datadeposit_require_enabled();
	}

	public function index()
	{
		$this->projects();
	}

	public function projects()
	{
		if ($this->input->get('create') === '1') {
			redirect('datadeposit/create');
			return;
		}
		$this->_render_deposit_spa();
	}

	public function create()
	{
		$this->_render_deposit_spa();
	}

	public function study($id)
	{
		if (!is_numeric($id)) {
			show_error('INVALID ID');
		}
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) {
			$this->session->set_flashdata('error', t('Access Denied: You don\'t have access to the project'));
			redirect('datadeposit/projects');
			return;
		}
		if ($this->DD_project_model->is_locked($id, $this->session->userdata('email'))) {
			redirect('datadeposit/summary/'.$id);
			return;
		}
		$this->_render_deposit_spa($id);
	}

	public function summary($id)
	{
		if (!is_numeric($id)) {
			show_error('INVALID ID');
		}
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) {
			redirect('datadeposit/projects');
			return;
		}
		$this->_render_deposit_spa($id);
	}

	public function email($id)
	{
		if (!is_numeric($id)) {
			show_error('INVALID ID');
		}
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) {
			redirect('datadeposit/projects');
			return;
		}
		$this->_render_deposit_spa($id);
	}

	private function _render_deposit_spa($id = null)
	{
		$this->_hide_library_breadcrumb();
		$this->lang->load('catalog_admin');
		$this->lang->load('dd_projects');
		$this->lang->load('dd_help');
		$this->lang->load('catalog_search');

		$config = $this->_deposit_spa_config($id);
		$content = $this->load->view('datadeposit/vue_app', array('config' => $config), true);
		$this->template->write('title', t('Data Deposit - ').$this->_deposit_spa_title($config), true);
		$this->template->write('content', $content, true);
		$this->template->render();
	}

	private function _deposit_spa_page()
	{
		$seg = strtolower((string) $this->uri->segment(2));
		if ($seg === 'create') {
			return 'create';
		}
		if ($seg === 'summary') {
			return 'summary';
		}
		if ($seg === 'email') {
			return 'email';
		}
		if ($seg === 'study') {
			return 'project';
		}
		return 'home';
	}

	private function _deposit_spa_title(array $config)
	{
		$page = isset($config['page']) ? $config['page'] : 'home';
		if ($page === 'create') {
			return t('new_project');
		}
		if ($page === 'summary') {
			return t('summary');
		}
		if ($page === 'email') {
			return t('email_project');
		}
		if ($page === 'project' && !empty($config['projectTitle'])) {
			return $config['projectTitle'];
		}
		return t('datadeposit');
	}

	private function _deposit_router_path_base()
	{
		$pu = parse_url(site_url('datadeposit'));
		$path = isset($pu['path']) ? $pu['path'] : '/datadeposit';
		return rtrim($path, '/');
	}

	private function _deposit_spa_config($id = null)
	{
		$api_base = site_url('api/datadeposit');
		$page = $this->_deposit_spa_page();
		$config = array(
			'page'                 => $page,
			'siteUrl'              => site_url(),
			'baseUrl'              => base_url(),
			'assetsBase'           => base_url('frontend/dist/'),
			'csrfToken'            => $this->security->get_csrf_hash(),
			'csrfTokenName'        => $this->security->get_csrf_token_name(),
			'routerPathBase'       => $this->_deposit_router_path_base(),
			'projectsApiUrl'       => $api_base,
			'deleteApiUrlBase'     => $api_base,
			'reopenApiUrlBase'     => $api_base,
			'projectsUrl'          => site_url('datadeposit/projects'),
			'createUrl'            => site_url('datadeposit/create'),
			'studyUrlBase'         => site_url('datadeposit/study'),
			'uploadsApiUrl'        => rtrim(site_url('api/uploads'), '/').'/',
			'allowedResourceTypes' => $this->config->item('allowed_resource_types'),
			'depositMaxUploadMb'   => datadeposit_max_upload_mb(),
			'projectTypes'         => $this->_deposit_project_types(),
			'flashMessage'         => (string) $this->session->flashdata('message'),
			'flashError'           => (string) $this->session->flashdata('error'),
			'labels'               => $this->_deposit_vue_labels(),
		);

		if ($page === 'home') {
			$list = $this->_deposit_projects_page();
			$config['projects'] = $list['projects'];
			$config['total'] = $list['total'];
			$config['listPage'] = $list['page'];
			$config['pageSize'] = $list['page_size'];
			$config['totalPages'] = $list['total_pages'];
			$config['searchQuery'] = $list['keywords'];
			$config['statusFilter'] = $list['status_filter'];
		}

		if (is_numeric($id)) {
			$config = array_merge($config, $this->_deposit_project_spa_config($id, $page));
		}

		return $config;
	}

	private function _deposit_project_spa_config($id, $page)
	{
		$email = $this->session->userdata('email');
		$project = $this->DD_project_model->project_id($id, $email);
		if (!$project) {
			show_error('PROJECT_NOT_FOUND');
		}
		$data_type = $this->_deposit_normalize_data_type(
			isset($project[0]->data_type) ? $project[0]->data_type : 'survey'
		);

		$this->load->library('Deposit_depositor');
		try {
			$templates = $this->deposit_depositor->deposit_templates($data_type);
		} catch (Exception $e) {
			show_error($e->getMessage());
			return array();
		}

		$allowed_steps = array('info', 'metadata', 'files', 'access', 'review', 'submit');
		$initial = $this->input->get('step');
		if (!in_array($initial, $allowed_steps, true)) {
			$initial = 'metadata';
		}
		if ($initial === 'submit') {
			$initial = 'review';
		}

		$api_base = site_url('api/datadeposit');
		$page_out = in_array($page, array('summary', 'email'), true) ? $page : 'project';
		return array(
			'page'              => $page_out,
			'initialStep'       => $initial,
			'canEdit'           => !$this->DD_project_model->is_locked($id, $email),
			'projectId'         => (int) $id,
			'projectTitle'      => isset($project[0]->title) ? $project[0]->title : '',
			'projectStatus'     => isset($project[0]->status) ? $project[0]->status : 'draft',
			'dataType'          => $data_type,
			'formTemplate'      => $templates['form_template'],
			'submitTemplate'    => $templates['submit_template'],
			'metadataApiUrl'    => $api_base.'/'.$id.'/metadata',
			'submissionApiUrl'  => $api_base.'/'.$id.'/submission',
			'submitApiUrl'      => $api_base.'/'.$id.'/submit',
			'projectApiUrl'     => $api_base.'/'.$id,
			'filesApiUrl'       => $api_base.'/'.$id.'/files',
			'filesSaveApiUrl'   => $api_base.'/'.$id.'/files',
			'filesDeleteApiUrl' => $api_base.'/'.$id.'/files/delete',
			'studyUrl'          => site_url('datadeposit/study/'.$id),
		);
	}

	private function _deposit_vue_labels()
	{
		$this->lang->load('catalog_admin');

		return array(
			'reload'                    => t('metadata_reload'),
			'save'                      => t('save'),
			'saveUnsaved'               => t('metadata_save_unsaved'),
			'saved'                     => t('metadata_saved'),
			'loadFailed'                => t('metadata_load_failed'),
			'saveFailed'                => t('metadata_save_failed'),
			'unsavedLeave'              => t('metadata_unsaved_leave'),
			'unsavedReload'             => t('metadata_unsaved_reload'),
			'validationFailed'          => t('metadata_validation_failed'),
			'requiredMissing'           => t('dd_required_missing'),
			'cannotSubmit'              => t('dd_cannot_submit'),
			'validationIssues'          => t('dd_validation_issues'),
			'pendingTasks'              => t('dd_pending_tasks'),
			'requestFailed'             => t('metadata_request_failed'),
			'selectSection'             => t('metadata_select_section'),
			'addFromList'               => t('metadata_add_from_list'),
			'addRow'                    => t('metadata_add_row'),
			'add'                       => t('metadata_add'),
			'deleteRow'                 => t('metadata_delete_row'),
			'deleteRowConfirm'          => t('metadata_delete_row_confirm'),
			'noRows'                    => t('metadata_no_rows'),
			'noItems'                   => t('metadata_no_items'),
			'showHelp'                  => t('metadata_show_help'),
			'hideHelp'                  => t('metadata_hide_help'),
			'showAllHelp'               => t('metadata_show_help'),
			'hideAllHelp'               => t('metadata_hide_help'),
			'filterAll'                 => t('all'),
			'filterRequired'            => t('required'),
			'filterRecommended'         => t('recommended'),
			'searchFields'              => t('search'),
			'noMatchingFields'          => t('no_records_found'),
			'containerOverview'         => t('metadata_container_overview'),
			'sectionsInGroup'           => t('metadata_sections_in_group'),
			'nothingEntered'            => t('metadata_nothing_entered'),
			'noFields'                  => t('metadata_no_fields'),
			'noPreview'                 => t('metadata_no_preview'),
			'editSection'               => t('metadata_edit_section'),
			'item'                      => t('metadata_item'),
			'trueLabel'                 => t('metadata_true'),
			'falseLabel'                => t('metadata_false'),
			'submitProject'             => t('submit'),
			'submitted'                 => t('project_submitted'),
			'submitConfirm'             => t('dd_submit_confirm'),
			'locked'                    => t('project_locked_message'),
			'stepInfo'                  => t('project_info'),
			'stepMetadata'              => t('study_desc'),
			'stepFiles'                 => t('files_tab'),
			'stepAccess'                => t('dd_access_and_notes'),
			'stepSubmit'                => t('metadata_review'),
			'editStep'                  => t('edit'),
			'noFiles'                   => t('dd_no_files_uploaded'),
			'myProjects'                => t('my_projects'),
			'title'                     => t('title'),
			'shortname'                 => t('shortname'),
			'description'               => t('description'),
			'collaborators'             => t('collaborator'),
			'collaboratorHelp'          => t('create_collab'),
			'filesHelp'                 => t('dd_files_help'),
			'dropZoneHint'              => t('dd_drop_zone'),
			'allowedTypes'              => t('dd_allowed_types'),
			'uploadedFiles'             => t('dd_uploaded_files'),
			'addDetails'                => t('dd_add_details'),
			'editDetails'               => t('dd_edit_details'),
			'resourceDetails'           => t('dd_resource_details'),
			'resourceType'              => t('type'),
			'resourceTitle'             => t('title'),
			'resourceAuthor'            => t('authors'),
			'resourceDescription'       => t('description'),
			'noDetails'                 => t('dd_no_details'),
			'fileSize'                  => t('size'),
			'downloadFile'              => t('download'),
			'deleteFile'                => t('delete'),
			'deleteSelected'            => t('dd_delete_selected'),
			'confirmDeleteFile'         => t('dd_confirm_delete_file'),
			'confirmDeleteFiles'        => t('dd_confirm_delete_files'),
			'noSelection'               => t('dd_no_file_selection'),
			'uploadFailed'              => t('dd_upload_failed'),
			'fileTypeNotAllowed'        => t('dd_file_type_not_allowed'),
			'fileTooLarge'              => t('dd_file_too_large'),
			'maxFileSize'               => t('dd_max_file_size'),
			'retryUpload'               => t('dd_retry_upload'),
			'saveDetails'               => t('save'),
			'cancel'                    => t('cancel'),
			'selectType'                => t('__select__'),
			'newProject'                => t('new_project'),
			'startProject'              => t('dd_start_project'),
			'projectType'               => t('type'),
			'status'                    => t('dd_status'),
			'projectTypeHelp'           => t('dd_project_type_help'),
			'backToProjects'            => t('my_projects'),
			'createdBy'                 => t('created_by'),
			'createdOn'                 => t('dd_created_on'),
			'lastModified'              => t('dd_last_modified'),
			'edit'                      => t('edit'),
			'summary'                   => t('summary'),
			'print'                     => t('print'),
			'email'                     => t('email_to_friend'),
			'emailProject'              => t('email_project'),
			'emailTo'                   => t('dd_email_to'),
			'emailToHelp'               => t('dd_email_to_help'),
			'provideEmail'              => t('provide_email'),
			'emailSend'                 => t('dd_send'),
			'emailSent'                 => t('email_sent_successful'),
			'emailMax'                  => t('email_max_recipients'),
			'emailInvalid'              => t('invalid_email'),
			'emailWillSend'             => t('dd_email_will_send'),
			'viewSummary'               => t('summary'),
			'export'                    => t('dd_export'),
			'exportDdi'                 => t('dd_export_ddi'),
			'exportJson'                => t('dd_export_json'),
			'exportProjectJson'         => t('dd_export_project_json'),
			'exportRdf'                 => t('dd_export_rdf'),
			'exportExternalResources'   => t('dd_export_external_json'),
			'importMetadata'            => t('dd_import_metadata'),
			'importMetadataHelp'        => t('dd_import_metadata_help'),
			'importFromProject'         => t('dd_import_from_project'),
			'importFromFile'            => t('dd_import_from_file'),
			'importMetadataSearch'      => t('dd_import_search'),
			'importMetadataHint'        => t('dd_import_hint'),
			'importMetadataEmpty'       => t('dd_import_empty'),
			'importMetadataFile'        => t('dd_import_file'),
			'importMetadataFileHelp'    => t('dd_import_file_help'),
			'importMetadataFileInvalid' => t('dd_import_file_invalid'),
			'importMetadataFileLarge'   => t('dd_import_file_large'),
			'importMetadataConfirm'     => t('confirm_metadata_import'),
			'importMetadataSuccess'     => t('success_import'),
			'importMetadataFailed'      => t('fail_import'),
			'importMetadataRun'         => t('dd_import_run'),
			'projectId'                 => t('dd_project_id'),
			'delete'                    => t('delete'),
			'reopen'                    => t('request_reopen'),
			'reopenReason'              => t('dd_reopen_reason'),
			'reopenRequested'           => t('dd_reopen_requested'),
			'reopenSent'                => t('reopen_requested'),
			'confirmDelete'             => t('confirm_delete'),
			'confirmDeleteBody'         => t('confirm_delete_records'),
			'titleHelp'                 => t('create_title'),
			'shortnameHelp'             => t('create_short'),
			'descriptionHelp'           => t('create_desc'),
			'searchProjects'            => t('search'),
			'allStatuses'               => t('all'),
			'noMatchingProjects'        => t('no_records_found'),
			'showingRange'              => t('dd_showing_range'),
			'projectDeleted'            => t('project_deleted'),
			'processedLocked'           => t('project_processed'),
			'na'                        => t('dd_na'),
		);
	}

	private function _hide_library_breadcrumb()
	{
		if (isset($this->breadcrumb) && is_object($this->breadcrumb) && method_exists($this->breadcrumb, 'disable')) {
			$this->breadcrumb->disable();
		}
	}

	private function _deposit_project_types()
	{
		$this->lang->load('catalog_search');

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

	private function _deposit_allowed_data_types()
	{
		$out = array();
		foreach ($this->_deposit_project_types() as $row) {
			$out[] = $row['value'];
		}
		return $out;
	}

	private function _deposit_normalize_status($value)
	{
		$status = strtolower(trim((string) $value));
		$allowed = array('draft', 'submitted', 'accepted', 'processed', 'closed');
		if ($status === '' || !in_array($status, $allowed, true)) {
			return '';
		}
		return $status;
	}

	private function _deposit_normalize_data_type($value, $strict = false)
	{
		$type = strtolower(trim((string) $value));
		$aliases = array(
			'microdata'    => 'survey',
			'timeseriesdb' => 'timeseries-db',
		);
		if (isset($aliases[$type])) {
			$type = $aliases[$type];
		}
		if ($type === '') {
			return 'survey';
		}
		if (!in_array($type, $this->_deposit_allowed_data_types(), true)) {
			return $strict ? null : 'survey';
		}
		return $type;
	}

	private function _deposit_page_params()
	{
		$page = (int) $this->input->get('page');
		if ($page < 1) {
			$page = 1;
		}
		$page_size = (int) $this->input->get('page_size');
		if ($page_size < 1) {
			$page_size = 100;
		}
		if ($page_size > 100) {
			$page_size = 100;
		}
		return array(
			'page'      => $page,
			'page_size' => $page_size,
			'keywords'  => trim((string) $this->input->get('q')),
			'status'    => $this->_deposit_normalize_status($this->input->get('status')),
		);
	}

	private function _deposit_projects_page()
	{
		$this->load->library('Deposit_depositor');
		$sort = $this->_deposit_sort_params();
		$params = $this->_deposit_page_params();
		return $this->deposit_depositor->list_projects(
			(int) $this->session->userdata('user_id'),
			array_merge($params, $sort)
		);
	}

	private function _deposit_sort_params()
	{
		$allowed = array('title', 'created_by', 'status', 'created_on', 'last_modified');
		$sort_by = $this->input->get('sort_by');
		$sort_order = strtolower((string) $this->input->get('sort_order'));
		if (!in_array($sort_by, $allowed, true)) {
			$sort_by = 'created_on';
		}
		if ($sort_order !== 'asc' && $sort_order !== 'desc') {
			$sort_order = 'desc';
		}
		return array(
			'sort_by'    => $sort_by,
			'sort_order' => $sort_order,
		);
	}
}
