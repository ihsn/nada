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
	 * Run catalog search restricted to IDNO/alias hits when the keyword is a single token.
	 *
	 * @param catalog_search_mysql $driver Active driver (params + filters)
	 * @param int $limit
	 * @param int $offset
	 * @return array|null Search result array, or null to continue with normal keyword search
	 */
	public static function try_search(catalog_search_mysql $driver, $limit, $offset)
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
		if (!class_exists('catalog_search_mysql', false)) {
			require_once APPPATH . 'libraries/Catalog_search_mysql.php';
		}

		$driver = new catalog_search_mysql($params);
		return self::try_search($driver, $limit, $offset);
	}
}
