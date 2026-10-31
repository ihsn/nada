<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MongoDB setup and connection check for indicator data and data API tables.
 *
 * Prints PASS / WARN / FAIL / INFO lines to the terminal. Connects with the same
 * settings and code as NADA (Timeseries_mongo_model), then checks the user's
 * permissions by creating, indexing, renaming and dropping a temporary collection
 * (nada_check_*). Pass "read-only" to skip that write test.
 *
 * Usage:
 *   php index.php cli/mongo_check
 *   php index.php cli/mongo_check read-only
 *   php index.php cli/mongo_check help
 *
 * The command line can use a different php.ini than the web server; compare
 * with phpinfo() if this passes but the site fails.
 *
 * Exit code: 0 = no warnings or failures, 1 = warnings, 2 = failures.
 */
class Mongo_check extends CI_Controller {

	private $counts = array('PASS' => 0, 'WARN' => 0, 'FAIL' => 0, 'INFO' => 0);
	private $color = false;

	const MIN_PHP = '8.1.0';
	const MIN_EXTENSION = '2.1.0';
	const MIN_SERVER = '6.0';

	/** Roles that give more than NADA needs. */
	private $broad_roles = array(
		'root', 'dbOwner', 'userAdmin', 'userAdminAnyDatabase', 'dbAdminAnyDatabase',
		'readWriteAnyDatabase', 'clusterAdmin', 'clusterManager', 'restore', '__system',
	);

	public function __construct()
	{
		if (php_sapi_name() !== 'cli') {
			die("This controller can only be accessed from the command line.\n");
		}

		// Same as other CLI controllers: the template library expects a web request.
		$autoload_config =& get_config();
		if (isset($autoload_config['autoload']['libraries']) && is_array($autoload_config['autoload']['libraries'])) {
			$key = array_search('template', $autoload_config['autoload']['libraries']);
			if ($key !== false) {
				unset($autoload_config['autoload']['libraries'][$key]);
				$autoload_config['autoload']['libraries'] = array_values($autoload_config['autoload']['libraries']);
			}
		}

		parent::__construct();

		$this->color = function_exists('posix_isatty') && @posix_isatty(STDOUT) && getenv('NO_COLOR') === false;
	}

	public function _remap($method, $params = array())
	{
		if ($method === 'help') {
			$this->usage();
			return;
		}
		$this->run($method !== 'read-only' && $method !== 'read_only');
	}

	private function usage()
	{
		echo "NADA MongoDB check\n\n";
		echo "  php index.php cli/mongo_check              all checks, including a write test\n";
		echo "  php index.php cli/mongo_check read-only    skip the write test\n\n";
		echo "The write test creates, indexes, renames and drops a temporary nada_check_* collection.\n";
		echo "Exit code: 0 = all clear, 1 = warnings, 2 = failures.\n";
	}

	private function run($write_test)
	{
		echo $this->paint('NADA MongoDB check', 'bold'), '  ', date('Y-m-d H:i'), "\n";
		echo 'Install: ', rtrim(FCPATH, '/\\'), "\n";
		echo 'PHP ', PHP_VERSION, ' (', php_ini_loaded_file() ?: 'no php.ini', ")\n";

		if ($this->check_setup()) {
			$client = $this->check_connection();
			if ($client !== null) {
				$this->check_permissions($client, $write_test);
				$this->check_data($client);
			}
		}

		$this->summary();
	}

	// -------------------------------------------------------------------------
	// PHP, extension, library, config file
	// -------------------------------------------------------------------------

