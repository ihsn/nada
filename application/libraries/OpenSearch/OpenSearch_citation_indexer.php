<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Citation Indexer
 *
 * Builds and bulk-indexes citation documents into the nada_citations index.
 *
 * All fields needed for display and filtering are denormalized into each
 * document — no DB re-fetch after search.
 *
 * Authors (from citation_authors), survey_ids and repository_ids
 * (from survey_citations + surveys + survey_repos) are batch-loaded
 * and stored on each document.
 *
 * Public API
 * ----------
 * index_citation(int $id): bool
 * index_citations_batch(int $offset, int $batch_size = 100): array
 * delete_citation(int $id): bool
 * count(): int
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

class OpenSearch_citation_indexer
{
    private $ci;
    private $index;

    public function __construct()
    {
        $this->ci    =& get_instance();
        $this->index = OpenSearch_client::index('citations');
    }

    // =========================================================================
    // Public API
    // =========================================================================

    public function index_citation(int $id): bool
    {
        $rows = $this->load_rows(null, null, $id);
        if (empty($rows)) {
            log_message('error', "OpenSearch_citation_indexer::index_citation — id {$id} not found");
            return false;
        }

        $ids      = [$id];
        $authors  = $this->load_authors($ids);
        $surveys  = $this->load_survey_links($ids);

        return $this->bulk_index([$this->build_document($rows[0], $authors, $surveys)]);
    }

    /**
     * Batch-index citations using LIMIT/OFFSET.
     * Returns: ['indexed' => N, 'errors' => [...], 'has_more' => bool]
     */
    public function index_citations_batch(int $offset = 0, int $batch_size = 100): array
    {
        $rows = $this->load_rows($batch_size, $offset);

        if (empty($rows)) {
            return ['indexed' => 0, 'errors' => [], 'has_more' => false];
        }

        $ids     = array_column($rows, 'id');
        $authors = $this->load_authors($ids);
        $surveys = $this->load_survey_links($ids);

        $documents = [];
        foreach ($rows as $row) {
            $documents[] = $this->build_document($row, $authors, $surveys);
        }

        $errors = [];
        $this->bulk_index($documents, $errors);

        return [
            'indexed'  => count($documents),
            'errors'   => $errors,
            'has_more' => count($rows) === $batch_size,
        ];
    }

