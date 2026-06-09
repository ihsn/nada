<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin-only JSON metadata attached to catalog studies (separate from surveys.metadata).
 */
class Study_admin_metadata_model extends CI_Model {

	private $table = 'study_admin_metadata';

	private $fields = array(
		'sid',
		'metadata',
		'created_by',
		'changed_by',
		'created',
		'changed',
	);

	/** @var int Maximum encoded JSON size in bytes */
	private $max_metadata_bytes = 1048576;

	public function get_row($sid)
	{
		$row = $this->db->from($this->table)->where('sid', (int) $sid)->get()->row_array();
		if (! is_array($row)) {
			return null;
		}
		return $this->decode_row($row);
	}

	public function get_metadata($sid)
	{
		$row = $this->get_row($sid);
		if (! is_array($row) || ! isset($row['metadata']) || ! is_array($row['metadata'])) {
			return array();
		}
		return $row['metadata'];
	}

	public function exists($sid)
	{
		$this->db->select('sid');
		$this->db->from($this->table);
		$this->db->where('sid', (int) $sid);
		return $this->db->count_all_results() > 0;
	}

	public function replace($sid, array $metadata, $user_id = null)
	{
		$this->assert_metadata_payload($metadata);

		$sid = (int) $sid;
		$now = time();
		$data = array(
			'metadata'   => json_encode($metadata),
			'changed'    => $now,
			'changed_by' => $user_id ? (int) $user_id : null,
		);

		if ($this->exists($sid)) {
			$this->db->where('sid', $sid);
			$this->db->update($this->table, $data);
		} else {
			$data['sid'] = $sid;
			$data['created'] = $now;
			$data['created_by'] = $user_id ? (int) $user_id : null;
			$this->db->insert($this->table, $data);
		}

		return $this->get_row($sid);
	}

	public function delete($sid)
	{
		$this->db->where('sid', (int) $sid);
		return $this->db->delete($this->table);
	}

	private function decode_row($row)
	{
		if (isset($row['metadata']) && is_string($row['metadata'])) {
			$decoded = json_decode($row['metadata'], true);
			$row['metadata'] = is_array($decoded) ? $decoded : array();
		} elseif (! isset($row['metadata']) || ! is_array($row['metadata'])) {
			$row['metadata'] = array();
		}
		return $row;
	}

	private function assert_metadata_payload(array $metadata)
	{
		if ($this->is_metadata_object($metadata) === false) {
			throw new Exception('METADATA_MUST_BE_OBJECT');
		}

		$encoded = json_encode($metadata);
		if ($encoded === false) {
			throw new Exception('METADATA_NOT_JSON_SERIALIZABLE');
		}
		if (strlen($encoded) > $this->max_metadata_bytes) {
			throw new Exception('METADATA_TOO_LARGE');
		}
	}

	private function is_metadata_object(array $metadata)
	{
		if ($metadata === array()) {
			return true;
		}
		if (function_exists('array_is_list')) {
			return ! array_is_list($metadata);
		}
		return array_keys($metadata) !== range(0, count($metadata) - 1);
	}
}
