<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cell and table notes for indicator studies (published from the Metadata Editor).
 *
 * - Data: footnote columns (DSD component metadata.display_role = footnote) hold note IDs, several separated by ';'.
 * - Metadata: study `data_notes` holds the text once per note: { id, note, scope?, column? }.
 *   scope 'table' notes apply to the whole indicator and are shown under every chart and table.
 *
 * Mirrors the editor's Indicator_data_notes (display subset).
 */
class Indicator_data_notes {

	const ID_SEPARATOR = ';';

	/**
	 * Split a footnote cell into unique, trimmed note IDs (order kept).
	 *
	 * @param mixed $value
	 * @return string[]
	 */
	public static function split_ids($value)
	{
		if ($value === null || is_array($value) || is_object($value)) {
			return array();
		}
		$out = array();
		foreach (explode(self::ID_SEPARATOR, (string) $value) as $part) {
			$id = trim($part);
			if ($id !== '' && !in_array($id, $out, true)) {
				$out[] = $id;
			}
		}
		return $out;
	}

	/**
	 * Names of DSD components marked as footnote columns.
	 *
	 * @param array $components data_structure_components rows (metadata as array or JSON string)
	 * @return string[]
	 */
	public static function footnote_column_names(array $components)
	{
		$names = array();
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name'])) {
				continue;
			}
			$meta = isset($c['metadata']) ? $c['metadata'] : null;
			if (is_string($meta) && $meta !== '') {
				$meta = json_decode($meta, true);
			}
			$role = is_array($meta) && isset($meta['display_role']) ? strtolower(trim((string) $meta['display_role'])) : '';
			if ($role === 'footnote') {
				$names[] = (string) $c['name'];
			}
		}
		return $names;
	}

	/** 'table' or 'cell' (default) for a data_notes entry. */
	public static function scope_of(array $note)
	{
		$s = isset($note['scope']) ? strtolower(trim((string) $note['scope'])) : '';
		return $s === 'table' ? 'table' : 'cell';
	}

	/**
	 * Notes per footnote column for one observation: { column => ids[] } (non-empty columns only).
	 *
	 * @param array    $row
	 * @param string[] $footnote_columns
	 * @return array
	 */
	public static function row_notes(array $row, array $footnote_columns)
	{
		$out = array();
		foreach ($footnote_columns as $col) {
			if (!array_key_exists($col, $row)) {
				continue;
			}
			$ids = self::split_ids($row[$col]);
			if (!empty($ids)) {
				$out[$col] = $ids;
			}
		}
		return $out;
	}

	/**
	 * Study data_notes from decoded study metadata.
	 *
	 * @param array|null $metadata
	 * @return array
	 */
	public static function notes_from_metadata($metadata)
	{
		if (!is_array($metadata) || empty($metadata['data_notes']) || !is_array($metadata['data_notes'])) {
			return array();
		}
		return array_values(array_filter($metadata['data_notes'], 'is_array'));
	}

	/**
	 * Dictionary of the notes used by chart records: { id => { note, column? } }; ids without text in $missing.
	 *
	 * @param array $records chart records with optional `notes` { column => ids[] }
	 * @param array $notes   study data_notes
	 * @param array $missing set to used ids not defined in data_notes
	 * @return array
	 */
	public static function dictionary_for_records(array $records, array $notes, &$missing)
	{
		$missing = array();
		$used = array();
		foreach ($records as $r) {
			if (empty($r['notes']) || !is_array($r['notes'])) {
				continue;
			}
			foreach ($r['notes'] as $ids) {
				foreach ((array) $ids as $id) {
					$used[(string) $id] = true;
				}
			}
		}
		$dictionary = array();
		foreach ($notes as $n) {
			$id = isset($n['id']) ? trim((string) $n['id']) : '';
			if ($id === '' || !isset($used[$id])) {
				continue;
			}
			$entry = array('note' => isset($n['note']) ? (string) $n['note'] : '');
			if (!empty($n['column'])) {
				$entry['column'] = (string) $n['column'];
			}
			$dictionary[$id] = $entry;
		}
		foreach (array_keys($used) as $id) {
			if (!isset($dictionary[$id])) {
				$missing[] = (string) $id;
			}
		}
		return $dictionary;
	}

	/**
	 * Table notes (scope = table) in data_notes order: [ { id, note } ].
	 *
	 * @param array $notes study data_notes
	 * @return array
	 */
	public static function table_notes(array $notes)
	{
		$out = array();
		foreach ($notes as $n) {
			if (self::scope_of($n) === 'table' && isset($n['note']) && trim((string) $n['note']) !== '') {
				$out[] = array('id' => isset($n['id']) ? (string) $n['id'] : '', 'note' => (string) $n['note']);
			}
		}
		return $out;
	}
}
