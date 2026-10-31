<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Widen dd_projects title/shortname and submission note mirror columns.
 */
class Migration_Datadeposit_field_limits extends MY_Migration {

	public function up()
	{
		$this->drop_sqlsrv_note_defaults();
		$this->execute_install_sql('nada57-dd-projects-field-limits');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	/**
	 * SQL Server: installs from earlier releases created these columns with an inline
	 * DEFAULT NULL, which is a named default constraint and blocks ALTER COLUMN.
	 *
	 * @return void
	 */
	private function drop_sqlsrv_note_defaults()
	{
		if ($this->db->dbdriver !== 'sqlsrv' || !$this->db->table_exists('dd_projects')) {
			return;
		}

		$rows = $this->db->query(
			"SELECT dc.name AS constraint_name
			 FROM sys.default_constraints dc
			 INNER JOIN sys.columns c ON c.object_id = dc.parent_object_id AND c.column_id = dc.parent_column_id
			 WHERE dc.parent_object_id = OBJECT_ID(N'dbo.dd_projects')
			 AND c.name IN ('embargoed','disclosure_risk','key_variables','sensitive_variables')"
		);

		if (!$rows) {
			return;
		}

		foreach ($rows->result_array() as $row) {
			$name = str_replace(']', ']]', $row['constraint_name']);
			$this->assert_db_query(
				$this->db->query('ALTER TABLE dbo.dd_projects DROP CONSTRAINT [' . $name . ']'),
				'drop dd_projects default ' . $row['constraint_name']
			);
		}
	}
}
