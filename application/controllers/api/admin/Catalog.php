<?php

use Swaggest\JsonDiff\JsonPatch;

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Admin Catalog API Controller
 *
 * Provides REST endpoints for the admin catalog management page.
 * All endpoints require authentication.
 *
 * Base URL: /api/admin/catalog
 *
 * Dataset writes (same contract as POST api/datasets/create|update|patch):
 *   POST /api/admin/catalog/create/{type}
 *   POST /api/admin/catalog/update/{type}/{idno}
 *   POST /api/admin/catalog/patch/{type}/{idno}
 *
 * Microdata (DDI data files + variables; `api/admin/microdata` controller): see `Microdata` class docblock — base path `/api/admin/catalog/{idno}/microdata/…`.
 *
 * Study tags: GET/POST/DELETE /api/admin/catalog/{idno}/tags (see `tags_get`, `tags_post`, `tags_delete`); POST …/tags/delete or …/tags_delete is an alias when DELETE is not available.
 *
 * Collections (owner + linked repos): GET|POST /api/admin/catalog/{idno}/collections — study-scoped (path `idno` required; optional query `id_format=id`).
 *
 * DOI: POST /api/admin/catalog/{idno}/doi — JSON `{ "doi": "..." }`; empty string or null clears (see also `options_post`).
 *
 * Aliases: GET|POST|DELETE /api/admin/catalog/{idno}/aliases (see `aliases_get`, `aliases_post`, `aliases_delete`); POST …/aliases/delete or …/aliases_delete is an alias when DELETE cannot be sent.
 *
 * Data access form list: GET /api/admin/catalog/data-access-codelist (`forms`: formid, fname, model).
 *
 * Data classifications: GET /api/admin/catalog/data-classifications — same controller as `/api/admin/data-classifications` (route alias).
 *
 * Study package ZIP: GET /api/admin/catalog/{idno}/package — same behaviour as GET api/datasets/{idno}/package (optional query `dsd_export=inline|reference`).
 * Staged package import (large ZIP): api/uploads then POST api/admin/catalog/import_package/{unzip|create|datafile|finalize}; GET import_package/status?upload_id=…
 *
 * IDNO utilities: GET /api/admin/catalog/check_idno/{idno} (existence); POST /api/admin/catalog/replace_idno — JSON `{ "old_idno", "new_idno" }` (same as api/datasets/replace_idno).
 *
 * DDI PDF on disk: POST/DELETE via `{idno}/generate_pdf` and `delete_pdf/{idno}`; PDF documentation status may use `api/admin/pdf_documentation` when deployed.
 *
 * Primary study payload: GET /api/admin/catalog/{idno} (`info_get`) includes idno, years, countries, aliases, folder_path / folder_path_full, metadata.
 *
 * Study maintenance warnings: GET /api/admin/catalog/{idno}/warnings — list of `key` + translated `message` (see `Catalog_admin::get_study_warnings`).
 *
 * Study summary aggregates: GET /api/admin/catalog/{idno}/summary — related-entity counts (`files`, `resources`, …); optional query `id_format=id`, `keys=files,resources,...`.
 *
 * Study folder on disk: GET|POST /api/admin/catalog/{idno}/folder-status — optional query `id_format=id`.
 *   GET: JSON `exists`, `folder_path`, `folder_path_full` (read-only).
 *   POST: creates the directory on disk (and may set `surveys.dirpath` when empty, same as new-study flow); requires edit access; JSON same shape as GET.
 *
 * Study link picker: GET /api/admin/catalog/list_collections — all collections `{ id, repositoryid, title }`; gated by `user_has_any_admin_capability()` (not ACL-scoped).
 */
