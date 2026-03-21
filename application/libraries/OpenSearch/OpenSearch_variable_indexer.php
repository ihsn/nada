<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Variable Indexer
 *
 * Builds and bulk-indexes variable documents into the nada_variables index.
 *
 * One document per variable row, denormalized with survey fields so search
 * results require no database round-trip.
 *
 * Public API
 * ----------
 * index_variable(int $uid): bool
 * index_variables_batch(int $offset, int $batch_size = 200): array
 * index_survey_variables(int $survey_id): bool
 * delete_variable(int $uid): bool
 * delete_survey_variables(int $survey_id): bool
 * count(): int
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

class OpenSearch_variable_indexer
{
    private $ci;
    private $index;

    public function __construct()
    {
        $this->ci    =& get_instance();
        $this->index = OpenSearch_client::index('variables');
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Index (or re-index) a single variable by uid.
     */
    public function index_variable(int $uid): bool
    {
        $rows = $this->load_rows(null, null, $uid);
        if (empty($rows)) {
            log_message('error', "OpenSearch_variable_indexer::index_variable — uid {$uid} not found");
            return false;
        }
        $country_map = $this->load_country_ids([(int)$rows[0]['sid']]);
        $rows        = $this->inject_country_ids($rows, $country_map);
        return $this->bulk_index([$this->build_document($rows[0])]);
    }

    /**
     * Batch-index variables using LIMIT/OFFSET over all published surveys.
     *
     * Returns:
     *   ['indexed' => N, 'errors' => [...], 'has_more' => bool]
     */
    public function index_variables_batch(int $offset = 0, int $batch_size = 200): array
    {
        $rows = $this->load_rows($batch_size, $offset);

        if (empty($rows)) {
            return ['indexed' => 0, 'errors' => [], 'has_more' => false];
        }

        $survey_ids  = array_unique(array_column($rows, 'sid'));
        $country_map = $this->load_country_ids($survey_ids);
        $rows        = $this->inject_country_ids($rows, $country_map);

        $documents = array_map([$this, 'build_document'], $rows);

        $errors = [];
        $this->bulk_index($documents, $errors);

        return [
            'indexed'  => count($documents),
            'errors'   => $errors,
            'has_more' => count($rows) === $batch_size,
        ];
    }

    /**
     * Delete all variable documents for a survey then re-index them.
     * Called on survey import/update/delete delta events.
     */
    public function index_survey_variables(int $survey_id): bool
    {
        $this->delete_survey_variables($survey_id);

        $offset     = 0;
        $batch_size = 200;

        $country_map = $this->load_country_ids([$survey_id]);

        do {
            $rows = $this->load_rows($batch_size, $offset, null, $survey_id);
            if (empty($rows)) {
                break;
            }

            $rows      = $this->inject_country_ids($rows, $country_map);
            $documents = array_map([$this, 'build_document'], $rows);
            $errors    = [];
            $this->bulk_index($documents, $errors);

            $offset  += count($rows);
            $has_more = count($rows) === $batch_size;
        } while ($has_more);

        return true;
    }

    /**
     * Delete a single variable document.
     */
    public function delete_variable(int $uid): bool
    {
        try {
            OpenSearch_client::get()->delete([
                'index'   => $this->index,
                'id'      => $uid,
                'refresh' => true,
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_variable_indexer::delete_variable({$uid}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all variable documents belonging to a survey.
     */
    public function delete_survey_variables(int $survey_id): bool
    {
        try {
            OpenSearch_client::get()->deleteByQuery([
                'index'   => $this->index,
                'refresh' => true,
                'body'    => ['query' => ['term' => ['survey_id' => $survey_id]]],
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_variable_indexer::delete_survey_variables({$survey_id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count documents in the variables index.
     */
    public function count(): int
    {
        try {
            $response = OpenSearch_client::get()->count(['index' => $this->index]);
            return (int)($response['count'] ?? 0);
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_variable_indexer::count: ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    // Document building
    // =========================================================================

    private function build_document(array $row): array
    {
        return [
            'id'               => (int)$row['uid'],
            'survey_id'        => (int)$row['sid'],
            'survey_published' => isset($row['published']) ? (int)$row['published'] : 0,
            'survey_idno'      => $row['idno']        ?? null,
            'survey_title'     => $row['title']       ?? null,
            'survey_nation'    => $row['nation']      ?? null,
            'year_start'       => isset($row['year_start']) ? (int)$row['year_start'] : null,
            'year_end'         => isset($row['year_end'])   ? (int)$row['year_end']   : null,
            'dataset_type'     => $row['type']        ?? null,
            'fid'              => $row['fid']         ?? null,
            'vid'              => $row['vid']         ?? null,
            'name'             => $row['name']        ?? null,
            'label'            => $row['labl']        ?? null,
            'question'         => $row['qstn']        ?? null,
            'categories'       => isset($row['catgry']) ? mb_strimwidth($row['catgry'], 0, 32000) : null,
            'country_ids'      => $row['country_ids'] ?? [],
        ];
    }

    /**
     * Partial update — sets survey_published on all variable documents for a survey.
     * Called by OpenSearch_manager::atomic_survey_update() on publish/unpublish.
     */
    public function update_survey_published(int $survey_id, int $published): bool
    {
        try {
            OpenSearch_client::get()->updateByQuery([
                'index'   => $this->index,
                'refresh' => true,
                'body'    => [
                    'query'  => ['term' => ['survey_id' => $survey_id]],
                    'script' => [
                        'source' => 'ctx._source.survey_published = params.published',
                        'lang'   => 'painless',
                        'params' => ['published' => $published],
                    ],
                ],
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_variable_indexer::update_survey_published({$survey_id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load survey_countries rows for the given survey IDs.
     * Returns [survey_id => [cid, cid, ...], ...]
     */
    private function load_country_ids(array $survey_ids): array
    {
        if (empty($survey_ids)) {
            return [];
        }

        $rows = $this->ci->db
            ->select('sid, cid')
            ->where_in('sid', $survey_ids)
            ->where('cid >', 0)
            ->get('survey_countries')
            ->result_array();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['sid']][] = (int)$r['cid'];
        }
        return $map;
    }

    /**
     * Inject country_ids from the map into each variable row.
     */
    private function inject_country_ids(array $rows, array $country_map): array
    {
        foreach ($rows as &$row) {
            $row['country_ids'] = $country_map[(int)$row['sid']] ?? [];
        }
        return $rows;
    }

    // =========================================================================
    // Database loading
    // =========================================================================

    /**
     * Load variable rows joined with survey fields.
     *
     * Pass $uid to load a single variable; $survey_id to load all for a survey;
     * otherwise use $limit/$offset for batch iteration.
     */
    private function load_rows(
        ?int $limit,
        ?int $offset,
        ?int $uid       = null,
        ?int $survey_id = null
    ): array {
        $this->ci->db->select(
            'v.uid, v.sid, v.fid, v.vid, v.name, v.labl, v.qstn, v.catgry,
             s.idno, s.title, s.nation, s.year_start, s.year_end, s.type, s.published'
        );
        $this->ci->db->from('variables v');
        $this->ci->db->join('surveys s', 's.id = v.sid', 'inner');

        if ($uid !== null) {
            $this->ci->db->where('v.uid', $uid);
        } elseif ($survey_id !== null) {
            $this->ci->db->where('v.sid', $survey_id);
            $this->ci->db->order_by('v.uid', 'asc');
        } else {
            $this->ci->db->order_by('v.uid', 'asc');
            $this->ci->db->limit($limit, $offset);
        }

        return $this->ci->db->get()->result_array();
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
                        log_message('error', 'OpenSearch variable bulk error on doc ' .
                            ($item[$action]['_id'] ?? '?') . ': ' .
                            json_encode($item[$action]['error'] ?? ''));
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_variable_indexer::bulk_index: ' . $e->getMessage());
            $errors[] = ['id' => null, 'error' => $e->getMessage()];
            return false;
        }

        return empty($errors);
    }
}
