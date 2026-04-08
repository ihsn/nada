<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — OpenSearch backend
 *
 * Implements the same public interface as catalog_search_mysql so
 * Catalog_search.php can swap backends transparently.
 *
 * Design rules
 * ------------
 * - All query DSL is built as PHP arrays; no string serialisation.
 * - search() returns all display fields from the index — zero DB round-trips.
 * - vsearch() / v_quick_search() query the nada_variables index (Phase 2).
 *
 * Constructor params (set on the object before calling search/vsearch):
 *   study_keywords   string
 *   variable_keywords string
 *   countries        int[]
 *   regions          int[]
 *   from             int    year range start
 *   to               int    year range end
 *   repo             string repository_id filter
 *   collections      int[]
 *   type             string[]  dataset_type values
 *   dtype            int[]     form_id values
 *   sid              string    comma-separated survey IDs (exact ID filter)
 *   created          string    date range "YYYY-MM-DD,YYYY-MM-DD"
 *   varcount         string    '' | 'with_vars' (only surveys that have variables)
 *   sort_by          string    title|nation|year|popularity|relevance
 *   sort_order       string    asc|desc
 *   debug            bool
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

if (! class_exists('Catalog_study_sort', false)) {
    require_once APPPATH . 'libraries/Catalog_study_sort.php';
}

class catalog_search_opensearch
{
    // -------------------------------------------------------------------------
    // Search parameters (set by Catalog_search before calling search())
    // -------------------------------------------------------------------------
    public $study_keywords    = '';
    public $variable_keywords = '';
    public $countries         = [];
    public $regions           = [];
    public $from              = 0;
    public $to                = 0;
    public $repo              = '';
    public $collections       = [];
    public $type              = [];
    public $dtype             = [];
    public $sid               = '';
    public $created           = '';
    public $varcount          = '';
    public $data_class        = [];
    public $country_iso3      = '';
    public $sort_by           = 'title';
    public $sort_order        = 'asc';
    public $debug             = false;

    private $ci;
    private $survey_index;
    private $variable_index;
    private $user_facets = [];

    private static $sort_map = [
        'title'      => ['title.keyword',     'asc'],
        'nation'     => ['nation.keyword',     'asc'],
        'country'    => ['nation.keyword',     'asc'],
        'year'       => ['year_start',         'desc'],
        'popularity' => ['total_views',        'desc'],
        'relevance'  => ['_score',             'desc'],
        'rank'       => ['_score',             'desc'],
    ];

    // Fields that can be targeted with field:value syntax in the keyword string
    private static $allowed_search_fields = [
        'title'       => 'title',
        'nation'      => 'nation',
        'country'     => 'nation',
        'year'        => 'year_start',
        'author'      => 'authoring_entity',
        'abstract'    => 'abstract',
        'keywords'    => 'keywords',
        'methodology' => 'methodology',
        'idno'        => 'idno',
        'type'        => 'dataset_type',
    ];

    public function __construct(array $params = [])
    {
        $this->ci           =& get_instance();
        $this->survey_index   = OpenSearch_client::index('surveys');
        $this->variable_index = OpenSearch_client::index('variables');
        $this->debug        = (bool)$this->ci->config->item('opensearch_debug');

        $this->ci->load->model('Facet_model');
        $this->user_facets = $this->ci->Facet_model->select_all('user');

        foreach ($params as $key => $value) {
            if (property_exists($this, $key) || $this->is_user_facet_key($key)) {
                $this->$key = $value;
            }
        }
    }

    // =========================================================================
    // Public interface
    // =========================================================================

