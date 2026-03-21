<?php

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * OpenSearch REST API Controller
 *
 * Admin-only endpoints for index management and batch imports.
 * Requires session authentication or API key.
 *
 * Endpoints
 * ---------
 * GET /api/opensearch/ping
 * GET /api/opensearch/status
 * GET /api/opensearch/import_surveys_batch[?offset=0&batch_size=50]
 * GET /api/opensearch/import_single_survey/<id>
 * GET /api/opensearch/import_variables_batch[?offset=0&batch_size=200]
 * GET /api/opensearch/clear_index[?type=surveys]
 * GET /api/opensearch/commit[?type=surveys]
 * GET /api/opensearch/schema_create[?type=surveys&replace=false]
 * GET /api/opensearch/schema_delete[?type=surveys]
 * GET /api/opensearch/schema_validate
 * GET /api/opensearch/schema_connection_test
 */
class OpenSearch extends MY_REST_Controller
{
    private $manager;

    public function __construct()
    {
        parent::__construct();
        $this->is_admin_or_die();

        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_manager.php';
        $this->manager = new OpenSearch_manager();
    }

    // Allow session-based auth in addition to API key
    function _auth_override_check()
    {
        if ($this->session->userdata('user_id')) {
            return true;
        }
        return parent::_auth_override_check();
    }

    // =========================================================================
    // Connectivity
    // =========================================================================

    public function ping_get(): void
    {
        $this->respond($this->manager->ping_test());
    }

    public function status_get(): void
    {
        try {
            require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
            $schema = new OpenSearch_schema_manager();

            $db_surveys   = (int)$this->db->query('SELECT COUNT(id)  AS t FROM surveys')->row_array()['t'];
            $db_variables = (int)$this->db->query('SELECT COUNT(uid) AS t FROM variables')->row_array()['t'];
            $db_citations = (int)$this->db->query('SELECT COUNT(id)  AS t FROM citations')->row_array()['t'];

            $this->respond([
                'database' => [
                    'surveys'   => $db_surveys,
                    'variables' => $db_variables,
                    'citations' => $db_citations,
                ],
                'index' => [
                    'surveys'   => $this->manager->count_documents('surveys'),
                    'variables' => $this->manager->count_documents('variables'),
                    'citations' => $this->manager->count_documents('citations'),
                ],
                'indices_exist' => [
                    'surveys'   => $schema->index_exists('surveys'),
                    'variables' => $schema->index_exists('variables'),
                    'citations' => $schema->index_exists('citations'),
                ],
            ]);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    // =========================================================================
    // Batch imports
    // =========================================================================

    public function import_surveys_batch_get(): void
    {
        $offset     = max(0, (int)$this->get('offset'));
        $batch_size = max(1, (int)($this->get('batch_size') ?: 50));

        try {
            $result = $this->manager->import_surveys_batch($offset, $batch_size);
            $this->respond($result);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    public function import_single_survey_get(int $survey_id): void
    {
        try {
            $ok = $this->manager->import_single_survey($survey_id);
            $this->respond(['survey_id' => $survey_id, 'success' => $ok]);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    public function import_variables_batch_get(): void
    {
        $offset     = max(0, (int)$this->get('offset'));
        $batch_size = max(1, (int)($this->get('batch_size') ?: 200));

        try {
            $result = $this->manager->import_variables_batch($offset, $batch_size);
            $this->respond($result);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    // =========================================================================
    // Index management
    // =========================================================================

    public function clear_index_get(): void
    {
        $type = $this->get('type') ?: 'surveys';
        try {
            $result = $this->manager->clear_index($type);
            $this->respond($result);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    public function commit_get(): void
    {
        $type   = $this->get('type') ?: 'surveys';
        $status = $this->manager->commit_index_changes($type);
        $this->respond(['status' => $status === 0 ? 'ok' : 'error']);
    }

    // =========================================================================
    // Schema management
    // =========================================================================

    public function schema_create_get(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema  = new OpenSearch_schema_manager();
        $type    = $this->get('type')    ?: 'surveys';
        $replace = $this->get('replace') === 'true' || $this->get('replace') === '1';

        try {
            $this->respond($schema->create_index($type, $replace));
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    public function schema_delete_get(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();
        $type   = $this->get('type') ?: 'surveys';

        try {
            $this->respond($schema->delete_index($type));
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    public function schema_validate_get(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();
        $result = [];
        foreach (['surveys', 'variables', 'citations'] as $type) {
            $result[$type] = $schema->index_exists($type);
        }
        $this->respond($result);
    }

    public function schema_connection_test_get(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();
        try {
            $this->respond($schema->test_connection());
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function respond(array $data): void
    {
        $this->set_response(['status' => 'success', 'result' => $data], REST_Controller::HTTP_OK);
    }

    private function respond_error(string $message): void
    {
        $this->set_response(
            ['status' => 'failed', 'message' => $message],
            REST_Controller::HTTP_BAD_REQUEST
        );
    }
}
