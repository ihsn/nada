<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Format metadata date values for display template views.
 *
 * Partial ISO dates (YYYY, YYYY-MM, YYYY-MM-DD) never invent missing month/day as 01.
 * Full calendar dates may include an ISO 8601 time suffix (e.g. 2024-03-15T14:30:00Z); only the date is formatted.
 */

if (!function_exists('parse_display_template_date_iso')) {
	/**
	 * @param string $value Trimmed date string
	 * @return array{y:int,m:?int,d:?int,precision:string}|null precision: year|month|day
	 */
	function parse_display_template_date_iso($value)
	{
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:$|[T\s].*)/', $value, $m)) {
			$y = (int) $m[1];
			$mo = (int) $m[2];
			$d = (int) $m[3];
			if (!checkdate($mo, $d, $y)) {
				return null;
			}
			return array('y' => $y, 'm' => $mo, 'd' => $d, 'precision' => 'day');
		}
		if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
			$y = (int) $m[1];
			$mo = (int) $m[2];
			if ($mo < 1 || $mo > 12) {
				return null;
			}
			return array('y' => $y, 'm' => $mo, 'd' => null, 'precision' => 'month');
		}
		if (preg_match('/^(\d{4})$/', $value, $m)) {
			return array('y' => (int) $m[1], 'm' => null, 'd' => null, 'precision' => 'year');
		}
		return null;
	}
}

if (!function_exists('format_display_template_date_parts')) {
	/**
	 * @param array{y:int,m:?int,d:?int,precision:string} $parts
	 * @param string $format PHP date() format or admin preset key
	 * @return string|false
	 */
	function format_display_template_date_parts($parts, $format)
	{
		$y = $parts['y'];
		$m = $parts['m'];
		$d = $parts['d'];
		$p = $parts['precision'];

		if ($p === 'year') {
			if ($format === 'Y' || $format === 'Y-m' || $format === 'Y-m-d') {
				return (string) $y;
			}
			if ($format === 'd/m/Y' || $format === 'm/d/Y') {
				return (string) $y;
			}
			if ($format === 'F j, Y') {
				return (string) $y;
			}
			return (string) $y;
		}

		if ($p === 'month') {
			if ($format === 'Y') {
				return (string) $y;
			}
			if ($format === 'Y-m' || $format === 'Y-m-d') {
				return sprintf('%04d-%02d', $y, $m);
			}
			if ($format === 'd/m/Y') {
				return sprintf('%02d/%04d', $m, $y);
			}
			if ($format === 'm/d/Y') {
				return sprintf('%02d/%04d', $m, $y);
			}
			if ($format === 'F j, Y') {
				$ts = mktime(0, 0, 0, $m, 1, $y);
				return date('F Y', $ts);
			}
			return sprintf('%04d-%02d', $y, $m);
		}

		// Full day precision — safe to use date() with real calendar day.
		$ts = mktime(0, 0, 0, $m, $d, $y);
		$out = date($format, $ts);
		return $out !== false ? $out : false;
	}
}

if (!function_exists('format_display_template_date')) {
	/**
	 * @param mixed $value Raw metadata value
	 * @param array<string, mixed> $display_options Field display_options (date_format)
	 * @return string HTML-safe formatted date or original value if unparseable
	 */
	function format_display_template_date($value, $display_options = array())
	{
		if ($value === null || $value === '') {
			return '';
		}
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}

		$ci =& get_instance();
		$format = isset($display_options['date_format']) ? (string) $display_options['date_format'] : 'Y-m-d';
		if ($format === 'site_default') {
			$format = (string) $ci->config->item('date_format');
			if ($format === '') {
				$format = 'Y-m-d';
			}
		}

		$parts = parse_display_template_date_iso($value);
		if ($parts !== null) {
			$out = format_display_template_date_parts($parts, $format);
			if ($out !== false && $out !== '') {
				return html_escape($out);
			}
		}

		// Non-ISO or legacy values: avoid strtotime on partial ISO (already handled above).
		$ts = strtotime($value);
		if ($ts === false) {
			return html_escape($value);
		}

		$out = @date($format, $ts);
		if ($out === false) {
			return html_escape($value);
		}

		return html_escape($out);
	}
}

if (!function_exists('display_template_field_uses_date_format')) {
	/**
	 * Whether a display template field/column should render with date formatting.
	 *
	 * @param array<string, mixed> $item Layout node or array column def (type + display_options)
	 * @return bool
	 */
	function display_template_field_uses_date_format($item)
	{
		if (!is_array($item)) {
			return false;
		}

		if (function_exists('display_template_field_is_uri') && display_template_field_is_uri($item)) {
			return false;
		}

		$type = isset($item['type']) ? (string) $item['type'] : '';
		if ($type === 'date') {
			return true;
		}

		if (!isset($item['display_options']) || !is_array($item['display_options'])) {
			return false;
		}

		if (!isset($item['display_options']['date_format'])) {
			return false;
		}

		return trim((string) $item['display_options']['date_format']) !== '';
	}
}
