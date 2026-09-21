<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI), study search API driver
 *
 * Used when semantic_search_engine = opensearch. (qdrant keeps the original driver, catalog_search_semantic;
 * qdrant_db uses catalog_search_semantic_fused.)
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
 * When nada-ai cannot be reached or fails on its side (timeout, HTTP 5xx or 429), the page is served by the
 * database search and result.semantic_fallback says so. A request the API rejects (HTTP 4xx: bad key, invalid
 * filters, an engine without the study search) is a configuration or contract error and is raised, not hidden.
 */

require_once dirname(__FILE__) . '/Catalog_search_semantic_base.php';

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
            $response = $this->post_json('/studies/search', $body, ['found', 'hits', 'search_counts_by_type']);
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
        // the admin key is used while debugging, because include_debug needs the admin role
        $key = ($this->debug && $this->admin_api_key !== '') ? $this->admin_api_key : $this->api_key;

        return $key !== '' ? ['X-NADA-Admin-Key: ' . $key] : [];
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

    private function search_in_database_after(Semantic_search_api_exception $e, int $limit, int $offset): array
    {
        $result = $this->database_search()->search($limit, $offset);
        $result['semantic_fallback'] = 'Semantic search is unavailable, so these results come from the catalog database search.';

        if ($this->debug) {
            $result['debug'] = ['fallback_reason' => $e->getMessage(), 'url' => $e->url, 'http_status' => $e->http_status];
        }

        return $result;
    }
}
