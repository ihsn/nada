<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Display template layout helpers (public catalog rendering).
 */

if (!function_exists('display_template_legacy_study_template_types')) {
	/**
	 * Data types configured to use PHP metadata_templates views on the catalog page.
	 *
	 * @return string[]
	 */
	function display_template_legacy_study_template_types()
	{
		static $types = null;
		if ($types !== null) {
			return $types;
		}

		$config = array();
		$path = APPPATH . 'config/display_templates.php';
		if (is_file($path)) {
			include $path;
		}

		$raw = isset($config['legacy_study_templates']) ? $config['legacy_study_templates'] : array();
		$types = array();
		if (!is_array($raw)) {
			return $types;
		}

		foreach ($raw as $key => $value) {
			if (is_int($key)) {
				$type = trim((string) $value);
			} elseif ($value) {
				$type = trim((string) $key);
			} else {
				continue;
			}
			if ($type !== '') {
				$types[] = $type;
			}
		}

		return $types;
	}
}

if (!function_exists('display_template_uses_legacy_study_template')) {
	/**
	 * @param string $type Catalog data type
	 * @return bool
	 */
	function display_template_uses_legacy_study_template($type)
	{
		$list = display_template_legacy_study_template_types();
		$lookup = (string) $type;
		if (in_array($lookup, $list, true)) {
			return true;
		}
		if ($lookup === 'timeseries-db' && in_array('timeseriesdb', $list, true)) {
			return true;
		}
		if ($lookup === 'timeseriesdb' && in_array('timeseries-db', $list, true)) {
			return true;
		}
		return false;
	}
}

if (!function_exists('display_template_node_is_custom_section_container')) {
	/**
	 * Metadata-editor section_container flagged is_custom (data files, variables, DSD, …).
	 * These are not study-description layout; they use dedicated catalog pages.
	 *
	 * @param mixed $node
	 * @return bool
	 */
	function display_template_node_is_custom_section_container($node)
	{
		if (!is_array($node)) {
			return false;
		}
		$type = isset($node['type']) ? (string) $node['type'] : '';
		if ($type !== 'section_container') {
			return false;
		}
		return !empty($node['is_custom']);
	}
}

if (!function_exists('display_template_omit_custom_section_containers')) {
	/**
	 * Drop is_custom section_containers (and their children) from a layout items[] tree.
	 *
	 * @param mixed $items
	 * @param array<int, array{key: string, title: string}>|null $skipped
	 * @return array<int, array<string, mixed>>
	 */
	function display_template_omit_custom_section_containers($items, &$skipped = null)
	{
		if ($skipped === null) {
			$skipped = array();
		}
		if (!is_array($items)) {
			return array();
		}

		$out = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			if (display_template_node_is_custom_section_container($item)) {
				$skipped[] = array(
					'key' => isset($item['key']) ? (string) $item['key'] : '',
					'title' => isset($item['title']) ? (string) $item['title'] : '',
				);
				continue;
			}
			if (isset($item['items']) && is_array($item['items'])) {
				$item['items'] = display_template_omit_custom_section_containers($item['items'], $skipped);
			}
			$out[] = $item;
		}
		return $out;
	}
}

if (!function_exists('display_template_node_hidden')) {
	/**
	 * @param array<string, mixed>|null $node Layout field, section, or array column def
	 * @return bool
	 */
	function display_template_node_hidden($node)
	{
		if (!is_array($node)) {
			return false;
		}
		if (!isset($node['display_options']) || !is_array($node['display_options'])) {
			return false;
		}
		return !empty($node['display_options']['hidden']);
	}
}

if (!function_exists('display_template_filter_props')) {
	/**
	 * Remove array columns marked hidden in display_options.
	 *
	 * @param array<int, array<string, mixed>>|null $props
	 * @return array<int, array<string, mixed>>
	 */
	function display_template_filter_props($props)
	{
		if (!is_array($props)) {
			return array();
		}
		$out = array();
		foreach ($props as $prop) {
			if (!is_array($prop)) {
				continue;
			}
			if (display_template_node_hidden($prop)) {
				continue;
			}
			$out[] = $prop;
		}
		return $out;
	}
}

if (!function_exists('display_template_author_display_name')) {
	/**
	 * Single display name for an author row.
	 * If full_name is set, first_name / initial / last_name are ignored.
	 * Otherwise join first_name, initial, last_name. If those are empty, use name.
	 *
	 * @param mixed $row
	 * @return string
	 */
	function display_template_author_display_name($row)
	{
		if (!is_array($row)) {
			return '';
		}

		$full = isset($row['full_name']) ? trim((string) $row['full_name']) : '';
		if ($full !== '') {
			return $full;
		}

		$parts = array();
		foreach (array('first_name', 'initial', 'last_name') as $key) {
			$value = isset($row[$key]) ? trim((string) $row[$key]) : '';
			if ($value !== '') {
				$parts[] = $value;
			}
		}
		if (count($parts) > 0) {
			return implode(' ', $parts);
		}

		return isset($row['name']) ? trim((string) $row['name']) : '';
	}
}

