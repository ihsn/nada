<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenSearch CLI Controller
 *
 * Usage:  php cli.php cli/opensearch/<command> [args]
 *
 * Indexing
 * --------
 *   index_studies [offset]                  Index all surveys (batched)
 *   index_single_survey <survey_id>         Index one survey
 *   index_variables [offset]                Index all variables (batched)
 *   index_citations [offset]                Index all citations (batched)
 *
 * Index management
 * ----------------
 *   clean_index [type]                      Delete all documents (default: surveys)
 *   refresh_index [type]                    Force index refresh
 *   status                                  Compare DB counts vs index counts (JSON)
 *   ping                                    Test connectivity
 *
 * Schema management
 * -----------------
 *   create_index <type> [replace]           Create index (type: surveys|variables|citations)
 *   delete_index <type>                     Delete index
 *   setup_schema [replace]                  Create all indices
 *   validate_schema                         Check indices exist
 *   test_connection                         Full connection + index check
 */
class OpenSearch extends CI_Controller
{
    private $manager;

    public function __construct()
    {
        parent::__construct();

        if (!$this->input->is_cli_request()) {
            die("This controller can only be run from the command line.\n");
        }

        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_manager.php';
        $this->manager = new OpenSearch_manager();
    }

    // =========================================================================
    // Indexing
    // =========================================================================

