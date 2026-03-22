<?php
/**
 * OpenSearch Configuration
 */

// Connection
$config['opensearch_host']            = 'localhost';
$config['opensearch_port']            = 9200;
$config['opensearch_use_ssl']         = false;
$config['opensearch_username']        = '';
$config['opensearch_password']        = '';

// Timeouts (seconds)
$config['opensearch_timeout']         = 60;
$config['opensearch_connect_timeout'] = 10;

// Debug — logs query DSL and timing; keep false in production
$config['opensearch_debug']           = false;

// Index names
$config['opensearch_index_surveys']   = 'nada_surveys';
$config['opensearch_index_variables'] = 'nada_variables';
$config['opensearch_index_citations'] = 'nada_citations';
