<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');
require_once(APPPATH . 'libraries/Citation_csl.php');
require_once(APPPATH . 'libraries/Citation_import_export.php');

/**
 * Error with an HTTP status and optional per-field messages.
 */
class Citations_api_error extends Exception {

	public $status;
	public $errors;

	public function __construct($message, $status = 400, array $errors = array())
	{
		parent::__construct($message);
		$this->status = $status;
		$this->errors = $errors;
	}
}

/**
 * Admin citations API.
 *
 * Base URL: /api/admin/citations
 * Auth: a browser session or an API key (X-API-KEY) of a user with the citation permissions: citation/view for
 * every call, plus create / edit / delete for writes. Writes from a browser session need the CSRF token
 * (require_session_csrf); API-key calls carry no cookie and need none. Linking a citation to a study also needs
 * study/view on the study's collection. Responses are built from explicit field lists.
 *
 *   GET  /                          list: keywords, ctype[], published, flag[], user[], has_notes, no_studies,
 *                                   year_from, year_to, collection, url_status[], sort_by, sort_order, page, ps,
 *                                   with_facets
 *   POST /                          create                                         (citation/create)
 *   GET  options                    types and their fields, flags, languages, users, what the caller may do
 *   POST batch                      delete | publish | unpublish | set_flag, up to 500 ids
 *   POST preview                    formatted (Chicago) citation of a document
 *   POST duplicates                 similar citations: title, container_title, authors_text, doi, exclude_id
 *   GET  doi?doi=10.x/y             look a DOI up at Crossref, returns a citation document
 *   POST import/parse               BibTeX / EndNote / RIS / JSON file or text -> preview rows + token
 *   POST import/commit              save the previewed rows
 *   GET  export                     csv | json | bibtex | ris, for list filters, ids[] or everything
 *   GET  {id}                       one citation
 *   POST {id}                       update (fields present in the body only)       (citation/edit)
 *   POST {id}/delete                delete                                         (citation/delete)
 *   POST {id}/publish               {published: 0|1}
 *   GET  {id}/studies               linked studies
 *   POST {id}/studies               {sids: []} replaces the links, or {add: [], remove: []}
 *   GET  {id}/attachment            download the attached file
 *   POST {id}/attachment            upload (multipart field "file")
 *   POST {id}/attachment/delete     remove the attached file
 *   POST {id}/check_url             check that the citation's URL answers
 *
 * A citation is {id, uuid, ctype (CSL type), metadata (CSL-JSON document), published, flag, notes, ...}.
 * Admin lists always use the database search: the filters here are not available in Solr / nada-ai.
 */
class Citations extends MY_REST_Controller {

	const MAX_PAGE_SIZE = 300;
	const MAX_BATCH = 500;
	const MAX_EXPORT_ROWS = 50000;
	const IMPORT_TTL = 3600;

	private static $list_sort_fields = array(
		'title', 'pub_year', 'changed', 'created', 'ctype', 'authors_text', 'container_title', 'published', 'flag', 'rank',
	);

	/** body keys that are never part of the document when the body is a flat (legacy) citation */
	private static $non_document_keys = array(
		'published', 'flag', 'notes', 'owner', 'id', 'uuid', 'created', 'changed', 'created_by', 'changed_by', 'url_status',
		'url_status_time', 'attachment', 'studies', 'metadata', 'overwrite', 'related_studies', 'related_datasets', 'csl_type',
	);

	private $csl;

	public function __construct()
	{
		parent::__construct();
		$this->_require_user();
		$this->require_access('citation', 'view');
		$this->require_session_csrf();

		$this->load->model('Citation_model');
		$this->load->model('Citation_flag_model');
		$this->load->library('citation_study_access');
		$this->load->library('chicago_citation');
		$this->load->config('citations');
		$this->load->config('citation_fields');
		$this->lang->load('general');
		$this->lang->load('citations');

		$this->csl = $this->Citation_model->csl();
	}

	// =============================================================================================
	// List, options, create
	// =============================================================================================

	/**
	 * The REST router sends an unknown path like /citations/abc to index: only /citations itself is the list.
	 */
	private function _require_collection_root()
	{
		$segment = $this->uri->segment(4);
		if ($segment !== null && $segment !== false && $segment !== '') {
			throw new Exception('NOT_FOUND');
		}
	}