if (!function_exists('display_template_authors_normalize_for_table')) {
	/**
	 * Simplified authors table: Name, other scalars (affiliation, …), then Type and ID.
	 * full_name wins over first/initial/last. Repeatable author_id is flattened
	 * into Type and ID columns on the main table (not a nested table).
	 *
	 * @param array<int, mixed> $data
	 * @param array<string, mixed> $template
	 * @return array{data: array<int, array<string, mixed>>, template: array<string, mixed>}
	 */
	function display_template_authors_normalize_for_table($data, $template)
	{
		if (!is_array($data)) {
			$data = array();
		}
		if (!is_array($template)) {
			$template = array();
		}

		$parent = isset($template['key']) ? (string) $template['key'] : 'authors';
		$name_keys = array('first_name', 'initial', 'last_name', 'full_name', 'name');
		$skip_keys = array_merge($name_keys, array('author_id'));

		$normalized = array();
		$has_author_id = false;
		foreach ($data as $row) {
			if (!is_array($row)) {
				continue;
			}
			$has_full_name = isset($row['full_name']) && trim((string) $row['full_name']) !== '';
			$row['name'] = display_template_author_display_name($row);
			if ($has_full_name) {
				unset($row['first_name'], $row['initial'], $row['last_name']);
			}

			$types = array();
			$ids = array();
			if (isset($row['author_id']) && is_array($row['author_id'])) {
				foreach ($row['author_id'] as $author_id) {
					if (!is_array($author_id)) {
						continue;
					}
					$type = isset($author_id['type']) ? trim((string) $author_id['type']) : '';
					$id = isset($author_id['id']) ? trim((string) $author_id['id']) : '';
					if ($type === '' && $id === '') {
						continue;
					}
					$types[] = $type;
					$ids[] = $id;
					$has_author_id = true;
				}
			}
			$row['author_id_type'] = implode(', ', $types);
			$row['author_id_id'] = implode(', ', $ids);
			unset($row['author_id']);
			$normalized[] = $row;
		}

		$props = isset($template['props']) && is_array($template['props'])
			? $template['props']
			: array();
		$out_props = array(
			array(
				'key' => 'name',
				'title' => 'Name',
				'type' => 'string',
				'prop_key' => $parent . '.name',
			),
		);
		foreach ($props as $prop) {
			if (!is_array($prop)) {
				continue;
			}
			$key = isset($prop['key']) ? (string) $prop['key'] : '';
			if ($key === '' || in_array($key, $skip_keys, true)) {
				continue;
			}
			$out_props[] = $prop;
		}
		if ($has_author_id) {
			$out_props[] = array(
				'key' => 'author_id_type',
				'title' => 'Type',
				'type' => 'string',
				'prop_key' => $parent . '.author_id.type',
			);
			$out_props[] = array(
				'key' => 'author_id_id',
				'title' => 'ID',
				'type' => 'string',
				'prop_key' => $parent . '.author_id.id',
			);
		}

		$template['props'] = $out_props;

		return array(
			'data' => $normalized,
			'template' => $template,
		);
	}
}

if (!function_exists('display_template_default_format_for_type')) {
	/**
	 * @param string|null $layoutType
	 * @return string plain|markdown
	 */
	function display_template_default_format_for_type($layoutType)
	{
		return $layoutType === 'text' ? 'richtext' : 'plain';
	}
}

if (!function_exists('display_template_scalar_format_values')) {
	/**
	 * @return string[]
	 */
	function display_template_scalar_format_values()
	{
		return array('plain', 'markdown', 'html', 'richtext');
	}
}

if (!function_exists('display_template_prepare_markup_source')) {
	/**
	 * Decode entity-encoded markup and strip unsafe tags/attributes.
	 *
	 * @param string $value
	 * @return string
	 */
	function display_template_prepare_markup_source($value)
	{
		$value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$ci =& get_instance();
		$ci->load->helper('security');
		return xss_clean($value);
	}
}

if (!function_exists('display_template_field_is_uri')) {
	/**
	 * Whole value is a URL (not prose that may contain URLs).
	 *
	 * @param array<string, mixed> $field_def
	 * @return bool
	 */
	function display_template_field_is_uri($field_def)
	{
		$opts = display_template_field_display_options($field_def);
		return !empty($opts['is_uri']);
	}
}