class Catalog extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->helper('date');
		$this->load->model('Catalog_model');
		$this->load->model('Form_model');
		$this->load->model('Repository_model');
		$this->load->model('Citation_model');
		$this->load->model('Search_helper_model');
		$this->load->model('Catalog_admin_search');
		$this->load->model('Licensed_model');
		$this->load->model('Catalog_tags_model');
		$this->load->library('Dataset_manager');
	}


	/**
	 * Support both session auth and API key auth
	 */


	/**
	 * Create new dataset (legacy-compatible with POST api/datasets/create/{type}).
	 *
	 * POST /api/admin/catalog/create/{type}
	 */
	function create_post($type = null, $idno = null)
	{
		if ($type == 'timeseries-db') {
			$type = 'timeseriesdb';
		}

		try {
			$options = $this->raw_json_input();
			$user_id = $this->get_api_user_id();

			$options['created_by'] = $user_id;
			$options['changed_by'] = $user_id;
			$options['created'] = date("U");
			$options['changed'] = date("U");

			if (! isset($options['repositoryid'])) {
				$options['repositoryid'] = 'central';
			}

			if (isset($options['data_remote_url'])) {
				$options['link_da'] = $options['data_remote_url'];
			}

			$this->has_dataset_access('edit', null, $options['repositoryid']);

			$dataset_id = $this->dataset_manager->create_dataset($type, $options);

			if (! $dataset_id) {
				throw new Exception("FAILED_TO_CREATE_DATASET");
			}

			$dataset = $this->dataset_manager->get_row($dataset_id);

			$dataset['dirpath'] = $this->dataset_manager->setup_folder($options['repositoryid'], md5($dataset['idno']));

			$update_options = array(
				'dirpath' => $dataset['dirpath'],
			);

			$this->dataset_manager->update_options($dataset_id, $update_options);
			$this->events->emit('db.after.update', 'surveys', $dataset_id, 'import');

			$response = array(
				'status' => 'success',
				'dataset' => $dataset,
				'_links' => array(
					'view' => site_url('catalog/' . $dataset['id']),
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => (array) $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Full dataset update (legacy-compatible with POST api/datasets/update/{type}/{idno}).
	 *
	 * POST /api/admin/catalog/update/{type}/{idno}
	 */
	function update_post($type = null, $idno = null)
	{
		if ($type == 'timeseries-db') {
			$type = 'timeseriesdb';
		}

		try {
			$options = $this->raw_json_input();
			$user_id = $this->get_api_user_id();

			$sid = $this->get_sid_from_idno($idno);

			$this->has_dataset_access('edit', $sid);

			$dataset = $this->dataset_manager->get_row($sid);

			$options['changed_by'] = $user_id;
			$options['changed'] = date("U");

			$merge_metadata = true;

			if (isset($options['merge_options'])) {
				if ($options['merge_options'] == 'replace') {
					$merge_metadata = false;
				}
			}

			$options = array_merge($dataset, $options);

			if ($type == 'survey' || $type == 'document' || $type == 'table' || $type == 'geospatial' || $type == 'image' || $type == 'video' || $type == 'timeseries' || $type == 'timeseriesdb') {
				$dataset_id = $this->dataset_manager->update_dataset($sid, $type, $options, $merge_metadata);
			}
			else {
				$metadata = $this->dataset_manager->get_metadata($sid);

				if ($merge_metadata == true) {
					$options = array_replace_recursive($metadata, $options);
				}

				$dataset_id = $this->dataset_manager->create_dataset($type, $options);
			}

			$dataset = $this->dataset_manager->get_row($dataset_id);

			$this->events->emit('db.after.update', 'surveys', $dataset_id, 'refresh');

			$response = array(
				'status' => 'success',
				'dataset' => $dataset,
				'_links' => array(
					'view' => site_url('catalog/' . $dataset['id']),
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * JSON Patch on dataset metadata (legacy-compatible with POST api/datasets/patch/{type}/{idno}).
	 *
	 * POST /api/admin/catalog/patch/{type}/{idno}
	 * Body: { "patches": [ ... ] }
	 */
	function patch_post($type = null, $idno = null)
	{
		if ($type == 'timeseries-db') {
			$type = 'timeseriesdb';
		}

		try {
			$body = $this->raw_json_input();
			if (! is_array($body)) {
				throw new Exception("INVALID_JSON_INPUT");
			}

			if (! isset($body['patches'])) {
				throw new Exception("`patches` parameter is required");
			}

			if (! is_array($body['patches'])) {
				throw new Exception("`patches` must be a JSON array");
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$dataset = $this->dataset_manager->get_row($sid);

			if (! $dataset) {
				throw new Exception("DATASET_NOT_FOUND");
			}

			if ($dataset['type'] !== $type) {
				throw new Exception("TYPE_MISMATCH: dataset type is [" . $dataset['type'] . "] but URL type is [" . $type . "]");
			}

			$metadata = $this->dataset_manager->get_metadata($sid);

			if (! is_array($metadata)) {
				throw new Exception("METADATA_NOT_AVAILABLE");
			}

			$metadata_for_patch = json_decode(json_encode($metadata));
			$patch = JsonPatch::import($body['patches']);
			$patch->setFlags(1);
			$patch->apply($metadata_for_patch);

			$patched = json_decode(json_encode($metadata_for_patch), true);

			$user_id = $this->get_api_user_id();
			$options = array_merge($dataset, $patched);
			$options['changed_by'] = $user_id;
			$options['changed'] = date("U");

			if ($type == 'survey' || $type == 'document' || $type == 'table' || $type == 'geospatial' || $type == 'image' || $type == 'video' || $type == 'timeseries' || $type == 'timeseriesdb') {
				$dataset_id = $this->dataset_manager->update_dataset($sid, $type, $options, true, false);
			}
			else {
				$dataset_id = $this->dataset_manager->create_dataset($type, $options);
			}

			$dataset = $this->dataset_manager->get_row($dataset_id);

			$this->events->emit('db.after.update', 'surveys', $dataset_id, 'refresh');

			$response = array(
				'status' => 'success',
				'dataset' => $dataset,
				'_links' => array(
					'view' => site_url('catalog/' . $dataset['id']),
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => (array) $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * List studies (`index_get`) or dataset row + optional JSON metadata (when `{idno}` path segment present).
	 *
	 * GET /api/admin/catalog
	 * GET /api/admin/catalog/{idno}
	 *
	 * Path `{idno}` is passed as `$idno` (REST remap) for single-study fetch.
	 * Query `idno` on the list endpoint is a prefix filter only (see list params below).
	 * Resolution: `get_sid_from_idno()`, query `id_format=id` for numeric `surveys.id`.
	 * Query `exclude_metadata=1` (or `true`) omits the embedded metadata blob (same row as api/datasets otherwise).
	 *
	 * List query params (when `$idno` is absent):
	 *   keywords     string   text search
	 *   title        string   filter by title
	 *   idno         string   filter by study identifier (prefix LIKE — not used when fetching a single study by idno ref)
	 *   nation[]     string   filter by country/nation (repeatable)
	 *   tag[]        string   filter by tag (repeatable)
	 *   data_access[] string  filter by data access code e.g. direct, licensed (repeatable)
	 *   type[]       string   filter by dataset type e.g. survey, geospatial (repeatable)
	 *   published    0|1      filter by publish status
	 *   sort_by      string   column to sort by (default: changed)
	 *   sort_order   asc|desc sort direction (default: desc)
	 *   sort         string   e.g. title_asc, country_desc, modified_desc (overrides sort_by/sort_order)
	 *   ps           int      page size, 1–300 (default: 15)
	 *   page         int      page number (default: 1)
	 *   owner_repo   string   optional filter by owner surveys.repositoryid; empty or central = no owner filter.
	 *                          Must be within the user's admin catalog scope when scope is restricted.
	 *   collections[] string  filter by collections (surveys linked to these repos via survey_repos)
	 *
	 * Optional path segment `search` (GET …/catalog/search) is treated as list mode so REST remap does not
	 * interpret it as an idno; query `idno` there remains a list filter (prefix LIKE).
	 */
	function index_get($idno = null)
	{
		if ($idno !== null && strcasecmp((string) $idno, 'data-access-codelist') === 0) {
			return $this->data_access_codelist_get();
		}

		if ($idno !== null && strcasecmp((string) $idno, 'search') === 0) {
			$idno = null;
		}

		// Single study only via path segment; ?idno= is a list filter (Catalog_admin_search).
		if ($idno) {
			return $this->_get_study_info($idno);
		}

		return $this->_admin_catalog_search_list_get();
	}


	/**
	 * Study row + metadata for one dataset (matches api/datasets `single_get`, plus optional metadata omission).
	 *
	 * GET /api/admin/catalog/{idno} via `index_get`.
	 *
	 * Query: `exclude_metadata=1|true` skips `dataset_manager::get_metadata()` (lighter payload).
	 * `aliases` is always included: rows from `survey_aliases` (`id`, `sid`, `alternate_id`).
	 * `countries` is always included: entries from `survey_countries` joined to `countries` (`cid`, `iso`, `name`).
	 * `folder_path` — relative catalog folder (`surveys.dirpath`); `folder_path_full` — absolute path on disk when configured (see `Catalog_model::get_survey_path_full`).
	 */
	private function _get_study_info($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$result = $this->dataset_manager->get_row($sid);
			if (! is_array($result) || ! $result) {
				throw new Exception('DATASET_NOT_FOUND');
			}

			array_walk($result, 'unix_date_to_gmt_row', array('created', 'changed'));

			$this->load->model('Survey_alias_model');
			$result['aliases'] = $this->Survey_alias_model->get_aliases($sid);
			if (! is_array($result['aliases'])) {
				$result['aliases'] = array();
			}

			$result['countries'] = $this->Catalog_model->get_survey_iso_countries($sid);
			if (! is_array($result['countries'])) {
				$result['countries'] = array();
			}

			$rel_folder = $this->Catalog_model->get_survey_path($sid);
			$result['folder_path']      = ($rel_folder !== false && $rel_folder !== '') ? $rel_folder : null;
			$full_folder                = $this->Catalog_model->get_survey_path_full($sid);
			$result['folder_path_full'] = ($full_folder !== false && $full_folder !== '') ? $full_folder : null;

			$this->load->model('Survey_resource_model');
			$result['microdata_files'] = $this->Survey_resource_model->get_microdata_resources($sid);
			if (! is_array($result['microdata_files'])) {
				$result['microdata_files'] = array();
			}

			$this->db->where('sid', $sid);
			$result['survey_repos'] = $this->db->get('survey_repos')->result_array();
			if (! is_array($result['survey_repos'])) {
				$result['survey_repos'] = array();
			}

			$this->load->library('catalog_admin');
			$result['pdf_documentation'] = $this->catalog_admin->get_study_pdf($sid);
			if (! is_array($result['pdf_documentation'])) {
				$result['pdf_documentation'] = array('status' => 'na');
			}

			$result['is_featured'] = $this->_survey_is_featured($sid, $result);

			$exclude_metadata = filter_var($this->input->get('exclude_metadata'), FILTER_VALIDATE_BOOLEAN);
			if (! $exclude_metadata) {
				$result['metadata'] = $this->dataset_manager->get_metadata($sid);
			}

			$response = array(
				'status'   => 'success',
				'dataset'  => $result,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$error_output = array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Data access policy codelist (`forms`: formid, display name, model code).
	 *
	 * GET /api/admin/catalog/data-access-codelist
	 */
	function data_access_codelist_get()
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$this->db->select('formid, fname, model');
			$this->db->from('forms');
			$this->db->order_by('formid', 'asc');
			$codelist = $this->db->get()->result_array();
			if (! is_array($codelist)) {
				$codelist = array();
			}

			$this->set_response(
				array(
					'status'   => 'success',
					'codelist' => $codelist,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Data access form rows allowed for a data classification code (matches `config/data_access.php` `data_access_options`).
	 *
	 * GET /api/admin/catalog/data-access-options?classification=public
	 */
	function data_access_options_get()
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$classification = trim((string) $this->input->get('classification'));
			if ($classification === '') {
				throw new Exception('CLASSIFICATION_QUERY_REQUIRED');
			}

			$this->load->config('data_access');
			$map    = $this->config->item('data_access_options');
			$map    = is_array($map) ? $map : array();
			$allowed = isset($map[$classification]) && is_array($map[$classification]) ? $map[$classification] : array();

			$this->db->select('formid, fname, model');
			$this->db->from('forms');
			$this->db->order_by('formid', 'asc');
			$rows = $this->db->get()->result_array();
			if (! is_array($rows)) {
				$rows = array();
			}

			$filtered = array();
			foreach ($rows as $row) {
				if (isset($row['model']) && in_array($row['model'], $allowed, true)) {
					$filtered[] = $row;
				}
			}

			$this->set_response(
				array(
					'status'          => 'success',
					'classification'  => $classification,
					'allowed_models'   => $allowed,
					'codelist'        => $filtered,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Generate DDI documentation PDF (same behaviour as POST api/datasets/{idno}/generate_pdf).
	 *
	 * POST /api/admin/catalog/{idno}/generate_pdf — JSON body optional (variable_toc, variable_description, include_resources, language).
	 */
	function generate_pdf_post($idno = null)
	{
		$this->load->helper('url_filter');

		try {
			$options = $this->raw_json_input();

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);
			$dataset = $this->dataset_manager->get_row($sid);

			if (! is_array($dataset) || ! $dataset) {
				throw new Exception('DATASET_NOT_FOUND');
			}

			if ($dataset['type'] != 'survey') {
				throw new Exception('PDF can only be generated for Surveys only');
			}

			$pdf_options = array(
				'publisher'      => $dataset['authoring_entity'],
				'website_title'  => $this->config->item('website_title'),
				'study_title'    => $dataset['title'],
				'website_url'    => site_url(),
				'toc_variable'   => isset($options['variable_toc']) ? (int) $options['variable_toc'] : 1,
				'data_dic_desc'  => isset($options['variable_description']) ? (int) $options['variable_description'] : 1,
				'ext_resources'  => isset($options['include_resources']) ? (int) $options['include_resources'] : 1,
				'report_lang'    => isset($options['language']) ? $options['language'] : 'en',
			);

			if ($pdf_options['ext_resources'] === 1) {
				$this->load->helper('Resource_helper');
				$this->load->model('Survey_resource_model');

				$survey_resources = array();
				$survey_resources['resources']     = $this->Survey_resource_model->get_grouped_resources_by_survey($sid);
				$survey_resources['survey_folder'] = $this->Catalog_model->get_survey_path_full($sid);

				$pdf_options['ext_resources_html'] = $this->load->view('ddibrowser/report_external_resource', $survey_resources, true);
			}

			$log_threshold = $this->config->item('log_threshold');
			$this->config->set_item('log_threshold', 0);

			$params = array('codepage' => $pdf_options['report_lang']);

			$this->load->library('pdf_report', $params);

			$survey_folder = $this->Catalog_model->get_survey_path_full($sid);

			$report_file = unix_path($survey_folder . '/ddi-documentation-' . $this->config->item('language') . '-' . $sid . '.pdf');

			$this->config->set_item('log_threshold', 0);

			$this->pdf_report->generate($sid, $report_file, $pdf_options);

			$this->config->set_item('log_threshold', $log_threshold);

			$response = array(
				'status'             => 'success',
				'options'            => $pdf_options,
				'dataset_id'         => $dataset['id'],
				'dataset_variables'  => $dataset['varcount'],
				'output'             => $report_file,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
				'errors'  => $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$error_output = array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Delete generated DDI documentation PDF file on disk (same as admin/pdf_generator/delete).
	 *
	 * POST /api/admin/catalog/delete_pdf/{idno} — optional query id_format=id for numeric surveys.id.
	 */
	function delete_pdf_post($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$dataset = $this->dataset_manager->get_row($sid);
			if (! is_array($dataset) || ! $dataset) {
				throw new Exception('DATASET_NOT_FOUND');
			}
			if ($dataset['type'] != 'survey') {
				throw new Exception('PDF delete applies to survey studies only');
			}

			$this->load->library('catalog_admin');
			$removed = (bool) $this->catalog_admin->delete_study_pdf($sid);

			$this->set_response(
				array(
					'status'   => 'success',
					'removed'  => $removed,
					'message'  => $removed ? 'PDF_DELETED' : 'PDF_NOT_PRESENT',
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Upload study thumbnail (same behaviour as POST api/datasets/{idno}/thumbnail).
	 *
	 * POST /api/admin/catalog/{idno}/thumbnail — multipart field `file` (jpg/png/gif/jpeg).
	 * Query `id_format=id` when `{idno}` is numeric surveys.id.
	 */
	function thumbnail_post($dataset_idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($dataset_idno);
			$this->has_dataset_access('edit', $sid);

			$thumbnail_storage_path = 'files/thumbnails';
			$thumbnail_abs_path     = FCPATH . $thumbnail_storage_path;

			if ( ! is_dir($thumbnail_abs_path)) {
				if ( ! @mkdir($thumbnail_abs_path, 0755, true)) {
					throw new Exception('Could not create thumbnail storage directory: ' . $thumbnail_abs_path);
				}
			}

			$config['upload_path']       = $thumbnail_abs_path;
			$config['overwrite']         = false;
			$config['encrypt_name']      = false;
			$config['file_name']         = 'thumbnail-s' . $sid;
			$config['file_ext_tolower']  = true;
			$config['allowed_types']     = 'jpg|png|gif|jpeg';

			$this->load->library('upload', $config);

			$upload_result = $this->upload->do_upload('file');

			if ( ! $upload_result) {
				$error = $this->upload->display_errors('', '');
				throw new Exception('FILE_UPLOAD::' . $error . ' [path: ' . $thumbnail_storage_path . ']');
			}

			$upload             = $this->upload->data();
			$uploaded_file_name = $upload['file_name'];

			$this->dataset_manager->update_options($sid, array('thumbnail' => $uploaded_file_name));
			$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');

			$this->set_response(
				array(
					'status'               => 'success',
					'uploaded_file_name' => $uploaded_file_name,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Remove study thumbnail reference (same behaviour as DELETE api/datasets/{idno}/thumbnail).
	 *
	 * DELETE /api/admin/catalog/{idno}/thumbnail — optional query id_format=id.
	 */
	function thumbnail_delete($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$this->dataset_manager->update_options($sid, array('thumbnail' => null));
			$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Alias when HTTP client cannot send DELETE (same as api/datasets thumbnail_delete_post).
	 */
	function thumbnail_delete_post($idno = null)
	{
		return $this->thumbnail_delete($idno);
	}


	/**
	 * List survey tags (`survey_tags` rows).
	 *
	 * GET /api/admin/catalog/{idno}/tags — optional query `id_format=id` for numeric surveys.id.
	 */
	function tags_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$tags = $this->Catalog_tags_model->survey_tags($sid);
			if (! is_array($tags)) {
				$tags = array();
			}

			$this->set_response(
				array(
					'status' => 'success',
					'tags'   => $tags,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Add tags to a study (upsert; duplicates ignored).
	 *
	 * POST /api/admin/catalog/{idno}/tags — JSON body `{ "tags": ["a","b"] }` (required).
	 */
	function tags_post($idno = null)
	{
		$this->load->model('Dataset_model');

		try {
			$input = $this->raw_json_input();
			if (! is_array($input) || ! isset($input['tags'])) {
				throw new Exception('TAGS_ARRAY_REQUIRED');
			}
			if (! is_array($input['tags'])) {
				throw new Exception('TAGS_MUST_BE_ARRAY');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$normalized = array();
			foreach ($input['tags'] as $t) {
				if ($t === null || $t === '') {
					continue;
				}
				$normalized[] = $this->_normalize_catalog_tag_for_storage((string) $t);
			}

			$this->Dataset_model->add_survey_tags($sid, $normalized);

			$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');

			$tags = $this->Catalog_tags_model->survey_tags($sid);
			if (! is_array($tags)) {
				$tags = array();
			}

			$this->set_response(
				array(
					'status' => 'success',
					'tags'   => $tags,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Remove tags from a study.
	 *
	 * DELETE /api/admin/catalog/{idno}/tags
	 * POST /api/admin/catalog/{idno}/tags_delete — same behaviour and query/body params (alias when DELETE cannot be sent).
	 *
	 * - Query: `id` = single `survey_tags.id` (must belong to this study), and/or `tag` = one tag string.
	 * - JSON body (optional): `{ "ids": [1,2] }` and/or `{ "tags": ["a","b"] }` (`tag_ids` accepted as alias for `ids`).
	 */
	function tags_delete($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$payload = $this->_catalog_tags_parse_delete_payload();

			$ids = $payload['ids'];
			$tag_strings = $payload['tags'];

			$id_q = $this->input->get('id');
			$tag_q = $this->input->get('tag');

			$had_any_delete_param = ($id_q !== null && $id_q !== '')
				|| ($tag_q !== null && $tag_q !== '')
				|| ! empty($payload['ids'])
				|| ! empty($payload['tags']);

			if (! $had_any_delete_param) {
				throw new Exception('NO_TAG_DELETE_PARAMS');
			}

			if ($id_q !== null && $id_q !== '') {
				$ids[] = (int) $id_q;
			}
			if ($tag_q !== null && $tag_q !== '') {
				$tag_strings[] = $tag_q;
			}

			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			$removed = 0;

			foreach ($ids as $tid) {
				if ($tid < 1) {
					continue;
				}
				$row = $this->Catalog_tags_model->single($tid);
				if (! is_array($row) || (int) $row['sid'] !== (int) $sid) {
					continue;
				}
				if ($this->Catalog_tags_model->delete($tid)) {
					$removed++;
				}
			}

			foreach ($tag_strings as $t) {
				$norm = $this->_normalize_catalog_tag_for_storage((string) $t);
				if ($norm === '') {
					continue;
				}
				if ($this->Catalog_tags_model->delete_by_sid_and_tag($sid, $norm)) {
					$removed++;
				}
			}

			if ($removed > 0) {
				$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');
			}

			$tags = $this->Catalog_tags_model->survey_tags($sid);
			if (! is_array($tags)) {
				$tags = array();
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'removed' => $removed,
					'tags'    => $tags,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * POST alias for `tags_delete` (same as `thumbnail_delete_post`).
	 */
	function tags_delete_post($idno = null)
	{
		return $this->tags_delete($idno);
	}


	/**
	 * Catalog maintenance warnings for one study (same rules as admin study edit sidebar).
	 *
	 * GET /api/admin/catalog/{idno}/warnings — optional query `id_format=id`.
	 *
	 * @return array{status: string, total: int, warnings: array<array{key: string, message: string}>}
	 */
	function warnings_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$this->load->library('catalog_admin');
			$this->lang->load('catalog_admin');

			$keys = $this->catalog_admin->get_study_warnings($sid);
			if (! is_array($keys)) {
				$keys = array();
			}

			$warnings = array();
			foreach ($keys as $key) {
				if (! is_string($key) || $key === '') {
					continue;
				}
				$warnings[] = array(
					'key'     => $key,
					'message' => t($key),
				);
			}

			$this->set_response(
				array(
					'status'   => 'success',
					'total'    => count($warnings),
					'warnings' => $warnings,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Lightweight study aggregates (related-entity counts for admin UI).
	 *
	 * GET /api/admin/catalog/{idno}/summary — optional query `id_format=id`, `keys=files,resources,citations,notes,related_studies`.
	 *
	 * @return array{status: string, summary: array<string, int>}
	 */
	function summary_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$summary = $this->_build_study_summary($sid, $this->_parse_summary_keys());

			$this->set_response(
				array(
					'status'  => 'success',
					'summary' => $summary,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * @return list<string>
	 */
	private function _parse_summary_keys()
	{
		$allowed = array('files', 'resources', 'citations', 'notes', 'related_studies');
		$raw = $this->input->get('keys');
		if ($raw === null || $raw === '') {
			return $allowed;
		}
		$parts = array_map('trim', explode(',', (string) $raw));
		$keys = array();
		foreach ($parts as $part) {
			if ($part !== '' && in_array($part, $allowed, true)) {
				$keys[] = $part;
			}
		}
		return count($keys) > 0 ? $keys : $allowed;
	}


	/**
	 * @param int        $sid
	 * @param list<string> $keys
	 * @return array<string, int>
	 */
	private function _build_study_summary($sid, array $keys)
	{
		$summary = array();
		$sid = (int) $sid;

		foreach ($keys as $key) {
			switch ($key) {
				case 'files':
					$this->load->library('catalog_admin');
					$files = $this->catalog_admin->get_files_array($sid);
					$summary['files'] = is_array($files) ? count($files) : 0;
					break;

				case 'resources':
					$this->load->model('Survey_resource_model');
					$summary['resources'] = (int) $this->Survey_resource_model->get_resources_count_by_survey($sid);
					break;

				case 'citations':
					$summary['citations'] = (int) $this->Citation_model->get_citations_count_by_survey($sid);
					break;

				case 'notes':
					$this->load->model('Catalog_notes_model');
					$summary['notes'] = (int) $this->Catalog_notes_model->get_notes_count_by_study($sid);
					break;

				case 'related_studies':
					$this->load->model('Related_study_model');
					$summary['related_studies'] = (int) $this->Related_study_model->get_relationships_count($sid);
					break;
			}
		}

		return $summary;
	}


	/**
	 * Whether the study catalog folder exists on disk (no writes; does not create folders).
	 *
	 * GET /api/admin/catalog/{idno}/folder-status — optional query `id_format=id`.
	 *
	 * @return array{status: string, exists: bool, folder_path: string|null, folder_path_full: string|null}
	 */
	function folder_status_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$rel_folder = $this->Catalog_model->get_survey_path($sid);
			$folder_path = ($rel_folder !== false && $rel_folder !== '') ? $rel_folder : null;

			$folder_path_full = null;
			$exists = false;

			if ($folder_path !== null) {
				$folder_path_full = unix_path(get_catalog_root() . '/' . $rel_folder);
				$exists = is_dir($folder_path_full);
			}

			$this->set_response(
				array(
					'status'           => 'success',
					'exists'           => $exists,
					'folder_path'      => $folder_path,
					'folder_path_full' => $folder_path_full,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Create the study catalog folder on disk (idempotent if it already exists).
	 *
	 * POST /api/admin/catalog/{idno}/folder-status — optional query `id_format=id`; empty JSON body.
	 *
	 * If `surveys.dirpath` is empty, assigns `dirpath` via `Dataset_manager::setup_folder` (repository + md5(idno)), matching new-dataset behaviour.
	 * If `dirpath` is set but the directory is missing, runs `mkdir` under resolved `get_catalog_root()`.
	 *
	 * @return array{status: string, exists: bool, folder_path: string|null, folder_path_full: string|null}
	 */
	function folder_status_post($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$row = $this->dataset_manager->get_row($sid);
			if (! is_array($row) || ! $row) {
				throw new Exception('DATASET_NOT_FOUND');
			}

			$rel_folder = $this->Catalog_model->get_survey_path($sid);

			if ($rel_folder === false || $rel_folder === '') {
				$repo = isset($row['repositoryid']) ? trim((string) $row['repositoryid']) : '';
				if ($repo === '') {
					$repo = 'central';
				}
				$idno_str = isset($row['idno']) ? trim((string) $row['idno']) : '';
				if ($idno_str === '') {
					throw new Exception('STUDY_IDNO_MISSING');
				}
				$new_rel = $this->dataset_manager->setup_folder($repo, md5($idno_str));
				$this->dataset_manager->update_options($sid, array('dirpath' => $new_rel));
				$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');
				$rel_folder = $new_rel;
			}

			$folder_path_full = unix_path(get_catalog_root() . '/' . $rel_folder);

			if (! is_dir($folder_path_full)) {
				if (! @mkdir($folder_path_full, 0777, true)) {
					throw new Exception('SURVEY_FOLDER_NOT_CREATED');
				}
				if (! is_dir($folder_path_full)) {
					throw new Exception('SURVEY_FOLDER_NOT_CREATED');
				}
			}

			$exists = is_dir($folder_path_full);

			$this->set_response(
				array(
					'status'           => 'success',
					'exists'           => $exists,
					'folder_path'      => $rel_folder,
					'folder_path_full' => $folder_path_full,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * List survey aliases (`survey_aliases` rows).
	 *
	 * GET /api/admin/catalog/{idno}/aliases — optional query `id_format=id`.
	 */
	function aliases_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$this->load->model('Survey_alias_model');
			$aliases = $this->Survey_alias_model->get_aliases($sid);
			if (! is_array($aliases)) {
				$aliases = array();
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'aliases' => $aliases,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Add aliases (additive; same rules as `Dataset_model::add_survey_aliases`).
	 *
	 * POST /api/admin/catalog/{idno}/aliases — JSON `{ "aliases": ["alt-idno", ...] }`.
	 */
	function aliases_post($idno = null)
	{
		$this->load->model('Dataset_model');

		try {
			$input = $this->raw_json_input();
			if (! is_array($input) || ! isset($input['aliases'])) {
				throw new Exception('ALIASES_ARRAY_REQUIRED');
			}
			if (! is_array($input['aliases'])) {
				throw new Exception('ALIASES_MUST_BE_ARRAY');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$this->load->model('Survey_alias_model');
			$this->Dataset_model->add_survey_aliases($sid, $input['aliases']);

			$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');

			$aliases = $this->Survey_alias_model->get_aliases($sid);
			if (! is_array($aliases)) {
				$aliases = array();
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'aliases' => $aliases,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Remove aliases from a study.
	 *
	 * DELETE /api/admin/catalog/{idno}/aliases
	 * POST /api/admin/catalog/{idno}/aliases_delete — same behaviour and query/body params (alias when DELETE cannot be sent).
	 *
	 * Query `id` (survey_aliases row id) and/or `alternate_id` (string);
	 * optional JSON body `{ "ids": [...], "alternate_ids": [...] }`.
	 */
	function aliases_delete($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$this->load->model('Survey_alias_model');

			$payload = $this->_catalog_aliases_parse_delete_payload();

			$ids              = $payload['ids'];
			$alternate_strings = $payload['alternate_ids'];

			$id_q = $this->input->get('id');
			$alt_q = $this->input->get('alternate_id');

			$had_any = ($id_q !== null && $id_q !== '')
				|| ($alt_q !== null && $alt_q !== '')
				|| ! empty($payload['ids'])
				|| ! empty($payload['alternate_ids']);

			if (! $had_any) {
				throw new Exception('NO_ALIAS_DELETE_PARAMS');
			}

			if ($id_q !== null && $id_q !== '') {
				$ids[] = (int) $id_q;
			}
			if ($alt_q !== null && $alt_q !== '') {
				$alternate_strings[] = $alt_q;
			}

			$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
			$removed = 0;

			foreach ($ids as $aid) {
				if ($aid < 1) {
					continue;
				}
				$row = $this->Survey_alias_model->single($aid);
				if (! is_array($row) || (int) $row['sid'] !== (int) $sid) {
					continue;
				}
				if ($this->Survey_alias_model->delete($aid)) {
					$removed++;
				}
			}

			foreach ($alternate_strings as $alt) {
				$alt = trim((string) $alt);
				if ($alt === '') {
					continue;
				}
				if ($this->Survey_alias_model->delete_by_sid_and_alternate($sid, $alt)) {
					$removed++;
				}
			}

			if ($removed > 0) {
				$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');
			}

			$aliases = $this->Survey_alias_model->get_aliases($sid);
			if (! is_array($aliases)) {
				$aliases = array();
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'removed' => $removed,
					'aliases' => $aliases,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * POST alias for `aliases_delete` (same as `tags_delete_post`).
	 */
	function aliases_delete_post($idno = null)
	{
		return $this->aliases_delete($idno);
	}


	/**
	 * Return owner and linked collections for one study.
	 *
	 * GET /api/admin/catalog/{idno}/collections — optional query `offset`, `limit` (default 500).
	 */
	function collections_get($idno = null)
	{
		try {
			if ($idno === null || $idno === '') {
				throw new Exception('IDNO-NOT-PROVIDED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$offset = (int) $this->input->get('offset');
			$limit  = (int) $this->input->get('limit');

			if (! $limit) {
				$limit = 500;
			}

			$study_idno = $this->dataset_manager->get_idno($sid);
			if (! $study_idno) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$result = $this->Repository_model->owner_linked_collections($limit, $offset, $study_idno);

			$this->set_response(
				array(
					'status'   => 'success',
					'found'    => count($result),
					'offset'   => $offset,
					'limit'    => $limit,
					'datasets' => $result,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Manage collection owner and links for one study (`study_idno` is taken from the path).
	 *
	 * POST /api/admin/catalog/{idno}/collections — JSON body: `link_collections` (required), optional `owner_collection`, `mode` (`replace`|`update`).
	 * Body may omit `study_idno`; if present it must match the path study.
	 */
	function collections_post($idno = null)
	{
		try {
			if ($idno === null || $idno === '') {
				throw new Exception('IDNO-NOT-PROVIDED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$canonical_idno = $this->dataset_manager->get_idno($sid);
			if (! $canonical_idno) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$options = (array) $this->raw_json_input();

			$key = key($options);
			if (! is_numeric($key)) {
				$tmp_options   = array();
				$tmp_options[] = $options;
				$options       = $tmp_options;
			}

			foreach ($options as $collection_options) {
				if (! is_array($collection_options)) {
					continue;
				}
				if (isset($collection_options['study_idno'])) {
					$body_idno = (string) $collection_options['study_idno'];
					if (strcasecmp($body_idno, (string) $canonical_idno) !== 0) {
						throw new Exception('study_idno_MISMATCH_PATH');
					}
				}
				$collection_options['study_idno'] = $canonical_idno;
				$this->Repository_model->update_collection_studies($collection_options);
			}

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Check if a study IDNO exists (same contract as GET api/datasets/check_idno/{idno}).
	 *
	 * GET /api/admin/catalog/check_idno/{idno}
	 */
	function check_idno_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->dataset_manager->find_by_idno($idno);
			if ($sid) {
				$this->has_dataset_access('view', $sid);
				$this->set_response(
					array(
						'status' => 'success',
						'idno'   => $idno,
						'id'     => $sid,
					),
					REST_Controller::HTTP_OK
				);
			}
			else {
				if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
					throw new AclAccessDeniedException('ACCESS-DENIED');
				}
				$this->set_response(
					array(
						'status'  => 'not-found',
						'idno'    => $idno,
						'message' => 'IDNO NOT FOUND',
					),
					REST_Controller::HTTP_NOT_FOUND
				);
			}
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Replace study IDNO (same contract as POST api/datasets/replace_idno).
	 *
	 * POST /api/admin/catalog/replace_idno — JSON `{ "old_idno": "…", "new_idno": "…" }`.
	 */
	function replace_idno_post()
	{
		$this->load->helper('array');
		$this->load->model('Dataset_model');

		try {
			$input    = $this->raw_json_input();
			$old_idno = array_get_value($input, 'old_idno');
			$new_idno = array_get_value($input, 'new_idno');

			if (empty($old_idno) || empty($new_idno)) {
				throw new Exception('OLD_IDNO and NEW_IDNO parameters not set');
			}

			$sid = $this->Dataset_model->get_id_by_idno($old_idno);

			if (! $sid) {
				throw new Exception('OLD_IDNO was not found');
			}

			if ($new_sid = $this->Dataset_model->get_id_by_idno($new_idno)) {
				throw new Exception('NEW_IDNO already in use: ' . $new_sid);
			}

			$this->has_dataset_access('edit', $sid);

			$this->Dataset_model->update_options($sid, array('idno' => $new_idno));

			$this->events->emit('db.after.update', 'surveys', $sid, 'refresh');

			$this->set_response(
				array(
					'status'   => 'success',
					'new_idno' => $new_idno,
					'id'       => $sid,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Export study package as ZIP (same behaviour as GET api/datasets/{idno}/package).
	 *
	 * GET /api/admin/catalog/{idno}/package — optional query `dsd_export=inline|reference` (default reference).
	 * Query `id_format=id` when `{idno}` is numeric surveys.id.
	 */
	function package_get($idno = null)
	{
		try {
			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$this->load->library('Package_Exporter');

			$dsd_export = strtolower(trim((string) $this->input->get('dsd_export'))) === 'inline'
				? 'inline'
				: 'reference';

			$temp_zip = $this->package_exporter->export($sid, null, $dsd_export);

			if (! file_exists($temp_zip)) {
				throw new Exception('FAILED_TO_CREATE_PACKAGE');
			}

			$this->load->model('Dataset_model');
			$dataset  = $this->Dataset_model->get_row($sid);
			$filename = $dataset['idno'] . '-package.zip';

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($temp_zip));
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: 0');

			readfile($temp_zip);
			@unlink($temp_zip);
			exit;
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Set or clear study DOI (same core behaviour as POST api/datasets/{idno}/doi; this admin route also allows clear).
	 *
	 * POST /api/admin/catalog/{idno}/doi — JSON `{ "doi": "10.x/..." }` to set; `"doi": null` or `"doi": ""` to unset.
	 */
	function doi_post($idno = null)
	{
		$this->load->model('Dataset_model');

		try {
			$input = $this->raw_json_input();
			$sid   = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			if (! array_key_exists('doi', $input)) {
				throw new Exception('DOI_KEY_REQUIRED');
			}

			$doi = $input['doi'];
			if ($doi === null || $doi === '') {
				$this->Dataset_model->assign_doi($sid, '');
			} else {
				$this->Dataset_model->assign_doi($sid, (string) $doi);
			}

			$this->events->emit('db.after.update', 'surveys', $sid, 'atomic');

			$this->set_response(
				array(
					'status' => 'success',
					'doi'    => ($doi === null || $doi === '') ? '' : (string) $doi,
					'id'     => $sid,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Replace study DDI from uploaded XML (same behaviour as POST api/datasets/{idno}/replace_ddi).
	 *
	 * POST /api/admin/catalog/{idno}/replace_ddi — multipart field `file` (.xml).
	 * Query `id_format=id` when `{idno}` is numeric surveys.id.
	 */
	function replace_ddi_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$temp_upload_folder = get_catalog_root() . '/tmp';
			if (! file_exists($temp_upload_folder)) {
				@mkdir($temp_upload_folder);
			}
			if (! file_exists($temp_upload_folder)) {
				throw new Exception('DATAFILES-TEMP-FOLDER-NOT-SET');
			}

			if (empty($_FILES['file'])) {
				throw new Exception('No file was uploaded');
			}

			$this->load->library('Catalog_admin');
			$uploaded_ddi_path = $this->_process_ddi_xml_upload($temp_upload_folder);
			$ddi_path          = $this->catalog_admin->replace_ddi($sid, $uploaded_ddi_path);

			$survey = $this->dataset_manager->get_row($sid);
			if (! $survey) {
				throw new Exception('Survey was not found');
			}

			$this->load->library('DDI2_import');
			$params = array(
				'file_type'    => 'survey',
				'file_path'    => $ddi_path,
				'repositoryid' => $survey['repositoryid'],
				'published'    => $survey['published'],
				'user_id'      => $this->get_api_user_id(),
				'formid'       => $survey['formid'],
				'link_da'      => $survey['remote_data_url'],
				'overwrite'    => 'yes',
			);

			$this->ddi2_import->import($params);

			$this->set_response(
				array(
					'status' => 'success',
					'result' => $survey,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Download DDI XML (same behaviour as GET api/catalog/{idno}/ddi).
	 *
	 * GET /api/admin/catalog/{idno}/export_ddi
	 */
	function export_ddi_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$this->load->model('Dataset_model');
			$dataset = $this->Dataset_model->get_row($sid);

			if (! $dataset) {
				throw new Exception('IDNO_NOT_FOUND');
			}

			if ($dataset['type'] != 'survey') {
				throw new Exception('DDI is only available for Survey/MICRODATA types');
			}

			$this->Dataset_model->download_metadata_ddi($sid);
			die();
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status' => 'failed',
					'errors' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Re-import DDI from disk (same behaviour as POST batch_refresh).
	 *
	 * POST /api/admin/catalog/{idno}/refresh_ddi
	 */
	function refresh_ddi_post($idno = null)
	{
		return $this->batch_refresh_post($idno);
	}


	/**
	 * Write survey DDI from DB to disk (same behaviour as POST batch_generate_ddi / GET api/datasets/generate_ddi).
	 *
	 * POST /api/admin/catalog/{idno}/generate_ddi
	 */
	function generate_ddi_post($idno = null)
	{
		return $this->batch_generate_ddi_post($idno);
	}


	/**
	 * Parse and validate the DDI file on disk for a survey without saving anything.
	 *
	 * GET /api/admin/catalog/{idno}/validate_ddi
	 * Query `id_format=id` when `{idno}` is numeric surveys.id.
	 *
	 * Response shape (always HTTP 200):
	 *   { status: "success"|"failed", idno, message, errors[] }
	 * Returns HTTP 400 only for infrastructure errors (file missing, wrong type, etc.).
	 */
	function validate_ddi_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$this->load->model('Dataset_model');
			$dataset = $this->Dataset_model->get_row($sid);
			if (! $dataset) {
				throw new Exception('IDNO_NOT_FOUND');
			}
			if ($dataset['type'] !== 'survey') {
				throw new Exception('NOT_A_SURVEY_DATASET');
			}

			$file_path = $this->Dataset_model->get_metadata_file_path($sid);
			if (! $file_path || ! file_exists($file_path)) {
				throw new Exception('DDI_FILE_NOT_FOUND');
			}

			$this->load->library('Metadata_parser', ['file_type' => 'survey', 'file_path' => $file_path]);
			$parser = $this->metadata_parser->get_reader();

			$mapper = new Nada\DdiParser\Mapping\NadaSurveyMapper(
				$this->config->item('survey', 'metadata_parser', TRUE)
			);
			$mapped = $mapper->map($parser->get_study_meta());

			$this->load->library('Schema_validator');
			$this->schema_validator->validate_schema(APPPATH . 'schemas/survey-schema.json', $mapped);

			$this->set_response(
				array('status' => 'success', 'idno' => $idno, 'message' => 'DDI is valid'),
				REST_Controller::HTTP_OK
			);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'idno'    => $idno,
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Transfer study ownership to another collection (same core action as POST api/datasets/{idno}/owner_collection).
	 *
	 * POST /api/admin/catalog/{idno}/transfer_ownership
	 * Body (JSON or form): `collection_idno` **or** `repositoryid` — target owner repository id (`central` allowed).
	 * Query `id_format=id` when `{idno}` is numeric surveys.id.
	 */
	function transfer_ownership_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$options = $this->input->post(null, true);
			if (empty($options)) {
				$options = $this->raw_json_input();
			}
			if (! is_array($options)) {
				$options = array();
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$repositoryid = null;
			if (isset($options['collection_idno'])) {
				$repositoryid = $options['collection_idno'];
			} elseif (isset($options['repositoryid'])) {
				$repositoryid = $options['repositoryid'];
			}

			if ($repositoryid === null || $repositoryid === '') {
				throw new Exception('PARAM_MISSING: collection_idno or repositoryid');
			}

			if ($repositoryid === 'central') {
				$exists = true;
			} else {
				$exists = $this->Catalog_model->repository_exists($repositoryid);
			}

			if (! $exists) {
				throw new Exception(t('COLLECTION_NOT_FOUND'));
			}

			$this->Catalog_model->transfer_ownership($repositoryid, $sid);
			$this->events->emit('db.after.update', 'surveys', $sid);

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => t('msg_study_ownership_has_changed'),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * @param string $temp_upload_folder
	 * @return string full path to uploaded file
	 */
	private function _process_ddi_xml_upload($temp_upload_folder)
	{
		$config['upload_path']   = $temp_upload_folder;
		$config['overwrite']     = false;
		$config['encrypt_name']  = true;
		$config['allowed_types'] = 'xml';

		$this->load->library('upload', $config);

		$ddi_upload_result = $this->upload->do_upload('file');

		if (! $ddi_upload_result) {
			$error = $this->upload->display_errors('', '');
			$this->db_logger->write_log('ddi-upload', $error, 'catalog');
			throw new Exception($error);
		}

		$uploaded_ddi_path = $this->upload->data();

		return $uploaded_ddi_path['full_path'];
	}


	/**
	 * Paginated admin catalog search (list branch of index_get).
	 */
	private function _admin_catalog_search_list_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$page_size = $this->_get_page_size();
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			// Collect all search parameters from GET (includes owner_repo when sent)
			$search_options = array();
			foreach ($_GET as $key => $value) {
				$search_options[$key] = $this->input->get($key, TRUE);
			}

			$this->_prepare_admin_catalog_acl($user, $search_options);

			$owner_repo = $this->_get_owner_repo();

			// Owner filter: Catalog_admin_search reads options['owner_repo'] from $search_options (no set_active_repo)
			$rows = $this->Catalog_admin_search->search($search_options, $page_size, $offset);
			$total = $this->Catalog_admin_search->get_search_count();

			if (!is_array($rows)) {
				$rows = array();
			}

			$rows = $this->_enrich_admin_catalog_rows($rows);

			$response = array(
				'status' => 'success',
				'owner_repo' => $owner_repo,
				'result' => array(
					'rows'      => $rows,
					'total'     => $total,
					'page'      => $page,
					'pages'     => $page_size > 0 ? (int) ceil($total / $page_size) : 1,
					'page_size' => $page_size,
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * @param array $rows surveys rows from Catalog_admin_search
	 * @return array
	 */
	private function _enrich_admin_catalog_rows(array $rows)
	{
		if (count($rows) < 1) {
			return $rows;
		}

		$sid_list = array_column($rows, 'id');

		$survey_repos = (array) $this->Repository_model->get_survey_repositories($sid_list);
		$pending_lic  = (array) $this->Licensed_model->get_pending_requests_count($sid_list);
		$survey_tags  = (array) $this->Catalog_model->get_tags_by_survey($sid_list);
		$citations    = (array) $this->Citation_model->get_citations_count_by_survey_list($sid_list);

		foreach ($rows as $key => $row) {
			$sid = $row['id'];
			$rows[$key]['repositories']         = isset($survey_repos[$sid]) ? $survey_repos[$sid] : array();
			$rows[$key]['pending_lic_requests'] = isset($pending_lic[$sid])  ? (int) $pending_lic[$sid]  : 0;
			$rows[$key]['tags']                 = isset($survey_tags[$sid])  ? $survey_tags[$sid]  : array();
			$rows[$key]['citations']            = isset($citations[$sid])    ? (int) $citations[$sid]    : 0;
		}

		return $rows;
	}


	/**
	 * Collections the current user may import a DDI into (admin catalog scope + study.create on repo).
	 *
	 * GET /api/admin/catalog/ddi_upload_collections
	 */
	function ddi_upload_collections_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$this->lang->load('general');

			$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
			$output = array();

			if ($this->_repositoryid_allowed_for_ddi_upload($user, $scope, 'central')
				&& $this->_user_may_create_study_in_repo($user, 'central')) {
				$central = $this->Repository_model->get_central_catalog_array();
				$output[] = array(
					'repositoryid' => $central['repositoryid'],
					'title'        => $central['title'],
				);
			}

			$repos = $this->Repository_model->select_all(null, false);
			$by_title = array();
			foreach ($repos as $row) {
				$rid = isset($row['repositoryid']) ? $row['repositoryid'] : '';
				if ($rid === '') {
					continue;
				}
				if (! $this->_repositoryid_allowed_for_ddi_upload($user, $scope, $rid)) {
					continue;
				}
				if (! $this->_user_may_create_study_in_repo($user, $rid)) {
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

			$this->set_response(
				array(
					'status'       => 'success',
					'collections'  => $output,
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
	 * Import a study from an uploaded metadata file (auto-detect: DDI XML, ISO 19139-style geospatial XML, or dataset JSON).
	 *
	 * POST /api/admin/catalog/import
	 *
	 * Multipart form fields:
	 *   file           (required) .xml, .json, .jsonl, or study package .zip
	 *   repositoryid   (required) target collection
	 *   overwrite      optional yes|no (default no)
	 *   published      optional 0|1 — only applied when sent
	 *   access_policy  optional form/model code — only sets formid when sent and resolves
	 *   data_remote_url optional link_da — only applied when non-empty
	 *   rdf            optional second file (.rdf) when import resolves to survey/microdata XML
	 */
	function import_post()
	{
		$saved_path       = null;
		$package_importer = null;
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
			$repositoryid = $this->input->post('repositoryid');
			if ($repositoryid === null || $repositoryid === '') {
				$repositoryid = 'central';
			}

			if (! $this->_repositoryid_allowed_for_ddi_upload($user, $scope, $repositoryid)) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ( ! $this->_user_has_study_acl_on_repository($user, $repositoryid, 'create')) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$this->load->helper('file');
			$temp_base = get_catalog_root() . '/tmp';
			if (! file_exists($temp_base)) {
				@mkdir($temp_base);
			}
			if (! file_exists($temp_base)) {
				throw new Exception('DATAFILES-TEMP-FOLDER-NOT-SET');
			}

			$saved_path = $this->_save_catalog_import_upload($temp_base);
			$kind       = $this->_detect_catalog_import_kind($saved_path);

			$overwrite_yes = ($this->input->post('overwrite') === 'yes');
			$published_raw = $this->input->post('published');
			$published_post = null;
			if (in_array($published_raw, array(0, 1, '0', '1'), true)) {
				$published_post = (int) $published_raw;
			}

			$result        = null;
			$detected_type = $kind;

			if ($kind === 'survey_xml') {
				$this->load->library('DDI2_import');
				$params = array(
					'file_path'     => $saved_path,
					'repositoryid'  => $repositoryid,
					'user_id'       => $this->get_api_user_id(),
					'overwrite'     => $overwrite_yes,
				);
				if ($published_post !== null) {
					$params['published'] = $published_post;
				}
				$access_policy = $this->input->post('access_policy');
				if ($access_policy !== null && trim((string) $access_policy) !== '') {
					$fid = $this->dataset_manager->get_data_access_type_id($access_policy);
					if ($fid) {
						$params['formid'] = $fid;
					}
				}
				$data_remote_url = $this->input->post('data_remote_url');
				if ($data_remote_url !== null && trim((string) $data_remote_url) !== '') {
					$params['link_da'] = $data_remote_url;
				}
				$result = $this->ddi2_import->import($params);

				$rdf_payload = array();
				$this->load->model('Survey_resource_model');
				if (! empty($_FILES['rdf']['name'])) {
					$rdf_payload = $this->Survey_resource_model->import_uploaded_rdf($result['sid'], $temp_base, 'rdf');
				}

				$this->events->emit('db.after.update', 'surveys', $result['sid'], 'import');
				$response = array(
					'status'         => 'success',
					'detected_type'  => $detected_type,
					'dataset'        => $result,
					'survey'         => $result,
					'sid'            => (int) $result['sid'],
					'idno'           => isset($result['idno']) ? $result['idno'] : null,
					'rdf'            => $rdf_payload,
				);
			}
			elseif ($kind === 'geospatial_xml') {
				$this->load->library('Geospatial_import');
				$options = array(
					'created_by'    => $this->get_api_user_id(),
					'changed_by'    => $this->get_api_user_id(),
					'created'       => date('U'),
					'changed'       => date('U'),
					'overwrite'     => $overwrite_yes ? 'yes' : 'no',
					'repositoryid'  => $repositoryid,
				);
				if ($published_post !== null) {
					$options['published'] = $published_post;
				}
				$result = $this->geospatial_import->import($saved_path, $options);
				$sid = isset($result['id']) ? (int) $result['id'] : null;
				if ($sid) {
					$this->events->emit('db.after.update', 'surveys', $sid, 'import');
				}
				$response = array(
					'status'        => 'success',
					'detected_type' => $detected_type,
					'dataset'       => $result,
					'sid'           => $sid,
					'idno'          => isset($result['idno']) ? $result['idno'] : null,
				);
			}
			elseif ($kind === 'dataset_json' || $kind === 'dataset_jsonl') {
				$this->load->library('Catalog_dataset_import');
				if ($kind === 'dataset_jsonl') {
					$this->load->library('JSON_Reader');
					$options = $this->json_reader->parse_jsonl_file($saved_path);
				}
				else {
					$options = json_decode(file_get_contents($saved_path), true);
					if (! is_array($options)) {
						throw new Exception('INVALID_JSON');
					}
				}
				$imported = $this->catalog_dataset_import->create_from_options(
					$options,
					$this->_catalog_import_params_from_request($repositoryid, $overwrite_yes, $published_post)
				);
				$response = array(
					'status'        => 'success',
					'detected_type' => $detected_type,
					'dataset'       => $imported['dataset'],
					'sid'           => $imported['sid'],
					'idno'          => $imported['idno'],
					'study_type'    => $imported['type'],
				);
			}
			elseif ($kind === 'package_zip') {
				$this->load->library('Package_Importer');
				$package_importer = $this->package_importer;
				$imported         = $this->package_importer->import(
					$saved_path,
					$this->_catalog_import_params_from_request($repositoryid, $overwrite_yes, $published_post)
				);
				$response = array(
					'status'        => 'success',
					'detected_type' => $detected_type,
					'dataset'       => $imported['dataset'],
					'sid'           => $imported['sid'],
					'idno'          => $imported['idno'],
					'study_type'    => $imported['type'],
					'package_stats' => $imported['package_stats'],
				);
			}
			else {
				throw new Exception('UNSUPPORTED_IMPORT_FORMAT');
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
		finally {
			if ($package_importer) {
				$package_importer->cleanup();
			}
			if ($saved_path && is_string($saved_path) && is_file($saved_path)) {
				@unlink($saved_path);
			}
		}
	}


	/**
	 * Build shared import params from the multipart import request.
	 *
	 * @param string   $repositoryid
	 * @param bool     $overwrite_yes
	 * @param int|null $published_post
	 * @return array
	 */
	private function _catalog_import_params_from_request($repositoryid, $overwrite_yes, $published_post)
	{
		$params = array(
			'repositoryid' => $repositoryid,
			'overwrite'    => $overwrite_yes,
			'published'    => $published_post,
			'user_id'      => $this->get_api_user_id(),
		);

		$data_remote_url = $this->input->post('data_remote_url');
		if ($data_remote_url !== null && trim((string) $data_remote_url) !== '') {
			$params['link_da'] = $data_remote_url;
		}

		$form_post = $this->input->post('access_policy');
		if ($form_post !== null && trim((string) $form_post) !== '') {
			$fid = $this->dataset_manager->get_data_access_type_id($form_post);
			if ($fid) {
				$params['formid'] = $fid;
			}
		}

		return $params;
	}


	/**
	 * Staged package import — poll progress (resume after page reload).
	 *
	 * GET /api/admin/catalog/import_package/status?upload_id={uuid}
	 */
	function import_package_status_get()
	{
		try {
			$user = $this->_import_package_user_or_die();
			$upload_id = $this->_import_package_upload_id_from_request(true);
			$this->load->library('Package_import_staged');
			$response = $this->package_import_staged->get_status($upload_id, (int) $this->get_api_user_id());
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Staged package import — extract uploaded ZIP.
	 *
	 * POST /api/admin/catalog/import_package/unzip  JSON: { upload_id }
	 */
	function import_package_unzip_post()
	{
		try {
			$user = $this->_import_package_user_or_die();
			$upload_id = $this->_import_package_upload_id_from_request(true);
			$this->load->library('Package_import_staged');
			$response = $this->package_import_staged->unzip($upload_id, (int) $this->get_api_user_id());
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Staged package import — create study (metadata only, no variables).
	 *
	 * POST /api/admin/catalog/import_package/create
	 * JSON: upload_id, repositoryid, overwrite, published?, access_policy?, data_remote_url?
	 */
	function import_package_create_post()
	{
		try {
			$user      = $this->_import_package_user_or_die();
			$payload   = $this->_import_package_json_payload();
			$upload_id = $this->_import_package_upload_id_from_request(true, $payload);

			$repositoryid = isset($payload['repositoryid']) ? trim((string) $payload['repositoryid']) : '';
			if ($repositoryid === '') {
				$repositoryid = 'central';
			}
			$this->_import_package_assert_repository_acl($user, $repositoryid);

			$overwrite = false;
			if (isset($payload['overwrite'])) {
				$ow = $payload['overwrite'];
				$overwrite = ($ow === true || $ow === 1 || $ow === '1' || $ow === 'yes');
			}

			$published_post = null;
			if (isset($payload['published']) && in_array($payload['published'], array(0, 1, '0', '1'), true)) {
				$published_post = (int) $payload['published'];
			}

			$params = array(
				'repositoryid' => $repositoryid,
				'overwrite'    => $overwrite,
				'published'    => $published_post,
				'user_id'      => $this->get_api_user_id(),
			);

			if (isset($payload['access_policy']) && trim((string) $payload['access_policy']) !== '') {
				$fid = $this->dataset_manager->get_data_access_type_id($payload['access_policy']);
				if ($fid) {
					$params['formid'] = $fid;
				}
			}
			if (isset($payload['data_remote_url']) && trim((string) $payload['data_remote_url']) !== '') {
				$params['link_da'] = $payload['data_remote_url'];
			}

			$this->load->library('Package_import_staged');
			$response = $this->package_import_staged->create_study(
				$upload_id,
				(int) $this->get_api_user_id(),
				$params
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Staged package import — next data file and all its variables.
	 *
	 * POST /api/admin/catalog/import_package/datafile  JSON: { upload_id }
	 */
	function import_package_datafile_post()
	{
		try {
			$user = $this->_import_package_user_or_die();
			$upload_id = $this->_import_package_upload_id_from_request(true);
			$this->load->library('Package_import_staged');
			$response = $this->package_import_staged->import_datafile($upload_id, (int) $this->get_api_user_id());
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (ValidationException $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
					'errors'  => $e->GetValidationErrors(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Staged package import — assets, search index, cleanup.
	 *
	 * POST /api/admin/catalog/import_package/finalize  JSON: { upload_id }
	 */
	function import_package_finalize_post()
	{
		try {
			$user = $this->_import_package_user_or_die();
			$upload_id = $this->_import_package_upload_id_from_request(true);
			$this->load->library('Package_import_staged');
			$response = $this->package_import_staged->finalize($upload_id, (int) $this->get_api_user_id());
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * @return object
	 */
	private function _import_package_user_or_die()
	{
		$user = $this->api_user();
		if (! $user) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		return $user;
	}


	/**
	 * @param object $user
	 * @param string $repositoryid
	 * @return void
	 */
	private function _import_package_assert_repository_acl($user, $repositoryid)
	{
		$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
		if (! $this->_repositoryid_allowed_for_ddi_upload($user, $scope, $repositoryid)) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		if (! $this->_user_has_study_acl_on_repository($user, $repositoryid, 'create')) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
	}


	/**
	 * @param bool       $required
	 * @param array|null $payload
	 * @return string
	 */
	private function _import_package_upload_id_from_request($required = true, $payload = null)
	{
		if ($payload === null) {
			$payload = $this->_import_package_json_payload();
		}
		$upload_id = '';
		if (isset($payload['upload_id'])) {
			$upload_id = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $payload['upload_id']);
		}
		if ($upload_id === '' && $this->input->get('upload_id')) {
			$upload_id = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $this->input->get('upload_id'));
		}
		if ($upload_id === '' && $required) {
			throw new Exception('UPLOAD_ID_REQUIRED');
		}
		return $upload_id;
	}


	/**
	 * @return array
	 */
	private function _import_package_json_payload()
	{
		$raw = $this->input->raw_input_stream;
		if ($raw !== null && trim($raw) !== '') {
			$decoded = json_decode(trim($raw), true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$post = $this->input->post(null, true);
		return is_array($post) ? $post : array();
	}


	/**
	 * Compact study rows for batch refresh / batch DDI generate (ACL + filters same as search).
	 *
	 * GET /api/admin/catalog/batch_studies
	 *
	 * Query:
	 *   owner_repo     optional — same as search (empty/central = no owner filter)
	 *   dataset_types  optional — comma list; default survey
	 *   ps             page size 1–2000 (default 500)
	 *   page           page number (default 1)
	 */
	function batch_studies_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$search_options = array();
			foreach ($_GET as $key => $value) {
				$search_options[$key] = $this->input->get($key, TRUE);
			}

			$rid = $this->_batch_repositoryid_for_acl($search_options);
			if (! $this->_repositoryid_in_admin_catalog_scope($user, $rid)) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ( ! $this->_user_has_study_acl_on_repository($user, $rid, 'edit')) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			if (empty($search_options['dataset_types'])) {
				$search_options['dataset_types'] = 'survey';
			}

			$this->_prepare_admin_catalog_acl($user, $search_options);

			$page_size = $this->_get_batch_list_page_size(500, 2000);
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			$rows  = $this->Catalog_admin_search->search($search_options, $page_size, $offset);
			$total = $this->Catalog_admin_search->get_search_count();

			$compact = array();
			foreach ((array) $rows as $row) {
				$compact[] = array(
					'id'           => isset($row['id']) ? (int) $row['id'] : 0,
					'idno'         => isset($row['idno']) ? $row['idno'] : '',
					'title'        => isset($row['title']) ? $row['title'] : '',
					'nation'       => isset($row['nation']) ? $row['nation'] : '',
					'repositoryid' => isset($row['repositoryid']) ? $row['repositoryid'] : '',
				);
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'rows'      => $compact,
						'total'     => (int) $total,
						'page'      => $page,
						'page_size' => $page_size,
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
	 * DDI XML files in the server import folder (paths as base64 ids for POST batch_import).
	 *
	 * GET /api/admin/catalog/batch_import_files
	 *
	 * Query:
	 *   repositoryid  ACL context (default central)
	 */
	function batch_import_files_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$rid = trim((string) $this->input->get('repositoryid'));
			if ($rid === '') {
				$rid = 'central';
			}
			if (! $this->_repositoryid_in_admin_catalog_scope($user, $rid)) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ( ! $this->_user_has_study_acl_on_repository($user, $rid, 'edit')) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$this->load->helper('file');
			$import_folder = $this->config->item('ddi_import_folder');
			if (! $import_folder || ! is_dir($import_folder)) {
				$import_folder = '/datasets';
			}
			$root = realpath($import_folder);
			if ($root === false || ! is_dir($root)) {
				throw new Exception('IMPORT_FOLDER_NOT_AVAILABLE');
			}

			$files_out = array();
			$raw       = get_dir_file_info($root);
			if (is_array($raw)) {
				foreach ($raw as $info) {
					if (empty($info['name']) || strcasecmp(substr($info['name'], -4), '.xml') !== 0) {
						continue;
					}
					$full = realpath($root . DIRECTORY_SEPARATOR . $info['name']);
					if ($full === false) {
						continue;
					}
					if ($full !== $root && strpos($full, $root . DIRECTORY_SEPARATOR) !== 0) {
						continue;
					}
					$files_out[] = array(
						'name' => $info['name'],
						'id'   => base64_encode($full),
					);
				}
			}

			usort(
				$files_out,
				function ($a, $b) {
					return strcasecmp($a['name'], $b['name']);
				}
			);

			$collections = array();
			$scope       = $this->acl_manager->get_admin_catalog_repository_scope($user);
			if ($this->_repositoryid_allowed_for_ddi_upload($user, $scope, 'central')
				&& $this->_user_may_create_study_in_repo($user, 'central')) {
				$central       = $this->Repository_model->get_central_catalog_array();
				$collections[] = array(
					'repositoryid' => $central['repositoryid'],
					'title'        => $central['title'],
				);
			}
			$repos = $this->Repository_model->select_all(null, false);
			$by    = array();
			foreach ((array) $repos as $row) {
				$prid = isset($row['repositoryid']) ? $row['repositoryid'] : '';
				if ($prid === '') {
					continue;
				}
				if (! $this->_repositoryid_allowed_for_ddi_upload($user, $scope, $prid)) {
					continue;
				}
				if (! $this->_user_may_create_study_in_repo($user, $prid)) {
					continue;
				}
				$by[$row['title'] . "\0" . $prid] = array(
					'repositoryid' => $prid,
					'title'        => $row['title'],
				);
			}
			ksort($by, SORT_NATURAL);
			foreach ($by as $c) {
				$collections[] = $c;
			}

			$this->set_response(
				array(
					'status'      => 'success',
					'import_root' => $root,
					'files'       => $files_out,
					'collections' => $collections,
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
	 * Batch-import one DDI XML from the server import folder.
	 *
	 * POST /api/admin/catalog/batch_import
	 *
	 * JSON or form body: id (base64 absolute path), repositoryid, overwrite (boolean or 0/1).
	 */
	function batch_import_post()
	{
		$this->lang->load('catalog_admin');

		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('error' => t('REPO_ACCESS_DENIED')), REST_Controller::HTTP_OK);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('error' => t('REPO_ACCESS_DENIED')), REST_Controller::HTTP_OK);
				return;
			}

			$payload = null;
			$raw = $this->input->raw_input_stream;
			if ($raw !== null && trim($raw) !== '') {
				$decoded = json_decode(trim($raw), true);
				if (is_array($decoded)) {
					$payload = $decoded;
				}
			}

			$encoded_filepath = '';
			$repositoryid = 'central';
			$overwrite = false;

			if (is_array($payload) && isset($payload['id'])) {
				$encoded_filepath = (string) $payload['id'];
				if (isset($payload['repositoryid'])) {
					$repositoryid = trim((string) $payload['repositoryid']);
				}
				if ($repositoryid === '') {
					$repositoryid = 'central';
				}
				if (isset($payload['overwrite'])) {
					$ow = $payload['overwrite'];
					$overwrite = ($ow === true || $ow === 1 || $ow === '1' || $ow === 'yes');
				}
			}
			else {
				$encoded_filepath = (string) $this->input->post('id');
				$repositoryid = trim((string) $this->input->post('repositoryid'));
				if ($repositoryid === '') {
					$repositoryid = 'central';
				}
				$overwrite = (bool) $this->input->post('overwrite');
			}

			if ($encoded_filepath === '') {
				$this->set_response(array('error' => t('file_not_found')), REST_Controller::HTTP_OK);
				return;
			}

			if (! $this->_repositoryid_in_admin_catalog_scope($user, $repositoryid)) {
				$this->set_response(array('error' => t('REPO_ACCESS_DENIED')), REST_Controller::HTTP_OK);
				return;
			}
			if ( ! $this->_user_has_study_acl_on_repository($user, $repositoryid, 'edit')) {
				$this->set_response(array('error' => t('REPO_ACCESS_DENIED')), REST_Controller::HTTP_OK);
				return;
			}

			try {
				$ddi_path = $this->_safe_batch_import_xml_path($encoded_filepath);
			}
			catch (Exception $e) {
				$this->set_response(array('error' => $e->getMessage()), REST_Controller::HTTP_OK);
				return;
			}

			$this->load->library('DDI2_import');
			$params = array(
				'file_type'    => 'survey',
				'file_path'    => $ddi_path,
				'user_id'      => $this->get_api_user_id(),
				'repositoryid' => $repositoryid,
				'overwrite'    => $overwrite,
			);

			$result = $this->ddi2_import->import($params);
			$this->_import_rdf_for_batch($result['sid'], str_replace('.xml', '.rdf', $result['idno']));

			$msg = '<strong>' . $result['idno'] . '</strong> - <em>' . $result['varcount'] . ' ' . t('variables') . '</em>';
			log_message('info', $msg);

			$this->set_response(array('success' => $msg), REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error = print_r($e->GetValidationErrors(), true);
			$this->set_response(array('error' => $error), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->set_response(array('error' => print_r($e->getMessage(), true)), REST_Controller::HTTP_OK);
		}
	}


	/**
	 * Re-import DDI for an existing study (same behaviour as admin/catalog/refresh with ajax=1).
	 *
	 * POST /api/admin/catalog/batch_refresh/{sid}?id_format=id
	 */
	function batch_refresh_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('error' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('error' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->load->model('Dataset_model');
			$this->load->library('DDI2_import');

			$ddi_file = $this->Catalog_model->get_survey_ddi_path($sid);
			if ($ddi_file === false) {
				$this->set_response(array('error' => 'DDI_NOT_FOUND'), REST_Controller::HTTP_OK);
				return;
			}

			$dataset = $this->Dataset_model->get_row($sid);
			if (! $dataset) {
				$this->set_response(array('error' => 'NOT_FOUND'), REST_Controller::HTTP_OK);
				return;
			}

			if ( ! $this->_user_has_study_acl_on_repository($user, $dataset['repositoryid'], 'edit')) {
				$this->set_response(array('error' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
				return;
			}

			$params = array(
				'file_type'    => 'survey',
				'file_path'    => $ddi_file,
				'user_id'      => $this->get_api_user_id(),
				'repositoryid' => $dataset['repositoryid'],
				'overwrite'    => 'yes',
			);

			$this->ddi2_import->import($params, $sid);

			$update_options = array(
				'changed'      => $dataset['changed'],
				'created'      => $dataset['created'],
				'repositoryid' => $dataset['repositoryid'],
			);
			$this->Dataset_model->update_options($sid, $update_options);
			$this->events->emit('db.after.update', 'surveys', $sid, 'refresh');

			$this->set_response(array('success' => 'UPDATED: ' . $sid), REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_str = 'Validation Error<br/><pre class="error-pre">' . print_r($e->GetValidationErrors(), true) . '</pre>';
			$this->set_response(array('error' => $error_str), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->set_response(array('error' => $e->getMessage()), REST_Controller::HTTP_OK);
		}
	}


	/**
	 * Write survey DDI to disk (same core action as api/datasets/generate_ddi).
	 *
	 * POST /api/admin/catalog/batch_generate_ddi/{sid}?id_format=id
	 */
	function batch_generate_ddi_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
				return;
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
				return;
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);
			$this->load->model('Dataset_model');
			$this->Dataset_model->write_ddi($sid, true);

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_OK);
		}
	}


	/**
	 * All collections for the study "Display in other collections" picker (minimal fields).
	 *
	 * GET /api/admin/catalog/list_collections
	 *
	 * Gate: authenticated + user_has_any_admin_capability() (includes collection-only admins with repositories_acl grants).
	 * Does not filter rows by catalog ACL; study edit on POST …/collections controls who may save links.
	 */
	function list_collections_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if (!$this->acl_manager->user_has_any_admin_capability($user)) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$repos = $this->Repository_model->select_all(null, false);
			$collections = array();

			foreach ($repos as $row) {
				$rid = isset($row['repositoryid']) ? (string) $row['repositoryid'] : '';
				if ($rid === '') {
					continue;
				}
				$collections[] = array(
					'id'             => isset($row['id']) ? (int) $row['id'] : null,
					'repositoryid'   => $rid,
					'title'          => isset($row['title']) ? (string) $row['title'] : $rid,
				);
			}

			usort($collections, function ($a, $b) {
				return strcasecmp($a['title'], $b['title']);
			});

			$this->set_response(
				array(
					'status'      => 'success',
					'collections' => $collections,
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
	 * Return standardized filter option values for the admin catalog sidebar
	 *
	 * GET /api/admin/catalog/filter_options
	 *
	 * Optional query param:
	 *   owner_repo   string   if set (and not central), narrows facet counts to studies owned by this repositoryid;
	 *                          must be allowed when the user's catalog scope is restricted.
	 *
	 * Returns consistent structure for all filters:
	 *   {id, name, count} for display/selection
	 *   {id, code, name, count} for dataset types
	 */
	function filter_options_get()
	{
		try {
			$user = $this->api_user();
			if (!$user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$search_options = array();
			foreach ($_GET as $key => $value) {
				$search_options[$key] = $this->input->get($key, TRUE);
			}
			$this->_prepare_admin_catalog_acl($user, $search_options);

			$owner_repo = $this->_get_owner_repo();
			$repo_id    = $owner_repo;

			// Facet counts: get_filter_options($repo_id) scopes via _apply_surveys_acl_and_owner_scope (owner_repo in GET only)
			$filters = $this->Catalog_admin_search->get_filter_options($repo_id);

			$response = array(
				'status'        => 'success',
				'countries'     => $filters['countries']     ?: array(),
				'tags'          => $filters['tags']          ?: array(),
				'collections'   => $filters['collections']   ?: array(),
				'data_access'   => $filters['data_access']   ?: array(),
				'dataset_types' => $filters['dataset_types'] ?: array(),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Update study options
	 * 
	 * @idno - study IDNO
	 *
	 * JSON body may include `featured` (bool|0|1) to set or clear the study as featured for its owner repository (`featured_surveys`).
	 * JSON body may include `doi` — string sets DOI; `null` or `""` clears it (uses `Dataset_model::assign_doi`).
	 * 
	 * note: replaces datasets/index_put method
	 * 
	 * 
	 * 
	 */
	function options_post($idno=null)
	{
		$this->load->helper("array");
		$this->load->model('Dataset_model');

		try{
			$input=$this->raw_json_input();
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit',$sid);

			$options=array(				
				'repositoryid'			=> array_get_value($input,'owner_collection'),
				'formid'				=> array_get_value($input,'access_policy'),
				'link_da'				=> array_get_value($input,'data_remote_url'),
				'published'				=> array_get_value($input,'published'),
				'link_study'			=> array_get_value($input,'link_study'),
				'link_indicator'		=> array_get_value($input,'link_indicator'),
				'thumbnail'				=> array_get_value($input,'thumbnail'),
				'tags'					=> array_get_value($input,'tags'),
				'aliases'				=> array_get_value($input,'aliases')				
			);

			if(!empty($options['formid'])){
				$options['formid']=$this->dataset_manager->get_data_access_type_id($options['formid']);
			}

			if (isset($input['data_classification'])){
				$options['data_class_id']=$this->dataset_manager->get_data_classification_id($input['data_classification']);
			}

			//remove options not set
			foreach($options as $key=>$value){
				if($value===false){
					unset($options[$key]);
				}
			}

			//linked collections
			$linked_collections=array_get_value($input,'linked_collections');

			if(is_array($linked_collections)){
				$collection_options=array(
					'study_idno'=>$idno,
					'link_collections'=>$linked_collections
				);

				$this->Repository_model->update_collection_studies($collection_options);
			}

			$featured_status = $this->featured_option_from_input($input);

			$doi_provided = array_key_exists('doi', $input);
			$linked_collections_provided = array_key_exists('linked_collections', $input) && is_array($input['linked_collections']);

			if (empty($options) && $featured_status === null && ! $doi_provided && ! $linked_collections_provided){
				throw new Exception("NO_PARAMS_PROVIDED");
			}

			if (!empty($options)){
				$this->dataset_manager->validate_options($options);
				$this->dataset_manager->update_options($sid,$options);
			}

			if ($featured_status !== null){
				$survey_row = $this->dataset_manager->get_row($sid);
				if (!$survey_row){
					throw new Exception("STUDY_NOT_FOUND");
				}
				$this->Repository_model->set_featured_study($survey_row['repositoryid'], $sid, $featured_status);
			}

			if ($doi_provided){
				$d = $input['doi'];
				if ($d === null || $d === ''){
					$this->Dataset_model->assign_doi($sid, '');
				}
				else{
					$this->Dataset_model->assign_doi($sid, (string) $d);
				}
			}

			if (!empty($options) || $featured_status !== null || $doi_provided){
				$this->events->emit('db.after.update', 'surveys', $sid,'atomic');
			}

			$response=array(
				'status'=>'success'				
			);


			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>"VALIDATION_ERRORS",
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		/*catch(Error $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}*/
	}


	/**
	 * 
	 *  Delete by IDNO
	 * 
	 */
	public function delete_post($idno=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('delete', $sid);
			$this->dataset_manager->delete($sid);
			$this->events->emit('db.after.delete', 'surveys', $sid);
		
			$response=array(
				'status'=>'success',
				'message'=>'DELETED'
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
	
	function delete_delete($idno=null)
	{
		return $this->delete_post($idno);
	}


	// ---------------------------------------------------------------
	// Private helpers
	// ---------------------------------------------------------------

	/**
	 * Resolve base64-encoded absolute path and ensure it is a .xml file under ddi_import_folder.
	 *
	 * @param string $encoded_filepath
	 * @return string absolute path
	 * @throws Exception
	 */
	private function _safe_batch_import_xml_path($encoded_filepath)
	{
		$this->lang->load('catalog_admin');
		$ddi_file = base64_decode($encoded_filepath);
		if (! is_string($ddi_file) || $ddi_file === '') {
			throw new Exception(t('file_not_found'));
		}

		$this->load->helper('file');
		$import_folder = $this->config->item('ddi_import_folder');
		if (! $import_folder || ! is_dir($import_folder)) {
			$import_folder = '/datasets';
		}
		$root = realpath($import_folder);
		if ($root === false || ! is_dir($root)) {
			throw new Exception('IMPORT_FOLDER_NOT_AVAILABLE');
		}
		$target = realpath($ddi_file);
		if ($target === false || ! is_file($target)) {
			throw new Exception(t('file_not_found'));
		}
		if ($target !== $root && strpos($target, $root . DIRECTORY_SEPARATOR) !== 0) {
			throw new Exception(t('file_not_found'));
		}
		if (strcasecmp(substr($target, -4), '.xml') !== 0) {
			throw new Exception(t('file_not_found'));
		}

		return $target;
	}


	/**
	 * Import RDF resources when a sibling .rdf file exists (same as former admin batch-import flow).
	 *
	 * @param int    $surveyid
	 * @param string $filepath
	 * @return bool
	 */
	private function _import_rdf_for_batch($surveyid, $filepath)
	{
		if (! file_exists($filepath)) {
			return false;
		}

		$rdf_contents = file_get_contents($filepath);
		$this->load->library('RDF_Parser');
		$this->load->model('Survey_resource_model');

		$rdf_array = $this->rdf_parser->parse($rdf_contents);
		if ($rdf_array === false || $rdf_array === null) {
			return false;
		}

		$rdf_fields = $this->rdf_parser->fields;
		foreach ($rdf_array as $rdf_rec) {
			$insert_data['survey_id'] = $surveyid;

			foreach ($rdf_fields as $key => $value) {
				if (isset($rdf_rec[$rdf_fields[$key]])) {
					$insert_data[$key] = trim($rdf_rec[$rdf_fields[$key]]);
				}
			}

			if (! is_url($insert_data['filename'])) {
				$insert_data['filename'] = unix_path($insert_data['filename']);
				if (substr($insert_data['filename'], 1, 1) == '/') {
					$insert_data['filename'] = substr($insert_data['filename'], 2, 255);
				}
			}

			$resource_exists = $this->Survey_resource_model->survey_resource_exists(
				$surveyid,
				$insert_data['title'],
				$insert_data['type'],
				$insert_data['filename']
			);

			if (! $resource_exists) {
				$this->Survey_resource_model->insert($insert_data);
			}
		}

		return true;
	}


	/**
	 * Same constraint as admin/Catalog::_repositoryid_allowed_by_catalog_scope for a given user.
	 *
	 * @param object        $user
	 * @param array|null|false $scope from get_admin_catalog_repository_scope
	 * @param string        $repositoryid
	 * @return bool
	 */
	private function _repositoryid_allowed_for_ddi_upload($user, $scope, $repositoryid)
	{
		unset($user);
		$rid = strtolower(trim((string) $repositoryid));
		if ($scope === false) {
			return false;
		}
		if ($scope === null) {
			return true;
		}
		return in_array($rid, array_map('strtolower', $scope), true);
	}


	/**
	 * @param object $user
	 * @param string $repositoryid
	 * @return bool
	 */
	private function _user_may_create_study_in_repo($user, $repositoryid)
	{
		return $this->_user_has_study_acl_on_repository($user, $repositoryid, 'create');
	}


	/**
	 * Study ACL on a repository via {@see Acl_manager::has_access} (Zend roles + collection ACL).
	 *
	 * @param object $user
	 * @param string $repositoryid
	 * @param string $study_privilege view|create|edit|...
	 * @return bool
	 */
	private function _user_has_study_acl_on_repository($user, $repositoryid, $study_privilege)
	{
		try {
			return $this->acl_manager->has_access('study', $study_privilege, $user, $repositoryid);
		}
		catch (Exception $e) {
			return false;
		}
	}


	/**
	 * Normalize a raw type string from import JSON (aliases, casing).
	 *
	 * @param mixed $raw
	 * @return string|null canonical type slug or null
	 */
	private function _normalize_catalog_import_type($raw)
	{
		if (! is_string($raw)) {
			return null;
		}
		$t = strtolower(trim($raw));
		if ($t === '') {
			return null;
		}
		$aliases = array(
			'microdata'      => 'survey',
			'timeseries-db'  => 'timeseriesdb',
		);
		if (isset($aliases[$t])) {
			return $aliases[$t];
		}
		return $t;
	}


	/**
	 * Infer dataset type from well-known metadata section keys.
	 *
	 * @param array $options
	 * @return string|null
	 */
	private function _infer_catalog_import_dataset_type(array $options)
	{
		$section_map = array(
			'database_description'       => 'timeseriesdb',
			'series_description'         => 'timeseries',
			'study_desc'                 => 'survey',
			'document_description'       => 'document',
			'project_desc'               => 'script',
			'table_description'          => 'table',
			'image_description'          => 'image',
			'video_description'          => 'video',
			'visualization_description'  => 'visualization',
		);
		foreach ($section_map as $section => $type) {
			if (! empty($options[$section]) && is_array($options[$section])) {
				return $type;
			}
		}
		if (! empty($options['description']) && is_array($options['description'])) {
			return 'geospatial';
		}
		return null;
	}


	/**
	 * Resolve dataset type for JSON catalog import.
	 *
	 * Accepts type, schema_type, schematype, datatype (export / JSONL variants),
	 * normalizes aliases, then infers from metadata sections, defaulting to survey.
	 *
	 * @param array $options decoded JSON (by reference not required)
	 * @return string
	 */
	private function _resolve_catalog_import_dataset_type(array $options)
	{
		$last_invalid = null;
		foreach (array('type', 'schema_type', 'schematype', 'datatype') as $key) {
			if (! isset($options[$key]) || ! is_string($options[$key]) || trim($options[$key]) === '') {
				continue;
			}
			$normalized = $this->_normalize_catalog_import_type($options[$key]);
			if ($normalized !== null && $this->dataset_manager->is_valid_type($normalized)) {
				return $normalized;
			}
			if ($normalized !== null) {
				$last_invalid = $normalized;
			}
		}

		$inferred = $this->_infer_catalog_import_dataset_type($options);
		if ($inferred !== null && $this->dataset_manager->is_valid_type($inferred)) {
			return $inferred;
		}

		if ($last_invalid !== null) {
			throw new Exception('INVALID_TYPE: ' . $last_invalid);
		}

		return 'survey';
	}


	/**
	 * Remove export-only top-level keys before create_dataset.
	 *
	 * @param array $options
	 * @return void
	 */
	private function _strip_catalog_import_export_fields(array &$options)
	{
		foreach (array('type', 'schema_type', 'schematype', 'datatype', 'id') as $key) {
			unset($options[$key]);
		}
	}


	/**
	 * Move multipart field "file" to a temp path; allow .xml, .json, .jsonl, .zip.
	 *
	 * @param string $temp_base
	 * @return string absolute path
	 */
	private function _save_catalog_import_upload($temp_base)
	{
		if (empty($_FILES['file']['tmp_name']) || ! is_uploaded_file($_FILES['file']['tmp_name'])) {
			throw new Exception('NO_FILE');
		}
		$name = isset($_FILES['file']['name']) ? $_FILES['file']['name'] : '';
		$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (! in_array($ext, array('xml', 'json', 'jsonl', 'zip'), true)) {
			throw new Exception('INVALID_FILE_EXTENSION');
		}
		$dest = $temp_base . '/' . md5(uniqid('', true)) . '.' . $ext;
		if (! @move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
			throw new Exception('FILE_UPLOAD_FAILED');
		}
		return $dest;
	}


	/**
	 * @param string $path
	 * @return string survey_xml|geospatial_xml|dataset_json|dataset_jsonl|package_zip
	 */
	private function _detect_catalog_import_kind($path)
	{
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($ext === 'zip') {
			return 'package_zip';
		}
		if ($ext === 'jsonl') {
			return 'dataset_jsonl';
		}

		$head = file_get_contents($path, false, null, 0, 65536);
		$trim = ltrim($head);
		if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
			$full = file_get_contents($path);
			$decoded = json_decode($full, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('INVALID_JSON');
			}
			if (! $this->_catalog_import_json_is_object($decoded)) {
				throw new Exception('INVALID_JSON_SHAPE');
			}
			return 'dataset_json';
		}

		libxml_use_internal_errors(true);
		$xml = simplexml_load_file($path);
		if ($xml === false) {
			libxml_clear_errors();
			throw new Exception('INVALID_XML');
		}
		$name = $xml->getName();
		if (strcasecmp($name, 'codeBook') === 0) {
			return 'survey_xml';
		}
		if (strcasecmp($name, 'MD_Metadata') === 0 || strcasecmp($name, 'MI_Metadata') === 0) {
			return 'geospatial_xml';
		}
		throw new Exception('UNSUPPORTED_XML_ROOT: ' . $name);
	}


	/**
	 * @param mixed $decoded
	 * @return bool
	 */
	private function _catalog_import_json_is_object($decoded)
	{
		if (! is_array($decoded)) {
			return false;
		}
		if ($decoded === array()) {
			return true;
		}
		$keys = array_keys($decoded);
		return ! ctype_digit((string) $keys[0]);
	}


	/**
	 * Apply repository ACL for admin catalog listing: unrestricted, scoped set, or empty (no matches)
	 * when owner_repo is outside the user's allowed collections.
	 *
	 * @param object $user
	 * @param array  $search_options Request GET options (owner_repo read via _get_owner_repo)
	 */
	private function _prepare_admin_catalog_acl($user, array $search_options)
	{
		$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
		if ($scope === null) {
			$this->Catalog_admin_search->set_acl_scope(true, array());
			return;
		}
		if ($scope === false) {
			$this->Catalog_admin_search->set_acl_scope(false, array());
			return;
		}

		$allowed = array_values(array_unique(array_map('strtolower', $scope)));
		$or = $this->_get_owner_repo();
		if ($or !== null && $or !== '' && strtolower((string) $or) !== 'central') {
			$lo = strtolower(trim((string) $or));
			if (! in_array($lo, $allowed, true)) {
				$this->Catalog_admin_search->set_acl_scope(false, array());
				return;
			}
		}
		$this->Catalog_admin_search->set_acl_scope(false, $allowed);
	}

	/**
	 * Resolve owner_repo for filtering by surveys.repositoryid (owner).
	 * When not set, empty, or "central", returns null (no filter = central includes everything).
	 *
	 * @return string|null owner_repo from GET, or null for no owner filter
	 */
	private function _get_owner_repo()
	{
		$owner_repo = $this->input->get('owner_repo');
		if ($owner_repo === null || $owner_repo === '' || $owner_repo === 'central') {
			return null;
		}
		return xss_clean($owner_repo);
	}


	/**
	 * Validated page size from the 'ps' query param.
	 */
	private function _get_page_size($default = 15, $min = 1, $max = 300)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= $min && $ps <= $max) {
			return $ps;
		}
		return $default;
	}


	/**
	 * Page size for batch study lists (larger cap than grid search).
	 */
	private function _get_batch_list_page_size($default = 500, $max = 2000)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= 1 && $ps <= $max) {
			return $ps;
		}
		return $default;
	}


	/**
	 * Repository id for batch list / batch_import ACL (study edit on collection).
	 */
	private function _batch_repositoryid_for_acl(array $search_options)
	{
		$o = isset($search_options['owner_repo']) ? trim((string) $search_options['owner_repo']) : '';
		if ($o === '' || strtolower($o) === 'central') {
			return 'central';
		}
		return $o;
	}


	/**
	 * Whether repositoryid is visible in this user's admin catalog scope.
	 */
	private function _repositoryid_in_admin_catalog_scope($user, $repositoryid)
	{
		$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
		if ($scope === false) {
			return false;
		}
		if ($scope === null) {
			return true;
		}
		$rid = strtolower(trim((string) $repositoryid));
		if ($rid === '' || $rid === 'central') {
			return in_array('central', array_map('strtolower', $scope), true);
		}
		return in_array($rid, array_map('strtolower', $scope), true);
	}


	/**
	 * Normalize user-entered tag text to stored form (matches legacy admin/catalog_tags add).
	 *
	 * @param string $tag
	 * @return string
	 */
	private function _normalize_catalog_tag_for_storage($tag)
	{
		$this->load->helper(array('security', 'string', 'url'));
		$tag = xss_clean($tag);
		$tag = strip_quotes($tag);
		$tag = strip_tags($tag);
		return url_title($tag);
	}


	/**
	 * Featured flag for owner repository (see `Repository_model::is_a_featured_study`).
	 *
	 * @param int   $sid
	 * @param array $survey_row Row from `Dataset_model::get_row` (needs repositoryid).
	 * @return bool
	 */
	private function _survey_is_featured($sid, array $survey_row)
	{
		if (empty($survey_row['repositoryid'])) {
			return false;
		}
		$rid = strtolower((string) $survey_row['repositoryid']);
		if ($rid === 'central') {
			$repoid = 0;
		} else {
			$r = $this->Repository_model->get_repository_by_repositoryid($survey_row['repositoryid']);
			$repoid = $r ? (int) $r['id'] : 0;
		}

		return $this->Repository_model->is_a_featured_study($repoid, (int) $sid) ? true : false;
	}


	/**
	 * Optional JSON body for DELETE .../tags (ids / tag_ids / tags).
	 *
	 * @return array{ids: array, tags: array}
	 */
	private function _catalog_tags_parse_delete_payload()
	{
		$stream = trim((string) $this->input->raw_input_stream);
		if ($stream === '') {
			return array('ids' => array(), 'tags' => array());
		}
		$decoded = json_decode($stream, true);
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('INVALID_JSON_INPUT');
		}
		if (! is_array($decoded)) {
			throw new Exception('INVALID_JSON_INPUT');
		}
		$ids = array();
		if (isset($decoded['ids']) && is_array($decoded['ids'])) {
			$ids = $decoded['ids'];
		}
		elseif (isset($decoded['tag_ids']) && is_array($decoded['tag_ids'])) {
			$ids = $decoded['tag_ids'];
		}
		$tags = array();
		if (isset($decoded['tags']) && is_array($decoded['tags'])) {
			$tags = $decoded['tags'];
		}
		return array('ids' => $ids, 'tags' => $tags);
	}


	/**
	 * Optional JSON body for DELETE .../aliases (`ids` / `alias_ids` / `alternate_ids`).
	 *
	 * @return array{ids: array, alternate_ids: array}
	 */
	private function _catalog_aliases_parse_delete_payload()
	{
		$stream = trim((string) $this->input->raw_input_stream);
		if ($stream === '') {
			return array('ids' => array(), 'alternate_ids' => array());
		}
		$decoded = json_decode($stream, true);
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('INVALID_JSON_INPUT');
		}
		if (! is_array($decoded)) {
			throw new Exception('INVALID_JSON_INPUT');
		}
		$ids = array();
		if (isset($decoded['ids']) && is_array($decoded['ids'])) {
			$ids = $decoded['ids'];
		}
		elseif (isset($decoded['alias_ids']) && is_array($decoded['alias_ids'])) {
			$ids = $decoded['alias_ids'];
		}
		$alternate_ids = array();
		if (isset($decoded['alternate_ids']) && is_array($decoded['alternate_ids'])) {
			$alternate_ids = $decoded['alternate_ids'];
		}
		elseif (isset($decoded['aliases']) && is_array($decoded['aliases'])) {
			$alternate_ids = $decoded['aliases'];
		}
		return array('ids' => $ids, 'alternate_ids' => $alternate_ids);
	}
}
