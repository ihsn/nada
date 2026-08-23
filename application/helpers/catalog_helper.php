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

/**
 * Default catalog browse query values (mirrors frontend catalogQuery.js).
 *
 * @return array<string, int|string>
 */
function catalog_browse_default_query()
{
	return array(
		'sk' => '',
		'tab_type' => '',
		'type' => '',
		'collection' => '',
		'region' => '',
		'dtype' => '',
		'data_class' => '',
		'database' => '',
		'country' => '',
		'tag' => '',
		'from' => '',
		'to' => '',
		'sort_by' => '',
		'sort_order' => '',
		'page' => 1,
		'ps' => 15,
		'view' => '',
		'vk' => '',
		'vf' => '',
		'image_view' => '',
	);
}

/**
 * Parse current request query into normalized catalog browse state.
 *
 * @return array<string, int|string>
 */
function catalog_browse_parse_request_query()
{
	$CI =& get_instance();
	$defaults = catalog_browse_default_query();
	$out = $defaults;

	foreach ($defaults as $key => $def) {
		$raw = $CI->input->get($key);
		if ($raw === false || $raw === null || $raw === '') {
			continue;
		}
		if ($key === 'page') {
			$out['page'] = max(1, (int) $raw);
		} elseif ($key === 'ps') {
			$out['ps'] = (int) $raw ?: 15;
		} else {
			$out[$key] = (string) $raw;
		}
	}

	$from = (int) $out['from'];
	$to = (int) $out['to'];
	if ($from > 0 && $to > 0 && $from > $to) {
		$out['from'] = (string) $to;
		$out['to'] = (string) $from;
	}

	$CI->load->model('Facet_model');
	foreach ($CI->Facet_model->select_all() as $facet) {
		if (($facet['facet_type'] ?? '') !== 'user') {
			continue;
		}
		$name = $facet['name'] ?? '';
		if ($name === '') {
			continue;
		}
		$raw = $CI->input->get($name);
		if ($raw !== false && $raw !== null && $raw !== '') {
			$out[$name] = (string) $raw;
		}
	}

	return $out;
}

/**
 * Serialize browse query omitting defaults (mirrors serializeRouteQuery).
 *
 * @param array<string, int|string>|null $query
 * @return array<string, string>
 */
function catalog_browse_serialize_query($query = null)
{
	$query = $query ?? catalog_browse_parse_request_query();
	$defaults = catalog_browse_default_query();
	$out = array();

	foreach ($defaults as $key => $def) {
		$val = isset($query[$key]) ? $query[$key] : $def;
		if ($val !== $def && $val !== '' && $val !== null) {
			$out[$key] = (string) $val;
		}
	}

	foreach ($query as $key => $val) {
		if (array_key_exists($key, $defaults)) {
			continue;
		}
		if ($val !== '' && $val !== null) {
			$out[$key] = (string) $val;
		}
	}

	ksort($out);
	return $out;
}

/**
 * Stable fingerprint for matching SSR bootstrap to the current URL query.
 */
function catalog_browse_query_fingerprint($query = null)
{
	$serialized = catalog_browse_serialize_query($query);
	if ($serialized === array()) {
		return '{}';
	}
	return json_encode($serialized, JSON_UNESCAPED_UNICODE);
}

/**
 * Build query string for catalog pagination links.
 *
 * @param int $page
 * @param array<string, int|string>|null $query
 * @return string
 */