if (!function_exists('display_template_field_uri_as_icon')) {
	/**
	 * When is_uri: icon vs URL text as the link label.
	 * Unset uri_as_icon keeps legacy icon rendering.
	 *
	 * @param array<string, mixed> $field_def
	 * @return bool
	 */
	function display_template_field_uri_as_icon($field_def)
	{
		if (!display_template_field_is_uri($field_def)) {
			return false;
		}
		$opts = display_template_field_display_options($field_def);
		if (!array_key_exists('uri_as_icon', $opts)) {
			return true;
		}
		return !empty($opts['uri_as_icon']);
	}
}

if (!function_exists('display_template_render_uri_value')) {
	/**
	 * @param string $value Already trimmed
	 * @param array<string, mixed> $field_def
	 * @return string HTML anchor
	 */
	function display_template_render_uri_value($value, $field_def)
	{
		$href = html_escape($value);
		$label = display_template_field_uri_as_icon($field_def)
			? '<i class="fas fa-external-link-alt"></i>'
			: $href;
		return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
	}
}

if (!function_exists('display_template_field_linkify_enabled')) {
	/**
	 * Linkify applies to plain format only (not URI fields).
	 *
	 * @param array<string, mixed> $field_def
	 * @return bool
	 */
	function display_template_field_linkify_enabled($field_def)
	{
		if (display_template_field_is_uri($field_def)) {
			return false;
		}
		if (display_template_effective_scalar_format($field_def) !== 'plain') {
			return false;
		}
		$opts = display_template_field_display_options($field_def);
		return !empty($opts['linkify']);
	}
}

if (!function_exists('display_template_field_display_options')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return array<string, mixed>
	 */
	function display_template_field_display_options($field_def)
	{
		if (!isset($field_def['display_options']) || !is_array($field_def['display_options'])) {
			return array();
		}
		return $field_def['display_options'];
	}
}

if (!function_exists('display_template_normalize_scalar_field_def')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return array<string, mixed>
	 */
	function display_template_normalize_scalar_field_def($field_def)
	{
		return is_array($field_def) ? $field_def : array();
	}
}

if (!function_exists('display_template_field_uses_inline_layout')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return bool
	 */
	function display_template_field_uses_inline_layout($field_def)
	{
		$opts = display_template_field_display_options($field_def);
		if (isset($opts['layout']) && (string) $opts['layout'] === 'inline') {
			return true;
		}
		return isset($opts['field_template'])
			&& (string) $opts['field_template'] === 'field_text_inline';
	}
}

if (!function_exists('display_template_field_layout')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return string stacked|inline
	 */
	function display_template_field_layout($field_def)
	{
		return display_template_field_uses_inline_layout($field_def) ? 'inline' : 'stacked';
	}
}

if (!function_exists('display_template_effective_scalar_format')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return string plain|markdown|html|richtext
	 */
	function display_template_effective_scalar_format($field_def)
	{
		$type = isset($field_def['type']) ? (string) $field_def['type'] : 'string';
		$opts = display_template_field_display_options($field_def);
		if (isset($opts['format'])) {
			$fmt = (string) $opts['format'];
			if (in_array($fmt, display_template_scalar_format_values(), true)) {
				return $fmt;
			}
		}
		return display_template_default_format_for_type($type);
	}
}

if (!function_exists('display_template_scalar_format_is_block')) {
	/**
	 * html / markdown / richtext emit block tags; wrapping them in span breaks mPDF.
	 *
	 * @param array<string, mixed> $field_def
	 * @return bool
	 */
	function display_template_scalar_format_is_block($field_def)
	{
		return in_array(
			display_template_effective_scalar_format($field_def),
			array('html', 'markdown', 'richtext'),
			true
		);
	}
}

if (!function_exists('display_template_scalar_value_is_empty')) {
	/**
	 * @param mixed $value
	 * @return bool
	 */
	function display_template_scalar_value_is_empty($value)
	{
		if ($value === null || $value === '') {
			return true;
		}
		if (is_array($value) && count($value) === 0) {
			return true;
		}
		return false;
	}
}

if (!function_exists('display_template_format_single_scalar')) {
	/**
	 * Format one scalar value (no field chrome).
	 *
	 * @param mixed $value
	 * @param array<string, mixed> $field_def Layout node or array column
	 * @return string HTML fragment
	 */
	function display_template_format_single_scalar($value, $field_def)
	{
		if (is_array($value)) {
			$value = implode(' ', array_map('strval', $value));
		}
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}

		$opts = display_template_field_display_options($field_def);

		if (display_template_field_is_uri($field_def)) {
			return display_template_render_uri_value($value, $field_def);
		}

		if (display_template_field_uses_date_format($field_def)) {
			$ci =& get_instance();
			$ci->load->helper('display_date');
			return format_display_template_date($value, $opts);
		}

		$format = display_template_effective_scalar_format($field_def);
		$ci =& get_instance();

		if ($format === 'html') {
			return display_template_prepare_markup_source($value);
		}

		if ($format === 'richtext') {
			$ci->load->helper('markdown');
			return markdown_parse_richtext(display_template_prepare_markup_source($value));
		}

		if ($format === 'markdown') {
			$ci->load->helper('markdown');
			return markdown_parse(display_template_prepare_markup_source($value));
		}

		$escaped = html_escape($value);
		$out = nl2br($escaped, false);
		if (display_template_field_linkify_enabled($field_def)) {
			$ci->load->helper('url');
			$out = linkify($out);
		}
		return $out;
	}
}

