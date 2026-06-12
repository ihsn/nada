<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI) backend
 *
 * Two-step strategy:
 *   1. POST /search to the external semantic API → get ranked idno list
 *   2. Re-fetch full survey rows from the NADA DB using those idnos
 *      and apply any NADA-specific filters not handled by the API
 *
 * Public interface matches catalog_search_mysql so Catalog_search.php
 * can swap backends transparently.
 *
 * Variable search (vsearch / v_quick_search) and per-study var_found badges
 * delegate to MySQL via db_fallback() — same as classic catalog cards.
 *
 * Dataset type / tab alignment
 * ----------------------------
 * NADA tab_type is passed as $type (surveys.type codes). The semantic API uses a
 * different vocabulary for some types (e.g. survey→microdata, timeseries→indicator).
 * Tab filter is sent as filters.type (string), e.g. "indicator".
 * When no tab is selected (All), no type filter is sent. Unmapped NADA types are
 * passed through as-is so they work once indexed remotely.
 *
 * Classic catalog filters (country, region, tag, dtype, years, repo/collection) are
 * mapped to the semantic API filters object. All filter values are encoded as strings.
 * dtype uses forms.model codes (e.g. ["public", "direct"]).
 * Sidebar facets are loaded from the DB (same as Solr/MySQL); API facet buckets are
 * not used for the catalog UI.
 *
 * Pagination and tab counts
 * -------------------------
 * Result rows come from the semantic API (keyword + filters, paginated). Pagination
 * totals and tab counts come from a dual MySQL query (filter universe size, no
 * keyword) via db_fallback() — same UX as other drivers; empty trailing pages are OK.
 */
class catalog_search_semantic
{
    /** NADA surveys.type codes that rename to a different semantic API filters.type */
    private static $nada_to_semantic_type = [
        'survey'     => 'microdata',
        'timeseries' => 'indicator',
    ];

    // -------------------------------------------------------------------------
    // Search parameters — set by Catalog_search before calling search()
    // -------------------------------------------------------------------------
    public $study_keywords    = '';
    public $variable_keywords = '';
    public $countries         = [];
    public $regions           = [];
    public $from              = 0;
    public $to                = 0;
    public $repo              = '';
    public $type              = [];
    public $data_class        = [];
    public $collections       = [];
    public $dtype             = [];
    public $sid               = '';
    public $created           = '';
    public $country_iso3      = '';
    public $sort_by           = 'title';
    public $sort_order        = 'asc';
    public $tags              = [];

    /** @var array Full search params including user-defined facet keys. */
    private $params          = [];

    /** @var array User-defined facets from Facet_model. */
    private $user_facets     = [];

    private $ci;
    private $api_url;
    private $api_key;
    private $mode;
    private $timeout;
    private $knn_k;
    private $debug;
    private $query_prompt;
    private $collapse_inner_hits_size;

    /** Remote semantic API maximum value for the size parameter */
    private const API_MAX_PAGE_SIZE = 100;

    /** Maximum CURLOPT_TIMEOUT for the nada-ai search request (seconds) */
    private const API_MAX_TIMEOUT_SEC = 15;

    // Fields returned by the DB re-fetch — must match MySQL driver shape
    private static $survey_fields =
        'surveys.id as id, surveys.type, surveys.idno as idno, surveys.doi, surveys.title,
         surveys.subtitle, nation, authoring_entity,
         forms.model as form_model, data_class_id, surveys.year_start, surveys.year_end,
         surveys.thumbnail,
         surveys.repositoryid as repositoryid, link_da, repositories.title as repo_title,
         surveys.created, surveys.changed, surveys.total_views, surveys.total_downloads, varcount,
         surveys.ts_dimensions, surveys.abstract';

