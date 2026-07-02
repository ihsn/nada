<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Catalog search performance indexes (surveys and related filter tables).
 * Runs install/nada56-search-indexes-{mysql,sqlsrv}.sql via execute_sql_file (idempotent).
 */
class Migration_Add_search_performance_indexes extends MY_Migration {

	public function up()
	{
		$driver = $this->db->dbdriver;

		if ($driver !== 'mysqli' && $driver !== 'sqlsrv') {
			throw new Exception(
				'Search performance indexes require dbdriver mysqli (MySQL) or sqlsrv (SQL Server); got: ' . $driver
			);
		}

		$sql_file = $this->get_sql_file_path('nada56-search-indexes');

		if (!file_exists($sql_file)) {
			throw new Exception('SQL file not found: ' . $sql_file);
		}

		log_message('info', 'Migration_Add_search_performance_indexes: executing ' . basename($sql_file));
		$this->execute_sql_file($sql_file);
		log_message('info', 'Migration_Add_search_performance_indexes completed');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
