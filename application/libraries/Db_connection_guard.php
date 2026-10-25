<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stops the request when the app's database connection did not open, before anything else uses it.
 *
 * Autoloaded right after 'database' (config/autoload.php): the libraries after it query the database while loading
 * (template, site_configurations, ...), and with no connection those fail in ways that hide the cause (MySQL's
 * query builder fails escaping values). The reason comes from Db_connection_check:
 *   - the installer and the command line (whoever sets up the site) see what to fix;
 *   - everyone else sees the general message, and the reason goes to the log.
 * Connected, this is one property check.
 */
class Db_connection_guard
{
	public function __construct()
	{
		$ci =& get_instance();
		if (!isset($ci->db) || $ci->db->conn_id) {
			return;
		}

		$ci->load->library('db_connection_check');
		$check = $ci->db_connection_check->check($ci->db);
		log_message('error', 'Database connection: ' . $check['status'] . ': ' . $check['message'] . ' ' . $check['detail']);

		if (is_cli()) {
			show_error($check['message'], 500, 'Database connection failed');
		}
		if (strtolower((string) $ci->router->class) === 'install') {
			show_error(html_escape($check['message']), 500, 'Database connection failed');
		}
		show_error('Failed to connect to database, check database settings', 500, 'Database connection failed');
	}
}
