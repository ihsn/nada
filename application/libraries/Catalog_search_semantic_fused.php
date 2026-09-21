<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI), Qdrant + database driver
 *
 * Used when semantic_search_engine = qdrant_db. The result is the semantic matches from Qdrant pinned to the
 * top, followed by the catalog database's own keyword search result without those studies:
 *
 *   No keywords     -> the plain database search (browsing needs no semantic search).
 *   Keywords        -> 1. the semantic block: the nearest studies from Qdrant (semantic_search_window, at most
 *                         100), kept when they clear the score floor and are close enough to the best one. The
 *                         database keeps those that pass the sidebar filters, and the ones that also match the
 *                         keyword come first. The block is cached briefly so paging and re-sorting do not query
 *                         Qdrant again.
 *                      2. the database keyword search, with the block's studies excluded, supplies everything
 *                         after it: its own relevance order, its own paging (so every page of a large result
 *                         exists) and its own counts.
 *                      found is the block plus that result; the tab counts are the database's counts plus the
 *                      block's studies that are not keyword matches. The rows of the page are loaded from the
 *                      database, so the cards are the same whatever the engine.
 *
 * Only a relevance sort merges the two. Any other sort lists the database's keyword matches only (the semantic
 * block has no place in a title or year order) and says so in semantic_note.
 *
 * Qdrant is pre-filtered by the filters it can express; filters it cannot (data classification, creation date,
 * study id lists, topics, timeseries databases) are applied by the database, so they can only make the semantic
 * block smaller. Studies are identified by surveys.id, which needs the Qdrant collection to have been indexed
 * with the study id (metadata.sid); hits without one are reported in semantic_note.
 *
 * If Qdrant is unreachable or fails on its side (timeout, 5xx, 429) the block is empty, so the result is the
 * plain database search, and result.semantic_fallback says so. A request Qdrant's API rejects (4xx) is raised.
 */

require_once dirname(__FILE__) . '/Catalog_search_semantic_base.php';
require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';
require_once dirname(__FILE__) . '/Semantic_document_pages.php';

class catalog_search_semantic_fused extends catalog_search_semantic_base
{
    /** nada-ai's POST /search serves at most this many hits. */
    private const API_MAX_WINDOW = 100;

    /** Studies asked of Qdrant. */
    private $window;

    /** Qdrant score floor (null = none) and the fraction of the best Qdrant score to keep (0 = all). */
    private $min_score;
    private $relative_cutoff;

    private $cache_ttl;
    private $knn_k;
    private $query_prompt;
    private $inner_hits_size;

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $cfg = $this->ci->config;

