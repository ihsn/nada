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

		$rows = $this->db
			->select('id, type, metadata, keywords')
			->where('type', 'timeseriesdb')
			->group_start()
				->where('keywords IS NULL', null, false)
				->or_where('keywords', '')
			->group_end()
			->get('surveys')
			->result_array();

		if (!$rows) {
			return;
		}

		$this->db->trans_start();
		foreach ($rows as $row) {
			$keywords = $this->build_keywords((string)$row['type'], isset($row['metadata']) ? $row['metadata'] : null);
			$this->db->where('id', (int)$row['id']);
			$this->db->update('surveys', array(
				'keywords' => $keywords,
				'changed' => date('U')
			));
		}
		$this->db->trans_complete();
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
