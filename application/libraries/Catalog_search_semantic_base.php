<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — shared base of the semantic (AI) drivers that use nada-ai with NADA's internal study ids
 * (catalog_search_semantic_studies, catalog_search_semantic_fused).
 *
 * Holds what both need: the search parameters, resolution of the NADA filters to ids, the HTTP call to nada-ai,
 * loading the rows of a page from this database, the result envelope, and access to the database search driver
 * of this installation (MySQL or SQL Server).
 *
 * The original Qdrant driver (catalog_search_semantic) is separate and unchanged.
 */

if (! class_exists('Catalog_country_resolver', false)) {
    require_once dirname(__FILE__) . '/Catalog_country_resolver.php';
}
if (! class_exists('Catalog_filter_guard', false)) {
    require_once dirname(__FILE__) . '/Catalog_filter_guard.php';
}
require_once dirname(__FILE__) . '/Semantic_search_api_exception.php';

abstract class catalog_search_semantic_base
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
    protected $params      = [];

    /** @var array User-defined facets from Facet_model. */
    protected $user_facets = [];

    protected $ci;
    protected $api_url;
    protected $api_key;
    protected $admin_api_key;
    protected $timeout;
    protected $debug;

    /** @var object|null The database search driver of this installation (see database_search()). */
    private $db_driver = null;

    /** Maximum CURLOPT_TIMEOUT for the nada-ai search request (seconds) */
    protected const API_MAX_TIMEOUT_SEC = 15;

    // Fields returned by the row load — the row shape of Catalog_search_mysql::search()
    protected static $survey_fields =
        'surveys.id as id, surveys.type, surveys.idno as idno, surveys.doi, surveys.title,
         surveys.subtitle, surveys.nation, surveys.authoring_entity,
         forms.model as form_model, surveys.data_class_id, surveys.year_start, surveys.year_end,
         surveys.thumbnail,
         surveys.repositoryid as repositoryid, surveys.link_da, repositories.title as repo_title,
         surveys.created, surveys.changed, surveys.total_views, surveys.total_downloads, surveys.varcount,
         surveys.ts_dimensions, surveys.ts_frequency, surveys.ts_data_count, surveys.abstract,
         tsdb.id as ts_db_study_id, tsdb.title as ts_db_title';

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

    abstract public function search(int $limit = 15, int $offset = 0): array;

    /** Headers that authenticate the request to nada-ai (the two APIs use different credentials). */
    abstract protected function auth_headers(): array;

    // Variable search is not part of the semantic search: it uses the database search.
    public function vsearch(int $limit = 15, int $offset = 0): array
    {
        return $this->database_search()->vsearch($limit, $offset);
    }

    public function v_quick_search(?int $sid = null, int $limit = 50, int $offset = 0): array
    {
        return $this->database_search()->v_quick_search($sid, $limit, $offset);
    }

    // =========================================================================
    // Filters: NADA parameters -> ids
    // =========================================================================

    /**
     * Country filter: country ids, names, ISO2/ISO3 codes and regions, in any mix, all resolved to country ids.
     * The three sources (countries, iso3, regions) must all hold, so their id sets are intersected.
     *
     * @return int[]|false ids; [] when no country filter was requested; false when nothing can match
     */
    protected function resolve_country_ids()
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
            $codes  = array_filter(array_map('trim', explode(',', $iso3)), static fn($c) => strlen($c) === 3);
            $rows   = empty($codes) ? [] : $this->ci->db->select('countryid')->where_in('iso', $codes)->get('countries')->result_array();
            $sets[] = array_map('intval', array_column($rows, 'countryid'));
        }

        $regions = $this->normalise_int_array($this->regions);
        if (!empty($regions)) {
            $rows   = $this->ci->db->distinct()->select('country_id')->where_in('region_id', $regions)->get('region_countries')->result_array();
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
    protected function resolve_sids()
    {
        $sets = [];

        $raw = trim((string) $this->sid);
        if ($raw !== '') {
            $ids    = array_values(array_filter(array_map('intval', array_filter(explode(',', $raw), 'is_numeric')), static fn($v) => $v > 0));
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
     * @return int[]|false ids; [] when no such filter was requested; false when nothing can match
     */
    protected function resolve_form_ids()
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
    protected function resolve_int_list($values)
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
    protected function resolve_user_facets()
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
     * Year range (from/to) -> [from, to] (0 = open), swapped when given in the wrong order.
     *
     * @return int[]
     */
    protected function year_bounds(): array
    {
        $from = (int) $this->from;
        $to   = (int) $this->to;

        if ($from > 0 && $to > 0 && $from > $to) {
            list($from, $to) = [$to, $from];
        }

        return [max(0, $from), max(0, $to)];
    }

    /**
     * The created filter, "date" or "date-date" (same reading as the database search: a single date covers that
     * day, a range includes its last day) -> [from, to] as unix seconds (inclusive).
     *
     * @return int[]|null null when there is no created filter
     */
    protected function created_bounds(): ?array
    {
        $range = explode('-', (string) $this->created);
        $start = strtotime($range[0]);
        if (empty($start)) {
            return null;
        }

        $end = (isset($range[1]) && strtotime($range[1])) ? strtotime($range[1]) + 86399 : $start + 86399;

        // the database search treats the end as exclusive
        return [(int) $start, (int) $end - 1];
    }

    /**
     * Intersect id sets; [] when there are none (no constraint), false when the result is empty.
     *
     * @param array<int[]> $sets
     * @return int[]|false
     */
    protected function intersect(array $sets)
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

    /**
     * The dataset types selected on the tab (empty = all).
     *
     * @return string[]
     */
    protected function tab_types(): array
    {
        return array_values(array_filter(
            $this->normalise_array($this->type),
            static fn($t) => strtolower($t) !== 'all'
        ));
    }

    // =========================================================================
    // nada-ai
    // =========================================================================

    /**
     * POST a JSON body to nada-ai.
     *
     * @param string[] $required_keys keys the response must have (anything else is an unexpected response)
     * @return array the decoded response
     * @throws Semantic_search_api_exception
     */
    protected function post_json(string $path, array $body, array $required_keys): array
    {
        $url     = $this->api_url . $path;
        $payload = json_encode($body);
        $class   = get_class($this);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json', 'Accept: application/json'], $this->auth_headers()),
        ]);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "{$class}::post_json curl error: {$err}");
            throw new Semantic_search_api_exception("Semantic search API request failed: {$err}", $url, 0, $body, '');
        }

        if ($status < 200 || $status >= 300) {
            log_message('error', "{$class}::post_json HTTP {$status} request: {$payload} response: {$raw}");
            throw new Semantic_search_api_exception($this->error_message($status, (string) $raw), $url, $status, $body, (string) $raw);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || count(array_diff($required_keys, array_keys($decoded))) > 0) {
            log_message('error', "{$class}::post_json unexpected response: {$raw}");
            throw new Semantic_search_api_exception('Semantic search API returned an unexpected response', $url, $status, $body, (string) $raw);
        }

        if ($this->debug) {
            log_message('debug', "{$class} request: {$payload}");
            log_message('debug', "{$class} response: {$raw}");
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

    /** nada-ai unreachable, timed out, or failed on its side (5xx, rate limited). */
    protected function is_outage(Semantic_search_api_exception $e): bool
    {
        return $e->http_status === 0 || $e->http_status === 429 || $e->http_status >= 500;
    }

    // =========================================================================
    // Rows and the result envelope
    // =========================================================================

    /**
     * The rows of the given studies from this database (published only).
     *
     * @param int[] $sids
     * @return array<int, array> study id => row
     */
    protected function fetch_rows(array $sids): array
    {
        if (empty($sids)) {
            return [];
        }

        $this->ci->db->select(self::$survey_fields, false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms', 'surveys.formid = forms.formid', 'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        $this->ci->db->join('timeseries_db_links tdbl', 'tdbl.series_id = surveys.id AND tdbl.is_primary = 1', 'left');
        $this->ci->db->join('surveys tsdb', "tsdb.idno = tdbl.db_idno AND tsdb.type = 'timeseriesdb' AND tsdb.published = 1", 'left');
        $this->ci->db->where('surveys.published', 1);
        $this->ci->db->where_in('surveys.id', array_map('intval', $sids));

        $by_sid = [];
        foreach ($this->ci->db->get()->result_array() as $row) {
            $by_sid[(int) $row['id']] = $row;
        }

        return $by_sid;
    }

    /**
     * @param array<string, int> $counts_by_type
     * @param string[]           $notes
     */
    protected function result(array $rows, array $counts_by_type, int $found, array $notes, int $limit, int $offset): array
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

    // =========================================================================
    // The database search of this installation
    // =========================================================================

    /**
     * The database search driver (MySQL or SQL Server) with the current search parameters: the baseline
     * search, the source of the catalog total, and of the keyword ranking the fused driver combines.
     */
    protected function database_search()
    {
        if ($this->db_driver === null) {
            require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';
            $this->db_driver = Catalog_study_idno_lookup::create_driver($this->database_params());
        }

        return $this->db_driver;
    }

    /**
     * A database search driver with the current search parameters and the given overrides (for example
     * ['exclude_sid' => [...]]), separate from the shared one returned by database_search().
     *
     * @param array<string, mixed> $overrides
     */
    protected function database_search_with(array $overrides)
    {
        require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';

        return Catalog_study_idno_lookup::create_driver(array_merge($this->database_params(), $overrides));
    }

    /**
     * The search parameters in the form the database search drivers take.
     *
     * @return array<string, mixed>
     */
    protected function database_params(): array
    {
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

        return $params;
    }

    // =========================================================================
    // Value helpers
    // =========================================================================

    protected function normalise_array($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $value)), 'strlen'));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }

        return [];
    }

    protected function normalise_int_array($value): array
    {
        return array_values(array_filter(array_map('intval', $this->normalise_array($value))));
    }
}
