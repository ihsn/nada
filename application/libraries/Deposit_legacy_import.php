<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Nada\DdiParser\Factory\ReaderFactory;
use Nada\DdiParser\Mapping\NadaSurveyMapper;

/**
 * Import a generic legacy dump into dd_projects.metadata / submission.
 *
 * Study DDI XML is generated from the dump, parsed to IHSN JSON (parser only).
 * WB fields and ident_ddp_id go on metadata.additional. Citations become Chicago text.
 */
class Deposit_legacy_import
{
	const SCHEMA_VERSION_NEW = 2;

	/** @var CI_Controller */
	private $ci;

	private $additional_keys = array(
		'operational_wb_name',
		'operational_wb_id',
		'operational_wb_net',
		'operational_wb_sector',
		'operational_wb_summary',
		'operational_wb_objectives',
		'impact_wb_name',
		'impact_wb_id',
		'impact_wb_area',
		'impact_wb_lead',
		'impact_wb_members',
		'impact_wb_description',
	);

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->database();
		$this->ci->load->library('Deposit_legacy_dump');
		$this->ci->load->library('DDI_Study_Export');
		$this->ci->load->library('Chicago_citation');
		$this->ci->config->load('metadata_parser', true);
	}

	/**
	 * @param int  $project_id
	 * @param bool $dry_run
	 * @param bool $force
	 * @return array
	 */
	public function import_project($project_id, $dry_run = false, $force = false)
	{
		$project_id = (int) $project_id;
		$dump = $this->ci->deposit_legacy_dump->read($project_id);

		$row = $this->ci->db->select('id, schema_version')->from('dd_projects')->where('id', $project_id)->get()->row_array();
		if (! $row) {
			throw new RuntimeException('Project not found in dd_projects: '.$project_id);
		}

		$current = isset($row['schema_version']) ? (int) $row['schema_version'] : 1;
		if ($current >= self::SCHEMA_VERSION_NEW && ! $force) {
			return array(
				'id'      => $project_id,
				'skipped' => true,
				'reason'  => 'schema_version '.$current,
			);
		}

		$warnings = array();
		$metadata = $this->build_metadata($dump, $warnings);
		$submission = $this->build_submission(isset($dump['submission']) ? $dump['submission'] : array());

		if (! $dry_run) {
			$this->save($project_id, $metadata, $submission);
		}

		return array(
			'id'          => $project_id,
			'skipped'     => false,
			'dry_run'     => $dry_run,
			'warnings'    => $warnings,
			'title'       => $this->nested_get($metadata, array('study_desc', 'title_statement', 'title')),
			'citations'   => isset($metadata['citations']) ? count($metadata['citations']) : 0,
			'additional'  => isset($metadata['additional']) ? array_keys($metadata['additional']) : array(),
		);
	}

	/**
	 * @param bool $dry_run
	 * @param bool $force
	 * @return array
	 */
	public function import_all($dry_run = false, $force = false)
	{
		$ids = $this->ci->deposit_legacy_dump->list_project_ids();
		$result = array(
			'ok'       => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'projects' => array(),
			'failures' => array(),
		);

		foreach ($ids as $id) {
			try {
				$row = $this->import_project($id, $dry_run, $force);
				if (! empty($row['skipped'])) {
					$result['skipped']++;
				} else {
					$result['ok']++;
				}
				$result['projects'][] = $row;
			} catch (Exception $e) {
				$result['failed']++;
				$result['failures'][] = array(
					'id'      => $id,
					'message' => $e->getMessage(),
				);
			}
		}

		return $result;
	}

	/**
	 * @param array $dump
	 * @param array $warnings
	 * @return array
	 */
	private function build_metadata(array $dump, array &$warnings)
	{
		$study_desc = array();

		try {
			$mapped = $this->parse_ddi_from_dump($dump);
			if (isset($mapped['study_desc']) && is_array($mapped['study_desc'])) {
				$study_desc = $mapped['study_desc'];
				if (isset($study_desc['title_statement']['idno'])) {
					unset($study_desc['title_statement']['idno']);
				}
				$study_desc = $this->prune_empty($study_desc);
			}
		} catch (Exception $e) {
			$warnings[] = 'DDI parse failed: '.$e->getMessage();
		}

		$title = $this->nested_get($study_desc, array('title_statement', 'title'));
		if ($title === null || $title === '') {
			$fallback = '';
			if (! empty($dump['study']['ident_title'])) {
				$fallback = $dump['study']['ident_title'];
			} elseif (! empty($dump['project']['title'])) {
				$fallback = $dump['project']['title'];
			}
			if ($fallback !== '') {
				if (! isset($study_desc['title_statement']) || ! is_array($study_desc['title_statement'])) {
					$study_desc['title_statement'] = array();
				}
				$study_desc['title_statement']['title'] = $fallback;
			}
		}

		$metadata = array(
			'study_desc' => $study_desc,
		);

		$additional = $this->build_additional(isset($dump['study']) ? $dump['study'] : array());
		if ($additional) {
			$metadata['additional'] = $additional;
		}

		$citations = $this->build_citations(isset($dump['citations']) ? $dump['citations'] : array(), $warnings);
		if ($citations) {
			$metadata['citations'] = $citations;
		}

		return $metadata;
	}

	/**
	 * @param array $dump
	 * @return array
	 */
	private function parse_ddi_from_dump(array $dump)
	{
		$row = $this->study_row_for_ddi($dump);
		$template = APPPATH.'templates/ddi_export_template.xml';
		$this->ci->ddi_study_export->load_template($template);
		$xml = $this->ci->ddi_study_export->to_ddi(array($row));

		$tmp = tempnam(sys_get_temp_dir(), 'dd_ddi_');
		if ($tmp === false || file_put_contents($tmp, $xml) === false) {
			throw new RuntimeException('Could not write temporary DDI file');
		}

		try {
			$this->ci->config->load('metadata_parser', true);
			$mappings = $this->ci->config->item('survey', 'metadata_parser', true);
			$reader = ReaderFactory::getReader('survey', $tmp, $mappings);
			$mapper = new NadaSurveyMapper($mappings);

			return $mapper->map($reader->get_study_meta());
		} finally {
			@unlink($tmp);
		}
	}

	/**
	 * Rebuild a dd_study-shaped row (positional JSON grids) for DDI_Study_Export.
	 *
	 * @param array $dump
	 * @return array
	 */
	private function study_row_for_ddi(array $dump)
	{
		$study = isset($dump['study']) && is_array($dump['study']) ? $dump['study'] : array();
		$row = $study;
		$grids = $this->ci->deposit_legacy_dump->get_grid_fields();

		foreach ($grids as $field => $columns) {
			$row[$field] = $this->encode_grid_for_ddi(isset($study[$field]) ? $study[$field] : array(), $columns);
		}

		if (empty($row['ident_ddp_id'])) {
			$row['ident_ddp_id'] = 'deposit-'.(int) $dump['project_id'];
		}
		if (empty($row['ident_title']) && ! empty($dump['project']['title'])) {
			$row['ident_title'] = $dump['project']['title'];
		}

		return $row;
	}

	/**
	 * @param mixed    $rows
	 * @param string[] $columns
	 * @return string
	 */
	private function encode_grid_for_ddi($rows, array $columns)
	{
		if (! is_array($rows) || empty($rows)) {
			return '';
		}

		$out = array();
		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			$pos = array();
			foreach ($columns as $name) {
				$pos[] = isset($row[$name]) ? $row[$name] : '';
			}
			$out[] = $pos;
		}

		return $out ? json_encode($out) : '';
	}

	/**
	 * @param array $study
	 * @return array
	 */
	private function build_additional(array $study)
	{
		$additional = array();
		foreach ($this->additional_keys as $key) {
			if (! array_key_exists($key, $study)) {
				continue;
			}
			$value = $study[$key];
			if ($this->is_empty_value($value)) {
				continue;
			}
			$additional[$key] = $value;
		}

		if (! empty($study['ident_ddp_id']) && trim((string) $study['ident_ddp_id']) !== '') {
			$additional['catalog_study_id'] = $study['ident_ddp_id'];
		}

		return $additional;
	}

	/**
	 * @param array $citations
	 * @param array $warnings
	 * @return array
	 */
	private function build_citations(array $citations, array &$warnings)
	{
		$out = array();
		foreach ($citations as $i => $row) {
			if (! is_array($row)) {
				continue;
			}
			$text = '';
			try {
				$text = trim((string) $this->ci->chicago_citation->format($row, isset($row['ctype']) ? $row['ctype'] : 'journal'));
			} catch (Exception $e) {
				$warnings[] = 'Citation '.($i + 1).' Chicago format failed: '.$e->getMessage();
			}

			if ($text === '') {
				$parts = array();
				if (! empty($row['title'])) {
					$parts[] = $row['title'];
				}
				if (! empty($row['pub_year'])) {
					$parts[] = $row['pub_year'];
				}
				if (! empty($row['doi'])) {
					$parts[] = $row['doi'];
				}
				$text = implode('. ', $parts);
			}

			if ($text === '') {
				continue;
			}

			$out[] = array(
				'format' => 'chicago',
				'text'   => $text,
			);
		}

		return $out;
	}

	/**
	 * @param array $submission
	 * @return array
	 */
	private function build_submission(array $submission)
	{
		$out = array();
		foreach ($submission as $key => $value) {
			if ($this->is_empty_value($value)) {
				continue;
			}
			$out[$key] = $value;
		}

		return $out;
	}

	/**
	 * @param int   $project_id
	 * @param array $metadata
	 * @param array $submission
	 * @return void
	 */
	private function save($project_id, array $metadata, array $submission)
	{
		$options = array(
			'metadata'        => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'submission'      => json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'schema_version'  => self::SCHEMA_VERSION_NEW,
		);

		if ($options['metadata'] === false || $options['submission'] === false) {
			throw new RuntimeException('JSON encode failed for project '.$project_id);
		}

		$this->ci->db->where('id', $project_id);
		if (! $this->ci->db->update('dd_projects', $options)) {
			throw new RuntimeException('Failed to update dd_projects id='.$project_id);
		}
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private function is_empty_value($value)
	{
		if ($value === null || $value === '') {
			return true;
		}
		if ($value === '--' || $value === '-') {
			return true;
		}
		if (is_array($value)) {
			return empty($value);
		}

		return false;
	}

	/**
	 * Drop empty DDI placeholders (blank strings, "--", empty arrays).
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function prune_empty($value)
	{
		if (! is_array($value)) {
			return $value;
		}

		$attr_only = array('required', 'form_no', 'form_uri');
		$out = array();
		foreach ($value as $key => $item) {
			if (is_array($item)) {
				$item = $this->prune_empty($item);
			}
			if ($this->is_empty_value($item)) {
				continue;
			}
			if (is_array($item) && $item && count(array_diff(array_keys($item), $attr_only)) === 0) {
				continue;
			}
			$out[$key] = $item;
		}

		return $out;
	}

	/**
	 * @param mixed $data
	 * @param array $path
	 * @return mixed
	 */
	private function nested_get($data, array $path)
	{
		$ref = $data;
		foreach ($path as $key) {
			if (! is_array($ref) || ! array_key_exists($key, $ref)) {
				return null;
			}
			$ref = $ref[$key];
		}

		return $ref;
	}
}