if (!function_exists('display_template_render_scalar_value')) {
	/**
	 * Tier 1: value HTML for a metadata value (handles array of scalars as multiple spans).
	 *
	 * @param mixed $value
	 * @param array<string, mixed> $field_def
	 * @param array<string, mixed> $context Reserved (e.g. mode cell|field)
	 * @return string
	 */
	function display_template_render_scalar_value($value, $field_def, $context = array())
	{
		unset($context);
		if (display_template_scalar_value_is_empty($value)) {
			return '';
		}

		$wrap_span = !display_template_scalar_format_is_block($field_def);

		if (is_array($value) && !display_template_field_uses_date_format($field_def)) {
			$parts = array();
			foreach ($value as $piece) {
				if (is_array($piece)) {
					$piece = implode(' ', $piece);
				}
				$html = display_template_format_single_scalar($piece, $field_def);
				if ($html !== '') {
					$parts[] = $wrap_span ? '<span>' . $html . '</span>' : $html;
				}
			}
			return implode('', $parts);
		}

		$html = display_template_format_single_scalar($value, $field_def);
		if ($html === '') {
			return '';
		}
		return $wrap_span ? '<span>' . $html . '</span>' : $html;
	}
}

if (!function_exists('display_template_field_css_class')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return string
	 */
	function display_template_field_css_class($field_def)
	{
		$key = isset($field_def['key']) ? (string) $field_def['key'] : 'field';
		return str_replace(".'", '-', $key);
	}
}

if (!function_exists('display_template_field_label')) {
	/**
	 * @param array<string, mixed> $field_def
	 * @return string Escaped label text
	 */
	function display_template_field_label($field_def)
	{
		$key = isset($field_def['key']) ? (string) $field_def['key'] : '';
		$prop_key = isset($field_def['prop_key']) ? trim((string) $field_def['prop_key']) : '';
		$lookup = $prop_key !== '' ? $prop_key : $key;
		$title = isset($field_def['title']) ? (string) $field_def['title'] : '';
		return tt('metadata.' . $lookup, $title);
	}
}

if (!function_exists('display_template_render_scalar_field')) {
	/**
	 * Tier 2: full field block (label + value) for catalog study metadata.
	 *
	 * @param mixed $value
	 * @param array<string, mixed> $field_def
	 * @return string|false HTML or false when omitted
	 */
	function display_template_render_scalar_field($value, $field_def)
	{
		if (display_template_scalar_value_is_empty($value)) {
			return false;
		}

		$field_def = display_template_normalize_scalar_field_def($field_def);
		$value_html = display_template_render_scalar_value($value, $field_def);
		if ($value_html === '') {
			return false;
		}

		$css = display_template_field_css_class($field_def);
		$label = display_template_field_label($field_def);
		$layout = display_template_field_layout($field_def);

		if ($layout === 'inline') {
			return '<div class="field field-' . $css . '">'
				. '<div class="row border-bottom">'
				. '<div class="col-md-4"><div class="field-title-inline p-1">' . $label . '</div></div>'
				. '<div class="col-md-8 border-left"><div class="field-value">' . $value_html . '</div></div>'
				. '</div></div>';
		}

		return '<div class="mb-2 field field-' . $css . '">'
			. '<div class="font-weight-bold field-title">' . $label . '</div>'
			. '<div class="field-value">' . $value_html . '</div>'
			. '</div>';
	}
}

if (!function_exists('display_template_header_field_keys')) {
	/**
	 * Column keys used as a nested-array record heading.
	 *
	 * @param array<string, mixed> $field_def
	 * @return string[]
	 */
	function display_template_header_field_keys($field_def)
	{
		$opts = display_template_field_display_options($field_def);
		if (!isset($opts['header_fields'])) {
			return array();
		}

		$raw = $opts['header_fields'];
		if (is_string($raw)) {
			$parts = explode(',', $raw);
		} elseif (is_array($raw)) {
			$parts = $raw;
		} else {
			return array();
		}

		$out = array();
		foreach ($parts as $part) {
			$key = trim((string) $part);
			if ($key !== '') {
				$out[] = $key;
			}
		}
		return $out;
	}
}

