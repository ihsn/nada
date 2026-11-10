<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared database migration helpers for admin UI, admin API, and CLI.
 *
 * Watermark model: the migrations table stores a single version; all files
 * with version <= watermark are treated as applied.
 */
class Migration_manager {

	/** @var CI_Controller */
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->database();
		$this->ci->load->config('migration');
	}

	/**
	 * @return bool
	 */
	public function is_enabled()
	{
		return $this->ci->config->item('migration_enabled') === TRUE;
	}

	/**
	 * @return bool
	 */
	public function is_db_debug_enabled()
	{
		return $this->ci->db->db_debug === TRUE;
	}

	/**
	 * Disable db_debug for this request if it was on.
	 *
	 * @return bool TRUE if it was enabled and got disabled
	 */
	public function ensure_db_debug_disabled()
	{
		if ($this->ci->db->db_debug === TRUE) {
			log_message('info', 'Database debug mode was enabled, disabling for migration session');
			$this->ci->db->db_debug = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function is_valid_version($version)
	{
		return (bool) preg_match('/^\d{14}$/', (string) $version);
	}

	/**
	 * Ensure migrations table exists and has at least one row (version 0).
	 */
	public function ensure_initialized()
	{
		if (! $this->ci->db->table_exists('migrations')) {
			$this->ci->load->dbforge();
			$this->ci->dbforge->add_field(array(
				'version' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE),
			));
			$this->ci->dbforge->add_key('version', TRUE);
			$this->ci->dbforge->create_table('migrations', TRUE);
		}

		$query = $this->ci->db->get('migrations');
		if ($query->num_rows() == 0) {
			$this->ci->db->insert('migrations', array('version' => 0));
		}
	}

	/**
	 * Current watermark as string.
	 *
	 * @return string
	 */
	public function get_current_version()
	{
		if (! $this->ci->db->table_exists('migrations')) {
			return '0';
		}

		$this->ci->db->select('version');
		$this->ci->db->order_by('version', 'DESC');
		$this->ci->db->limit(1);
		$row = $this->ci->db->get('migrations')->row();

		return $row ? (string) $row->version : '0';
	}

	/**
	 * Active migration files (not archived/), sorted ascending.
	 *
	 * @return array  [{version, name, file}, ...]
	 */
	public function get_available_migrations()
	{
		$migrations = array();
		$path = APPPATH . 'migrations/';

		if (! is_dir($path)) {
			return $migrations;
		}

		foreach (scandir($path) as $file) {
			if (! preg_match('/^(\d{14})_(.+)\.php$/', $file, $matches)) {
				continue;
			}
			$migrations[] = array(
				'version' => $matches[1],
				'name'    => ucwords(str_replace('_', ' ', $matches[2])),
				'file'    => $file,
			);
		}

		usort($migrations, function ($a, $b) {
			return strcmp($a['version'], $b['version']);
		});

		return $migrations;
	}

	/**
	 * @param  string $version
	 * @return bool
	 */
	public function migration_version_exists($version)
	{
		foreach ($this->get_available_migrations() as $migration) {
			if ($migration['version'] === (string) $version) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * First pending version after the watermark, or null.
	 *
	 * @return string|null
	 */
	public function get_next_pending_version()
	{
		$current = $this->get_current_version();
		foreach ($this->get_available_migrations() as $migration) {
			if ((int) $migration['version'] > (int) $current) {
				return $migration['version'];
			}
		}
		return NULL;
	}

	/**
	 * Version immediately before $version in the file list, or "0".
	 *
	 * @param  string $version
	 * @return string
	 */
	public function get_previous_version($version)
	{
		$versions = array();
		foreach ($this->get_available_migrations() as $migration) {
			$versions[] = $migration['version'];
		}

		$index = array_search((string) $version, $versions, TRUE);
		if ($index === FALSE || $index === 0) {
			return '0';
		}

		return $versions[$index - 1];
	}

	/**
	 * Status payload for API / Vue.
	 *
	 * @return array
	 */
	public function get_status()
	{
		$current = $this->get_current_version();
		$next = $this->get_next_pending_version();
		$list = array();

		foreach ($this->get_available_migrations() as $migration) {
			$v = $migration['version'];
			if ((int) $v > (int) $current) {
				$status = 'pending';
			} elseif ($v === $current) {
				$status = 'current';
			} else {
				$status = 'applied';
			}

			$list[] = array(
				'version' => $v,
				'name'    => $migration['name'],
				'file'    => $migration['file'],
				'status'  => $status,
			);
		}

		$pending = 0;
		foreach ($list as $row) {
			if ($row['status'] === 'pending') {
				$pending++;
			}
		}

		return array(
			'migration_enabled'    => $this->is_enabled(),
			'db_debug_enabled'     => $this->is_db_debug_enabled(),
			'current_version'      => $current,
			'next_pending_version' => $next,
			'pending_count'        => $pending,
			'migrations'           => $list,
		);
	}

	/**
	 * Run one migration version (or "latest" = next pending only).
	 *
	 * @param  string $version
	 * @return array
	 */
	public function run($version)
	{
		$version = (string) $version;

		if (! $this->is_enabled()) {
			return $this->_fail('Migrations are disabled. Enable in application/config/migration.php');
		}

		$db_debug_was_enabled = $this->ensure_db_debug_disabled();
		$migration_output = '';
		$before_version = '';
		$after_version = '';
		$version_to_run = $version;

		try {
			ob_start();
			$this->ci->load->library('migration');
			$this->ensure_initialized();

			$before_version = $this->get_current_version();
			$next_version = $this->get_next_pending_version();

			if ($version !== 'latest' && $next_version !== NULL && (string) $version !== (string) $next_version) {
				if (ob_get_level() > 0) {
					ob_end_clean();
				}
				return $this->_fail(
					'Run ' . $next_version . ' first. Running ' . $version
					. ' would also execute every earlier pending migration in this same request.',
					array(
						'before_version'       => $before_version,
						'after_version'        => $before_version,
						'version'              => $version,
						'version_run'          => NULL,
						'db_debug_was_enabled' => $db_debug_was_enabled,
					)
				);
			}

			if ($version === 'latest') {
				if ($next_version === NULL) {
					if (ob_get_level() > 0) {
						ob_end_clean();
					}
					return array(
						'type'                 => 'success',
						'msg'                  => 'Database is already at the latest migration version.',
						'success'              => TRUE,
						'already_latest'       => TRUE,
						'version'              => 'latest',
						'version_run'          => NULL,
						'output'               => '',
						'before_version'       => $before_version,
						'after_version'        => $before_version,
						'next_pending_version' => NULL,
						'db_debug_was_enabled' => $db_debug_was_enabled,
					);
				}
				$version_to_run = $next_version;
			} elseif (! $this->is_valid_version($version) || ! $this->migration_version_exists($version)) {
				if (ob_get_level() > 0) {
					ob_end_clean();
				}
				return $this->_fail('Invalid or unknown migration version: ' . $version);
			}

			$result = $this->ci->migration->version($version_to_run);
			$after_version = $this->get_current_version();

			if (ob_get_level() > 0) {
				$migration_output = ob_get_clean();
			}

			if ($result === FALSE) {
				return array(
					'type'                 => 'error',
					'msg'                  => 'Migration failed: ' . $this->ci->migration->error_string(),
					'success'              => FALSE,
					'version'              => $version,
					'version_run'          => $version_to_run,
					'output'               => $migration_output,
					'before_version'       => $before_version,
					'after_version'        => $after_version,
					'next_pending_version' => $this->get_next_pending_version(),
					'db_debug_was_enabled' => $db_debug_was_enabled,
				);
			}

			return array(
				'type'                 => 'success',
				'msg'                  => 'Migration completed successfully.',
				'success'              => TRUE,
				'already_latest'       => FALSE,
				'version'              => $version,
				'version_run'          => $version_to_run,
				'output'               => $migration_output,
				'before_version'       => $before_version,
				'after_version'        => $after_version,
				'next_pending_version' => $this->get_next_pending_version(),
				'db_debug_was_enabled' => $db_debug_was_enabled,
			);
		}
		catch (Exception $e) {
			if (ob_get_level() > 0) {
				$migration_output = ob_get_clean();
			}
			return array(
				'type'                 => 'error',
				'msg'                  => 'Migration error: ' . $e->getMessage(),
				'success'              => FALSE,
				'version'              => $version,
				'version_run'          => isset($version_to_run) ? $version_to_run : $version,
				'output'               => $migration_output,
				'before_version'       => $before_version,
				'after_version'        => $this->get_current_version(),
				'next_pending_version' => $this->get_next_pending_version(),
				'db_debug_was_enabled' => $db_debug_was_enabled,
			);
		}
	}

	/**
	 * Mark watermark through $version without running SQL.
	 *
	 * @param  string $version
	 * @return array
	 */
	public function mark_applied($version)
	{
		$version = (string) $version;

		if (! $this->is_valid_version($version)) {
			return $this->_fail('Invalid version format. Expected 14-digit timestamp (e.g., 20251022000001)');
		}
		if (! $this->migration_version_exists($version)) {
			return $this->_fail('Migration version not found: ' . $version);
		}

		$current = $this->get_current_version();
		if ((int) $version <= (int) $current) {
			return $this->_fail('Version ' . $version . ' is already marked as applied (current: ' . $current . ')');
		}

		return $this->change_stored_version('mark_applied', $version, $version);
	}

	/**
	 * Lower watermark so $version and later become pending again.
	 *
	 * @param  string $version
	 * @return array
	 */
	public function unmark($version)
	{
		$version = (string) $version;

		if (! $this->is_valid_version($version)) {
			return $this->_fail('Invalid version format. Expected 14-digit timestamp (e.g., 20251022000001)');
		}
		if (! $this->migration_version_exists($version)) {
			return $this->_fail('Migration version not found: ' . $version);
		}

		$current = $this->get_current_version();
		if ((int) $version > (int) $current) {
			return $this->_fail('Version ' . $version . ' is not marked as applied (current: ' . $current . ')');
		}

		$previous = $this->get_previous_version($version);
		return $this->change_stored_version('unmark', $version, $previous);
	}

	/**
	 * Directly set watermark (advanced / CLI parity).
	 *
	 * @param  string $version
	 * @return array
	 */
	public function set_version($version)
	{
		$version = (string) $version;

		if (! $this->is_valid_version($version)) {
			return $this->_fail('Invalid version format. Expected 14-digit timestamp (e.g., 20251022000001)');
		}

		return $this->change_stored_version('set_version', $version, $version);
	}

	/**
	 * @param  string $action
	 * @param  string $target_version
	 * @param  string $new_version
	 * @return array
	 */
	public function change_stored_version($action, $target_version, $new_version)
	{
		try {
			$this->ensure_initialized();
			$before = $this->get_current_version();

			$this->ci->db->truncate('migrations');
			$result = $this->ci->db->insert('migrations', array('version' => $new_version));

			if (! $result) {
				return $this->_fail('Failed to update migration version');
			}

			$this->log_version_change($action, $target_version, $before, (string) $new_version);

			if ($action === 'mark_applied') {
				$msg = 'Marked migrations as applied through version ' . $target_version
					. ' (previous: ' . $before . ', now: ' . $new_version . ')';
			} elseif ($action === 'unmark') {
				$msg = 'Unmarked from version ' . $target_version
					. '. Migrations after ' . ($new_version === '0' ? 'none' : $new_version)
					. ' are pending again (previous: ' . $before . ', now: ' . $new_version . ')';
			} else {
				$msg = 'Migration version set to ' . $new_version . ' (previous: ' . $before . ')';
			}

			return array(
				'type'            => 'success',
				'msg'             => $msg,
				'before_version'  => $before,
				'after_version'   => (string) $new_version,
				'target_version'  => (string) $target_version,
				'current_version' => (string) $new_version,
			);
		}
		catch (Exception $e) {
			return $this->_fail('Error updating migration version: ' . $e->getMessage());
		}
	}

	/**
	 * @param  string $action
	 * @param  string $target_version
	 * @param  string $before_version
	 * @param  string $after_version
	 */
	public function log_version_change($action, $target_version, $before_version, $after_version)
	{
		$user = $this->ci->ion_auth->current_user();
		$user_label = $user ? ($user->email ? $user->email : 'user#' . $user->id) : 'unknown';

		log_message(
			'info',
			'Database migration version ' . $action
			. ' by ' . $user_label
			. ' target=' . $target_version
			. ' before=' . $before_version
			. ' after=' . $after_version
		);
	}

	/**
	 * @param  string $msg
	 * @param  array  $extra
	 * @return array
	 */
	protected function _fail($msg, array $extra = array())
	{
		return array_merge(array(
			'type'    => 'error',
			'msg'     => $msg,
			'success' => FALSE,
		), $extra);
	}
}
