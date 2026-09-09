<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fail-closed guard for catalog search filter builders.
 *
 * The `_build_*_query()` builders validate their input and drop values that can't be used
 * (non-numeric ids, wrong-length ISO codes, and so on). When EVERY supplied value is dropped
 * the builder has historically returned FALSE, which removes the clause from the WHERE
 * altogether — so an unusable filter returned the entire catalog while the UI still showed an
 * active filter chip. That reads as a successful broad search rather than a failed filter.
 *
 * The distinction that matters:
 *   - no filter requested at all      -> return FALSE (no clause; correct)
 *   - filter requested, nothing valid -> return self::NO_MATCH (clause matching zero rows)
 *
 * @see Catalog_country_resolver which applies the same contract to country values.
 */
class Catalog_filter_guard {

	/**
	 * WHERE fragment that matches no rows. Safe to AND into any of the search queries on both
	 * MySQL and SQL Server; both optimisers recognise it as an impossible predicate.
	 */
	const NO_MATCH = '1=0';

	/**
	 * Sentinel for params that are normalised to a numeric id list before reaching a builder
	 * (currently `sid`). surveys.id is a positive AUTO_INCREMENT/IDENTITY value, so -1 can
	 * never match a real row, and the value still satisfies the "comma separated numbers"
	 * contract the builder expects.
	 */
	const NO_MATCH_ID = '-1';

	/**
	 * Lucene/Solr equivalent of NO_MATCH. `1=0` is not valid query syntax there; negating the
	 * match-all query is the standard idiom for a filter query that matches no documents.
	 */
	const NO_MATCH_SOLR = '-*:*';
}