	/**
	 * @return bool true when NADA can try to connect
	 */
	private function check_setup()
	{
		$this->section('Setup');
		$ok = true;

		if (version_compare(PHP_VERSION, self::MIN_PHP, '>=')) {
			$this->result('PASS', 'M1', 'PHP ' . PHP_VERSION);
		} else {
			$this->result('FAIL', 'M1', 'PHP ' . PHP_VERSION . ' is too old for MongoDB support', 'Upgrade PHP to ' . self::MIN_PHP . ' or later.');
			$ok = false;
		}

		$ext = phpversion('mongodb');
		if ($ext === false) {
			$this->result('FAIL', 'M2', 'The mongodb PHP extension is not loaded', 'Install it (pecl install mongodb) and add extension=mongodb to php.ini.');
			$ok = false;
		} elseif (version_compare($ext, self::MIN_EXTENSION, '<')) {
			$this->result('FAIL', 'M2', 'mongodb extension ' . $ext . ' is too old', 'Install version ' . self::MIN_EXTENSION . ' or later.');
			$ok = false;
		} else {
			$this->result('PASS', 'M2', 'mongodb extension ' . $ext);
		}

		$autoload = FCPATH . 'modules/mongodb/vendor/autoload.php';
		if (is_file($autoload)) {
			$this->result('PASS', 'M3', 'MongoDB library found in modules/mongodb');
		} else {
			$this->result('FAIL', 'M3', 'modules/mongodb/vendor/autoload.php is missing', 'Copy the modules/mongodb folder from the NADA release.');
			$ok = false;
		}

		$config_file = APPPATH . 'config/mongo.php';
		if (!is_file($config_file)) {
			$this->result('FAIL', 'M4', 'application/config/mongo.php not found', 'Copy application/config/mongo.sample.php to mongo.php and edit it.');
			return false;
		}

		$this->config->load('mongo', FALSE, TRUE);
		$db       = (string) $this->config->item('mongodb_database');
		$host     = (string) $this->config->item('mongodb_host');
		$port     = (string) $this->config->item('mongodb_port');
		$username = (string) $this->config->item('mongodb_username');
		$password = (string) $this->config->item('mongodb_password');

		if ($db === '' || $host === '') {
			$this->result('FAIL', 'M4', 'mongodb_database or mongodb_host is empty in mongo.php', 'Set both in application/config/mongo.php.');
			$ok = false;
		} else {
			$this->result('PASS', 'M4', 'mongo.php: database "' . $db . '" on ' . $host . ':' . $port);
		}

		if ($username !== '' && $password !== '') {
			$this->result('PASS', 'M5', 'Authenticates as "' . $username . '" (user must exist in database "' . $db . '")');
		} elseif ($username !== '' || $password !== '') {
			$this->result('WARN', 'M5', 'Only one of mongodb_username / mongodb_password is set, so NADA connects without authentication', 'Set both, or leave both empty.');
		} else {
			$this->result('WARN', 'M5', 'No authentication configured', 'Recommended for production: create a readWrite user in "' . ($db !== '' ? $db : 'nada_db') . '" and set it in mongo.php.');
		}

		if (DIRECTORY_SEPARATOR === '/' && $password !== '') {
			$perms = fileperms($config_file) & 0777;
			if ($perms & 0004) {
				$this->result('WARN', 'M6', sprintf('mongo.php is readable by all users (%o) and contains the password', $perms), 'chmod 640 application/config/mongo.php (owner and web server group only).');
			} else {
				$this->result('PASS', 'M6', sprintf('mongo.php is not world-readable (%o)', $perms));
			}
		}

		return $ok;
	}

	// -------------------------------------------------------------------------
	// Connection
	// -------------------------------------------------------------------------

	private function check_connection()
	{
		$this->section('Connection');

		try {
			$this->load->model('Timeseries_mongo_model');
			$client = $this->Timeseries_mongo_model->get_db_connection();
			$db = $this->Timeseries_mongo_model->get_mongo_db_name();
			$client->selectDatabase($db)->command(array('ping' => 1));
			$this->result('PASS', 'M7', 'Connected and authenticated (ping ok)');
		} catch (Exception $e) {
			$this->result('FAIL', 'M7', 'Connection failed: ' . $e->getMessage(), $this->connection_hint($e->getMessage()));
			return null;
		}

		try {
			$info = $client->selectDatabase($db)->command(array('buildInfo' => 1))->toArray()[0];
			$version = (string) $info['version'];
			if (version_compare($version, self::MIN_SERVER, '>=')) {
				$this->result('PASS', 'M8', 'MongoDB server ' . $version);
			} else {
				$this->result('WARN', 'M8', 'MongoDB server ' . $version . ' is older than ' . self::MIN_SERVER, 'Upgrade to a currently supported MongoDB release.');
			}
		} catch (Exception $e) {
			$this->result('INFO', 'M8', 'Could not read the server version: ' . $e->getMessage());
		}

		return $client;
	}

