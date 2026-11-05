<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Create study_presentation: catalog display settings per study (e.g. the default view of an
 * indicator's chart and table pages). Admin-managed settings, not part of the public metadata.
 */
class Migration_Study_presentation extends MY_Migration {

	public function up()
	{
		if ($this->table_exists('study_presentation')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->db->query("
				CREATE TABLE `study_presentation` (
					`sid` INT NOT NULL,
					`settings` JSON NOT NULL,
					`created_by` INT UNSIGNED DEFAULT NULL,
					`changed_by` INT UNSIGNED DEFAULT NULL,
					`created` INT UNSIGNED DEFAULT NULL,
					`changed` INT UNSIGNED DEFAULT NULL,
					PRIMARY KEY (`sid`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
		} elseif ($driver === 'sqlsrv') {
			$this->db->query("
				CREATE TABLE study_presentation (
					sid INT NOT NULL,
					settings NVARCHAR(MAX) NOT NULL,
					created_by INT NULL,
					changed_by INT NULL,
					created INT NULL,
					changed INT NULL,
					PRIMARY KEY (sid),
					CONSTRAINT ck_study_presentation_settings_isjson CHECK (ISJSON(settings)=1)
				)
			");
		} else {
			$this->emit('study_presentation: unsupported driver ' . $driver . ', skipping' . PHP_EOL);
			return;
		}

		$this->forget_table_cache();
		$this->emit('Created study_presentation' . PHP_EOL);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
