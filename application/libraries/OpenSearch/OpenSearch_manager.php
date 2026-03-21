<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch Manager
 *
 * Handles real-time delta updates triggered by dataset operations
 * (import, publish, delete, etc.).
 *
 * Bulk / batch indexing is handled by the dedicated indexer classes:
 *   - OpenSearch_survey_indexer  (surveys)
 *   - OpenSearch_variable_indexer (Phase 2)
 *
 * Called from Events.php / dataset controllers via:
 *   process_delta_update($table, $operation, $object_id)
 */

require_once dirname(__FILE__) . '/OpenSearch_client.php';
require_once dirname(__FILE__) . '/OpenSearch_survey_indexer.php';
require_once dirname(__FILE__) . '/OpenSearch_variable_indexer.php';

class OpenSearch_manager
{
    private $ci;
    private $survey_indexer;
    private $variable_indexer;

    public function __construct(array $params = [])
    {
        $this->ci               =& get_instance();
        $this->survey_indexer   = new OpenSearch_survey_indexer();
        $this->variable_indexer = new OpenSearch_variable_indexer();
    }

    // =========================================================================
    // Delta updates — called by the event system when data changes
    // =========================================================================

    /**
     * Process a delta update originating from a dataset operation.
     *
     * @param string $table     'surveys' | 'citations'
     * @param string $operation 'import'|'replace'|'update'|'create'|'refresh'|
     *                           'facet'|'publish'|'atomic'|'delete'
     * @param int    $obj_id    Survey / citation ID
     */
    public function process_delta_update(string $table, string $operation, int $obj_id): void
    {
        switch ($table) {
            case 'surveys':
                $this->handle_survey_delta($operation, $obj_id);
                break;

            case 'citations':
                // Phase 3 — not yet implemented
                break;
        }
    }

    // =========================================================================
    // Utility — used by CLI / API status checks
    // =========================================================================

    /**
     * Ping the OpenSearch cluster.
     */
    public function ping_test(): array
    {
        try {
            $info = OpenSearch_client::get()->info();
            return [
                'status'       => 'OK',
                'cluster_name' => $info['cluster_name']      ?? 'unknown',
                'version'      => $info['version']['number'] ?? 'unknown',
            ];
        } catch (Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }

    /**
     * Count documents in a given index type.
     *
     * @param string $type  'surveys' | 'variables' | 'citations'
     */
    public function count_documents(string $type): int
    {
        try {
            $response = OpenSearch_client::get()->count([
                'index' => OpenSearch_client::index($type),
            ]);
            return (int)($response['count'] ?? 0);
        } catch (Exception $e) {
            log_message('error', "OpenSearch_manager::count_documents({$type}): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Legacy numeric doctype count — kept for backward compatibility with
     * existing CLI/API controllers until they are updated.
     *
     * @param int $doctype  1=surveys, 2=variables, 3=citations
     */
    public function count_documents_by_type(int $doctype, ?int $published = null): int
    {
        $map = [1 => 'surveys', 2 => 'variables', 3 => 'citations'];
        $type = $map[$doctype] ?? null;
        if ($type === null) {
            return 0;
        }
        return $this->count_documents($type);
    }

    /**
     * Refresh (flush) an index to make recent writes immediately visible.
     */
    public function commit_index_changes(string $type = 'surveys'): int
    {
        try {
            OpenSearch_client::get()->indices()->refresh([
                'index' => OpenSearch_client::index($type),
            ]);
            return 0;
        } catch (Exception $e) {
            log_message('error', "OpenSearch_manager::commit_index_changes: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Delete all documents from an index (delete-by-query match_all).
     */
    public function clear_index(string $type = 'surveys'): array
    {
        try {
            $response = OpenSearch_client::get()->deleteByQuery([
                'index'   => OpenSearch_client::index($type),
                'refresh' => true,
                'body'    => ['query' => ['match_all' => (object)[]]],
            ]);
            return ['status' => 0, 'deleted' => $response['deleted'] ?? 0];
        } catch (Exception $e) {
            log_message('error', "OpenSearch_manager::clear_index: " . $e->getMessage());
            return ['status' => 1, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Batch import helpers — thin wrappers used by CLI / API controllers
    // =========================================================================

    /**
     * Batch-index surveys.  Returns the same array shape the CLI controller expects.
     */
    public function import_surveys_batch(int $offset = 0, int $batch_size = 50): array
    {
        $result = $this->survey_indexer->index_surveys_batch($offset, $batch_size);
        return [
            'rows_processed' => $result['indexed'],
            'errors'         => $result['errors'],
            'has_more'       => $result['has_more'],
            'last_row_id'    => $offset + $result['indexed'],
        ];
    }

    /**
     * Index a single survey and its variables.
     */
    public function import_single_survey(int $survey_id): bool
    {
        return $this->survey_indexer->index_survey($survey_id);
    }

    /**
     * Batch-index variables.
     */
    public function import_variables_batch(int $offset = 0, int $batch_size = 200): array
    {
        $result = $this->variable_indexer->index_variables_batch($offset, $batch_size);
        return [
            'rows_processed' => $result['indexed'],
            'errors'         => $result['errors'],
            'has_more'       => $result['has_more'],
            'last_row_id'    => $offset + $result['indexed'],
        ];
    }

    /**
     * Index (or re-index) a single variable document.
     */
    public function index_variable(int $uid): bool
    {
        return $this->variable_indexer->index_variable($uid);
    }

    /**
     * Delete a single variable document from the index.
     */
    public function delete_variable(int $uid): bool
    {
        return $this->variable_indexer->delete_variable($uid);
    }

    /**
     * Delete all variable documents for a survey.
     * Called when a study is deleted or unpublished.
     */
    public function delete_survey_variables(int $survey_id): bool
    {
        return $this->variable_indexer->delete_survey_variables($survey_id);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function handle_survey_delta(string $operation, int $survey_id): void
    {
        $full_reindex_ops = ['import', 'replace', 'update', 'create', 'refresh', 'facet'];
        $atomic_ops       = ['publish', 'atomic'];

        if (in_array($operation, $full_reindex_ops, true)) {
            $this->survey_indexer->index_survey($survey_id);
            $this->variable_indexer->index_survey_variables($survey_id);
            return;
        }

        if (in_array($operation, $atomic_ops, true)) {
            $this->atomic_survey_update($survey_id);
            return;
        }

        if ($operation === 'delete') {
            $this->survey_indexer->delete_survey($survey_id);
            $this->variable_indexer->delete_survey_variables($survey_id);
            return;
        }

        log_message('warning', "OpenSearch_manager: unhandled survey operation '{$operation}' for ID {$survey_id}");
    }

    /**
     * Partial update — refreshes only the fields that change on publish/unpublish.
     */
    private function atomic_survey_update(int $survey_id): void
    {
        $row = $this->ci->db
            ->select('published, changed, total_views, total_downloads, varcount')
            ->where('id', $survey_id)
            ->get('surveys')
            ->row_array();

        if (empty($row)) {
            return;
        }

        $published = (int)$row['published'];

        $this->survey_indexer->atomic_update($survey_id, [
            'published'       => $published,
            'changed'         => (int)$row['changed'],
            'total_views'     => (int)$row['total_views'],
            'total_downloads' => (int)$row['total_downloads'],
            'var_count'       => (int)$row['varcount'],
        ]);

        $this->variable_indexer->update_survey_published($survey_id, $published);
    }
}
