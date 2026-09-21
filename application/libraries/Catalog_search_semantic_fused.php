<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog Search — Semantic (AI), Qdrant + database driver
 *
 * Used when semantic_search_engine = qdrant_db. The database keyword search and Qdrant's vector search each
 * rank the catalog; the two rankings are fused, and the database stays the gate for every filter, the source of
 * the counts, and the source of the rows.
 *
 *   No keywords         -> the plain database search (browsing needs no semantic search).
 *   Keywords            -> 1. cut set: the best keyword matches from the database (semantic_search_keyword_window)
 *                             and a small window of the nearest studies from Qdrant (semantic_search_window,
 *                             at most 100), fused by rank (reciprocal rank fusion) into one list of study ids.
 *                             The cut set does not depend on the dataset-type tab, the sort or the page, so
 *                             pages are stable; it is cached briefly so paging does not embed the query again.
 *                          2. the database keeps the studies that pass the sidebar filters and reports their
 *                             types: found is their count (narrowed by the tab), the tab counts are their
 *                             counts by type.
 *                          3. relevance order comes from the fusion; any other sort is done by the database
 *                             over the same set. The rows of the page are loaded from the database.
 *
 * Qdrant is asked for the studies (one hit per study id, the passages that matched a document included) and is
 * pre-filtered by the filters it can express; filters it cannot (data classification, creation date, study id
 * lists, topics, timeseries databases) are applied by the database in step 2, so they can only make the
 * semantic window smaller. Studies are identified by surveys.id, which needs the Qdrant collection to have been
 * indexed with the study id (metadata.sid); hits without one are reported in semantic_note.
 *
 * If Qdrant is unreachable or fails on its side (timeout, 5xx, 429) the cut set is the keyword matches alone and
 * result.semantic_fallback says so. A request Qdrant's API rejects (4xx) is raised.
 */

require_once dirname(__FILE__) . '/Catalog_search_semantic_base.php';
require_once dirname(__FILE__) . '/Catalog_study_idno_lookup.php';
require_once dirname(__FILE__) . '/Semantic_document_pages.php';

class catalog_search_semantic_fused extends catalog_search_semantic_base
{
    /** nada-ai's POST /search serves at most this many hits. */
    private const API_MAX_WINDOW = 100;

    /** Reciprocal rank fusion rank constant. */
    private const RRF_K = 60;

    /** Studies asked of Qdrant. */
    private $window;

    /** Keyword matches considered from the database. */
    private $keyword_window;

    /** Qdrant score floor (null = none) and the fraction of the best Qdrant score to keep (0 = all). */
    private $min_score;
    private $relative_cutoff;

    private $keyword_weight;
    private $vector_weight;
    private $cache_ttl;
    private $knn_k;
    private $query_prompt;
    private $inner_hits_size;

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $cfg = $this->ci->config;

        $this->window          = min(self::API_MAX_WINDOW, max(1, (int) $cfg->item('semantic_search_window') ?: 50));
        $this->keyword_window  = max(1, (int) $cfg->item('semantic_search_keyword_window') ?: 500);
        $min                   = $cfg->item('semantic_search_min_score');
        $this->min_score       = ($min === null || $min === '' || $min === false) ? null : (float) $min;
        $this->relative_cutoff = min(1.0, max(0.0, (float) $cfg->item('semantic_search_relative_cutoff')));
        $this->keyword_weight  = (float) ($cfg->item('semantic_search_keyword_weight') ?: 1.0);
        $this->vector_weight   = (float) ($cfg->item('semantic_search_vector_weight') ?: 1.0);
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

        $cut = $this->cut_set($filters);

        // the studies that pass every sidebar filter (the dataset-type tab is applied below)
        $order = array_keys($cut['studies']);
        $types = $this->database_search()->filtered_study_types($order);
        $kept  = array_values(array_filter($order, static fn($sid) => isset($types[$sid])));

        $counts = array_count_values(array_map(static fn($sid) => $types[$sid], $kept));
        $tab    = $this->tab_types();
        $shown  = empty($tab) ? $kept : array_values(array_filter($kept, static fn($sid) => in_array($types[$sid], $tab, true)));

        if ($this->is_relevance_sort()) {
            if (strtolower(trim((string) $this->sort_order)) === 'asc') {
                $shown = array_reverse($shown);
            }
            $found = count($shown);
            $by_id = $this->fetch_rows(array_slice($shown, $offset, $limit));
            $rows  = [];
            foreach (array_slice($shown, $offset, $limit) as $sid) {
                if (isset($by_id[$sid])) {
                    $rows[] = $by_id[$sid];
                }
            }
        } else {
            // the database sorts and pages the same set of studies
            $scoped = $this->database_search()->search_for_survey_ids($kept, $limit, $offset);
            $found  = (int) ($scoped['found'] ?? 0);
            $counts = $scoped['search_counts_by_type'] ?? $counts;
            $rows   = $scoped['rows'] ?? [];
        }

