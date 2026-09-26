<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * nada-ai Configuration
 *
 * NADA can search through a separate service: nada-ai
 *   https://github.com/avsolatorio/nada-ai
 *
 * Deploy and run nada-ai alongside this NADA instance (see the nada-ai README: Docker Compose, ingest from catalog,
 * health checks). The FastAPI app serves the catalog search; local dev default is http://localhost:8020 (no
 * trailing slash).
 *
 * Set nada_ai_url, nada_ai_api_key, nada_ai_admin_api_key and nada_ai_debug in Site configurations > Search. They can
 * be saved without making nada-ai the search engine; enable it by choosing nada-ai as the search engine on the same
 * page (search_engine = nada_ai).
 */

// Which engine nada-ai runs (OpenSearch or Qdrant), and which searches it can serve (studies, variables,
// citations), is read from nada-ai itself (GET /info); nothing here or in the site settings says so.
// With OpenSearch, NADA calls POST /studies/search, which needs none of the settings below except nada_ai_url,
// nada_ai_api_key, nada_ai_timeout and nada_ai_debug. With Qdrant, NADA calls POST /search and the settings below
// apply (the query prompt, knn_k, collapse size and mode). The site setting nada_ai_combine_with_database (Qdrant
// only) also runs the catalog database's keyword search and pins the best semantic matches to the top of a
// relevance search, followed by the database's own keyword result; the query prompt, knn_k and collapse size below
// apply too. The values below were chosen on 67 golden queries over a development catalog (nada-ai:
// docs/qdrant-db-search-evaluation.md); re-check them on your own catalog with nada-ai's eval/run_nada_api.py
// before relying on them.

// Studies asked of Qdrant per query: the size of the pinned block (at most 100, the limit of nada-ai's POST /search)
$config['semantic_search_window'] = 50;

// Score floor on a Qdrant hit ('' = none) and the fraction of the best Qdrant score a hit must reach (0 = none).
// Qdrant scores are raw cosine similarities: on the golden queries the best hit of a real query scored 0.46-0.83
// and that of an unrelated query 0.38-0.56.
$config['semantic_search_min_score'] = 0.45;
$config['semantic_search_relative_cutoff'] = 0.85;

// Seconds a query's semantic block is kept, so paging and re-sorting do not run the semantic search again (0 = off)
$config['semantic_search_cache_ttl'] = 60;

// Search mode sent to nada-ai POST /search: hybrid | vector | keyword
$config['semantic_search_mode']    = 'vector';

// HTTP timeout for nada-ai search requests, in seconds (max 15)
$config['nada_ai_timeout'] = 15;

// knn_k — number of nearest neighbours considered for vector retrieval
$config['semantic_search_knn_k']   = 50;

// Prompt prefix required by the semantic API (appended to the user query for vector/hybrid modes).
$config['semantic_search_query_prompt'] = "Instruct: Retrieve texts that help answer the user's query or find related information\nQuery: ";

// collapse_inner_hits.size in the search request body
$config['semantic_search_collapse_inner_hits_size'] = 15;

// Seconds nada-ai is not called after a failure that means it is down (no connection, timeout, HTTP 5xx except 501,
// HTTP 429), so requests do not each wait for their own timeout. After this, one request tries it again.
$config['nada_ai_breaker_cooldown'] = 30;
