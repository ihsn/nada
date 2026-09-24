<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Users CLI — admin reports for account maintenance.
 *
 * Usage:
 *   php index.php cli/users email_domain_duplicates
 *   php index.php cli/users email_domain_duplicates ihsn.org surveynetwork.org
 *   php index.php cli/users email_domain_duplicates --all
 *   php index.php cli/users email_domain_duplicates --json
 *   php index.php cli/users user_info 123
 *   php index.php cli/users user_info user@example.com
 *   php index.php cli/users user_info --email user@example.com
 *   php index.php cli/users user_info 5aa83c17-cf94-4114-8bb2-d5a462cffa70
 *   php index.php cli/users user_info user@example.com --json
 */
class Users extends CI_Controller {

	public function __construct()
	{
		if (php_sapi_name() !== 'cli') {
			die("This controller can only be accessed from the command line.\n");
		}

		$autoload_config =& get_config();
		if (isset($autoload_config['autoload']['libraries']) && is_array($autoload_config['autoload']['libraries'])) {
			$key = array_search('template', $autoload_config['autoload']['libraries']);
			if ($key !== false) {
				unset($autoload_config['autoload']['libraries'][$key]);
				$autoload_config['autoload']['libraries'] = array_values($autoload_config['autoload']['libraries']);
			}
		}

		parent::__construct();

		$this->load->config('auth');
		$this->load->database();
		$this->load->model('Ion_auth_model');
	}

	public function index()
	{
		echo "NADA Users CLI\n";
		echo "==============\n\n";
		echo "Commands:\n";
		echo "  email_domain_duplicates [domain ...] [--all] [--json]\n";
		echo "  user_info <id|email|oid> [--json]\n";
		echo "               [--email addr] [--id n] [--oid uuid]\n\n";
		echo "Finds accounts that share the same mailbox local-part across configured org\n";
		echo "domains (e.g. john@ihsn.org and john@surveynetwork.org).\n\n";
		echo "Domains default to email_domain_equivalence.domains from auth config\n";
		echo "(application/config/auth.php and auth.local.php).\n\n";
		echo "Examples:\n";
		echo "  php index.php cli/users email_domain_duplicates\n";
		echo "  php index.php cli/users email_domain_duplicates ihsn.org surveynetwork.org\n";
		echo "  php index.php cli/users email_domain_duplicates --json\n";
		echo "  php index.php cli/users email_domain_duplicates --all\n";
		echo "  php index.php cli/users user_info 42\n";
		echo "  php index.php cli/users user_info user@example.com\n";
		echo "  php index.php cli/users user_info --email user@example.com\n";
		echo "  php index.php cli/users user_info 5aa83c17-cf94-4114-8bb2-d5a462cffa70 --json\n";
	}

	/**
	 * Show basic account info for one user (includes Azure oid when linked).
	 */
	public function user_info()
	{
		$args = $this->get_cli_command_args('user_info');
		$options = $this->parse_user_info_options($args);

		if ($options['identifier'] === '') {
			$this->stderr("Usage: php index.php cli/users user_info <id|email|oid> [--json]\n");
			$this->stderr("       php index.php cli/users user_info --email user@example.com [--json]\n");
			exit(2);
		}

		$user = $this->resolve_user_for_cli($options['identifier']);
		if (!$user) {
			$this->stderr("User not found: " . $options['identifier'] . "\n");
			exit(1);
		}

		$summary = $this->build_user_summary($user);

		if ($options['json']) {
			echo json_encode($summary, JSON_PRETTY_PRINT) . "\n";
			exit(0);
		}

		echo str_repeat('=', 72) . "\n";
		echo "User info\n";
		echo str_repeat('=', 72) . "\n";
		echo "ID:           " . $summary['id'] . "\n";
		echo "Email:        " . $summary['email'] . "\n";
		echo "Username:     " . $summary['username'] . "\n";
		echo "Name:         " . trim($summary['first_name'] . ' ' . $summary['last_name']) . "\n";
		echo "Active:       " . ($summary['active'] ? 'yes' : 'no') . "\n";
		echo "Auth type:    " . ($summary['authtype'] !== '' ? $summary['authtype'] : '-') . "\n";
		echo "Auth id:      " . ($summary['authtype_id'] !== '' ? $summary['authtype_id'] : '-') . "\n";
		echo "OID (Azure):  " . ($summary['oid'] !== '' ? $summary['oid'] : '-') . "\n";
		echo "Created:      " . $summary['created_on'] . "\n";
		echo "Last login:   " . $summary['last_login'] . "\n";
		echo "Groups:       " . (empty($summary['groups']) ? '-' : implode(', ', $summary['groups'])) . "\n";
		echo str_repeat('=', 72) . "\n";

		exit(0);
	}