        $position = array_flip($order);
        foreach ($rows as $i => $row) {
            $sid   = (int) $row['id'];
            $info  = $cut['studies'][$sid] ?? [];
            $rows[$i]['semantic_document_pages'] = $row['type'] === 'document' ? ($info['pages'] ?? []) : [];
            if ($this->debug) {
                $rows[$i]['semantic_hit'] = [
                    '_score'      => $info['score'] ?? null,
                    'matched_by'  => $info['matched_by'] ?? [],
                    'fused_rank'  => isset($position[$sid]) ? $position[$sid] + 1 : null,
                ];
            }
        }

        $result = $this->result($rows, $counts, $found, $cut['notes'], $limit, $offset);

        if ($cut['fallback'] !== null) {
            $result['semantic_fallback'] = $cut['fallback'];
        }

        if ($this->debug) {
            $result['debug'] = [
                'cut_set'          => count($order),
                'keyword_matches'  => $cut['keyword_count'],
                'semantic_matches' => $cut['semantic_count'],
                'passed_filters'   => count($kept),
                'counts_source'    => 'fused_cut_set',
                'cached'           => $cut['cached'],
                'window'           => $this->window,
                'elapsed'          => round(microtime(true) - $t0, 4),
            ];
        }

        return $result;
    }

    protected function auth_headers(): array
    {
        return $this->api_key !== '' ? ['Authorization: Bearer ' . $this->api_key] : [];
    }

    private function is_relevance_sort(): bool
    {
        return in_array(strtolower(trim((string) $this->sort_by)), ['rank', 'relevance'], true);
    }

    // =========================================================================
    // The cut set
    // =========================================================================

    /**
     * The fused list of studies for the keyword and the filters, best first.
     *
     * @return array{studies: array<int, array>, notes: string[], fallback: ?string,
     *               keyword_count: int, semantic_count: int, cached: bool}
     */
    private function cut_set(array $qdrant_filters): array
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

        $keyword_ids = $this->database_search()->ranked_study_ids($this->keyword_window, false);

        $notes    = [];
        $fallback = null;
        $semantic = [];
        try {
            list($semantic, $missing, $unindexed) = $this->semantic_hits($qdrant_filters);
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

        $cut = [
            'studies'        => $this->fuse($keyword_ids, $semantic),
            'notes'          => $notes,
            'fallback'       => $fallback,
            'keyword_count'  => count($keyword_ids),
            'semantic_count' => count($semantic),
            'cached'         => false,
        ];

        // a degraded cut set must not outlive the outage
        if ($cache !== null && $fallback === null) {
            $cache->save($key, $cut, $this->cache_ttl);
        }

        return $cut;
    }

    /**
     * Reciprocal rank fusion of the two rankings: a study's score is the sum, over the rankings it is in, of
     * weight / (RRF_K + rank). Ties break by study id.
     *
     * @param int[]                $keyword_ids best keyword match first
     * @param array<int, array>    $semantic    study id => hit info, best first
     * @return array<int, array>   study id => {score, matched_by, pages}, best first
     */
    private function fuse(array $keyword_ids, array $semantic): array
    {
        $scores     = [];
        $matched_by = [];

        foreach ($keyword_ids as $i => $sid) {
            $scores[$sid]      = ($scores[$sid] ?? 0.0) + $this->keyword_weight / (self::RRF_K + $i + 1);
            $matched_by[$sid][] = 'keyword';
        }

        $rank = 0;
        foreach ($semantic as $sid => $info) {
            $rank++;
            $scores[$sid]      = ($scores[$sid] ?? 0.0) + $this->vector_weight / (self::RRF_K + $rank);
            $matched_by[$sid][] = 'semantic';
        }

        $sids = array_keys($scores);
        usort($sids, static fn($a, $b) => ($scores[$b] <=> $scores[$a]) ?: ($a <=> $b));

        $studies = [];
        foreach ($sids as $sid) {
            $studies[$sid] = [
                'score'      => $semantic[$sid]['score'] ?? null,
                'matched_by' => $matched_by[$sid],
                'pages'      => $semantic[$sid]['pages'] ?? [],
            ];
        }

        return $studies;
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

    /** The file cache, or null when caching is off or not writable here. The cut set is only an optimisation. */
    private function cache()
    {
        if ($this->cache_ttl <= 0) {
            return null;
        }

        $this->ci->load->driver('cache', ['adapter' => 'file']);

        return $this->ci->cache->file->is_supported() ? $this->ci->cache->file : null;
    }

    /**
     * Everything that decides the cut set: the keyword, the filters, the windows and the ranking settings. Not the
     * dataset-type tab, the sort or the page.
     */
    private function cache_key(array $qdrant_filters): string
    {
        $params = $this->database_params();
        unset($params['type'], $params['sort_by'], $params['sort_order']);

        return 'semantic_fused_' . md5(json_encode([
            $params,
            $qdrant_filters,
            [$this->window, $this->keyword_window, $this->min_score, $this->relative_cutoff, $this->keyword_weight, $this->vector_weight],
        ]));
    }
}
