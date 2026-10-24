<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search index change tracking — queue + state for the index engine (search_index_engine).
 * No rows are written while search_index_engine is none. See Search_engine_resolver::tracked().
 */

/** Process queue rows on the catalog request (keeps Solr live). nada-ai is pull-only. */
$config['search_index_inline_engines'] = array('solr');

$config['search_index_queue_default_limit'] = 50;
$config['search_index_queue_max_limit']     = 100;
