<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add api_logs.user_id and the api_logs performance indexes. Fresh installs get both from
 * schema.*.sql, but upgraded sites kept the NADA 5 table without the column, so REST
 * request logging failed with "Unknown column 'user_id'".
 */
class Migration_Api_logs_user_id extends MY_Migration {

	public function up()
	{
		if (! $this->table_exists('api_logs')) {
			return;
		}

		if (! $this->column_exists('api_logs', 'user_id')) {
			$this->load->dbforge();
			$this->dbforge->add_column('api_logs', array(
				'user_id' => array('type' => 'INT', 'null' => TRUE, 'default' => NULL),
			));
			$this->forget_table_cache();
			$this->emit('Added api_logs.user_id' . PHP_EOL);
		}

		$this->execute_install_sql('add-api-logs-indexes');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
