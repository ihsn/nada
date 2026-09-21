<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI), study search API driver
 *
 * Used when semantic_search_engine = opensearch. The Qdrant engine keeps using catalog_search_semantic.
 *
 * The search is done by nada-ai's engine-agnostic study search, POST /studies/search:
 *   1. NADA translates its request (keywords, sidebar filters, sort, page) into the API request. NADA resolves
 *      vocabulary (country names, ISO codes, regions, form codes, topics, timeseries databases) into ids; the
 *      API only matches ids.
 *   2. The API returns one page of studies as internal study ids (surveys.id), plus the number of studies found
 *      and the per-type tab counts. Both describe the relevant studies, not the whole filter universe.
 *   3. NADA loads the rows of that page from its own database by id, in the order the API returned them, so the
 *      cards look the same whatever the engine.
 *
 * The API result is the source of truth for found, tab counts and order; the database only supplies row data.
 * A hit whose row is missing or whose idno differs from the API's (the index is out of sync with this catalog)
 * is left out of the page and reported in semantic_note.
 *
 * When nada-ai cannot be reached or fails on its side (timeout, HTTP 5xx or 429), the page is served by the
 * database search and result.semantic_fallback says so. A request the API rejects (HTTP 4xx: bad key, invalid
 * filters, an engine without the study search) is a configuration or contract error and is raised, not hidden.
 *
 * Variable search (vsearch / v_quick_search) is not part of the study search API and uses the database search.
 */

if (! class_exists('Catalog_country_resolver', false)) {
    require_once dirname(__FILE__) . '/Catalog_country_resolver.php';
}
if (! class_exists('Catalog_filter_guard', false)) {
    require_once dirname(__FILE__) . '/Catalog_filter_guard.php';
}
require_once dirname(__FILE__) . '/Semantic_search_api_exception.php';

class catalog_search_semantic_studies
{
    // -------------------------------------------------------------------------
    // Search parameters — set by Catalog_search before calling search()
    // -------------------------------------------------------------------------
    public $study_keywords    = '';
    public $variable_keywords = '';
    public $topics            = [];
    public $tags              = [];
    public $countries         = [];
    public $regions           = [];
    public $from              = 0;
    public $to                = 0;
    public $repo              = '';
    public $type              = [];
    public $data_class        = [];
    public $collections       = [];
    public $dtype             = [];
    public $database          = [];
    public $sid               = '';
    public $created           = '';
    public $country_iso3      = '';
    public $sort_by           = 'title';
    public $sort_order        = 'asc';

    /** @var array Full search params including user-defined facet keys. */
    private $params      = [];

    /** @var array User-defined facets from Facet_model. */
    private $user_facets = [];

    private $ci;
    private $api_url;
    private $api_key;
    private $admin_api_key;
    private $timeout;
    private $debug;

    /** Largest page the study search API serves (contract: limit <= 100). */
    private const API_MAX_PAGE_SIZE = 100;

    /** Largest id list the API accepts in filters.sids. */
    private const API_MAX_SIDS = 5000;

    /** Maximum CURLOPT_TIMEOUT for the nada-ai search request (seconds) */
    private const API_MAX_TIMEOUT_SEC = 15;

    /** NADA sort_by values -> API sort fields. */
    private const SORT_FIELDS = [
        'rank'        => 'relevance',
        'relevance'   => 'relevance',
        'title'       => 'title',
        'country'     => 'nation',
        'nation'      => 'nation',
        'year'        => 'year',
        'proddate'    => 'year',
        'popularity'  => 'popularity',
        'total_views' => 'popularity',
        'created'     => 'created',
        'changed'     => 'changed',
    ];

    // Fields returned by the row load — the row shape of Catalog_search_mysql::search()
    private static $survey_fields =
        'surveys.id as id, surveys.type, surveys.idno as idno, surveys.doi, surveys.title,
         surveys.subtitle, surveys.nation, surveys.authoring_entity,
         forms.model as form_model, surveys.data_class_id, surveys.year_start, surveys.year_end,
         surveys.thumbnail,
         surveys.repositoryid as repositoryid, surveys.link_da, repositories.title as repo_title,
         surveys.created, surveys.changed, surveys.total_views, surveys.total_downloads, surveys.varcount,
         surveys.ts_dimensions, surveys.ts_frequency, surveys.ts_data_count, surveys.abstract,
         tsdb.id as ts_db_study_id, tsdb.title as ts_db_title';