if (!function_exists('display_template_nested_record_heading')) {
	/**
	 * First non-empty header field value for a nested-array row.
	 *
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $field_def
	 * @return string Plain text (not escaped)
	 */
	function display_template_nested_record_heading($row, $field_def)
	{
		if (!is_array($row)) {
			return '';
		}

		foreach (display_template_header_field_keys($field_def) as $key) {
			if (!array_key_exists($key, $row)) {
				continue;
			}
			$value = $row[$key];
			if (is_array($value)) {
				continue;
			}
			$value = trim((string) $value);
			if ($value !== '') {
				return $value;
			}
		}

		return '';
	}
}

if (!function_exists('display_template_nested_prop_value')) {
	/**
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $column
	 * @return mixed
	 */
	function display_template_nested_prop_value($row, $column)
	{
		$type = isset($column['type']) ? (string) $column['type'] : 'string';
		if ($type === 'nested_section' || $type === 'section') {
			return $row;
		}
		if (!is_array($row)) {
			return null;
		}

		$key = isset($column['key']) ? (string) $column['key'] : '';
		if ($key === '') {
			return null;
		}
		if (array_key_exists($key, $row)) {
			return $row[$key];
		}

		return array_data_get($row, $key);
	}
}

if (!function_exists('display_template_is_pdf_context')) {
	/**
	 * @return bool
	 */
	function display_template_is_pdf_context()
	{
		$ci =& get_instance();
		return isset($ci->display_template)
			&& is_object($ci->display_template)
			&& method_exists($ci->display_template, 'is_pdf_context')
			&& $ci->display_template->is_pdf_context();
	}
}

if (!function_exists('display_template_pdf_safe_renderer_key')) {
	/**
	 * Swap interactive / nested-table-heavy renderers for mPDF.
	 *
	 * @param string $renderer_key
	 * @return string Empty string means skip this renderer in PDF
	 */
	function display_template_pdf_safe_renderer_key($renderer_key)
	{
		$key = (string) $renderer_key;
		if (!display_template_is_pdf_context()) {
			return $key;
		}

		$map = array(
			'field_array_accordion' => 'field_array_stacked',
			'field_bounding_box' => 'field_array',
			'field_photo_gallery' => '',
			'field_video' => '',
			'field_feature_catalog' => 'field_array',
			'field_scripts_script' => 'field_array_stacked',
		);

		return array_key_exists($key, $map) ? $map[$key] : $key;
	}
}

if (!function_exists('display_template_default_view_for_layout_type')) {
	/**
	 * View basename under display_templates/fields/ for a nested prop, or null for scalars.
	 *
	 * @param string $type
	 * @return string|null
	 */
	function display_template_default_view_for_layout_type($type)
	{
		switch ((string) $type) {
			case 'array':
				return 'field_array';
			case 'nested_array':
				return display_template_is_pdf_context() ? 'field_array_stacked' : 'field_array_accordion';
			case 'simple_array':
				return 'field_simple_array';
			case 'object':
				return 'field_object_additional';
			case 'nested_section':
			case 'section':
				return 'field_nested_section';
			case 'widget':
				return 'field_widget';
			default:
				return null;
		}
	}
}

if (!function_exists('display_template_render_pdf_nested_cell')) {
	/**
	 * Nested array/object cell as stacked text instead of a table-in-table.
	 *
	 * @param mixed $data
	 * @param array<string, mixed> $column
	 * @return string
	 */
	function display_template_render_pdf_nested_cell($data, $column)
	{
		if (!is_array($data) || count($data) === 0) {
			return '';
		}

		$props = display_template_filter_props(isset($column['props']) ? $column['props'] : array());
		$rows = display_template_additional_is_list($data) ? $data : array($data);
		$html = array('<div class="pdf-nested-array">');

		foreach ($rows as $row) {
			if (!is_array($row)) {
				$txt = trim((string) $row);
				if ($txt !== '') {
					$html[] = '<div class="pdf-nested-row">' . html_escape($txt) . '</div>';
				}
				continue;
			}

			$parts = array();
			if (count($props) > 0) {
				foreach ($props as $prop) {
					$key = isset($prop['key']) ? (string) $prop['key'] : '';
					$val = ($key !== '' && isset($row[$key])) ? $row[$key] : null;
					$fragment = display_template_pdf_nested_cell_value($val, $prop);
					if ($fragment === '') {
						continue;
					}
					$label = isset($prop['title']) ? (string) $prop['title'] : $key;
					if (isset($prop['prop_key'])) {
						$label = tt('metadata.' . $prop['prop_key'], $label);
					}
					$parts[] = strip_tags($label) . ': ' . $fragment;
				}
			} else {
				$fragment = display_template_pdf_nested_cell_value($row, $column);
				if ($fragment !== '') {
					$parts[] = $fragment;
				}
			}

			if (count($parts) > 0) {
				$html[] = '<div class="pdf-nested-row">' . implode('; ', $parts) . '</div>';
			}
		}

		$html[] = '</div>';
		return count($html) > 2 ? implode('', $html) : '';
	}
}