    /**
     * Survey / study search.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(int $limit = 15, int $offset = 0): array
    {
        $t0   = microtime(true);
        $body = $this->build_survey_query($limit, $offset);

        $response = $this->execute($this->survey_index, $body);

        $found          = $response['hits']['total']['value'] ?? 0;
        $total_in_index = $response['aggregations']['total_all']['count']['value'] ?? $found;
        $hits           = $response['hits']['hits']           ?? [];

        $rows = $this->format_survey_hits($hits);

        // dataset_type aggregation
        $counts_by_type = [];
        foreach ($response['aggregations']['by_type']['buckets'] ?? [] as $bucket) {
            $counts_by_type[$bucket['key']] = (int)$bucket['doc_count'];
        }

        // var_found badge: query nada_variables with the same keywords, aggregated by survey_id.
        // This gives the real match count ("3 variables found"), not the total variable count.
        if ($this->study_keywords && !empty($rows)) {
            $id_list    = array_column($rows, 'id');
            $var_counts = $this->search_variable_counts($id_list, $this->study_keywords);
            foreach ($rows as &$row) {
                if (isset($var_counts[(int)$row['id']])) {
                    $row['var_found'] = $var_counts[(int)$row['id']];
                }
            }
            unset($row);
        }

        // Citation counts are a lightweight lookup — fetch from DB only
        $survey_ids = array_column($rows, 'id');
        $citations  = $this->fetch_citation_counts($survey_ids);

        $result = [
            'found'                 => $found,
            'total'                 => $total_in_index,
            'limit'                 => $limit,
            'offset'                => $offset,
            'search_counts_by_type' => $counts_by_type,
            'rows'                  => $rows,
            'citations'             => $citations,
        ];

        if ($this->debug) {
            $result['debug'] = [
                'index'   => $this->survey_index,
                'query'   => $body,
                'elapsed' => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    /**
     * Variable search across all surveys.
     * Queries nada_variables; applies year/type filters available in the variable index.
     * Returns rows with the same field names as the MySQL driver (uid, labl, qstn, etc.)
     * for view-template compatibility.
     */
    public function vsearch(int $limit = 15, int $offset = 0): array
    {
        $t0   = microtime(true);
        $body = $this->build_variable_query($limit, $offset);

        $response = $this->execute($this->variable_index, $body);

        $found = $response['hits']['total']['value'] ?? 0;
        $total = $response['aggregations']['total_all']['count']['value'] ?? $found;
        $hits  = $response['hits']['hits'] ?? [];

        $result = [
            'found'  => $found,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
            'rows'   => $this->format_variable_hits($hits),
        ];

        if ($this->debug) {
            $result['debug'] = [
                'index'   => $this->variable_index,
                'query'   => $body,
                'elapsed' => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    /**
     * Variable search within a single survey.
     * Filters by survey_id; optionally applies study_keywords on variable fields.
     */
    public function v_quick_search(?int $sid = null, int $limit = 50, int $offset = 0): array
    {
        if ($sid === null) {
            return ['found' => 0, 'total' => 0, 'limit' => $limit, 'offset' => $offset, 'rows' => []];
        }

        $filters = [
            ['term' => ['survey_id'        => $sid]],
            ['term' => ['survey_published' => 1]],
        ];
        $bool    = ['filter' => $filters];

        $fulltext = $this->build_variable_fulltext_clause($this->variable_keywords);
        if ($fulltext !== null) {
            $bool['must'] = [$fulltext];
        }

        $body = [
            'size'             => $limit,
            'from'             => $offset,
            'track_total_hits' => true,
            '_source'          => ['id', 'survey_id', 'vid', 'fid', 'name', 'label', 'question'],
            'query'            => ['bool' => $bool],
            'sort'             => $this->build_variable_sort(true),
            'aggs'             => [
                'total_in_study' => ['filter' => ['term' => ['survey_id' => $sid]]],
            ],
        ];

        $response = $this->execute($this->variable_index, $body);

        $found = $response['hits']['total']['value'] ?? 0;
        $total = $response['aggregations']['total_in_study']['doc_count'] ?? $found;

        $rows = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $s      = $hit['_source'];
            $rows[] = [
                'uid'  => $s['id']       ?? null,
                'name' => $s['name']     ?? null,
                'labl' => $s['label']    ?? null,
                'qstn' => $s['question'] ?? null,
                'vid'  => $s['vid']      ?? null,
                'fid'  => $s['fid']      ?? null,
            ];
        }

        return [
            'found'  => $found,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
            'rows'   => $rows,
        ];
    }

    // =========================================================================
    // Query building — all methods return arrays, never strings
    // =========================================================================

    // =========================================================================
    // Variable query building
    // =========================================================================

    private function build_variable_query(int $limit, int $offset): array
    {
        $filters = [
            ['term' => ['survey_published' => 1]],
        ];

        // Year range
        $from = (int)($this->from ?? 0);
        $to   = (int)($this->to   ?? 0);
        if ($from > 0 || $to > 0) {
            $range = [];
            if ($from > 0) $range['gte'] = $from;
            if ($to   > 0) $range['lte'] = $to;
            $filters[] = ['range' => ['year_start' => $range]];
        }

        // Dataset type
        $types = $this->normalise_array($this->type);
        if (!empty($types)) {
            $filters[] = ['terms' => ['dataset_type' => $types]];
        }

        // Country filter — denormalized into variable documents
        $countries = $this->normalise_int_array($this->countries);
        if (!empty($countries)) {
            $filters[] = ['terms' => ['country_ids' => $countries]];
        }

        $bool = empty($filters) ? [] : ['filter' => $filters];

        $fulltext = $this->build_variable_fulltext_clause($this->variable_keywords);
        if ($fulltext !== null) {
            $bool['must'] = [$fulltext];
        }

        if (empty($bool)) {
            $query = ['match_all' => (object)[]];
        } else {
            $query = ['bool' => $bool];
        }

        return [
            'size'              => $limit,
            'from'              => $offset,
            'track_total_hits'  => true,
            '_source'           => ['id', 'survey_id', 'survey_idno', 'survey_title', 'survey_nation',
                                    'vid', 'name', 'label', 'question'],
            'query'             => $query,
            'sort'              => $this->build_variable_sort(),
            'aggs'              => [
                'total_all' => ['global' => (object)[], 'aggs' => ['count' => ['value_count' => ['field' => '_id']]]],
            ],
        ];
    }

    private function build_variable_fulltext_clause(string $keywords): ?array
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return null;
        }

        $is_phrase = (substr($keywords, 0, 1) === '"' && substr($keywords, -1) === '"');
        $query_str = trim($keywords, '"');

        return [
            'multi_match' => array_filter([
                'query'               => $query_str,
                'fields'              => ['name^30', 'label^20', 'question^10', 'categories', 'survey_idno^40'],
                'type'                => $is_phrase ? 'phrase' : 'most_fields',
                'minimum_should_match'=> $is_phrase ? null     : '2<75%',
                'fuzziness'           => $is_phrase ? null     : 'AUTO',
                'prefix_length'       => $is_phrase ? null     : 2,
            ]),
        ];
    }

