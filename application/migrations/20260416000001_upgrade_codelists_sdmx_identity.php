<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: Upgrade codelists table with SDMX-style identity (agency, version, idno)
 *
 * Changes to the `codelists` table:
 *   - Adds `agency`  VARCHAR(64)  NOT NULL DEFAULT 'NADA'
 *   - Adds `version` VARCHAR(32)  NOT NULL DEFAULT '1.0'
 *   - Adds `idno`    VARCHAR(191) NULL          (compact SDMX-style identifier)
 *   - Drops the old `unq_codelists_name` (name-only) uniqueness
 *   - Adds `unq_codelists_identity` UNIQUE (agency, name, version)
 *   - Adds `unq_codelists_idno`     UNIQUE (idno)   (ignores NULLs on MySQL; filtered on SQL Server)
 *   - Adds `idx_codelists_agency_name` on (agency, name)
 *
 * Backfill for existing rows:
 *   - agency  -> 'NADA'
 *   - version -> '1.0'
 *   - idno    -> '{agency}_{name}_{version}'   (deterministic, URL-safe)
 *
 * The (agency, name, version) triple is the source-of-truth identity.
 * `idno` is a single-string alias (unique when set) so callers can look up a
 * codelist by one value instead of the triple.
 */
class Migration_Upgrade_codelists_sdmx_identity extends MY_Migration {

	const DEFAULT_AGENCY  = 'NADA';
	const DEFAULT_VERSION = '1.0';

	public function up()
	{
		log_message('info', 'Migration_Upgrade_codelists_sdmx_identity::up() called');

		if (!$this->db->table_exists('codelists')) {
			log_message('info', 'codelists table not present; skipping');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Upgrade_codelists_sdmx_identity: unsupported driver ' . $driver . ', skipping');
			return;
		}

		$this->backfill_rows();

		log_message('info', 'Migration_Upgrade_codelists_sdmx_identity completed successfully');
	}

	// =========================================================================
	// MySQL
	// =========================================================================

