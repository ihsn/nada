<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Display template composite renderer registry (Tier 3).
 */

if (!function_exists('display_renderer_registry_merged')) {
	/**
	 * @return array<string, array<string, mixed>>
	 */
	function display_renderer_registry_merged()
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}

		$core = array();
		$custom = array();

		$core_path = APPPATH . 'config/display_renderers.php';
		$custom_path = APPPATH . 'config/display_renderers_custom.php';

		if (file_exists($core_path)) {
			$config = array();
			include $core_path;
			$core = isset($config['display_renderers']) && is_array($config['display_renderers'])
				? $config['display_renderers']
				: array();
		}

		if (file_exists($custom_path)) {
			$config = array();
			include $custom_path;
			$custom = isset($config['display_renderers_custom']) && is_array($config['display_renderers_custom'])
				? $config['display_renderers_custom']
				: array();
		}

		$cache = $core;
		foreach ($custom as $key => $row) {
			$cache[$key] = $row;
		}

		return $cache;
	}
}

if (!function_exists('display_renderer_lookup')) {
	/**
	 * @param string $renderer_key
	 * @return array<string, mixed>|null
	 */
	function display_renderer_lookup($renderer_key)
	{
		$key = trim((string) $renderer_key);
		if ($key === '') {
			return null;
		}
		$registry = display_renderer_registry_merged();
		return isset($registry[$key]) && is_array($registry[$key]) ? $registry[$key] : null;
	}
}

if (!function_exists('display_renderer_view_basename')) {
	/**
	 * PHP view name under display_templates/fields/ (no .php).
	 *
	 * @param array<string, mixed> $record
	 * @return string
	 */
	function display_renderer_view_basename($record)
	{
		if (!empty($record['field_template'])) {
			return (string) $record['field_template'];
		}
		if (isset($record['handler']['id']) && (string) $record['handler']['id'] !== '') {
			return (string) $record['handler']['id'];
		}
		return isset($record['key']) ? (string) $record['key'] : '';
	}
}

if (!function_exists('display_renderer_is_active')) {
	/**
	 * @param array<string, mixed>|null $record
	 * @return bool
	 */
	function display_renderer_is_active($record)
	{
		if (!is_array($record)) {
			return false;
		}
		$status = isset($record['status']) ? (string) $record['status'] : 'active';
		return $status !== 'inactive';
	}
}

if (!function_exists('display_renderer_key_for_legacy_field_template')) {
	/**
	 * Map display_options.field_template (view basename) to registry key.
	 *
	 * @param string $field_template
	 * @return string|null
	 */
	function display_renderer_key_for_legacy_field_template($field_template)
	{
		$ft = trim((string) $field_template);
		if ($ft === '' || $ft === 'field_text_inline') {
			return null;
		}

		$registry = display_renderer_registry_merged();
		if (isset($registry[$ft])) {
			return $ft;
		}

		foreach ($registry as $key => $row) {
			if (!is_array($row)) {
				continue;
			}
			if (display_renderer_view_basename($row) === $ft) {
				return (string) $key;
			}
		}

		return null;
	}
}

if (!function_exists('display_template_field_composite_renderer_key')) {
	/**
	 * Registry key for Tier 3 composite rendering, if any.
	 *
	 * @param array<string, mixed> $item
	 * @return string|null
	 */
	function display_template_field_composite_renderer_key($item)
	{
		if (!is_array($item)) {
			return null;
		}

		$opts = isset($item['display_options']) && is_array($item['display_options'])
			? $item['display_options']
			: array();

		if (isset($opts['renderer']) && trim((string) $opts['renderer']) !== '') {
			return trim((string) $opts['renderer']);
		}

		if (isset($opts['field_template']) && trim((string) $opts['field_template']) !== '') {
			return display_renderer_key_for_legacy_field_template((string) $opts['field_template']);
		}

		return null;
	}
}

