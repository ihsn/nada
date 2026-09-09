<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Filestore plus codelists SDMX/PID schema. SQL Server uses T-SQL gates
 * (OBJECT_ID / COL_LENGTH / sys.indexes) instead of bound OBJECT_ID(?) or
 * CI field_exists, which miss objects and then record the migration complete.
 */
trait Catalog_schema_gaps_trait {

	const CATALOG_DEFAULT_AGENCY = 'NADA';
	const CATALOG_DEFAULT_VERSION = '1.0';

	protected function ensure_filestore_table()
	{
		$driver = $this->db->dbdriver;

		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$this->assert_db_query($this->db->query("
				CREATE TABLE IF NOT EXISTS `filestore` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`file_name` varchar(255) DEFAULT NULL,
					`file_path` varchar(500) DEFAULT NULL,
					`file_ext` varchar(10) DEFAULT NULL,
					`is_image` tinyint(4) DEFAULT NULL,
					`changed` int(11) DEFAULT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `idx_filestore_file` (`file_name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			"), 'create filestore');
			$this->forget_table_cache();
			if (!$this->index_exists('filestore', 'idx_filestore_file')) {
				$this->assert_db_query(
					$this->db->query('ALTER TABLE `filestore` ADD UNIQUE KEY `idx_filestore_file` (`file_name`)'),
					'index idx_filestore_file'
				);
			}
			return;
		}

		if ($driver !== 'sqlsrv') {
			return;
		}

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.filestore', N'U') IS NULL
BEGIN
	CREATE TABLE dbo.filestore (
		id int NOT NULL IDENTITY(1,1),
		file_name varchar(255) NULL,
		file_path varchar(500) NULL,
		file_ext varchar(10) NULL,
		is_image tinyint NULL,
		changed int NULL,
		PRIMARY KEY (id)
	)
END
		"), 'create filestore');
		$this->forget_table_cache();

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.filestore', N'U') IS NOT NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'IX_filestore' AND object_id = OBJECT_ID(N'dbo.filestore')
)
BEGIN
	CREATE UNIQUE NONCLUSTERED INDEX IX_filestore ON dbo.filestore (file_name ASC)
END
		"), 'index IX_filestore');
	}

	protected function ensure_codelists_sdmx_identity()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$this->ensure_codelists_sdmx_identity_mysql();
			return;
		}
		if ($driver === 'sqlsrv') {
			$this->ensure_codelists_sdmx_identity_sqlsrv();
		}
	}

	protected function ensure_codelists_pid_versioning()
	{
		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'), true)) {
			$this->ensure_codelists_pid_versioning_mysql();
			return;
		}
		if ($driver === 'sqlsrv') {
			$this->ensure_codelists_pid_versioning_sqlsrv();
		}
	}

	private function ensure_codelists_sdmx_identity_mysql()
	{
		if (!$this->db->table_exists('codelists')) {
			return;
		}

		$agency = self::CATALOG_DEFAULT_AGENCY;
		$version = self::CATALOG_DEFAULT_VERSION;

		if (!$this->db->field_exists('agency', 'codelists')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `agency` VARCHAR(64) NOT NULL DEFAULT '{$agency}' AFTER `name`
			"), 'add codelists.agency');
		}
		if (!$this->db->field_exists('version', 'codelists')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `version` VARCHAR(32) NOT NULL DEFAULT '{$version}' AFTER `agency`
			"), 'add codelists.version');
		}
		if (!$this->db->field_exists('idno', 'codelists')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `idno` VARCHAR(191) DEFAULT NULL AFTER `version`
			"), 'add codelists.idno');
		}

		$this->assert_db_query($this->db->query("
			UPDATE `codelists` SET `agency` = '{$agency}' WHERE `agency` IS NULL OR `agency` = ''
		"), 'backfill codelists.agency');
		$this->assert_db_query($this->db->query("
			UPDATE `codelists` SET `version` = '{$version}' WHERE `version` IS NULL OR `version` = ''
		"), 'backfill codelists.version');
		$this->assert_db_query($this->db->query("
			UPDATE `codelists`
			SET `idno` = CONCAT(`agency`, '_', `name`, '_', `version`)
			WHERE `idno` IS NULL OR `idno` = ''
		"), 'backfill codelists.idno');

		if ($this->index_exists('codelists', 'unq_codelists_name')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` DROP INDEX `unq_codelists_name`'),
				'drop unq_codelists_name'
			);
		}
		if (!$this->index_exists('codelists', 'unq_codelists_identity')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD UNIQUE KEY `unq_codelists_identity` (`agency`, `name`, `version`)
			"), 'unq_codelists_identity');
		}
		if (!$this->index_exists('codelists', 'unq_codelists_idno')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD UNIQUE KEY `unq_codelists_idno` (`idno`)
			"), 'unq_codelists_idno');
		}
		if (!$this->index_exists('codelists', 'idx_codelists_agency_name')) {
			$this->assert_db_query($this->db->query("
				ALTER TABLE `codelists`
				ADD KEY `idx_codelists_agency_name` (`agency`, `name`)
			"), 'idx_codelists_agency_name');
		}
	}

	private function ensure_codelists_sdmx_identity_sqlsrv()
	{
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NULL
BEGIN
	CREATE TABLE dbo.codelists (
		id int NOT NULL IDENTITY(1,1),
		pid int NULL,
		name varchar(64) NOT NULL,
		agency varchar(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT N'NADA',
		version varchar(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT N'1.0',
		version_seq int NOT NULL,
		idno varchar(191) NULL,
		description varchar(255) NULL,
		status smallint NOT NULL CONSTRAINT df_codelists_status DEFAULT 0,
		created int NULL,
		changed int NULL,
		PRIMARY KEY (id),
		CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version)
	)
END
		"), 'create codelists');
		$this->forget_table_cache();

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'agency') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD agency varchar(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT N'NADA'
END
		"), 'add codelists.agency');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'version') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD version varchar(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT N'1.0'
END
		"), 'add codelists.version');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'idno') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD idno varchar(191) NULL
END
		"), 'add codelists.idno');

		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'agency') IS NOT NULL
BEGIN
	UPDATE dbo.codelists SET agency = N'NADA' WHERE agency IS NULL OR agency = N''
END
		"), 'backfill codelists.agency');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'version') IS NOT NULL
BEGIN
	UPDATE dbo.codelists SET version = N'1.0' WHERE version IS NULL OR version = N''
END
		"), 'backfill codelists.version');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'idno') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'agency') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'version') IS NOT NULL
BEGIN
	UPDATE dbo.codelists
	SET idno = agency + N'_' + name + N'_' + version
	WHERE idno IS NULL OR idno = N''
END
		"), 'backfill codelists.idno');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'agency') IS NOT NULL
	ALTER TABLE dbo.codelists ALTER COLUMN agency varchar(64) NOT NULL
		"), 'codelists.agency NOT NULL');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'version') IS NOT NULL
	ALTER TABLE dbo.codelists ALTER COLUMN version varchar(32) NOT NULL
		"), 'codelists.version NOT NULL');

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.unq_codelists_name', N'UQ') IS NOT NULL
	ALTER TABLE dbo.codelists DROP CONSTRAINT unq_codelists_name
		"), 'drop constraint unq_codelists_name');
		$this->assert_db_query($this->db->query("
IF EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'unq_codelists_name' AND object_id = OBJECT_ID(N'dbo.codelists')
)
	DROP INDEX unq_codelists_name ON dbo.codelists
		"), 'drop index unq_codelists_name');

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND OBJECT_ID(N'dbo.unq_codelists_identity', N'UQ') IS NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'unq_codelists_identity' AND object_id = OBJECT_ID(N'dbo.codelists')
)
BEGIN
	ALTER TABLE dbo.codelists ADD CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version)
END
		"), 'unq_codelists_identity');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'unq_codelists_idno' AND object_id = OBJECT_ID(N'dbo.codelists')
)
BEGIN
	CREATE UNIQUE INDEX unq_codelists_idno ON dbo.codelists(idno) WHERE idno IS NOT NULL
END
		"), 'unq_codelists_idno');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'idx_codelists_agency_name' AND object_id = OBJECT_ID(N'dbo.codelists')
)
BEGIN
	CREATE INDEX idx_codelists_agency_name ON dbo.codelists(agency, name)
END
		"), 'idx_codelists_agency_name');
	}