    public function delete_citation(int $id): bool
    {
        try {
            OpenSearch_client::get()->delete([
                'index'   => $this->index,
                'id'      => $id,
                'refresh' => true,
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_citation_indexer::delete_citation({$id}): " . $e->getMessage());
            return false;
        }
    }

    public function count(): int
    {
        try {
            $response = OpenSearch_client::get()->count(['index' => $this->index]);
            return (int)($response['count'] ?? 0);
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_citation_indexer::count: ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    // Document building
    // =========================================================================

    private function build_document(array $row, array $authors_map, array $surveys_map): array
    {
        $id          = (int)$row['id'];
        $authors     = $authors_map[$id]  ?? [];
        $survey_data = $surveys_map[$id]  ?? ['ids' => [], 'repo_ids' => []];

        // Build searchable author string: "John Smith; Jane Doe"
        $authors_text = implode('; ', array_map(function ($a) {
            return trim(($a['fname'] ?? '') . ' ' . ($a['lname'] ?? ''));
        }, $authors));

        return [
            'id'                 => $id,
            'uuid'               => $row['uuid']               ?? null,
            'title'              => $row['title']              ?? null,
            'subtitle'           => $row['subtitle']           ?? null,
            'alt_title'          => $row['alt_title']          ?? null,
            'authors_text'       => $authors_text ?: null,
            'authors'            => $authors,
            'editors'            => $row['editors']            ?? null,
            'translators'        => $row['translators']        ?? null,
            'abstract'           => $row['abstract']           ?? null,
            'keywords'           => $row['keywords']           ?? null,
            'notes'              => $row['notes']              ?? null,
            'organization'       => $row['organization']       ?? null,
            'doi'                => $row['doi']                ?? null,
            'url'                => $row['url']                ?? null,
            'ctype'              => $row['ctype']              ?? null,
            'pub_year'           => isset($row['pub_year'])    ? (int)$row['pub_year']   : null,
            'pub_month'          => $row['pub_month']          ?? null,
            'pub_day'            => $row['pub_day']            ?? null,
            'volume'             => $row['volume']             ?? null,
            'issue'              => $row['issue']              ?? null,
            'edition'            => $row['edition']            ?? null,
            'place_publication'  => $row['place_publication']  ?? null,
            'place_state'        => $row['place_state']        ?? null,
            'publisher'          => $row['publisher']          ?? null,
            'page_from'          => $row['page_from']          ?? null,
            'page_to'            => $row['page_to']            ?? null,
            'publication_medium' => isset($row['publication_medium']) ? (int)$row['publication_medium'] : null,
            'flag'               => $row['flag']               ?? null,
            'url_status'         => $row['url_status']         ?? null,
            'owner'              => $row['owner']              ?? null,
            'published'          => isset($row['published'])   ? (int)$row['published']  : 0,
            'created'            => isset($row['created'])     ? (int)$row['created']    : null,
            'changed'            => isset($row['changed'])     ? (int)$row['changed']    : null,
            'created_by'         => isset($row['created_by'])  ? (int)$row['created_by'] : null,
            'changed_by'         => isset($row['changed_by'])  ? (int)$row['changed_by'] : null,
            'survey_ids'         => $survey_data['ids'],
            'survey_count'       => count($survey_data['ids']),
            'repository_ids'     => $survey_data['repo_ids'],
        ];
    }

    // =========================================================================
    // Database loading
    // =========================================================================

    private function load_rows(?int $limit, ?int $offset, ?int $id = null): array
    {
        $this->ci->db->select(
            'id, uuid, title, subtitle, alt_title, authors, editors, translators,
             abstract, keywords, notes, organization, doi, url, ctype,
             pub_year, pub_month, pub_day, volume, issue, edition,
             place_publication, place_state, publisher, page_from, page_to,
             publication_medium, flag, url_status, owner,
             published, created, changed, created_by, changed_by',
            false
        );
        $this->ci->db->from('citations');

        if ($id !== null) {
            $this->ci->db->where('id', $id);
        } else {
            $this->ci->db->order_by('id', 'ASC');
            $this->ci->db->limit($limit, $offset);
        }

        return $this->ci->db->get()->result_array();
    }

    /**
     * Load authors for a batch of citation IDs.
     * Returns [citation_id => [{fname, lname, initial, author_type}, ...], ...]
     */
    private function load_authors(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $this->ci->db
            ->select('cid, fname, lname, initial, author_type')
            ->where_in('cid', $ids)
            ->get('citation_authors')
            ->result_array();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['cid']][] = [
                'fname'       => $row['fname']       ?? null,
                'lname'       => $row['lname']       ?? null,
                'initial'     => $row['initial']     ?? null,
                'author_type' => $row['author_type'] ?? null,
            ];
        }
        return $map;
    }

    /**
     * Load survey links and repository IDs for a batch of citation IDs.
     *
     * Returns [citation_id => ['ids' => [sid, ...], 'repo_ids' => [repositoryid, ...]], ...]
     *
     * 2 queries for any batch size:
     *   1. survey_citations → get sids per citation
     *   2. surveys + survey_repos → get all repository IDs for those surveys
     */
    private function load_survey_links(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Query 1: citation → survey IDs
        $sc_rows = $this->ci->db
            ->select('citationid, sid')
            ->where_in('citationid', $ids)
            ->get('survey_citations')
            ->result_array();

        $citation_to_sids = [];
        $all_sids         = [];
        foreach ($sc_rows as $row) {
            $cid = (int)$row['citationid'];
            $sid = (int)$row['sid'];
            $citation_to_sids[$cid][] = $sid;
            $all_sids[]               = $sid;
        }

        if (empty($all_sids)) {
            // No linked surveys — return empty arrays for all citation IDs
            $map = [];
            foreach ($ids as $id) {
                $map[$id] = ['ids' => [], 'repo_ids' => []];
            }
            return $map;
        }

        // Query 2: surveys + survey_repos → repository IDs per survey
        $all_sids = array_unique($all_sids);

        $repo_rows = $this->ci->db
            ->select('surveys.id AS sid, surveys.repositoryid, sr.repositoryid AS repo_id2')
            ->from('surveys')
            ->join('survey_repos sr', 'sr.sid = surveys.id', 'left')
            ->where_in('surveys.id', $all_sids)
            ->get()
            ->result_array();

        // Build sid → unique repository_ids map
        $sid_to_repos = [];
        foreach ($repo_rows as $row) {
            $sid = (int)$row['sid'];
            if (!empty($row['repositoryid'])) {
                $sid_to_repos[$sid][$row['repositoryid']] = true;
            }
            if (!empty($row['repo_id2'])) {
                $sid_to_repos[$sid][$row['repo_id2']] = true;
            }
        }

        // Assemble per-citation result
        $map = [];
        foreach ($ids as $id) {
            $sids     = $citation_to_sids[$id] ?? [];
            $repo_ids = [];
            foreach ($sids as $sid) {
                foreach (array_keys($sid_to_repos[$sid] ?? []) as $repo_id) {
                    $repo_ids[$repo_id] = true;
                }
            }
            $map[$id] = [
                'ids'      => $sids,
                'repo_ids' => array_keys($repo_ids),
            ];
        }

        return $map;
    }

    // =========================================================================
    // Bulk indexing
    // =========================================================================

    private function bulk_index(array $documents, array &$errors = []): bool
    {
        if (empty($documents)) {
            return true;
        }

        $body = [];
        foreach ($documents as $doc) {
            $body[] = ['index' => ['_index' => $this->index, '_id' => $doc['id']]];
            $body[] = $doc;
        }

        try {
            $response = OpenSearch_client::get()->bulk(['body' => $body, 'refresh' => false]);

            if (!empty($response['errors'])) {
                foreach ($response['items'] as $item) {
                    $action = key($item);
                    if (isset($item[$action]['error'])) {
                        $errors[] = [
                            'id'    => $item[$action]['_id']   ?? null,
                            'error' => $item[$action]['error'] ?? 'unknown',
                        ];
                        log_message('error', 'OpenSearch citation bulk error on doc ' .
                            ($item[$action]['_id'] ?? '?') . ': ' .
                            json_encode($item[$action]['error'] ?? ''));
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_citation_indexer::bulk_index: ' . $e->getMessage());
            $errors[] = ['id' => null, 'error' => $e->getMessage()];
            return false;
        }

        return empty($errors);
    }
}
