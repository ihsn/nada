<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Backfill keywords for timeseriesdb records already migrated into surveys.
 * This enables keyword search for records created by direct SQL migration.
 */
class Migration_Backfill_timeseriesdb_keywords extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('surveys')) {
			return;
		}

		if (!$this->db->field_exists('keywords', 'surveys')) {
			log_message('info', 'Migration_Backfill_timeseriesdb_keywords: surveys.keywords missing, skipping');
			return;
		}

		$rows = $this->fetch_rows_needing_keywords();
		if (!$rows) {
			return;
		}

		$has_metadata = $this->db->field_exists('metadata', 'surveys');

		$this->db->trans_start();
		foreach ($rows as $row) {
			$metadata = ($has_metadata && isset($row['metadata'])) ? $row['metadata'] : null;
			$keywords = $this->build_keywords((string)$row['type'], $metadata);
			$this->db->where('id', (int)$row['id']);
			$this->db->update('surveys', array(
				'keywords' => $keywords,
				'changed' => date('U')
			));
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$error = $this->db->error();
			throw new Exception(
				'Failed backfilling timeseriesdb keywords: '
				. (isset($error['message']) ? $error['message'] : 'transaction failed')
			);
		}
	}

	/**
	 * Rows migrated into surveys (direct SQL or PHP) may still have empty keywords.
	 */
	private function fetch_rows_needing_keywords()
	{
		$driver = $this->db->dbdriver;
		$select_metadata = $this->db->field_exists('metadata', 'surveys') ? ', metadata' : '';

		if ($driver === 'sqlsrv') {
			// Legacy text/ntext: no LEN() (8116) and no = '' vs varchar (402).
			$sql = "SELECT id, [type]{$select_metadata}, keywords
				FROM surveys
				WHERE [type] IN ('timeseriesdb', 'timeseries-db')
				AND (keywords IS NULL OR DATALENGTH(keywords) = 0)";
		} elseif (in_array($driver, array('mysql', 'mysqli'), true)) {
			$sql = "SELECT id, type{$select_metadata}, keywords
				FROM surveys
				WHERE type IN ('timeseriesdb', 'timeseries-db')
				AND (keywords IS NULL OR keywords = '')";
		} else {
			$result = $this->db
				->select('id, type' . ($select_metadata ? ', metadata' : '') . ', keywords')
				->where_in('type', array('timeseriesdb', 'timeseries-db'))
				->where('(keywords IS NULL OR keywords = \'\')', null, false)
				->get('surveys');
			if ($result === false) {
				return $this->fail_select($this->db->last_query());
			}
			return $result->result_array();
		}

		$result = $this->db->query($sql);
		if ($result === false) {
			return $this->fail_select($sql);
		}

		return $result->result_array();
	}

	private function fail_select($sql)
	{
		$error = $this->db->error();
		$message = isset($error['message']) ? $error['message'] : 'unknown database error';
		$code = isset($error['code']) ? $error['code'] : '';
		throw new Exception(
			'Backfill timeseriesdb keywords SELECT failed'
			. ($code !== '' ? " ({$code})" : '')
			. ": {$message}\nSQL: {$sql}"
		);
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

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
