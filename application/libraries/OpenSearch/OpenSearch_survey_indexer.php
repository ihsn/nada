<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Survey Indexer
 *
 * Builds and bulk-indexes survey documents into the nada_surveys index.
 *
 * Index name: opensearch_index_surveys (default: nada_surveys)
 *
 *
 * index_survey(int $survey_id): bool
 * index_surveys_batch(int $offset, int $batch_size = 50): array
 * delete_survey(int $survey_id): bool
 * count(): int
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

class OpenSearch_survey_indexer
{
    private $ci;
    private $index;

    public function __construct()
    {
        $this->ci    =& get_instance();
        $this->index = OpenSearch_client::index('surveys');

        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Facet_model');
    }

    /**
     * Index (or re-index) a single survey by ID.
     */
    public function index_survey(int $survey_id): bool
    {
        $rows = $this->load_survey_rows(null, null, $survey_id);
        if (empty($rows)) {
            log_message('error', "OpenSearch_survey_indexer::index_survey — survey {$survey_id} not found");
            return false;
        }

        $ids          = [$survey_id];
        $related      = $this->load_related_data($ids);
        $documents    = [];
        $documents[]  = $this->build_document($rows[0], $related);

        return $this->bulk_index($documents);
    }

    /**
     * Batch-index surveys using LIMIT/OFFSET.
     *
     * Returns:
     *   ['indexed' => N, 'errors' => [...], 'has_more' => bool]
     */
    public function index_surveys_batch(int $offset = 0, int $batch_size = 50): array
    {
        $rows = $this->load_survey_rows($batch_size, $offset);

        if (empty($rows)) {
            return ['indexed' => 0, 'errors' => [], 'has_more' => false];
        }

        $ids     = array_column($rows, 'id');
        $related = $this->load_related_data($ids);

        $documents = [];
        foreach ($rows as $row) {
            $documents[] = $this->build_document($row, $related);
        }

        $errors = [];
        $this->bulk_index($documents, $errors);

        return [
            'indexed'  => count($documents),
            'errors'   => $errors,
            'has_more' => count($rows) === $batch_size,
        ];
    }

