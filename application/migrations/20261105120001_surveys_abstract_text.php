<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Widen surveys.abstract from 500 to 2000 characters so search result cards can show longer
 * abstracts. Keep in step with Dataset_model::ABSTRACT_MAX_LENGTH.
 *
 * MySQL  : VARCHAR(500)  -> VARCHAR(2000)
 * SQLSRV : NVARCHAR(500) -> NVARCHAR(MAX)
 *
 * Existing rows keep their 500-character copies until rebuilt with:
 *   php index.php cli/catalog/populate_abstracts 200 all
 */
class Migration_Surveys_abstract_text extends MY_Migration {

	public function up()
	{
		if (!$this->column_exists('surveys', 'abstract')) {
			$this->emit('surveys.abstract does not exist, skipping' . PHP_EOL);
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			$this->emit('surveys.abstract: unsupported driver ' . $driver . ', skipping' . PHP_EOL);
			return;
		}

		$this->forget_table_cache();
	}

	private function up_mysql()
	{
		$row = $this->db->query("SHOW COLUMNS FROM `surveys` LIKE 'abstract'")->row_array();
		if ($row && preg_match('/^varchar\((\d+)\)/i', $row['Type'], $m) && (int) $m[1] >= 2000) {
			$this->emit('surveys.abstract is already ' . $row['Type'] . ', skipping' . PHP_EOL);
			return;
		}

		$this->assert_db_query(
			$this->db->query("ALTER TABLE `surveys` MODIFY COLUMN `abstract` VARCHAR(2000) NULL DEFAULT NULL"),
			'widen surveys.abstract to VARCHAR(2000)'
		);
		$this->emit('Widened surveys.abstract to VARCHAR(2000)' . PHP_EOL);
	}

	private function up_sqlsrv()
	{
		$row = $this->db->query("
			SELECT c.max_length
			FROM   sys.columns c
			JOIN   sys.tables  t ON c.object_id = t.object_id
			WHERE  t.name = 'surveys' AND c.name = 'abstract'
		")->row_array();

		if ($row && (int) $row['max_length'] === -1) {
			$this->emit('surveys.abstract is already NVARCHAR(MAX), skipping' . PHP_EOL);
			return;
		}

		$this->assert_db_query(
			$this->db->query("ALTER TABLE surveys ALTER COLUMN abstract NVARCHAR(MAX) NULL"),
			'widen surveys.abstract to NVARCHAR(MAX)'
		);
		$this->emit('Widened surveys.abstract to NVARCHAR(MAX)' . PHP_EOL);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
