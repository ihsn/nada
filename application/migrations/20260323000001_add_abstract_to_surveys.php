<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: add abstract column to surveys table
 *
 * Stores a short plain-text abstract (up to 500 chars) extracted from each
 * study's metadata JSON at insert/update time. The column is also populated
 * in bulk via the CLI command:
 *   php index.php cli/catalog/populate_abstracts
 *
 * MySQL  : ALTER TABLE surveys ADD COLUMN abstract VARCHAR(500) NULL AFTER keywords
 * SQLSRV : ALTER TABLE surveys ADD abstract NVARCHAR(500) NULL
 */
class Migration_Add_abstract_to_surveys extends MY_Migration {

    public function up()
    {
        $driver   = $this->db->dbdriver;
        $is_mysql = in_array($driver, ['mysql', 'mysqli']);

        if ($is_mysql) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Add_abstract_to_surveys: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Add_abstract_to_surveys completed');
    }

    private function up_mysql()
    {
        $exists = $this->db->query(
            "SHOW COLUMNS FROM `surveys` LIKE 'abstract'"
        )->row_array();

        if ($exists) {
            log_message('info', 'surveys.abstract already exists (MySQL), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE `surveys` ADD COLUMN `abstract` VARCHAR(500) NULL DEFAULT NULL AFTER `keywords`"
        );
        log_message('info', 'Added surveys.abstract VARCHAR(500) (MySQL)');
    }

    private function up_sqlsrv()
    {
        $exists = $this->db->query("
            SELECT 1
            FROM   sys.columns c
            JOIN   sys.tables  t ON c.object_id = t.object_id
            WHERE  t.name = 'surveys'
            AND    c.name = 'abstract'
        ")->row_array();

        if ($exists) {
            log_message('info', 'surveys.abstract already exists (SQLSRV), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE surveys ADD abstract NVARCHAR(500) NULL"
        );
        log_message('info', 'Added surveys.abstract NVARCHAR(500) (SQLSRV)');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