    /**
     * Delete a survey document from the index.
     */
    public function delete_survey(int $survey_id): bool
    {
        try {
            OpenSearch_client::get()->delete([
                'index'   => $this->index,
                'id'      => $survey_id,
                'refresh' => true,
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_survey_indexer::delete_survey({$survey_id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count documents in the surveys index.
     */
    public function count(): int
    {
        try {
            $response = OpenSearch_client::get()->count(['index' => $this->index]);
            return (int)($response['count'] ?? 0);
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_survey_indexer::count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Perform an atomic (partial) update on a survey document.
     * Only the provided fields are updated; the rest remain unchanged.
     */
    public function atomic_update(int $survey_id, array $fields): bool
    {
        try {
            OpenSearch_client::get()->update([
                'index' => $this->index,
                'id'    => $survey_id,
                'body'  => ['doc' => $fields],
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_survey_indexer::atomic_update({$survey_id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build one OpenSearch document from a survey DB row plus pre-loaded related data.
     */
    private function build_document(array $row, array $related): array
    {
        $id = (int)$row['id'];

        // Decode metadata for fields not stored in dedicated columns
        $metadata    = $this->ci->Dataset_model->decode_metadata($row['metadata'] ?? null);
        $methodology = $this->extract_nested($metadata, 'study_desc.method.method_notes');

        // abstract is now a dedicated column on surveys — no metadata decode needed
        $abstract    = isset($row['abstract']) && $row['abstract'] !== ''
                       ? $row['abstract']
                       : null;

        return [
            // --- Identification ---
            'id'              => $id,
            'idno'            => $row['idno']            ?? null,
            'doi'             => $row['doi']             ?? null,

            // --- Full-text search fields ---
            'title'           => $row['title']           ?? null,
            'subtitle'        => $row['subtitle']        ?? null,
            'nation'          => $row['nation']          ?? null,
            'authoring_entity'=> $row['authoring_entity']?? null,
            'abstract'        => $abstract,
            'keywords'        => $row['keywords']        ?? null,
            'methodology'     => $methodology,

            // --- Temporal ---
            'year_start'      => isset($row['year_start']) ? (int)$row['year_start'] : null,
            'year_end'        => isset($row['year_end'])   ? (int)$row['year_end']   : null,
            'years'           => $related['years'][$id]    ?? [],

            // --- Classification ---
            'dataset_type'    => $row['dataset_type']    ?? null,
            'form_id'         => isset($row['formid'])   ? (int)$row['formid']      : null,
            'form_model'      => $row['form_model']      ?? null,

            // --- Repository / collections ---
            'repository_id'   => $row['repositoryid']   ?? null,
            'repo_title'      => $row['repo_title']      ?? null,
            'repository_ids'  => $related['repository_ids'][$id] ?? [],
            'collection_ids'  => $related['collection_ids'][$id] ?? [],

            // --- Facets / filters ---
            'country_ids'     => $related['country_ids'][$id]  ?? [],
            'region_ids'      => $related['region_ids'][$id]   ?? [],
            'topic_ids'       => $related['topic_ids'][$id]    ?? [],

            // --- Status ---
            'published'       => isset($row['published']) ? (int)$row['published']         : 0,
            'created'         => isset($row['created'])   ? (int)$row['created']           : null,
            'changed'         => isset($row['changed'])   ? (int)$row['changed']           : null,

            // --- Statistics ---
            'var_count'       => isset($row['varcount'])        ? (int)$row['varcount']        : 0,
            'total_views'     => isset($row['total_views'])     ? (int)$row['total_views']     : 0,
            'total_downloads' => isset($row['total_downloads']) ? (int)$row['total_downloads'] : 0,

            // --- Display (stored, not indexed for search) ---
            'thumbnail'       => $row['thumbnail']  ?? null,
            'link_da'         => $row['link_da']    ?? null,
            'data_class_id'   => isset($row['data_class_id']) ? (int)$row['data_class_id'] : null,
        ];
    }


    /**
     * Load survey rows from the database.
     *
     * Pass $survey_id to load a single survey; otherwise use $limit/$offset for batches.
     */
    private function load_survey_rows(?int $limit, ?int $offset, ?int $survey_id = null): array
    {
        $this->ci->db->select(
            'surveys.id,
             surveys.idno,
             surveys.doi,
             surveys.title,
             surveys.subtitle,
             surveys.nation,
             surveys.authoring_entity,
             surveys.keywords,
             surveys.abstract,
             surveys.metadata,
             surveys.year_start,
             surveys.year_end,
             surveys.type         AS dataset_type,
             surveys.formid,
             surveys.repositoryid,
             surveys.published,
             surveys.created,
             surveys.changed,
             surveys.varcount,
             surveys.total_views,
             surveys.total_downloads,
             surveys.thumbnail,
             surveys.link_da,
             surveys.data_class_id,
             forms.model          AS form_model,
             repositories.title   AS repo_title',
            false
        );
        $this->ci->db->join('forms',        'surveys.formid = forms.formid',                    'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');

        if ($survey_id !== null) {
            $this->ci->db->where('surveys.id', $survey_id);
        } else {
            $this->ci->db->order_by('surveys.id', 'ASC');
            $this->ci->db->limit($limit, $offset);
        }

        return $this->ci->db->get('surveys')->result_array();
    }

    /**
     * Load all related data for a set of survey IDs in 6 queries.
     * Returns a map keyed by survey ID for each relation type.
     */
    private function load_related_data(array $ids): array
    {
        $related = [
            'country_ids'    => [],
            'region_ids'     => [],
            'repository_ids' => [],
            'collection_ids' => [],
            'topic_ids'      => [],
            'years'          => [],
        ];

        if (empty($ids)) {
            return $related;
        }

        // 1. Countries
        $rows = $this->ci->db
            ->select('sid, cid')
            ->where_in('sid', $ids)
            ->where('cid >', 0)
            ->get('survey_countries')
            ->result_array();
        foreach ($rows as $r) {
            $related['country_ids'][(int)$r['sid']][] = (int)$r['cid'];
        }

        // 2. Regions (derived from countries via region_countries)
        $all_cids = array_unique(array_merge(...array_values($related['country_ids']) ?: [[]]));
        if (!empty($all_cids)) {
            $region_rows = $this->ci->db
                ->select('country_id, region_id')
                ->where_in('country_id', $all_cids)
                ->get('region_countries')
                ->result_array();

            // Build country → region map (one country can belong to multiple regions)
            $country_regions = [];
            foreach ($region_rows as $r) {
                $country_regions[(int)$r['country_id']][] = (int)$r['region_id'];
            }

            // Assign region IDs per survey
            foreach ($related['country_ids'] as $sid => $cids) {
                $region_ids = [];
                foreach ($cids as $cid) {
                    if (isset($country_regions[$cid])) {
                        $region_ids = array_merge($region_ids, $country_regions[$cid]);
                    }
                }
                $related['region_ids'][$sid] = array_values(array_unique($region_ids));
            }
        }

        // 3. Repository memberships
        $rows = $this->ci->db
            ->select('sid, repositoryid')
            ->where_in('sid', $ids)
            ->get('survey_repos')
            ->result_array();
        foreach ($rows as $r) {
            $related['repository_ids'][(int)$r['sid']][] = $r['repositoryid'];
        }

        // 4. Collection memberships
        $rows = $this->ci->db
            ->select('sid, cid')
            ->where_in('sid', $ids)
            ->get('da_collection_surveys')
            ->result_array();
        foreach ($rows as $r) {
            $related['collection_ids'][(int)$r['sid']][] = (int)$r['cid'];
        }

        // 5. Topic / facet IDs  (returns [sid => [facet_name => [term_id, ...]]])
        $facets_by_study = $this->ci->Facet_model->facet_terms_by_studies($ids);
        foreach ($facets_by_study as $sid => $facets) {
            $all_term_ids = [];
            foreach ($facets as $term_ids) {
                $all_term_ids = array_merge($all_term_ids, $term_ids);
            }
            $related['topic_ids'][(int)$sid] = array_values(array_unique($all_term_ids));
        }

        // 6. Collection years
        $rows = $this->ci->db
            ->select('sid, data_coll_year')
            ->where_in('sid', $ids)
            ->where('data_coll_year >', 0)
            ->get('survey_years')
            ->result_array();
        foreach ($rows as $r) {
            $related['years'][(int)$r['sid']][] = (int)$r['data_coll_year'];
        }

        return $related;
    }


    /**
     * Send documents to OpenSearch via the Bulk API.
     * Document _id is the survey's integer database ID.
     *
     * @param array $documents  Array of document arrays (from build_document())
     * @param array &$errors    Populated with any per-document errors
     * @return bool             True if no errors
     */
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
                            'id'    => $item[$action]['_id']    ?? null,
                            'error' => $item[$action]['error']  ?? 'unknown',
                        ];
                        log_message('error', 'OpenSearch bulk index error on doc ' .
                            ($item[$action]['_id'] ?? '?') . ': ' .
                            json_encode($item[$action]['error'] ?? ''));
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', 'OpenSearch_survey_indexer::bulk_index: ' . $e->getMessage());
            $errors[] = ['id' => null, 'error' => $e->getMessage()];
            return false;
        }

        return empty($errors);
    }

    /**
     * Extract a value from a nested array using dot-notation path.
     * Returns null if the path does not exist.
     */
    private function extract_nested($data, string $path)
    {
        if (empty($data)) {
            return null;
        }
        $keys = explode('.', $path);
        $node = (array)$data;
        foreach ($keys as $key) {
            if (!isset($node[$key])) {
                return null;
            }
            $node = $node[$key];
        }
        return is_string($node) ? trim($node) : $node;
    }
}
