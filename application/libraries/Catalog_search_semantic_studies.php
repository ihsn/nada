<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI), study search API driver
 *
 * Used when search_engine = nada_ai.
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
 * The API result is the source of truth for found, tab counts and order; the database only supplies row data. Every
 * keyword match is counted and paged (only the semantic side is bounded, by the API), so found can be large; when it
 * exceeds the depth the engine can page to, semantic_note says so.
 * A hit whose row is missing or whose idno differs from the API's (the index is out of sync with this catalog)
 * is left out of the page and reported in semantic_note.
 *
 * An exact idno or alias is answered by the database, as in every other driver. A request the API cannot take (a page
 * of more than API_MAX_PAGE_SIZE studies, or topic/database/sid filters selecting more than API_MAX_SIDS studies) is
 * served by the database search, and semantic_note says why.
 *
 * When nada-ai cannot be reached or fails on its side (timeout, HTTP 5xx or 429), the page is served by the
 * database search and result.semantic_fallback says so. A request the API rejects (HTTP 4xx: bad key, invalid
 * filters, an engine without the study search) is a configuration or contract error and is raised, not hidden.
 */

require_once dirname(__FILE__) . '/Catalog_search_semantic_base.php';
require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';

class catalog_search_semantic_studies extends catalog_search_semantic_base
{
    /** Largest page the study search API serves (contract: limit <= 100). */
    private const API_MAX_PAGE_SIZE = 100;

    /** Largest id list the API accepts in filters.sids. */
    private const API_MAX_SIDS = 5000;

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

    // =========================================================================
    // Public interface
    // =========================================================================

