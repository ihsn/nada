<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stored settings for Admin > Settings > Site layout (table site_layout).
 *
 * One row per part and state:
 *   part      header | footer | styles
 *   state     published | draft
 *   settings  the part's settings as one JSON document
 *
 * Only the admin pages and their preview pages read this table. Public pages read the published file
 * under {catalog_root}/cache/site_{part}/ and never query it.
 */
class Site_layout_model extends CI_Model {

	const TABLE = 'site_layout';

	/** Thrown by writes when the migration that creates the table has not run */
	const TABLE_MISSING = 'SITE_LAYOUT_TABLE_MISSING';

	/** @var array "part/state" => row array or null */
	private $rows = array();

	/** @var bool|null */
	private $ready = null;

	public function table_exists()
	{
		if ($this->ready === null) {
			$this->ready = (bool) $this->db->table_exists(self::TABLE);
		}
		return $this->ready;
	}

	/** The stored settings JSON, or '' when there is none */
	public function get($part, $state)
	{
		$row = $this->get_row($part, $state);
		return $row ? trim((string) $row['settings']) : '';
	}

	/** The whole row, or null. Pass it to restore() to undo a save. */
	public function get_row($part, $state)
	{
		$key = $part . '/' . $state;
		if (!array_key_exists($key, $this->rows)) {
			$row = null;
			if ($this->table_exists()) {
				$query = $this->db
					->where('part', $part)
					->where('state', $state)
					->get(self::TABLE);
				$row = $query ? $query->row_array() : null;
			}
			$this->rows[$key] = $row ? $row : null;
		}
		return $this->rows[$key];
	}

	/** Insert or update the settings; throws when the table is missing or the write fails */
	public function save($part, $state, $settings, $user_id = null)
	{
		$this->require_table();
		$now = time();
		$user_id = $user_id ? (int) $user_id : null;

		if ($this->get_row($part, $state)) {
			$ok = $this->db
				->where('part', $part)
				->where('state', $state)
				->update(self::TABLE, array(
					'settings'   => (string) $settings,
					'changed'    => $now,
					'changed_by' => $user_id,
				));
		} else {
			$ok = $this->db->insert(self::TABLE, array(
				'part'       => $part,
				'state'      => $state,
				'settings'   => (string) $settings,
				'created'    => $now,
				'created_by' => $user_id,
				'changed'    => $now,
				'changed_by' => $user_id,
			));
		}
		$this->forget($part, $state);

		if (!$ok) {
			throw new Exception('SAVE_FAILED');
		}
	}

	/** Put back a row returned by get_row(); null removes the row */
	public function restore($part, $state, $row)
	{
		if (!$row) {
			return $this->delete($part, $state);
		}
		$this->require_table();
		$this->db->where('part', $part)->where('state', $state)->delete(self::TABLE);
		$ok = $this->db->insert(self::TABLE, array(
			'part'       => $part,
			'state'      => $state,
			'settings'   => (string) $row['settings'],
			'created'    => isset($row['created']) ? $row['created'] : null,
			'created_by' => isset($row['created_by']) ? $row['created_by'] : null,
			'changed'    => isset($row['changed']) ? $row['changed'] : null,
			'changed_by' => isset($row['changed_by']) ? $row['changed_by'] : null,
		));
		$this->forget($part, $state);
		return (bool) $ok;
	}

	/** Remove one state of a part, or both when $state is null. Nothing to do when the table is missing. */
	public function delete($part, $state = null)
	{
		if (!$this->table_exists()) {
			return true;
		}
		$this->db->where('part', $part);
		if ($state !== null) {
			$this->db->where('state', $state);
		}
		$ok = $this->db->delete(self::TABLE);
		$this->forget($part, $state);
		return (bool) $ok;
	}

	private function forget($part, $state = null)
	{
		foreach (array('published', 'draft') as $s) {
			if ($state === null || $state === $s) {
				unset($this->rows[$part . '/' . $s]);
			}
		}
	}

	private function require_table()
	{
		if (!$this->table_exists()) {
			throw new Exception(self::TABLE_MISSING);
		}
	}
}