	private function connection_hint($message)
	{
		if (stripos($message, 'Authentication failed') !== false) {
			return 'Check the username and password. The user must be created in mongodb_database, not in admin.';
		}
		if (stripos($message, 'No suitable servers') !== false || stripos($message, 'Connection refused') !== false) {
			return 'Check that mongod is running and that mongodb_host / mongodb_port are correct.';
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// Permissions
	// -------------------------------------------------------------------------

	private function check_permissions($client, $write_test)
	{
		$this->section('Permissions');
		$db_name = $this->Timeseries_mongo_model->get_mongo_db_name();
		$db = $client->selectDatabase($db_name);

		try {
			$status = $db->command(array('connectionStatus' => 1))->toArray()[0];
			$roles = array();
			foreach ($status['authInfo']['authenticatedUserRoles'] as $role) {
				$roles[] = $role['role'] . '@' . $role['db'];
			}
			if (empty($roles)) {
				$this->result('INFO', 'M9', 'Not authenticated; the server does not require a login');
			} else {
				$broad = array();
				foreach ($status['authInfo']['authenticatedUserRoles'] as $role) {
					if (in_array($role['role'], $this->broad_roles, true) || $role['db'] !== $db_name) {
						$broad[] = $role['role'] . '@' . $role['db'];
					}
				}
				if ($broad) {
					$this->result('WARN', 'M9', 'User has more access than NADA needs: ' . implode(', ', $broad), 'Give the NADA user only readWrite on "' . $db_name . '".');
				} else {
					$this->result('PASS', 'M9', 'Roles: ' . implode(', ', $roles));
				}
			}
		} catch (Exception $e) {
			$this->result('INFO', 'M9', 'Could not read the user roles: ' . $e->getMessage());
		}

		try {
			$db->command(array('listCollections' => 1, 'nameOnly' => true));
			$this->result('PASS', 'M10', 'Can list collections in "' . $db_name . '"');
		} catch (Exception $e) {
			$this->result('FAIL', 'M10', 'Cannot list collections: ' . $e->getMessage(), 'Give the NADA user the readWrite role on "' . $db_name . '".');
			return;
		}

		if (!$write_test) {
			$this->result('INFO', 'M11', 'Write test skipped (read-only)');
			return;
		}

		$name = 'nada_check_' . bin2hex(random_bytes(4));
		$renamed = $name . '_renamed';
		$step = 'insert';
		try {
			$db->selectCollection($name)->insertOne(array('check' => true, 'at' => date('c')));
			$step = 'create index';
			$db->selectCollection($name)->createIndex(array('check' => 1));
			$step = 'rename';
			$client->selectDatabase('admin')->command(array(
				'renameCollection' => $db_name . '.' . $name,
				'to' => $db_name . '.' . $renamed,
			));
			$step = 'drop';
			$db->selectCollection($renamed)->drop();
			$this->result('PASS', 'M11', 'Write test passed (insert, index, rename, drop)');
		} catch (Exception $e) {
			$this->result('FAIL', 'M11', 'Write test failed at "' . $step . '": ' . $e->getMessage(), 'Give the NADA user the readWrite role on "' . $db_name . '".');
			foreach (array($name, $renamed) as $leftover) {
				try {
					$db->selectCollection($leftover)->drop();
				} catch (Exception $ignored) {
				}
			}
		}
	}

	// -------------------------------------------------------------------------
	// What is stored
	// -------------------------------------------------------------------------

	private function check_data($client)
	{
		$this->section('Data');
		$db_name = $this->Timeseries_mongo_model->get_mongo_db_name();
		$db = $client->selectDatabase($db_name);

		$prefix = (string) $this->config->item('indicator_timeseries_collection_prefix');
		if ($prefix === '') {
			$prefix = 'indicator_ts_';
		}

		try {
			$names = array();
			foreach ($db->listCollectionNames() as $collection_name) {
				$names[] = $collection_name;
			}
		} catch (Exception $e) {
			$this->result('INFO', 'M12', 'Could not list collections: ' . $e->getMessage());
			return;
		}

		$indicator_collections = 0;
		$observations = 0;
		$tables = 0;
		foreach ($names as $collection_name) {
			if (strpos($collection_name, $prefix) === 0) {
				$indicator_collections++;
				$observations += $db->selectCollection($collection_name)->estimatedDocumentCount();
			} elseif (strpos($collection_name, 'table_') === 0 && !in_array($collection_name, array('table_dictionary', 'table_types'), true)) {
				$tables++;
			}
		}

		if (empty($names)) {
			$this->result('INFO', 'M12', 'Database "' . $db_name . '" is empty; NADA creates collections on the first upload');
		} else {
			$this->result('INFO', 'M12', $indicator_collections . ' indicator collection(s) with ' . number_format($observations) . ' observation(s); ' . $tables . ' data API table(s)');
		}

		if (in_array('table_dictionary', $names, true)) {
			$index_names = array();
			foreach ($db->selectCollection('table_dictionary')->listIndexes() as $index) {
				$index_names[] = $index->getName();
			}
			if (in_array('idx_table_field_name', $index_names, true)) {
				$this->result('PASS', 'M13', 'table_dictionary indexes exist');
			} else {
				$this->result('WARN', 'M13', 'table_dictionary has no NADA indexes', 'POST /api/tables/create_dictionary_indexes once with an admin API key.');
			}
		}
	}

	// -------------------------------------------------------------------------
	// Output
	// -------------------------------------------------------------------------

	private function summary()
	{
		echo "\n", $this->paint('Summary', 'bold'), ': ';
		echo $this->counts['PASS'], ' passed, ', $this->counts['WARN'], ' warnings, ', $this->counts['FAIL'], " failed\n";
		echo "Note: the web server may use a different php.ini than the command line.\n";

		if ($this->counts['FAIL'] > 0) {
			exit(2);
		}
		if ($this->counts['WARN'] > 0) {
			exit(1);
		}
	}

	private function section($title)
	{
		echo "\n", $this->paint($title, 'bold'), "\n";
	}

	private function result($status, $id, $message, $fix = null)
	{
		$this->counts[$status]++;
		$colors = array('PASS' => 'green', 'WARN' => 'yellow', 'FAIL' => 'red', 'INFO' => 'cyan');
		echo '  ', $this->paint('[' . $status . ']', $colors[$status]), ' ', str_pad($id, 6), ' ', $message, "\n";
		if ($fix !== null && $status !== 'PASS') {
			echo str_repeat(' ', 16), 'Fix: ', $fix, "\n";
		}
	}

	private function paint($text, $style)
	{
		if (!$this->color) {
			return $text;
		}
		$codes = array('bold' => '1', 'green' => '32', 'yellow' => '33', 'red' => '31', 'cyan' => '36');
		return "\033[" . $codes[$style] . 'm' . $text . "\033[0m";
	}
}
