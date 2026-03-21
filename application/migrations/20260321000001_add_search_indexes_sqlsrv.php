<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: add search and filter indexes for SQL Server
 *
 * The base schema was missing non-clustered indexes on tables that are
 * hit by every catalog search and variable search query. This caused
 * full table/index scans on high-traffic filter paths.
 *
 * Tables covered:
 *   surveys        - published, type, repositoryid, formid, data_class_id,
 *                    year_start, total_views, changed, created
 *   survey_years   - (data_coll_year) INCLUDE (sid)  [range filter]
 *   survey_countries - (cid) INCLUDE (sid)           [country filter]
 *   survey_repos   - (repositoryid) INCLUDE (sid), (sid)
 *   survey_facets  - (term_id) INCLUDE (sid), (sid)
 *   survey_tags    - (tag) INCLUDE (sid)              [tag filter]
 *
 * Safe to re-run: each index is guarded by IF NOT EXISTS, and the
 * migration framework silently skips SQL Server error 1913
 * (index already exists).
 *
 * MySQL / other drivers: skipped (no equivalent file).
 */
class Migration_Add_search_indexes_sqlsrv extends MY_Migration {

    public function up()
    {
        $driver = $this->db->dbdriver;

        if ($driver !== 'sqlsrv') {
            log_message('info', 'Migration_Add_search_indexes_sqlsrv: driver is ' . $driver . ', skipping (sqlsrv only)');
            return;
        }

        $sql_file = $this->get_sql_file_path('nada56-search-indexes');

        if (!file_exists($sql_file)) {
            throw new Exception('SQL file not found: ' . $sql_file);
        }

        log_message('info', 'Migration_Add_search_indexes_sqlsrv: adding search indexes');
        $this->execute_sql_file($sql_file);
        log_message('info', 'Migration_Add_search_indexes_sqlsrv: completed');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