	/**
	 * Report duplicate user accounts across equivalent email domains.
	 */
	public function email_domain_duplicates()
	{
		$args = array_slice($this->uri->segment_array(), 3);
		$domains = array();
		$options = $this->parse_cli_options($args, $domains);

		if (empty($domains)) {
			$this->stderr("No domains configured. Set email_domain_equivalence.domains in auth.local.php\n");
			$this->stderr("or pass domains: php index.php cli/users email_domain_duplicates ihsn.org surveynetwork.org\n");
			exit(2);
		}

		$duplicate_groups = $this->Ion_auth_model->find_duplicate_local_parts_in_domains(
			$domains,
			!$options['include_inactive']
		);

		if (empty($duplicate_groups)) {
			if ($options['json']) {
				echo json_encode(array(
					'domains' => $domains,
					'duplicate_groups' => array(),
					'duplicate_local_part_count' => 0,
					'duplicate_account_count' => 0,
				), JSON_PRETTY_PRINT) . "\n";
			} else {
				echo "No duplicate local-parts found across domains: " . implode(', ', $domains) . "\n";
				if (!$options['include_inactive']) {
					echo "(active users only; use --all to include inactive)\n";
				}
			}
			exit(0);
		}

		$local_parts = array();
		foreach ($duplicate_groups as $group) {
			$local_parts[] = $group['local_part'];
		}

		$accounts = $this->Ion_auth_model->get_users_by_local_parts_in_domains(
			$local_parts,
			$domains,
			!$options['include_inactive']
		);

		$by_local_part = array();
		foreach ($accounts as $row) {
			$pos = strrpos($row['email'], '@');
			$lp = $pos !== false ? strtolower(substr($row['email'], 0, $pos)) : '';
			if (!isset($by_local_part[$lp])) {
				$by_local_part[$lp] = array();
			}
			$by_local_part[$lp][] = $row;
		}

		$duplicate_account_count = count($accounts);

		if ($options['json']) {
			$payload = array(
				'domains' => $domains,
				'include_inactive' => $options['include_inactive'],
				'duplicate_local_part_count' => count($duplicate_groups),
				'duplicate_account_count' => $duplicate_account_count,
				'duplicate_groups' => array(),
			);
			foreach ($duplicate_groups as $group) {
				$lp = $group['local_part'];
				$payload['duplicate_groups'][] = array(
					'local_part' => $lp,
					'account_count' => (int) $group['account_count'],
					'accounts' => isset($by_local_part[$lp]) ? $by_local_part[$lp] : array(),
				);
			}
			echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
			exit(1);
		}

		echo str_repeat('=', 72) . "\n";
		echo "Email domain duplicate report\n";
		echo str_repeat('=', 72) . "\n";
		echo "Domains: " . implode(', ', $domains) . "\n";
		echo "Scope: " . ($options['include_inactive'] ? 'all users' : 'active users only') . "\n";
		echo "Duplicate local-parts: " . count($duplicate_groups) . "\n";
		echo "Accounts involved: " . $duplicate_account_count . "\n";
		echo str_repeat('-', 72) . "\n";

		foreach ($duplicate_groups as $group) {
			$lp = $group['local_part'];
			echo "\n[" . $lp . "] (" . $group['account_count'] . " accounts)\n";
			if (!isset($by_local_part[$lp])) {
				continue;
			}
			foreach ($by_local_part[$lp] as $row) {
				echo sprintf(
					"  id=%-6s email=%-40s authtype=%-8s authtype_id=%s\n",
					$row['id'],
					$row['email'],
					$row['authtype'] !== null && $row['authtype'] !== '' ? $row['authtype'] : '-',
					$row['authtype_id'] !== null && $row['authtype_id'] !== '' ? $row['authtype_id'] : '-'
				);
			}
		}

		echo "\n" . str_repeat('-', 72) . "\n";
		echo "These duplicates can block Azure SSO when require_unique_local_part is enabled,\n";
		echo "or attach Azure to only one account when the token email matches exactly.\n";
		echo "Merge or deactivate extras before enabling email_domain_equivalence.\n";

		exit(1);
	}

	/**
	 * @param array $args CLI args after command name
	 * @param array &$domains populated domain list
	 * @return array options
	 */
	protected function parse_cli_options($args, &$domains)
	{
		$domains = array();
		$options = array(
			'include_inactive' => false,
			'json' => false,
		);

		foreach ($args as $arg) {
			if ($arg === '--all') {
				$options['include_inactive'] = true;
			} elseif ($arg === '--json') {
				$options['json'] = true;
			} elseif (strpos($arg, '--') === 0) {
				$this->stderr("Unknown option: {$arg}\n");
				exit(2);
			} else {
				$domains[] = strtolower(trim($arg));
			}
		}

		if (empty($domains)) {
			$cfg = $this->config->item('email_domain_equivalence');
			if (is_array($cfg) && !empty($cfg['domains']) && is_array($cfg['domains'])) {
				$domains = $cfg['domains'];
			}
		}

		$normalized = array();
		foreach ($domains as $d) {
			$d = strtolower(trim((string) $d));
			if ($d !== '') {
				$normalized[] = $d;
			}
		}
		$domains = array_values(array_unique($normalized));

		return $options;
	}