    /** @var catalog_search_mysql|null */
    private $db_driver = null;

    public function __construct(array $params = [])
    {
        $this->ci = & get_instance();
        $this->ci->config->load('semantic_search');

        $this->api_url       = rtrim((string) $this->ci->config->item('semantic_search_url'), '/');
        $this->api_key       = (string) $this->ci->config->item('semantic_search_api_key');
        $this->admin_api_key = (string) $this->ci->config->item('semantic_search_admin_api_key');
        $timeout             = (int) $this->ci->config->item('semantic_search_timeout');
        $this->timeout       = min(max(1, $timeout > 0 ? $timeout : self::API_MAX_TIMEOUT_SEC), self::API_MAX_TIMEOUT_SEC);
        $this->debug         = filter_var($this->ci->config->item('semantic_search_debug'), FILTER_VALIDATE_BOOLEAN);

        // same default sort as the database search
        if ($this->ci->config->item('regional_search') == 'yes') {
            $this->sort_by = 'nation';
        }

        $this->ci->load->model('Facet_model');
        $this->user_facets = $this->ci->Facet_model->select_all('user');
        $this->params      = $params;

        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // =========================================================================
    // Public interface
    // =========================================================================

    public function search(int $limit = 15, int $offset = 0): array
    {
        if ($limit > self::API_MAX_PAGE_SIZE) {
            throw new RuntimeException(
                sprintf('Semantic search serves at most %d studies per page (requested %d).', self::API_MAX_PAGE_SIZE, $limit)
            );
        }

        $t0      = microtime(true);
        $filters = $this->build_filters();

        if ($filters === null) {
            // a filter was given whose values match nothing: no study can match, nothing to ask the API
            return $this->result([], [], 0, [], $limit, $offset);
        }

        $body = $this->build_request($filters, $limit, $offset);

        try {
            $response = $this->call_api($body);
        } catch (Semantic_search_api_exception $e) {
            if (!$this->is_outage($e)) {
                throw $e;
            }
            return $this->search_in_database_after($e, $limit, $offset);
        }

        $hits = $response['hits'];
        list($rows, $dropped) = $this->load_rows($hits);

        $notes = [];
        foreach ($response['warnings'] ?? [] as $warning) {
            $notes[] = (string) ($warning['message'] ?? '');
        }
        if ($dropped > 0) {
            $notes[] = sprintf(
                '%d result(s) could not be shown: the search index is out of sync with this catalog. Re-index the affected studies.',
                $dropped
            );
        }

        $result = $this->result(
            $rows,
            $response['search_counts_by_type'],
            (int) $response['found'],
            array_filter($notes, 'strlen'),
            $limit,
            $offset
        );

        if ($this->debug) {
            $result['debug'] = [
                'request'         => $body,
                'engine'          => $response['engine'] ?? null,
                'applied'         => $response['applied'] ?? null,
                'truncated'       => $response['truncated'] ?? null,
                'result_cap'      => $response['result_cap'] ?? null,
                'timing_ms'       => $response['timing_ms'] ?? null,
                'api_debug'       => $response['debug'] ?? null,
                'hits'            => count($hits),
                'rows_loaded'     => count($rows),
                'dropped'         => $dropped,
                'counts_source'   => 'search_api',
                'elapsed'         => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    public function vsearch(int $limit = 15, int $offset = 0): array
    {
        return $this->database_search()->vsearch($limit, $offset);
    }

    public function v_quick_search(?int $sid = null, int $limit = 50, int $offset = 0): array
    {
        return $this->database_search()->v_quick_search($sid, $limit, $offset);
    }

    // =========================================================================
    // Request building
    // =========================================================================

    private function build_request(array $filters, int $limit, int $offset): array
    {
        $body = [
            'mode'   => 'auto',
            'limit'  => $limit,
            'offset' => $offset,
        ];

        $keywords = trim((string) $this->study_keywords);
        if ($keywords !== '') {
            $body['query'] = $keywords;
        }

        if (!empty($filters)) {
            $body['filters'] = $filters;
        }

        $sort = $this->build_sort();
        if ($sort !== null) {
            $body['sort'] = $sort;
        }

        // include_debug needs the admin role, so it is only asked for when an admin key is configured
        if ($this->debug && $this->admin_api_key !== '') {
            $body['include_debug'] = true;
        }

        return $body;
    }

    /**
     * @return array{by: string, order: string}|null null = the API default (relevance with a keyword, title without)
     */
    private function build_sort(): ?array
    {
        $key = strtolower(trim((string) $this->sort_by));
        if (!array_key_exists($key, self::SORT_FIELDS)) {
            return null;
        }

        // relevance needs a keyword; without one the database search sorts by title, which is the API default
        if (self::SORT_FIELDS[$key] === 'relevance' && trim((string) $this->study_keywords) === '') {
            return null;
        }

        $order = strtolower(trim((string) $this->sort_order));

        return [
            'by'    => self::SORT_FIELDS[$key],
            'order' => $order === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Map the NADA search parameters to the API filters object.
     *
     * Values within a filter are any-of, filters combine with AND, and a filter whose values are all
     * unresolvable matches nothing (fail closed, like the database search).
     *
     * @return array<string, mixed>|null null when the filters can match no study at all
     */
    private function build_filters(): ?array
    {
        $filters = [];

        $types = $this->normalise_array($this->type);
        $types = array_values(array_filter($types, static fn($t) => strtolower($t) !== 'all'));
        if (!empty($types)) {
            $filters['types'] = $types;
        }

        $countries = $this->resolve_country_ids();
        if ($countries === false) {
            return null;
        }
        if (!empty($countries)) {
            $filters['countries'] = $countries;
        }

        $sids = $this->resolve_sids();
        if ($sids === false) {
            return null;
        }
        if (!empty($sids)) {
            if (count($sids) > self::API_MAX_SIDS) {
                throw new RuntimeException(
                    sprintf('The topic/database/sid filters select %d studies; semantic search accepts at most %d.', count($sids), self::API_MAX_SIDS)
                );
            }
            $filters['sids'] = $sids;
        }

        $data_class = $this->resolve_int_list($this->data_class);
        if ($data_class === false) {
            return null;
        }
        if (!empty($data_class)) {
            $filters['data_class_ids'] = $data_class;
        }

        $form_ids = $this->resolve_form_ids();
        if ($form_ids === false) {
            return null;
        }
        if (!empty($form_ids)) {
            $filters['form_ids'] = $form_ids;
        }

        $tags = $this->normalise_array($this->tags);
        if (!empty($tags)) {
            $filters['tags'] = $tags;
        }

        $collections = $this->normalise_array($this->collections);
        if (!empty($collections)) {
            $filters['collections'] = $collections;
        }

        $repo = trim((string) $this->repo);
        if ($repo !== '' && $repo !== 'central') {
            $filters['repository'] = $repo;
        }

        $years = $this->build_year_bounds();
        $filters = array_merge($filters, $years);

        $created = $this->build_created_bounds();
        if ($created === false) {
            return null;
        }
        $filters = array_merge($filters, $created);

        $facets = $this->resolve_user_facets();
        if ($facets === false) {
            return null;
        }
        if (!empty($facets)) {
            $filters['facets'] = $facets;
        }

        return $filters;
    }

    /**
     * Country filter: country ids, names, ISO2/ISO3 codes and regions, in any mix, all resolved to country ids.
     * The three sources (countries, iso3, regions) must all hold, so their id sets are intersected.
     *
     * @return int[]|false ids; [] when no country filter was requested; false when nothing can match
     */
    private function resolve_country_ids()
    {
        $sets = [];

        $countries = Catalog_country_resolver::resolve($this->countries);
        if (Catalog_country_resolver::is_no_match($countries)) {
            return false;
        }
        if (!empty($countries)) {
            $sets[] = array_map('intval', $countries);
        }

        $iso3 = trim((string) $this->country_iso3);
        if ($iso3 !== '') {
            $codes = array_filter(array_map('trim', explode(',', $iso3)), static fn($c) => strlen($c) === 3);
            $rows  = empty($codes) ? [] : $this->ci->db->select('countryid')->where_in('iso', $codes)->get('countries')->result_array();
            $sets[] = array_map('intval', array_column($rows, 'countryid'));
        }

        $regions = $this->normalise_int_array($this->regions);
        if (!empty($regions)) {
            $rows = $this->ci->db
                ->distinct()
                ->select('country_id')
                ->where_in('region_id', $regions)
                ->get('region_countries')
                ->result_array();
            $sets[] = array_map('intval', array_column($rows, 'country_id'));
        }

        return $this->intersect($sets);
    }

    /**
     * Study id filter: the sid parameter, and the studies of the selected topics and timeseries databases,
     * resolved from this database. All requested sources must hold, so their id sets are intersected.
     *
     * @return int[]|false ids; [] when no such filter was requested; false when nothing can match
     */
    private function resolve_sids()
    {
        $sets = [];

        $raw = trim((string) $this->sid);
        if ($raw !== '') {
            $ids = array_values(array_filter(array_map('intval', array_filter(explode(',', $raw), 'is_numeric')), static fn($v) => $v > 0));
            $sets[] = $ids;
        }

        $topics = $this->normalise_int_array($this->topics);
        if (!empty($topics)) {
            $rows   = $this->ci->db->distinct()->select('sid')->where_in('tid', $topics)->get('survey_topics')->result_array();
            $sets[] = array_map('intval', array_column($rows, 'sid'));
        }

        $databases = $this->normalise_array($this->database);
        if (!empty($databases)) {
            $rows   = $this->ci->db->distinct()->select('series_id')->where_in('db_idno', $databases)->get('timeseries_db_links')->result_array();
            $sets[] = array_map('intval', array_column($rows, 'series_id'));
        }

        return $this->intersect($sets);
    }

    /**
     * Data access type filter — form ids. Numeric values are used as-is; legacy forms.model codes
     * (e.g. "public") are looked up.
     *
     * @return int[]|false
     */
    private function resolve_form_ids()
    {
        $values = $this->normalise_array($this->dtype);
        if (empty($values)) {
            return [];
        }

        $ids    = [];
        $models = [];
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            } else {
                $models[] = $value;
            }
        }

        if (!empty($models)) {
            $rows = $this->ci->db->select('formid')->where_in('model', array_values(array_unique($models)))->get('forms')->result_array();
            foreach ($rows as $row) {
                $ids[] = (int) $row['formid'];
            }
        }

        $ids = array_values(array_unique(array_filter($ids, static fn($v) => $v > 0)));

        return empty($ids) ? false : $ids;
    }

    /**
     * @param mixed $values
     * @return int[]|false ids; [] when nothing was requested; false when values were given but none is a valid id
     */
    private function resolve_int_list($values)
    {
        $raw = $this->normalise_array($values);
        if (empty($raw)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', array_filter($raw, 'is_numeric')), static fn($v) => $v > 0)));

        return empty($ids) ? false : $ids;
    }

    /**
     * User-defined facets: facet name => term ids.
     *
     * @return array<string, int[]>|false
     */
    private function resolve_user_facets()
    {
        $facets = [];

        foreach ($this->user_facets as $facet) {
            $name = trim((string) ($facet['name'] ?? ''));
            if ($name === '' || !array_key_exists($name, $this->params)) {
                continue;
            }

            $terms = $this->resolve_int_list($this->params[$name]);
            if ($terms === false) {
                return false;
            }
            if (!empty($terms)) {
                $facets[$name] = $terms;
            }
        }

        return $facets;
    }

    /**
     * Year range (from/to) -> year_from / year_to, swapped when given in the wrong order.
     *
     * @return array<string, int>
     */
    private function build_year_bounds(): array
    {
        $from = (int) $this->from;
        $to   = (int) $this->to;

        if ($from > 0 && $to > 0 && $from > $to) {
            list($from, $to) = [$to, $from];
        }

        $bounds = [];
        if ($from > 0) {
            $bounds['year_from'] = $from;
        }
        if ($to > 0) {
            $bounds['year_to'] = $to;
        }

        return $bounds;
    }

    /**
     * The created filter, "date" or "date-date" (same reading as the database search: a single date covers that
     * day, a range includes its last day) -> created_from / created_to as unix seconds (inclusive).
     *
     * @return array<string, int>|false false when the range is empty
     */
    private function build_created_bounds()
    {
        $range = explode('-', (string) $this->created);
        $start = strtotime($range[0]);
        if (empty($start)) {
            return [];
        }

        $end = (isset($range[1]) && strtotime($range[1])) ? strtotime($range[1]) + 86399 : $start + 86399;

        // the database search treats the end as exclusive
        return ['created_from' => (int) $start, 'created_to' => (int) $end - 1];
    }

    /**
     * Intersect id sets; [] when there are none (no constraint), false when the result is empty.
     *
     * @param array<int[]> $sets
     * @return int[]|false
     */
    private function intersect(array $sets)
    {
        if (empty($sets)) {
            return [];
        }

        $ids = array_shift($sets);
        foreach ($sets as $set) {
            $ids = array_intersect($ids, $set);
        }
        $ids = array_values(array_unique($ids));

        return empty($ids) ? false : $ids;
    }

    // =========================================================================
    // API call
    // =========================================================================

    /**
     * @return array the decoded response
     * @throws Semantic_search_api_exception
     */
    private function call_api(array $body): array
    {
        $url     = $this->api_url . '/studies/search';
        $payload = json_encode($body);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $this->build_headers(),
        ]);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "catalog_search_semantic_studies::call_api curl error: {$err}");
            throw new Semantic_search_api_exception("Semantic search API request failed: {$err}", $url, 0, $body, '');
        }

        if ($status < 200 || $status >= 300) {
            log_message('error', "catalog_search_semantic_studies::call_api HTTP {$status} request: {$payload} response: {$raw}");
            throw new Semantic_search_api_exception($this->error_message($status, (string) $raw), $url, $status, $body, (string) $raw);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || !isset($decoded['found'], $decoded['hits'], $decoded['search_counts_by_type'])) {
            log_message('error', "catalog_search_semantic_studies::call_api unexpected response: {$raw}");
            throw new Semantic_search_api_exception('Semantic search API returned an unexpected response', $url, $status, $body, (string) $raw);
        }

        if ($this->debug) {
            log_message('debug', "catalog_search_semantic_studies request: {$payload}");
            log_message('debug', "catalog_search_semantic_studies response: {$raw}");
        }

        return $decoded;
    }

    /** "Semantic search API returned HTTP 422 (invalid_filter_value): ..." from the API's error envelope. */
    private function error_message(int $status, string $raw): string
    {
        $error = json_decode($raw, true)['error'] ?? null;
        if (is_array($error) && isset($error['code'], $error['message'])) {
            return sprintf('Semantic search API returned HTTP %d (%s): %s', $status, $error['code'], $error['message']);
        }

        return "Semantic search API returned HTTP {$status}";
    }

    private function build_headers(): array
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        // the admin key is used while debugging, because include_debug needs the admin role
        $key = ($this->debug && $this->admin_api_key !== '') ? $this->admin_api_key : $this->api_key;
        if ($key !== '') {
            $headers[] = 'X-NADA-Admin-Key: ' . $key;
        }

        return $headers;
    }

