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
 * Set semantic_search_url, semantic_search_api_key, semantic_search_admin_api_key and
 * semantic_search_debug in Site configurations → Search → Semantic search settings. These
 * can be saved without making semantic search the active provider.
 * Enable it by selecting Semantic Search (AI) as the search provider on the same page
 * (search_provider = semantic).
 */

// Which engine nada-ai runs is the site setting semantic_search_engine (qdrant | opensearch, default qdrant;
// Site configurations > Search). With qdrant, NADA calls POST /search and the settings below apply (the query
// prompt, knn_k, collapse size and mode). With opensearch, NADA calls POST /studies/search, which needs none of
// them: only semantic_search_url, semantic_search_api_key, semantic_search_timeout and semantic_search_debug.
// Do not set semantic_search_engine in this file: it would override the site setting.
//
// qdrant_db (Qdrant + catalog database): the database keyword search and Qdrant each rank the catalog and the two
// rankings are fused; the query prompt, knn_k and collapse size below apply too. The values below were chosen on
// 67 golden queries over a development catalog (nada-ai: docs/qdrant-db-search-evaluation.md); re-check them on
// your own catalog with nada-ai's eval/run_nada_api.py before relying on them.

// Studies asked of Qdrant per query (at most 100, the limit of nada-ai's POST /search)
$config['semantic_search_window'] = 50;

// Best keyword matches taken from the database per query
$config['semantic_search_keyword_window'] = 100;

// Score floor on a Qdrant hit ('' = none) and the fraction of the best Qdrant score a hit must reach (0 = none).
// Qdrant scores are raw cosine similarities: on the golden queries the best hit of a real query scored 0.46-0.83
// and that of an unrelated query 0.38-0.56.
$config['semantic_search_min_score'] = 0.45;
$config['semantic_search_relative_cutoff'] = 0.85;

// Weight of each ranking in the fusion (the database's fulltext ranking is coarser than the vector ranking)
$config['semantic_search_keyword_weight'] = 0.5;
$config['semantic_search_vector_weight'] = 1.0;

// Seconds a query's fused list is kept, so paging and re-sorting do not run the semantic search again (0 = off)
$config['semantic_search_cache_ttl'] = 60;

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
