<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Citation Search — OpenSearch Backend
 *
 * Drop-in replacement for Citation_search_mysql / Citation_search_sqlsrv.
 * Same public interface:
 *   search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid)
 * Sets $this->search_found_rows after each call.
 *
 * All display data is served from the nada_citations index — no DB re-fetch.
 *
 * Filter keys (same as DB backends):
 *   keywords         — fulltext across title, subtitle, alt_title, authors_text,
 *                      abstract, keywords, notes, organization, doi
 *   ctype            — array of citation types
 *   from             — pub_year >= N
 *   to               — pub_year <= N
 *   flag             — array of flag values
 *   user             — array of changed_by user IDs (integers)
 *   has_notes        — truthy: only citations with a notes value
 *   no_survey_attached — truthy: citations with survey_count == 0
 *   url_status       — array of url_status values
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

class Citation_search_opensearch
{
    public $search_found_rows = 0;

    private $index;

    public function __construct()
    {
        $this->index = OpenSearch_client::index('citations');
    }

    // =========================================================================
    // Public API
    // =========================================================================

    public function search(
        $limit      = null,
        $offset     = null,
        $filter     = null,
        $sort_by    = null,
        $sort_order = null,
        $published  = null,
        $repositoryid = null
    ): array
    {
        $limit  = max(1, (int)($limit  ?? 15));
        $offset = max(0, (int)($offset ?? 0));

        [$must, $filters] = $this->build_query_parts($filter, $published, $repositoryid);

        $bool = [];
        if (!empty($must))    { $bool['must']   = $must;    }
        if (!empty($filters)) { $bool['filter'] = $filters; }

        $body = [
            'size'  => $limit,
            'from'  => $offset,
            'query' => empty($bool) ? ['match_all' => (object)[]] : ['bool' => $bool],
            'sort'  => $this->build_sort($sort_by, $sort_order, !empty($must)),
        ];

        try {
            $response = OpenSearch_client::get()->search([
                'index' => $this->index,
                'body'  => $body,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Citation_search_opensearch::search: ' . $e->getMessage());
            $this->search_found_rows = 0;
            return [];
        }

        $this->search_found_rows = (int)($response['hits']['total']['value'] ?? 0);

        return $this->format_hits($response['hits']['hits'] ?? []);
    }

    // =========================================================================
    // Query building
    // =========================================================================

    /**
     * Returns [$must_clauses, $filter_clauses].
     */
    private function build_query_parts(
        ?array $filter,
        $published,
        $repositoryid
    ): array
    {
        $must    = [];
        $filters = [];

        // Published
        if (is_numeric($published)) {
            $filters[] = ['term' => ['published' => (int)$published]];
        }

        // Repository — citations linked to surveys in this repository
        if (!empty($repositoryid) && strtolower((string)$repositoryid) !== 'central') {
            $filters[] = ['term' => ['repository_ids' => (string)$repositoryid]];
        }

        if (empty($filter)) {
            return [$must, $filters];
        }

        foreach ($filter as $key => $value) {
            switch ($key) {

                case 'keywords':
                    if (is_string($value) && trim($value) !== '') {
                        $must[] = [
                            'multi_match' => [
                                'query'  => $value,
                                'fields' => [
                                    'title^3', 'subtitle', 'alt_title',
                                    'authors_text^2', 'organization',
                                    'abstract', 'keywords', 'notes', 'doi',
                                ],
                                'type'                 => 'best_fields',
                                'fuzziness'            => 'AUTO',
                                'minimum_should_match' => '75%',
                            ],
                        ];
                    }
                    break;

                case 'ctype':
                    if (is_array($value) && !empty($value)) {
                        $filters[] = ['terms' => ['ctype' => $value]];
                    }
                    break;

                case 'from':
                    if (strlen((string)$value) === 4 && is_numeric($value)) {
                        $filters[] = ['range' => ['pub_year' => ['gte' => (int)$value]]];
                    }
                    break;

                case 'to':
                    if (strlen((string)$value) === 4 && is_numeric($value)) {
                        $filters[] = ['range' => ['pub_year' => ['lte' => (int)$value]]];
                    }
                    break;

                case 'flag':
                    if (is_array($value) && !empty($value)) {
                        $filters[] = ['terms' => ['flag' => $value]];
                    }
                    break;

                case 'user':
                    if (is_array($value) && !empty($value)) {
                        $filters[] = ['terms' => ['changed_by' => array_map('intval', $value)]];
                    }
                    break;

                case 'has_notes':
                    if (!empty($value)) {
                        $filters[] = ['exists' => ['field' => 'notes']];
                    }
                    break;

                case 'no_survey_attached':
                    if (!empty($value)) {
                        $filters[] = ['term' => ['survey_count' => 0]];
                    }
                    break;

                case 'url_status':
                    if (is_array($value) && !empty($value)) {
                        $filters[] = ['terms' => ['url_status' => $value]];
                    }
                    break;
            }
        }

        return [$must, $filters];
    }

    /**
     * Build a sort array for OpenSearch.
     */
    private function build_sort(?string $sort_by, ?string $sort_order, bool $has_keywords): array
    {
        $dir = (strtolower((string)$sort_order) === 'asc') ? 'asc' : 'desc';

        $sortable = [
            'id'         => 'id',
            'title'      => 'title.keyword',
            'authors'    => 'authors_text.keyword',
            'pub_year'   => 'pub_year',
            'changed'    => 'changed',
            'created'    => 'created',
            'ctype'      => 'ctype',
            'published'  => 'published',
            'flag'       => 'flag',
            'url_status' => 'url_status',
        ];

        if (!empty($sort_by) && isset($sortable[$sort_by])) {
            return [[$sortable[$sort_by] => ['order' => $dir]]];
        }

        // Default: relevance if keyword search, otherwise newest first
        if ($has_keywords) {
            return [['_score' => ['order' => 'desc']], ['created' => ['order' => 'desc']]];
        }

        return [['created' => ['order' => 'desc']]];
    }

    // =========================================================================
    // Result formatting
    // =========================================================================

    private function format_hits(array $hits): array
    {
        $rows = [];
        foreach ($hits as $hit) {
            $src  = $hit['_source'] ?? [];
            $rows[] = [
                'id'                 => $src['id']                 ?? null,
                'uuid'               => $src['uuid']               ?? null,
                'title'              => $src['title']              ?? null,
                'subtitle'           => $src['subtitle']           ?? null,
                'alt_title'          => $src['alt_title']          ?? null,
                'authors'            => $src['authors']            ?? [],
                'editors'            => $src['editors']            ?? null,
                'translators'        => $src['translators']        ?? null,
                'abstract'           => $src['abstract']           ?? null,
                'keywords'           => $src['keywords']           ?? null,
                'notes'              => $src['notes']              ?? null,
                'organization'       => $src['organization']       ?? null,
                'doi'                => $src['doi']                ?? null,
                'url'                => $src['url']                ?? null,
                'ctype'              => $src['ctype']              ?? null,
                'pub_year'           => $src['pub_year']           ?? null,
                'pub_month'          => $src['pub_month']          ?? null,
                'pub_day'            => $src['pub_day']            ?? null,
                'volume'             => $src['volume']             ?? null,
                'issue'              => $src['issue']              ?? null,
                'edition'            => $src['edition']            ?? null,
                'place_publication'  => $src['place_publication']  ?? null,
                'place_state'        => $src['place_state']        ?? null,
                'publisher'          => $src['publisher']          ?? null,
                'page_from'          => $src['page_from']          ?? null,
                'page_to'            => $src['page_to']            ?? null,
                'publication_medium' => $src['publication_medium'] ?? null,
                'flag'               => $src['flag']               ?? null,
                'url_status'         => $src['url_status']         ?? null,
                'owner'              => $src['owner']              ?? null,
                'published'          => $src['published']          ?? null,
                'created'            => $src['created']            ?? null,
                'changed'            => $src['changed']            ?? null,
                'survey_count'       => $src['survey_count']       ?? 0,
                // Fields not in index — callers receive null
                'idnumber'           => null,
                'data_accessed'      => null,
                'changed_by_user'    => null,
                'created_by_user'    => null,
            ];
        }
        return $rows;
    }
}
