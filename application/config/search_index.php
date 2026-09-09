<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search index change tracking — queue + state for one configured provider.
 * Database search does not write tracking rows.
 */

$config['search_index_tracking_providers'] = array('solr', 'opensearch', 'semantic');

/** Process queue rows on the catalog request (keeps Solr/OpenSearch live). Semantic is pull-only. */
$config['search_index_inline_providers'] = array('solr', 'opensearch');

$config['search_index_queue_default_limit'] = 50;
$config['search_index_queue_max_limit']     = 100;
$config['search_index_max_attempts']        = 8;
