<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: link data deposit collaborators to user accounts.
 *
 * Project membership (owner / collaborator) was stored by email only, so a user lost every
 * project when their account email changed (for example after a move to a new email domain).
 *
 * - Adds dd_collaborators.user_id (NULL = invite to an email that has no account yet).
 * - Indexes user_id and email (membership checks and linking look up by both).
 * - Links existing rows to accounts by email. With email_domain_equivalence enabled in
 *   auth.local.php, an email in one of the listed domains also links to the single account
 *   with the same mailbox name in another listed domain.
 * - Removes duplicate rows left when one account appears twice on a project (keeps the owner).
 *
 * Rows that cannot be linked now are linked when that user next uses data deposit, or with
 * `php index.php cli/datadeposit/link_collaborators`. Safe to run again.
 */
class Migration_Dd_collaborators_user_id extends MY_Migration {

	public function up()
	{
		if (!$this->table_exists('dd_collaborators')) {
			$this->emit("  dd_collaborators does not exist, skipping\n");
			return;
		}

		$driver = strtolower((string) $this->db->dbdriver);
		$is_mysql = strpos($driver, 'mysql') !== false;
		$is_sqlsrv = $driver === 'sqlsrv';
		if (!$is_mysql && !$is_sqlsrv) {
			$this->emit("  database driver '{$driver}' not handled, skipping\n");
			return;
		}

		$table = $this->db->dbprefix('dd_collaborators');

		if ($this->sqlsrv_has_column('dd_collaborators', 'user_id')) {
			$this->emit("  dd_collaborators.user_id already exists\n");
		} else {
			$sql = $is_mysql
				? 'ALTER TABLE `' . $table . '` ADD COLUMN `user_id` INT NULL'
				: 'ALTER TABLE ' . $table . ' ADD user_id INT NULL';
			$this->assert_db_query($this->db->query($sql), 'dd_collaborators.user_id');
			$this->emit("  added dd_collaborators.user_id\n");
		}
		$this->db->data_cache = array();

		$this->add_index($table, 'idx_ddc_user_id', 'user_id', $is_mysql);
		$this->add_index($table, 'idx_ddc_email', 'email', $is_mysql);

		$this->load->model('DD_project_model');
		$stats = $this->DD_project_model->link_all_collaborators();
		$this->emit(sprintf(
			"  linked %d row(s) for %d email(s); %d email(s) have no account yet; %d duplicate row(s) removed\n",
			$stats['linked_rows'],
			$stats['emails'] - $stats['unmatched_emails'],
			$stats['unmatched_emails'],
			$stats['duplicates_removed']
		));
	}

	private function add_index($table, $name, $column, $is_mysql)
	{
		if ($this->index_exists($table, $name)) {
			$this->emit("  {$name} already exists\n");
			return;
		}
		$sql = $is_mysql
			? 'ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (`' . $column . '`)'
			: 'CREATE NONCLUSTERED INDEX ' . $name . ' ON ' . $table . ' (' . $column . ')';
		$this->assert_db_query($this->db->query($sql), $name);
		$this->emit("  added {$name}\n");
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
