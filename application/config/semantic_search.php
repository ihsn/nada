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
 * Set nada_ai_url, nada_ai_api_key and nada_ai_debug in Site configurations > Search. nada_ai_api_key is nada-ai's
 * admin key, used both for searching and for indexing from the Semantic search admin pages. These settings can be
 * saved without making nada-ai the search engine; enable it by choosing NADA-AI as the search engine on the same page
 * (search_engine = nada_ai).
 */

// nada-ai must run OpenSearch; which engine it runs and which searches it can serve (studies, variables, citations)
// are read from nada-ai itself (GET /info), and any engine but OpenSearch is an error. NADA calls POST /studies/search,
// POST /variables/search and POST /citations/search.

// HTTP timeout for nada-ai search requests, in seconds (max 15). Every search that starts while nada-ai hangs waits
// this long before falling back, so keep it short: nada-ai abandons a slow OpenSearch query itself after 5 seconds
// (search_timeout), and a semantic search adds the query's embedding (slower just after nada-ai starts).
$config['nada_ai_timeout'] = 8;

// Seconds nada-ai is not called after a failure that means it is down (no connection, timeout, HTTP 5xx except 501,
// HTTP 429), so requests do not each wait for their own timeout. After this, one request tries it again.
$config['nada_ai_breaker_cooldown'] = 30;
