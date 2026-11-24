<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Server-side file cache for the data tables API (/api/tables).
 *
 * One folder per table: {cache_path}/tables/{db_id}_{table_id}/{type}_{hash}.json
 * Entries never expire; the whole table folder is cleared when the table's
 * data or dictionary changes (see Data_table_mongo_model::clear_table_api_cache).
 *
 * Optional config items (application/config/config.php):
 *   tables_api_cache_enabled    bool  default TRUE
 *   tables_api_cache_min_limit  int   default 1000  (data requests below this limit are not cached)
 */
class Table_api_cache
{
	private $ci;
	private $root;
	private $enabled;
	private $min_limit;

	public function __construct()
	{
		$this->ci =& get_instance();

		$cache_path = $this->ci->config->item('cache_path');
		if (empty($cache_path)) {
			$cache_path = APPPATH . 'cache/';
		}
		$this->root = rtrim($cache_path, '/\\') . '/tables/';

		$enabled = $this->ci->config->item('tables_api_cache_enabled');
		$this->enabled = ($enabled === null) ? true : (bool) $enabled;

		$min_limit = (int) $this->ci->config->item('tables_api_cache_min_limit');
		$this->min_limit = $min_limit > 0 ? $min_limit : 1000;
	}

	public function is_enabled()
	{
		return $this->enabled;
	}

	public function min_limit()
	{
		return $this->min_limit;
	}

	/**
	 * @param string $type   entry type, e.g. data, aggregate, count
	 * @param mixed  $params anything that identifies the request (array, filter)
	 * @return mixed|null cached value, or null on a miss
	 */
	public function get($db_id, $table_id, $type, $params)
	{
		if (!$this->enabled) {
			return null;
		}

		$file = $this->entry_path($db_id, $table_id, $type, $params);
		if (!is_file($file)) {
			return null;
		}

		$content = @file_get_contents($file);
		if ($content === false || $content === '') {
			return null;
		}

		$value = json_decode($content, true);
		if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}

		return $value;
	}

	public function save($db_id, $table_id, $type, $params, $value)
	{
		if (!$this->enabled) {
			return false;
		}

		$dir = $this->table_dir($db_id, $table_id);
		if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
			log_message('error', 'Table_api_cache: cannot create ' . $dir);
			return false;
		}

		$json = json_encode($value);
		if ($json === false) {
			return false;
		}

		$file = $this->entry_path($db_id, $table_id, $type, $params);
		if (@file_put_contents($file, $json) === false) {
			log_message('error', 'Table_api_cache: cannot write ' . $file);
			return false;
		}

		return true;
	}

	/**
	 * Delete all cache entries for a table.
	 *
	 * @return int number of files deleted
	 */
	public function clear($db_id, $table_id)
	{
		$dir = $this->table_dir($db_id, $table_id);
		if (!is_dir($dir)) {
			return 0;
		}

		$deleted = 0;
		foreach (glob($dir . '*.json') ?: array() as $file) {
			if (@unlink($file)) {
				$deleted++;
			}
		}
		@rmdir($dir);

		return $deleted;
	}

	/**
	 * Clear by MongoDB collection name (table_{db_id}_{table_id}), e.g. after a rename.
	 */
	public function clear_collection($collection_name)
	{
		$name = strtolower((string) $collection_name);
		if (strpos($name, 'table_') !== 0) {
			return 0;
		}
		// table_dir() joins db_id and table_id with '_', so the rest of the name is the folder
		return $this->clear(substr($name, 6), '');
	}

	private function table_dir($db_id, $table_id)
	{
		$name = strtolower($table_id === '' ? $db_id : $db_id . '_' . $table_id);
		$name = preg_replace('/[^a-z0-9_-]/', '_', $name);
		return $this->root . $name . '/';
	}

	private function entry_path($db_id, $table_id, $type, $params)
	{
		$type = preg_replace('/[^a-z0-9_-]/', '_', strtolower((string) $type));
		$key = json_encode($this->normalize($params));
		return $this->table_dir($db_id, $table_id) . $type . '_' . md5($key) . '.json';
	}

	/**
	 * Sort array keys recursively so the same request in a different
	 * parameter order maps to the same entry.
	 */
	private function normalize($value)
	{
		if (is_object($value)) {
			$value = json_decode(json_encode($value), true);
		}
		if (!is_array($value)) {
			return $value;
		}
		$is_list = array_keys($value) === range(0, count($value) - 1);
		if (!$is_list) {
			ksort($value);
		}
		foreach ($value as $k => $v) {
			$value[$k] = $this->normalize($v);
		}
		return $value;
	}
}
