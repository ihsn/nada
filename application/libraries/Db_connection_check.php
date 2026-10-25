<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Why the database connection failed, from the app's own connection (the database.php settings).
 *
 * The connection the app opened (or failed to open) is the test: when it is open, the server answered, the login
 * worked and the database is open. When it is not, the driver's connect error says which step failed:
 *
 *   server_unreachable  no answer from the server (host, port, firewall)
 *   tls_rejected        the server's TLS certificate is not trusted
 *   login_failed        the server rejected the user name or password
 *   database_missing    logged in, but the database does not exist
 *   database_no_access  logged in, the database exists, but the user cannot open it
 *   unknown             anything else (the raw error is in 'detail')
 *
 * MySQL's error codes tell all of them apart. SQL Server answers 4060 both for a missing database and for one the
 * login cannot open; only then is a second, server-only connection made (fast: the server just answered) to ask
 * DB_ID(). No second attempt is ever made after a network failure: on SQL Server each one waits the login timeout.
 */
class Db_connection_check
{
	const OK                 = 'ok';
	const SERVER_UNREACHABLE = 'server_unreachable';
	const TLS_REJECTED       = 'tls_rejected';
	const LOGIN_FAILED       = 'login_failed';
	const DATABASE_MISSING   = 'database_missing';
	const DATABASE_NO_ACCESS = 'database_no_access';
	const UNKNOWN            = 'unknown';

	/** @var CI_Controller */
	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	/**
	 * @param CI_DB $db the app's connection ($this->db)
	 * @return array{status: string, message: string, detail: string} message: what to fix, safe to show an
	 *         administrator (no password, no raw driver text); detail: the raw driver error, for the log
	 */
	public function check($db)
	{
		if ($db->conn_id) {
			return $this->result(self::OK, $db, '');
		}

		if ($db->dbdriver === 'mysqli') {
			list($status, $detail) = $this->classify_mysql();
		} elseif ($db->dbdriver === 'sqlsrv') {
			list($status, $detail) = $this->classify_sqlsrv($db);
		} else {
			list($status, $detail) = array(self::UNKNOWN, 'unsupported database driver ' . $db->dbdriver);
		}

		return $this->result($status, $db, $detail);
	}

	private function classify_mysql()
	{
		$code   = (int) mysqli_connect_errno();
		$detail = $code . ' ' . mysqli_connect_error();

		switch ($code) {
			case 1049: return array(self::DATABASE_MISSING, $detail);   // Unknown database
			case 1044: return array(self::DATABASE_NO_ACCESS, $detail); // Access denied ... to database
			case 1045: return array(self::LOGIN_FAILED, $detail);       // Access denied for user
			case 2026: return array(self::TLS_REJECTED, $detail);       // SSL connection error
			case 2002:                                                  // can't connect (socket / host)
			case 2003:                                                  // can't connect (TCP)
			case 2005:                                                  // unknown host
			case 2006:                                                  // server has gone away
			case 2013:                                                  // lost connection during connect
				return array(self::SERVER_UNREACHABLE, $detail);
		}
		return array(self::UNKNOWN, $detail);
	}

	private function classify_sqlsrv($db)
	{
		$errors = isset($db->connect_errors) && $db->connect_errors ? $db->connect_errors : (sqlsrv_errors(SQLSRV_ERR_ERRORS) ?: array());
		$codes  = array();
		$states = array();
		$detail = array();
		foreach ($errors as $e) {
			$codes[]  = (int) $e['code'];
			$states[] = (string) $e['SQLSTATE'];
			$detail[] = $e['SQLSTATE'] . '/' . $e['code'] . ' ' . preg_replace('/^(\[[^\]]+\])+\s*/', '', $e['message']);
		}
		$detail = implode(' | ', $detail);

		if (in_array(4060, $codes, TRUE)) {
			// logged in, the database cannot be opened: missing, or no access
			return array($this->sqlsrv_database_status($db), $detail);
		}
		if (in_array(18456, $codes, TRUE)) {
			return array(self::LOGIN_FAILED, $detail);
		}
		if (stripos($detail, 'SSL Provider') !== FALSE || stripos($detail, 'certificate') !== FALSE) {
			return array(self::TLS_REJECTED, $detail);
		}
		if (in_array('HYT00', $states, TRUE) || in_array('08001', $states, TRUE)) {
			return array(self::SERVER_UNREACHABLE, $detail);
		}
		return array(self::UNKNOWN, $detail);
	}

	/** SQL Server 4060: a server-only connection (same settings, no database) asks whether the database exists. */
	private function sqlsrv_database_status($db)
	{
		$settings = array('dbdriver', 'hostname', 'port', 'username', 'password', 'char_set', 'dbcollat', 'encrypt',
			'trust_server_certificate', 'login_timeout');
		$config = array('database' => '', 'db_debug' => FALSE, 'pconnect' => FALSE);
		foreach ($settings as $name) {
			if (isset($db->$name)) {
				$config[$name] = $db->$name;
			}
		}

		$server = $this->ci->load->database($config, TRUE);
		if (!$server->conn_id) {
			return self::DATABASE_NO_ACCESS; // cannot tell; the login can reach the server but not the database
		}
		$row = $server->query('SELECT DB_ID(?) AS id', array($db->database))->row_array();
		$server->close();

		return ($row && $row['id'] !== NULL) ? self::DATABASE_NO_ACCESS : self::DATABASE_MISSING;
	}

	private function result($status, $db, $detail)
	{
		$host = (string) $db->hostname . (!empty($db->port) ? ':' . $db->port : '');
		$messages = array(
			self::OK                 => 'Connected to the database.',
			self::SERVER_UNREACHABLE => sprintf('Cannot reach the database server %s. Check the host name and port in application/config/database.php, and that the server is running and not blocked by a firewall.', $host),
			self::TLS_REJECTED       => sprintf('The database server %s uses a TLS certificate this server does not trust. Install a trusted certificate on the database server, or set \'trust_server_certificate\' (SQL Server) in application/config/database.php.', $host),
			self::LOGIN_FAILED       => sprintf('The database server %s rejected the user name or password set in application/config/database.php.', $host),
			self::DATABASE_MISSING   => sprintf('Database "%s" does not exist on the server %s. Create it (empty) and reload this page.', $db->database, $host),
			self::DATABASE_NO_ACCESS => sprintf('User "%s" cannot open database "%s" on the server %s. Grant the user access to the database.', $db->username, $db->database, $host),
			self::UNKNOWN            => 'Could not connect to the database. Check the settings in application/config/database.php; the error is in the application log.',
		);

		return array('status' => $status, 'message' => $messages[$status], 'detail' => $detail);
	}
}