    /**
     * @param bool $quick_only_fields When true, only sort fields present on v_quick_search _source (name/label/score).
     */
    private function build_variable_sort(bool $quick_only_fields = false): array
    {
        $def_by  = $this->ci->config->item('catalog_default_sort_by');
        $def_ord = $this->ci->config->item('catalog_default_sort_order');
        list($key, $order) = Catalog_study_sort::resolve(
            trim((string) $this->variable_keywords),
            $this->sort_by,
            $this->sort_order,
            $def_by,
            $def_ord
        );
        $key   = strtolower(trim($key));
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        if ($key === 'relevance' || $key === 'rank') {
            return [['_score' => 'desc'], ['name.keyword' => 'asc']];
        }

        if ($quick_only_fields) {
            $quick_map = [
                'title'   => 'name.keyword',
                'nation'  => 'name.keyword',
                'country' => 'name.keyword',
                'year'    => 'name.keyword',
            ];
            if ($key === 'popularity') {
                $order = 'asc';
            }
            $field = $quick_map[$key] ?? 'name.keyword';
            return [[$field => $order], ['name.keyword' => 'asc']];
        }

        $map = [
            'title'      => 'survey_title.keyword',
            'nation'     => 'survey_nation.keyword',
            'country'    => 'survey_nation.keyword',
            'year'       => 'year_start',
            'popularity' => 'year_start',
        ];
        if ($key === 'popularity') {
            $order = 'desc';
        }
        $field = $map[$key] ?? 'name.keyword';

        return [[$field => $order], ['name.keyword' => 'asc']];
    }

    private function format_variable_hits(array $hits): array
    {
        $rows = [];
        foreach ($hits as $hit) {
            $s      = $hit['_source'];
            $rows[] = [
                'uid'    => $s['id']            ?? null,
                'name'   => $s['name']          ?? null,
                'labl'   => $s['label']         ?? null,
                'qstn'   => $s['question']      ?? null,
                'vid'    => $s['vid']           ?? null,
                'title'  => $s['survey_title']  ?? null,
                'idno'   => $s['survey_idno']   ?? null,
                'nation' => $s['survey_nation'] ?? null,
                'sid'    => $s['survey_id']     ?? null,
            ];
        }
        return $rows;
    }

    // =========================================================================
    // Survey query building
    // =========================================================================

