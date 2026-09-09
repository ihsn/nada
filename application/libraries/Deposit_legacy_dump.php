<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generic legacy data-deposit dump (not IHSN / microdata JSON).
 *
 * Snapshot of dd_projects + dd_study + citations + resources + collaborators + history.
 * Grids are decoded to named-object arrays. File bytes are not included.
 */
class Deposit_legacy_dump
{
	const DUMP_TYPE = 'nada-datadeposit-legacy';
	const DUMP_VERSION = 1;
	const MANIFEST_NAME = 'manifest.json';

	/** @var CI_Controller */
	private $ci;

	/**
	 * Study TEXT columns stored as positional JSON grids → named keys.
	 *
	 * @var array<string, string[]>
	 */
	private $grid_fields = array(
		'overview_methods'         => array('text', 'vocab', 'uri'),
		'scope_class'              => array('text', 'vocab', 'uri'),
		'scope_keywords'           => array('text', 'vocab', 'uri'),
		'coverage_country'         => array('name', 'abbr'),
		'prod_s_investigator'      => array('name', 'affiliation'),
		'prod_s_other_prod'        => array('name', 'abbr', 'affiliation', 'role'),
		'prod_s_funding'           => array('agency', 'abbr', 'grant', 'role'),
		'prod_s_acknowledgements'  => array('name', 'affiliation', 'role'),
		'coll_dates'               => array('start', 'end', 'cycle'),
		'coll_periods'             => array('start', 'end', 'cycle'),
		'coll_collectors'          => array('name', 'abbr', 'affiliation'),
		'access_authority'         => array('name', 'affiliation', 'email', 'uri'),
		'contacts_contacts'        => array('name', 'affiliation', 'email', 'uri'),
		'impact_wb_lead'           => array('name', 'affiliation', 'email', 'uri'),
		'impact_wb_members'        => array('name', 'affiliation', 'email', 'uri'),
	);

	private $project_keys = array(
		'id',
		'title',
		'shortname',
		'description',
		'status',
		'data_type',
		'data_folder_path',
		'created_by',
		'uid',
		'created_on',
		'last_modified',
		'submitted_on',
		'requested_reopen',
		'requested_when',
	);

	private $submission_keys = array(
		'access_policy',
		'to_catalog',
		'is_embargoed',
		'embargoed',
		'disclosure_risk',
		'key_variables',
		'sensitive_variables',
		'library_notes',
		'cc',
	);