	/**
	 * @param array $args
	 * @return array identifier, json
	 */
	protected function parse_user_info_options($args)
	{
		$options = array(
			'identifier' => '',
			'json' => false,
		);

		for ($i = 0, $n = count($args); $i < $n; $i++) {
			$arg = $args[$i];

			if ($arg === '--json') {
				$options['json'] = true;
				continue;
			}

			if ($arg === '--email' || $arg === '--id' || $arg === '--oid') {
				$value = ($i + 1 < $n) ? $args[$i + 1] : '';
				if ($value === '' || strpos($value, '--') === 0) {
					$this->stderr("Missing value for {$arg}\n");
					exit(2);
				}
				$options['identifier'] = $this->normalize_cli_identifier($value);
				$i++;
				continue;
			}

			if (strpos($arg, '--email=') === 0) {
				$options['identifier'] = $this->normalize_cli_identifier(substr($arg, 8));
				continue;
			}
			if (strpos($arg, '--id=') === 0) {
				$options['identifier'] = $this->normalize_cli_identifier(substr($arg, 5));
				continue;
			}
			if (strpos($arg, '--oid=') === 0) {
				$options['identifier'] = $this->normalize_cli_identifier(substr($arg, 6));
				continue;
			}

			if (strpos($arg, '--') === 0) {
				$this->stderr("Unknown option: {$arg}\n");
				exit(2);
			}

			if ($options['identifier'] === '') {
				$options['identifier'] = $this->normalize_cli_identifier($arg);
			} else {
				$this->stderr("Unexpected argument: {$arg}\n");
				exit(2);
			}
		}

		return $options;
	}

	/**
	 * Read CLI args after a subcommand from argv (avoids URI segment quirks).
	 *
	 * @param string $command
	 * @return array
	 */
	protected function get_cli_command_args($command)
	{
		$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$index = array_search($command, $argv, true);
		if ($index !== false) {
			return array_slice($argv, $index + 1);
		}

		return array_slice($this->uri->segment_array(), 3);
	}

	protected function normalize_cli_identifier($value)
	{
		return rawurldecode(trim((string) $value));
	}

	/**
	 * @param string $identifier numeric id, email, or Azure oid (UUID)
	 * @return object|false
	 */
	protected function resolve_user_for_cli($identifier)
	{
		$identifier = trim((string) $identifier);
		if ($identifier === '') {
			return false;
		}

		if (ctype_digit($identifier)) {
			return $this->Ion_auth_model->get_user((int) $identifier);
		}

		if ($this->looks_like_uuid($identifier)) {
			$row = $this->Ion_auth_model->get_user_by_authtype_id($identifier);
			if ($row) {
				return $this->Ion_auth_model->get_user($row->id);
			}
		}

		$row = $this->Ion_auth_model->get_user_by_email_normalized($identifier);
		if ($row) {
			return $this->Ion_auth_model->get_user($row->id);
		}

		return false;
	}

	/**
	 * @param object $user from Ion_auth_model::get_user()
	 * @return array
	 */
	protected function build_user_summary($user)
	{
		$groups = array();
		if (!empty($user->groups) && is_array($user->groups)) {
			foreach ($user->groups as $group) {
				if (is_object($group) && isset($group->name)) {
					$groups[] = $group->name;
				} elseif (is_array($group) && isset($group['name'])) {
					$groups[] = $group['name'];
				}
			}
		}

		$authtype = isset($user->authtype) ? (string) $user->authtype : '';
		$authtype_id = isset($user->authtype_id) ? (string) $user->authtype_id : '';
		$oid = (strtoupper($authtype) === 'AAD' && $authtype_id !== '') ? $authtype_id : '';

		return array(
			'id' => (int) $user->id,
			'email' => isset($user->email) ? (string) $user->email : '',
			'username' => isset($user->username) ? (string) $user->username : '',
			'first_name' => isset($user->first_name) ? (string) $user->first_name : '',
			'last_name' => isset($user->last_name) ? (string) $user->last_name : '',
			'active' => isset($user->active) ? (int) $user->active : 0,
			'authtype' => $authtype,
			'authtype_id' => $authtype_id,
			'oid' => $oid,
			'created_on' => $this->format_cli_timestamp(isset($user->created_on) ? $user->created_on : null),
			'last_login' => $this->format_cli_timestamp(isset($user->last_login) ? $user->last_login : null),
			'groups' => $groups,
		);
	}

	protected function looks_like_uuid($value)
	{
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			(string) $value
		);
	}

	protected function format_cli_timestamp($value)
	{
		if ($value === null || $value === '' || (int) $value <= 0) {
			return '-';
		}
		return date('Y-m-d H:i:s', (int) $value);
	}

	protected function stderr($message)
	{
		fwrite(STDERR, $message);
	}
}
