<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog CLI Controller
 *
 * Usage:  php index.php cli/catalog/<command> [args]
 *
 * Data maintenance
 * ----------------
 *   populate_abstracts [batch_size] [all]
 *                                     Extract and back-fill surveys.abstract (default batch: 200).
 *                                     Only NULL rows unless "all" is given, which rebuilds every row.
 *
 *   indicator_ts_orphans [purge]      Scan MongoDB indicator_ts_* collections for observation
 *                                     rows whose sid no longer exists in surveys. Optional
 *                                     argument "purge" deletes those documents (destructive).
 *
 *   indicator_ts_refresh_counts [sid] Recompute surveys.ts_data_count from Mongo for one sid
 *                                     or all studies with a linked data_structure_id.
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
        echo "  php index.php cli/catalog/populate_abstracts [batch_size] [all]\n";
        echo "      Extract abstract from metadata and populate surveys.abstract\n";
        echo "      for all existing records. Safe to re-run (skips non-NULL rows).\n";
        echo "  php index.php cli/catalog/indicator_ts_orphans [purge]\n";
        echo "      Report (or with purge, remove) MongoDB indicator observations orphaned\n";
        echo "      after study deletion or sid changes.\n";
        echo "  php index.php cli/catalog/indicator_ts_refresh_counts [sid]\n";
        echo "      Back-fill or refresh surveys.ts_data_count from Mongo (one sid or all linked studies).\n";
        echo "\n";
    }

    // =========================================================================
    // populate_abstracts
    // =========================================================================

    /**
     * Back-fill surveys.abstract from metadata JSON.
     *
     * By default only rows whose abstract is NULL are processed. Pass "all" as the second
     * argument to rebuild every row (e.g. after the abstract length limit was raised).
     * Rows are paged by id, so the command is safe to interrupt and re-run.
     *
     * Usage:
     *   php index.php cli/catalog/populate_abstracts
     *   php index.php cli/catalog/populate_abstracts 500
     *   php index.php cli/catalog/populate_abstracts 200 all
     */
    public function populate_abstracts(int $batch_size = 200, string $mode = ''): void
    {
        $batch_size = max(1, (int)$batch_size);
        $rebuild_all = ($mode === 'all');

        $this->load->model('Dataset_model');

        if ($rebuild_all) {
            $total_rows = (int)$this->db->count_all('surveys');
        } else {
            $total_rows = (int)$this->db->where('abstract IS NULL')->count_all_results('surveys');
        }
        $total_surveys = (int)$this->db->count_all('surveys');

        echo "surveys.abstract back-fill" . ($rebuild_all ? " (rebuild all)" : "") . "\n";
        echo str_repeat('-', 50) . "\n";
        echo "Total surveys       : {$total_surveys}\n";
        echo "Rows needing update : {$total_rows}\n";
        echo "Batch size          : {$batch_size}\n";
        echo str_repeat('-', 50) . "\n\n";

        if ($total_rows === 0) {
            echo "Nothing to do — all rows already have an abstract value.\n";
            return;
        }

        $last_id    = 0;
        $updated    = 0;
        $skipped    = 0;
        $batch_num  = 0;
        $start      = microtime(true);

        while (true) {
            $this->db
                ->select('id, type, metadata')
                ->where('id >', $last_id);
            if (!$rebuild_all) {
                $this->db->where('abstract IS NULL');
            }
            $rows = $this->db
                ->order_by('id', 'ASC')
                ->limit($batch_size)
                ->get('surveys')
                ->result_array();

            if (empty($rows)) {
                break;
            }

            echo 'Batch ' . (++$batch_num) . ' (' . count($rows) . ' rows)... ';

            $batch_updated = 0;

            foreach ($rows as $row) {
                $last_id  = (int)$row['id'];
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

            echo "updated {$batch_updated}. Running total: {$updated}\n";
        }

        $elapsed = round(microtime(true) - $start, 1);
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Done in {$elapsed}s.\n";
        echo "Updated : {$updated}\n";
        echo "Skipped : {$skipped} (unreadable metadata)\n";
    }

    // =========================================================================
    // indicator_ts_orphans
    // =========================================================================

    /**
     * Scan MongoDB indicator_ts_* collections for observations referencing deleted surveys.
     *
     * Usage:
     *   php index.php cli/catalog/indicator_ts_orphans
     *   php index.php cli/catalog/indicator_ts_orphans purge
     */
    public function indicator_ts_orphans(string $mode = 'scan'): void
    {
        $this->load->model('Timeseries_mongo_model');

        $purge = ($mode === 'purge');
        if ($purge) {
            echo "Purging orphan indicator observations (MongoDB)...\n";
            $r = $this->Timeseries_mongo_model->purge_orphan_indicator_observations();
        } else {
            echo "Scanning for orphan indicator observations (MongoDB)...\n";
            $r = $this->Timeseries_mongo_model->scan_orphan_indicator_observations();
        }

        echo str_repeat('-', 50) . "\n";

        if (empty($r['ok'])) {
            echo "FAILED: " . (isset($r['error']) ? $r['error'] : 'unknown error') . "\n";
            exit(1);
        }

        if ($purge) {
            echo 'Groups purged (collection + sid): ' . (int) $r['groups'] . "\n";
            echo 'Documents deleted: ' . (int) $r['deleted'] . "\n";
            return;
        }

        echo 'Collections scanned: ' . (int) $r['collections_scanned'] . "\n";
        echo 'Orphan groups (collection + sid): ' . count($r['orphans']) . "\n";
        echo 'Total orphan documents: ' . (int) $r['total_orphan_documents'] . "\n";
        echo str_repeat('-', 50) . "\n";

        if ($r['orphans'] === []) {
            echo "No orphan indicator observations found.\n";
            return;
        }

        foreach ($r['orphans'] as $row) {
            echo sprintf(
                "%s  sid=%d  documents=%d\n",
                $row['collection'],
                (int) $row['sid'],
                (int) $row['documents']
            );
        }

        echo "\nTo delete these documents, run:\n";
        echo "  php index.php cli/catalog/indicator_ts_orphans purge\n";
    }

    // =========================================================================
    // indicator_ts_refresh_counts
    // =========================================================================

    /**
     * Recompute surveys.ts_data_count from Mongo observation counts.
     *
     * Usage:
     *   php index.php cli/catalog/indicator_ts_refresh_counts
     *   php index.php cli/catalog/indicator_ts_refresh_counts 369
     */
    public function indicator_ts_refresh_counts($sid = null): void
    {
        $this->load->model('Timeseries_mongo_model');

        if (!$this->db->field_exists('ts_data_count', 'surveys')) {
            echo "surveys.ts_data_count is missing — run database migrations first.\n";
            exit(1);
        }

        if ($sid !== null && $sid !== '' && ctype_digit((string) $sid)) {
            $id = (int) $sid;
            $n  = $this->Timeseries_mongo_model->refresh_ts_data_count_for_sid($id);
            echo "sid={$id}  ts_data_count={$n}\n";

            return;
        }

        if ($sid !== null && $sid !== '') {
            echo "Invalid sid; use a numeric surveys.id or omit for bulk refresh.\n";
            exit(1);
        }

        echo "Refreshing ts_data_count for surveys with data_structure_id set...\n";

        $q = $this->db->select('id')->from('surveys')->where('data_structure_id IS NOT NULL')->order_by('id', 'ASC')->get();
        if (!$q) {
            echo "Query failed.\n";
            exit(1);
        }

        $rows   = $q->result_array();
        $total  = count($rows);
        $done   = 0;
        $start  = microtime(true);

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $this->Timeseries_mongo_model->refresh_ts_data_count_for_sid($id);
            $done++;
            if ($done % 100 === 0 || $done === $total) {
                echo "  processed {$done} / {$total}\n";
            }
        }

        $elapsed = round(microtime(true) - $start, 1);
        echo str_repeat('-', 50) . "\n";
        echo "Done in {$elapsed}s. Refreshed {$done} studies.\n";
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
