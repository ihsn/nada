<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Citation flags (citations.flag) from the codelist NADA:citation_flags.
 *
 * The codelist is seeded by migration 20261111120001_citations_csl in draft status, so admins can add,
 * rename and reorder flags in the Codelists admin. The latest version of the codelist is used (a site that
 * publishes it adds flags by creating a new version). Same pattern as Dctype_model.
 * Falls back to the built-in flags when the codelist is missing.
 */
class Citation_flag_model extends CI_Model {

	const CODELIST_NAME = 'citation_flags';
	const CODELIST_IDNO = 'NADA_citation_flags_1.0';

	/** Built-in flags (code => English title), used when the codelist is not seeded. */
	public static $defaults = array(
		'ds_unclear'     => 'Unclear related dataset',
		'incomplete'     => 'Incomplete',
		'tobe_checked'   => 'To be checked',
		'duplicate'      => 'Duplicate',
		'back_to_editor' => 'Back to editor',
	);

	private $cache = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * The codelist row (latest version), or null.
	 */
	public function codelist_row()
	{
		if (array_key_exists('row', $this->cache)) {
			return $this->cache['row'];
		}
		$row = null;
		try {
			if ($this->db->table_exists('codelists')) {
				$this->load->model('Codelist_model');
				$row = $this->Codelist_model->get_codelist_by_name(self::CODELIST_NAME);
				if (!$row) {
					$row = $this->Codelist_model->get_codelist_by_idno(self::CODELIST_IDNO);
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Citation_flag_model: ' . $e->getMessage());
			$row = null;
		}
		return $this->cache['row'] = $row ?: null;
	}

	/**
	 * Flags as code => title, in codelist order. Titles use the item translation for $lang when there is one.
	 *
	 * @param string $lang ISO 639-1 language code
	 * @return array
	 */
	public function get_flat_for_lang($lang = 'en')
	{
		$key = 'flat_' . $lang;
		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$codelist = $this->codelist_row();
		if (!$codelist) {
			return $this->cache[$key] = self::$defaults;
		}

		// items come ordered by sort_order; translations as [lang => title]
		$this->load->model('Codelist_item_model');
		$items = $this->Codelist_item_model->get_items_by_codelist((int) $codelist['id'], true);

		$out = array();
		foreach ($items as $item) {
			$title = isset($item['title']) && $item['title'] !== '' ? $item['title'] : $item['code'];
			if (!empty($item['translations'][$lang])) {
				$title = $item['translations'][$lang];
			}
			$out[$item['code']] = $title;
		}
		return $this->cache[$key] = $out;
	}

	/**
	 * True for '' (no flag) and for codes in the codelist.
	 */
	public function is_valid_code($code)
	{
		$code = trim((string) $code);
		if ($code === '') {
			return true;
		}
		return array_key_exists($code, $this->get_flat_for_lang('en'));
	}

	/**
	 * Label for a code; codes not in the codelist (deleted later) are returned as is.
	 */
	public function label($code, $lang = 'en')
	{
		$flags = $this->get_flat_for_lang($lang);
		return isset($flags[$code]) ? $flags[$code] : (string) $code;
	}

	/**
	 * Codelist id, for the "Manage flags" link to the Codelists admin; null when not seeded.
	 */
	public function codelist_id()
	{
		$row = $this->codelist_row();
		return $row ? (int) $row['id'] : null;
	}
}