	private function ensure_codelists_pid_versioning_mysql()
	{
		if (!$this->db->table_exists('codelists')) {
			return;
		}

		if (!$this->db->field_exists('pid', 'codelists')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD COLUMN `pid` INT(11) NULL AFTER `id`'),
				'add codelists.pid'
			);
		}
		if (!$this->db->field_exists('version_seq', 'codelists')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD COLUMN `version_seq` INT(11) NULL AFTER `version`'),
				'add codelists.version_seq'
			);
		}
		if (!$this->db->field_exists('status', 'codelists')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD COLUMN `status` SMALLINT NOT NULL DEFAULT 0 AFTER `description`'),
				'add codelists.status'
			);
		}
		if (!$this->db->field_exists('created', 'codelists')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD COLUMN `created` INT(11) NULL AFTER `status`'),
				'add codelists.created'
			);
		}
		if (!$this->db->field_exists('changed', 'codelists')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD COLUMN `changed` INT(11) NULL AFTER `created`'),
				'add codelists.changed'
			);
		}

		$this->assert_db_query($this->db->query('
			UPDATE codelists c
			SET c.version_seq = (
				SELECT COUNT(*) FROM codelists x
				WHERE x.agency = c.agency AND x.name = c.name AND x.id <= c.id
			)
			WHERE c.version_seq IS NULL OR c.version_seq <= 0
		'), 'backfill codelists.version_seq');
		$this->assert_db_query(
			$this->db->query('ALTER TABLE `codelists` MODIFY COLUMN `version_seq` INT(11) NOT NULL'),
			'codelists.version_seq NOT NULL'
		);
		$this->assert_db_query($this->db->query("
			UPDATE codelists c
			JOIN (
				SELECT agency, name, MAX(id) AS latest_id
				FROM codelists
				GROUP BY agency, name
			) latest
			  ON latest.agency = c.agency AND latest.name = c.name
			SET c.pid = latest.latest_id
			WHERE c.pid IS NULL OR c.pid <> latest.latest_id
		"), 'backfill codelists.pid');
		$this->assert_db_query(
			$this->db->query('UPDATE `codelists` SET `created` = UNIX_TIMESTAMP(), `changed` = UNIX_TIMESTAMP() WHERE `created` IS NULL'),
			'backfill codelists.created'
		);

		if (!$this->index_exists('codelists', 'unq_codelists_family_seq')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD UNIQUE KEY `unq_codelists_family_seq` (`agency`,`name`,`version_seq`)'),
				'unq_codelists_family_seq'
			);
		}
		if (!$this->index_exists('codelists', 'idx_codelists_pid')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD KEY `idx_codelists_pid` (`pid`)'),
				'idx_codelists_pid'
			);
		}
		if (!$this->mysql_fk_exists('codelists', 'fk_codelists_pid')) {
			$this->assert_db_query(
				$this->db->query('ALTER TABLE `codelists` ADD CONSTRAINT `fk_codelists_pid` FOREIGN KEY (`pid`) REFERENCES `codelists` (`id`) ON DELETE RESTRICT'),
				'fk_codelists_pid'
			);
		}
	}

	private function ensure_codelists_pid_versioning_sqlsrv()
	{
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'pid') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD pid int NULL
END
		"), 'add codelists.pid');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'version_seq') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD version_seq int NULL
END
		"), 'add codelists.version_seq');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'status') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD status smallint NOT NULL CONSTRAINT df_codelists_status DEFAULT 0
END
		"), 'add codelists.status');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'created') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD created int NULL
END
		"), 'add codelists.created');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'changed') IS NULL
BEGIN
	ALTER TABLE dbo.codelists ADD changed int NULL
END
		"), 'add codelists.changed');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'status') IS NOT NULL
BEGIN
	UPDATE dbo.codelists SET status = 0 WHERE status IS NULL
END
		"), 'backfill codelists.status');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'status') IS NOT NULL
	ALTER TABLE dbo.codelists ALTER COLUMN status smallint NOT NULL
		"), 'codelists.status NOT NULL');

		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'version_seq') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'agency') IS NOT NULL
