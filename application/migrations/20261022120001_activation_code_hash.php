<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Store activation codes hashed, with an expiry and a short failure window.
 */
class Migration_Activation_code_hash extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('users')) {
			return;
		}

		$this->widen_activation_code();
		$this->add_int_column('activation_code_expiry', true);
		$this->add_int_column('activation_attempt_ts', true);
		$this->add_attempt_count_column();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function widen_activation_code()
	{
		if ($this->db->dbdriver === 'sqlsrv') {
			$sql = 'ALTER TABLE users ALTER COLUMN activation_code varchar(64) NULL';
		} else {
			$sql = 'ALTER TABLE `users` MODIFY `activation_code` VARCHAR(64) NULL';
		}

		$this->assert_db_query($this->db->query($sql), 'widen users.activation_code');
	}

	/**
	 * @param string $column
	 * @param bool   $nullable
	 * @return void
	 */
	private function add_int_column($column, $nullable)
	{
		if ($this->users_column_exists($column)) {
			return;
		}

		$null_sql = $nullable ? 'NULL' : 'NOT NULL';
		if ($this->db->dbdriver === 'sqlsrv') {
			$sql = 'ALTER TABLE users ADD '.$column.' int '.$null_sql;
		} else {
			$sql = 'ALTER TABLE `users` ADD `'.$column.'` INT '.$null_sql;
		}

		$this->assert_db_query($this->db->query($sql), 'add users.'.$column);
	}

	private function add_attempt_count_column()
	{
		if ($this->users_column_exists('activation_attempt_count')) {
			return;
		}

		if ($this->db->dbdriver === 'sqlsrv') {
			$sql = 'ALTER TABLE users ADD activation_attempt_count int NOT NULL CONSTRAINT DF_users_activation_attempt_count DEFAULT 0';
		} else {
			$sql = 'ALTER TABLE `users` ADD `activation_attempt_count` INT NOT NULL DEFAULT 0';
		}

		$this->assert_db_query($this->db->query($sql), 'add users.activation_attempt_count');
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	private function users_column_exists($column)
	{
		if ($this->db->dbdriver === 'sqlsrv') {
			return $this->sqlsrv_has_column('users', $column);
		}

		return $this->column_exists('users', $column);
	}
}
