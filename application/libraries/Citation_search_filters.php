<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/Citation_csl.php');

/**
 * Filters and sort columns shared by the database citation searches (Citation_search_mysql, _sqlsrv, _sql).
 * Keyword matching is engine specific and stays in each engine.
 *
 * Filter keys: ctype[] (CSL or legacy NADA types), from, to (4-digit years), flag[], user[] (created or
 * changed by), has_notes, no_survey_attached, url_status[]. The query must select FROM citations with the
 * users joins added by join_users().
 */
class Citation_search_filters
{
	/** sort key => column */
	public static $sort_fields = array(
		'id'              => 'citations.id',
		'title'           => 'citations.title',
		'subtitle'        => 'citations.container_title',
		'container_title' => 'citations.container_title',
		'authors'         => 'citations.authors_text',
		'authors_text'    => 'citations.authors_text',
		'pub_year'        => 'citations.pub_year',
		'changed'         => 'citations.changed',
		'created'         => 'citations.created',
		'ctype'           => 'citations.ctype',
		'doi'             => 'citations.doi',
		'flag'            => 'citations.flag',
		'published'       => 'citations.published',
		'url_status'      => 'citations.url_status',
		'changed_by_user' => 'user_changed.username',
		'created_by_user' => 'user_created.username',
	);

	public static function join_users($db)
	{
		$db->join('users user_created', 'citations.created_by = user_created.id', 'left');
		$db->join('users user_changed', 'citations.changed_by = user_changed.id', 'left');
	}

	/**
	 * @param CI_DB_query_builder $db
	 */
	public static function apply($db, $filter, $published, $repositoryid)
	{
		if ($repositoryid != NULL && strtolower($repositoryid) !== 'central') {
			$db->where(
				'citations.id IN (SELECT survey_citations.citationid FROM survey_citations
					INNER JOIN survey_repos ON survey_citations.sid = survey_repos.sid
					WHERE survey_repos.repositoryid = ' . $db->escape($repositoryid) . ')',
				NULL, FALSE
			);
		}

		if (is_numeric($published)) {
			$db->where('citations.published', (int) $published);
		}

		if (!is_array($filter)) {
			return;
		}

		foreach ($filter as $key => $value) {
			switch ($key) {
				case 'published':
					if ($value !== NULL && $value !== '' && is_numeric($value) && !is_numeric($published)) {
						$db->where('citations.published', (int) $value);
					}
					break;

				case 'ctype':
					$types = Citation_csl::to_csl_types($value);
					if (!empty($types)) {
						$db->where_in('citations.ctype', $types);
					} elseif (!empty($value)) {
						// only unknown types asked for: nothing matches
						$db->where('1 = 0', NULL, FALSE);
					}
					break;

				case 'from':
					if (strlen((string) $value) === 4 && is_numeric($value)) {
						$db->where('citations.pub_year >=', (int) $value);
					}
					break;

				case 'to':
					if (strlen((string) $value) === 4 && is_numeric($value)) {
						$db->where('citations.pub_year <=', (int) $value);
					}
					break;

				case 'flag':
					$flags = array_values(array_filter(array_map('strval', (array) $value), 'strlen'));
					if (!empty($flags)) {
						$db->where_in('citations.flag', $flags);
					}
					break;

				case 'user':
					$users = array_values(array_filter(array_map('intval', (array) $value)));
					if (!empty($users)) {
						$list = implode(',', $users);
						$db->where('(citations.changed_by IN (' . $list . ') OR citations.created_by IN (' . $list . '))', NULL, FALSE);
					}
					break;

				case 'has_notes':
					if (!empty($value)) {
						$db->where("(citations.notes IS NOT NULL AND citations.notes <> '')", NULL, FALSE);
					}
					break;

				case 'no_survey_attached':
					if (!empty($value)) {
						$db->where('NOT EXISTS (SELECT 1 FROM survey_citations WHERE survey_citations.citationid = citations.id)', NULL, FALSE);
					}
					break;

				case 'url_status':
					$status = array_values(array_filter(array_map('strval', (array) $value), 'strlen'));
					if (!empty($status)) {
						$db->where_in('citations.url_status', $status);
					}
					break;
			}
		}
	}

	/**
	 * Apply ORDER BY for a sort key; returns FALSE when the key is not a column (e.g. rank).
	 */
	public static function order_by($db, $sort_by, $sort_order)
	{
		if (!isset(self::$sort_fields[$sort_by])) {
			return FALSE;
		}
		$db->order_by(self::$sort_fields[$sort_by], strtolower((string) $sort_order) === 'asc' ? 'ASC' : 'DESC');
		$db->order_by('citations.id', 'DESC');
		return TRUE;
	}

	/**
	 * "column LIKE '%text%' ESCAPE '!'" (escape_like_str() escapes with '!')
	 */
	public static function like($db, $column, $text)
	{
		return $column . ' LIKE ' . $db->escape('%' . $db->escape_like_str($text) . '%') . " ESCAPE '!'";
	}

	public static function keywords($filter)
	{
		return (is_array($filter) && isset($filter['keywords']) && is_string($filter['keywords'])) ? trim($filter['keywords']) : '';
	}

	public static function include_notes($filter)
	{
		return is_array($filter) && !empty($filter['include_notes']);
	}
}
