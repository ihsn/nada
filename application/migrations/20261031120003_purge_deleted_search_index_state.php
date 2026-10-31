<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: purge search_index_state rows with status 'deleted'
 *
 * search_index_state now only tracks objects that are in the index or in
 * flight; a delete ack removes the row instead of marking it 'deleted'. No
 * sync diff ever read those rows, so this just clears the ones left behind
 * by earlier versions. Works on both MySQL and SQL Server.
 */
class Migration_Purge_deleted_search_index_state extends MY_Migration {

    public function up()
    {
        if (!$this->db->table_exists('search_index_state')) {
            log_message('info', 'search_index_state does not exist, skipping');
            return;
        }

        $this->db->query("DELETE FROM search_index_state WHERE status = 'deleted'");
        log_message('info', 'Purged ' . $this->db->affected_rows() . ' deleted rows from search_index_state');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