function catalog_browse_pagination_query_string($page, $query = null)
{
	$params = catalog_browse_serialize_query($query);
	if ($page > 1) {
		$params['page'] = (string) $page;
	} else {
		unset($params['page']);
	}
	if ($params === array()) {
		return '';
	}
	return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Full catalog browse URL for a pagination page.
 *
 * @param int $page
 * @param string|null $repositoryid
 * @return string
 */
function catalog_browse_page_url($page, $repositoryid = null)
{
	$qs = catalog_browse_pagination_query_string($page);
	$base = catalog_search_url($repositoryid);
	return $qs === '' ? $base : $base . '?' . $qs;
}

/**
 * Merge featured studies ahead of result rows on page 1 (mirrors Vue mergeFeaturedRows).
 *
 * @param array<string, mixed> $bootstrap
 * @return array<int, array<string, mixed>>
 */
function catalog_browse_merge_featured_rows(array $bootstrap)
{
	$result = isset($bootstrap['result']) && is_array($bootstrap['result']) ? $bootstrap['result'] : array();
	$rows = isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : array();
	$featured = isset($bootstrap['featured_studies']) && is_array($bootstrap['featured_studies'])
		? $bootstrap['featured_studies']
		: array();

	$page = isset($result['page']) ? (int) $result['page'] : 1;
	if ($page > 1 || ($bootstrap['search_type'] ?? '') === 'variable' || $featured === array()) {
		return $rows;
	}

	$featured_ids = array();
	foreach ($featured as $study) {
		if (isset($study['id'])) {
			$featured_ids[$study['id']] = true;
		}
	}

	$merged = array();
	foreach ($featured as $study) {
		$study['featured'] = true;
		$merged[] = $study;
	}
	foreach ($rows as $row) {
		$id = isset($row['id']) ? $row['id'] : null;
		if ($id === null || !isset($featured_ids[$id])) {
			$merged[] = $row;
		}
	}

	return $merged;
}

/**
 * Simple year range label for SSR study cards.
 *
 * @param array<string, mixed> $row
 * @return string
 */
function catalog_browse_ssr_year_range(array $row)
{
	$start = isset($row['year_start']) ? trim((string) $row['year_start']) : '';
	$end = isset($row['year_end']) ? trim((string) $row['year_end']) : '';
	if ($start === '' && $end === '') {
		return '';
	}
	if ($start !== '' && $end !== '' && $start !== $end) {
		return $start . '–' . $end;
	}
	return $start !== '' ? $start : $end;
}

/**
 * Variable label for SSR (mirrors CatalogVariableResultsList variableTitle).
 *
 * @param array<string, mixed> $row
 * @return string
 */
function catalog_browse_ssr_variable_title(array $row)
{
	$parts = array();
	if (!empty($row['name'])) {
		$parts[] = trim((string) $row['name']);
	}
	if (!empty($row['labl'])) {
		$labl = trim((string) $row['labl']);
		if ($labl !== '' && !in_array($labl, $parts, true)) {
			$parts[] = $labl;
		}
	}
	if ($parts !== array()) {
		return implode(' - ', $parts);
	}
	return !empty($row['labl']) ? (string) $row['labl'] : (!empty($row['name']) ? (string) $row['name'] : '');
}

/**
 * Study metadata line for variable SSR rows.
 *
 * @param array<string, mixed> $row
 * @return string
 */
function catalog_browse_ssr_variable_study_meta(array $row)
{
	$parts = array();
	if (!empty($row['nation'])) {
		$parts[] = trim((string) $row['nation']);
	}
	$years = catalog_browse_ssr_year_range($row);
	if ($years !== '') {
		$parts[] = $years;
	}
	if (!empty($row['idno'])) {
		$parts[] = trim((string) $row['idno']);
	}
	if (!empty($row['authoring_entity'])) {
		$parts[] = trim((string) $row['authoring_entity']);
	}
	return implode(' · ', $parts);
}

/**
 * Thumbnail URL for a repository/collection card.
 *
 * @param array<string, mixed> $repo
 * @return string
 */
function collection_card_thumbnail_url($repo)
{
	if (!empty($repo['thumbnail'])) {
		return base_url() . $repo['thumbnail'];
	}
	return base_url() . 'files/icon-blank.png';
}

/**
 * Collection card (home page, collections index, etc.).
 *
 * @param array<string, mixed>|object $repo
 * @return void
 */
function render_collection_card($repo)
{
	if (is_object($repo)) {
		$repo = (array) $repo;
	}

	$repo_url = site_url('catalog/' . $repo['repositoryid']);
	$short_text = isset($repo['short_text']) ? trim(strip_tags($repo['short_text'])) : '';
	?>
	<div class="col-md-6 mb-4">
		<a class="home-featured-card home-featured-card--collection" href="<?php echo $repo_url; ?>" title="<?php echo html_escape($repo['title']); ?>">
			<span class="home-featured-card-media">
				<img src="<?php echo collection_card_thumbnail_url($repo); ?>" alt="" loading="lazy">
			</span>
			<div class="home-featured-card-content">
				<h3 class="home-featured-card-title"><?php echo html_escape($repo['title']); ?></h3>
				<?php if ($short_text !== ''): ?>
					<p class="home-featured-card-desc"><?php echo html_escape($short_text); ?></p>
				<?php endif; ?>
				<?php if (isset($repo['surveys_found']) && $repo['surveys_found'] > 0): ?>
					<span class="home-card-chip">
						<i class="fas fa-database" aria-hidden="true"></i>
						<?php echo number_format($repo['surveys_found']); ?> datasets
					</span>
				<?php endif; ?>
			</div>
		</a>
	</div>
	<?php
}
