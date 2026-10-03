<?php
/**
 * Test Microsoft SQL Server connectivity from the command line.
 *
 * Reads connection settings from application/config/database.php and connects
 * with the PHP sqlsrv extension directly (no CodeIgniter bootstrap).
 *
 * Usage:
 *   php install/test_sqlsrv_connection.php
 *   php install/test_sqlsrv_connection.php default
 */

if (php_sapi_name() !== 'cli') {
	fwrite(STDERR, "This script must be run from the command line.\n");
	exit(1);
}

if (!extension_loaded('sqlsrv')) {
	fwrite(STDERR, "The PHP sqlsrv extension is not loaded.\n");
	fwrite(STDERR, "Install Microsoft ODBC Driver for SQL Server and enable the sqlsrv extension.\n");
	exit(1);
}

$config_file = __DIR__ . '/../application/config/database.php';
if (!is_file($config_file)) {
	fwrite(STDERR, "Config file not found: {$config_file}\n");
	exit(1);
}

define('BASEPATH', __DIR__ . '/../system/');
require $config_file;

$group = isset($argv[1]) ? $argv[1] : $active_group;
if (!isset($db[$group]) || !is_array($db[$group])) {
	fwrite(STDERR, "Connection group not found in database.php: {$group}\n");
	exit(1);
}

$cfg = $db[$group];
$driver = isset($cfg['dbdriver']) ? strtolower((string) $cfg['dbdriver']) : '';
if ($driver !== '' && $driver !== 'sqlsrv') {
	fwrite(STDERR, "Warning: dbdriver is '{$cfg['dbdriver']}' (expected 'sqlsrv'). Continuing anyway.\n");
}

$hostname = isset($cfg['hostname']) ? trim((string) $cfg['hostname']) : '';
$username = isset($cfg['username']) ? (string) $cfg['username'] : '';
$password = isset($cfg['password']) ? (string) $cfg['password'] : '';
$database = isset($cfg['database']) ? trim((string) $cfg['database']) : '';

if ($hostname === '') {
	fwrite(STDERR, "hostname is empty in database.php (group: {$group}).\n");
	exit(1);
}

echo "SQL Server connection test\n";
echo str_repeat('-', 60) . "\n";
echo "Config file : {$config_file}\n";
echo "Group       : {$group}\n";
echo "Hostname    : {$hostname}\n";
echo "Database    : " . ($database !== '' ? $database : '(not set)') . "\n";
echo "Username    : " . ($username !== '' ? $username : '(Windows authentication)') . "\n";
echo "Driver      : " . ($driver !== '' ? $driver : '(not set)') . "\n";
echo str_repeat('-', 60) . "\n";

$connection_info = build_sqlsrv_connection_info($cfg);
$conn = sqlsrv_connect($hostname, $connection_info);

if ($conn === false) {
	fwrite(STDERR, "Connection failed.\n");
	print_sqlsrv_errors(STDERR);
	exit(1);
}

echo "Connected.\n";

$version_row = run_scalar_query($conn, 'SELECT @@VERSION AS server_version');
if ($version_row !== null) {
	echo "Server      : {$version_row}\n";
}

$db_row = run_scalar_query($conn, 'SELECT DB_NAME() AS current_database');
if ($db_row !== null) {
	echo "Current DB  : {$db_row}\n";
}

$migration_row = run_scalar_query(
	$conn,
	"IF OBJECT_ID('migrations', 'U') IS NOT NULL
		SELECT TOP 1 CAST(version AS varchar(32)) AS migration_version
		FROM migrations ORDER BY version DESC
	ELSE
		SELECT NULL AS migration_version"
);
if ($migration_row !== null && $migration_row !== '') {
	echo "Migrations  : {$migration_row}\n";
} else {
	echo "Migrations  : (table missing or empty)\n";
}

sqlsrv_close($conn);
echo "OK\n";
exit(0);

/**
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function build_sqlsrv_connection_info(array $cfg)
{
	$char_set = isset($cfg['char_set']) ? strtolower((string) $cfg['char_set']) : 'utf8';
	$charset = in_array($char_set, array('utf-8', 'utf8'), true) ? 'UTF-8' : SQLSRV_ENC_CHAR;

	$info = array(
		'Database' => isset($cfg['database']) ? (string) $cfg['database'] : '',
		'ConnectionPooling' => config_bool($cfg, 'pconnect', false) ? 1 : 0,
		'CharacterSet' => $charset,
		'Encrypt' => config_bool($cfg, 'encrypt', false) ? 1 : 0,
		'ReturnDatesAsStrings' => 1,
	);

	$username = isset($cfg['username']) ? (string) $cfg['username'] : '';
	$password = isset($cfg['password']) ? (string) $cfg['password'] : '';

	if ($username !== '' || $password !== '') {
		$info['UID'] = $username;
		$info['PWD'] = $password;
	}

	return $info;
}

/**
 * @param array<string,mixed> $cfg
 * @param string $key
 * @param bool $default
 * @return bool
 */
function config_bool(array $cfg, $key, $default)
{
	if (!array_key_exists($key, $cfg)) {
		return $default;
	}

	return $cfg[$key] === true || $cfg[$key] === 1 || $cfg[$key] === '1'
		|| (is_string($cfg[$key]) && strcasecmp($cfg[$key], 'true') === 0);
}

/**
 * @param resource $conn
 * @param string $sql
 * @return string|null
 */
function run_scalar_query($conn, $sql)
{
	$stmt = sqlsrv_query($conn, $sql);
	if ($stmt === false) {
		return null;
	}

	$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
	sqlsrv_free_stmt($stmt);

	if (!is_array($row) || empty($row)) {
		return null;
	}

	$row = array_change_key_case($row, CASE_LOWER);
	$value = reset($row);

	return $value !== null ? (string) $value : null;
}

/**
 * @param resource $stream
 * @return void
 */
function print_sqlsrv_errors($stream)
{
	$errors = sqlsrv_errors(SQLSRV_ERR_ALL);
	if (!is_array($errors)) {
		return;
	}

	foreach ($errors as $error) {
		$code = isset($error['code']) ? $error['code'] : '';
		$message = isset($error['message']) ? $error['message'] : 'unknown error';
		$state = isset($error['SQLSTATE']) ? $error['SQLSTATE'] : '';
		fwrite($stream, "  [{$state}/{$code}] {$message}\n");
	}
}
