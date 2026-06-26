<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * One-time cutover:
 * - migrate ts_databases rows into surveys as type=timeseriesdb
 * - register survey_types.code=timeseriesdb (title: Datasets)
 * - drop ts_databases
 */
class Migration_Migrate_timeseries_databases_to_surveys extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('surveys')) {
			throw new Exception('surveys table not found');
		}

		$this->ensure_survey_type_exists();

		if (!$this->db->table_exists('ts_databases')) {
			return;
		}

		$rows = $this->db->get('ts_databases')->result_array();
		if (!is_array($rows) || count($rows) === 0) {
			$this->dbforge->drop_table('ts_databases', true);
			return;
		}

		$this->db->trans_start();
		$idno_map = array(); // old_idno => new_idno when conflicts are renamed
		$inserted = 0;
		$skipped = 0;

		foreach ($rows as $row) {
			$source_idno = isset($row['idno']) ? trim((string)$row['idno']) : '';
			if ($source_idno === '') {
				throw new Exception('ts_databases row has empty idno');
			}

			$existing = $this->find_existing_migrated_survey($source_idno);
			if ($existing) {
				if ($existing['idno'] !== $source_idno) {
					$idno_map[$source_idno] = $existing['idno'];
				}
				$skipped++;
				log_message('info', 'Skipping ts_databases idno already migrated: ' . $source_idno);
				continue;
			}

			$idno = $this->resolve_unique_idno($source_idno);
			if ($idno !== $source_idno) {
				$idno_map[$source_idno] = $idno;
			}

			$created = isset($row['created']) && $row['created'] !== '' ? $row['created'] : date('U');
			$changed = isset($row['changed']) && $row['changed'] !== '' ? $row['changed'] : $created;
			$metadata = isset($row['metadata']) ? $this->rewrite_timeseriesdb_metadata_idno($row['metadata'], $idno) : null;

			$insert = array(
				'type' => 'timeseriesdb',
				'repositoryid' => 'central',
				'idno' => $idno,
				'title' => isset($row['title']) ? $row['title'] : null,
				'abbreviation' => isset($row['abbreviation']) ? $row['abbreviation'] : null,
				'published' => isset($row['published']) ? (int)$row['published'] : 0,
				'created' => $created,
				'changed' => $changed,
				'created_by' => isset($row['created_by']) ? $row['created_by'] : null,
				'changed_by' => isset($row['changed_by']) ? $row['changed_by'] : null,
				'metadata' => $metadata,
				'keywords' => $this->build_keywords('timeseriesdb', $metadata),
				'abstract' => isset($row['abstract']) ? $row['abstract'] : null
			);

			if ($this->db->field_exists('thumbnail', 'ts_databases') && $this->db->field_exists('thumbnail', 'surveys')) {
				$insert['thumbnail'] = isset($row['thumbnail']) ? $row['thumbnail'] : null;
			}

			$result = $this->db->insert('surveys', $insert);
			if ($result === false) {
				$error = $this->db->error();
				throw new Exception('Failed migrating ts_databases row: ' . implode(', ', (array)$error));
			}
			$inserted++;
		}

		$this->rewrite_timeseries_series_database_links($idno_map);

		$this->dbforge->drop_table('ts_databases', true);
		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			throw new Exception('Failed migrating ts_databases to surveys');
		}

		log_message('info', 'Migrated ts_databases to surveys: inserted=' . $inserted . ', skipped=' . $skipped);
	}

	/**
	 * Detect surveys already created by a prior run of this migration.
	 *
	 * @return array|null Row with at least idno key, or null
	 */
	private function find_existing_migrated_survey($source_idno)
	{
		$exact = $this->db
			->select('id, idno')
			->where('idno', $source_idno)
			->where('type', 'timeseriesdb')
			->get('surveys')
			->row_array();

		if ($exact) {
			return $exact;
		}

		$pattern = '/^' . preg_quote($source_idno, '/') . '-tsdb(-\d+)?$/';
		$candidates = $this->db
			->select('id, idno')
			->where('type', 'timeseriesdb')
			->like('idno', $source_idno . '-tsdb', 'after')
			->get('surveys')
			->result_array();

		foreach ($candidates as $candidate) {
			$idno = isset($candidate['idno']) ? (string)$candidate['idno'] : '';
			if ($idno !== '' && preg_match($pattern, $idno)) {
				return $candidate;
			}
		}

		return null;
	}

	private function ensure_survey_type_exists()
	{
		if (!$this->db->table_exists('survey_types')) {
			return;
		}

		$weight = 75; // shown after timeseries (80) since catalog sorts by weight desc
		$exists = $this->db->where('code', 'timeseriesdb')->get('survey_types')->row_array();
		if ($exists) {
			$this->db->where('code', 'timeseriesdb');
			$this->db->update('survey_types', array(
				'title' => 'Datasets',
				'weight' => $weight
			));
			return;
		}

		$this->db->insert('survey_types', array(
			'code' => 'timeseriesdb',
			'title' => 'Datasets',
			'weight' => $weight
		));
	}

	private function resolve_unique_idno($source_idno)
	{
		$idno = $source_idno;
		$seq = 0;

		while (true) {
			$exists = $this->db->select('id')->where('idno', $idno)->get('surveys')->row_array();
			if (!$exists) {
				return $idno;
			}

			$seq++;
			$suffix = $seq === 1 ? '-tsdb' : '-tsdb-' . $seq;
			$idno = $source_idno . $suffix;
		}
	}

	private function rewrite_timeseriesdb_metadata_idno($metadata_encoded, $new_idno)
	{
		$metadata = $this->decode_metadata($metadata_encoded);
		if (!is_array($metadata)) {
			return $metadata_encoded;
		}

		if (!isset($metadata['database_description']) || !is_array($metadata['database_description'])) {
			$metadata['database_description'] = array();
		}
		if (!isset($metadata['database_description']['title_statement']) || !is_array($metadata['database_description']['title_statement'])) {
			$metadata['database_description']['title_statement'] = array();
		}

		$metadata['database_description']['title_statement']['idno'] = $new_idno;
		return $this->encode_metadata($metadata);
	}

	private function rewrite_timeseries_series_database_links(array $idno_map)
	{
		if (count($idno_map) === 0) {
			return;
		}

		$rows = $this->db
			->select('id,metadata')
			->where('type', 'timeseries')
			->get('surveys')
			->result_array();

		foreach ($rows as $row) {
			$metadata = $this->decode_metadata(isset($row['metadata']) ? $row['metadata'] : null);
			if (!is_array($metadata)) {
				continue;
			}

			$current = isset($metadata['series_description']['database_id'])
				? trim((string)$metadata['series_description']['database_id'])
				: '';

			if ($current === '' || !isset($idno_map[$current])) {
				continue;
			}

			$metadata['series_description']['database_id'] = $idno_map[$current];
			$updated = $this->encode_metadata($metadata);

			$this->db->where('id', (int)$row['id']);
			$this->db->update('surveys', array(
				'metadata' => $updated,
				'changed' => date('U')
			));
		}
	}

	private function decode_metadata($metadata_encoded)
	{
		if ($metadata_encoded === null || $metadata_encoded === '') {
			return null;
		}
		$decoded = base64_decode($metadata_encoded, true);
		if ($decoded === false) {
			return null;
		}
		try {
			return unserialize($decoded);
		} catch (Throwable $e) {
			return null;
		}
	}

	private function encode_metadata($metadata_array)
	{
		return base64_encode(serialize($metadata_array));
	}

	private function build_keywords($type, $metadata_encoded)
	{
		$metadata = $this->decode_metadata($metadata_encoded);
		if (!is_array($metadata)) {
			return (string)$type;
		}

		$flat = $this->flatten_values($metadata);
		$text = trim((string)$type . ' ' . implode(' ', $flat));
		$text = preg_replace('/\s+/', ' ', $text);
		return mb_substr((string)$text, 0, 65535);
	}

	private function flatten_values($value)
	{
		$out = array();
		if (is_array($value)) {
			foreach ($value as $v) {
				$out = array_merge($out, $this->flatten_values($v));
			}
			return $out;
		}
		if (is_scalar($value)) {
			$s = trim((string)$value);
			if ($s !== '') {
				$out[] = str_replace(array("\n","\r","\t"), ' ', $s);
			}
		}
		return $out;
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
