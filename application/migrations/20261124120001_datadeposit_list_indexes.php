<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: indexes for the admin data deposit project list (admin/datadeposit).
 *
 * dd_projects only had its primary key. Its rows are wide (metadata, submission and other
 * varchar(max) columns), so each list request scanned the whole table several times: once for
 * the total, once for the page (sorted by created_on), and once per status tab count.
 * dd_tasks.project_id (joined on every list row) and dd_collaborators.pid (keyword search)
 * had no index either.
 *
 * Keyword search on the list also matches each project's depositor and collaborators against
 * users (by email or username) and meta (by user_id). Those columns had no index, so with a
 * large users table every search scanned users once per project. Indexes on users.email,
 * users.username and meta.user_id fix that (and help login / user lookups site-wide).
 *
 * On SQL Server the created_on index also carries the list columns (INCLUDE), so the page
 * query reads only the narrow index. Columns of type text/ntext/image are left out, because
 * SQL Server does not allow them in INCLUDE (older installs may still have them).
 *
 * Each index is skipped when it already exists, so it can be run again.
 */
class Migration_Datadeposit_list_indexes extends MY_Migration {

	public function up()
	{
		$this->db->data_cache = array();

		$driver = strtolower((string) $this->db->dbdriver);
		$is_mysql = strpos($driver, 'mysql') !== false;
		$is_sqlsrv = $driver === 'sqlsrv';
		if (!$is_mysql && !$is_sqlsrv) {
			$this->emit("  database driver '{$driver}' not handled, skipping\n");
			return;
		}

		$indexes = array(
			array(
				'table' => 'dd_projects',
				'name' => 'idx_ddp_created_on',
				'columns' => array('created_on'),
				'include' => array('status', 'title', 'shortname', 'last_modified', 'created_by', 'is_embargoed', 'requested_reopen'),
			),
			array(
				'table' => 'dd_projects',
				'name' => 'idx_ddp_status',
				'columns' => array('status'),
				'include' => array('requested_reopen', 'is_embargoed'),
			),
			array(
				'table' => 'dd_tasks',
				'name' => 'idx_ddt_project_id',
				'columns' => array('project_id'),
				'include' => array('user_id', 'status'),
			),
			array(
				'table' => 'dd_collaborators',
				'name' => 'idx_ddc_pid',
				'columns' => array('pid'),
				'include' => array(),
			),
			array(
				'table' => 'users',
				'name' => 'idx_users_email',
				'columns' => array('email'),
				'include' => array(),
			),
			array(
				'table' => 'users',
				'name' => 'idx_users_username',
				'columns' => array('username'),
				'include' => array(),
			),
			array(
				'table' => 'meta',
				'name' => 'idx_meta_user_id',
				'columns' => array('user_id'),
				'include' => array('first_name', 'last_name'),
			),
		);

		foreach ($indexes as $index) {
			$this->add_index($index, $is_mysql);
		}
	}

	private function add_index(array $index, $is_mysql)
	{
		$name = $index['name'];
		if (!$this->table_exists($index['table'])) {
			$this->emit("  {$index['table']} does not exist, skipping {$name}\n");
			return;
		}
		if ($this->index_exists($index['table'], $name)) {
			$this->emit("  {$name} already exists\n");
			return;
		}
		foreach ($index['columns'] as $column) {
			if (!$this->sqlsrv_has_column($index['table'], $column)) {
				$this->emit("  {$index['table']}.{$column} does not exist, skipping {$name}\n");
				return;
			}
		}

		$table = $this->db->dbprefix($index['table']);

		if (!$is_mysql) {
			$info = $this->sqlsrv_column_info($table);
			foreach ($index['columns'] as $column) {
				$col = isset($info[strtolower($column)]) ? $info[strtolower($column)] : null;
				// Key columns: no text/ntext/image or (max), and at most 1700 bytes.
				if ($col === null
					|| in_array($col['type'], array('text', 'ntext', 'image'), true)
					|| $col['max_length'] < 0
					|| $col['max_length'] > 1700) {
					$this->emit("  {$index['table']}.{$column} cannot be an index key, skipping {$name}\n");
					return;
				}
			}
		}

		if ($is_mysql) {
			$sql = 'ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (`' . implode('`, `', $index['columns']) . '`)';
		} else {
			$sql = 'CREATE NONCLUSTERED INDEX ' . $name . ' ON ' . $table . ' (' . implode(', ', $index['columns']) . ')';
			$include = $this->sqlsrv_includable_columns($table, $index['include']);
			if (!empty($include)) {
				$sql .= ' INCLUDE (' . implode(', ', $include) . ')';
			}
		}

		$this->assert_db_query($this->db->query($sql), $name);
		$this->emit("  added {$name}\n");
	}

	/**
	 * Column type and byte length (-1 for max) by lower-case column name.
	 *
	 * @param string $table
	 * @return array
	 */
	private function sqlsrv_column_info($table)
	{
		$rows = $this->db->query(
			'SELECT c.name AS column_name, t.name AS type_name, c.max_length'
			. ' FROM sys.columns c'
			. ' INNER JOIN sys.tables tb ON tb.object_id = c.object_id'
			. ' INNER JOIN sys.types t ON t.user_type_id = c.user_type_id'
			. ' WHERE tb.name = ' . $this->db->escape($table)
		)->result_array();

		$info = array();
		foreach ($rows as $row) {
			$info[strtolower($row['column_name'])] = array(
				'type' => strtolower($row['type_name']),
				'max_length' => (int) $row['max_length'],
			);
		}
		return $info;
	}

	/**
	 * Columns from $columns that exist on the table and can be used in INCLUDE.
	 *
	 * @param string $table
	 * @param array $columns
	 * @return array
	 */
	private function sqlsrv_includable_columns($table, array $columns)
	{
		$info = $this->sqlsrv_column_info($table);
		$out = array();
		foreach ($columns as $column) {
			$col = isset($info[strtolower($column)]) ? $info[strtolower($column)] : null;
			if ($col === null || in_array($col['type'], array('text', 'ntext', 'image'), true)) {
				continue;
			}
			$out[] = $column;
		}
		return $out;
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
