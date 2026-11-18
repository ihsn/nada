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