        $this->window          = min(self::API_MAX_WINDOW, max(1, (int) $cfg->item('semantic_search_window') ?: 50));
        $min                   = $cfg->item('semantic_search_min_score');
        $this->min_score       = ($min === null || $min === '' || $min === false) ? null : (float) $min;
        $this->relative_cutoff = min(1.0, max(0.0, (float) $cfg->item('semantic_search_relative_cutoff')));
        $this->cache_ttl       = max(0, (int) $cfg->item('semantic_search_cache_ttl'));
        $this->knn_k           = (int) $cfg->item('semantic_search_knn_k') ?: 50;
        $this->query_prompt    = (string) $cfg->item('semantic_search_query_prompt');
        $inner                 = (int) $cfg->item('semantic_search_collapse_inner_hits_size');
        $this->inner_hits_size = $inner > 0 ? $inner : 5;
    }

    // =========================================================================
    // Public interface
    // =========================================================================

    public function search(int $limit = 15, int $offset = 0): array
    {
        if (trim((string) $this->study_keywords) === '') {
            return $this->database_search()->search($limit, $offset);
        }

        // an exact idno or alias is answered by the database, as in every other driver
        $exact = Catalog_study_idno_lookup::try_search_from_params($this->database_params(), $limit, $offset);
        if ($exact !== null) {
            return $exact;
        }

        $t0      = microtime(true);
        $filters = $this->qdrant_filters();

        if ($filters === null) {
            // a filter was given whose values match nothing
            return $this->result([], [], 0, [], $limit, $offset);
        }

        if (!$this->is_relevance_sort()) {
            $result = $this->database_search()->search($limit, $offset);
            $result['semantic_note'] = 'Results sorted by something other than relevance list keyword matches only; sort by relevance to include related results.';

            return $result;
        }

        $semantic = $this->semantic_block($filters);
        $db       = $this->database_search();

        // the semantic studies that pass the sidebar filters, and which of them also match the keyword
        $types   = $db->filtered_study_types(array_keys($semantic['studies']));
        $passing = array_values(array_filter(array_keys($semantic['studies']), static fn($sid) => isset($types[$sid])));
        $usable  = $db->has_usable_keyword();
        $agree   = array_flip($usable ? $db->keyword_matching_ids($passing) : []);

        // the block: what also matches the keyword first, each group in Qdrant's order; narrowed by the tab
        $ordered = array_merge(
            array_values(array_filter($passing, static fn($sid) => isset($agree[$sid]))),
            array_values(array_filter($passing, static fn($sid) => !isset($agree[$sid])))
        );
        $tab   = $this->tab_types();
        $block = empty($tab) ? $ordered : array_values(array_filter($ordered, static fn($sid) => in_array($types[$sid], $tab, true)));
        $size  = count($block);

        // the studies in the block that the keyword search would not list are added to its counts
        $extra_counts = array_count_values(array_map(
            static fn($sid) => $types[$sid],
            array_values(array_filter($passing, static fn($sid) => !isset($agree[$sid])))
        ));

        // the rest of the page, and found and the tab counts, come from the keyword search without the block
        $from_block = max(0, min($limit, $size - $offset));
        $tail       = ['found' => 0, 'rows' => [], 'search_counts_by_type' => []];
        if ($usable) {
            $tail_search = $this->database_search_with(['exclude_sid' => $block]);
            // a search that shows no rows is still asked for one, because it also supplies found and the counts
            $tail_rows   = $limit - $from_block;
            $tail        = $tail_search->search(max(1, $tail_rows), max(0, $offset - $size)) ?: $tail;
        } else {
            $tail_rows = 0;
        }

        $rows = [];
        if ($from_block > 0) {
            $slice = array_slice($block, $offset, $from_block);
            $by_id = $this->fetch_rows($slice);
            foreach ($slice as $sid) {
                if (!isset($by_id[$sid])) {
                    continue;
                }
                $info = $semantic['studies'][$sid];
                $row  = $by_id[$sid];
                $row['semantic_document_pages'] = $row['type'] === 'document' ? $info['pages'] : [];
                if ($this->debug) {
                    $row['semantic_hit'] = [
                        '_score'     => $info['score'],
                        'matched_by' => isset($agree[$sid]) ? ['semantic', 'keyword'] : ['semantic'],
                        'pinned_at'  => array_search($sid, $block, true) + 1,
                    ];
                }
                $rows[] = $row;
            }
        }
        if ($tail_rows > 0) {
            foreach ($tail['rows'] as $row) {
                $row['semantic_document_pages'] = [];
                if ($this->debug) {
                    $row['semantic_hit'] = ['_score' => null, 'matched_by' => ['keyword']];
                }
                $rows[] = $row;
            }
        }

        $counts = [];
        foreach (array_keys($tail['search_counts_by_type'] + $extra_counts) as $type) {
            $counts[$type] = (int) ($tail['search_counts_by_type'][$type] ?? 0) + (int) ($extra_counts[$type] ?? 0);
        }

        $result = $this->result($rows, $counts, $size + (int) $tail['found'], $semantic['notes'], $limit, $offset);

        if ($semantic['fallback'] !== null) {
            $result['semantic_fallback'] = $semantic['fallback'];
        }

        if ($this->debug) {
            $result['debug'] = [
                'semantic_hits'     => count($semantic['studies']),
                'passed_filters'    => count($passing),
                'also_keyword'      => count($agree),
                'pinned_block'      => $size,
                'keyword_found'     => (int) $tail['found'],
                'keyword_usable'    => $usable,
                'counts_source'     => 'database_counts_plus_semantic_extras',
                'cached'            => $semantic['cached'],
                'window'            => $this->window,
                'elapsed'           => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    protected function auth_headers(): array
    {
        return $this->api_key !== '' ? ['Authorization: Bearer ' . $this->api_key] : [];
    }

    /** The relevance order, best first: the only order in which the semantic block has a place. */
    private function is_relevance_sort(): bool
    {
        return in_array(strtolower(trim((string) $this->sort_by)), ['rank', 'relevance'], true)
            && strtolower(trim((string) $this->sort_order)) !== 'asc';
    }

    // =========================================================================
    // The semantic block
    // =========================================================================

    /**
     * The nearest studies from Qdrant for the keyword and the filters, best first (before the database's gate).
     *
     * @return array{studies: array<int, array>, notes: string[], fallback: ?string, cached: bool}
     */
    private function semantic_block(array $qdrant_filters): array
    {
        $cache = $this->cache();
        $key   = $cache !== null ? $this->cache_key($qdrant_filters) : null;
        if ($cache !== null) {
            $stored = $cache->get($key);
            if (is_array($stored)) {
                $stored['cached'] = true;

                return $stored;
            }
        }

        $notes    = [];
        $fallback = null;
        $studies  = [];
        try {
            list($studies, $missing, $unindexed) = $this->semantic_hits($qdrant_filters);
            if ($missing > 0) {
                $notes[] = sprintf(
                    '%d semantic result(s) have no study id: the semantic index was built before study ids were stored. Re-index it.',
                    $missing
                );
            }
            if ($unindexed) {
                $notes[] = 'The semantic index has no study ids (it was built before study ids were stored), so only keyword matches are shown. Re-index it.';
            }
        } catch (Semantic_search_api_exception $e) {
            if (!$this->is_outage($e)) {
                throw $e;
            }
            log_message('error', 'catalog_search_semantic_fused: semantic search unavailable: ' . $e->getMessage());
            $fallback = 'Semantic search is unavailable, so these results are keyword matches only.';
        }

        $block = ['studies' => $studies, 'notes' => $notes, 'fallback' => $fallback, 'cached' => false];

        // a degraded block must not outlive the outage
        if ($cache !== null && $fallback === null) {
            $cache->save($key, $block, $this->cache_ttl);
        }

        return $block;
    }

    // =========================================================================
    // Qdrant
    // =========================================================================

    /**
     * The nearest studies from Qdrant (POST /search, one hit per study), within the score floor and the
     * relative cutoff.
     *
     * @return array{0: array<int, array>, 1: int, 2: bool} study id => {score, pages}, best first; hits left out for
     *         lack of a study id; whether the whole index lacks study ids
     * @throws Semantic_search_api_exception
     */
    private function semantic_hits(array $filters): array
    {
        $body = [
            'query'               => trim((string) $this->study_keywords),
            'mode'                => 'vector',
            'size'                => $this->window,
            'from'                => 0,
            'knn_k'               => $this->knn_k,
            'include_embedding'   => false,
            'include_facets'      => false,
            'include_total'       => false,
            'collapse_field'      => 'sid',
            'collapse_inner_hits' => ['name' => 'variants', 'size' => $this->inner_hits_size],
        ];
        if ($this->query_prompt !== '') {
            $body['query_prompt'] = $this->query_prompt;
        }
        if ($this->min_score !== null) {
            $body['vector_score_threshold'] = $this->min_score;
        }
        if (!empty($filters)) {
            $body['filters'] = $filters;
        }

        $response = $this->post_json('/search', $body, ['hits']);

        // Qdrant groups hits by study id and returns nothing at all when no point has one, which looks the same as
        // "nothing is similar"; ask once without the floor and the filters to tell the two apart
        $unindexed = empty($response['hits']) && !$this->index_has_study_ids($body);

        $studies = [];
        $missing = 0;
        foreach ($response['hits'] as $hit) {
            $sid = (int) ($hit['_source']['metadata']['sid'] ?? 0);
            if ($sid < 1) {
                $missing++;
                continue;
            }
            if (isset($studies[$sid])) {
                continue;
            }
            $studies[$sid] = [
                'score' => (float) ($hit['_score'] ?? 0.0),
                'pages' => Semantic_document_pages::from_hit($hit),
            ];
        }

        if (!empty($studies) && $this->relative_cutoff > 0) {
            $best      = reset($studies)['score'];
            $threshold = $best * $this->relative_cutoff;
            $studies   = array_filter($studies, static fn($info) => $info['score'] >= $threshold);
        }

        return [$studies, $missing, $unindexed];
    }

    /**
     * Whether the semantic index returns any hit grouped by study id: false means it was built before study ids
     * were stored. Only asked when a search found nothing.
     *
     * @throws Semantic_search_api_exception
     */
    private function index_has_study_ids(array $body): bool
    {
        $probe = array_diff_key($body, ['vector_score_threshold' => 1, 'filters' => 1]);
        $probe['size'] = 1;

        return !empty($this->post_json('/search', $probe, ['hits'])['hits']);
    }

    /**
     * The filters Qdrant can express, in its /search format (string values), or null when a filter can match
     * no study at all (that includes the filters only the database applies).
     *
     * @return array<string, string[]>|null
     */
    private function qdrant_filters(): ?array
    {
        $filters = ['published' => ['1']];

        $countries = $this->resolve_country_ids();
        if ($countries === false) {
            return null;
        }
        if (!empty($countries)) {
            $filters['countries'] = $this->strings($countries);
        }

        $form_ids = $this->resolve_form_ids();
        if ($form_ids === false) {
            return null;
        }
        if (!empty($form_ids)) {
            $filters['formid'] = $this->strings($form_ids);
        }

        $tags = $this->normalise_array($this->tags);
        if (!empty($tags)) {
            $filters['tags'] = $this->strings($tags);
        }

        $years = $this->year_values();
        if (!empty($years)) {
            $filters['years'] = $years;
        }

        $repo = trim((string) $this->repo);
        if ($repo !== '' && $repo !== 'central') {
            $filters['repositoryid'] = [$repo];
        }

        $collections = $this->normalise_array($this->collections);
        if (!empty($collections)) {
            $filters['repositories'] = $this->strings($collections);
        }

        $facets = $this->resolve_user_facets();
        if ($facets === false) {
            return null;
        }
        foreach ($facets as $name => $terms) {
            $filters[$name] = $this->strings($terms);
        }

        // the filters only the database applies still decide whether anything can match
        if ($this->resolve_sids() === false || $this->resolve_int_list($this->data_class) === false) {
            return null;
        }

        return $filters;
    }

    /**
     * The year range as the list of years Qdrant matches any of; an open end runs to the next year (or back to
     * 1900).
     *
     * @return string[]
     */
    private function year_values(): array
    {
        list($from, $to) = $this->year_bounds();
        if ($from === 0 && $to === 0) {
            return [];
        }

        $from = $from > 0 ? $from : 1900;
        $to   = $to > 0 ? $to : (int) date('Y') + 1;

        return $this->strings(range($from, min($to, $from + 400)));
    }

    /** @return string[] */
    private function strings(array $values): array
    {
        return array_values(array_map('strval', $values));
    }

    // =========================================================================
    // Cache
    // =========================================================================

    /** The file cache, or null when caching is off or not writable here. The cached block is only an optimisation. */
    private function cache()
    {
        if ($this->cache_ttl <= 0) {
            return null;
        }

        $this->ci->load->driver('cache', ['adapter' => 'file']);

        return $this->ci->cache->file->is_supported() ? $this->ci->cache->file : null;
    }

    /**
     * Everything that decides the semantic block: the keyword, the filters and the Qdrant settings. Not the
     * dataset-type tab, the sort or the page.
     */
    private function cache_key(array $qdrant_filters): string
    {
        $params = $this->database_params();
        unset($params['type'], $params['sort_by'], $params['sort_order']);

        return 'semantic_block_' . md5(json_encode([
            $params,
            $qdrant_filters,
            [$this->window, $this->min_score, $this->relative_cutoff],
        ]));
    }
}
