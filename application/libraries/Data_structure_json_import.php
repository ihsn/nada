<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import global DSD + codelists from JSON (data-structure-schema.json shape).
 */
class Data_structure_json_import {

	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model('Codelist_model');
		$this->CI->load->model('Codelist_item_model');
		$this->CI->load->model('Data_structure_model');
		$this->CI->load->model('Data_structure_component_model');
	}

	/**
	 * @param array $payload { structure: array, components: array, import_options?: array }
	 * @param array $options overwrite_codelists (bool), dry_run (bool), user_id (int|null) for component audit fields
	 * @return array
	 * @throws Exception
	 */
	public function import_from_array(array $payload, array $options = [])
	{
		$overwrite = !empty($options['overwrite_codelists']);
		$dryRun    = !empty($options['dry_run']);

		$errors = $this->_validate_payload($payload, $overwrite);
		if (!empty($errors)) {
			throw new Exception('VALIDATION_FAILED: ' . json_encode($errors));
		}

		$structure   = $payload['structure'];
		$components  = $payload['components'];
		$summary = [
			'dry_run'            => $dryRun,
			'data_structure'     => null,
			'components_created' => [],
			'codelists_created'  => [],
			'codelists_reused'   => [],
			'codelists_updated'  => [],
			'warnings'           => [],
		];

		if ($dryRun) {
			$summary['data_structure'] = $this->_preview_structure_row($structure);
			$summary['components_preview'] = count($components);
			return $summary;
		}

		$this->CI->db->trans_begin();
		try {
			$dsId = $this->CI->Data_structure_model->create_structure($structure);

			foreach ($components as $idx => $comp) {
				$codelistId = $this->_resolve_codelist_for_component($comp, $overwrite, $summary);

				$insert = $this->_component_to_create_row($comp, $codelistId);
				if (!empty($options['user_id'])) {
					$insert['created_by'] = (int) $options['user_id'];
					$insert['updated_by'] = (int) $options['user_id'];
				}
				$newCompId = $this->CI->Data_structure_component_model->create_component($dsId, $insert);
				$summary['components_created'][] = [
					'id'   => $newCompId,
					'name' => $insert['name'],
				];
			}

			if ($this->CI->db->trans_status() === false) {
				throw new Exception('Database transaction failed.');
			}
			$this->CI->db->trans_commit();

			$summary['data_structure'] = $this->CI->Data_structure_model->get_structure_by_id($dsId, true);
			return $summary;
		} catch (Exception $e) {
			$this->CI->db->trans_rollback();
			throw $e;
		}
	}

	/**
	 * @return array[] error objects
	 */
	protected function _validate_payload(array $payload, $overwrite)
	{
		$errors = [];
		if (empty($payload['structure']) || !is_array($payload['structure'])) {
			$errors[] = ['path' => 'structure', 'message' => 'Required object.'];
			return $errors;
		}
		if (!isset($payload['components']) || !is_array($payload['components'])) {
			$errors[] = ['path' => 'components', 'message' => 'Required array.'];
			return $errors;
		}

		$st = $payload['structure'];
		$name = isset($st['name']) ? trim((string) $st['name']) : '';
		if ($name === '') {
			$errors[] = ['path' => 'structure.name', 'message' => 'Required.'];
		}
		$idno = isset($st['idno']) ? trim((string) $st['idno']) : '';
		if ($idno === '') {
			$errors[] = ['path' => 'structure.idno', 'message' => 'Required for JSON import.'];
		}

		$agency  = isset($st['agency']) && trim((string) $st['agency']) !== '' ? trim((string) $st['agency']) : Data_structure_model::DEFAULT_AGENCY;
		$version = isset($st['version']) && trim((string) $st['version']) !== '' ? trim((string) $st['version']) : Data_structure_model::DEFAULT_VERSION;

		if ($name !== '' && $this->CI->Data_structure_model->get_structure_by_identity($name, $agency, $version)) {
			$errors[] = ['path' => 'structure', 'message' => "Data structure already exists for agency '{$agency}', name '{$name}', version '{$version}'."];
		}
		if ($idno !== '' && $this->CI->Data_structure_model->get_structure_by_idno($idno)) {
			$errors[] = ['path' => 'structure.idno', 'message' => "idno '{$idno}' already exists."];
		}

		$names = [];
		foreach ($payload['components'] as $idx => $row) {
			if (!is_array($row)) {
				$errors[] = ['path' => "components[{$idx}]", 'message' => 'Must be an object.'];
				continue;
			}
			$cname = isset($row['name']) ? trim((string) $row['name']) : '';
			if ($cname === '') {
				$errors[] = ['path' => "components[{$idx}].name", 'message' => 'Required.'];
			} elseif (isset($names[$cname])) {
				$errors[] = ['path' => "components[{$idx}].name", 'message' => 'Duplicate component name in payload.'];
			} else {
				$names[$cname] = true;
			}
			$ct = isset($row['column_type']) ? trim((string) $row['column_type']) : '';
			if ($ct === '' || !in_array($ct, Data_structure_component_model::$allowed_column_types, true)) {
				$errors[] = ['path' => "components[{$idx}].column_type", 'message' => 'Invalid or missing column_type.'];
			}
			if (isset($row['data_type']) && $row['data_type'] !== null && trim((string) $row['data_type']) !== '') {
				$dt = trim((string) $row['data_type']);
				if (!in_array($dt, Data_structure_component_model::$allowed_data_types, true)) {
					$errors[] = ['path' => "components[{$idx}].data_type", 'message' => 'Invalid data_type.'];
				}
			}

			$cl = isset($row['codelist']) && is_array($row['codelist']) ? $row['codelist'] : null;
			$clIdno = $cl !== null && isset($cl['idno']) ? trim((string) $cl['idno']) : '';
			$clName = $cl !== null && isset($cl['name']) ? trim((string) $cl['name']) : '';
			$clItems = $cl !== null && isset($cl['items']) && is_array($cl['items']) ? $cl['items'] : [];
			$hasRef = !empty($row['code_list_reference']) && is_array($row['code_list_reference']);

			if ($hasRef) {
				$errors[] = ['path' => "components[{$idx}].code_list_reference", 'message' => 'External code list reference is not resolved by JSON import.'];
			}

			$needs = in_array($ct, ['dimension', 'geography'], true);
			if ($needs) {
				if ($cl === null) {
					$errors[] = ['path' => "components[{$idx}].codelist", 'message' => 'dimension/geography requires a codelist object.'];
				} else {
					$existingByIdno = $clIdno !== '' ? $this->CI->Codelist_model->get_codelist_by_idno($clIdno) : null;
					if ($existingByIdno) {
						if (!$overwrite && count($clItems) > 0) {
							$errors[] = ['path' => "components[{$idx}].codelist.items", 'message' => 'Cannot supply codelist.items for an existing codelist unless overwrite_codelists is true.'];
						}
					} else {
						if ($clName === '') {
							$errors[] = ['path' => "components[{$idx}].codelist.name", 'message' => 'Required to create a new codelist.'];
						}
						if ($clIdno === '') {
							$errors[] = ['path' => "components[{$idx}].codelist.idno", 'message' => 'Required to create a new codelist.'];
						}
					}

					foreach ($clItems as $j => $item) {
						if (!is_array($item)) {
							$errors[] = ['path' => "components[{$idx}].codelist.items[{$j}]", 'message' => 'Must be an object.'];
							continue;
						}
						$code = isset($item['code']) ? trim((string) $item['code']) : '';
						if ($code === '') {
							$errors[] = ['path' => "components[{$idx}].codelist.items[{$j}].code", 'message' => 'Required.'];
						} elseif (strlen($code) > 64) {
							$errors[] = ['path' => "components[{$idx}].codelist.items[{$j}].code", 'message' => 'Code exceeds 64 characters.'];
						}
					}
				}
			}
		}

		return $errors;
	}

	protected function _preview_structure_row(array $structure)
	{
		$agency  = isset($structure['agency']) && trim((string) $structure['agency']) !== '' ? trim((string) $structure['agency']) : Data_structure_model::DEFAULT_AGENCY;
		$version = isset($structure['version']) && trim((string) $structure['version']) !== '' ? trim((string) $structure['version']) : Data_structure_model::DEFAULT_VERSION;
		$name    = isset($structure['name']) ? trim((string) $structure['name']) : '';
		$idno    = isset($structure['idno']) ? trim((string) $structure['idno']) : '';
		if ($idno === '') {
			$idno = Data_structure_model::make_idno($agency, $name, $version);
		}
		return [
			'name'    => $name,
			'agency'  => $agency,
			'version' => $version,
			'idno'    => $idno,
		];
	}

	/**
	 * @param array $comp payload component
	 * @param bool  $overwrite
	 * @param array $summary mutated
	 * @return int|null codelist_id
	 * @throws Exception
	 */
	protected function _resolve_codelist_for_component(array $comp, $overwrite, array &$summary)
	{
		$ct = isset($comp['column_type']) ? trim((string) $comp['column_type']) : '';
		$needs = in_array($ct, ['dimension', 'geography'], true);
		if (!$needs) {
			return null;
		}

		$cl = isset($comp['codelist']) && is_array($comp['codelist']) ? $comp['codelist'] : [];
		$clIdno  = isset($cl['idno']) ? trim((string) $cl['idno']) : '';
		$clName  = isset($cl['name']) ? trim((string) $cl['name']) : '';
		$items   = isset($cl['items']) && is_array($cl['items']) ? $cl['items'] : [];
		$agency  = isset($cl['agency']) && trim((string) $cl['agency']) !== '' ? trim((string) $cl['agency']) : Codelist_model::DEFAULT_AGENCY;
		$version = isset($cl['version']) && trim((string) $cl['version']) !== '' ? trim((string) $cl['version']) : Codelist_model::DEFAULT_VERSION;

		$existing = null;
		if ($clIdno !== '') {
			$existing = $this->CI->Codelist_model->get_codelist_by_idno($clIdno);
		}
		if (!$existing && $clName !== '') {
			$existing = $this->CI->Codelist_model->get_codelist_by_name($clName, $agency, $version);
		}

		if ($existing) {
			$cid = (int) $existing['id'];
			if ($overwrite && count($items) > 0) {
				$this->CI->Codelist_item_model->delete_all_items_for_codelist($cid);
				$this->_insert_code_list_items($cid, $items);
				$summary['codelists_updated'][] = ['id' => $cid, 'idno' => $existing['idno']];
			} else {
				if (count($items) > 0 && !$overwrite) {
					$summary['warnings'][] = [
						'message'     => 'Codelist already exists; items not applied (set overwrite_codelists or omit items).',
						'codelist_id' => $cid,
					];
				}
				$summary['codelists_reused'][] = ['id' => $cid, 'idno' => $existing['idno']];
			}
			return $cid;
		}

		if ($clName === '') {
			throw new Exception('codelist.name is required to create a new codelist.');
		}

		$create = [
			'name'        => $clName,
			'agency'      => $agency,
			'version'     => $version,
			'description' => isset($cl['description']) ? trim((string) $cl['description']) : null,
		];
		if ($clIdno !== '') {
			$create['idno'] = $clIdno;
		}
		$newId = $this->CI->Codelist_model->create_codelist($create);
		$this->_insert_code_list_items($newId, $items);
		$row = $this->CI->Codelist_model->get_codelist_by_id($newId);
		$summary['codelists_created'][] = ['id' => $newId, 'idno' => $row ? $row['idno'] : null];
		return $newId;
	}

	protected function _insert_code_list_items($codelistId, array $items)
	{
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$code = isset($item['code']) ? trim((string) $item['code']) : '';
			if ($code === '') {
				continue;
			}
			$title = null;
			if (isset($item['label']) && $item['label'] !== '' && $item['label'] !== null) {
				$title = is_string($item['label']) ? trim($item['label']) : (string) $item['label'];
			}
			$sortOrder = isset($item['sort_order']) ? (int) $item['sort_order'] : 0;
			$parentId  = null;
			if (isset($item['parent_id']) && $item['parent_id'] !== '' && $item['parent_id'] !== null && (int) $item['parent_id'] > 0) {
				$parentId = (int) $item['parent_id'];
			}
			$this->CI->Codelist_item_model->create_item((int) $codelistId, [
				'code'        => $code,
				'title'       => $title,
				'sort_order'  => $sortOrder,
				'parent_id'   => $parentId,
			]);
		}
	}

	/**
	 * Strip import-only keys; fold extras into metadata.
	 *
	 * @param int|null $codelistId
	 * @return array
	 */
	protected function _component_to_create_row(array $comp, $codelistId)
	{
		$meta = [];
		if (!empty($comp['metadata']) && is_array($comp['metadata'])) {
			$meta = $comp['metadata'];
		}
		if (!empty($comp['paired_time_column'])) {
			$meta['paired_time_column'] = trim((string) $comp['paired_time_column']);
		}
		if (!empty($comp['value_label_column'])) {
			$meta['value_label_column'] = trim((string) $comp['value_label_column']);
		}
		if (!empty($comp['attachment_level'])) {
			$meta['attachment_level'] = trim((string) $comp['attachment_level']);
		}
		if (!empty($comp['assignment_status'])) {
			$meta['assignment_status'] = trim((string) $comp['assignment_status']);
		}

		$row = [
			'name'               => trim((string) $comp['name']),
			'label'               => isset($comp['label']) ? trim((string) $comp['label']) : null,
			'description'         => isset($comp['description']) ? trim((string) $comp['description']) : null,
			'data_type'           => isset($comp['data_type']) && trim((string) $comp['data_type']) !== '' ? trim((string) $comp['data_type']) : null,
			'column_type'         => trim((string) $comp['column_type']),
			'time_period_format'  => isset($comp['time_period_format']) && trim((string) $comp['time_period_format']) !== '' ? trim((string) $comp['time_period_format']) : null,
			'sort_order'          => isset($comp['sort_order']) ? (int) $comp['sort_order'] : 0,
			'codelist_id'         => $codelistId !== null && $codelistId > 0 ? (int) $codelistId : null,
			'metadata'            => !empty($meta) ? $meta : null,
		];
		if ($row['label'] === '') {
			$row['label'] = null;
		}
		if ($row['description'] === '') {
			$row['description'] = null;
		}

		return $row;
	}
}
