<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catalog display settings per study (separate from surveys.metadata, not published).
 *
 * For indicators (timeseries) this is the default view of the chart and table pages,
 * validated against application/schemas/timeseries-presentation-schema.json.
 */
class Study_presentation_model extends CI_Model {

	private $table = 'study_presentation';

	private $schema_file = 'application/schemas/timeseries-presentation-schema.json';

	/**
	 * Stored row with decoded settings and the changing user's name, or null.
	 *
	 * @return array{sid:int,settings:array,created:int|null,changed:int|null,changed_by:int|null,changed_by_name:string|null}|null
	 */
	public function get_row($sid)
	{
		if (! $this->db->table_exists($this->table)) {
			return null;
		}
		$this->db->select('p.sid, p.settings, p.created, p.changed, p.changed_by, u.username AS changed_by_name');
		$this->db->from($this->table . ' p');
		$this->db->join('users u', 'u.id = p.changed_by', 'left');
		$this->db->where('p.sid', (int) $sid);
		$row = $this->db->get()->row_array();
		if (! is_array($row)) {
			return null;
		}
		$decoded = json_decode((string) $row['settings'], true);
		$row['settings'] = is_array($decoded) ? $decoded : array();
		return $row;
	}

	/** Decoded settings, or null when the study has none. */
	public function get_settings($sid)
	{
		$row = $this->get_row($sid);
		return $row && ! empty($row['settings']) ? $row['settings'] : null;
	}

	/**
	 * Replace the study's settings; empty settings remove them. Validate first with {@see validate}.
	 */
	public function replace($sid, array $settings, $user_id = null)
	{
		$sid = (int) $sid;
		if (empty($settings)) {
			$this->delete($sid);
			return null;
		}
		if (! $this->db->table_exists($this->table)) {
			throw new Exception('The study_presentation table is missing; run the database migrations.');
		}

		$now = time();
		$data = array(
			'settings'   => json_encode($settings),
			'changed'    => $now,
			'changed_by' => $user_id ? (int) $user_id : null,
		);

		$exists = $this->db->where('sid', $sid)->count_all_results($this->table) > 0;
		if ($exists) {
			$this->db->where('sid', $sid);
			$this->db->update($this->table, $data);
		} else {
			$data['sid'] = $sid;
			$data['created'] = $now;
			$data['created_by'] = $data['changed_by'];
			$this->db->insert($this->table, $data);
		}

		return $this->get_row($sid);
	}

	public function delete($sid)
	{
		if (! $this->db->table_exists($this->table)) {
			return false;
		}
		$this->db->where('sid', (int) $sid);
		return $this->db->delete($this->table);
	}

	/**
	 * Validate settings against the presentation schema.
	 *
	 * @throws ValidationException
	 */
	public function validate(array $settings)
	{
		static $schema = null;
		if ($schema === null) {
			$schema = json_decode(file_get_contents(unix_path(realpath($this->schema_file))));
			if (! is_object($schema)) {
				throw new Exception('Presentation schema not found: ' . $this->schema_file);
			}
		}
		// Arrays to objects for the validator (an empty settings array is handled by replace()).
		$data = json_decode(json_encode($settings));
		$validator = new JsonSchema\Validator;
		$validator->validate($data, $schema);
		if (! $validator->isValid()) {
			$this->load->library('form_validation'); // defines ValidationException
			throw new ValidationException('SCHEMA_VALIDATION_FAILED [presentation]: ', $validator->getErrors());
		}
		return true;
	}
}
