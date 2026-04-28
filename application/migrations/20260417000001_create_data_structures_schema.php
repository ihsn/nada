<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: Global data structures (DSD) for NADA catalogue
 *
 * Creates:
 *   - data_structures: one row per DSD version (agency + name + version unique; optional idno)
 *   - data_structure_components: columns aligned with metadata editor indicator_dsd (minus sid,
 *     sum_stats, inline/local codelists). Codelist binding is only via codelist_id -> codelists.id.
 */
class Migration_Create_data_structures_schema extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Create_data_structures_schema::up() called');

		if (!$this->db->table_exists('codelists')) {
			log_message('info', 'codelists table not present; skipping data_structures migration');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Create_data_structures_schema: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Create_data_structures_schema completed successfully');
	}

	private function up_mysql()
	{
		if ($this->db->table_exists('data_structures')) {
			log_message('info', 'data_structures already exists; skipping');
			return;
		}

		$this->db->query("
			CREATE TABLE `data_structures` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`agency` varchar(64) NOT NULL DEFAULT 'NADA',
				`name` varchar(64) NOT NULL,
				`version` varchar(32) NOT NULL,
				`idno` varchar(191) DEFAULT NULL,
				`status` varchar(32) DEFAULT NULL,
				`title` varchar(255) DEFAULT NULL,
				`description` varchar(255) DEFAULT NULL,
				`notes` text,
				`content_hash` char(64) DEFAULT NULL,
				`metadata` json DEFAULT NULL,
				`created` int(11) DEFAULT NULL,
				`updated` int(11) DEFAULT NULL,
				`created_by` int(11) DEFAULT NULL,
				`updated_by` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `unq_data_structures_identity` (`agency`,`name`,`version`),
				UNIQUE KEY `unq_data_structures_idno` (`idno`),
				KEY `idx_data_structures_agency_name` (`agency`,`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");

		$this->db->query("
			CREATE TABLE `data_structure_components` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`data_structure_id` int(11) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`name` varchar(100) NOT NULL,
				`label` varchar(255) DEFAULT NULL,
				`description` text,
				`data_type` enum('string','integer','float','double','date','boolean') DEFAULT NULL,
				`column_type` enum('dimension','time_period','measure','attribute','indicator_id','indicator_name','annotation','geography','observation_value','periodicity') NOT NULL,
				`time_period_format` varchar(30) DEFAULT NULL,
				`codelist_id` int(11) DEFAULT NULL,
				`metadata` json DEFAULT NULL,
				`created` int(11) DEFAULT NULL,
				`updated` int(11) DEFAULT NULL,
				`created_by` int(11) DEFAULT NULL,
				`updated_by` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `unq_dsc_structure_name` (`data_structure_id`,`name`),
				KEY `idx_dsc_structure_sort` (`data_structure_id`,`sort_order`),
				KEY `idx_dsc_codelist` (`codelist_id`),
				CONSTRAINT `fk_dsc_data_structure` FOREIGN KEY (`data_structure_id`) REFERENCES `data_structures` (`id`) ON DELETE CASCADE,
				CONSTRAINT `fk_dsc_codelist` FOREIGN KEY (`codelist_id`) REFERENCES `codelists` (`id`) ON DELETE RESTRICT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");

		log_message('info', 'Created data_structures and data_structure_components (MySQL)');
	}

	private function up_sqlsrv()
	{
		if ($this->db->table_exists('data_structures')) {
			log_message('info', 'data_structures already exists; skipping');
			return;
		}

		$this->db->query("
			CREATE TABLE data_structures (
				id int NOT NULL IDENTITY(1,1),
				agency varchar(64) NOT NULL CONSTRAINT df_data_structures_agency DEFAULT 'NADA',
				name varchar(64) NOT NULL,
				version varchar(32) NOT NULL,
				idno varchar(191) NULL,
				status varchar(32) NULL,
				title varchar(255) NULL,
				description varchar(255) NULL,
				notes nvarchar(max) NULL,
				content_hash char(64) NULL,
				metadata nvarchar(max) NULL,
				created int NULL,
				updated int NULL,
				created_by int NULL,
				updated_by int NULL,
				PRIMARY KEY (id),
				CONSTRAINT unq_data_structures_identity UNIQUE (agency, name, version)
			)
		");
		$this->db->query('CREATE UNIQUE INDEX unq_data_structures_idno ON data_structures(idno) WHERE idno IS NOT NULL');
		$this->db->query('CREATE INDEX idx_data_structures_agency_name ON data_structures(agency, name)');

		$this->db->query("
			CREATE TABLE data_structure_components (
				id int NOT NULL IDENTITY(1,1),
				data_structure_id int NOT NULL,
				sort_order int NOT NULL CONSTRAINT df_dsc_sort_order DEFAULT 0,
				name varchar(100) NOT NULL,
				label varchar(255) NULL,
				description nvarchar(max) NULL,
				data_type varchar(16) NULL,
				column_type varchar(32) NOT NULL,
				time_period_format varchar(30) NULL,
				codelist_id int NULL,
				metadata nvarchar(max) NULL,
				created int NULL,
				updated int NULL,
				created_by int NULL,
				updated_by int NULL,
				PRIMARY KEY (id),
				CONSTRAINT unq_dsc_structure_name UNIQUE (data_structure_id, name),
				CONSTRAINT fk_dsc_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id) ON DELETE CASCADE,
				CONSTRAINT fk_dsc_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id)
			)
		");
		$this->db->query('CREATE INDEX idx_dsc_structure_sort ON data_structure_components (data_structure_id, sort_order)');
		$this->db->query('CREATE INDEX idx_dsc_codelist ON data_structure_components (codelist_id)');

		log_message('info', 'Created data_structures and data_structure_components (SQL Server)');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