if (!function_exists('display_renderer_prepare_item')) {
	/**
	 * Merge registry default_params into display_options for rendering.
	 *
	 * @param array<string, mixed> $item
	 * @param array<string, mixed> $record
	 * @return array<string, mixed>
	 */
	function display_renderer_prepare_item($item, $record)
	{
		if (!is_array($item)) {
			$item = array();
		}
		if (!isset($item['display_options']) || !is_array($item['display_options'])) {
			$item['display_options'] = array();
		}

		$defaults = isset($record['default_params']) && is_array($record['default_params'])
			? $record['default_params']
			: array();

		foreach ($defaults as $param_key => $default_val) {
			if (
				!isset($item['display_options'][$param_key])
				|| $item['display_options'][$param_key] === ''
				|| $item['display_options'][$param_key] === null
			) {
				$item['display_options'][$param_key] = $default_val;
			}
		}

		return $item;
	}
}

if (!function_exists('display_renderer_supported_for_layout_type')) {
	/**
	 * @param array<string, mixed> $record
	 * @param string $layout_type
	 * @return bool
	 */
	function display_renderer_supported_for_layout_type($record, $layout_type)
	{
		$supported = isset($record['supported_source_types']) && is_array($record['supported_source_types'])
			? $record['supported_source_types']
			: array();
		if (empty($supported)) {
			return true;
		}
		return in_array((string) $layout_type, $supported, true);
	}
}

if (!function_exists('display_renderer_data_type_match_keys')) {
	/**
	 * Canonical keys that should be treated as the same catalog data type.
	 *
	 * @param string $data_type
	 * @return string[]
	 */
	function display_renderer_data_type_match_keys($data_type)
	{
		$key = strtolower(trim((string) $data_type));
		if ($key === '') {
			return array();
		}

		static $groups = array(
			array('survey', 'microdata'),
			array('timeseries', 'indicator'),
			array('timeseries-db', 'timeseriesdb', 'indicator-db'),
		);

		foreach ($groups as $group) {
			if (in_array($key, $group, true)) {
				return $group;
			}
		}

		return array($key);
	}
}

if (!function_exists('display_renderer_supported_for_data_type')) {
	/**
	 * Empty/omitted supported_data_types means every project type is allowed.
	 * Empty $data_type means no filter (API backward compatible).
	 *
	 * @param array<string, mixed> $record
	 * @param string|null $data_type
	 * @return bool
	 */
	function display_renderer_supported_for_data_type($record, $data_type)
	{
		if (!is_array($record)) {
			return false;
		}

		$supported = isset($record['supported_data_types']) && is_array($record['supported_data_types'])
			? $record['supported_data_types']
			: array();
		if (empty($supported)) {
			return true;
		}

		$requested = display_renderer_data_type_match_keys($data_type);
		if (empty($requested)) {
			return true;
		}

		foreach ($supported as $allowed) {
			$allowed_keys = display_renderer_data_type_match_keys($allowed);
			foreach ($allowed_keys as $allowed_key) {
				if (in_array($allowed_key, $requested, true)) {
					return true;
				}
			}
		}

		return false;
	}
}

if (!function_exists('display_renderer_is_widget')) {
	/**
	 * @param array<string, mixed>|null $record
	 * @return bool
	 */
	function display_renderer_is_widget($record)
	{
		if (!is_array($record)) {
			return false;
		}
		if (isset($record['kind']) && (string) $record['kind'] === 'widget') {
			return true;
		}
		$supported = isset($record['supported_source_types']) && is_array($record['supported_source_types'])
			? $record['supported_source_types']
			: array();
		return in_array('widget', $supported, true);
	}
}

if (!function_exists('display_template_legacy_widget_field_to_renderer')) {
	/**
	 * @param string $widget_field
	 * @return string
	 */
	function display_template_legacy_widget_field_to_renderer($widget_field)
	{
		$ft = trim((string) $widget_field);
		if ($ft === '') {
			return '';
		}

		static $map = array(
			'doi-citation' => 'field_doi_citation',
			'field_doi-citation' => 'field_doi_citation',
			'widget_default' => 'field_iframe_embed',
			'field_widget_default' => 'field_iframe_embed',
			'field_iframe_embed' => 'field_iframe_embed',
		);
		if (isset($map[$ft])) {
			return $map[$ft];
		}

		$from_registry = display_renderer_key_for_legacy_field_template($ft);
		return $from_registry ? $from_registry : $ft;
	}
}