if (!function_exists('display_template_pdf_nested_cell_value')) {
	/**
	 * @param mixed $value
	 * @param array<string, mixed> $field_def
	 * @return string HTML fragment without tables
	 */
	function display_template_pdf_nested_cell_value($value, $field_def)
	{
		if (display_template_scalar_value_is_empty($value)) {
			return '';
		}

		if (is_array($value)) {
			if (display_template_additional_is_list($value)) {
				$parts = array();
				foreach ($value as $item) {
					$bit = display_template_pdf_nested_cell_value($item, $field_def);
					if ($bit !== '') {
						$parts[] = $bit;
					}
				}
				return implode(', ', $parts);
			}

			$props = display_template_filter_props(isset($field_def['props']) ? $field_def['props'] : array());
			if (count($props) > 0) {
				return display_template_render_pdf_nested_cell(array($value), $field_def);
			}

			$parts = array();
			foreach ($value as $key => $child) {
				$bit = display_template_pdf_nested_cell_value($child, $field_def);
				if ($bit !== '') {
					$parts[] = html_escape((string) $key) . ': ' . $bit;
				}
			}
			return implode('; ', $parts);
		}

		$html = display_template_format_single_scalar($value, $field_def);
		return is_string($html) ? $html : '';
	}
}

if (!function_exists('display_template_render_nested_row_prop')) {
	/**
	 * Render one nested-array column using the same display_options as top-level fields.
	 *
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $column
	 * @param array<string, mixed> $view_data Extra view vars (e.g. resources)
	 * @return string HTML
	 */
	function display_template_render_nested_row_prop($row, $column, $view_data = array())
	{
		if (!is_array($column) || display_template_node_hidden($column)) {
			return '';
		}

		$type = isset($column['type']) ? (string) $column['type'] : 'string';
		$data = display_template_nested_prop_value($row, $column);

		if ($type === 'widget' && display_template_is_pdf_context()) {
			return '';
		}

		$ci =& get_instance();
		$ci->load->helper('display_renderer');

		$view = null;
		$renderer_key = display_template_field_composite_renderer_key($column);
		if ($renderer_key !== null) {
			$renderer_key = display_template_pdf_safe_renderer_key($renderer_key);
			if ($renderer_key === '') {
				return '';
			}
			$record = display_renderer_lookup($renderer_key);
			if (
				$record
				&& display_renderer_is_active($record)
				&& display_renderer_supported_for_layout_type($record, $type)
			) {
				$view = display_renderer_view_basename($record);
				$column = display_renderer_prepare_item($column, $record);
			}
		}

		if ($view === null) {
			$view = display_template_default_view_for_layout_type($type);
		}

		if ($view !== null) {
			if (display_template_scalar_value_is_empty($data) && $type !== 'nested_section' && $type !== 'section') {
				return '';
			}

			$vars = is_array($view_data) ? $view_data : array();
			$vars['data'] = $data;
			$vars['template'] = $column;
			if (!isset($vars['resources'])) {
				$vars['resources'] = array();
			}

			$html = $ci->load->view('display_templates/fields/' . $view, $vars, true);
			return is_string($html) ? $html : '';
		}

		$html = display_template_render_scalar_field($data, $column);
		return $html !== false ? $html : '';
	}
}

