<?php
/**
 * Catalog Admin Search Model
 *
 * Clean, maintainable search and filter logic for the admin catalog.
 * Replaces the legacy Catalog_admin_search_model.
 *
 * @package Application/Models
 */
class Catalog_admin_search extends CI_Model
{
	// Database field definitions
	protected $study_fields = array(
		'surveys.type',
		'surveys.id',
		'surveys.repositoryid',
		'idno',
		'title',
		'authoring_entity',
		'nation',
		'dirpath',
		'metafile',
		'link_technical',
		'link_study',
		'link_report',
		'link_indicator',
		'year_start',
		'year_end',
		'link_da',
		'published',
		'surveys.created',
		'changed',
		'surveys.created_by',
		'surveys.changed_by',
		'users.username as created_by_user',
		'users2.username as changed_by_user',
		'forms.model as form_model',
		'surveys.thumbnail',
		'surveys.abstract'
	);

	// Filter parameter mappings: parameter_name => database_field_or_handler
	protected $search_fields = array(
		'keywords' => 'search_keywords',
		'nation'   => 'nation',
		'idno'     => 'idno',
		'title'    => 'title',
		'published' => 'published'
	);

	protected $search_count = 0;

	/** When false, search/filter-option queries are limited to these repository ids (owner or survey_repos link). */
	protected $acl_scope_unrestricted = true;
	protected $acl_repository_allowlist = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Limit study visibility for admin catalog search / filter counts.
	 *
	 * @param bool  $unrestricted When true, no ACL repository filter is applied.
	 * @param array $repository_allowlist Lowercase repositoryid values (ignored when unrestricted).
	 */
	public function set_acl_scope($unrestricted, array $repository_allowlist = array())
	{
		$this->acl_scope_unrestricted = (bool) $unrestricted;
		$this->acl_repository_allowlist = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ($r) {
							return strtolower(trim((string) $r));
						},
						$repository_allowlist
					)
				)
			)
		);
	}

	/**
	 * Apply ACL scope for the logged-in user (session). Returns false if user has no admin catalog study view.
	 */
	public function apply_session_user_acl_scope()
	{
		$CI =& get_instance();
		$CI->load->library('acl_manager');
		$user = $CI->ion_auth->current_user();
		$scope = $CI->acl_manager->get_admin_catalog_repository_scope($user);
		if ($scope === false) {
			$this->set_acl_scope(false, array());
			return false;
		}
		if ($scope === null) {
			$this->set_acl_scope(true, array());
			return true;
		}
		$this->set_acl_scope(false, $scope);
		return true;
	}

	/**
	 * Search surveys with filters
	 *
	 * @param array $options Search parameters (keywords, nation, idno, title, published, tag[], type[], etc.).
	 *                       Optional: exclude_survey_ids (int[]), only_survey_ids (int[]) for dialog use.
	 * @param int $limit Page size
	 * @param int $offset Page offset
	 * @return array Survey results
	 */
	public function search($options = array(), $limit = 15, $offset = 0)
	{
		$this->search_count = $this->count_results($options);

		if ($this->search_count == 0) {
			return array();
		}

		$this->_build_base_query(true, $options);

		// Apply filters
		$this->_apply_filters($options);

		// Apply sorting
		$this->_apply_sorting($options);

		// Add pagination
		$this->db->limit($limit, $offset);

		$results = $this->db->get('surveys')->result_array();
		return $results ?: array();
	}

	/**
	 * Count total results for current search
	 *
	 * @param array $options Search parameters
	 * @return int Total count
	 */
	public function count_results($options = array())
	{
		$this->_build_base_query(false, $options);
		$this->_apply_filters($options);
		return $this->db->count_all_results('surveys');
	}

	/**
	 * Get standardized filter options for UI
	 *
	 * @param string|null $repo_id Repository ID (optional)
	 * @return array Standardized filter options
	 */
	public function get_filter_options($repo_id = null)
	{
		return array(
			'countries'     => $this->_format_countries($repo_id),
			'tags'          => $this->_format_tags($repo_id),
			'data_access'   => $this->_format_data_access_types($repo_id),
			'dataset_types' => $this->_format_dataset_types($repo_id),
			'collections'   => $this->_format_collections($repo_id)
		);
	}

	/**
	 * Get the last search count
	 *
	 * @return int
	 */
	public function get_search_count()
	{
		return $this->search_count;
	}

	// ---------------------------------------------------------------
	// Private Helpers
	// ---------------------------------------------------------------

	/**
	 * Build base query with joins
	 *
	 * @param bool $select_fields Whether to select fields
	 * @param array $options Search options (reserved for base query tweaks)
	 */
	protected function _build_base_query($select_fields = true, $options = array())
	{
		if ($select_fields) {
			$this->db->select(implode(',', $this->study_fields));
		}

		$this->db->join('forms', 'forms.formid = surveys.formid', 'left');
		$this->db->join('users', 'users.id = surveys.created_by', 'left');
		$this->db->join('users as users2', 'users2.id = surveys.changed_by', 'left');
	}

	/**
	 * Apply all filters to query
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_filters($options)
	{
		// Keyword search
		if (isset($options['keywords']) && $options['keywords']) {
			$this->_apply_keyword_filter($options['keywords']);
		}

		// Field searches (nation, idno, title)
		$this->_apply_field_filters($options);

		// Published filter (exact 0/1)
		$this->_apply_published_filter($options);

		// Multi-select filters
		$this->_apply_country_filter($options);
		$this->_apply_tag_filter($options);
		$this->_apply_data_access_filter($options);
		$this->_apply_dataset_type_filter($options);
		$this->_apply_collection_filter($options);

		// ACL: restrict to repositories the user may see (owner or linked collection)
		$this->_apply_acl_scope_filter();

		// Owner filter: surveys.repositoryid (from options['owner_repo'] only)
		$this->_apply_owner_repo_filter($options);

		// Optional ID scoping: exclude list or restrict-to list (for dialogs)
		$this->_apply_survey_id_filters($options);
	}

	/**
	 * Apply optional survey ID filters from options
	 *
	 * - exclude_survey_ids: array of survey IDs to exclude from results (surveys.id NOT IN (...))
	 * - only_survey_ids: array of survey IDs to restrict results to (surveys.id IN (...))
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_survey_id_filters($options)
	{
		if (!empty($options['exclude_survey_ids'])) {
			$ids = array_map('intval', (array) $options['exclude_survey_ids']);
			$ids = array_filter($ids);
			if (!empty($ids)) {
				$this->db->where_not_in('surveys.id', $ids);
			}
		}

		if (!empty($options['only_survey_ids'])) {
			$ids = array_map('intval', (array) $options['only_survey_ids']);
			$ids = array_filter($ids);
			if (!empty($ids)) {
				$this->db->where_in('surveys.id', $ids);
			}
		}
	}

	/**
	 * Apply keyword search filter
	 *
	 * @param string $keywords Search keywords
	 */
	protected function _apply_keyword_filter($keywords)
	{
		$like_str = '%' . $this->db->escape_like_str($keywords) . '%';
		$this->db->where(
			"(surveys.title LIKE '$like_str' OR surveys.idno LIKE '$like_str' OR surveys.authoring_entity LIKE '$like_str')",
			null,
			false
		);
	}

	/**
	 * Apply individual field filters
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_field_filters($options)
	{
		$field_filters = array('nation', 'idno', 'title');

		foreach ($field_filters as $field) {
			if (!isset($options[$field]) || !$options[$field]) {
				continue;
			}

			$value = $options[$field];

			if ($field === 'idno') {
				// Use $this->db->escape for LIKE, allow wildcards
				if (is_array($value)) {
					foreach ($value as $v) {
						if (trim($v)) {
							$like_str = $this->db->escape($v . '%');
							$this->db->where("surveys.idno LIKE $like_str", null, false);
						}
					}
				} else {
					$like_str = $this->db->escape($value . '%');
					$this->db->where("surveys.idno LIKE $like_str", null, false);
				}
			} else if (is_array($value)) {
				$this->_apply_multi_field_filter($field, $value);
			} else {
				$like_str = $this->db->escape('%' . $value . '%');
				$this->db->where("surveys.$field LIKE $like_str", null, false);
			}
		}
	}

	/**
	 * Apply multi-value field filter (e.g., nation[]=X&nation[]=Y)
	 *
	 * @param string $field Field name
	 * @param array $values Values
	 */
	protected function _apply_multi_field_filter($field, $values)
	{
		$where_parts = array();
		foreach ($values as $value) {
			if (trim($value)) {
				$like_str = '%' . $this->db->escape_like_str($value) . '%';
				$where_parts[] = "surveys.$field LIKE '$like_str'";
			}
		}

		if (!empty($where_parts)) {
			$this->db->where('(' . implode(' OR ', $where_parts) . ')', null, false);
		}
	}

	/**
	 * Apply country filter via survey_countries (surveys linked to selected country names)
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_country_filter($options)
	{
		$countries = $options['countries'] ?? null;
		if ($countries === null || $countries === '') {
			return;
		}
		if (is_string($countries)) {
			$countries = array_map('trim', explode(',', $countries));
		}
		if (!is_array($countries)) {
			return;
		}

		$valid = array();
		foreach ($countries as $country) {
			$c = trim($country);
			if ($c !== '') {
				$valid[] = $this->db->escape($c);
			}
		}
		if (empty($valid)) {
			return;
		}

		$in_list = implode(',', $valid);
		$this->db->where("surveys.id IN (SELECT sid FROM survey_countries WHERE country_name IN ($in_list))", null, false);
	}

	/**
	 * Apply tag filter
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_tag_filter($options)
	{
		$tags = $options['tags'] ?? null;
		if ($tags === null || $tags === '') {
			return;
		}
		if (is_string($tags)) {
			$tags = array_map('trim', explode(',', $tags));
		}
		if (!is_array($tags)) {
			return;
		}

		$sql_parts = array();
		foreach ($tags as $tag) {
			if (trim($tag)) {
				$escaped = $this->db->escape($tag);
				$sql_parts[] = "tag = $escaped";
			}
		}

		if (!empty($sql_parts)) {
			$subquery = 'SELECT DISTINCT sid FROM survey_tags WHERE ' . implode(' OR ', $sql_parts);
			$this->db->where("surveys.id IN ($subquery)", null, false);
		}
	}

	/**
	 * Apply data access type filter (by code e.g. direct, licensed)
	 *
	 * @param array $options Search parameters; data_access = array of form codes or comma-separated string
	 */
	protected function _apply_data_access_filter($options)
	{
		$codes = $options['data_access'] ?? null;
		if ($codes === null || $codes === '') {
			return;
		}
		if (is_string($codes)) {
			$codes = array_map('trim', explode(',', $codes));
		}
		if (!is_array($codes)) {
			return;
		}

		$valid = array();
		foreach ($codes as $code) {
			$c = trim((string) $code);
			if ($c !== '') {
				$valid[] = $this->db->escape($c);
			}
		}
		if (empty($valid)) {
			return;
		}

		$in_list = implode(',', $valid);
		$this->db->where("surveys.formid IN (SELECT formid FROM forms WHERE model IN ($in_list))", null, false);
	}

	/**
	 * Apply dataset type filter
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_dataset_type_filter($options)
	{
		$types = $options['dataset_types'] ?? null;
		if ($types === null || $types === '') {
			return;
		}
		if (is_string($types)) {
			$types = array_map('trim', explode(',', $types));
		}
		if (!is_array($types)) {
			return;
		}

		$codes = array();
		foreach ($types as $type) {
			if (trim($type)) {
				$codes[] = $type;
			}
		}

		if (!empty($codes)) {
			$this->db->where_in('surveys.type', $codes);
		}
	}

	/**
	 * Apply published filter (exact match: 0 or 1)
	 *
	 * @param array $options Search parameters (published = '0' | '1' | 0 | 1)
	 */
	protected function _apply_published_filter($options)
	{
		if (!isset($options['published'])) {
			return;
		}
		$value = $options['published'];
		if ($value === '' || $value === null) {
			return;
		}
		$published = (string) $value === '1' || $value === 1 ? 1 : 0;
		$this->db->where('surveys.published', $published);
	}

	/**
	 * Apply collection filter (surveys linked to collections via survey_repos / repositories table)
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_collection_filter($options)
	{
		$collections = $options['collections'] ?? null;

		// Handle comma-separated string
		if (is_string($collections)) {
			$collections = array_map('trim', explode(',', $collections));
		}

		if (!$collections || !is_array($collections)) {
			return;
		}

		// repositoryid is varchar; accept both string and numeric IDs
		$ids = array();
		foreach ($collections as $id) {
			$id = is_string($id) ? trim($id) : $id;
			if ($id !== '' && $id !== null) {
				$ids[] = $id;
			}
		}

		if (!empty($ids)) {
			$escaped = array();
			foreach ($ids as $rid) {
				$escaped[] = $this->db->escape(strtolower((string) $rid));
			}
			$in = implode(',', $escaped);
			$this->db->where(
				"surveys.id IN (SELECT sid FROM survey_repos WHERE LOWER(repositoryid) IN ($in))",
				null,
				false
			);
		}
	}

	/**
	 * Restrict rows to studies the user may see: owner repository in allowlist OR linked via survey_repos.
	 * Owner and collection IDs use case-insensitive comparison (allowlist is normalized to lowercase).
	 */
	protected function _apply_acl_scope_filter()
	{
		if ($this->acl_scope_unrestricted) {
			return;
		}
		$repos = $this->acl_repository_allowlist;
		if (empty($repos)) {
			$this->db->where('1 = 0', null, false);
			return;
		}
		$escaped = array();
		foreach ($repos as $r) {
			$escaped[] = $this->db->escape($r);
		}
		$in_list = implode(',', $escaped);
		$this->db->group_start();
		$this->db->where('LOWER(surveys.repositoryid) IN (' . $in_list . ')', null, false);
		$this->db->or_where('surveys.id IN (SELECT sid FROM survey_repos WHERE LOWER(repositoryid) IN (' . $in_list . '))', null, false);
		$this->db->group_end();
	}

	/**
	 * Limit survey-derived filter stats to ACL scope and optional owner_repo.
	 *
	 * @param string $survey_alias Table alias for surveys (e.g. surveys, s)
	 * @param string|null $repo_id Optional owner_repo from filter_options
	 */
	protected function _apply_surveys_acl_and_owner_scope($survey_alias, $repo_id = null)
	{
		if (!$this->acl_scope_unrestricted) {
			$repos = $this->acl_repository_allowlist;
			if (empty($repos)) {
				$this->db->where('1 = 0', null, false);
				return;
			}
			$escaped = array();
			foreach ($repos as $r) {
				$escaped[] = $this->db->escape($r);
			}
			$in_list = implode(',', $escaped);
			$this->db->group_start();
			$this->db->where('LOWER(' . $survey_alias . '.repositoryid) IN (' . $in_list . ')', null, false);
			$this->db->or_where($survey_alias . '.id IN (SELECT sid FROM survey_repos WHERE LOWER(repositoryid) IN (' . $in_list . '))', null, false);
			$this->db->group_end();
		}
		if ($repo_id && $repo_id !== 'central' && trim($repo_id) !== '') {
			$this->db->where($survey_alias . '.repositoryid', $repo_id);
		}
	}

	/**
	 * Apply owner filter: surveys.repositoryid (owner repository).
	 * Pass owner_repo in search options; empty, null, or central = no filter.
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_owner_repo_filter($options)
	{
		if (!isset($options['owner_repo'])) {
			return;
		}
		$owner = $options['owner_repo'];
		if ($owner === '' || $owner === null || $owner === 'central') {
			return;
		}
		$this->db->where('surveys.repositoryid', $owner);
	}

	/**
	 * Apply sorting to query
	 *
	 * Accepts either:
	 * - options['sort']: single value e.g. title_asc, country_desc, id_asc, modified_desc, created_asc
	 * - options['sort_by'] + options['sort_order']: legacy pair
	 *
	 * Allowed: title_asc/desc, country_asc/desc (nation), id_asc/desc, idno_asc/desc,
	 * modified_asc/desc (changed), created_asc/desc
	 *
	 * @param array $options Search parameters
	 */
	protected function _apply_sorting($options)
	{
		$sort_by   = null;
		$sort_order = 'asc';

		$sort = isset($options['sort']) ? trim((string) $options['sort']) : '';
		if ($sort !== '') {
			$allowed_combos = array(
				'title_asc', 'title_desc',
				'country_asc', 'country_desc',
				'id_asc', 'id_desc',
				'idno_asc', 'idno_desc',
				'modified_asc', 'modified_desc',
				'created_asc', 'created_desc'
			);
			if (in_array($sort, $allowed_combos, true)) {
				list($sort_by, $sort_order) = explode('_', $sort, 2);
			}
		}

		if ($sort_by === null) {
			$sort_by   = $options['sort_by'] ?? 'changed';
			$sort_order = strtolower($options['sort_order'] ?? 'desc');
		}

		// Map UI names to DB columns
		$field_map = array(
			'title'    => 'surveys.title',
			'country'  => 'surveys.nation',
			'nation'   => 'surveys.nation',
			'id'       => 'surveys.id',
			'idno'     => 'surveys.idno',
			'modified' => 'surveys.changed',
			'changed'  => 'surveys.changed',
			'created'  => 'surveys.created',
			'year'     => 'surveys.year_start'
		);

		$db_field = isset($field_map[$sort_by]) ? $field_map[$sort_by] : 'surveys.changed';
		if (!in_array($sort_order, array('asc', 'desc'), true)) {
			$sort_order = 'desc';
		}

		$this->db->order_by($db_field, $sort_order);
	}

	// ---------------------------------------------------------------
	// Local filter data getters (counts for filter options)
	// ---------------------------------------------------------------

	/**
	 * Get all survey countries with counts (scoped by owner repo when provided)
	 *
	 * @param string|null $repo_id Owner repository ID; null/empty/central = all
	 * @return array List of arrays with country_name, total
	 */
	protected function _get_survey_countries($repo_id = null)
	{
		$this->db->select('country_name, COUNT(country_name) AS total');
		$this->db->from('survey_countries');
		$this->db->join('surveys', 'surveys.id = survey_countries.sid', 'inner');
		$this->_apply_surveys_acl_and_owner_scope('surveys', $repo_id);
		$this->db->group_by('country_name');
		$result = $this->db->get();
		return $result ? $result->result_array() : array();
	}

	/**
	 * Get all survey tags with counts (scoped by owner repo when provided)
	 *
	 * @param string|null $repo_id Owner repository ID; null/empty/central = all
	 * @return array List of arrays with tag, total
	 */
	protected function _get_survey_tags($repo_id = null)
	{
		$this->db->select('tag, COUNT(tag) AS total');
		$this->db->from('survey_tags');
		$this->db->join('surveys', 'surveys.id = survey_tags.sid', 'inner');
		$this->_apply_surveys_acl_and_owner_scope('surveys', $repo_id);
		$this->db->group_by('tag');
		$result = $this->db->get();
		return $result ? $result->result_array() : array();
	}

	/**
	 * Get all forms (data access types) for filter options
	 *
	 * @return array List of form rows (formid, fname, model, etc.)
	 */
	protected function _get_forms()
	{
		$result = $this->db->get('forms');
		if (!$result) {
			return array();
		}
		$rows = $result->result_array();
		$out = array();
		foreach ($rows as $row) {
			$out[$row['model']] = $row;
		}
		return $out;
	}

	/**
	 * Get survey counts per formid (for data access type counts), scoped by owner repo
	 *
	 * @param string|null $repo_id Owner repository ID; null/empty/central = all
	 * @return array Map formid => total
	 */
	protected function _get_formid_counts($repo_id = null)
	{
		$this->db->select('formid, COUNT(*) AS total');
		$this->db->from('surveys');
		$this->_apply_surveys_acl_and_owner_scope('surveys', $repo_id);
		$this->db->group_by('formid');
		$result = $this->db->get();
		if (!$result) {
			return array();
		}
		$counts = array();
		foreach ($result->result_array() as $row) {
			$counts[(int) $row['formid']] = (int) $row['total'];
		}
		return $counts;
	}

	/**
	 * Get dataset types with survey counts (scoped by owner repo when provided)
	 *
	 * @param string|null $repo_id Owner repository ID; null/empty/central = all
	 * @return array Map code => array(code, title, weight, found)
	 */
	protected function _get_dataset_types($repo_id = null)
	{
		$this->db->select('survey_types.code, survey_types.title,  COUNT(*) AS found');
		$this->db->from('survey_types');
		$this->db->join('surveys s', 's.type = survey_types.code', 'inner');
		$this->_apply_surveys_acl_and_owner_scope('s', $repo_id);
		$this->db->group_by('survey_types.code, survey_types.title');
		$result = $this->db->get();
		if (!$result) {
			return array();
		}
		$output = array();
		foreach ($result->result_array() as $row) {
			$output[$row['code']] = array(
				'code' => $row['code'],
				'title' => $row['title'],
				'found' => $row['found']
			);
		}
		return $output;
	}

	/**
	 * Get repositories list for collections filter (published, with section, ordered)
	 *
	 * @param bool $published Only published repositories
	 * @return array Map repositoryid => row (with title, section_title, etc.)
	 */
	protected function _get_repositories($published = true)
	{
		$this->db->select('repositories.*, repository_sections.title AS section_title, repository_sections.weight AS section_weight');
		if ($published) {
			$this->db->where('repositories.ispublished', 1);
		}
		$this->db->join('repository_sections', 'repository_sections.id = repositories.section', 'left');
		$this->db->order_by('repository_sections.weight ASC, repositories.weight ASC, repositories.title');
		$query = $this->db->get('repositories');
		if (!$query) {
			return array();
		}
		$result = $query->result_array();
		$repos = array();
		foreach ((array) $result as $row) {
			$repos[$row['repositoryid']] = $row;
		}
		return $repos;
	}

	/**
	 * Distinct visible surveys per repository for collection facets: linked in survey_repos OR owned
	 * (surveys.repositoryid). Matches catalog ACL / owner scope. Keys are lowercase repository ids.
	 *
	 * @param string|null $owner_repo_scope Optional owner_repo for filter_options
	 * @return array Map lowercase repositoryid => int count
	 */
	protected function _get_collection_facet_counts($owner_repo_scope = null)
	{
		$this->db->select('sr.sid AS facet_sid, LOWER(sr.repositoryid) AS facet_rk', false);
		$this->db->from('survey_repos sr');
		$this->db->join('surveys s', 's.id = sr.sid', 'inner');
		$this->_apply_surveys_acl_and_owner_scope('s', $owner_repo_scope);
		$sql_linked = $this->db->get_compiled_select('', false);
		$this->db->reset_query();

		$this->db->select('s.id AS facet_sid, LOWER(s.repositoryid) AS facet_rk', false);
		$this->db->from('surveys s');
		$this->_apply_surveys_acl_and_owner_scope('s', $owner_repo_scope);
		$this->db->where('s.repositoryid IS NOT NULL', null, false);
		$this->db->where("s.repositoryid <> ''", null, false);
		$sql_owned = $this->db->get_compiled_select('', false);
		$this->db->reset_query();

		$sql = 'SELECT facet_rk, COUNT(DISTINCT facet_sid) AS total FROM ('
			. '(' . $sql_linked . ') UNION ALL (' . $sql_owned . ')'
			. ') facet_union GROUP BY facet_rk';

		$query = $this->db->query($sql);
		if (!$query) {
			return array();
		}
		$out = array();
		foreach ($query->result_array() as $row) {
			$row = array_change_key_case($row, CASE_LOWER);
			$key = strtolower(trim((string) ($row['facet_rk'] ?? '')));
			if ($key === '') {
				continue;
			}
			$out[$key] = (int) ($row['total'] ?? 0);
		}
		return $out;
	}

	/**
	 * Repository rows for facet labels (any publish state), matched case-insensitively.
	 *
	 * @param array $lowercase_ids Normalized lowercase repositoryid values
	 * @return array Map lowercase repositoryid => row
	 */
	protected function _get_repository_rows_by_lowercase_ids(array $lowercase_ids)
	{
		$lowercase_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ($id) {
							return strtolower(trim((string) $id));
						},
						$lowercase_ids
					)
				)
			)
		);
		if (empty($lowercase_ids)) {
			return array();
		}
		$escaped = array();
		foreach ($lowercase_ids as $id) {
			$escaped[] = $this->db->escape($id);
		}
		$in = implode(',', $escaped);
		$this->db->select('repositories.*, repository_sections.title AS section_title, repository_sections.weight AS section_weight');
		$this->db->from('repositories');
		$this->db->join('repository_sections', 'repository_sections.id = repositories.section', 'left');
		$this->db->where('LOWER(repositories.repositoryid) IN (' . $in . ')', null, false);
		$this->db->order_by('repository_sections.weight ASC, repositories.weight ASC, repositories.title');
		$query = $this->db->get();
		$result = $query ? $query->result_array() : array();
		$by_lower = array();
		foreach ((array) $result as $row) {
			$by_lower[strtolower((string) $row['repositoryid'])] = $row;
		}
		return $by_lower;
	}

	// ---------------------------------------------------------------
	// Format filter options for API
	// ---------------------------------------------------------------

	/**
	 * Format countries for API response (from survey_countries.country_name to match _apply_country_filter)
	 *
	 * @param string|null $repo_id Repository ID (owner_repo) to scope counts
	 * @return array Standardized country list
	 */
	protected function _format_countries($repo_id = null)
	{
		$raw_countries = $this->_get_survey_countries($repo_id);

		$formatted = array();
		foreach ((array) $raw_countries as $row) {
			if (empty($row['country_name'])) {
				continue;
			}
			$formatted[] = array(
				'id'    => $row['country_name'],
				'name'  => $row['country_name'],
				'count' => (int) ($row['total'] ?? 0)
			);
		}

		return $formatted;
	}

	/**
	 * Format tags for API response
	 *
	 * @param string|null $repo_id Repository ID
	 * @return array Standardized tag list
	 */
	protected function _format_tags($repo_id = null)
	{
		$raw_tags = $this->_get_survey_tags($repo_id);

		$formatted = array();
		foreach ((array) $raw_tags as $row) {
			// Skip null/empty tags
			if (empty($row['tag'])) {
				continue;
			}

			$formatted[] = array(
				'id'    => $row['tag'],
				'name'  => $row['tag'],
				'count' => (int) ($row['total'] ?? 0)
			);
		}

		return $formatted;
	}

	/**
	 * Format data access types for API response (with survey counts per formid)
	 *
	 * @param string|null $repo_id Optional owner_repo to scope counts
	 * @return array Standardized data access type list
	 */
	protected function _format_data_access_types($repo_id = null)
	{
		$raw_types = $this->_get_forms();
		$counts = $this->_get_formid_counts($repo_id);

		$formatted = array();
		foreach ((array) $raw_types as $row) {
			$formid = (int) $row['formid'];
			$code   = $row['model'];
			$count  = isset($counts[$formid]) ? $counts[$formid] : 0;
			// Counts are ACL/owner-scoped via _get_formid_counts; omit types with no visible surveys (same as collections)
			if ($count < 1) {
				continue;
			}
			$formatted[] = array(
				'id'    => $code,
				'name'  => $row['fname'],
				'code'  => $code,
				'count' => $count
			);
		}

		return $formatted;
	}

	/**
	 * Format dataset types for API response
	 *
	 * @param string|null $repo_id Repository ID
	 * @return array Standardized dataset type list
	 */
	protected function _format_dataset_types($repo_id = null)
	{
		$raw_types = $this->_get_dataset_types($repo_id);

		$formatted = array();
		foreach ((array) $raw_types as $key => $row) {
			if (is_array($row)) {
				$formatted[] = array(
					'id'    => $row['code'] ?? $key,
					'name'  => $row['title'] ?? $key,
					'code'  => $row['code'] ?? $key,
					'count' => (int) ($row['found'] ?? 0)
				);
			}
		}

		return $formatted;
	}

	/**
	 * Format collections for API response (repositories the user may facet on).
	 * Counts match visible studies: survey_repos link OR owner repository, ACL/owner-scoped.
	 *
	 * @param string|null $repo_id Repository ID (owner_repo scope for filter_options)
	 * @return array Standardized collection list with survey count per collection
	 */
	protected function _format_collections($repo_id = null)
	{
		$counts = $this->_get_collection_facet_counts($repo_id);

		$formatted = array();

		if ($this->acl_scope_unrestricted) {
			$raw_collections = $this->_get_repositories(true);
			foreach ((array) $raw_collections as $repo) {
				$rid = $repo['repositoryid'];
				$c = $counts[strtolower((string) $rid)] ?? 0;
				if ($c < 1) {
					continue;
				}
				$formatted[] = array(
					'id'    => $rid,
					'name'  => $repo['title'],
					'code'  => $rid,
					'count' => $c
				);
			}
			return $formatted;
		}

		$need_meta = array();
		foreach ($this->acl_repository_allowlist as $allowed) {
			$low = strtolower((string) $allowed);
			$c = $counts[$low] ?? 0;
			if ($c < 1) {
				continue;
			}
			$need_meta[] = $low;
		}
		if (empty($need_meta)) {
			return array();
		}

		$meta_by_lower = $this->_get_repository_rows_by_lowercase_ids($need_meta);
		foreach ($need_meta as $low) {
			if (!isset($meta_by_lower[$low])) {
				continue;
			}
			$repo = $meta_by_lower[$low];
			$rid  = $repo['repositoryid'];
			$c    = $counts[$low];
			$formatted[] = array(
				'id'    => $rid,
				'name'  => $repo['title'],
				'code'  => $rid,
				'count' => $c
			);
		}

		usort(
			$formatted,
			static function ($a, $b) use ($meta_by_lower) {
				$ma = $meta_by_lower[strtolower((string) $a['id'])] ?? array();
				$mb = $meta_by_lower[strtolower((string) $b['id'])] ?? array();
				$swa = (int) ($ma['section_weight'] ?? 0);
				$swb = (int) ($mb['section_weight'] ?? 0);
				if ($swa !== $swb) {
					return $swa - $swb;
				}
				$wa = (int) ($ma['weight'] ?? 0);
				$wb = (int) ($mb['weight'] ?? 0);
				if ($wa !== $wb) {
					return $wa - $wb;
				}
				return strcasecmp($a['name'], $b['name']);
			}
		);

		return $formatted;
	}
}