	private $resource_keys = array(
		'id',
		'title',
		'author',
		'created',
		'description',
		'filename',
		'dctype',
		'dcformat',
		'filesize',
	);

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->database();
		$this->ci->load->model('DD_project_model');
		$this->ci->load->model('DD_study_model');
		$this->ci->load->model('DD_resource_model');
		$this->ci->load->model('DD_citation_model');
	}

	/**
	 * Directory for dump files: {userdata_path}/datadeposit-legacy-dumps
	 *
	 * @return string
	 */
	public function get_dump_directory()
	{
		$userdata = $this->ci->config->item('userdata_path');
		if ($userdata === null || $userdata === false || trim((string) $userdata) === '') {
			$userdata = 'userdata';
		}

		$userdata = rtrim(str_replace('\\', '/', (string) $userdata), '/');
		if ($userdata !== '' && $userdata[0] !== '/' && ! preg_match('#^[A-Za-z]:/#', $userdata)) {
			$userdata = FCPATH.$userdata;
		}

		return $userdata.'/datadeposit-legacy-dumps';
	}

	/**
	 * @param int $project_id
	 * @return string
	 */
	public function dump_filename($project_id)
	{
		return 'P-'.(int) $project_id.'.json';
	}

	/**
	 * @return array<string, string[]>
	 */
	public function get_grid_fields()
	{
		return $this->grid_fields;
	}

	/**
	 * Load a dump file from disk.
	 *
	 * @param int         $project_id
	 * @param string|null $directory
	 * @return array
	 */
	public function read($project_id, $directory = null)
	{
		$directory = $directory ? rtrim(str_replace('\\', '/', $directory), '/') : $this->get_dump_directory();
		$path = $directory.'/'.$this->dump_filename($project_id);
		if (! is_file($path)) {
			throw new RuntimeException('Dump file not found: '.$path);
		}

		$dump = json_decode(file_get_contents($path), true);
		if (! is_array($dump) || empty($dump['dump_type'])) {
			throw new RuntimeException('Invalid dump JSON: '.$path);
		}

		return $dump;
	}

	/**
	 * Build one project dump (in memory).
	 *
	 * @param int $project_id
	 * @return array
	 */
	public function build($project_id)
	{
		$project_id = (int) $project_id;
		if ($project_id < 1) {
			throw new InvalidArgumentException('Invalid project id');
		}

		if (! $this->ci->db->table_exists('dd_projects')) {
			throw new RuntimeException('dd_projects table not found');
		}

		$project = $this->ci->DD_project_model->get_by_id($project_id);
		if (! is_array($project) || empty($project['id'])) {
			throw new RuntimeException('Project not found: '.$project_id);
		}

		return array(
			'dump_type'     => self::DUMP_TYPE,
			'dump_version'  => self::DUMP_VERSION,
			'dumped_at'     => gmdate('c'),
			'project_id'    => $project_id,
			'project'       => $this->pick($project, $this->project_keys),
			'submission'    => $this->pick($project, $this->submission_keys),
			'study'         => $this->build_study($project_id),
			'citations'     => $this->build_citations($project_id),
			'resources'     => $this->build_resources($project_id),
			'collaborators' => $this->build_collaborators($project_id),
			'history'       => $this->build_history($project_id),
		);
	}

	/**
	 * Encode dump as pretty JSON.
	 *
	 * @param array $dump
	 * @return string
	 */
	public function encode($dump)
	{
		$json = json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($json === false) {
			throw new RuntimeException('JSON encode failed: '.json_last_error_msg());
		}

		return $json;
	}

	/**
	 * Write one dump file. Returns path, checksum, and counts.
	 *
	 * @param int         $project_id
	 * @param string|null $directory
	 * @return array{path:string,checksum:string,dump:array}
	 */
	public function write($project_id, $directory = null)
	{
		$directory = $directory ? rtrim(str_replace('\\', '/', $directory), '/') : $this->get_dump_directory();
		$this->ensure_directory($directory);

		$dump = $this->build($project_id);
		$json = $this->encode($dump);
		$path = $directory.'/'.$this->dump_filename($project_id);

		if (file_put_contents($path, $json) === false) {
			throw new RuntimeException('Failed to write '.$path);
		}

		return array(
			'path'     => $path,
			'checksum' => hash_file('sha256', $path),
			'dump'     => $dump,
		);
	}

	/**
	 * Dump every project. Returns a manifest array (also written to disk).
	 *
	 * @param string|null $directory
	 * @return array
	 */
	public function dump_all($directory = null)
	{
		$directory = $directory ? rtrim(str_replace('\\', '/', $directory), '/') : $this->get_dump_directory();
		$ids = $this->list_project_ids();

		$manifest = array(
			'dump_type'    => self::DUMP_TYPE,
			'dump_version' => self::DUMP_VERSION,
			'dumped_at'    => gmdate('c'),
			'directory'    => $directory,
			'project_count'=> count($ids),
			'ok'           => 0,
			'failed'       => 0,
			'projects'     => array(),
			'failures'     => array(),
		);

		foreach ($ids as $id) {
			try {
				$result = $this->write($id, $directory);
				$dump = $result['dump'];
				$manifest['ok']++;
				$manifest['projects'][] = array(
					'id'               => $id,
					'file'             => $this->dump_filename($id),
					'checksum'         => $result['checksum'],
					'status'           => isset($dump['project']['status']) ? $dump['project']['status'] : '',
					'citation_count'   => count($dump['citations']),
					'resource_count'   => count($dump['resources']),
					'history_count'    => count($dump['history']),
					'has_study'        => ! empty($dump['study']),
				);
			} catch (Exception $e) {
				$manifest['failed']++;
				$manifest['failures'][] = array(
					'id'      => $id,
					'message' => $e->getMessage(),
				);
			}
		}

		$this->write_manifest($directory, $manifest);

		return $manifest;
	}

	/**
	 * Re-read dumps and compare to the live database.
	 *
	 * @param string|null $directory
	 * @return array{ok:bool,errors:string[],checked:int}
	 */
	public function verify($directory = null)
	{
		$directory = $directory ? rtrim(str_replace('\\', '/', $directory), '/') : $this->get_dump_directory();
		$errors = array();
		$checked = 0;

		$manifest_path = $directory.'/'.self::MANIFEST_NAME;
		if (! is_file($manifest_path)) {
			return array(
				'ok'      => false,
				'errors'  => array('Manifest not found: '.$manifest_path),
				'checked' => 0,
			);
		}

		$manifest = json_decode(file_get_contents($manifest_path), true);
		if (! is_array($manifest)) {
			return array(
				'ok'      => false,
				'errors'  => array('Manifest is not valid JSON'),
				'checked' => 0,
			);
		}

		$db_ids = $this->list_project_ids();
		$dumped_ids = array();
		if (! empty($manifest['projects']) && is_array($manifest['projects'])) {
			foreach ($manifest['projects'] as $row) {
				$dumped_ids[] = (int) $row['id'];
			}
		}

		sort($db_ids);
		sort($dumped_ids);

		if ($db_ids !== $dumped_ids) {
			$missing = array_diff($db_ids, $dumped_ids);
			$extra = array_diff($dumped_ids, $db_ids);
			if ($missing) {
				$errors[] = 'Projects in DB but not in manifest: '.implode(', ', $missing);
			}
			if ($extra) {
				$errors[] = 'Projects in manifest but not in DB: '.implode(', ', $extra);
			}
		}

		if (! empty($manifest['failures'])) {
			$errors[] = count($manifest['failures']).' dump failure(s) recorded in the manifest';
		}

		foreach ((array) $manifest['projects'] as $row) {
			$checked++;
			$id = (int) $row['id'];
			$path = $directory.'/'.$this->dump_filename($id);

			if (! is_file($path)) {
				$errors[] = 'P-'.$id.': file missing';
				continue;
			}

			$checksum = hash_file('sha256', $path);
			if (! empty($row['checksum']) && $checksum !== $row['checksum']) {
				$errors[] = 'P-'.$id.': checksum mismatch';
			}

			$dump = json_decode(file_get_contents($path), true);
			if (! is_array($dump)) {
				$errors[] = 'P-'.$id.': invalid JSON';
				continue;
			}

			if (empty($dump['dump_type']) || $dump['dump_type'] !== self::DUMP_TYPE) {
				$errors[] = 'P-'.$id.': dump_type mismatch';
			}
			if (! isset($dump['dump_version']) || (int) $dump['dump_version'] !== self::DUMP_VERSION) {
				$errors[] = 'P-'.$id.': dump_version mismatch';
			}
			if ((int) $dump['project_id'] !== $id) {
				$errors[] = 'P-'.$id.': project_id mismatch';
			}

			foreach (array('project', 'submission', 'study', 'citations', 'resources', 'collaborators', 'history') as $key) {
				if (! array_key_exists($key, $dump)) {
					$errors[] = 'P-'.$id.': missing section '.$key;
				}
			}

			if (isset($dump['citations']) && is_array($dump['citations'])) {
				$db_cit = $this->count_table_rows('dd_citations', 'pid', $id);
				if ($db_cit !== count($dump['citations'])) {
					$errors[] = 'P-'.$id.': citation count dump='.count($dump['citations']).' db='.$db_cit;
				}
			}

			if (isset($dump['resources']) && is_array($dump['resources'])) {
				$db_res = $this->count_table_rows('dd_project_resources', 'project_id', $id);
				if ($db_res !== count($dump['resources'])) {
					$errors[] = 'P-'.$id.': resource count dump='.count($dump['resources']).' db='.$db_res;
				}
			}
		}

		return array(
			'ok'      => empty($errors),
			'errors'  => $errors,
			'checked' => $checked,
		);
	}

	/**
	 * @return int[]
	 */
	public function list_project_ids()
	{
		if (! $this->ci->db->table_exists('dd_projects')) {
			return array();
		}

		$rows = $this->ci->db->select('id')->from('dd_projects')->order_by('id', 'asc')->get()->result_array();
		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row['id'];
		}

		return $ids;
	}

	/**
	 * @param int $project_id
	 * @return array
	 */
	private function build_study($project_id)
	{
		if (! $this->ci->db->table_exists('dd_study')) {
			return array();
		}

		$rows = $this->ci->DD_study_model->get_study_array($project_id);
		if (empty($rows[0]) || ! is_array($rows[0])) {
			return array();
		}

		$study = $rows[0];
		unset($study['id']);

		foreach ($this->grid_fields as $field => $columns) {
			$raw = isset($study[$field]) ? $study[$field] : null;
			$study[$field] = $this->decode_grid($raw, $columns);
		}

		return $study;
	}

	/**
	 * @param int $project_id
	 * @return array
	 */
	private function build_citations($project_id)
	{
		if (! $this->ci->db->table_exists('dd_citations')) {
			return array();
		}

		$rows = $this->ci->DD_citation_model->get_citations_by_project($project_id);
		if (! is_array($rows)) {
			return array();
		}

		$out = array();
		foreach ($rows as $row) {
			unset($row['pid']);
			foreach (array('authors', 'editors', 'translators') as $role) {
				$row[$role] = $this->normalize_citation_people(isset($row[$role]) ? $row[$role] : array());
			}
			$out[] = $row;
		}

		return $out;
	}

	/**
	 * @param mixed $people
	 * @return array
	 */
	private function normalize_citation_people($people)
	{
		if (! is_array($people)) {
			return array();
		}

		$out = array();
		foreach ($people as $person) {
			if (! is_array($person)) {
				continue;
			}
			$out[] = array(
				'fname'   => isset($person['fname']) ? $person['fname'] : '',
				'lname'   => isset($person['lname']) ? $person['lname'] : '',
				'initial' => isset($person['initial']) ? $person['initial'] : '',
			);
		}

		return $out;
	}

	/**
	 * @param int $project_id
	 * @return array
	 */
	private function build_resources($project_id)
	{
		if (! $this->ci->db->table_exists('dd_project_resources')) {
			return array();
		}

		$rows = $this->ci->DD_resource_model->get_project_resources($project_id);
		$out = array();
		foreach ($rows as $row) {
			$out[] = $this->pick($row, $this->resource_keys);
		}

		return $out;
	}

	/**
	 * @param int $project_id
	 * @return array
	 */
	private function build_collaborators($project_id)
	{
		if (! $this->ci->db->table_exists('dd_collaborators')) {
			return array();
		}

		$rows = $this->ci->db
			->select('email, access')
			->from('dd_collaborators')
			->where('pid', $project_id)
			->order_by('id', 'asc')
			->get()
			->result_array();

		return $rows ? $rows : array();
	}

	/**
	 * @param int $project_id
	 * @return array
	 */
	private function build_history($project_id)
	{
		if (! $this->ci->db->table_exists('dd_datadeposit_history')) {
			return array();
		}

		$rows = $this->ci->DD_project_model->history_id($project_id);
		$out = array();
		foreach ($rows as $row) {
			$row = (array) $row;
			$out[] = array(
				'user_identity'  => isset($row['user_identity']) ? $row['user_identity'] : '',
				'created_on'     => isset($row['created_on']) ? $row['created_on'] : null,
				'project_status' => isset($row['project_status']) ? $row['project_status'] : '',
				'comments'       => isset($row['comments']) ? $row['comments'] : '',
			);
		}

		return $out;
	}

	/**
	 * @param mixed    $raw
	 * @param string[] $columns
	 * @return array
	 */
	private function decode_grid($raw, array $columns)
	{
		if ($raw === null || $raw === '' || $raw === '[]') {
			return array();
		}

		$data = is_array($raw) ? $raw : json_decode($raw, true);
		if (! is_array($data)) {
			return array();
		}

		$rows = array();
		foreach ($data as $row) {
			if (! is_array($row)) {
				continue;
			}

			$named = array();
			foreach ($columns as $i => $name) {
				$named[$name] = array_key_exists($i, $row) ? $row[$i] : '';
			}
			$empty = true;
			foreach ($named as $value) {
				if ($value !== '' && $value !== null) {
					$empty = false;
					break;
				}
			}
			if (! $empty) {
				$rows[] = $named;
			}
		}

		return $rows;
	}

	/**
	 * @param array    $source
	 * @param string[] $keys
	 * @return array
	 */
	private function pick(array $source, array $keys)
	{
		$out = array();
		foreach ($keys as $key) {
			$out[$key] = array_key_exists($key, $source) ? $source[$key] : null;
		}

		return $out;
	}

	/**
	 * @param string $table
	 * @param string $fk
	 * @param int    $id
	 * @return int
	 */
	private function count_table_rows($table, $fk, $id)
	{
		if (! $this->ci->db->table_exists($table)) {
			return 0;
		}

		return (int) $this->ci->db->where($fk, $id)->count_all_results($table);
	}

	/**
	 * @param string $directory
	 * @return void
	 */
	private function ensure_directory($directory)
	{
		if (is_dir($directory)) {
			return;
		}

		if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
			throw new RuntimeException('Cannot create dump directory: '.$directory);
		}
	}

	/**
	 * @param string $directory
	 * @param array  $manifest
	 * @return void
	 */
	private function write_manifest($directory, array $manifest)
	{
		$this->ensure_directory($directory);
		$path = $directory.'/'.self::MANIFEST_NAME;
		$json = $this->encode($manifest);
		if (file_put_contents($path, $json) === false) {
			throw new RuntimeException('Failed to write '.$path);
		}
	}
}
