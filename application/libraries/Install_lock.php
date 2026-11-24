<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The install lock file (CATALOG_ROOT/.nada_installed): lets a request skip the "is the app installed" database query.
 *
 * The database stays the source of truth. The lock file records which database it was written for (driver, host,
 * database name, table prefix), so a lock file that does not belong to the configured database — a copied or reused
 * datafiles folder, or database.php pointed at a new, empty database — is ignored and the database is checked instead.
 */
class Install_lock
{
	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	/**
	 * Absolute path to the lock file, or FALSE if catalog_root is not configured.
	 *
	 * @return string|false
	 */
	public function path()
	{
		$catalog_root = $this->ci->config->item('catalog_root');
		if (empty($catalog_root))
		{
			return FALSE;
		}

		// Resolve to absolute path if relative (no leading / and no Windows drive letter)
		if ($catalog_root[0] !== '/' && strpos($catalog_root, ':') === FALSE)
		{
			$catalog_root = FCPATH . $catalog_root;
		}

		return rtrim($catalog_root, '/') . '/.nada_installed';
	}

	/**
	 * Whether the lock file exists and was written for the configured database.
	 *
	 * @return bool
	 */
	public function is_valid()
	{
		$path = $this->path();
		if (!$path || !is_file($path))
		{
			return FALSE;
		}

		$contents = @file_get_contents($path);
		return $contents !== FALSE && trim($contents) === $this->fingerprint();
	}

	/**
	 * Whether the configured database holds an installed NADA, checked in the database (not the lock file).
	 *
	 * Installed means the install marker (the configurations row app_installed) exists, or the database already has
	 * user accounts. A site with user accounts but no marker is restored by writing the marker: older lock files
	 * (or other upgrade paths) hid a missing marker, and such a site must never be sent to the installer, which would
	 * run against its live data. Database errors (no tables yet) read as "not installed"; db_debug is off meanwhile so
	 * they do not stop the request.
	 *
	 * @return bool
	 */
	public function database_is_installed()
	{
		if ($this->has_install_marker())
		{
			return TRUE;
		}

		if ( ! $this->has_user_accounts())
		{
			return FALSE;
		}

		log_message('error', 'Install check: user accounts exist but the app_installed marker is missing; restoring it.');
		if ( ! $this->_without_db_debug(function ($db) {
			return $db->insert('configurations', array('name' => 'app_installed', 'value' => date('U')));
		}))
		{
			// Could not write the marker (e.g. no INSERT permission): still an installed site, never the installer
			log_message('error', 'Install check: could not restore the app_installed marker.');
		}

		return TRUE;
	}

	/**
	 * Whether the install marker (the configurations row app_installed) exists.
	 *
	 * @return bool
	 */
	public function has_install_marker()
	{
		return $this->_without_db_debug(function ($db) {
			$query = $db->select('name')->where('name', 'app_installed')->get('configurations');
			return $query && $query->num_rows() > 0;
		});
	}

	/**
	 * Whether the database has at least one user account.
	 *
	 * @return bool
	 */
	public function has_user_accounts()
	{
		$this->ci->config->load('ion_auth', TRUE);
		$tables = $this->ci->config->item('tables', 'ion_auth');
		$users_table = ! empty($tables['users']) ? $tables['users'] : 'users';

		return $this->_without_db_debug(function ($db) use ($users_table) {
			$query = $db->select('id')->limit(1)->get($users_table);
			return $query && $query->num_rows() > 0;
		});
	}

	/**
	 * Run a query with db_debug off: a missing table (database not installed yet) reads as FALSE instead of stopping
	 * the request with a database error page.
	 */
	private function _without_db_debug($fn)
	{
		$db = $this->ci->db;
		$db_debug = $db->db_debug;
		$db->db_debug = FALSE;
		$result = (bool) $fn($db);
		$db->db_debug = $db_debug;
		return $result;
	}

	/**
	 * Write (or replace) the lock file for the configured database.
	 *
	 * @return bool
	 */
	public function write()
	{
		$path = $this->path();
		return $path && @file_put_contents($path, $this->fingerprint()) !== FALSE;
	}

	/**
	 * Identifies the configured database without storing its settings in the file.
	 *
	 * @return string
	 */
	private function fingerprint()
	{
		$db = $this->ci->db;
		return hash('sha256', implode('|', array($db->dbdriver, $db->hostname, $db->port, $db->database, $db->dbprefix)));
	}
}