	private function up_mysql()
	{
		if (!$this->mysql_column_exists('codelists', 'agency')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `agency` VARCHAR(64) NOT NULL DEFAULT '" . self::DEFAULT_AGENCY . "' AFTER `name`
			");
			log_message('info', 'Added codelists.agency');
		}

		if (!$this->mysql_column_exists('codelists', 'version')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `version` VARCHAR(32) NOT NULL DEFAULT '" . self::DEFAULT_VERSION . "' AFTER `agency`
			");
			log_message('info', 'Added codelists.version');
		}

		if (!$this->mysql_column_exists('codelists', 'idno')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD COLUMN `idno` VARCHAR(191) DEFAULT NULL AFTER `version`
			");
			log_message('info', 'Added codelists.idno');
		}

		if ($this->mysql_index_exists('codelists', 'unq_codelists_name')) {
			$this->db->query("ALTER TABLE `codelists` DROP INDEX `unq_codelists_name`");
			log_message('info', 'Dropped old unq_codelists_name');
		}

		if (!$this->mysql_index_exists('codelists', 'unq_codelists_identity')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD UNIQUE KEY `unq_codelists_identity` (`agency`, `name`, `version`)
			");
			log_message('info', 'Added unq_codelists_identity');
		}

		if (!$this->mysql_index_exists('codelists', 'unq_codelists_idno')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD UNIQUE KEY `unq_codelists_idno` (`idno`)
			");
			log_message('info', 'Added unq_codelists_idno');
		}

		if (!$this->mysql_index_exists('codelists', 'idx_codelists_agency_name')) {
			$this->db->query("
				ALTER TABLE `codelists`
				ADD KEY `idx_codelists_agency_name` (`agency`, `name`)
			");
			log_message('info', 'Added idx_codelists_agency_name');
		}
	}

	private function mysql_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.columns
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND column_name  = ?',
			[$table, $column]
		);
		return $_r && $_r->row_array();
	}

	private function mysql_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.statistics
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND index_name   = ?',
			[$table, $index_name]
		);
		return $_r && $_r->row_array();
	}

	// =========================================================================
	// SQL Server
	// =========================================================================

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('codelists', 'agency')) {
			$this->db->query(
				"ALTER TABLE codelists ADD agency VARCHAR(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT '" . self::DEFAULT_AGENCY . "'"
			);
			log_message('info', 'Added codelists.agency');
		}

		if (!$this->sqlsrv_column_exists('codelists', 'version')) {
			$this->db->query(
				"ALTER TABLE codelists ADD version VARCHAR(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT '" . self::DEFAULT_VERSION . "'"
			);
			log_message('info', 'Added codelists.version');
		}

		if (!$this->sqlsrv_column_exists('codelists', 'idno')) {
			$this->db->query("ALTER TABLE codelists ADD idno VARCHAR(191) NULL");
			log_message('info', 'Added codelists.idno');
		}

		if ($this->sqlsrv_constraint_exists('unq_codelists_name')) {
			$this->db->query("ALTER TABLE codelists DROP CONSTRAINT unq_codelists_name");
			log_message('info', 'Dropped old unq_codelists_name');
		}

		if (!$this->sqlsrv_index_exists('codelists', 'unq_codelists_identity')) {
			$this->db->query(
				"ALTER TABLE codelists ADD CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version)"
			);
			log_message('info', 'Added unq_codelists_identity');
		}

		// Filtered unique index so multiple NULL idno values are allowed on SQL Server.
		if (!$this->sqlsrv_index_exists('codelists', 'unq_codelists_idno')) {
			$this->db->query(
				"CREATE UNIQUE INDEX unq_codelists_idno ON codelists(idno) WHERE idno IS NOT NULL"
			);
			log_message('info', 'Added filtered unique index unq_codelists_idno');
		}

		if (!$this->sqlsrv_index_exists('codelists', 'idx_codelists_agency_name')) {
			$this->db->query("CREATE INDEX idx_codelists_agency_name ON codelists(agency, name)");
			log_message('info', 'Added idx_codelists_agency_name');
		}
	}

	private function sqlsrv_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?',
			[$table, $column]
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
			[$index_name, $table]
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_constraint_exists($constraint_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.key_constraints WHERE name = ?',
			[$constraint_name]
		);
		return $_r && $_r->row_array();
	}

	// =========================================================================
	// Backfill
	// =========================================================================

	/**
	 * Ensure agency/version/idno are populated for every existing row.
	 * idno is deterministically generated as '{agency}:{name}({version})'.
	 */
	private function backfill_rows()
	{
		// Agency
		$this->db->where("agency IS NULL OR agency = ''", null, false)
			->update('codelists', ['agency' => self::DEFAULT_AGENCY]);

		// Version
		$this->db->where("version IS NULL OR version = ''", null, false)
			->update('codelists', ['version' => self::DEFAULT_VERSION]);

		// idno (auto-generate where missing)
		$_r = $this->db->query("SELECT id, name, agency, version FROM codelists WHERE idno IS NULL OR idno = ''");
		$rows = $_r ? $_r->result_array() : [];
		foreach ($rows as $row) {
			$idno = self::make_idno($row['agency'], $row['name'], $row['version']);
			$this->db->where('id', (int) $row['id'])->update('codelists', ['idno' => $idno]);
		}

		log_message('info', 'Backfilled agency/version/idno for ' . count($rows) . ' codelist row(s)');
	}

	/**
	 * Deterministic idno builder. Format: '{agency}_{name}_{version}'.
	 * URL-safe and readable. Callers may override by supplying their own idno
	 * when creating a codelist.
	 */
	public static function make_idno($agency, $name, $version)
	{
		$agency  = trim((string) $agency)  !== '' ? trim((string) $agency)  : self::DEFAULT_AGENCY;
		$version = trim((string) $version) !== '' ? trim((string) $version) : self::DEFAULT_VERSION;
		$name    = trim((string) $name);
		return $agency . '_' . $name . '_' . $version;
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
