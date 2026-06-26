<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Semantic Search Configuration
 *
 * NADA semantic search is powered by a separate service: nada-ai
 *   https://github.com/avsolatorio/nada-ai
 *
 * Deploy and run nada-ai alongside this NADA instance (see the nada-ai README:
 * Docker Compose, ingest from catalog, health checks). The FastAPI app exposes
 * POST /search; local dev default is http://localhost:8020 (no trailing slash).
 *
 * Enable semantic search in NADA via Site configurations → search_provider = semantic.
 * Set semantic_search_url, semantic_search_api_key, and semantic_search_debug
 * in Site configurations → Fulltext search.
 */

// Search mode sent to nada-ai POST /search: hybrid | vector | keyword
$config['semantic_search_mode']    = 'vector';

// HTTP timeout for nada-ai POST /search, in seconds (max 15)
$config['semantic_search_timeout'] = 15;

// knn_k — number of nearest neighbours considered for vector retrieval
$config['semantic_search_knn_k']   = 50;

// Prompt prefix required by the semantic API (appended to the user query for vector/hybrid modes).
$config['semantic_search_query_prompt'] = "Instruct: Retrieve texts that help answer the user's query or find related information\nQuery: ";

// collapse_inner_hits.size in the search request body
$config['semantic_search_collapse_inner_hits_size'] = 15;