if (!function_exists('display_template_additional_is_list')) {
	/**
	 * Sequential 0..n array (JSON list), not an object map.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	function display_template_additional_is_list($value)
	{
		if (!is_array($value) || $value === array()) {
			return false;
		}
		$i = 0;
		foreach ($value as $key => $unused) {
			unset($unused);
			if ($key !== $i) {
				return false;
			}
			$i++;
		}
		return true;
	}
}

if (!function_exists('display_template_additional_is_empty')) {
	/**
	 * @param mixed $value
	 * @return bool
	 */
	function display_template_additional_is_empty($value)
	{
		if ($value === null || $value === '') {
			return true;
		}
		if (is_array($value)) {
			if (count($value) === 0) {
				return true;
			}
			foreach ($value as $child) {
				if (!display_template_additional_is_empty($child)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
}

if (!function_exists('display_template_additional_label')) {
	/**
	 * @param string $key
	 * @return string Escaped label
	 */
	function display_template_additional_label($key)
	{
		$key = (string) $key;
		$human = trim(str_replace('_', ' ', $key));
		if ($human === '') {
			$human = $key;
		} else {
			$human = ucfirst($human);
		}
		return tt('metadata.additional.' . $key, $human);
	}
}

if (!function_exists('display_template_render_additional_scalar')) {
	/**
	 * @param mixed $value
	 * @return string
	 */
	function display_template_render_additional_scalar($value)
	{
		if (is_bool($value)) {
			return $value ? 'Yes' : 'No';
		}
		$def = array(
			'type' => 'string',
			'display_options' => array('format' => 'plain', 'linkify' => true),
		);
		return display_template_render_scalar_value($value, $def, array('mode' => 'cell'));
	}
}

if (!function_exists('display_template_render_additional_value')) {
	/**
	 * Recursively render an additional-metadata value of any JSON shape.
	 *
	 * @param mixed $value
	 * @param int $depth
	 * @return string
	 */
	function display_template_render_additional_value($value, $depth = 0)
	{
		if ($depth > 12 || display_template_additional_is_empty($value)) {
			return '';
		}

		if (!is_array($value)) {
			return display_template_render_additional_scalar($value);
		}

		if (display_template_additional_is_list($value)) {
			return display_template_render_additional_list($value, $depth);
		}

		return display_template_render_additional_map($value, $depth);
	}
}

if (!function_exists('display_template_render_additional_value_flat')) {
	/**
	 * Nested additional-metadata as text (no tables) so mPDF does not nest tables.
	 *
	 * @param mixed $value
	 * @param int $depth
	 * @return string
	 */
	function display_template_render_additional_value_flat($value, $depth = 0)
	{
		if ($depth > 12 || display_template_additional_is_empty($value)) {
			return '';
		}

		if (!is_array($value)) {
			return display_template_render_additional_scalar($value);
		}

		$parts = array();
		if (display_template_additional_is_list($value)) {
			foreach ($value as $item) {
				$bit = display_template_render_additional_value_flat($item, $depth + 1);
				if ($bit !== '') {
					$parts[] = $bit;
				}
			}
			return implode('<br />', $parts);
		}

		foreach ($value as $key => $child) {
			$bit = display_template_render_additional_value_flat($child, $depth + 1);
			if ($bit !== '') {
				$parts[] = display_template_additional_label((string) $key) . ': ' . $bit;
			}
		}
		return implode('; ', $parts);
	}
}

if (!function_exists('display_template_render_additional_list')) {
	/**
	 * @param array<int, mixed> $items
	 * @param int $depth
	 * @return string
	 */
	function display_template_render_additional_list($items, $depth)
	{
		$assoc_rows = array();
		$all_assoc = true;
		foreach ($items as $row) {
			if (!is_array($row) || display_template_additional_is_list($row) || display_template_additional_is_empty($row)) {
				$all_assoc = false;
				break;
			}
			$assoc_rows[] = $row;
		}

		if ($all_assoc && count($assoc_rows) > 0) {
			return display_template_render_additional_table($assoc_rows, $depth);
		}

		$html = array('<ul class="field-additional-list mb-0">');
		$any = false;
		foreach ($items as $row) {
			$cell = display_template_render_additional_value($row, $depth + 1);
			if ($cell === '') {
				continue;
			}
			$any = true;
			$html[] = '<li>' . $cell . '</li>';
		}
		$html[] = '</ul>';
		return $any ? implode('', $html) : '';
	}
}

if (!function_exists('display_template_render_additional_table')) {
	/**
	 * @param array<int, array<string, mixed>> $rows
	 * @param int $depth
	 * @return string
	 */
	function display_template_render_additional_table($rows, $depth)
	{
		$columns = array();
		foreach ($rows as $row) {
			foreach ($row as $col => $unused) {
				unset($unused);
				$col = (string) $col;
				if ($col !== '' && !in_array($col, $columns, true)) {
					$columns[] = $col;
				}
			}
		}
		if (count($columns) === 0) {
			return '';
		}

		$html = array('<div class="table-responsive"><table class="table table-sm table-bordered table-striped table-condensed xsl-table table-grid mb-0">');
		$html[] = '<thead><tr>';
		foreach ($columns as $col) {
			$html[] = '<th>' . display_template_additional_label($col) . '</th>';
		}
		$html[] = '</tr></thead><tbody>';

		$any_row = false;
		foreach ($rows as $row) {
			$cells = array();
			$has = false;
			foreach ($columns as $col) {
				$raw = isset($row[$col]) ? $row[$col] : null;
				if (display_template_is_pdf_context() && is_array($raw)) {
					$cell = display_template_render_additional_value_flat($raw, $depth + 1);
				} else {
					$cell = $raw !== null
						? display_template_render_additional_value($raw, $depth + 1)
						: '';
				}
				if ($cell !== '') {
					$has = true;
				}
				$cells[] = '<td>' . $cell . '</td>';
			}
			if (!$has) {
				continue;
			}
			$any_row = true;
			$html[] = '<tr>' . implode('', $cells) . '</tr>';
		}
		$html[] = '</tbody></table></div>';
		return $any_row ? implode('', $html) : '';
	}
}

if (!function_exists('display_template_render_additional_map')) {
	/**
	 * @param array<string, mixed> $map
	 * @param int $depth
	 * @return string
	 */
	function display_template_render_additional_map($map, $depth)
	{
		$html = array('<dl class="field-additional-map row mb-0">');
		$any = false;
		foreach ($map as $key => $child) {
			$cell = display_template_render_additional_value($child, $depth + 1);
			if ($cell === '') {
				continue;
			}
			$any = true;
			$html[] = '<dt class="col-sm-3">' . display_template_additional_label((string) $key) . '</dt>';
			$html[] = '<dd class="col-sm-9">' . $cell . '</dd>';
		}
		$html[] = '</dl>';
		return $any ? implode('', $html) : '';
	}
}

if (!function_exists('display_template_collect_layout_field_keys')) {
	/**
	 * Metadata field keys from a display layout tree (not sections, containers, or widgets).
	 *
	 * @param mixed $items
	 * @param array<int, string> $out
	 * @return array<int, string>
	 */
	function display_template_collect_layout_field_keys($items, &$out = null)
	{
		if ($out === null) {
			$out = array();
		}
		if (!is_array($items)) {
			return $out;
		}
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$type = isset($item['type']) ? (string) $item['type'] : '';
			$structural = ($type === 'section' || $type === 'section_container' || $type === 'template' || $type === 'template_root' || $type === 'template_description');
			if (!$structural && $type !== 'widget' && isset($item['key']) && $item['key'] !== '') {
				$out[] = (string) $item['key'];
			}
			if (isset($item['items']) && is_array($item['items'])) {
				display_template_collect_layout_field_keys($item['items'], $out);
			}
		}
		return $out;
	}
}

if (!function_exists('display_template_array_copy')) {
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	function display_template_array_copy($value)
	{
		if (!is_array($value)) {
			return $value;
		}
		$out = array();
		foreach ($value as $key => $child) {
			$out[$key] = is_array($child) ? display_template_array_copy($child) : $child;
		}
		return $out;
	}
}

if (!function_exists('display_template_array_unset_path')) {
	/**
	 * Remove a dotted path from an associative array in place.
	 *
	 * @param mixed $arr
	 * @param string $path
	 */
	function display_template_array_unset_path(&$arr, $path)
	{
		if (!is_array($arr) || $path === '') {
			return;
		}
		$parts = explode('.', $path);
		$last = array_pop($parts);
		$cur = &$arr;
		foreach ($parts as $part) {
			if (!is_array($cur) || !array_key_exists($part, $cur)) {
				return;
			}
			$cur = &$cur[$part];
		}
		if (is_array($cur) && array_key_exists($last, $cur)) {
			unset($cur[$last]);
		}
	}
}

if (!function_exists('display_template_omit_named_bag_paths')) {
	/**
	 * Copy bag data with paths already rendered as named layout fields removed.
	 *
	 * @param mixed $data
	 * @param string $bag_key
	 * @param mixed $layout_items
	 * @return mixed
	 */
	function display_template_omit_named_bag_paths($data, $bag_key, $layout_items)
	{
		if (!is_array($data) || !is_array($layout_items)) {
			return $data;
		}
		$bag_key = (string) $bag_key;
		if ($bag_key === '') {
			return $data;
		}
		$prefix = $bag_key . '.';
		$keys = array();
		display_template_collect_layout_field_keys($layout_items, $keys);
		$copy = display_template_array_copy($data);
		foreach ($keys as $key) {
			if ($key === $bag_key || strpos($key, $prefix) !== 0) {
				continue;
			}
			$relative = substr($key, strlen($prefix));
			if ($relative === '') {
				continue;
			}
			display_template_array_unset_path($copy, $relative);
		}
		return $copy;
	}
}

if (!function_exists('display_template_render_additional_field')) {
	/**
	 * Full field block for the additional metadata bag.
	 *
	 * @param mixed $data
	 * @param array<string, mixed> $template
	 * @param array<int, array<string, mixed>> $layout_items Full template items (for named-key subtraction)
	 * @return string
	 */
	function display_template_render_additional_field($data, $template, $layout_items = array())
	{
		$key = isset($template['key']) ? (string) $template['key'] : 'additional';
		if (is_array($layout_items) && $layout_items !== array()) {
			$data = display_template_omit_named_bag_paths($data, $key, $layout_items);
		}

		$inner = display_template_render_additional_value($data, 0);
		if ($inner === '') {
			return '';
		}

		$title = isset($template['title']) ? (string) $template['title'] : 'Additional metadata';
		$css = str_replace('.', '_', $key);

		return '<div class="field field-' . html_escape($css) . ' pb-3">'
			. '<div class="field-title">' . tt('metadata.' . $key, $title) . '</div>'
			. '<div class="field-value">' . $inner . '</div>'
			. '</div>';
	}
}
