<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog CLI Controller
 *
 * Usage:  php index.php cli/catalog/<command> [args]
 *
 * Data maintenance
 * ----------------
 *   populate_abstracts [batch_size]   Extract and back-fill surveys.abstract
 *                                     for all existing records (default batch: 200)
 */
class Catalog extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->input->is_cli_request()) {
            die("This controller can only be run from the command line.\n");
        }

        set_time_limit(0);
        $this->load->database();
    }

    public function index(): void
    {
        echo "NADA Catalog CLI\n";
        echo "================\n\n";
        echo "Available commands:\n";
        echo "  php index.php cli/catalog/populate_abstracts [batch_size]\n";
        echo "      Extract abstract from metadata and populate surveys.abstract\n";
        echo "      for all existing records. Safe to re-run (skips non-NULL rows).\n";
        echo "\n";
    }

    // =========================================================================
    // populate_abstracts
    // =========================================================================

    /**
     * Back-fill surveys.abstract from metadata JSON for all existing records.
     *
     * Processes surveys in batches. Skips rows where abstract is already set.
     * Safe to resume — re-running processes only rows still NULL.
     *
     * Usage:
     *   php index.php cli/catalog/populate_abstracts
     *   php index.php cli/catalog/populate_abstracts 500
     */
    public function populate_abstracts(int $batch_size = 200): void
    {
        $batch_size = max(1, (int)$batch_size);

        $this->load->model('Dataset_model');

        $total_rows    = (int)$this->db->where('abstract IS NULL')->count_all_results('surveys');
        $total_surveys = (int)$this->db->count_all('surveys');

        echo "surveys.abstract back-fill\n";
        echo str_repeat('-', 50) . "\n";
        echo "Total surveys       : {$total_surveys}\n";
        echo "Rows needing update : {$total_rows}\n";
        echo "Batch size          : {$batch_size}\n";
        echo str_repeat('-', 50) . "\n\n";

        if ($total_rows === 0) {
            echo "Nothing to do — all rows already have an abstract value.\n";
            return;
        }

        $offset     = 0;
        $updated    = 0;
        $skipped    = 0;
        $batch_num  = 0;
        $start      = microtime(true);

        while (true) {
            $rows = $this->db
                ->select('id, type, metadata')
                ->where('abstract IS NULL')
                ->limit($batch_size, $offset)
                ->get('surveys')
                ->result_array();

            if (empty($rows)) {
                break;
            }

            echo 'Batch ' . (++$batch_num) . ' (' . count($rows) . ' rows)... ';

            $batch_updated = 0;

            foreach ($rows as $row) {
                $metadata = $this->decode_metadata($row['metadata']);

                if ($metadata === null) {
                    $skipped++;
                    continue;
                }

                $abstract = $this->Dataset_model->extract_abstract($metadata, $row['type']);

                $this->db->where('id', (int)$row['id'])
                         ->update('surveys', ['abstract' => $abstract]);

                $batch_updated++;
            }

            $updated += $batch_updated;
            $offset  += count($rows);

            echo "updated {$batch_updated}. Running total: {$updated}\n";
        }

        $elapsed = round(microtime(true) - $start, 1);
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Done in {$elapsed}s.\n";
        echo "Updated : {$updated}\n";
        echo "Skipped : {$skipped} (unreadable metadata)\n";
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function decode_metadata(?string $raw)
    {
        if (empty($raw)) {
            return null;
        }

        // Metadata is stored as base64(serialize($array))
        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            return null;
        }

        $data = @unserialize($decoded);
        if ($data === false || !is_array($data)) {
            return null;
        }

        return $data;
    }
}