	public function index_get()
	{
		try {
			$this->_require_collection_root();
			$page_size = $this->_get_page_size(25, 1, self::MAX_PAGE_SIZE);
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			$q = $this->_list_query();
			$sort_by = $this->_enum_param('sort_by', self::$list_sort_fields, $q['filter']['keywords'] !== '' ? 'rank' : 'changed');
			if ($sort_by === 'rank' && $q['filter']['keywords'] === '') {
				$sort_by = 'changed';
			}
			$sort_order = $this->_enum_param('sort_order', array('asc', 'desc'), 'desc');

			$rows = $this->Citation_model->search_database($page_size, $offset, $q['filter'], $sort_by, $sort_order, $q['published'], $q['collection']);
			$total = (int) $this->Citation_model->search_count();

			if (!is_array($rows)) {
				$rows = array();
			}

			$result = array(
				'rows'      => array_map(array($this, '_list_row'), $rows),
				'total'     => $total,
				'page'      => $page,
				'pages'     => (int) max(1, ceil($total / $page_size)),
				'page_size' => $page_size,
			);

			$response = array('status' => 'success', 'result' => $result);

			if ($this->input->get('with_facets')) {
				$response['facets'] = $this->_facets();
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function options_get()
	{
		try {
			$types = array();
			foreach ((array) $this->config->item('citation_types') as $type => $def) {
				$types[] = array(
					'value'  => $type,
					'label'  => $this->_line($type, ucfirst(str_replace('-', ' ', $type))),
					'legacy' => $def['legacy'],
					'roles'  => array_map(function ($role) {
						return array('name' => $role, 'label' => $this->_line('cit_f_role_' . $role, ucfirst($role) . 's'));
					}, $def['roles']),
					'fields' => $this->_field_defs($def['fields'], $type),
				);
			}

			$flag_counts = array();
			foreach ((array) $this->Citation_model->get_citations_flag_list() as $row) {
				$flag_counts[(string) $row['flag']] = (int) $row['total'];
			}
			$flags = array();
			foreach ($this->Citation_flag_model->get_flat_for_lang() as $code => $title) {
				$flags[] = array('code' => $code, 'label' => $title, 'count' => isset($flag_counts[$code]) ? $flag_counts[$code] : 0);
			}

			$langs = array();
			foreach (Citation_csl::$lang_codes as $name => $code) {
				$langs[] = array('code' => $code, 'label' => trim($name));
			}
			usort($langs, function ($a, $b) { return strcmp($a['label'], $b['label']); });

			$users = array();
			foreach ((array) $this->Citation_model->get_citations_user_list() as $u) {
				$users[] = array('id' => (int) $u['id'], 'username' => $u['username']);
			}

			$collections = array();
			$query = $this->db->select('repositoryid, title')->order_by('title', 'asc')->get('repositories');
			if ($query) {
				foreach ($query->result_array() as $r) {
					$collections[] = array('id' => $r['repositoryid'], 'title' => $r['title']);
				}
			}

			$url_status = array();
			foreach (array(0, 1, 2, 3) as $code) {
				$url_status[] = array('code' => $code, 'label' => $this->_line('url-status-' . $code, (string) $code));
			}

			$this->set_response(array(
				'status'  => 'success',
				'options' => array(
					'ctypes'        => $types,
					'common_fields' => $this->_field_defs((array) $this->config->item('citation_common_fields'), ''),
					'flags'         => $flags,
					'langs'         => $langs,
					'users'         => $users,
					'collections'   => $collections,
					'url_status'    => $url_status,
					'can'           => array(
						'create'       => $this->_can('citation', 'create'),
						'edit'         => $this->_can('citation', 'edit'),
						'delete'       => $this->_can('citation', 'delete'),
						'manage_flags' => $this->_can('codelist', 'edit'),
					),
					'flags_codelist_id' => $this->Citation_flag_model->codelist_id(),
					'attachment_types'  => explode('|', (string) $this->config->item('citations_attachment_types')),
					'max_upload_mb'     => (int) floor($this->_max_upload_bytes() / 1048576),
					'import'            => array(
						'formats'   => explode('|', Citation_import_export::FORMATS),
						'max_bytes' => (int) $this->config->item('citations_import_max_bytes'),
						'max_rows'  => (int) $this->config->item('citations_import_max_rows'),
					),
					'crossref_enabled' => (bool) $this->config->item('crossref_enabled'),
				),
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations
	 * Body: {metadata: <CSL>, published, flag, notes, owner, studies: [study ids]}; a flat (legacy) citation is accepted too.
	 */
	public function index_post()
	{
		try {
			$this->_require_collection_root();
			$this->require_access('citation', 'create');

			$body = $this->_body();
			$studies = $this->_studies_from_body($body);
			$user_id = $this->_user_id();

			$input = $this->_citation_input($body);
			$input['created_by'] = $user_id;
			$input['changed_by'] = $user_id;

			$id = $this->Citation_model->insert($input);

			if (!empty($studies)) {
				$this->Citation_model->attach_related_surveys($id, $studies);
			}

			$citation = $this->_item($id);
			$this->events->emit('db.after.update', 'citations', $id, 'import');
			$this->_log('new', $citation['metadata']);

			$this->set_response(array('status' => 'success', 'id' => $id, 'uuid' => $citation['uuid'], 'citation' => $citation), REST_Controller::HTTP_CREATED);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// One citation
	// =============================================================================================

	public function item_get($id = null)
	{
		try {
			$this->_require_citation($id);
			$this->set_response(array('status' => 'success', 'citation' => $this->_item((int) $id)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/{id}: fields present in the body only. A body with metadata replaces the
	 * whole document; a flat (legacy) body changes just the fields it has.
	 */
	public function item_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$this->_require_citation($id);
			$id = (int) $id;

			$body = $this->_body();
			$studies = $this->_studies_from_body($body);

			$input = $this->_citation_input($body);
			$input['changed_by'] = $this->_user_id();
			unset($input['uuid']);

			$this->Citation_model->update($id, $input);

			if ($studies !== null) {
				$this->_set_studies($id, $studies);
			}

			$citation = $this->_item($id);
			$this->events->emit('db.after.update', 'citations', $id, 'import');
			$this->_log('change', $citation['metadata']);

			$this->set_response(array('status' => 'success', 'citation' => $citation), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function item_delete_post($id = null)
	{
		try {
			$this->require_access('citation', 'delete');
			$row = $this->_require_citation($id);

			$this->_log('delete', $row['metadata']);
			if (!$this->Citation_model->delete((int) $id)) {
				throw new Exception('DELETE_FAILED');
			}
			$this->events->emit('db.after.delete', 'citations', (int) $id);

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function publish_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$this->_require_citation($id);

			$body = $this->_body();
			if (!isset($body['published']) || !in_array($body['published'], array(0, 1, '0', '1', false, true), true)) {
				throw new Citations_api_error('VALIDATION_ERROR', 422, array('published' => 'INVALID_VALUE'));
			}
			$published = (int) $body['published'];

			$this->Citation_model->set_published((int) $id, $published);
			$this->events->emit('db.after.update', 'citations', (int) $id, 'publish');

			$this->set_response(array('status' => 'success', 'published' => $published), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/batch
	 * Body: {action: delete | publish | unpublish | set_flag, ids: [...], flag: "code" (set_flag)}
	 */
	public function batch_post()
	{
		try {
			$body = $this->_body();
			$action = isset($body['action']) ? (string) $body['action'] : '';
			if (!in_array($action, array('delete', 'publish', 'unpublish', 'set_flag'), true)) {
				throw new Citations_api_error('INVALID_ACTION', 400);
			}
			$this->require_access('citation', $action === 'delete' ? 'delete' : 'edit');

			$ids = $this->citation_study_access->clean_ids(isset($body['ids']) ? $body['ids'] : array());
			if (empty($ids)) {
				throw new Citations_api_error('NO_IDS', 400);
			}
			if (count($ids) > self::MAX_BATCH) {
				throw new Citations_api_error('TOO_MANY_IDS', 400);
			}

			$existing = array();
			$this->db->select('id, title');
			$this->db->where_in('id', $ids);
			foreach ($this->db->get('citations')->result_array() as $r) {
				$existing[(int) $r['id']] = $r['title'];
			}

			$failed = array();
			foreach ($ids as $id) {
				if (!isset($existing[$id])) {
					$failed[] = array('id' => $id, 'message' => 'NOT_FOUND');
				}
			}
			$ok = array_values(array_intersect($ids, array_keys($existing)));

			switch ($action) {
				case 'delete':
					$done = array();
					foreach ($ok as $id) {
						try {
							$this->_log('delete', array('title' => $existing[$id]));
							if ($this->Citation_model->delete($id)) {
								$this->events->emit('db.after.delete', 'citations', $id);
								$done[] = $id;
							}
							else {
								$failed[] = array('id' => $id, 'message' => 'DELETE_FAILED');
							}
						}
						catch (Exception $e) {
							log_message('error', 'api/admin/citations batch delete ' . $id . ': ' . $e->getMessage());
							$failed[] = array('id' => $id, 'message' => 'SERVER_ERROR');
						}
					}
					$ok = $done;
					break;

				case 'publish':
				case 'unpublish':
					if (!empty($ok)) {
						$this->Citation_model->set_published($ok, $action === 'publish' ? 1 : 0);
						$this->events->emit('db.after.update', 'citations', $ok, 'publish');
					}
					break;

				case 'set_flag':
					$flag = isset($body['flag']) ? trim((string) $body['flag']) : '';
					if (!empty($ok)) {
						$this->Citation_model->set_flag($ok, $flag);
						$this->events->emit('db.after.update', 'citations', $ok, 'import');
					}
					break;
			}

			$this->set_response(array('status' => 'success', 'affected' => count($ok), 'failed' => $failed), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Helpers for the form: preview, duplicates, DOI
	// =============================================================================================

	/**
	 * POST /api/admin/citations/preview   Body: {metadata}
	 */
	public function preview_post()
	{
		try {
			$body = $this->_body();
			if (!isset($body['metadata']) || !is_array($body['metadata'])) {
				throw new Citations_api_error('VALIDATION_ERROR', 422, array('metadata' => 'REQUIRED'));
			}
			$doc = $this->csl->normalize($body['metadata']);

			$html = $this->_format_doc($doc, 0);
			// nothing to link to yet
			$html = preg_replace('#</?a\b[^>]*>#i', '', $html);

			$this->set_response(array('status' => 'success', 'formatted' => $html, 'text' => trim(strip_tags($html))), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/duplicates   Body: {title, container_title, authors_text, doi, exclude_id}
	 */
	public function duplicates_post()
	{
		try {
			$body = $this->_body();
			$text = function ($key, $max) use ($body) {
				return isset($body[$key]) && is_scalar($body[$key]) ? mb_substr(trim((string) $body[$key]), 0, $max) : '';
			};

			$keywords = trim($text('title', 500) . ' ' . $text('container_title', 200) . ' ' . $text('authors_text', 300));
			$exclude = isset($body['exclude_id']) ? (int) $body['exclude_id'] : 0;

			$rows = $this->Citation_model->search_duplicates($keywords, $text('doi', 255));
			$out = array();
			foreach ((is_array($rows) ? $rows : array()) as $row) {
				if ((int) $row['id'] === $exclude) {
					continue;
				}
				$out[] = $this->_summary($row) + array('score' => (float) $row['score']);
			}

			$this->set_response(array('status' => 'success', 'rows' => $out), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/citations/doi?doi=10.1000/xyz
	 * Looks the DOI up at Crossref, then DataCite if needed; returns a citation document (nothing is saved).
	 */
	public function doi_get()
	{
		try {
			if (!$this->_can('citation', 'create') && !$this->_can('citation', 'edit')) {
				$this->access_denied_response();
			}
			if (!$this->config->item('crossref_enabled')) {
				throw new Citations_api_error('CROSSREF_DISABLED', 503);
			}

			require_once(APPPATH . 'libraries/Citation_crossref.php');
			$crossref = new Citation_crossref(array('mailto' => $this->config->item('crossref_mailto')));

			$doi = Citation_crossref::normalize_doi((string) $this->input->get('doi'));
			if ($doi === '') {
				throw new Citations_api_error('DOI_INVALID', 400);
			}

			try {
				$doc = $this->csl->normalize($crossref->lookup($doi));
			}
			catch (Exception $e) {
				$map = array('DOI_INVALID' => 400, 'DOI_NOT_FOUND' => 404, 'CROSSREF_UNAVAILABLE' => 502);
				if (isset($map[$e->getMessage()])) {
					throw new Citations_api_error($e->getMessage(), $map[$e->getMessage()]);
				}
				throw $e;
			}

			$duplicates = array();
			$found = $this->Citation_model->search_duplicates('', $doi);
			foreach ((is_array($found) ? $found : array()) as $row) {
				$duplicates[] = $this->_summary($row);
			}

			$this->set_response(array('status' => 'success', 'metadata' => $doc, 'duplicates' => $duplicates), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Studies
	// =============================================================================================

	public function studies_get($id = null)
	{
		try {
			$this->_require_citation($id);
			$this->set_response(array('status' => 'success', 'studies' => $this->_studies((int) $id)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/{id}/studies
	 * Body: {sids: [...]} makes these the linked studies, or {add: [...], remove: [...]}.
	 * Studies the caller may not view are left as they are; adding or removing one needs study/view on its collection.
	 */
	public function studies_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$this->_require_citation($id);
			$id = (int) $id;

			$body = $this->_body();
			$current = array_map(function ($s) { return (int) $s['id']; }, $this->Citation_model->get_related_surveys($id));

			if (isset($body['sids'])) {
				$wanted = $this->citation_study_access->clean_ids($body['sids']);
				$add = array_values(array_diff($wanted, $current));
				$remove = array_values(array_diff($current, $wanted));
			}
			else {
				$add = $this->citation_study_access->clean_ids(isset($body['add']) ? $body['add'] : array());
				$remove = $this->citation_study_access->clean_ids(isset($body['remove']) ? $body['remove'] : array());
			}

			$this->_change_studies($id, $add, $remove);
			$this->events->emit('db.after.update', 'citations', $id, 'import');

			$this->set_response(array('status' => 'success', 'studies' => $this->_studies($id)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Attachment
	// =============================================================================================

	public function attachment_get($id = null)
	{
		try {
			$row = $this->_require_citation($id);
			$path = $this->_attachment_path($row['attachment']);
			if ($path === null) {
				throw new Exception('NOT_FOUND');
			}

			$name = $this->_attachment_name($row);
			$ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
			header('Content-Length: ' . filesize($path));
			header('X-Content-Type-Options: nosniff');
			header('Cache-Control: private, no-store');
			readfile($path);
			exit;
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/{id}/attachment   multipart, field "file"
	 */
	public function attachment_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$row = $this->_require_citation($id);

			$file = $this->_uploaded_file('file');
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed = explode('|', (string) $this->config->item('citations_attachment_types'));

			if (!in_array($ext, $allowed, true)) {
				throw new Citations_api_error('FILE_TYPE_NOT_ALLOWED', 422, array('file' => 'FILE_TYPE_NOT_ALLOWED'));
			}
			if (!$this->_file_matches_type($file['tmp_name'], $ext)) {
				throw new Citations_api_error('FILE_CONTENT_MISMATCH', 422, array('file' => 'FILE_CONTENT_MISMATCH'));
			}

			$dir = $this->_storage_dir();
			$base = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
			$base = trim(mb_substr($base, 0, 100), '.-') ?: 'file';
			$stored = preg_replace('/[^A-Za-z0-9-]/', '', $row['uuid']) . '-' . $base . '.' . $ext;

			if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
				throw new Exception('UPLOAD_FAILED');
			}
			@chmod($dir . '/' . $stored, 0644);

			$old = (string) $row['attachment'];
			$this->Citation_model->update_attachment((int) $id, array('attachment' => $stored));
			if ($old !== '' && basename($old) !== $stored) {
				$old_path = $this->_attachment_path($old);
				if ($old_path !== null) {
					@unlink($old_path);
				}
			}

			$this->Citation_model->update((int) $id, array('changed_by' => $this->_user_id()));

			$this->set_response(array('status' => 'success', 'attachment' => $this->_attachment_info($this->_require_citation($id))), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function attachment_delete_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$this->_require_citation($id);

			$this->Citation_model->delete_attachment((int) $id);
			$this->Citation_model->update((int) $id, array('changed_by' => $this->_user_id()));

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Link check
	// =============================================================================================

	/**
	 * POST /api/admin/citations/{id}/check_url
	 * Run by an editor, one citation at a time. The URL is the citation's own; the server refuses
	 * non-public addresses (see Citation_url_checker).
	 */
	public function check_url_post($id = null)
	{
		try {
			$this->require_access('citation', 'edit');
			$row = $this->_require_citation($id);

			require_once(APPPATH . 'libraries/Citation_url_checker.php');
			$url = isset($row['metadata']['URL']) ? (string) $row['metadata']['URL'] : '';
			$checker = new Citation_url_checker();
			$result = $checker->check($url);

			$this->Citation_model->set_url_status((int) $id, $result['status']);

			$this->set_response(array(
				'status'     => 'success',
				'url'        => $url,
				'url_status' => $result['status'],
				'http_code'  => $result['http_code'],
				'message'    => $result['message'],
				'checked_at' => time(),
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Import
	// =============================================================================================

	/**
	 * POST /api/admin/citations/import/parse
	 * Multipart: file (BibTeX, EndNote, RIS or JSON) or text; format = bibtex | endnote | ris | json | auto.
	 * Nothing is saved: the rows are kept for an hour under the returned token for import/commit.
	 */
	public function import_parse_post()
	{
		try {
			$this->require_access('citation', 'create');

			$text = $this->_import_text();
			$format = (string) $this->input->post('format');
			if ($format === '') {
				$body = json_decode((string) file_get_contents('php://input'), true);
				$format = is_array($body) && isset($body['format']) ? (string) $body['format'] : 'auto';
			}

			$io = new Citation_import_export(array('max_rows' => (int) $this->config->item('citations_import_max_rows')));
			$parsed = $io->parse($format, $text);

			if (empty($parsed['rows'])) {
				throw new Citations_api_error('IMPORT_FAILED', 422, array('file' => $parsed['errors']));
			}

			$docs = array();
			$rows = array();
			$importable = 0;
			foreach ($parsed['rows'] as $row) {
				$doc = $row['doc'];
				$docs[$row['index']] = $doc;
				$cols = $this->csl->project_columns($doc);

				// duplicate_of: the same work (same DOI, or same type, title and year); import/commit skips these.
				// similar: the same title with another year or type; only for a look.
				$duplicate_of = null;
				$similar = array();
				if (empty($row['errors'])) {
					$importable++;
					$dup = $this->Citation_model->find_duplicate_id($doc);
					$duplicate_of = $dup !== false ? (int) $dup : null;
					$found = $this->Citation_model->search_duplicates($cols['title'], '');
					foreach ((is_array($found) ? $found : array()) as $d) {
						$summary = $this->_summary($d);
						if ($summary['id'] !== $duplicate_of && mb_strtolower($summary['title']) === mb_strtolower($cols['title'])) {
							$similar[] = $summary;
						}
						if (count($similar) >= 3) {
							break;
						}
					}
				}

				$rows[] = array(
					'index'           => $row['index'],
					'type'            => $doc['type'],
					'title'           => $cols['title'],
					'container_title' => $cols['container_title'],
					'authors_text'    => $cols['authors_text'],
					'pub_year'        => $cols['pub_year'],
					'doi'             => $cols['doi'],
					'warnings'        => $row['warnings'],
					'errors'          => $row['errors'],
					'duplicate_of'    => $duplicate_of,
					'similar'         => $similar,
					'metadata'        => $doc,
				);
			}

			$token = bin2hex(random_bytes(16));
			$this->_import_store($token, array('uid' => $this->_user_id(), 'created' => time(), 'format' => $parsed['format'], 'docs' => $docs, 'errors' => array_column($rows, 'errors', 'index')));

			$this->set_response(array(
				'status'     => 'success',
				'token'      => $token,
				'format'     => $parsed['format'],
				'total'      => count($rows),
				'importable' => $importable,
				'rows'       => $rows,
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/citations/import/commit
	 * Body: {token, indexes: [...] | all: true, published, flag, studies: [ids], skip_duplicates: true}
	 */
	public function import_commit_post()
	{
		try {
			$this->require_access('citation', 'create');

			$body = $this->_body();
			$token = isset($body['token']) ? (string) $body['token'] : '';
			$payload = $this->_import_load($token);
			if ($payload === null) {
				throw new Citations_api_error('IMPORT_NOT_FOUND', 404);
			}

			$indexes = !empty($body['all']) ? array_keys($payload['docs']) : array_map('intval', (array) (isset($body['indexes']) ? $body['indexes'] : array()));
			if (empty($indexes)) {
				throw new Citations_api_error('NOTHING_SELECTED', 400);
			}

			$studies = $this->_studies_from_body($body);
			$user_id = $this->_user_id();
			$defaults = array(
				'published'  => !empty($body['published']) ? 1 : 0,
				'created_by' => $user_id,
				'changed_by' => $user_id,
			);
			if (isset($body['flag']) && trim((string) $body['flag']) !== '') {
				$defaults['flag'] = trim((string) $body['flag']);
			}
			if (isset($defaults['flag']) && !$this->Citation_flag_model->is_valid_code($defaults['flag'])) {
				throw new Citations_api_error('VALIDATION_ERROR', 422, array('flag' => 'INVALID_FLAG'));
			}
			$skip_duplicates = !isset($body['skip_duplicates']) || !empty($body['skip_duplicates']);

			$created = array();
			$skipped = array();
			foreach (array_unique($indexes) as $index) {
				if (!isset($payload['docs'][$index])) {
					$skipped[] = array('index' => $index, 'reason' => 'NOT_FOUND');
					continue;
				}
				if (!empty($payload['errors'][$index])) {
					$skipped[] = array('index' => $index, 'reason' => 'INVALID');
					continue;
				}
				$doc = $payload['docs'][$index];

				if ($skip_duplicates && ($dup = $this->Citation_model->find_duplicate_id($doc)) !== false) {
					$skipped[] = array('index' => $index, 'reason' => 'DUPLICATE', 'duplicate_id' => (int) $dup);
					continue;
				}

				try {
					$id = $this->Citation_model->insert(array('metadata' => $doc) + $defaults);
					if (!empty($studies)) {
						$this->Citation_model->attach_related_surveys($id, $studies);
					}
					$this->events->emit('db.after.update', 'citations', $id, 'import');
					$this->_log('import', $doc);
					$created[] = array('index' => $index, 'id' => $id);
				}
				catch (ValidationException $e) {
					$skipped[] = array('index' => $index, 'reason' => 'INVALID');
				}
				catch (Exception $e) {
					log_message('error', 'api/admin/citations import ' . $index . ': ' . $e->getMessage());
					$skipped[] = array('index' => $index, 'reason' => 'SERVER_ERROR');
				}
			}

			$this->_import_delete($token);

			$this->set_response(array('status' => 'success', 'created' => $created, 'skipped' => $skipped), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Export
	// =============================================================================================

	/**
	 * GET /api/admin/citations/export?format=csv|json|bibtex|ris
	 * With ids[] only those citations; with list filters (as in the list) the matching ones; otherwise all.
	 */
	public function export_get()
	{
		try {
			$format = $this->_enum_param('format', array('csv', 'json', 'bibtex', 'ris'), 'csv');

			$ids = null;
			$requested = $this->input->get('ids');
			if (is_array($requested) || (is_string($requested) && $requested !== '')) {
				$ids = $this->citation_study_access->clean_ids(is_array($requested) ? $requested : explode(',', $requested));
				if (empty($ids)) {
					throw new Citations_api_error('NO_IDS', 400);
				}
			}
			elseif ($this->_has_list_filters()) {
				$ids = $this->_filtered_ids();
			}

			$types = array('csv' => 'text/csv', 'json' => 'application/json', 'bibtex' => 'application/x-bibtex', 'ris' => 'application/x-research-info-systems');
			$ext = array('csv' => 'csv', 'json' => 'json', 'bibtex' => 'bib', 'ris' => 'ris');

			header('Content-Type: ' . $types[$format] . '; charset=utf-8');
			header('Content-Disposition: attachment; filename="citations-' . date('Y-m-d-His') . '.' . $ext[$format] . '"');
			header('X-Content-Type-Options: nosniff');
			header('Cache-Control: private, no-store');

			set_time_limit(0);
			$this->Citation_model->stream_export($format, fopen('php://output', 'w'), $ids);
			exit;
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// =============================================================================================
	// Request helpers
	// =============================================================================================

	/** 401 unless the request has a session or a valid API key */
	private function _require_user()
	{
		if ($this->_user_id() < 1) {
			$this->response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_UNAUTHORIZED, false);
			exit;
		}
	}

	/** The user making the request: the session user, or the owner of the API key */
	private function _user_id()
	{
		return (int) $this->get_api_user_id();
	}

	private function _can($resource, $privilege)
	{
		try {
			$this->has_access($resource, $privilege);
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	private function _body()
	{
		$body = $this->raw_json_input();
		return is_array($body) ? $body : array();
	}

	private function _get_page_size($default, $min, $max)
	{
		$ps = (int) $this->input->get('ps');
		return ($ps >= $min && $ps <= $max) ? $ps : $default;
	}

	private function _enum_param($name, array $allowed, $default)
	{
		$value = $this->input->get($name);
		if (!is_string($value)) {
			return $default;
		}
		$value = strtolower(trim($value));
		return in_array($value, $allowed, true) ? $value : $default;
	}

	private function _text_param($name, $max_length)
	{
		$value = $this->input->get($name);
		if (!is_string($value)) {
			return '';
		}
		$value = trim($value);
		if (mb_strlen($value) > $max_length) {
			throw new Exception('PARAM_TOO_LONG: ' . $name);
		}
		return $value;
	}

	/** ctype[]=a&ctype[]=b or ctype=a,b */
	private function _list_param($name)
	{
		$value = $this->input->get($name);
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		if (!is_array($value)) {
			return array();
		}
		$out = array();
		foreach ($value as $v) {
			if (!is_scalar($v)) {
				continue;
			}
			$v = trim((string) $v);
			if ($v !== '' && mb_strlen($v) <= 64) {
				$out[$v] = $v;
			}
			if (count($out) >= 50) {
				break;
			}
		}
		return array_values($out);
	}

	/**
	 * List filters from the query string: {filter, published, collection}
	 */
	private function _list_query()
	{
		$filter = array(
			'keywords'      => $this->_text_param('keywords', 200),
			'include_notes' => 1, // keywords also match the internal notes
		);

		if (($ctype = $this->_list_param('ctype'))) {
			$filter['ctype'] = $ctype;
		}
		if (($flag = $this->_list_param('flag'))) {
			$filter['flag'] = $flag;
		}
		if (($user = array_filter(array_map('intval', $this->_list_param('user'))))) {
			$filter['user'] = array_values($user);
		}
		if (($status = $this->_list_param('url_status'))) {
			$filter['url_status'] = $status;
		}
		if ($this->input->get('has_notes')) {
			$filter['has_notes'] = 1;
		}
		if ($this->input->get('no_studies')) {
			$filter['no_survey_attached'] = 1;
		}
		foreach (array('year_from' => 'from', 'year_to' => 'to') as $param => $key) {
			$year = $this->input->get($param);
			if (is_string($year) && preg_match('/^\d{4}$/', $year)) {
				$filter[$key] = $year;
			}
		}

		$published = $this->input->get('published');
		$published = in_array((string) $published, array('0', '1'), true) ? (int) $published : null;

		$collection = $this->_text_param('collection', 64);

		return array('filter' => $filter, 'published' => $published, 'collection' => $collection !== '' ? $collection : null);
	}

	private function _has_list_filters()
	{
		$q = $this->_list_query();
		return $q['filter']['keywords'] !== '' || count($q['filter']) > 2 || $q['published'] !== null || $q['collection'] !== null;
	}

	/**
	 * Ids of all citations matching the list filters (export)
	 */
	private function _filtered_ids()
	{
		$q = $this->_list_query();
		$ids = array();
		$offset = 0;
		while (count($ids) < self::MAX_EXPORT_ROWS) {
			$rows = $this->Citation_model->search_database(500, $offset, $q['filter'], 'changed', 'desc', $q['published'], $q['collection']);
			if (empty($rows)) {
				break;
			}
			foreach ($rows as $r) {
				$ids[] = (int) $r['id'];
			}
			if (count($rows) < 500) {
				break;
			}
			$offset += 500;
		}
		if (empty($ids)) {
			throw new Citations_api_error('NO_CITATIONS_MATCH', 404);
		}
		return $ids;
	}

	private function _facets()
	{
		$facets = $this->Citation_model->get_facets();
		$flags = $this->Citation_flag_model->get_flat_for_lang();
		foreach ($facets['flag'] as $i => $f) {
			if ((string) $f['value'] === '') {
				unset($facets['flag'][$i]);
				continue;
			}
			$facets['flag'][$i]['label'] = isset($flags[$f['value']]) ? $flags[$f['value']] : $f['value'];
			$facets['flag'][$i]['known'] = isset($flags[$f['value']]);
		}
		$facets['flag'] = array_values($facets['flag']);
		return $facets;
	}

	private function _line($key, $default)
	{
		$line = $this->lang->line($key, false);
		return ($line !== false && $line !== '') ? $line : $default;
	}

	private function _field_defs(array $fields, $type)
	{
		$inputs = (array) $this->config->item('citation_field_inputs');
		$out = array();
		foreach ($fields as $field) {
			$label = ($type !== '' ? $this->lang->line('cit_f_' . $field . '_' . $type, false) : false);
			if ($label === false || $label === '') {
				$label = $this->_line('cit_f_' . $field, $field);
			}
			$out[] = array('name' => $field, 'label' => $label, 'input' => isset($inputs[$field]) ? $inputs[$field] : 'text');
		}
		return $out;
	}

	// =============================================================================================
	// Rows
	// =============================================================================================

	/**
	 * @return array hydrated citation row
	 * @throws Exception NOT_FOUND
	 */
	private function _require_citation($id)
	{
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Exception('NOT_FOUND');
		}
		$row = $this->Citation_model->get_row((int) $id);
		if (!$row) {
			throw new Exception('NOT_FOUND');
		}
		return $row;
	}

	/**
	 * Row of the list
	 */
	private function _list_row(array $r)
	{
		$doc = isset($r['metadata']) && is_array($r['metadata']) ? $r['metadata'] : array();
		$parts = $this->csl->date_parts(isset($doc['issued']) ? $doc['issued'] : null);
		$notes = (string) $r['notes'];

		return array(
			'id'              => (int) $r['id'],
			'uuid'            => $r['uuid'],
			'ctype'           => $r['csl_type'],
			'legacy_ctype'    => $r['ctype'],
			'title'           => isset($doc['title']) ? (string) $doc['title'] : '',
			'container_title' => isset($doc['container-title']) ? (string) $doc['container-title'] : '',
			'authors_text'    => (string) $r['authors_text'],
			'pub_year'        => isset($parts[0]) ? (int) $parts[0] : null,
			'doi'             => isset($doc['DOI']) ? (string) $doc['DOI'] : '',
			'url'             => isset($doc['URL']) ? (string) $doc['URL'] : '',
			'lang'            => isset($doc['language']) ? (string) $doc['language'] : '',
			'published'       => (int) $r['published'],
			'flag'            => (string) $r['flag'],
			'has_notes'       => trim($notes) !== '',
			'notes_excerpt'   => mb_substr($notes, 0, 200),
			'survey_count'    => isset($r['survey_count']) ? (int) $r['survey_count'] : 0,
			'url_status'      => $r['url_status'] !== null && $r['url_status'] !== '' ? (int) $r['url_status'] : null,
			'url_status_time' => $r['url_status_time'] !== null ? (int) $r['url_status_time'] : null,
			'has_attachment'  => trim((string) $r['attachment']) !== '',
			'created'         => (int) $r['created'],
			'changed'         => (int) $r['changed'],
			'created_by'      => $r['created_by'] !== null ? (int) $r['created_by'] : null,
			'changed_by'      => $r['changed_by'] !== null ? (int) $r['changed_by'] : null,
			'created_by_user' => isset($r['created_by_user']) ? $r['created_by_user'] : null,
			'changed_by_user' => isset($r['changed_by_user']) ? $r['changed_by_user'] : null,
		);
	}

	/**
	 * Short form for duplicate lists
	 */
	private function _summary(array $r)
	{
		$doc = isset($r['metadata']) && is_array($r['metadata']) ? $r['metadata'] : array();
		$parts = $this->csl->date_parts(isset($doc['issued']) ? $doc['issued'] : null);
		return array(
			'id'              => (int) $r['id'],
			'title'           => isset($doc['title']) ? (string) $doc['title'] : '',
			'container_title' => isset($doc['container-title']) ? (string) $doc['container-title'] : '',
			'authors_text'    => (string) $r['authors_text'],
			'pub_year'        => isset($parts[0]) ? (int) $parts[0] : null,
			'doi'             => isset($doc['DOI']) ? (string) $doc['DOI'] : '',
			'published'       => (int) $r['published'],
		);
	}

	/**
	 * A citation as the API returns it
	 */
	private function _item($id)
	{
		$row = $this->Citation_model->select_single((int) $id);
		if (!$row) {
			throw new Exception('NOT_FOUND');
		}

		$doc = $row['metadata'];
		unset($doc['id']);

		return array(
			'id'              => (int) $row['id'],
			'uuid'            => $row['uuid'],
			'ctype'           => $row['csl_type'],
			'legacy_ctype'    => $row['ctype'],
			'metadata'        => $doc,
			'published'       => (int) $row['published'],
			'flag'            => (string) $row['flag'],
			'notes'           => (string) $row['notes'],
			'owner'           => (string) $row['owner'],
			'url_status'      => $row['url_status'] !== null && $row['url_status'] !== '' ? (int) $row['url_status'] : null,
			'url_status_time' => $row['url_status_time'] !== null ? (int) $row['url_status_time'] : null,
			'attachment'      => $this->_attachment_info($row),
			'created'         => (int) $row['created'],
			'changed'         => (int) $row['changed'],
			'created_by'      => $row['created_by'] !== null ? (int) $row['created_by'] : null,
			'changed_by'      => $row['changed_by'] !== null ? (int) $row['changed_by'] : null,
			'created_by_user' => isset($row['created_by_user']) ? $row['created_by_user'] : null,
			'changed_by_user' => isset($row['changed_by_user']) ? $row['changed_by_user'] : null,
			'studies'         => $this->_studies((int) $row['id']),
			'formatted'       => $this->_format_row($row),
		);
	}

	private function _studies($id)
	{
		$out = array();
		foreach ($this->Citation_model->get_related_surveys((int) $id) as $s) {
			$out[] = array(
				'id'           => (int) $s['id'],
				'idno'         => $s['idno'],
				'title'        => $s['title'],
				'nation'       => $s['nation'],
				'year_start'   => $s['year_start'],
				'year_end'     => $s['year_end'],
				'repositoryid' => isset($s['repositoryid']) ? $s['repositoryid'] : null,
			);
		}
		return $out;
	}

	/** Chicago style, HTML-escaped */
	private function _format_row(array $row)
	{
		try {
			return (string) $this->chicago_citation->format_escaped($row, 'journal');
		}
		catch (Exception $e) {
			return html_escape(isset($row['title']) ? $row['title'] : '');
		}
	}

	private function _format_doc(array $doc, $id)
	{
		$row = $this->csl->to_legacy_row($doc);
		$row['id'] = (int) $id;
		return $this->_format_row($row);
	}

	/**
	 * Input for Citation_model insert/update from a request body
	 */
	private function _citation_input(array $body)
	{
		$input = array();
		foreach (array('published', 'flag', 'notes', 'owner') as $key) {
			if (array_key_exists($key, $body)) {
				$input[$key] = $body[$key];
			}
		}

		if (isset($body['metadata'])) {
			if (!is_array($body['metadata'])) {
				throw new Citations_api_error('VALIDATION_ERROR', 422, array('metadata' => 'INVALID_METADATA'));
			}
			$input['metadata'] = $body['metadata'];
		}
		else {
			foreach ($body as $key => $value) {
				if (!in_array($key, self::$non_document_keys, true)) {
					$input[$key] = $value;
				}
			}
		}

		return $input;
	}

	// =============================================================================================
	// Studies
	// =============================================================================================

	/**
	 * Study ids of a body, validated: null when the body has no `studies`.
	 */
	private function _studies_from_body(array $body)
	{
		if (!array_key_exists('studies', $body)) {
			return null;
		}
		$ids = $this->citation_study_access->clean_ids($body['studies']);
		$this->_check_studies($ids);
		return $ids;
	}

	/**
	 * The studies must exist and be viewable by the caller.
	 */
	private function _check_studies(array $ids)
	{
		if (empty($ids)) {
			return;
		}
		$missing = array_values(array_diff($ids, $this->citation_study_access->existing_ids($ids)));
		if (!empty($missing)) {
			throw new Citations_api_error('STUDY_NOT_FOUND', 422, array('studies' => $missing));
		}
		$denied = $this->citation_study_access->denied_ids($ids, $this->_user_id());
		if (!empty($denied)) {
			throw new Citations_api_error('ACCESS-DENIED', 403, array('studies' => $denied));
		}
	}

	/**
	 * Make $ids the studies of a citation. Studies the caller may not view are not touched.
	 */
	private function _set_studies($citation_id, array $ids)
	{
		$current = array_map(function ($s) { return (int) $s['id']; }, $this->Citation_model->get_related_surveys($citation_id));
		$this->_change_studies($citation_id, array_values(array_diff($ids, $current)), array_values(array_diff($current, $ids)), true);
	}

	private function _change_studies($citation_id, array $add, array $remove, $checked = false)
	{
		if (!$checked) {
			$this->_check_studies(array_values(array_unique(array_merge($add, $remove))));
		}
		foreach ($remove as $sid) {
			$this->Citation_model->delete_related_survey($citation_id, $sid);
		}
		if (!empty($add)) {
			$this->Citation_model->attach_related_surveys($citation_id, $add);
		}
	}

	// =============================================================================================
	// Files
	// =============================================================================================

	private function _storage_dir()
	{
		$dir = rtrim((string) $this->config->item('citations_storage_path'), '/\\');
		if ($dir === '') {
			throw new Exception('STORAGE_NOT_CONFIGURED');
		}
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new Exception('STORAGE_NOT_WRITABLE');
		}
		return $dir;
	}

	/** Full path of a stored attachment, or null when there is no such file */
	private function _attachment_path($stored)
	{
		$stored = basename(trim((string) $stored));
		if ($stored === '') {
			return null;
		}
		$path = rtrim((string) $this->config->item('citations_storage_path'), '/\\') . '/' . $stored;
		return is_file($path) ? $path : null;
	}

	/** Name shown to the user: the stored name without the leading uuid */
	private function _attachment_name(array $row)
	{
		$stored = basename((string) $row['attachment']);
		$prefix = preg_replace('/[^A-Za-z0-9-]/', '', $row['uuid']) . '-';
		return ($prefix !== '-' && strpos($stored, $prefix) === 0 && strlen($stored) > strlen($prefix)) ? substr($stored, strlen($prefix)) : $stored;
	}

	private function _attachment_info(array $row)
	{
		if (trim((string) $row['attachment']) === '') {
			return null;
		}
		$path = $this->_attachment_path($row['attachment']);
		return array(
			'name'   => $this->_attachment_name($row),
			'size'   => $path !== null ? filesize($path) : null,
			'exists' => $path !== null,
		);
	}

	private function _max_upload_bytes()
	{
		$to_bytes = function ($v) {
			$v = trim((string) $v);
			$n = (int) $v;
			switch (strtolower(substr($v, -1))) {
				case 'g': $n *= 1024;
				case 'm': $n *= 1024;
				case 'k': $n *= 1024;
			}
			return $n;
		};
		$limits = array_filter(array($to_bytes(ini_get('upload_max_filesize')), $to_bytes(ini_get('post_max_size'))));
		return empty($limits) ? 8 * 1048576 : min($limits);
	}

	/**
	 * A valid uploaded file of the multipart field
	 */
	private function _uploaded_file($field)
	{
		if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || is_array($_FILES[$field]['name'])) {
			throw new Citations_api_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		$file = $_FILES[$field];
		if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
			throw new Citations_api_error('FILE_TOO_LARGE', 413, array($field => 'FILE_TOO_LARGE'));
		}
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			throw new Citations_api_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
			throw new Citations_api_error('UPLOAD_FAILED', 400, array($field => 'UPLOAD_FAILED'));
		}
		return $file;
	}

	/**
	 * The start of the file has to look like the type the name says (a .pdf that is a program is refused).
	 */
	private function _file_matches_type($path, $ext)
	{
		$head = (string) @file_get_contents($path, false, null, 0, 1024);
		switch ($ext) {
			case 'pdf':
				return strpos($head, '%PDF-') !== false;
			case 'zip':
			case 'docx':
				return substr($head, 0, 2) === 'PK';
			case 'doc':
				return substr($head, 0, 4) === "\xD0\xCF\x11\xE0" || substr($head, 0, 5) === '{\\rtf';
			case 'txt':
				return strpos($head, "\0") === false;
		}
		return false;
	}

	// =============================================================================================
	// Import files
	// =============================================================================================

	/**
	 * Text of the uploaded file, or of the `text` field / JSON body
	 */
	private function _import_text()
	{
		$max = (int) $this->config->item('citations_import_max_bytes');

		if (!empty($_FILES['file'])) {
			$file = $this->_uploaded_file('file');
			if ($file['size'] > $max) {
				throw new Citations_api_error('FILE_TOO_LARGE', 413, array('file' => 'FILE_TOO_LARGE'));
			}
			$text = (string) file_get_contents($file['tmp_name']);
		}
		else {
			$text = $this->input->post('text');
			if (!is_string($text) || $text === '') {
				$body = json_decode((string) file_get_contents('php://input'), true);
				$text = is_array($body) && isset($body['text']) && is_string($body['text']) ? $body['text'] : '';
			}
		}

		if ($text === '') {
			throw new Citations_api_error('NO_FILE', 400, array('file' => 'NO_FILE'));
		}
		if (strlen($text) > $max) {
			throw new Citations_api_error('FILE_TOO_LARGE', 413, array('file' => 'FILE_TOO_LARGE'));
		}

		// UTF-16 (with BOM) and Windows-1252 files are common from reference managers
		if (substr($text, 0, 2) === "\xFF\xFE" || substr($text, 0, 2) === "\xFE\xFF") {
			$text = mb_convert_encoding($text, 'UTF-8', 'UTF-16');
		}
		elseif (!mb_check_encoding($text, 'UTF-8')) {
			$text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
		}
		return $text;
	}

	private function _import_dir()
	{
		$dir = $this->_storage_dir() . '/_import';
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new Exception('STORAGE_NOT_WRITABLE');
		}
		return $dir;
	}

	private function _import_store($token, array $payload)
	{
		$dir = $this->_import_dir();

		// expired previews
		foreach ((array) glob($dir . '/*.json') as $f) {
			if (@filemtime($f) < time() - self::IMPORT_TTL) {
				@unlink($f);
			}
		}

		if (file_put_contents($dir . '/' . $token . '.json', json_encode($payload, JSON_UNESCAPED_UNICODE)) === false) {
			throw new Exception('STORAGE_NOT_WRITABLE');
		}
	}

	/** @return array|null the stored preview of this user */
	private function _import_load($token)
	{
		if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
			return null;
		}
		$path = $this->_import_dir() . '/' . $token . '.json';
		if (!is_file($path) || filemtime($path) < time() - self::IMPORT_TTL) {
			return null;
		}
		$payload = json_decode((string) file_get_contents($path), true);
		if (!is_array($payload) || (int) $payload['uid'] !== $this->_user_id()) {
			return null;
		}
		return $payload;
	}

	private function _import_delete($token)
	{
		if (preg_match('/^[a-f0-9]{32}$/', $token)) {
			@unlink($this->_import_dir() . '/' . $token . '.json');
		}
	}

	// =============================================================================================
	// Log and errors
	// =============================================================================================

	/**
	 * Activity log entry; a failure to log never fails the request
	 */
	private function _log($type, array $doc)
	{
		try {
			if (!isset($this->db_logger)) {
				$this->load->library('db_logger');
			}
			$this->db_logger->write_log($type, isset($doc['title']) ? (string) $doc['title'] : '', 'citations');
		}
		catch (Throwable $e) {
			log_message('error', 'api/admin/citations log: ' . $e->getMessage());
		}
	}

	private function _error_response(Exception $e)
	{
		$message = $e->getMessage();

		if ($e instanceof Citations_api_error && $e->status < 500) {
			$body = array('status' => 'error', 'message' => $message);
			if ($e->errors) {
				$body['errors'] = $e->errors;
			}
			$this->set_response($body, $e->status);
			return;
		}

		if ($e instanceof ValidationException) {
			$this->set_response(array('status' => 'error', 'message' => 'VALIDATION_ERROR', 'errors' => (array) $e->GetValidationErrors()), 422);
			return;
		}

		if ($message === 'NOT_FOUND' || $message === 'CITATION_NOT_FOUND') {
			$code = REST_Controller::HTTP_NOT_FOUND;
			$message = 'NOT_FOUND';
		}
		elseif ($message === 'INVALID_JSON_INPUT' || strpos($message, 'PARAM_TOO_LONG') === 0) {
			$code = REST_Controller::HTTP_BAD_REQUEST;
		}
		else {
			log_message('error', 'api/admin/citations: ' . $message);
			$code    = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
			$message = 'SERVER_ERROR';
		}

		$this->set_response(array('status' => 'error', 'message' => $message), $code);
	}
}
