<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public catalog search UI mode: classic (PHP) or vue (same /catalog URL).
 *
 * @return string 'classic'|'vue'
 */
function catalog_public_search_ui()
{
	$CI =& get_instance();
	$ui = $CI->config->item('catalog_public_search_ui');
	if ($ui === false || $ui === null || $ui === '') {
		return 'classic';
	}
	$ui = strtolower(trim((string) $ui));
	return ($ui === 'vue') ? 'vue' : 'classic';
}

/**
 * Base URL for the public catalog search/browse UI (not study detail pages).
 *
 * @param string|null $repositoryid Collection repository ID, or null/central for root
 * @return string
 */
function catalog_search_url($repositoryid = null)
{
	$repo = trim((string) $repositoryid);
	if ($repo === '' || strtolower($repo) === 'central') {
		return site_url('catalog');
	}

	return site_url('catalog/' . rawurlencode($repo));
}

/**
 * Whether catalog search debug payloads may be exposed to clients or verbose logs.
 */
function catalog_search_debug_enabled()
{
	$CI =& get_instance();
	$CI->config->load('semantic_search');
	if (filter_var($CI->config->item('semantic_search_debug'), FILTER_VALIDATE_BOOLEAN)) {
		return true;
	}
	if (filter_var($CI->config->item('opensearch_debug'), FILTER_VALIDATE_BOOLEAN)) {
		return true;
	}
	return false;
}

/**
 * Remove driver debug blobs from browse/API search results unless debug is enabled.
 *
 * @param array<string, mixed> $result
 * @return array<string, mixed>
 */
function catalog_browse_sanitize_search_result(array $result)
{
	if (!catalog_search_debug_enabled()) {
		unset($result['debug']);
	}
	return $result;
}
