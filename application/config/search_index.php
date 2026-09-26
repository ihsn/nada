<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search index change tracking — queue + state for the engine that serves each object type.
 * Database search does not write tracking rows. Which object types are tracked is decided by
 * Search_engine_resolver::tracked(), not by a setting.
 */

/** Process queue rows on the catalog request (keeps Solr/OpenSearch live). nada-ai is pull-only. */
$config['search_index_inline_engines'] = array('solr', 'opensearch');

$config['search_index_queue_default_limit'] = 50;
$config['search_index_queue_max_limit']     = 100;
$config['search_index_max_attempts']        = 8;