    /** nada-ai unreachable, timed out, or failed on its side (5xx, rate limited). */
    private function is_outage(Semantic_search_api_exception $e): bool
    {
        return $e->http_status === 0 || $e->http_status === 429 || $e->http_status >= 500;
    }

    // =========================================================================
    // Rows
    // =========================================================================

    /**
     * Load the rows of the API's hits from this database, in the API's order.
     *
     * @param array $hits API hits: sid, idno, rank, score, matched_by, optional passages
     * @return array{0: array, 1: int} rows, and the number of hits left out
     */
    private function load_rows(array $hits): array
    {
        if (empty($hits)) {
            return [[], 0];
        }

        $sids = array_map(static fn($hit) => (int) $hit['sid'], $hits);

        $this->ci->db->select(self::$survey_fields, false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms', 'surveys.formid = forms.formid', 'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        $this->ci->db->join('timeseries_db_links tdbl', 'tdbl.series_id = surveys.id AND tdbl.is_primary = 1', 'left');
        $this->ci->db->join('surveys tsdb', "tsdb.idno = tdbl.db_idno AND tsdb.type = 'timeseriesdb' AND tsdb.published = 1", 'left');
        $this->ci->db->where('surveys.published', 1);
        $this->ci->db->where_in('surveys.id', $sids);
        $by_sid = [];
        foreach ($this->ci->db->get()->result_array() as $row) {
            $by_sid[(int) $row['id']] = $row;
        }

        $rows    = [];
        $dropped = 0;
        foreach ($hits as $hit) {
            $row = $by_sid[(int) $hit['sid']] ?? null;
            // a missing row (deleted or unpublished since indexing) or a different idno (the id now belongs to
            // another study) means the index no longer describes this catalog
            if ($row === null || strcasecmp((string) $row['idno'], (string) $hit['idno']) !== 0) {
                $dropped++;
                continue;
            }

            $row['semantic_document_pages'] = $this->document_pages($hit['passages'] ?? []);
            if ($this->debug) {
                $row['semantic_hit'] = $hit;
            }
            $rows[] = $row;
        }

        return [$rows, $dropped];
    }

    /**
     * API passages ({page (1-based), total_pages?, score?, excerpt?}, best first) -> the page list of the study
     * card ({page_index (0-based), page, ...}).
     *
     * @return array<int, array>
     */
    private function document_pages(array $passages): array
    {
        $pages = [];
        foreach ($passages as $passage) {
            $page    = (int) ($passage['page'] ?? 0);
            if ($page < 1) {
                continue;
            }
            $entry = ['page_index' => $page - 1, 'page' => $page];
            foreach (['total_pages', 'score', 'excerpt'] as $key) {
                if (isset($passage[$key])) {
                    $entry[$key] = $passage[$key];
                }
            }
            $pages[] = $entry;
        }

        return $pages;
    }

    private function fetch_citation_counts(array $survey_ids): array
    {
        if (empty($survey_ids)) {
            return [];
        }

        $rows = $this->ci->db
            ->select('sid, COUNT(id) AS cnt')
            ->where_in('sid', $survey_ids)
            ->group_by('sid')
            ->get('survey_citations')
            ->result_array();

        $counts = [];
        foreach ($rows as $r) {
            $counts[(int) $r['sid']] = (int) $r['cnt'];
        }

        return $counts;
    }

    /**
     * "Keyword(s) found in N variable(s)" badge on study cards (classic catalog parity).
     */
    private function attach_variable_match_counts(array $rows): array
    {
        $keywords = trim((string) $this->study_keywords);
        if ($keywords === '' || empty($rows)) {
            return $rows;
        }

        $variables_by_study = $this->database_search()->search_variable_counts(array_column($rows, 'id'), $keywords);
        if (empty($variables_by_study)) {
            return $rows;
        }

        foreach ($rows as $idx => $row) {
            $sid = (int) ($row['id'] ?? 0);
            if ($sid && array_key_exists($sid, $variables_by_study)) {
                $rows[$idx]['var_found'] = $variables_by_study[$sid]['var_found'];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, int> $counts_by_type
     * @param string[]           $notes
     */
    private function result(array $rows, array $counts_by_type, int $found, array $notes, int $limit, int $offset): array
    {
        $rows = $this->attach_variable_match_counts($rows);

        $result = [
            'found'                 => $found,
            'total'                 => $this->database_search()->count_total_published(),
            'limit'                 => $limit,
            'offset'                => $offset,
            'rows'                  => $rows,
            'citations'             => $this->fetch_citation_counts(array_column($rows, 'id')),
            'search_counts_by_type' => $counts_by_type,
        ];

        if (!empty($notes)) {
            $result['semantic_note'] = implode(' ', $notes);
        }

        return $result;
    }

    // =========================================================================
    // Database search (fallback, variable search, catalog total)
    // =========================================================================

    private function search_in_database_after(Semantic_search_api_exception $e, int $limit, int $offset): array
    {
        $result = $this->database_search()->search($limit, $offset);
        $result['semantic_fallback'] = 'Semantic search is unavailable, so these results come from the catalog database search.';

        if ($this->debug) {
            $result['debug'] = ['fallback_reason' => $e->getMessage(), 'url' => $e->url, 'http_status' => $e->http_status];
        }

        return $result;
    }

    private function database_search(): catalog_search_mysql
    {
        if ($this->db_driver === null) {
            require_once dirname(__FILE__) . '/Catalog_search_mysql.php';
            $props = [
                'study_keywords', 'variable_keywords', 'topics', 'tags', 'countries', 'regions', 'from', 'to', 'repo', 'type',
                'data_class', 'collections', 'dtype', 'database', 'sid', 'created', 'country_iso3', 'sort_by', 'sort_order',
            ];
            $params = [];
            foreach ($props as $prop) {
                $params[$prop] = $this->$prop;
            }
            foreach ($this->user_facets as $facet) {
                $name = $facet['name'] ?? '';
                if ($name !== '' && array_key_exists($name, $this->params)) {
                    $params[$name] = $this->params[$name];
                }
            }
            $this->db_driver = new catalog_search_mysql($params);
        }

        return $this->db_driver;
    }

    // =========================================================================
    // Value helpers
    // =========================================================================

    private function normalise_array($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $value)), 'strlen'));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }

        return [];
    }

    private function normalise_int_array($value): array
    {
        return array_values(array_filter(array_map('intval', $this->normalise_array($value))));
    }
}
