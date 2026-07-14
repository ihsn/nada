<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Single-token study IDNO / alias resolution for public catalog search.
 *
 * Exact match (case-insensitive) on surveys.idno and survey_aliases.alternate_id,
 * scoped to the same sidebar filters as the active search driver.
 */
class Catalog_study_idno_lookup {

	/**
	 * True when keywords are one non-empty token (no whitespace).
	 *
	 * @param string $keywords
	 * @return bool
	 */
	public static function is_single_token($keywords)
	{
		$tokens = preg_split('/\s+/', trim((string) $keywords), -1, PREG_SPLIT_NO_EMPTY);
		return count($tokens) === 1;
	}

	/**
	 * Catalog search driver for the active DB backend (mysql/mysqli or sqlsrv).
	 *
	 * @param array<string, mixed> $params Catalog_search constructor params
	 * @return catalog_search_mysql|catalog_search_sqlsrv
	 */
	public static function create_driver(array $params)
	{
		$ci =& get_instance();
		$dbdriver = $ci->db->dbdriver;

		require_once APPPATH . 'libraries/Catalog_search_mysql.php';

		if ($dbdriver === 'sqlsrv') {
			require_once APPPATH . 'libraries/Catalog_search_sqlsrv.php';
			return new catalog_search_sqlsrv($params);
		}

		return new catalog_search_mysql($params);
	}

	/**
	 * Run catalog search restricted to IDNO/alias hits when the keyword is a single token.
	 *
	 * @param catalog_search_mysql|catalog_search_sqlsrv $driver Active driver (params + filters)
	 * @param int $limit
	 * @param int $offset
	 * @return array|null Search result array, or null to continue with normal keyword search
	 */
	public static function try_search($driver, $limit, $offset)
	{
		$keyword = trim((string) $driver->study_keywords);
		if ($keyword === '' || !self::is_single_token($keyword)) {
			return null;
		}

		$ids = $driver->resolve_idno_alias_survey_ids($keyword);
		if (empty($ids)) {
			return null;
		}

		return $driver->search_for_survey_ids($ids, $limit, $offset);
	}

	/**
	 * @param array<string, mixed> $params Catalog_search constructor params
	 * @param int $limit
	 * @param int $offset
	 * @return array|null
	 */
	public static function try_search_from_params(array $params, $limit, $offset)
	{
		$driver = self::create_driver($params);
		return self::try_search($driver, $limit, $offset);
	}
}