    private function build_survey_query(int $limit, int $offset): array
    {
        $filters = $this->build_filters();

        // Build the bool query
        $bool = ['filter' => $filters];

        $fulltext = $this->build_fulltext_clause($this->study_keywords);
        if ($fulltext !== null) {
            $bool['must'] = [$fulltext];
        }

        return [
            'size'             => $limit,
            'from'             => $offset,
            'track_total_hits' => true,
            '_source'          => $this->survey_source_fields(),
            'query'            => ['bool' => $bool],
            'sort'             => $this->build_sort(),
            'aggs'             => [
                'by_type'    => ['terms'  => ['field' => 'dataset_type', 'size' => 50]],
                'total_all'  => ['global' => (object)[], 'aggs' => ['count' => ['value_count' => ['field' => '_id']]]],
            ],
        ];
    }

    /**
     * Build the full-text clause.
     * Supports:
     *   - plain keywords           → multi_match across all search fields
     *   - "quoted phrase"          → multi_match with phrase type
     *   - field:value              → match on a specific field
     *   - +must -exclude keywords  → bool must/must_not breakdown
     */
    private function build_fulltext_clause(string $keywords): ?array
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return null;
        }

        // field:value  →  single-field match
        if (preg_match('/^(\w+):(.+)$/', $keywords, $m)) {
            $field = self::$allowed_search_fields[strtolower($m[1])] ?? null;
            if ($field !== null) {
                return ['match' => [$field => ['query' => trim($m[2])]]];
            }
        }

        // +must -exclude  →  bool breakdown
        if (preg_match('/[+-]/', $keywords)) {
            return $this->build_advanced_fulltext($keywords);
        }

        // "exact phrase"
        $is_phrase = (substr($keywords, 0, 1) === '"' && substr($keywords, -1) === '"');
        $query_str = trim($keywords, '"');

        return [
            'multi_match' => array_filter([
                'query'               => $query_str,
                'fields'              => $this->survey_search_fields(),
                'type'                => $is_phrase ? 'phrase' : 'most_fields',
                'minimum_should_match'=> $is_phrase ? null     : '2<75%',
                'fuzziness'           => $is_phrase ? null     : 'AUTO',
                'prefix_length'       => $is_phrase ? null     : 2,
            ]),
        ];
    }

    /**
     * Parse +term / -term boolean keyword syntax into a bool must/must_not query.
     */
    private function build_advanced_fulltext(string $keywords): array
    {
        $must_not = [];
        $must     = [];
        $should   = [];

        // Split on whitespace, keeping quoted groups together
        preg_match_all('/[+-]?"[^"]+"|[+-]?\S+/', $keywords, $tokens);

        foreach ($tokens[0] as $token) {
            $prefix = substr($token, 0, 1);
            $term   = ltrim($token, '+-');

            $clause = [
                'multi_match' => [
                    'query'  => trim($term, '"'),
                    'fields' => $this->survey_search_fields(),
                    'type'   => 'best_fields',
                ],
            ];

            if ($prefix === '-') {
                $must_not[] = $clause;
            } elseif ($prefix === '+') {
                $must[] = $clause;
            } else {
                $should[] = $clause;
            }
        }

        $bool = [];
        if ($must)     $bool['must']     = $must;
        if ($must_not) $bool['must_not'] = $must_not;
        if ($should)   $bool['should']   = $should;

        return ['bool' => $bool];
    }

    /**
     * Build the filter array — each element is an OpenSearch filter clause.
     */
    private function build_filters(): array
    {
        $filters = [];

        // Always filter to published surveys
        $filters[] = ['term' => ['published' => 1]];

        // Dataset type
        $types = $this->normalise_array($this->type);
        if (!empty($types)) {
            $filters[] = ['terms' => ['dataset_type' => $types]];
        }

        // Form ID (dtype)
        $dtypes = $this->normalise_int_array($this->dtype);
        if (!empty($dtypes)) {
            $filters[] = ['terms' => ['form_id' => $dtypes]];
        }

        // Countries
        $countries = $this->normalise_int_array($this->countries);
        if (!empty($countries)) {
            $filters[] = ['terms' => ['country_ids' => $countries]];
        }

        // Regions
        $regions = $this->normalise_int_array($this->regions);
        if (!empty($regions)) {
            $filters[] = ['terms' => ['region_ids' => $regions]];
        }

        // Repository
        $repo = trim($this->repo ?? '');
        if ($repo !== '') {
            $filters[] = ['term' => ['repository_ids' => $repo]];
        }

        // Collections (repository memberships — values are repositoryid strings)
        $collections = $this->normalise_array($this->collections);
        if (!empty($collections)) {
            $filters[] = ['terms' => ['repository_ids' => $collections]];
        }

        // Year range
        $from = (int)($this->from ?? 0);
        $to   = (int)($this->to   ?? 0);
        if ($from > 0 || $to > 0) {
            $range = [];
            if ($from > 0) $range['gte'] = $from;
            if ($to   > 0) $range['lte'] = $to;
            $filters[] = ['range' => ['years' => $range]];
        }

        // Surveys with variables only
        if ($this->varcount === 'with_vars' || $this->varcount === '1') {
            $filters[] = ['range' => ['var_count' => ['gte' => 1]]];
        }

        // Exact survey ID list
        $sid_str = trim($this->sid ?? '');
        if ($sid_str !== '') {
            $sids = array_filter(array_map('intval', explode(',', $sid_str)));
            if (!empty($sids)) {
                $filters[] = ['terms' => ['id' => $sids]];
            }
        }

        // Created date range — expects "YYYY-MM-DD,YYYY-MM-DD"
        $created = trim($this->created ?? '');
        if ($created !== '') {
            $parts = explode(',', $created, 2);
            if (count($parts) === 2) {
                $ts_from = strtotime(trim($parts[0]));
                $ts_to   = strtotime(trim($parts[1]));
                if ($ts_from && $ts_to) {
                    $filters[] = ['range' => ['created' => ['gte' => $ts_from, 'lte' => $ts_to]]];
                }
            }
        }

        // Data classification
        $data_class = $this->normalise_int_array($this->data_class);
        if (!empty($data_class)) {
            $filters[] = ['terms' => ['data_class_id' => $data_class]];
        }

        // Country ISO3 — convert codes to country_ids via DB lookup
        $iso3 = trim($this->country_iso3 ?? '');
        if ($iso3 !== '') {
            $codes = array_filter(array_map('trim', explode(',', $iso3)), fn($c) => strlen($c) === 3);
            if (!empty($codes)) {
                $rows = $this->ci->db
                    ->select('countryid')
                    ->where_in('iso', $codes)
                    ->get('countries')
                    ->result_array();
                $cids = array_map('intval', array_column($rows, 'countryid'));
                if (!empty($cids)) {
                    $filters[] = ['terms' => ['country_ids' => $cids]];
                }
            }
        }

        // User-defined facets — each facet's selected term IDs filter via topic_ids
        foreach ($this->user_facets as $fc) {
            $name   = $fc['name'];
            $values = isset($this->$name) ? $this->normalise_int_array($this->$name) : [];
            if (!empty($values)) {
                $filters[] = ['terms' => ['topic_ids' => $values]];
            }
        }

        return $filters;
    }

    private function is_user_facet_key(string $key): bool
    {
        foreach ($this->user_facets as $fc) {
            if ($fc['name'] === $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build the sort clause.
     * Primary sort field + stable secondary tiebreakers.
     */
    private function build_sort(): array
    {
        $def_by  = $this->ci->config->item('catalog_default_sort_by');
        $def_ord = $this->ci->config->item('catalog_default_sort_order');
        list($key, $order) = Catalog_study_sort::resolve(
            trim((string) $this->study_keywords),
            $this->sort_by,
            $this->sort_order,
            $def_by,
            $def_ord
        );
        $key   = strtolower(trim($key));
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        $map_entry  = self::$sort_map[$key] ?? ['title.keyword', 'asc'];
        $field      = $map_entry[0];

        // For _score the order must be desc
        if ($field === '_score') {
            return [
                ['_score'      => 'desc'],
                ['year_start'  => 'desc'],
                ['title.keyword' => 'asc'],
            ];
        }

        return [
            [$field      => $order],
            ['year_start' => 'desc'],
            ['title.keyword' => 'asc'],
        ];
    }

    // =========================================================================
    // Result formatting — no DB re-fetch
    // =========================================================================

    /**
     * Map OpenSearch hit _source arrays to the response row format.
     */
    private function format_survey_hits(array $hits): array
    {
        $rows = [];
        foreach ($hits as $hit) {
            $s    = $hit['_source'] ?? [];
            $rows[] = [
                'id'              => (int)($s['id']              ?? 0),
                'type'            => $s['dataset_type']          ?? null,
                'idno'            => $s['idno']                  ?? null,
                'doi'             => $s['doi']                   ?? null,
                'title'           => $s['title']                 ?? null,
                'subtitle'        => $s['subtitle']              ?? null,
                'abstract'        => $s['abstract']              ?? null,
                'nation'          => $s['nation']                ?? null,
                'authoring_entity'=> $s['authoring_entity']      ?? null,
                'form_model'      => $s['form_model']            ?? null,
                'data_class_id'   => isset($s['data_class_id'])  ? (int)$s['data_class_id'] : null,
                'year_start'      => isset($s['year_start'])     ? (int)$s['year_start']    : null,
                'year_end'        => isset($s['year_end'])       ? (int)$s['year_end']      : null,
                'thumbnail'       => $s['thumbnail']             ?? null,
                'repositoryid'    => $s['repository_id']         ?? null,
                'repo_title'      => $s['repo_title']            ?? null,
                'link_da'         => $s['link_da']               ?? null,
                'created'         => isset($s['created'])        ? (int)$s['created']       : null,
                'changed'         => isset($s['changed'])        ? (int)$s['changed']       : null,
                'total_views'     => isset($s['total_views'])    ? (int)$s['total_views']   : 0,
                'total_downloads' => isset($s['total_downloads'])? (int)$s['total_downloads']:0,
                'varcount'        => isset($s['var_count'])      ? (int)$s['var_count']     : 0,
            ];
        }
        return $rows;
    }

    // =========================================================================
    // Execution
    // =========================================================================

    /**
     * Execute a search query against an index.
     * Throws on connection/query errors so callers can decide how to handle them.
     */
    private function execute(string $index, array $body): array
    {
        try {
            return OpenSearch_client::get()->search([
                'index' => $index,
                'body'  => $body,
            ]);
        } catch (Exception $e) {
            log_message('error', "catalog_search_opensearch::execute({$index}): " . $e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    // Lightweight DB helpers
    // =========================================================================

    /**
     * Fetch citation counts for a list of survey IDs.
     * This is a small, indexed lookup — acceptable as a DB call.
     */
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

    // =========================================================================
    // Field lists
    // =========================================================================

    /** Fields stored in the index that are returned in search results. */
    private function survey_source_fields(): array
    {
        return [
            'id', 'idno', 'doi', 'title', 'subtitle', 'nation', 'authoring_entity',
            'abstract',
            'dataset_type', 'form_model', 'data_class_id',
            'year_start', 'year_end',
            'repository_id', 'repo_title',
            'thumbnail', 'link_da',
            'created', 'changed',
            'total_views', 'total_downloads', 'var_count',
        ];
    }

    /** Fields and boost weights used in full-text multi_match queries. */
    private function survey_search_fields(): array
    {
        return [
            'idno^60',
            'title^40',
            'nation^30',
            'authoring_entity^10',
            'keywords^10',
            'abstract',
            'methodology',
            'var_keywords^15',
        ];
    }

    // =========================================================================
    // Normalisation helpers
    // =========================================================================

    private function normalise_array($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        return [];
    }

    private function normalise_int_array($value): array
    {
        $arr = $this->normalise_array($value);
        return array_values(array_filter(array_map('intval', $arr)));
    }

    /**
     * Count how many variable documents in nada_variables match $keywords
     * for each study in $id_list.
     * Uses a terms aggregation (size=page) — zero rows returned, counts only.
     *
     * @param  int[]  $id_list   Survey IDs on the current result page
     * @param  string $keywords  Raw search string
     * @return array             [ survey_id => match_count ]
     */
    private function search_variable_counts(array $id_list, string $keywords): array
    {
        if (empty($id_list) || trim($keywords) === '') {
            return [];
        }

        $fulltext = $this->build_variable_fulltext_clause($keywords);
        if ($fulltext === null) {
            return [];
        }

        $body = [
            'size'             => 0,
            'track_total_hits' => false,
            'query'            => [
                'bool' => [
                    'must'   => [$fulltext],
                    'filter' => [
                        ['terms' => ['survey_id' => $id_list]],
                    ],
                ],
            ],
            'aggs' => [
                'by_survey' => [
                    'terms' => [
                        'field' => 'survey_id',
                        'size'  => count($id_list),
                    ],
                ],
            ],
        ];

        try {
            $response = $this->execute($this->variable_index, $body);
        } catch (Exception $e) {
            log_message('error', 'Catalog_search_opensearch::search_variable_counts — ' . $e->getMessage());
            return [];
        }

        $counts = [];
        foreach ($response['aggregations']['by_survey']['buckets'] ?? [] as $bucket) {
            $counts[(int)$bucket['key']] = (int)$bucket['doc_count'];
        }
        return $counts;
    }
}