    public function search(int $limit = 15, int $offset = 0): array
    {
        // a browse (no keyword) past max_offset studies continues by cursor (next_cursor/prev_cursor of a result)
        $cursor = trim((string) $this->cursor);

        // an exact idno or alias is answered by the database, as in every other driver
        $exact = Catalog_study_idno_lookup::try_search_from_params($this->database_params(), $limit, $offset);
        if ($exact !== null) {
            return $this->with_offset_paging($exact);
        }

        if ($limit > self::API_MAX_PAGE_SIZE) {
            $this->refuse_cursor($cursor, 'a page of more than ' . self::API_MAX_PAGE_SIZE . ' studies');
            return $this->search_in_database(
                $limit,
                $offset,
                sprintf('Pages of more than %d studies are served by the catalog database search.', self::API_MAX_PAGE_SIZE)
            );
        }

        $t0      = microtime(true);
        $filters = $this->build_filters();

        if ($filters === null) {
            // a filter was given whose values match nothing: no study can match, nothing to ask the API
            return $this->with_offset_paging($this->result([], [], 0, [], $limit, $offset));
        }

        if (count($filters['sids'] ?? []) > self::API_MAX_SIDS) {
            $this->refuse_cursor($cursor, 'a search the catalog database serves (more than ' . self::API_MAX_SIDS . ' studies selected)');
            return $this->search_in_database(
                $limit,
                $offset,
                sprintf(
                    'The topic, database or study filters select more than %d studies, so these results come from the catalog database search.',
                    self::API_MAX_SIDS
                )
            );
        }

        $body = $this->build_request($filters, $limit, $offset);
        if ($cursor !== '') {
            unset($body['offset']);
            $body['cursor'] = $cursor;
        }

        try {
            $response = $this->post_json('/studies/search', $body, ['found', 'hits', 'search_counts_by_type']);
        } catch (Semantic_search_api_exception $e) {
            if (!$this->falls_back($e)) {
                throw $e;
            }
            return $this->search_in_database_after($e, $limit, $cursor === '' ? $offset : 0);
        }
        $offset = (int) ($response['offset'] ?? $offset);

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
        if (!empty($response['truncated'])) {
            // every match is counted, but the search engine cannot page beyond a fixed depth
            $notes[] = 'More studies match than can be paged through; refine your search to narrow the results.';
        }

        $result = $this->result(
            $rows,
            $response['search_counts_by_type'],
            (int) $response['found'],
            array_filter($notes, 'strlen'),
            $limit,
            $offset
        );
        $result += [
            'found_is_lower_bound' => false,
            'max_offset'           => isset($response['max_offset']) ? (int) $response['max_offset'] : null,
            'next_cursor'          => $response['next_cursor'] ?? null,
            'prev_cursor'          => $response['prev_cursor'] ?? null,
            'paging'               => $response['applied']['paging'] ?? 'offset',
        ];

        if ($this->debug) {
            $result['debug'] = [
                'request'         => $body,
                'engine'          => $response['engine'] ?? null,
                'applied'         => $response['applied'] ?? null,
                'truncated'       => $response['truncated'] ?? null,
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

    protected function auth_headers(): array
    {
        return $this->api_key !== '' ? ['X-NADA-Admin-Key: ' . $this->api_key] : [];
    }

    // =========================================================================
    // Variable search — POST /variables/search (lexical only; see nada-ai's docs/variables-search-contract.md).
    //
    // Keyword searches and keyword-less browsing both go to the API, with every sidebar filter (dataset type,
    // countries, years, collections, repository, data access type): nada-ai applies them to the variable's study,
    // through its study index, with the same meaning as for the study search.
    // A page is reached by offset for the first max_offset rows (nada-ai's setting), then by the cursors each result
    // carries (next_cursor/prev_cursor, passed through unread: set $cursor to one to get its page). The result adds
    // found_is_lower_bound, max_offset, next_cursor, prev_cursor and paging to the database drivers' shape.
    //
    // Served by the database search instead (offset paging, any depth, max_offset null): a page of more than
    // API_MAX_PAGE_SIZE rows, a per-study listing without a keyword, or an outage (then from the first page when a
    // cursor was given, since the database cannot read one).
    // =========================================================================

    public function vsearch(int $limit = 15, int $offset = 0): array
    {
        $cursor   = trim((string) $this->cursor);
        $keywords = trim((string) $this->variable_keywords);

        if ($limit > self::API_MAX_PAGE_SIZE) {
            $this->refuse_cursor($cursor, 'a page of more than ' . self::API_MAX_PAGE_SIZE . ' variables');
            return $this->with_offset_paging($this->database_search()->vsearch($limit, $offset));
        }

        $filters = $this->variable_api_filters();
        if ($filters === null) {
            // a country or data access filter whose values match nothing: no variable can match, nothing to ask the API
            return $this->with_offset_paging(['found' => 0, 'total' => 0, 'limit' => $limit, 'offset' => 0, 'rows' => []]);
        }

        $body = $this->variable_request($keywords, $filters, $limit, $offset, $cursor);
        try {
            $response = $this->post_json('/variables/search', $body, ['found', 'hits', 'offset']);
        } catch (Semantic_search_api_exception $e) {
            if (!$this->falls_back($e)) {
                throw $e;
            }
            return $this->variables_in_database_after($e, $this->database_search()->vsearch($limit, $cursor === '' ? $offset : 0));
        }

        return $this->variable_result($response, $limit, $this->published_variable_total());
    }

    /** How long the catalog-wide published variable count is reused (seconds). */
    private const VARIABLE_TOTAL_TTL = 600;

    /**
     * The catalog's published variable count (`total` of a variable search result). It counts every published variable
     * in the database — a heavy query on a large catalog for a number that changes slowly — so it is cached for
     * VARIABLE_TOTAL_TTL seconds rather than counted on every search.
     */
    private function published_variable_total(): int
    {
        $this->ci->load->driver('cache', ['adapter' => 'file']);
        $cache = $this->ci->cache->file->is_supported() ? $this->ci->cache->file : null;
        $key   = 'nada_ai_published_variable_total';
        if ($cache !== null) {
            $cached = $cache->get($key);
            if ($cached !== false) {
                return (int) $cached;
            }
        }
        $total = (int) $this->database_search()->get_total_variable_count();
        if ($cache !== null) {
            $cache->save($key, $total, self::VARIABLE_TOTAL_TTL);
        }

        return $total;
    }

    public function v_quick_search(?int $sid = null, int $limit = 50, int $offset = 0): array
    {
        $cursor   = trim((string) $this->cursor);
        $keywords = trim((string) $this->variable_keywords);

        if ($sid === null || $keywords === '' || $limit > self::API_MAX_PAGE_SIZE) {
            $this->refuse_cursor($cursor, 'a study variable listing without a keyword, or a page of more than '
                . self::API_MAX_PAGE_SIZE . ' variables');
            return $this->with_offset_paging($this->database_search()->v_quick_search($sid, $limit, $offset));
        }

        $body = $this->variable_request($keywords, ['sids' => [$sid]], $limit, $offset, $cursor);
        try {
            $response = $this->post_json('/variables/search', $body, ['found', 'hits', 'offset']);
        } catch (Semantic_search_api_exception $e) {
            if (!$this->falls_back($e)) {
                throw $e;
            }
            return $this->variables_in_database_after(
                $e,
                $this->database_search()->v_quick_search($sid, $limit, $cursor === '' ? $offset : 0)
            );
        }

        // total is this one study's own variable count, not the catalog's — same denominator the database
        // driver's v_quick_search() uses.
        $total = (int) $this->ci->db->where('sid', $sid)->count_all_results('variables');

        return $this->variable_result($response, $limit, $total);
    }

    /**
     * The API filters of a catalog-wide variable search: dataset types (the tab), countries (names, codes and regions
     * resolved to ids), the data collection year range, data access forms, collections and the active repository —
     * built as the study search builds them (build_filters()).
     *
     * @return array|null null when a country or data access filter matches nothing (nothing can match)
     */
    private function variable_api_filters(): ?array
    {
        $filters = [];
        $types   = $this->tab_types();
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
        list($year_from, $year_to) = $this->year_bounds();
        if ($year_from > 0) {
            $filters['year_from'] = $year_from;
        }
        if ($year_to > 0) {
            $filters['year_to'] = $year_to;
        }
        $form_ids = $this->resolve_form_ids();
        if ($form_ids === false) {
            return null;
        }
        if (!empty($form_ids)) {
            $filters['form_ids'] = $form_ids;
        }
        $collections = $this->normalise_array($this->collections);
        if (!empty($collections)) {
            $filters['collections'] = $collections;
        }
        $repo = trim((string) $this->repo);
        if ($repo !== '' && $repo !== 'central') {
            $filters['repository'] = $repo;
        }

        return $filters;
    }

    /** The POST /variables/search body: no query for a browse, and the cursor in place of the offset when given. */
    private function variable_request(string $keywords, array $filters, int $limit, int $offset, string $cursor): array
    {
        $body = ['limit' => $limit];
        if ($keywords !== '') {
            $body['query'] = $keywords;
        }
        if ($cursor !== '') {
            $body['cursor'] = $cursor;
        } else {
            $body['offset'] = $offset;
        }
        if (!empty($filters)) {
            $body['filters'] = $filters;
        }
        $sort = $this->variable_sort_field();
        if ($sort !== null) {
            $body['sort']  = $sort['by'];
            $body['order'] = $sort['order'];
        }

        return $body;
    }

    /**
     * A cursor pages a search the API serves: one given to a search the database must serve came from another search
     * (its page size changed since). Raised (Search_cursor_exception) rather than silently restarting from the first page.
     */
    private function refuse_cursor(string $cursor, string $why): void
    {
        if ($cursor !== '') {
            throw new Search_cursor_exception("A cursor cannot page {$why}: start again from the first page.");
        }
    }

    /**
     * The API's `sort` (`name` or `title`) and `order` (`asc`/`desc`), sent as two top-level fields.
     *
     * @return array{by: string, order: string}|null null = the API default (relevance with a query, title without)
     */
    private function variable_sort_field(): ?array
    {
        $key = strtolower(trim((string) $this->sort_by));
        $sort = $key === 'name' ? 'name' : ($key === 'title' ? 'title' : null);
        if ($sort === null) {
            return null;
        }

        return ['by' => $sort, 'order' => strtolower((string) $this->sort_order) === 'desc' ? 'desc' : 'asc'];
    }

    /** A database variable result with the paging fields of an API one: offset paging to any depth, no cursors. */
    private function with_offset_paging(array $result): array
    {
        return $result + [
            'found_is_lower_bound' => false,
            'max_offset'           => null,
            'next_cursor'          => null,
            'prev_cursor'          => null,
            'paging'               => 'offset',
        ];
    }

    private function variables_in_database_after(Semantic_search_api_exception $e, array $result): array
    {
        $result = $this->with_offset_paging($result);
        $result['semantic_fallback'] = 'Semantic search is unavailable, so these results come from the catalog database search.';
        if ($this->debug) {
            $result['debug'] = ['fallback_reason' => $e->getMessage(), 'url' => $e->url, 'http_status' => $e->http_status];
        }

        return $result;
    }

    /**
     * API variable-search response -> the database drivers' {found, total, limit, offset, rows} shape plus the paging
     * fields. The API returns the matching variables by id; the variables themselves are read from the database (the
     * variable index stores ids only), in the order of the hits. A hit whose variable or study is gone, or whose study
     * is not published, is left out.
     */
    private function variable_result(array $response, int $limit, int $total): array
    {
        $uids = array_map(static function (array $hit): int {
            return (int) $hit['uid'];
        }, $response['hits']);

        $by_uid = [];
        if (!empty($uids)) {
            $this->ci->db->select(
                'v.uid, v.sid, v.fid, df.file_name, v.vid, v.name, v.labl, v.qstn,'
                . ' surveys.title AS title, surveys.idno, surveys.nation, surveys.year_start, surveys.year_end,'
                . ' surveys.authoring_entity',
                false
            );
            $this->ci->db->from('variables v');
            $this->ci->db->join('surveys', 'v.sid = surveys.id', 'inner');
            $this->ci->db->join('data_files df', 'df.sid = v.sid AND df.file_id = v.fid', 'left'); // the file's name
            $this->ci->db->where('surveys.published', 1);
            $this->ci->db->where_in('v.uid', $uids);
            foreach ($this->ci->db->get()->result_array() as $row) {
                $by_uid[(int) $row['uid']] = $row;
            }
        }

        $rows = [];
        foreach ($uids as $uid) {
            if (isset($by_uid[$uid])) {
                $rows[] = $by_uid[$uid];
            }
        }

        $result = [
            'found'                => (int) $response['found'],
            'total'                => $total,
            'limit'                => $limit,
            'offset'               => (int) $response['offset'],
            'rows'                 => $rows,
            'found_is_lower_bound' => !empty($response['found_is_lower_bound']),
            'max_offset'           => isset($response['max_offset']) ? (int) $response['max_offset'] : null,
            'next_cursor'          => $response['next_cursor'] ?? null,
            'prev_cursor'          => $response['prev_cursor'] ?? null,
            'paging'               => $response['applied']['paging'] ?? 'offset',
        ];
        if ($this->debug) {
            $result['debug'] = ['request_applied' => $response['applied'] ?? null, 'timing_ms' => $response['timing_ms'] ?? null];
        }

        return $result;
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

        if ($this->debug) {
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

        $types = $this->tab_types();
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
            // more than API_MAX_SIDS is routed to the database by search()
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

        list($year_from, $year_to) = $this->year_bounds();
        if ($year_from > 0) {
            $filters['year_from'] = $year_from;
        }
        if ($year_to > 0) {
            $filters['year_to'] = $year_to;
        }

        $created = $this->created_bounds();
        if ($created !== null) {
            list($filters['created_from'], $filters['created_to']) = $created;
        }

        $facets = $this->resolve_user_facets();
        if ($facets === false) {
            return null;
        }
        if (!empty($facets)) {
            $filters['facets'] = $facets;
        }

        return $filters;
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
        $by_sid = $this->fetch_rows(array_map(static fn($hit) => (int) $hit['sid'], $hits));

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
            $page = (int) ($passage['page'] ?? 0);
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

    // =========================================================================
    // Database fallback
    // =========================================================================

    /** A search the API cannot take, served by the database search; $reason goes to semantic_note. */
    private function search_in_database(int $limit, int $offset, string $reason): array
    {
        $result = $this->with_offset_paging($this->database_search()->search($limit, $offset));
        $result['semantic_note'] = $reason;

        return $result;
    }

    private function search_in_database_after(Semantic_search_api_exception $e, int $limit, int $offset): array
    {
        $result = $this->with_offset_paging($this->database_search()->search($limit, $offset));
        $result['semantic_fallback'] = 'Semantic search is unavailable, so these results come from the catalog database search.';

        if ($this->debug) {
            $result['debug'] = ['fallback_reason' => $e->getMessage(), 'url' => $e->url, 'http_status' => $e->http_status];
        }

        return $result;
    }
}