if (!function_exists('display_template_widget_renderer_key')) {
	/**
	 * Registry key for a widget node (display_options.renderer, then legacy widget_options).
	 *
	 * @param array<string, mixed> $item
	 * @return string|null
	 */
	function display_template_widget_renderer_key($item)
	{
		$key = display_template_field_composite_renderer_key($item);
		if ($key !== null) {
			return $key;
		}
		if (!is_array($item)) {
			return null;
		}
		$wo = isset($item['widget_options']) && is_array($item['widget_options'])
			? $item['widget_options']
			: array();
		if (isset($wo['widget_field']) && trim((string) $wo['widget_field']) !== '') {
			$mapped = display_template_legacy_widget_field_to_renderer((string) $wo['widget_field']);
			return $mapped !== '' ? $mapped : null;
		}
		return null;
	}
}

if (!function_exists('display_template_widget_data_key')) {
	/**
	 * @param array<string, mixed> $item
	 * @return string
	 */
	function display_template_widget_data_key($item)
	{
		if (!is_array($item)) {
			return '';
		}
		$opts = isset($item['display_options']) && is_array($item['display_options'])
			? $item['display_options']
			: array();
		if (isset($opts['data_key']) && trim((string) $opts['data_key']) !== '') {
			return trim((string) $opts['data_key']);
		}
		$wo = isset($item['widget_options']) && is_array($item['widget_options'])
			? $item['widget_options']
			: array();
		if (isset($wo['data_key']) && trim((string) $wo['data_key']) !== '') {
			return trim((string) $wo['data_key']);
		}
		return '';
	}
}

if (!function_exists('display_template_doi_from_identifiers')) {
	/**
	 * @param mixed $rows
	 * @return string
	 */
	function display_template_doi_from_identifiers($rows)
	{
		if (!is_array($rows)) {
			return '';
		}
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$type = isset($row['type']) ? strtolower(trim((string) $row['type'])) : '';
			if ($type !== 'doi') {
				continue;
			}
			if (isset($row['identifier']) && trim((string) $row['identifier']) !== '') {
				return trim((string) $row['identifier']);
			}
		}
		return '';
	}
}

if (!function_exists('display_template_resolve_doi')) {
	/**
	 * Study DOI column, then identifiers (type=doi), then datacite.doi.
	 *
	 * @param array<string, mixed> $project Display_template metadata (study row + nested metadata)
	 * @param string $data_key Optional identifiers path (without metadata. prefix)
	 * @return string
	 */
	function display_template_resolve_doi($project, $data_key = '')
	{
		if (!is_array($project)) {
			return '';
		}

		if (isset($project['doi']) && trim((string) $project['doi']) !== '') {
			return trim((string) $project['doi']);
		}

		$paths = array();
		$key = trim((string) $data_key);
		if ($key !== '') {
			$paths[] = $key;
		}

		$type = isset($project['type']) ? strtolower(trim((string) $project['type'])) : '';
		if ($type === 'script') {
			$paths[] = 'project_desc.title_statement.identifiers';
		} elseif ($type === 'document') {
			$paths[] = 'document_description.identifiers';
		} elseif ($type === 'table') {
			$paths[] = 'table_description.identifiers';
		} elseif ($type === 'image') {
			$paths[] = 'image_description.identifiers';
		} elseif ($type === 'video') {
			$paths[] = 'video_description.identifiers';
		} else {
			$paths[] = 'study_desc.title_statement.identifiers';
		}

		$paths = array_values(array_unique($paths));
		foreach ($paths as $path) {
			$rows = array_data_get($project, 'metadata.' . $path);
			$doi = display_template_doi_from_identifiers($rows);
			if ($doi !== '') {
				return $doi;
			}
		}

		$datacite = array_data_get($project, 'metadata.datacite.doi');
		if (is_string($datacite) && trim($datacite) !== '') {
			return trim($datacite);
		}

		return '';
	}
}
