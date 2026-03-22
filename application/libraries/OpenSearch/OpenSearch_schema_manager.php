<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Schema Manager
 *
 * Creates and manages the nada_surveys (and future nada_variables,
 * nada_citations) indices with explicit field mappings.
 *
 * Each index has its own mapping defined inline here — no config arrays.
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';

class OpenSearch_schema_manager
{
    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Create an index.
     *
     * @param string $type            'surveys' | 'variables' | 'citations'
     * @param bool   $replace         Delete and recreate if the index exists
     * @return array  ['status' => 'success'|'exists'|'error', ...]
     */
    public function create_index(string $type, bool $replace = false): array
    {
        $index_name = OpenSearch_client::index($type);
        $client     = OpenSearch_client::get();

        if ($this->index_exists($type)) {
            if (!$replace) {
                return ['status' => 'exists', 'index' => $index_name,
                        'message' => 'Index already exists. Use replace=true to recreate.'];
            }
            $this->delete_index($type);
        }

        $mapping = $this->get_mapping_for($type);

        try {
            $client->indices()->create([
                'index' => $index_name,
                'body'  => ['mappings' => ['properties' => $mapping]],
            ]);
            return ['status' => 'success', 'index' => $index_name];
        } catch (Exception $e) {
            log_message('error', "OpenSearch_schema_manager::create_index({$type}): " . $e->getMessage());
            return ['status' => 'error', 'index' => $index_name, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete an index.
     */
    public function delete_index(string $type): array
    {
        $index_name = OpenSearch_client::index($type);
        $client     = OpenSearch_client::get();

        if (!$this->index_exists($type)) {
            return ['status' => 'not_found', 'index' => $index_name];
        }

        try {
            $client->indices()->delete(['index' => $index_name]);
            return ['status' => 'success', 'index' => $index_name];
        } catch (Exception $e) {
            log_message('error', "OpenSearch_schema_manager::delete_index({$type}): " . $e->getMessage());
            return ['status' => 'error', 'index' => $index_name, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check whether an index exists.
     */
    public function index_exists(string $type): bool
    {
        try {
            $response = OpenSearch_client::get()->indices()->exists([
                'index' => OpenSearch_client::index($type),
            ]);
            return $response === true || (is_array($response) && ($response['status'] ?? 0) === 200);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the current mapping of an index.
     */
    public function get_mapping(string $type): array
    {
        $index_name = OpenSearch_client::index($type);
        try {
            $response = OpenSearch_client::get()->indices()->getMapping(['index' => $index_name]);
            return $response[$index_name]['mappings']['properties'] ?? [];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Test the OpenSearch connection and report index existence.
     */
    public function test_connection(): array
    {
        try {
            $info = OpenSearch_client::get()->info();
            $result = [
                'connected'    => true,
                'cluster_name' => $info['cluster_name']       ?? 'unknown',
                'version'      => $info['version']['number']  ?? 'unknown',
            ];
            foreach (['surveys', 'variables', 'citations'] as $type) {
                $result['index_' . $type] = $this->index_exists($type);
            }
            return $result;
        } catch (Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Mappings — defined explicitly per index type
    // =========================================================================

    public function get_mapping_for(string $type): array
    {
        switch ($type) {
            case 'surveys':   return $this->surveys_mapping();
            case 'variables': return $this->variables_mapping();
            case 'citations': return $this->citations_mapping();
            default:
                throw new InvalidArgumentException("Unknown index type: {$type}");
        }
    }

    private function surveys_mapping(): array
    {
        return [
            // --- Identification ---
            'id'              => ['type' => 'integer'],
            'idno'            => ['type' => 'text',    'analyzer' => 'english',
                                  'fields' => ['keyword' => ['type' => 'keyword']]],
            'doi'             => ['type' => 'keyword'],

            // --- Full-text search ---
            'title'           => ['type' => 'text',    'analyzer' => 'english',
                                  'fields' => ['keyword' => ['type' => 'keyword']]],
            'subtitle'        => ['type' => 'keyword', 'index' => false],
            'nation'          => ['type' => 'text',    'analyzer' => 'english',
                                  'fields' => ['keyword' => ['type' => 'keyword']]],
            'authoring_entity'=> ['type' => 'text',    'analyzer' => 'english'],
            'abstract'        => ['type' => 'text',    'analyzer' => 'english'],
            'keywords'        => ['type' => 'text',    'analyzer' => 'english'],
            'methodology'     => ['type' => 'text',    'analyzer' => 'english'],

            // --- Temporal ---
            'year_start'      => ['type' => 'integer'],
            'year_end'        => ['type' => 'integer'],
            'years'           => ['type' => 'integer'],

            // --- Classification ---
            'dataset_type'    => ['type' => 'keyword'],
            'form_id'         => ['type' => 'integer'],
            'form_model'      => ['type' => 'keyword'],

            // --- Repository ---
            'repository_id'   => ['type' => 'keyword'],
            'repo_title'      => ['type' => 'keyword', 'index' => false],
            'repository_ids'  => ['type' => 'keyword'],

            // --- Facets / filters ---
            'country_ids'     => ['type' => 'integer'],
            'region_ids'      => ['type' => 'integer'],
            'collection_ids'  => ['type' => 'integer'],
            'topic_ids'       => ['type' => 'integer'],

            // --- Status ---
            'published'       => ['type' => 'integer'],
            'created'         => ['type' => 'integer'],
            'changed'         => ['type' => 'integer'],

            // --- Statistics ---
            'var_count'       => ['type' => 'integer'],
            'total_views'     => ['type' => 'integer'],
            'total_downloads' => ['type' => 'integer'],

            // --- Display only (stored, not searchable) ---
            'thumbnail'       => ['type' => 'keyword', 'index' => false],
            'link_da'         => ['type' => 'keyword', 'index' => false],
            'data_class_id'   => ['type' => 'integer', 'index' => false],
        ];
    }

    private function variables_mapping(): array
    {
        return [
            'id'               => ['type' => 'integer'],
            'vid'              => ['type' => 'keyword'],
            'fid'              => ['type' => 'keyword'],
            'survey_id'        => ['type' => 'integer'],
            'survey_published' => ['type' => 'integer'],
            'name'          => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
            'label'         => ['type' => 'text', 'analyzer' => 'english',
                                'fields' => ['keyword' => ['type' => 'keyword']]],
            'question'      => ['type' => 'text', 'analyzer' => 'english'],
            'categories'    => ['type' => 'text', 'analyzer' => 'english'],
            'survey_idno'   => ['type' => 'keyword'],
            'survey_title'  => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
            'survey_nation' => ['type' => 'keyword'],
            'year_start'    => ['type' => 'integer'],
            'year_end'      => ['type' => 'integer'],
            'dataset_type'  => ['type' => 'keyword'],
            'country_ids'   => ['type' => 'integer'],
        ];
    }

    private function citations_mapping(): array
    {
        return [
            // --- Identification ---
            'id'           => ['type' => 'integer'],
            'uuid'         => ['type' => 'keyword'],

            // --- Full-text search ---
            'title'        => ['type' => 'text', 'analyzer' => 'english',
                               'fields' => ['keyword' => ['type' => 'keyword']]],
            'subtitle'     => ['type' => 'keyword', 'index' => false],
            'alt_title'    => ['type' => 'text',    'analyzer' => 'english'],
            'authors_text' => ['type' => 'text',    'analyzer' => 'english',
                               'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
            'abstract'     => ['type' => 'text',    'analyzer' => 'english'],
            'keywords'     => ['type' => 'text',    'analyzer' => 'english'],
            'notes'        => ['type' => 'text',    'analyzer' => 'english'],
            'organization' => ['type' => 'text',    'analyzer' => 'english'],
            'doi'          => ['type' => 'keyword'],

            // --- Authors (display, structured) ---
            'authors' => [
                'type'       => 'object',
                'properties' => [
                    'fname'       => ['type' => 'keyword', 'index' => false],
                    'lname'       => ['type' => 'keyword', 'index' => false],
                    'initial'     => ['type' => 'keyword', 'index' => false],
                    'author_type' => ['type' => 'keyword', 'index' => false],
                ],
            ],

            // --- Display fields ---
            'editors'            => ['type' => 'keyword', 'index' => false],
            'translators'        => ['type' => 'keyword', 'index' => false],
            'url'                => ['type' => 'keyword', 'index' => false],
            'volume'             => ['type' => 'keyword', 'index' => false],
            'issue'              => ['type' => 'keyword', 'index' => false],
            'edition'            => ['type' => 'keyword', 'index' => false],
            'place_publication'  => ['type' => 'keyword', 'index' => false],
            'place_state'        => ['type' => 'keyword', 'index' => false],
            'publisher'          => ['type' => 'keyword', 'index' => false],
            'page_from'          => ['type' => 'keyword', 'index' => false],
            'page_to'            => ['type' => 'keyword', 'index' => false],
            'pub_month'          => ['type' => 'keyword', 'index' => false],
            'pub_day'            => ['type' => 'keyword', 'index' => false],
            'owner'              => ['type' => 'keyword', 'index' => false],

            // --- Filterable ---
            'ctype'              => ['type' => 'keyword'],
            'pub_year'           => ['type' => 'integer'],
            'publication_medium' => ['type' => 'integer'],
            'flag'               => ['type' => 'keyword'],
            'url_status'         => ['type' => 'keyword'],
            'published'          => ['type' => 'integer'],
            'created'            => ['type' => 'integer'],
            'changed'            => ['type' => 'integer'],
            'created_by'         => ['type' => 'integer'],
            'changed_by'         => ['type' => 'integer'],

            // --- Survey / repository linkage ---
            'survey_ids'     => ['type' => 'integer'],
            'survey_count'   => ['type' => 'integer'],
            'repository_ids' => ['type' => 'keyword'],
        ];
    }
}
