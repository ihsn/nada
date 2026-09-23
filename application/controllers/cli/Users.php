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
		echo "  email_domain_duplicates [domain ...] [--all] [--json]\n\n";
		echo "Finds accounts that share the same mailbox local-part across configured org\n";
		echo "domains (e.g. john@ihsn.org and john@surveynetwork.org).\n\n";
		echo "Domains default to email_domain_equivalence.domains from auth config\n";
		echo "(application/config/auth.php and auth.local.php).\n\n";
		echo "Examples:\n";
		echo "  php index.php cli/users email_domain_duplicates\n";
		echo "  php index.php cli/users email_domain_duplicates ihsn.org surveynetwork.org\n";
		echo "  php index.php cli/users email_domain_duplicates --json\n";
		echo "  php index.php cli/users email_domain_duplicates --all\n";
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

	protected function stderr($message)
	{
		fwrite(STDERR, $message);
	}
}
