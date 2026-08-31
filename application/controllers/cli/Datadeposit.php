<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data-deposit modernization CLI.
 *
 *   php index.php cli/datadeposit
 *   php index.php cli/datadeposit/dump
 *   php index.php cli/datadeposit/dump 42
 *   php index.php cli/datadeposit/verify
 */
class Datadeposit extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		if (! $this->input->is_cli_request()) {
			die("This controller can only be run from the command line.\n");
		}

		set_time_limit(0);
		$this->load->database();
		$this->load->library('Deposit_legacy_dump');
	}

	public function index()
	{
		echo "NADA Data Deposit CLI\n";
		echo "=====================\n\n";
		echo "Legacy dump (generic JSON, not IHSN metadata):\n";
		echo "  php index.php cli/datadeposit/dump          Dump all projects\n";
		echo "  php index.php cli/datadeposit/dump {id}     Dump one project\n";
		echo "  php index.php cli/datadeposit/verify        Re-read dumps vs live DB\n";
		echo "\n";
		echo "Import dumps into metadata / submission (schema_version=2):\n";
		echo "  php index.php cli/datadeposit/import dry    Dry-run all\n";
		echo "  php index.php cli/datadeposit/import        Import all (skip already v2)\n";
		echo "  php index.php cli/datadeposit/import 42     Import one project\n";
		echo "  php index.php cli/datadeposit/import force  Re-import all\n";
		echo "  php index.php cli/datadeposit/show {id}     Print imported blobs\n";
		echo "\n";
		echo "Folder-path repair (also at admin/datadeposit/old_folder_paths):\n";
		echo "  php index.php cli/datadeposit/old_folder_paths      List legacy md5 folders\n";
		echo "  php index.php cli/datadeposit/update_folder_paths   Write missing data_folder_path\n";
		echo "\n";
		echo "Dump directory: ".$this->deposit_legacy_dump->get_dump_directory()."\n";
		echo "Guide: install/DATADEPOSIT_MIGRATION.md\n";
		echo "\n";
	}

	/**
	 * @param string|int $target  all | numeric id
	 */
	public function dump($target = 'all')
	{
		if (! $this->db->table_exists('dd_projects')) {
			echo "dd_projects table not found. Nothing to dump.\n";
			exit(1);
		}

		$dir = $this->deposit_legacy_dump->get_dump_directory();
		echo "Dump directory: {$dir}\n";

		if ($target !== 'all' && $target !== '' && $target !== null && ! is_numeric($target)) {
			echo "Usage: php index.php cli/datadeposit/dump [id|all]\n";
			exit(1);
		}

		if (is_numeric($target)) {
			$id = (int) $target;
			echo "Dumping project {$id}...\n";
			try {
				$result = $this->deposit_legacy_dump->write($id, $dir);
				echo "Wrote {$result['path']}\n";
				echo "Checksum {$result['checksum']}\n";
			} catch (Exception $e) {
				echo "Error: ".$e->getMessage()."\n";
				exit(1);
			}

			return;
		}

		echo "Dumping all projects...\n";
		$manifest = $this->deposit_legacy_dump->dump_all($dir);
		echo "Projects in DB : {$manifest['project_count']}\n";
		echo "Wrote          : {$manifest['ok']}\n";
		echo "Failed         : {$manifest['failed']}\n";

		if (! empty($manifest['failures'])) {
			foreach ($manifest['failures'] as $failure) {
				echo "  P-{$failure['id']}: {$failure['message']}\n";
			}
		}

		echo "Manifest: {$dir}/".Deposit_legacy_dump::MANIFEST_NAME."\n";

		if ($manifest['failed'] > 0) {
			exit(1);
		}
	}

	public function verify()
	{
		if (! $this->db->table_exists('dd_projects')) {
			echo "dd_projects table not found.\n";
			exit(1);
		}

		$dir = $this->deposit_legacy_dump->get_dump_directory();
		echo "Verifying dumps in {$dir}\n";

		$result = $this->deposit_legacy_dump->verify($dir);
		echo "Checked: {$result['checked']}\n";

		if ($result['ok']) {
			echo "OK — dumps match the live database.\n";
			return;
		}

		echo "FAILED\n";
		foreach ($result['errors'] as $error) {
			echo "  - {$error}\n";
		}
		exit(1);
	}

	/**
	 * @param string $target  all | dry | force | numeric id
	 * @param string $mode    dry | force (when $target is an id or all)
	 */
	public function import($target = 'all', $mode = '')
	{
		if (! $this->db->table_exists('dd_projects')) {
			echo "dd_projects table not found.\n";
			exit(1);
		}

		if (! $this->db->field_exists('schema_version', 'dd_projects')
			|| ! $this->db->field_exists('submission', 'dd_projects')) {
			echo "schema_version / submission columns missing.\n";
			echo "Run: php index.php cli/migrate latest\n";
			exit(1);
		}

		$dry = false;
		$force = false;
		$id = null;

		$args = array_filter(array($target, $mode), function ($v) {
			return $v !== '' && $v !== null;
		});

		foreach ($args as $arg) {
			if ($arg === 'dry' || $arg === 'dry-run') {
				$dry = true;
			} elseif ($arg === 'force') {
				$force = true;
			} elseif ($arg === 'all') {
				continue;
			} elseif (is_numeric($arg)) {
				$id = (int) $arg;
			} else {
				echo "Usage: php index.php cli/datadeposit/import [id|all] [dry|force]\n";
				exit(1);
			}
		}

		$this->load->library('Deposit_legacy_import');

		if ($id) {
			echo ($dry ? 'Dry-run ' : 'Importing ')."project {$id}".($force ? ' (force)' : '')."...\n";
			try {
				$row = $this->deposit_legacy_import->import_project($id, $dry, $force);
				$this->print_import_row($row);
			} catch (Exception $e) {
				echo "Error: ".$e->getMessage()."\n";
				exit(1);
			}

			return;
		}

		echo ($dry ? 'Dry-run' : 'Importing').' all projects'.($force ? ' (force)' : '')."...\n";
		$result = $this->deposit_legacy_import->import_all($dry, $force);
		echo "Imported : {$result['ok']}\n";
		echo "Skipped  : {$result['skipped']}\n";
		echo "Failed   : {$result['failed']}\n";

		foreach ($result['projects'] as $row) {
			if (! empty($row['warnings'])) {
				$this->print_import_row($row);
			}
		}

		if (! empty($result['failures'])) {
			foreach ($result['failures'] as $failure) {
				echo "  P-{$failure['id']}: {$failure['message']}\n";
			}
			exit(1);
		}
	}

	public function show($id = null)
	{
		if (! is_numeric($id)) {
			echo "Usage: php index.php cli/datadeposit/show {id}\n";
			exit(1);
		}

		$row = $this->db->select('id, title, schema_version, metadata, submission')
			->from('dd_projects')
			->where('id', (int) $id)
			->get()
			->row_array();

		if (! $row) {
			echo "Project not found.\n";
			exit(1);
		}

		$metadata = json_decode($row['metadata'], true);
		$submission = json_decode($row['submission'], true);

		echo "id              : {$row['id']}\n";
		echo "title           : {$row['title']}\n";
		echo "schema_version  : {$row['schema_version']}\n";
		echo "study title     : ".(isset($metadata['study_desc']['title_statement']['title']) ? $metadata['study_desc']['title_statement']['title'] : '')."\n";
		echo "additional keys : ".(isset($metadata['additional']) ? implode(', ', array_keys($metadata['additional'])) : '')."\n";
		echo "citations       : ".(isset($metadata['citations']) ? count($metadata['citations']) : 0)."\n";
		echo "submission keys : ".(is_array($submission) ? implode(', ', array_keys($submission)) : '')."\n";
		echo "\n--- metadata ---\n";
		echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
		echo "\n--- submission ---\n";
		echo json_encode($submission, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
	}

	public function old_folder_paths()
	{
		$this->load->model('DD_project_model');
		$root = $this->DD_project_model->get_datadeposit_root_folder();
		if (!$root) {
			echo "Data deposit resources folder is not configured.\n";
			exit(1);
		}

		if (!$this->db->table_exists('dd_projects')) {
			echo "dd_projects table not found.\n";
			exit(1);
		}

		$projects = $this->db->select('id, created_on, title')->get('dd_projects')->result_array();
		if (!is_array($projects)) {
			$projects = array();
		}

		echo "id\tcreated_on\tmd5\tfolder\texists\n";
		foreach ($projects as $project) {
			$md5 = md5($project['id'].$project['created_on']);
			$folder = $root.'/'.$md5;
			$exists = file_exists($folder) ? 'YES' : '';
			echo $project['id']."\t".$project['created_on']."\t".$md5."\t".$folder."\t".$exists."\n";
		}
	}

	public function update_folder_paths()
	{
		$this->load->model('DD_project_model');
		$root = $this->DD_project_model->get_datadeposit_root_folder();
		if (!$root) {
			echo "Data deposit resources folder is not configured.\n";
			exit(1);
		}

		if (!$this->db->table_exists('dd_projects')) {
			echo "dd_projects table not found.\n";
			exit(1);
		}

		$projects = $this->db->select('id, created_on, title, data_folder_path')
			->get('dd_projects')
			->result_array();
		if (!is_array($projects)) {
			$projects = array();
		}

		$updated = 0;
		foreach ($projects as $project) {
			if (!empty($project['data_folder_path'])) {
				continue;
			}
			$md5 = md5($project['id'].$project['created_on']);
			$folder = $root.'/'.$md5;
			if (!file_exists($folder)) {
				continue;
			}
			$this->DD_project_model->set_project_folder($project['id'], $md5);
			echo "updated project ".$project['id']."\n";
			$updated++;
		}

		echo "Updated: {$updated}\n";
	}

	private function print_import_row(array $row)
	{
		$label = 'P-'.$row['id'];
		if (! empty($row['skipped'])) {
			echo "  {$label}: skipped ({$row['reason']})\n";
			return;
		}

		$title = isset($row['title']) ? $row['title'] : '';
		echo "  {$label}: {$title}";
		if (! empty($row['dry_run'])) {
			echo ' [dry-run]';
		}
		echo "\n";
		if (! empty($row['warnings'])) {
			foreach ($row['warnings'] as $warning) {
				echo "    warning: {$warning}\n";
			}
		}
	}
}
