<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dctype_model
 *
 * All dctype data is read from the codelists framework (codelist name = 'dctypes').
 * The dctypes / dctype_translations tables are never used.
 * Falls back to external_resources config when the codelist is not seeded.
 */
class Dctype_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	private function _codelist_id()
	{
		$this->load->model('Codelist_model');
		$codelist = $this->Codelist_model->get_codelist_by_name('dctypes');
		return $codelist ? (int) $codelist['id'] : null;
	}

	private function _item_by_code($codelist_id, $code)
	{
		$row = $this->db->get_where('codelist_item', [
			'codelist_id' => (int) $codelist_id,
			'code'        => $code,
		])->row_array();
		return $row ?: null;
	}

	// -------------------------------------------------------------------------
	// Read
	// -------------------------------------------------------------------------

	/**
	 * Get all dctypes as a flat code => title map (English default).
	 *
	 * @return array [ code => title ]
	 */
	public function get_flat()
	{
		return $this->get_flat_for_lang('en');
	}

	/**
	 * Get all dctypes as a flat code => title map for a specific language.
	 * Source: codelists framework → external_resources config fallback.
	 *
	 * @param string $lang ISO 639-1 language code (e.g. 'en', 'es')
	 * @return array [ code => title ]
	 */
	public function get_flat_for_lang($lang = 'en')
	{
		try {
			$this->load->model('Codelist_item_model');
			$id = $this->_codelist_id();
			if ($id) {
				$items = $this->Codelist_item_model->get_items_by_codelist($id, true);
				if (!empty($items)) {
					$out = array();
					foreach ($items as $item) {
						$out[$item['code']] = !empty($item['translations'][$lang])
							? $item['translations'][$lang]
							: $item['title'];
					}
					return $out;
				}
			}
		} catch (\Throwable $e) {
			// fall through
		}
		$this->load->config('external_resources');
		return $this->config->item('dctypes', 'external_resources') ?: array();
	}

	/**
	 * Get dctype groups as a group_name => [codes] map.
	 * Source: codelists framework → external_resources config fallback.
	 *
	 * @return array [ group_name => [code, ...] ]
	 */
	public function get_groups()
	{
		try {
			$this->load->model('Codelist_model');
			$this->load->model('Codelist_item_model');
			$this->load->model('Codelist_group_model');

			$codelist = $this->Codelist_model->get_codelist_by_name('dctypes');
			if ($codelist) {
				$id     = (int) $codelist['id'];
				$items  = $this->Codelist_item_model->get_items_by_codelist($id, false);
				$groups = $this->Codelist_group_model->get_groups_by_codelist($id, true);

				$item_map = array();
				foreach ($items as $item) {
					$item_map[$item['id']] = $item['code'];
				}

				$out = array();
				foreach ($groups as $group) {
					$codes = array();
					foreach ($group['item_ids'] as $item_id) {
						if (isset($item_map[$item_id])) {
							$codes[] = $item_map[$item_id];
						}
					}
					$out[$group['name']] = $codes;
				}
				return $out;
			}
		} catch (\Throwable $e) {
			// fall through
		}
		$this->load->config('external_resources');
		return $this->config->item('dctype_groups', 'external_resources') ?: array();
	}

	/**
	 * Get dctype group translations as group_name => [lang => title] map.
	 * Source: codelists framework.
	 *
	 * @return array [ group_name => [ lang => title ] ]
	 */
	public function get_group_translations()
	{
		try {
			$this->load->model('Codelist_model');
			$this->load->model('Codelist_group_model');

			$codelist = $this->Codelist_model->get_codelist_by_name('dctypes');
			if (!$codelist) {
				return array();
			}
			$groups = $this->Codelist_group_model->get_groups_by_codelist((int) $codelist['id'], true);
			$out = array();
			foreach ($groups as $group) {
				if (!empty($group['translations'])) {
					$out[$group['name']] = $group['translations'];
				}
			}
			return $out;
		} catch (Exception $e) {
			return array();
		}
	}

	/**
	 * Get display label for a dctype in a given language.
	 * Source: codelists framework → item title → code as final fallback.
	 *
	 * @param int|string $id_or_code codelist_item.id (int) or dctype code (string)
	 * @param string $lang ISO language code
	 * @return string
	 */
	public function get_label_for_lang($id_or_code, $lang)
	{
		try {
			$this->load->model('Codelist_item_model');
			if (is_numeric($id_or_code)) {
				$item = $this->Codelist_item_model->get_item_by_id((int) $id_or_code, true);
			} else {
				$cl_id = $this->_codelist_id();
				$item  = $cl_id ? $this->_item_by_code($cl_id, $id_or_code) : null;
				if ($item) {
					$item['translations'] = $this->Codelist_item_model->get_item_translations($item['id']);
				}
			}
			if ($item) {
				if (!empty($item['translations'][$lang])) return $item['translations'][$lang];
				if (!empty($item['title']))               return $item['title'];
				return $item['code'];
			}
		} catch (Exception $e) {}
		return is_string($id_or_code) ? $id_or_code : '';
	}

	// -------------------------------------------------------------------------
	// CRUD — all delegate to Codelist_item_model
	// -------------------------------------------------------------------------

	/**
	 * Get one dctype by id (codelist_item.id).
	 */
	public function get_by_id($id, $with_translations = true)
	{
		$this->load->model('Codelist_item_model');
		return $this->Codelist_item_model->get_item_by_id((int) $id, $with_translations);
	}

	/**
	 * Get one dctype by code.
	 */
	public function get_by_code($code)
	{
		try {
			$cl_id = $this->_codelist_id();
			return $cl_id ? $this->_item_by_code($cl_id, $code) : null;
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Create a dctype (adds item to the 'dctypes' codelist).
	 *
	 * @param array $data ['code' => string, 'title' => string]
	 * @return int New item id
	 * @throws Exception
	 */
	public function create_dctype($data)
	{
		$this->load->model('Codelist_item_model');
		$cl_id = $this->_codelist_id();
		if (!$cl_id) {
			throw new Exception('Dctypes codelist not found.');
		}
		return $this->Codelist_item_model->create_item($cl_id, $data);
	}

	/**
	 * Update a dctype.
	 *
	 * @param int $id codelist_item.id
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function update_dctype($id, $data)
	{
		$this->load->model('Codelist_item_model');
		return $this->Codelist_item_model->update_item((int) $id, $data);
	}

	/**
	 * Delete a dctype.
	 *
	 * @param int $id codelist_item.id
	 * @return bool
	 * @throws Exception
	 */
	public function delete_dctype($id)
	{
		$this->load->model('Codelist_item_model');
		return $this->Codelist_item_model->delete_item((int) $id);
	}

	// -------------------------------------------------------------------------
	// Translations — delegate to Codelist_item_model
	// -------------------------------------------------------------------------

	/**
	 * Get translations for one dctype (lang => title).
	 *
	 * @param int $item_id codelist_item.id
	 * @return array [ lang => title ]
	 */
	public function get_translations($item_id)
	{
		$this->load->model('Codelist_item_model');
		return $this->Codelist_item_model->get_item_translations((int) $item_id);
	}

	/**
	 * Set (upsert) one translation for a dctype.
	 *
	 * @param int $item_id codelist_item.id
	 * @param string $lang
	 * @param string $title
	 */
	public function set_translation($item_id, $lang, $title)
	{
		$this->load->model('Codelist_item_model');
		$this->Codelist_item_model->set_item_translation((int) $item_id, $lang, $title);
	}

	/**
	 * Delete one translation.
	 *
	 * @param int $item_id codelist_item.id
	 * @param string $lang
	 */
	public function delete_translation($item_id, $lang)
	{
		$this->load->model('Codelist_item_model');
		$this->Codelist_item_model->delete_item_translation((int) $item_id, $lang);
	}
}
