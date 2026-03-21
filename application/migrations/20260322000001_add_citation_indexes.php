<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: add citation search and batch-loading indexes
 *
 * Add missing indexes on the citations,
 * survey_citations, and citation_authors tables required for
 * performant citation search and author/survey-count batch loads.
 *
 * Tables covered:
 *   citations        - published, ctype, pub_year, flag,
 *                      url_status, created_by, changed_by
 *   survey_citations - citationid  (batch count + NOT EXISTS filter)
 *   citation_authors - (cid, author_type)  [SQLSRV only — MySQL already has it]
 *
 */
class Migration_Add_citation_indexes extends MY_Migration {

    public function up()
    {
        $driver = $this->db->dbdriver;

        if (in_array($driver, ['mysql', 'mysqli'])) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Add_citation_indexes: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Add_citation_indexes: completed');
    }

    // =========================================================================
    // MySQL
    // =========================================================================

    private function up_mysql()
    {
        // citations — scalar filter indexes
        $this->mysql_add_index('citations', 'idx_citations_published',  'published');
        $this->mysql_add_index('citations', 'idx_citations_ctype',      'ctype');
        $this->mysql_add_index('citations', 'idx_citations_pub_year',   'pub_year');
        $this->mysql_add_index('citations', 'idx_citations_flag',       'flag');
        $this->mysql_add_index('citations', 'idx_citations_url_status', 'url_status');
        $this->mysql_add_index('citations', 'idx_citations_created_by', 'created_by');
        $this->mysql_add_index('citations', 'idx_citations_changed_by', 'changed_by');

        // survey_citations — citationid lookup
        $this->mysql_add_index('survey_citations', 'idx_survey_citations_citationid', 'citationid');
    }

    /**
     * Add a plain non-unique index if it does not already exist.
     */
    private function mysql_add_index(string $table, string $index_name, string $column)
    {
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name   = ?
               AND index_name   = ?',
            [$table, $index_name]
        )->row_array();

        if ($exists) {
            log_message('info', "Migration_Add_citation_indexes: {$index_name} already exists, skipping");
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` (`{$column}`)");
        log_message('info', "Migration_Add_citation_indexes: created {$index_name} on {$table}({$column})");
    }

    // =========================================================================
    // SQL Server
    // =========================================================================

    private function up_sqlsrv()
    {
        // citations — scalar filter indexes
        $this->sqlsrv_add_index('citations', 'idx_citations_published',  '([published])');
        $this->sqlsrv_add_index('citations', 'idx_citations_ctype',      '([ctype])');
        $this->sqlsrv_add_index('citations', 'idx_citations_pub_year',   '([pub_year])');
        $this->sqlsrv_add_index('citations', 'idx_citations_flag',       '([flag])');
        $this->sqlsrv_add_index('citations', 'idx_citations_url_status', '([url_status])');
        $this->sqlsrv_add_index('citations', 'idx_citations_created_by', '([created_by])');
        $this->sqlsrv_add_index('citations', 'idx_citations_changed_by', '([changed_by])');

        // survey_citations — citationid lookup
        $this->sqlsrv_add_index('survey_citations', 'idx_survey_citations_citationid', '([citationid])');

        // citation_authors — (cid, author_type) composite; present in MySQL, missing in SQLSRV
        $this->sqlsrv_add_index('citation_authors', 'idx_citation_authors_cid_type', '([cid], [author_type])');
    }

    /**
     * Add a non-clustered index if it does not already exist.
     *
     * @param string $columns  e.g. '([cid], [author_type])'
     */
    private function sqlsrv_add_index(string $table, string $index_name, string $columns)
    {
        $exists = $this->db->query(
            "SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)",
            [$index_name, $table]
        )->row_array();

        if ($exists) {
            log_message('info', "Migration_Add_citation_indexes: {$index_name} already exists, skipping");
            return;
        }

        $this->db->query("CREATE NONCLUSTERED INDEX {$index_name} ON [dbo].[{$table}] {$columns}");
        log_message('info', "Migration_Add_citation_indexes: created {$index_name} on {$table}");
    }

    // =========================================================================

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