    public function __construct(array $params = [])
    {
        $this->ci = & get_instance();
        $this->ci->config->load('semantic_search');

        $this->api_url = rtrim((string)$this->ci->config->item('semantic_search_url'),     '/');
        $this->api_key = (string)$this->ci->config->item('semantic_search_api_key');
        $this->mode    = (string)$this->ci->config->item('semantic_search_mode')    ?: 'hybrid';
        $timeout = (int) $this->ci->config->item('semantic_search_timeout');
        $this->timeout = min(
            max(1, $timeout > 0 ? $timeout : self::API_MAX_TIMEOUT_SEC),
            self::API_MAX_TIMEOUT_SEC
        );
        $this->knn_k   = (int)   $this->ci->config->item('semantic_search_knn_k')   ?: 50;
        $this->debug   = (bool)  $this->ci->config->item('semantic_search_debug');
        $this->query_prompt = (string) $this->ci->config->item('semantic_search_query_prompt');
        $collapse_size = (int) $this->ci->config->item('semantic_search_collapse_inner_hits_size');
        $this->collapse_inner_hits_size = $collapse_size > 0 ? $collapse_size : 15;

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
        if (trim($this->study_keywords) === '') {
            return $this->db_fallback()->search($limit, $offset);
        }

        if (!class_exists('Catalog_study_idno_lookup', false)) {
            require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';
        }
        $idno_result = Catalog_study_idno_lookup::try_search_from_params(
            $this->search_params_for_db_lookup(),
            $limit,
            $offset
        );
        if ($idno_result !== null) {
            return $idno_result;
        }

        $t0 = microtime(true);

        $body     = $this->build_request($limit, $offset);
        $response = $this->call_api($body);

        $api_total = (int)($response['total'] ?? 0);
        $hits      = $response['hits']         ?? [];

        $extracted = $this->extract_idnos_from_hits($hits);
        $idnos     = $extracted['idnos'];
        $hit_map   = $extracted['hit_map'];

        $rows = [];
        if (!empty($idnos)) {
            $rows = $this->fetch_surveys_by_idno($idnos);
        }

        require_once dirname(__FILE__) . '/Semantic_document_pages.php';

        foreach ($rows as &$row) {
            $hit = $hit_map[strtolower($row['idno'])] ?? null;
            $row['semantic_hit'] = $hit;
            $row['semantic_document_pages'] = (
                isset($row['type']) && $row['type'] === 'document' && is_array($hit)
            ) ? Semantic_document_pages::from_hit($hit) : [];
        }
        unset($row);

        $rows = $this->attach_variable_match_counts($rows, $this->study_keywords);

        $citations = $this->fetch_citation_counts(array_column($rows, 'id'));

        $counter = $this->db_fallback();
        $counter->study_keywords = '';
        $tab_counts = $counter->search_counts_by_type();
        if ($tab_counts === false) {
            $tab_counts = [];
        }

        $result = [
            'found'                 => $counter->count_filter_universe(),
            'total'                 => $counter->count_total_published(),
            'limit'                 => $limit,
            'offset'                => $offset,
            'rows'                  => $rows,
            'citations'             => $citations,
            'search_counts_by_type' => $tab_counts,
        ];

        if ($api_total === 0) {
            $result['semantic_note'] = 'No results in the semantic index for this query and filters.';
        } elseif (!empty($idnos) && empty($rows)) {
            $result['semantic_note'] = 'Semantic search returned results but none match published studies in this catalog. The remote index may not be synced with this site.';
        }

        if ($this->debug) {
            $result['debug'] = [
                'request'                  => $body,
                'response'                 => $response,
                'idnos'                    => $idnos,
                'db_rows_found'            => count($rows),
                'api_total'                => $api_total,
                'db_found'                 => $result['found'],
                'db_search_counts_by_type' => $tab_counts,
                'counts_source'            => 'db_filter_universe',
                'nada_dataset_types'       => $this->get_nada_dataset_types(),
                'elapsed'                  => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    public function vsearch(int $limit = 15, int $offset = 0): array
    {
        return $this->db_fallback()->vsearch($limit, $offset);
    }

    public function v_quick_search(?int $sid = null, int $limit = 50, int $offset = 0): array
    {
        return $this->db_fallback()->v_quick_search($sid, $limit, $offset);
    }

    // =========================================================================
    // Request building
    // =========================================================================

    private function build_request(int $limit, int $offset): array
    {
        $body = [
            'query'                  => (string) $this->study_keywords,
            'mode'                   => $this->mode,
            'size'                   => $this->api_page_size($limit),
            'from'                   => $offset,
            'knn_k'                  => $this->knn_k,
            'include_embedding'      => false,
            'include_facets'         => false,
            'include_opensearch_body'=> false,
            'collapse_field'         => 'idno',
            'collapse_inner_hits'    => [
                'name' => 'variants',
                'size' => $this->collapse_inner_hits_size,
            ],
        ];

        if ($this->query_prompt !== '') {
            $body['query_prompt'] = $this->query_prompt;
        }

        $filters = $this->build_filters();
        if (!empty($filters)) {
            $body['filters'] = $filters;
        }

        return $body;
    }

    /**
     * Map classic NADA catalog filters to the semantic API filters object.
     * Most filter values are string arrays, e.g. ["16"]. Dataset type is a single string.
     *
     * @return array<string, string|string[]>
     */
    private function build_filters(): array
    {
        $filters = [];

        $filters['published'] = ['1'];

        $semantic_type = $this->resolve_semantic_type_filter();
        if ($semantic_type !== null) {
            $filters['type'] = $semantic_type;
        }

        $countries = $this->resolve_country_ids();
        if (!empty($countries)) {
            $filters['countries'] = $this->stringify_filter_values($countries);
        }

        $regions = $this->normalise_int_array($this->regions);
        if (!empty($regions)) {
            $filters['regions'] = $this->stringify_filter_values($regions);
        }

        $tags = $this->normalise_array($this->tags);
        if (!empty($tags)) {
            $filters['tags'] = $this->stringify_filter_values($tags);
        }

        $dtypes = $this->resolve_dtype_filter_values();
        if (!empty($dtypes)) {
            $filters['dtype'] = $dtypes;
        }

        $years = $this->build_years_filter_values();
        if (!empty($years)) {
            $filters['years'] = $years;
        }

        $repo = trim($this->repo ?? '');
        if ($repo !== '' && $repo !== 'central') {
            $filters['repositoryid'] = $this->stringify_filter_values([$repo]);
        }

        $collections = $this->normalise_array($this->collections);
        if (!empty($collections)) {
            $filters['repositories'] = $this->stringify_filter_values($collections);
        }

        foreach ($this->build_user_facet_filters() as $facet_name => $values) {
            $filters[$facet_name] = $values;
        }

        return $filters;
    }

    /**
     * User-defined catalog facets (e.g. authentity) — sent under the facet name as string arrays.
     *
     * @return array<string, string[]>
     */
    private function build_user_facet_filters(): array
    {
        $filters = [];

        foreach ($this->user_facets as $fc) {
            $name = trim((string) ($fc['name'] ?? ''));
            if ($name === '' || !array_key_exists($name, $this->params)) {
                continue;
            }

            $values = $this->normalise_array($this->params[$name]);
            if (empty($values)) {
                continue;
            }

            $filters[$name] = $this->stringify_filter_values($values);
        }

        return $filters;
    }

    /**
     * Data-access type filter — semantic API expects forms.model codes, e.g. ["public", "direct"].
     * Numeric URL values (legacy formid) are mapped to model codes; codes pass through unchanged.
     *
     * @return string[]
     */
    private function resolve_dtype_filter_values(): array
    {
        $values = $this->normalise_array($this->dtype);
        if (empty($values)) {
            return [];
        }

        $models = [];
        $form_ids = [];

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $id = (int) $value;
                if ($id > 0) {
                    $form_ids[] = $id;
                }
                continue;
            }
            $models[] = trim((string) $value);
        }

        if (!empty($form_ids)) {
            $rows = $this->ci->db
                ->select('formid, model')
                ->where_in('formid', array_values(array_unique($form_ids)))
                ->get('forms')
                ->result_array();

            foreach ($rows as $row) {
                $model = trim((string) ($row['model'] ?? ''));
                if ($model !== '') {
                    $models[] = $model;
                }
            }
        }

        return $this->stringify_filter_values($models);
    }

    /**
     * @param array<int|string> $values
     * @return string[]
     */
    private function stringify_filter_values(array $values): array
    {
        return array_values(array_filter(
            array_map(static fn($v) => trim((string) $v), $values),
            'strlen'
        ));
    }

    /**
     * Year range (from/to) → list of year strings for the API years filter.
     *
     * @return string[]
     */
    private function build_years_filter_values(): array
    {
        $from = (int) ($this->from ?? 0);
        $to   = (int) ($this->to ?? 0);

        if ($from <= 0 && $to <= 0) {
            return [];
        }

        if ($from > 0 && $to > 0 && $from > $to) {
            $this->from = $to;
            $this->to   = $from;
            $from = (int) $this->from;
            $to   = (int) $this->to;
        }

        if ($from > 0 && $to > 0) {
            $years = [];
            for ($y = $from; $y <= $to; $y++) {
                $years[] = (string) $y;
            }
            return $years;
        }

        if ($from > 0) {
            return [(string) $from];
        }

        return [(string) $to];
    }

    /**
     * Resolve country filter values to numeric country IDs.
     *
     * @return int[]
     */
    private function resolve_country_ids(): array
    {
        $countries = $this->normalise_array($this->countries);

        if (!empty($countries) && !is_numeric($countries[0])) {
            $countries = $this->get_country_id_by_name($countries);
        }

        $iso3 = trim($this->country_iso3 ?? '');
        if ($iso3 !== '') {
            $codes = array_filter(array_map('trim', explode(',', $iso3)), static fn($c) => strlen($c) === 3);
            if (!empty($codes)) {
                $rows = $this->ci->db
                    ->select('countryid')
                    ->where_in('iso', $codes)
                    ->get('countries')
                    ->result_array();
                $countries = array_merge($countries, array_column($rows, 'countryid'));
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $countries))));
    }

    /**
     * @param string[] $country_names
     * @return int[]
     */
    private function get_country_id_by_name(array $country_names): array
    {
        if (empty($country_names)) {
            return [];
        }

        $rows = $this->ci->db
            ->select('countryid')
            ->where_in('name', $country_names)
            ->get('countries')
            ->result_array();

        return array_map('intval', array_column($rows, 'countryid'));
    }

    // =========================================================================
    // API call
    // =========================================================================

    private function call_api(array $body): array
    {
        require_once dirname(__FILE__) . '/Semantic_search_api_exception.php';

        $url     = $this->api_url . '/search';
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
            log_message('error', "catalog_search_semantic::call_api curl error: {$err}");
            throw new Semantic_search_api_exception(
                "Semantic search API request failed: {$err}",
                $url,
                0,
                $body,
                ''
            );
        }

        if ($status < 200 || $status >= 300) {
            log_message('error', "catalog_search_semantic::call_api HTTP {$status} request: {$payload} response: {$raw}");
            throw new Semantic_search_api_exception(
                "Semantic search API returned HTTP {$status}",
                $url,
                $status,
                $body,
                (string) $raw
            );
        }

        if ($this->debug) {
            log_message('debug', "catalog_search_semantic request: {$payload}");
            log_message('debug', "catalog_search_semantic response: {$raw}");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            log_message('error', "catalog_search_semantic::call_api invalid JSON: {$raw}");
            throw new Semantic_search_api_exception(
                'Semantic search API returned invalid JSON',
                $url,
                $status,
                $body,
                (string) $raw
            );
        }

        return $decoded;
    }

    private function build_headers(): array
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->api_key !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->api_key;
        }
        return $headers;
    }

    // =========================================================================
    // Hit / idno helpers
    // =========================================================================

    /**
     * @return array{idnos: string[], hit_map: array<string, array>}
     */
    private function extract_idnos_from_hits(array $hits): array
    {
        $idnos   = [];
        $hit_map = [];

        foreach ($hits as $hit) {
            $idno = $hit['_source']['metadata']['idno'] ?? null;
            if ($idno === null || $idno === '') {
                continue;
            }
            $key = strtolower((string)$idno);
            if (!isset($hit_map[$key])) {
                $idnos[]       = (string)$idno;
                $hit_map[$key] = $hit;
            }
        }

        return ['idnos' => $idnos, 'hit_map' => $hit_map];
    }

    // =========================================================================
    // DB re-fetch
    // =========================================================================

    private function fetch_surveys_by_idno(array $idnos): array
    {
        $this->ci->db->select(self::$survey_fields, false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms',        'surveys.formid = forms.formid',                  'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        $this->ci->db->where('surveys.published', 1);
        $this->apply_idno_filter($idnos);
        $this->apply_db_filters();

        $rows = $this->ci->db->get()->result_array();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[strtolower($row['idno'])] = $row;
        }

        $ordered = [];
        foreach ($idnos as $idno) {
            $key = strtolower($idno);
            if (isset($indexed[$key])) {
                $ordered[] = $indexed[$key];
            }
        }

        return $ordered;
    }

    private function apply_idno_filter(array $idnos): void
    {
        $idnos_lower = array_map('strtolower', $idnos);
        $escaped = implode(',', array_map(
            fn($v) => "'" . $this->ci->db->escape_str($v) . "'",
            $idnos_lower
        ));
        $this->ci->db->where("LOWER(surveys.idno) IN ({$escaped})", null, false);
    }

    /**
     * Filters with no semantic API equivalent (applied after idno re-fetch).
     */
    private function apply_db_filters(): void
    {
        $data_class = $this->normalise_int_array($this->data_class);
        if (!empty($data_class)) {
            $this->ci->db->where_in('surveys.data_class_id', $data_class);
        }

        $sid_str = trim($this->sid ?? '');
        if ($sid_str !== '') {
            $sids = array_filter(array_map('intval', explode(',', $sid_str)));
            if (!empty($sids)) {
                $this->ci->db->where_in('surveys.id', $sids);
            }
        }

        $created = trim($this->created ?? '');
        if ($created !== '') {
            $parts = explode(',', $created, 2);
            if (count($parts) === 2) {
                $ts_from = strtotime(trim($parts[0]));
                $ts_to   = strtotime(trim($parts[1]));
                if ($ts_from && $ts_to) {
                    $this->ci->db->where('surveys.created >=', $ts_from);
                    $this->ci->db->where('surveys.created <=', $ts_to + 86399);
                }
            }
        }

        $this->apply_db_user_facet_filters();
    }

    private function apply_db_user_facet_filters(): void
    {
        foreach ($this->user_facets as $fc) {
            $name = trim((string) ($fc['name'] ?? ''));
            if ($name === '' || !array_key_exists($name, $this->params)) {
                continue;
            }

            $term_ids = array_values(array_filter(array_map('intval', $this->normalise_array($this->params[$name]))));
            if (empty($term_ids)) {
                continue;
            }

            $escaped = implode(',', $term_ids);
            $this->ci->db->where(
                "surveys.id IN (SELECT sid FROM survey_facets WHERE term_id IN ({$escaped}))",
                null,
                false
            );
        }
    }

    private function api_page_size(int $limit): int
    {
        return min(max(1, $limit), self::API_MAX_PAGE_SIZE);
    }

    /**
     * @return string[]
     */
    private function get_nada_dataset_types(): array
    {
        $types = $this->normalise_array($this->type);

        return array_values(array_filter(
            $types,
            static fn($t) => strtolower((string)$t) !== 'all' && (string)$t !== ''
        ));
    }

    /**
     * Semantic API filters.type — single mapped value. Omitted for All tab or multiple types.
     */
    private function resolve_semantic_type_filter(): ?string
    {
        $nada_types = $this->get_nada_dataset_types();
        if (count($nada_types) !== 1) {
            return null;
        }

        return $this->map_nada_type_to_semantic($nada_types[0]);
    }

    private function map_nada_type_to_semantic(string $nada_type): string
    {
        $key = strtolower(trim($nada_type));

        return self::$nada_to_semantic_type[$key] ?? $key;
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
            $counts[(int)$r['sid']] = (int)$r['cnt'];
        }
        return $counts;
    }

    /**
     * "Keyword(s) found in N variable(s)" badge on study cards (classic catalog parity).
     *
     * @param array  $rows
     * @param string $keywords Study keyword string (sk)
     * @return array
     */
    private function attach_variable_match_counts(array $rows, string $keywords): array
    {
        if (trim($keywords) === '' || empty($rows)) {
            return $rows;
        }

        $id_list = array_column($rows, 'id');
        if (empty($id_list)) {
            return $rows;
        }

        $variables_by_study = $this->db_fallback()->search_variable_counts($id_list, $keywords);
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
     * @return array<string, mixed>
     */
    private function search_params_for_db_lookup(): array
    {
        $props = [
            'study_keywords', 'variable_keywords', 'countries', 'regions',
            'from', 'to', 'repo', 'type', 'data_class', 'collections',
            'dtype', 'sid', 'created', 'country_iso3', 'sort_by', 'sort_order', 'tags',
        ];
        $params = [];
        foreach ($props as $p) {
            $params[$p] = $this->$p;
        }
        foreach ($this->user_facets as $fc) {
            $name = $fc['name'] ?? '';
            if ($name !== '' && array_key_exists($name, $this->params)) {
                $params[$name] = $this->params[$name];
            }
        }
        return $params;
    }

    private function db_fallback(): catalog_search_mysql
    {
        require_once dirname(__FILE__) . '/Catalog_search_mysql.php';
        return new catalog_search_mysql($this->search_params_for_db_lookup());
    }

    private function normalise_array($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), 'strlen'));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }
        return [];
    }

    private function normalise_int_array($value): array
    {
        $arr = $this->normalise_array($value);
        return array_values(array_filter(array_map('intval', $arr)));
    }
}
