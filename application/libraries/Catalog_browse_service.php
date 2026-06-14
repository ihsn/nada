<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public catalog browse/search logic shared by the HTML catalog and JSON API.
 * Mirrors {@see Catalog::_search()}, facet loading, and repository context.
 */
class Catalog_browse_service {

	/** @var CI_Controller */
	protected $CI;

	public $active_repo = null;
	public $active_repo_id = null;
	public $active_tab = '';
	public $facets = array();
	public $enabled_filters = array();
	public $filters_list = array();
	public $regional_search = 'no';
	public $collection_search = 'no';
	public $da_search = 'no';
	public $data_types_nav_bar = null;
	public $search_box_orientation = 'default';

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->helper('security');
		$this->CI->load->model('Search_helper_model');
		$this->CI->load->model('Catalog_model');
		$this->CI->load->model('Repository_model');
		$this->CI->load->model('Form_model');
		$this->CI->load->model('Data_classification_model');
		$this->CI->load->model('Facet_model');
		$this->CI->load->config('facets');
		$this->CI->lang->load('catalog_search');
		$this->filters_list = array_keys($this->CI->Facet_model->select_all());
		$this->regional_search = ($this->CI->config->item('regional_search') === false) ? 'no' : $this->CI->config->item('regional_search');
		$this->collection_search = ($this->CI->config->item('collection_search') === false) ? 'no' : $this->CI->config->item('collection_search');
		$this->da_search = ($this->CI->config->item('da_search') === false) ? 'no' : $this->CI->config->item('da_search');
		$this->data_types_nav_bar = $this->CI->config->item('data_types_nav_bar');
		$sb = $this->CI->config->item('search_box_orientation');
		$this->search_box_orientation = ($sb === false) ? 'default' : $sb;
	}

	public function set_active_repo($repo)
	{
		$this->CI->load->model('repository_model');
		$repo = trim(strtolower((string) $repo));
		$repositories = $this->CI->Catalog_model->get_repository_array();
		$repositories[] = 'central';
		foreach ($repositories as $key => $value) {
			$repositories[$key] = strtolower($value);
		}

		if ( ! in_array($repo, $repositories, true)) {
			return;
		}

		if ($repo === 'central') {
			$this->active_repo = null;
			$this->active_repo_id = null;
		} else {
			$this->active_repo = $this->CI->repository_model->get_repository_by_repositoryid($repo);
			$this->active_repo_id = $this->active_repo['repositoryid'];
		}
	}

	public function validate_tab_type($tab_type)
	{
		if (empty($tab_type)) {
			return '';
		}
		$allowed_types = $this->CI->Search_helper_model->get_dataset_types($this->active_repo_id);
		$allowed_type_codes = array_keys($allowed_types);
		$allowed_type_codes[] = 'all';
		$tab_type_lower = strtolower(trim((string) $tab_type));
		$allowed_type_codes = array_map('strtolower', $allowed_type_codes);
		if (in_array($tab_type_lower, $allowed_type_codes, true)) {
			return $tab_type_lower;
		}
		return '';
	}

	protected function get_active_data_type()
	{
		if ($this->active_tab === '') {
			return 'all';
		}
		return $this->active_tab;
	}

	public function set_enabled_filters()
	{
		$tab = $this->get_active_data_type();
		if ($tab === 'survey') {
			$tab = 'microdata';
		}
		$filters_config = $this->CI->config->item('facets_' . $tab);
		if ($filters_config === false || $filters_config === null || $filters_config === '') {
			$filters_config = $this->CI->config->item('facets_all');
		}
		$this->enabled_filters = (array) json_decode($filters_config, true);
	}

	/**
	 * Full sidebar facet vocabulary (countries, tags, user facet values, etc.).
	 */
	public function load_facets_context()
	{
		$this->set_enabled_filters();
		$this->load_facets_data();
	}

	/**
	 * Minimal facet registry for search when sidebar data is not loaded.
	 * Keeps user facet query params and tab type labels working.
	 */
	protected function load_search_facet_registry()
	{
		$this->facets = array();
		$repo_id = '';
		if (isset($this->active_repo['repositoryid'])) {
			$repo_id = $this->active_repo['repositoryid'];
		}

		$this->facets['types'] = $this->CI->Search_helper_model->get_dataset_types($repo_id);

		$facets_list = $this->CI->Facet_model->select_all();
		foreach ($facets_list as $fc) {
			if ($fc['facet_type'] === 'user') {
				$this->facets[$fc['name']] = array(
					'type' => $fc['facet_type'],
					'title' => $fc['title'],
				);
			}
		}
	}

	public function load_facets_data()
	{
		$this->facets = array();
		$repo_id = '';
		if (isset($this->active_repo['repositoryid'])) {
			$repo_id = $this->active_repo['repositoryid'];
		}

		$this->facets['years'] = $this->CI->Search_helper_model->get_min_max_years();
		$this->facets['repositories'] = $this->CI->Search_helper_model->get_active_repositories(
			$this->active_tab,
			$this->CI->input->get('collection')
		);

		$this->facets['regions'] = array();
		if ($this->is_facet_enabled($this->active_tab, 'region')) {
			$this->facets['regions'] = $this->CI->Search_helper_model->get_active_regions(
				$repo_id,
				$this->active_tab,
				$this->CI->input->get('region')
			);
		}

		$this->facets['da_types'] = $this->CI->Search_helper_model->get_active_data_types(
			$repo_id,
			$this->active_tab,
			$this->CI->input->get('dtype')
		);

		$this->facets['data_class'] = $this->CI->Search_helper_model->get_active_data_classifications($repo_id);

		$this->facets['databases'] = $this->CI->Search_helper_model->get_active_databases(
			$repo_id,
			$this->active_tab
		);

		$this->facets['countries'] = $this->CI->Search_helper_model->get_active_countries(
			$repo_id,
			$this->active_tab,
			$this->CI->input->get('country')
		);

		$this->facets['tags'] = $this->CI->Search_helper_model->get_active_tags(
			$repo_id,
			$this->active_tab,
			$this->CI->input->get('tag')
		);

		$this->facets['types'] = $this->CI->Search_helper_model->get_dataset_types($repo_id);

		$facets_list = $this->CI->Facet_model->select_all();
		foreach ($facets_list as $fc) {
			if ($fc['facet_type'] === 'user') {
				$this->facets[$fc['name']] = array(
					'type' => $fc['facet_type'],
					'title' => $fc['title'],
					'values' => $this->CI->Facet_model->get_facet_values(
						$fc['id'],
						1,
						'value',
						'ASC',
						$this->active_tab,
						$this->CI->input->get($fc['name'])
					),
				);
			}
		}
	}

	protected function is_facet_enabled($type, $facet)
	{
		if ($type === '') {
			$type = 'all';
		}
		return in_array($facet, $this->enabled_filters, true);
	}

	public function get_page_size()
	{
		$page_size_min = 15;
		$page_size_max = 100;
		$page_size = (int) $this->CI->input->get('ps');
		if ($page_size >= $page_size_min && $page_size <= $page_size_max) {
			return $page_size;
		}
		return 15;
	}

	protected function is_numeric_array($arr_str)
	{
		$arr = explode(',', $arr_str);
		foreach ($arr as $val) {
			if ( ! is_numeric($val)) {
				return '';
			}
		}
		return $arr_str;
	}

	/**
	 * Parse facet filter from query string (comma-separated or array) for catalog_search.
	 *
	 * @param mixed $raw
	 * @return array
	 */
	protected function parse_multi_value_param($raw)
	{
		if ($raw === false || $raw === null || $raw === '') {
			return array();
		}
		if (is_array($raw)) {
			return array_values(array_filter(array_map('trim', $raw), 'strlen'));
		}
		return array_values(array_filter(array_map('trim', explode(',', (string) $raw)), 'strlen'));
	}

	/**
	 * @param bool $load_facets When false, skip sidebar facet DB queries (catalog_browse search-only).
	 */
	public function run_search($load_facets = true)
	{
		$keywords = trim((string) xss_clean($this->CI->input->get('sk')));

		if ($load_facets) {
			$this->load_facets_context();
		} else {
			$this->set_enabled_filters();
			$this->load_search_facet_registry();
		}

		$search_options = new StdClass();
		$limit = $this->get_page_size();

		$search_options->collection = xss_clean($this->CI->input->get('collection'));
		if ( ! is_array($search_options->collection)) {
			if ($search_options->collection === false || $search_options->collection === null || $search_options->collection === '') {
				$search_options->collection = array();
			} else {
				$search_options->collection = array($search_options->collection);
			}
		}
		$search_options->sk = $keywords;
		$search_options->vk = trim(xss_clean($this->CI->input->get('vk')));
		$search_options->vf = xss_clean($this->CI->input->get('vf'));
		$search_options->country = xss_clean($this->CI->input->get('country'));
		$search_options->region = xss_clean($this->CI->input->get('region'));
		$search_options->view = xss_clean($this->CI->input->get('view'));
		$search_options->image_view = xss_clean($this->CI->input->get('image_view'));
		$search_options->topic = xss_clean($this->CI->input->get('topic'));
		$search_options->from = (int) xss_clean($this->CI->input->get('from'));
		$search_options->to = (int) xss_clean($this->CI->input->get('to'));
		if ($search_options->from > 0 && $search_options->to > 0 && $search_options->from > $search_options->to) {
			$tmp = $search_options->from;
			$search_options->from = $search_options->to;
			$search_options->to = $tmp;
		}
		$search_options->sort_by = xss_clean($this->CI->input->get('sort_by'));
		$search_options->sort_order = xss_clean($this->CI->input->get('sort_order'));
		$search_options->page = (int) xss_clean($this->CI->input->get('page'));
		$search_options->page = ($search_options->page > 0) ? $search_options->page : 1;
		$search_options->dtype = xss_clean($this->CI->input->get('dtype'));
		$search_options->data_class = xss_clean($this->CI->input->get('data_class'));
		$search_options->ts_database = xss_clean($this->CI->input->get('database'));
		$search_options->tag = xss_clean($this->CI->input->get('tag'));
		$search_options->sid = $this->is_numeric_array(xss_clean($this->CI->input->get('sid')));
		$search_options->type = xss_clean($this->CI->input->get('type'));
		$search_options->country_iso3 = xss_clean($this->CI->input->get('country_iso3'));
		$search_options->tab_type = $this->validate_tab_type(xss_clean((string) $this->CI->input->get('tab_type')));
		$search_options->repo = xss_clean($this->active_repo_id);
		$search_options->ps = $limit;
		$offset = ($search_options->page - 1) * $limit;

		if ($this->CI->config->item('catalog_variable_view') !== 'yes' && $search_options->view === 'v') {
			throw new RuntimeException('Variable search view is not available');
		}

		foreach ($this->facets as $facet_key => $facet) {
			if (isset($facet['type']) && $facet['type'] === 'user') {
				$search_options->{$facet_key} = xss_clean($this->CI->input->get($facet_key));
			}
		}

		$allowed_fields = array('year', 'title', 'nation', 'country', 'popularity', 'rank', 'relevance');
		$allowed_order = array('asc', 'desc');
		if ( ! in_array(trim((string) $search_options->sort_by), $allowed_fields, true)) {
			$search_options->sort_by = '';
		}
		if ( ! in_array($search_options->sort_order, $allowed_order, true)) {
			$search_options->sort_order = '';
		}

		if ($this->CI->input->get('sk') && isset($this->CI->db_logger)) {
			$this->CI->db_logger->write_log('search', $this->CI->input->get('sk') . '/' . $this->CI->input->get('vk'), 'sk-vk');
		}

		$data['repositories'] = $this->CI->Search_helper_model->get_repositories_list(1);

		if (is_array($search_options->country) && count($search_options->country) > 0) {
			$data['countries'] = $this->CI->Search_helper_model->get_countries_list($search_options->country);
		}

		$data['tags'] = array();
		$data['active_repo'] = $this->active_repo;
		$data['active_repo_id'] = $this->active_repo_id;

		if ($search_options->tab_type !== '') {
			$search_options->type = $search_options->tab_type;
		} else {
			$search_options->type = $this->CI->Search_helper_model->filter_catalog_type_param($search_options->type);
		}

		$variable_keywords = $search_options->sk;
		if ($search_options->view === 'v' && $search_options->vk !== '') {
			$variable_keywords = $search_options->vk;
		}

		$this->CI->load->library('catalog_study_sort');
		$ft_for_sort = ($search_options->view === 'v')
			? trim((string) $variable_keywords)
			: trim((string) $search_options->sk);
		list($search_options->sort_by, $search_options->sort_order) = Catalog_study_sort::resolve(
			$ft_for_sort,
			$search_options->sort_by,
			$search_options->sort_order,
			$this->CI->config->item('catalog_default_sort_by'),
			$this->CI->config->item('catalog_default_sort_order')
		);

		$params = array(
			'collections' => $search_options->collection,
			'study_keywords' => $search_options->sk,
			'variable_keywords' => $variable_keywords,
			'variable_fields' => $search_options->vf,
			'countries' => $this->parse_multi_value_param($search_options->country),
			'regions' => $this->parse_multi_value_param($search_options->region),
			'from' => $search_options->from,
			'to' => $search_options->to,
			'tags' => $this->parse_multi_value_param($search_options->tag),
			'sort_by' => $search_options->sort_by,
			'sort_order' => $search_options->sort_order,
			'repo' => $search_options->repo,
			'dtype' => $this->parse_multi_value_param($search_options->dtype),
			'data_class' => $this->parse_multi_value_param($search_options->data_class),
			'database' => $this->parse_multi_value_param($search_options->ts_database),
			'sid' => $search_options->sid,
			'type' => $search_options->type,
			'country_iso3' => $search_options->country_iso3,
		);

		foreach ($this->facets as $facet_key => $facet) {
			if (isset($facet['type']) && $facet['type'] === 'user') {
				$params[$facet_key] = $this->parse_multi_value_param(
					xss_clean($this->CI->input->get($facet_key))
				);
			}
		}

		// Variable view always uses DB search (semantic/fulltext drivers do not support vsearch).
		if ($search_options->view === 'v') {
			$params['search_provider'] = 'db';
		}

		$this->CI->load->library('catalog_search', $params);
		$data['is_regional_search'] = $this->regional_search;

		if ($search_options->view === 'v') {
			$data['variables'] = $this->CI->catalog_search->vsearch($limit, $offset);
			$data['search_type'] = 'variable';
		} else {
			$data['surveys'] = $this->CI->catalog_search->search($limit, $offset);
			$data['search_type'] = 'study';
		}

		$data['current_page'] = $search_options->page;
		$data['search_options'] = $search_options;
		$data['data_access_types'] = $this->facets['da_types'];
		$data['data_classifications'] = $this->facets['data_class'];
		$data['databases'] = $this->facets['databases'];
		$data['regions'] = $this->facets['regions'];
		$data['sid'] = $search_options->sid;

		if (isset($data['surveys']['found'], $data['surveys']['total']) && $data['surveys']['found'] == $data['surveys']['total']) {
			$data['featured_studies'] = $this->CI->Repository_model->get_featured_study($this->active_repo_id, $this->active_tab);
		}

		if (isset($data['surveys']['rows'])) {
			$sid_arr = array_values(array_column($data['surveys']['rows'], 'id'));
			$data['related_collections'] = $this->CI->Search_helper_model->related_collections($sid_arr);
		}

		return $data;
	}

	public function build_tabs($data)
	{
		$tabs = array();
		$tabs['types'] = isset($this->facets['types']) ? $this->facets['types'] : array();
		if (isset($data['variables'])) {
			$tabs['search_counts_by_type'] = array();
			$tabs['active_tab'] = 'survey';
		} else {
			if (isset($data['surveys']['search_counts_by_type']) && ! empty($data['surveys']['search_counts_by_type'])) {
				$tabs['search_counts_by_type'] = $data['surveys']['search_counts_by_type'];
			} else {
				$tabs['search_counts_by_type'] = array(
					'survey' => isset($data['surveys']['found']) ? $data['surveys']['found'] : 0,
				);
			}
			$tabs['active_tab'] = $this->validate_tab_type(xss_clean((string) $this->CI->input->get('tab_type')));
		}
		return $tabs;
	}

	public function active_repo_for_client()
	{
		if (empty($this->active_repo) || ! is_array($this->active_repo)) {
			return null;
		}
		$thumb = isset($this->active_repo['thumbnail']) ? $this->active_repo['thumbnail'] : '';
		return array(
			'repositoryid' => $this->active_repo['repositoryid'],
			'title' => isset($this->active_repo['title']) ? $this->active_repo['title'] : '',
			'short_text' => isset($this->active_repo['short_text']) ? $this->active_repo['short_text'] : '',
			'thumbnail' => $thumb,
		);
	}

	public function site_config_for_client()
	{
		$show_abstract = $this->CI->config->item('catalog_show_abstract');
		$search_provider = $this->CI->config->item('search_provider');
		$this->CI->load->helper('catalog');
		return array(
			'data_types_nav_bar' => $this->data_types_nav_bar,
			'search_box_orientation' => $this->search_box_orientation,
			'regional_search' => $this->regional_search,
			'collection_search' => $this->collection_search,
			'da_search' => $this->da_search,
			'catalog_variable_view' => $this->CI->config->item('catalog_variable_view'),
			'catalog_show_abstract' => ($show_abstract === false) ? 'yes' : $show_abstract,
			'search_provider' => ($search_provider === false || $search_provider === null) ? 'db' : $search_provider,
			'catalog_public_search_ui' => catalog_public_search_ui(),
			'catalog_search_debug' => catalog_search_debug_enabled(),
		);
	}
}