    public function index_studies($start_offset = 0): void
    {
        $start_offset = (int)$start_offset;
        echo "Starting survey indexing (offset: {$start_offset})...\n\n";

        set_time_limit(0);

        $batch_size     = 50;
        $offset         = $start_offset;
        $total          = 0;
        $batch_num      = 0;
        $start_time     = microtime(true);

        while (true) {
            echo "Batch " . (++$batch_num) . " (offset: {$offset})... ";
            $result = $this->manager->import_surveys_batch($offset, $batch_size);

            if ($result['rows_processed'] === 0) {
                echo "done.\n";
                break;
            }

            $total  += $result['rows_processed'];
            $offset += $result['rows_processed'];
            echo "indexed {$result['rows_processed']} surveys. Total: {$total}\n";

            if (!empty($result['errors'])) {
                echo "  ERRORS in this batch: " . count($result['errors']) . "\n";
                foreach ($result['errors'] as $e) {
                    echo "    doc " . ($e['id'] ?? '?') . ": " . json_encode($e['error']) . "\n";
                }
            }

            if (!$result['has_more']) {
                break;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);
        echo "\nDone.  {$total} surveys indexed in {$elapsed}s.\n";
    }

    public function index_variables($start_offset = 0): void
    {
        $start_offset = (int)$start_offset;
        echo "Starting variable indexing (offset: {$start_offset})...\n\n";

        set_time_limit(0);

        $batch_size = 200;
        $offset     = $start_offset;
        $total      = 0;
        $batch_num  = 0;
        $start_time = microtime(true);

        while (true) {
            echo "Batch " . (++$batch_num) . " (offset: {$offset})... ";
            $result = $this->manager->import_variables_batch($offset, $batch_size);

            if ($result['rows_processed'] === 0) {
                echo "done.\n";
                break;
            }

            $total  += $result['rows_processed'];
            $offset += $result['rows_processed'];
            echo "indexed {$result['rows_processed']} variables. Total: {$total}\n";

            if (!empty($result['errors'])) {
                echo "  ERRORS in this batch: " . count($result['errors']) . "\n";
                foreach ($result['errors'] as $e) {
                    echo "    doc " . ($e['id'] ?? '?') . ": " . json_encode($e['error']) . "\n";
                }
            }

            if (!$result['has_more']) {
                break;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);
        echo "\nDone.  {$total} variables indexed in {$elapsed}s.\n";
    }

    public function index_single_survey($survey_id): void
    {
        $survey_id = (int)$survey_id;
        echo "Indexing survey {$survey_id}...\n";
        $ok = $this->manager->import_single_survey($survey_id);
        echo $ok ? "Done.\n" : "Failed — check logs.\n";
    }

    public function index_citations($start_offset = 0): void
    {
        $start_offset = (int)$start_offset;

        echo "Starting citation indexing (offset: {$start_offset})...\n\n";

        set_time_limit(0);

        $batch_size = 100;
        $offset     = $start_offset;
        $total      = 0;
        $batch_num  = 0;
        $start_time = microtime(true);

        while (true) {
            echo "Batch " . (++$batch_num) . " (offset: {$offset})... ";
            $result = $this->manager->import_citations_batch($offset, $batch_size);

            if ($result['rows_processed'] === 0) {
                echo "done.\n";
                break;
            }

            $total  += $result['rows_processed'];
            $offset += $result['rows_processed'];
            echo "indexed {$result['rows_processed']} citations. Total: {$total}\n";

            if (!empty($result['errors'])) {
                echo "  ERRORS in this batch: " . count($result['errors']) . "\n";
                foreach ($result['errors'] as $e) {
                    echo "    doc " . ($e['id'] ?? '?') . ": " . json_encode($e['error']) . "\n";
                }
            }

            if (!$result['has_more']) {
                break;
            }
        }

        $elapsed = round(microtime(true) - $start_time, 2);
        echo "\nDone.  {$total} citations indexed in {$elapsed}s.\n";
    }

    // =========================================================================
    // Index management
    // =========================================================================

    public function clean_index(string $type = 'surveys'): void
    {
        echo "Clearing {$type} index...\n";
        $result = $this->manager->clear_index($type);
        if (($result['status'] ?? 1) === 0) {
            echo "Done. Deleted " . ($result['deleted'] ?? 0) . " documents.\n";
        } else {
            echo "Error: " . ($result['error'] ?? 'unknown') . "\n";
        }
    }

    public function refresh_index(string $type = 'surveys'): void
    {
        echo "Refreshing {$type} index...\n";
        $status = $this->manager->commit_index_changes($type);
        echo $status === 0 ? "Done.\n" : "Error — check logs.\n";
    }

    // Alias kept for SOLR-style familiarity
    public function commit_index(string $type = 'surveys'): void
    {
        $this->refresh_index($type);
    }

    public function status(): void
    {
        try {
            require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
            $schema = new OpenSearch_schema_manager();

            $db_surveys   = (int)$this->db->query('SELECT COUNT(id)  AS t FROM surveys')->row_array()['t'];
            $db_variables = (int)$this->db->query('SELECT COUNT(uid) AS t FROM variables')->row_array()['t'];
            $db_citations = (int)$this->db->query('SELECT COUNT(id)  AS t FROM citations')->row_array()['t'];

            $idx_surveys   = $this->manager->count_documents('surveys');
            $idx_variables = $this->manager->count_documents('variables');
            $idx_citations = $this->manager->count_documents('citations');

            $out = [
                'database' => [
                    'surveys'   => $db_surveys,
                    'variables' => $db_variables,
                    'citations' => $db_citations,
                ],
                'index' => [
                    'surveys'   => $idx_surveys,
                    'variables' => $idx_variables,
                    'citations' => $idx_citations,
                ],
                'indices_exist' => [
                    'surveys'   => $schema->index_exists('surveys'),
                    'variables' => $schema->index_exists('variables'),
                    'citations' => $schema->index_exists('citations'),
                ],
                'sync' => [
                    'surveys'   => $db_surveys   - $idx_surveys,
                    'variables' => $db_variables - $idx_variables,
                    'citations' => $db_citations - $idx_citations,
                ],
            ];
            echo json_encode($out, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n";
            exit(1);
        }
    }

    public function ping(): void
    {
        echo "Testing connectivity...\n";
        $result = $this->manager->ping_test();
        if (($result['status'] ?? '') === 'OK') {
            echo "OK — cluster: " . $result['cluster_name'] . ", version: " . $result['version'] . "\n";
        } else {
            echo "FAILED — " . ($result['error'] ?? 'unknown error') . "\n";
            exit(1);
        }
    }

    // =========================================================================
    // Schema management
    // =========================================================================

    public function create_index(string $type = 'surveys', string $replace = 'false'): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema   = new OpenSearch_schema_manager();
        $do_replace = ($replace === 'true' || $replace === '1');

        echo "Creating {$type} index" . ($do_replace ? ' (replacing existing)' : '') . "...\n";
        $result = $schema->create_index($type, $do_replace);

        switch ($result['status']) {
            case 'success': echo "Done. Index: " . $result['index'] . "\n"; break;
            case 'exists':  echo "Already exists. Use 'create_index {$type} true' to replace.\n"; break;
            default:        echo "Error: " . ($result['error'] ?? 'unknown') . "\n"; exit(1);
        }
    }

    public function delete_index(string $type = 'surveys'): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();

        echo "Deleting {$type} index...\n";
        $result = $schema->delete_index($type);

        switch ($result['status']) {
            case 'success':   echo "Done.\n"; break;
            case 'not_found': echo "Index does not exist.\n"; break;
            default:          echo "Error: " . ($result['error'] ?? 'unknown') . "\n"; exit(1);
        }
    }

    /**
     * Create all three indices.
     */
    public function setup_schema(string $replace = 'false'): void
    {
        foreach (['surveys', 'variables', 'citations'] as $type) {
            $this->create_index($type, $replace);
        }
    }

    public function validate_schema(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();

        $all_ok = true;
        foreach (['surveys', 'variables', 'citations'] as $type) {
            $exists = $schema->index_exists($type);
            echo ($exists ? '[OK] ' : '[MISSING] ') . $type . "\n";
            if (!$exists) $all_ok = false;
        }

        if ($all_ok) {
            echo "\nAll indices present.\n";
        } else {
            echo "\nRun 'setup_schema' to create missing indices.\n";
            exit(1);
        }
    }

    public function test_connection(): void
    {
        require_once APPPATH . 'libraries/OpenSearch/OpenSearch_schema_manager.php';
        $schema = new OpenSearch_schema_manager();
        $result = $schema->test_connection();

        if ($result['connected']) {
            echo "Connected — cluster: " . $result['cluster_name'] . ", version: " . $result['version'] . "\n";
            echo "  nada_surveys:   " . ($result['index_surveys']   ? 'EXISTS' : 'missing') . "\n";
            echo "  nada_variables: " . ($result['index_variables'] ? 'EXISTS' : 'missing') . "\n";
            echo "  nada_citations: " . ($result['index_citations'] ? 'EXISTS' : 'missing') . "\n";
        } else {
            echo "FAILED — " . ($result['error'] ?? 'unknown') . "\n";
            exit(1);
        }
    }

    // =========================================================================
    // Help
    // =========================================================================

    public function index(): void
    {
        $this->help();
    }

    public function help(): void
    {
        echo <<<'HELP'
OpenSearch CLI
==============

Indexing:
  index_studies [offset]               Index all surveys (batched, offset optional)
  index_single_survey <id>             Index one survey
  index_variables [offset]             Index all variables (batched, offset optional)
  index_citations [offset]             Index all citations (batched, offset optional)

Index management:
  clean_index [type]                   Delete all documents (default: surveys)
  refresh_index [type]                 Force refresh (default: surveys)
  status                               DB vs index counts (JSON)
  ping                                 Connectivity test

Schema management:
  create_index <type> [replace]        Create index (surveys|variables|citations)
  delete_index <type>                  Delete index
  setup_schema [replace]               Create all indices
  validate_schema                      Check all indices exist
  test_connection                      Full connection + index check

Examples:
  php index.php cli/opensearch/ping
  php index.php cli/opensearch/create_index surveys true
  php index.php cli/opensearch/setup_schema
  php index.php cli/opensearch/index_studies
  php index.php cli/opensearch/index_studies 500
  php index.php cli/opensearch/index_variables
  php index.php cli/opensearch/index_variables 1000
  php index.php cli/opensearch/index_citations
  php index.php cli/opensearch/index_citations 500
  php index.php cli/opensearch/status
  php index.php cli/opensearch/refresh_index

HELP;
    }
}