BEGIN
	;WITH seq AS (
		SELECT id, ROW_NUMBER() OVER (PARTITION BY agency, name ORDER BY id ASC) AS rn
		FROM dbo.codelists
	)
	UPDATE c
	SET version_seq = seq.rn
	FROM dbo.codelists c
	INNER JOIN seq ON seq.id = c.id
	WHERE c.version_seq IS NULL OR c.version_seq <= 0
END
		"), 'backfill codelists.version_seq');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'version_seq') IS NOT NULL
	ALTER TABLE dbo.codelists ALTER COLUMN version_seq int NOT NULL
		"), 'codelists.version_seq NOT NULL');

		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'pid') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'agency') IS NOT NULL
BEGIN
	;WITH latest AS (
		SELECT agency, name, MAX(id) AS latest_id
		FROM dbo.codelists
		GROUP BY agency, name
	)
	UPDATE c
	SET pid = latest.latest_id
	FROM dbo.codelists c
	INNER JOIN latest
		ON latest.agency = c.agency
	   AND latest.name = c.name
	WHERE c.pid IS NULL OR c.pid <> latest.latest_id
END
		"), 'backfill codelists.pid');
		$this->assert_db_query($this->db->query("
IF COL_LENGTH(N'dbo.codelists', N'created') IS NOT NULL
BEGIN
	UPDATE dbo.codelists
	SET created = DATEDIFF(SECOND, '1970-01-01', SYSUTCDATETIME()),
	    changed = DATEDIFF(SECOND, '1970-01-01', SYSUTCDATETIME())
	WHERE created IS NULL
END
		"), 'backfill codelists.created');

		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'unq_codelists_family_seq' AND object_id = OBJECT_ID(N'dbo.codelists')
)
BEGIN
	CREATE UNIQUE INDEX unq_codelists_family_seq ON dbo.codelists(agency, name, version_seq)
END
		"), 'unq_codelists_family_seq');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND NOT EXISTS (
	SELECT 1 FROM sys.indexes
	WHERE name = N'idx_codelists_pid' AND object_id = OBJECT_ID(N'dbo.codelists')
)
BEGIN
	CREATE INDEX idx_codelists_pid ON dbo.codelists(pid)
END
		"), 'idx_codelists_pid');
		$this->assert_db_query($this->db->query("
IF OBJECT_ID(N'dbo.fk_codelists_pid', N'F') IS NULL
AND OBJECT_ID(N'dbo.codelists', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.codelists', N'pid') IS NOT NULL
BEGIN
	ALTER TABLE dbo.codelists ADD CONSTRAINT fk_codelists_pid FOREIGN KEY (pid) REFERENCES dbo.codelists(id)
END
		"), 'fk_codelists_pid');
	}

	private function mysql_fk_exists($table, $constraint_name)
	{
		$result = $this->db->query(
			'SELECT 1 FROM information_schema.table_constraints
			 WHERE table_schema = DATABASE()
			   AND table_name = ?
			   AND constraint_name = ?
			   AND constraint_type = \'FOREIGN KEY\'',
			array($table, $constraint_name)
		);
		return $result && $result->row_array();
	}
}
