<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Platform baseline: resources table upgrade, OAuth authtype columns, API key security.
 * Idempotent: each bundled step skips already-applied schema changes.
 *
 * Then brings older tables to the fresh-install schema (install/schema.*.sql): resources text columns widened (and
 * Unicode on SQL Server; never narrowed), and the api_logs indexes.
 */
class Migration_Platform_baseline extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20251022000001_upgrade_resources_table.php',
			'20251027000001_add_authtype_to_users.php',
			'20251128000001_api_keys_security_enhancement.php',
		));
		$this->widen_resources_columns();
		$this->add_api_logs_indexes();
	}

	/** resources text columns as install/schema.*.sql defines them: column => [max length (0 = unlimited), NOT NULL] */
	private $resources_columns = array(
		'dctype'      => array(255, FALSE),
		'filename'    => array(500, FALSE),
		'dcformat'    => array(255, FALSE),
		'title'       => array(500, TRUE),
		'subtitle'    => array(500, FALSE),
		'author'      => array(500, FALSE),
		'dcdate'      => array(45, FALSE),
		'country'     => array(100, FALSE),
		'language'    => array(255, FALSE),
		'contributor' => array(500, FALSE),
		'publisher'   => array(500, FALSE),
		'rights'      => array(0, FALSE),
		'subjects'    => array(0, FALSE),
		'description' => array(0, FALSE),
		'abstract'    => array(0, FALSE),
		'toc'         => array(0, FALSE),
	);

	private function widen_resources_columns()
	{
		if (!$this->table_exists('resources')) {
			return;
		}
		$driver = $this->db->dbdriver;
		foreach ($this->resources_columns as $column => $spec) {
			list($length, $not_null) = $spec;
			if (!$this->column_exists('resources', $column)) {
				continue;
			}
			if (in_array($driver, array('mysql', 'mysqli'), TRUE)) {
				$row = $this->db->query(
					'SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS
					 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
					array('resources', $column)
				)->row_array();
				// text columns longer than the target (mediumtext) are left as they are
				$current = (int) $row['len'];
				$target  = $length === 0 ? 65535 : $length;
				if ($current >= $target) {
					continue;
				}
				$type = $length === 0 ? 'TEXT' : 'VARCHAR(' . $length . ')';
				$this->assert_db_query(
					$this->db->query('ALTER TABLE `resources` MODIFY `' . $column . '` ' . $type . ($not_null ? ' NOT NULL' : ' NULL')),
					'widen resources.' . $column
				);
			} elseif ($driver === 'sqlsrv') {
				$row = $this->db->query(
					'SELECT DATA_TYPE AS t, CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
					array('resources', $column)
				)->row_array();
				$current = (int) $row['len'];
				$wide_enough = $current === -1 || ($length > 0 && $current >= $length);
				if ($row['t'] === 'nvarchar' && $wide_enough) {
					continue;
				}
				$type = $length === 0 ? 'NVARCHAR(MAX)' : 'NVARCHAR(' . max($length, $current) . ')';
				// ALTER COLUMN refuses a column with a default constraint (older schemas made one for DEFAULT NULL):
				// drop it, alter, and put the same default back
				$default = $this->db->query(
					"SELECT dc.name, dc.definition FROM sys.default_constraints dc
					 JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
					 WHERE dc.parent_object_id = OBJECT_ID('resources') AND c.name = ?",
					array($column)
				)->row_array();
				if ($default) {
					$this->assert_db_query(
						$this->db->query('ALTER TABLE resources DROP CONSTRAINT [' . $default['name'] . ']'),
						'drop default of resources.' . $column
					);
				}
				$this->assert_db_query(
					$this->db->query('ALTER TABLE resources ALTER COLUMN [' . $column . '] ' . $type . ($not_null ? ' NOT NULL' : ' NULL')),
					'widen resources.' . $column
				);
				if ($default && strtoupper(trim($default['definition'], '() ')) !== 'NULL') {
					$this->assert_db_query(
						$this->db->query('ALTER TABLE resources ADD CONSTRAINT [' . $default['name'] . '] DEFAULT ' . $default['definition'] . ' FOR [' . $column . ']'),
						'restore default of resources.' . $column
					);
				}
			}
		}
		if ($driver === 'sqlsrv' && !$this->index_exists('resources', 'idx_res_resource_type') && $this->column_exists('resources', 'resource_type')) {
			$this->assert_db_query(
				$this->db->query('CREATE NONCLUSTERED INDEX idx_res_resource_type ON resources (resource_type)'),
				'idx_res_resource_type'
			);
		}
		$this->forget_table_cache();
	}

	/** api_logs indexes as install/schema.*.sql defines them */
	private function add_api_logs_indexes()
	{
		if (!$this->table_exists('api_logs')) {
			return;
		}
		$driver  = $this->db->dbdriver;
		$indexes = array(
			'idx_api_logs_time'               => array('time'),
			'idx_api_logs_method'             => array('method'),
			'idx_api_logs_response_code'      => array('response_code'),
			'idx_api_logs_authorized'         => array('authorized'),
			'idx_api_logs_user_id'            => array('user_id'),
			'idx_api_logs_ip_address'         => array('ip_address'),
			'idx_api_logs_time_method'        => array('time', 'method'),
			'idx_api_logs_time_response_code' => array('time', 'response_code'),
		);
		// long text columns: prefix indexes on MySQL, as the fresh schema
		if ($driver === 'sqlsrv') {
			$indexes['idx_api_logs_uri']     = array('uri');
			$indexes['idx_api_logs_api_key'] = array('api_key');
		} else {
			$indexes['idx_api_logs_uri_prefix']     = array('uri(100)');
			$indexes['idx_api_logs_api_key_prefix'] = array('api_key(20)');
		}
		foreach ($indexes as $name => $columns) {
			if ($this->index_exists('api_logs', $name)) {
				continue;
			}
			if ($driver === 'sqlsrv') {
				$sql = 'CREATE NONCLUSTERED INDEX ' . $name . ' ON api_logs (' . implode(', ', $columns) . ')';
			} else {
				$cols = array_map(function ($c) {
					return preg_replace('/^(\w+)/', '`$1`', $c);
				}, $columns);
				$sql = 'ALTER TABLE `api_logs` ADD INDEX `' . $name . '` (' . implode(', ', $cols) . ')';
			}
			$this->assert_db_query($this->db->query($sql), $name);
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
