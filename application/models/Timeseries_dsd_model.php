<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalogue DSD resolution and export helpers for indicator timeseries (SQL / relational only).
 *
 * Study→DSD link, structure/components loading, JSON export shaping, and reference normalization
 * live here so Timeseries_mongo_model stays MongoDB-focused.
 */
class Timeseries_dsd_model extends CI_Model {

	/**
	 * Resolve the linked DSD id for a study by reading surveys.data_structure_id.
	 *
	 * @param int $sid surveys.id
	 * @return int|null
	 */
	public function extract_dsd_id_for_sid($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return null;
		}

		$q = $this->db->select('data_structure_id')
			->from('surveys')
			->where('id', $sid)
			->get();
		$row = $q ? $q->row_array() : null;
		if (!$row || empty($row['data_structure_id'])) {
			return null;
		}
		return (int) $row['data_structure_id'];
	}

	/**
	 * @param int $dsd_id data_structures.id
	 * @return array{structure:array,components:array}|null
	 */
	public function load_dsd_bundle($dsd_id)
	{
		$dsd_id = (int) $dsd_id;
		if ($dsd_id <= 0) {
			return null;
		}
		$this->load->model('Data_structure_model');
		$this->load->model('Data_structure_component_model');
		$structure = $this->Data_structure_model->get_structure_by_id($dsd_id, false);
		if (!$structure) {
			return null;
		}
		$components = $this->Data_structure_component_model->get_components_by_structure_id($dsd_id);
		return [
			'structure'  => $structure,
			'components' => $components,
		];
	}

	/**
	 * Stable string when identity rules change (rehash detection).
	 *
	 * @param array $structure_row data_structures row
	 * @return string
	 */
	public function build_key_spec_revision(array $structure_row)
	{
		if (!empty($structure_row['content_hash']) && is_string($structure_row['content_hash'])) {
			return trim($structure_row['content_hash']);
		}
		$id = isset($structure_row['id']) ? (int) $structure_row['id'] : 0;
		$u  = isset($structure_row['updated']) ? (int) $structure_row['updated'] : 0;
		return 'id:' . $id . '|u:' . $u;
	}

	/**
	 * @param int $sid surveys.id
	 * @return array{dsd_id:int,structure:array,components:array,key_spec_rev:string}|null
	 */
	public function resolve_dsd_for_sid($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return null;
		}
		$dsd_id = $this->extract_dsd_id_for_sid($sid);
		if ($dsd_id === null || $dsd_id <= 0) {
			return null;
		}
		$bundle = $this->load_dsd_bundle($dsd_id);
		if ($bundle === null) {
			return null;
		}
		$bundle['dsd_id']        = $dsd_id;
		$bundle['key_spec_rev'] = $this->build_key_spec_revision($bundle['structure']);
		return $bundle;
	}

	/**
	 * Canonical object reference for exports: { idno, agency, name, version } from catalogue when possible.
	 *
	 * @param int $sid surveys.id
	 * @return array{idno:string,agency:string,name:string,version:string,uri?:string,notes?:string}|null
	 */
	public function normalized_data_structure_reference_for_sid($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return null;
		}
		$this->load->model('Dataset_model');
		$this->load->model('Data_structure_model');

		$meta = $this->Dataset_model->get_metadata($sid);
		if (!is_array($meta)) {
			$meta = [];
		}
		$ref = isset($meta['data_structure_reference']) ? $meta['data_structure_reference'] : null;

		$from_meta = [
			'idno' => '',
			'agency' => '',
			'name' => '',
			'version' => '',
			'uri' => '',
			'notes' => '',
		];

		if (is_array($ref)) {
			foreach (['idno', 'agency', 'name', 'version', 'uri', 'notes'] as $k) {
				if (!empty($ref[$k])) {
					$from_meta[$k] = trim((string) $ref[$k]);
				}
			}
		} elseif (is_string($ref) && trim($ref) !== '') {
			$from_meta['idno'] = trim($ref);
		}

		$structure_row = null;

		$bundle = $this->resolve_dsd_for_sid($sid);
		if ($bundle !== null && isset($bundle['structure']) && is_array($bundle['structure'])) {
			$structure_row = $bundle['structure'];
		}

		if ($structure_row === null) {
			$dsd_id = $this->extract_dsd_id_for_sid($sid);
			if ($dsd_id !== null && $dsd_id > 0) {
				$structure_row = $this->Data_structure_model->get_structure_by_id($dsd_id, false);
			}
		}

		if ($structure_row === null && $from_meta['idno'] !== '') {
			$structure_row = $this->Data_structure_model->get_structure_by_idno($from_meta['idno']);
		}

		if ($structure_row === null && $from_meta['idno'] !== '') {
			$alt_idno = str_replace('.', '_', $from_meta['idno']);
			if ($alt_idno !== $from_meta['idno']) {
				$structure_row = $this->Data_structure_model->get_structure_by_idno($alt_idno);
			}
		}

		if ($structure_row === null && $from_meta['idno'] !== '') {
			$parsed = $this->_parse_data_structure_reference_string_to_identity($from_meta['idno']);
			if ($parsed !== null) {
				$structure_row = $this->Data_structure_model->get_structure_by_identity(
					$parsed['name'],
					$parsed['agency'],
					$parsed['version']
				);
			}
		}

		if ($structure_row !== null && is_array($structure_row)) {
			return $this->_with_optional_data_structure_reference_fields([
				'idno' => trim((string) ($structure_row['idno'] ?? '')),
				'agency' => trim((string) ($structure_row['agency'] ?? '')),
				'name' => trim((string) ($structure_row['name'] ?? '')),
				'version' => trim((string) ($structure_row['version'] ?? '')),
			], $from_meta);
		}

		if ($from_meta['idno'] === '') {
			return null;
		}

		return $this->_with_optional_data_structure_reference_fields([
			'idno' => $from_meta['idno'],
			'agency' => $from_meta['agency'],
			'name' => $from_meta['name'],
			'version' => $from_meta['version'],
		], $from_meta);
	}

	/**
	 * Attach optional uri/notes from stored metadata to a normalized reference.
	 *
	 * @param array $base
	 * @param array $from_meta
	 * @return array
	 */
	private function _with_optional_data_structure_reference_fields(array $base, array $from_meta)
	{
		foreach (['uri', 'notes'] as $k) {
			if (!empty($from_meta[$k])) {
				$base[$k] = trim((string) $from_meta[$k]);
			}
		}
		return $base;
	}

	/**
	 * Parse dotted reference strings such as Agency.Name.SemVer into identity parts for catalogue lookup.
	 *
	 * @param string $raw
	 * @return array{agency:string,name:string,version:string}|null
	 */
	private function _parse_data_structure_reference_string_to_identity($raw)
	{
		$raw = trim((string) $raw);
		if ($raw === '' || strpos($raw, '.') === false) {
			return null;
		}
		if (!preg_match('/^(.+)\.(\d+\.\d+\.\d+)$/', $raw, $m)) {
			return null;
		}
		$prefix = $m[1];
		$version = $m[2];
		if (!preg_match('/^([^.]+)\.(.+)$/', $prefix, $p)) {
			return null;
		}
		$agency = trim($p[1]);
		$name = trim($p[2]);
		if ($agency === '' || $name === '' || $version === '') {
			return null;
		}

		return [
			'agency' => $agency,
			'name' => $name,
			'version' => $version,
		];
	}

	/**
	 * Attach full codelist (+ items + groups) to one component when codelist_id is set.
	 *
	 * @param array $component data_structure_components row
	 * @return array
	 */
	public function enrich_component_with_codelist(array $component)
	{
		if (empty($component['codelist_id'])) {
			$component['codelist'] = null;
			return $component;
		}
		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');
		$this->load->model('Codelist_group_model');
		$cl = $this->Codelist_model->get_codelist_by_id((int) $component['codelist_id']);
		if ($cl) {
			$id = (int) $cl['id'];
			$cl['items'] = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$cl['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$component['codelist'] = $cl;
		} else {
			$component['codelist'] = null;
		}
		return $component;
	}

	/**
	 * Sanitized structure + components with codelists (same shape as public API …/data/{idno}/export).
	 *
	 * @param array $structure data_structures row
	 * @param array $components data_structure_components rows
	 * @return array{data_structure:array,components:array}
	 */
	public function build_inline_dsd_export_from_structure_components(array $structure, array $components)
	{
		foreach ($components as &$c) {
			if (!is_array($c)) {
				continue;
			}
			$c = $this->enrich_component_with_codelist($c);
		}
		unset($c);

		return $this->sanitize_dsd_export_payload([
			'data_structure' => $structure,
			'components' => $components,
		]);
	}

	/**
	 * Sanitized structure + components with codelists for a study (same shape as public API …/data/{idno}/export).
	 *
	 * @param int $sid surveys.id
	 * @return array{data_structure:array,components:array}|null
	 */
	public function build_inline_dsd_export_bundle_for_sid($sid)
	{
		$ctx = $this->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			return null;
		}
		$components = isset($ctx['components']) && is_array($ctx['components']) ? $ctx['components'] : [];

		return $this->build_inline_dsd_export_from_structure_components($ctx['structure'], $components);
	}

	/**
	 * Strip internal/export-irrelevant fields from DSD export payload (shared with public REST API).
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize_dsd_export_payload($value)
	{
		if (!is_array($value)) {
			return $value;
		}
		$is_list = array_keys($value) === range(0, count($value) - 1);
		if ($is_list) {
			$out = [];
			foreach ($value as $item) {
				$out[] = $this->sanitize_dsd_export_payload($item);
			}
			return $out;
		}
		$blocked = [
			'content_hash' => true,
			'metadata' => true,
			'created_by' => true,
			'updated_by' => true,
			'pid' => true,
		];
		$out = [];
		foreach ($value as $k => $v) {
			$key = (string) $k;
			if ($key === 'id' || isset($blocked[$key]) || preg_match('/_id$/', $key)) {
				continue;
			}
			$sanitized = $this->sanitize_dsd_export_payload($v);
			if (($key === 'created' || $key === 'updated' || $key === 'changed')
				&& (is_string($sanitized) || is_numeric($sanitized))) {
				$sanitized = $this->_dsd_export_datetime_to_utc_string($sanitized);
			}
			if ($key === 'translations' && is_array($sanitized) && empty($sanitized)) {
				continue;
			}
			$out[$key] = $sanitized;
		}
		return $out;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function _dsd_export_datetime_to_utc_string($value)
	{
		$raw = is_string($value) ? trim($value) : (string) $value;
		if ($raw === '') {
			return $value;
		}
		$ts = null;
		if (preg_match('/^\d+$/', $raw)) {
			$num = (float) $raw;
			$ts = strlen($raw) >= 13 ? (int) floor($num / 1000) : (int) $num;
		} else {
			$parsed = strtotime($raw);
			$ts = $parsed === false ? null : $parsed;
		}
		if ($ts === null) {
			return $value;
		}
		return gmdate('Y-m-d\TH:i:s\Z', $ts);
	}
}
