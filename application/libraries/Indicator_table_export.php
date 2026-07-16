<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Server-side pivot table builder + HTML/XLSX renderer for indicator table export.
 *
 * Mirrors the JS pivot logic in frontend/shared/timeseries/pivotIndicatorTable.js.
 *
 * Usage:
 *   $this->load->library('Indicator_table_export');
 *   $html  = $this->indicator_table_export->build_html($records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $options);
 *   $bytes = $this->indicator_table_export->build_xlsx($records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $options);
 */
class Indicator_table_export {

	const TIME_PERIOD_SENTINEL = '_time_period';
	const SINGLE_COL_KEY       = '__value__';
	const SINGLE_ROW_KEY       = '__single__';

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Build a complete HTML export document for the pivot table.
	 *
	 * @param  array  $records    Output of Timeseries_mongo_model::build_catalog_chart_records.
	 * @param  array  $row_dims   Ordered dim keys for rows (may contain TIME_PERIOD_SENTINEL).
	 * @param  array  $col_dims   Ordered dim keys for columns.
	 * @param  string $tp_comp    Actual DSD component name for time_period.
	 * @param  string $time_order 'asc' or 'desc'.
	 * @param  array  $label_map  [ dim_key => [ code => label ] ].
	 * @param  array  $options    [ 'title' => ..., 'dataset_title' => ... ]
	 * @return string
	 */
	public function build_html(
		array  $records,
		array  $row_dims,
		array  $col_dims,
		$tp_comp,
		$time_order   = 'asc',
		array  $label_map = [],
		array  $sort_map  = [],
		array  $options   = []
	) {
		$tp_comp    = (string) ($tp_comp ?? '');
		$time_order = ($time_order === 'desc') ? 'desc' : 'asc';

		$pivot = $this->_build_pivot($records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $sort_map);

		$title         = isset($options['title'])         ? (string) $options['title']         : '';
		$dataset_title = isset($options['dataset_title']) ? (string) $options['dataset_title'] : '';
		return $this->_render_html(
			$pivot['header_rows'],
			$pivot['leaf_columns'],
			$pivot['body_sections'],
			$pivot['show_row_labels'],
			$title,
			$dataset_title
		);
	}

	/**
	 * Build an XLSX export for the pivot table. Returns the raw file bytes.
	 *
	 * @param  array  $records
	 * @param  array  $row_dims
	 * @param  array  $col_dims
	 * @param  string $tp_comp
	 * @param  string $time_order
	 * @param  array  $label_map
	 * @param  array  $options    [ 'dataset_title' => ... ]
	 * @return string  Raw XLSX bytes.
	 */
	public function build_xlsx(
		array  $records,
		array  $row_dims,
		array  $col_dims,
		$tp_comp,
		$time_order   = 'asc',
		array  $label_map = [],
		array  $sort_map  = [],
		array  $options   = []
	) {
		$tp_comp    = (string) ($tp_comp ?? '');
		$time_order = ($time_order === 'desc') ? 'desc' : 'asc';

		$pivot = $this->_build_pivot($records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $sort_map);

		$dataset_title = isset($options['dataset_title']) ? (string) $options['dataset_title'] : '';
		return $this->_render_xlsx(
			$pivot['header_rows'],
			$pivot['leaf_columns'],
			$pivot['body_sections'],
			$pivot['show_row_labels'],
			$dataset_title
		);
	}

	// ── Private: shared pivot builder ─────────────────────────────────────────

	/**
	 * Run the full pivot algorithm and return structured data for rendering.
	 * Cell values are raw floats (null when absent); rendering layers format them.
	 *
	 * @return array { header_rows, leaf_columns, body_sections, show_row_labels }
	 */
	private function _build_pivot(array $records, array $row_dims, array $col_dims, $tp_comp, $time_order, array $label_map, array $sort_map = [])
	{
		// 1. Collect cells and unique paths
		$cells        = [];
		$col_path_map = [];
		$row_path_map = [];

		foreach ($records as $rec) {
			if (!is_array($rec)) continue;
			$val = isset($rec['observation_value']) ? $rec['observation_value'] : null;
			if ($val === null || $val === '' || !is_numeric($val)) continue;

			$col_path = $col_dims ? $this->_build_path($col_dims, $rec, $tp_comp) : [];
			$row_path = $row_dims ? $this->_build_path($row_dims, $rec, $tp_comp) : [];

			if ($col_dims && in_array('', $col_path, true)) continue;
			if ($row_dims && in_array('', $row_path, true)) continue;

			$col_key = $col_dims ? $this->_path_key($col_path) : self::SINGLE_COL_KEY;
			$row_key = $row_dims ? $this->_path_key($row_path) : self::SINGLE_ROW_KEY;

			if ($col_dims) $col_path_map[$col_key] = $col_path;
			if ($row_dims) $row_path_map[$row_key] = $row_path;

			$cells[$row_key . "\x01" . $col_key] = (float) $val;
		}

		// 2. Sort paths
		$sorted_col_paths = $col_dims
			? $this->_sort_paths(array_values($col_path_map), $col_dims, $tp_comp, $time_order, $sort_map)
			: [[]];
		$sorted_row_paths = $row_dims
			? $this->_sort_paths(array_values($row_path_map), $row_dims, $tp_comp, $time_order, $sort_map)
			: [[]];

		if (!$row_dims) {
			$sorted_row_paths = [[]];
			$row_path_map[self::SINGLE_ROW_KEY] = [];
		}
		if (!$col_dims) {
			$sorted_col_paths = [[]];
			$col_path_map[self::SINGLE_COL_KEY] = [];
		}

		// 3. Build column headers
		$col_headers  = $this->_build_col_headers($sorted_col_paths, $col_dims, $label_map);
		$leaf_columns = $col_headers['leaf_columns'];
		$header_rows  = $col_headers['header_rows'];

		// 4. Build body sections (stores raw float|null values)
		$show_row_labels = !empty($row_dims);
		$body_sections   = $this->_build_row_body(
			$sorted_row_paths, $row_dims, $leaf_columns, $cells, $label_map
		);

		return [
			'header_rows'     => $header_rows,
			'leaf_columns'    => $leaf_columns,
			'body_sections'   => $body_sections,
			'show_row_labels' => $show_row_labels,
		];
	}

	// ── Private: path helpers ─────────────────────────────────────────────────

	private function _get_dim_value(array $rec, $dim_key, $tp_comp)
	{
		if ($dim_key === self::TIME_PERIOD_SENTINEL) {
			if (isset($rec['time_period']) && $rec['time_period'] !== '' && $rec['time_period'] !== null) {
				return (string) $rec['time_period'];
			}
			if ($tp_comp !== '' && isset($rec[$tp_comp]) && $rec[$tp_comp] !== '' && $rec[$tp_comp] !== null) {
				return (string) $rec[$tp_comp];
			}
			return '';
		}
		if (isset($rec[$dim_key]) && $rec[$dim_key] !== '' && $rec[$dim_key] !== null) {
			return (string) $rec[$dim_key];
		}
		$parsed = $this->_parse_series_key(isset($rec['series_key']) ? $rec['series_key'] : '');
		return isset($parsed[$dim_key]) ? (string) $parsed[$dim_key] : '';
	}

	private function _parse_series_key($sk)
	{
		$out = [];
		$raw = trim((string) $sk);
		if (!$raw || $raw === 'Series') return $out;
		foreach (explode(' | ', $raw) as $seg) {
			$eq = strpos($seg, '=');
			if ($eq === false || $eq === 0) continue;
			$k = trim(substr($seg, 0, $eq));
			$v = trim(substr($seg, $eq + 1));
			if ($k !== '') $out[$k] = $v;
		}
		return $out;
	}

	private function _build_path(array $dim_keys, array $rec, $tp_comp)
	{
		$out = [];
		foreach ($dim_keys as $k) {
			$out[] = $this->_get_dim_value($rec, $k, $tp_comp);
		}
		return $out;
	}

	private function _path_key(array $parts)
	{
		return implode("\x00", $parts);
	}

	private function _is_time_dim($dim_key, $tp_comp)
	{
		return $dim_key === self::TIME_PERIOD_SENTINEL
			|| ($tp_comp !== '' && $dim_key === $tp_comp);
	}

	private function _compare_paths(array $a, array $b, array $dim_keys, $tp_comp, $time_order, array $sort_map)
	{
		$len = max(count($a), count($b));
		for ($i = 0; $i < $len; $i++) {
			$va = isset($a[$i]) ? $a[$i] : '';
			$vb = isset($b[$i]) ? $b[$i] : '';
			if ($va === $vb) continue;
			$dk = isset($dim_keys[$i]) ? $dim_keys[$i] : '';
			if ($this->_is_time_dim($dk, $tp_comp)) {
				$cmp = strnatcasecmp($va, $vb);
				if ($time_order === 'desc') $cmp = -$cmp;
			} elseif (isset($sort_map[$dk])) {
				$pa  = isset($sort_map[$dk][$va]) ? $sort_map[$dk][$va] : PHP_INT_MAX;
				$pb  = isset($sort_map[$dk][$vb]) ? $sort_map[$dk][$vb] : PHP_INT_MAX;
				$cmp = $pa - $pb;
			} else {
				$cmp = strnatcasecmp($va, $vb);
			}
			return $cmp;
		}
		return 0;
	}

	private function _sort_paths(array $paths, array $dim_keys, $tp_comp, $time_order, array $sort_map = [])
	{
		usort($paths, function ($a, $b) use ($dim_keys, $tp_comp, $time_order, $sort_map) {
			return $this->_compare_paths($a, $b, $dim_keys, $tp_comp, $time_order, $sort_map);
		});
		return $paths;
	}

	private function _resolve_label(array $label_map, $dim_key, $code)
	{
		return isset($label_map[$dim_key][$code])
			? (string) $label_map[$dim_key][$code]
			: (string) $code;
	}

	// ── Private: column header builder ────────────────────────────────────────

	private function _build_col_headers(array $sorted_paths, array $dim_keys, array $label_map)
	{
		if (!$dim_keys) {
			return [
				'header_rows'  => [[['title' => 'Value', 'key' => self::SINGLE_COL_KEY, 'colspan' => 1, 'rowspan' => 1, 'group_start' => false]]],
				'leaf_columns' => [['key' => self::SINGLE_COL_KEY, 'title' => 'Value', 'group_start' => false]],
			];
		}

		$depth        = count($dim_keys);
		$leaf_columns = [];
		foreach ($sorted_paths as $parts) {
			$leaf_columns[] = [
				'key'         => $this->_path_key($parts),
				'title'       => $this->_resolve_label($label_map, $dim_keys[$depth - 1], isset($parts[$depth - 1]) ? $parts[$depth - 1] : ''),
				'group_start' => false,
			];
		}

		if ($depth === 1) {
			$header_rows = [array_map(function ($c) {
				return ['title' => $c['title'], 'key' => $c['key'], 'colspan' => 1, 'rowspan' => 1, 'group_start' => false];
			}, $leaf_columns)];
			return ['header_rows' => $header_rows, 'leaf_columns' => $leaf_columns];
		}

		$header_rows = array_fill(0, $depth, []);
		for ($level = 0; $level < $depth - 1; $level++) {
			$i = 0;
			$n = count($sorted_paths);
			while ($i < $n) {
				$prefix = array_slice($sorted_paths[$i], 0, $level + 1);
				$j      = $i + 1;
				while ($j < $n) {
					$next = array_slice($sorted_paths[$j], 0, $level + 1);
					if ($this->_path_key($next) !== $this->_path_key($prefix)) break;
					$j++;
				}
				$header_rows[$level][] = [
					'title'       => $this->_resolve_label($label_map, $dim_keys[$level], $prefix[$level]),
					'colspan'     => $j - $i,
					'rowspan'     => 1,
					'group_start' => false,
				];
				$i = $j;
			}
		}
		$header_rows[$depth - 1] = array_map(function ($c) {
			return ['title' => $c['title'], 'key' => $c['key'], 'colspan' => 1, 'rowspan' => 1, 'group_start' => false];
		}, $leaf_columns);

		if (count($header_rows[0]) > 1) {
			$top_group_starts = [];
			$g_pos            = 0;
			foreach ($header_rows[0] as $gi => &$cell) {
				if ($gi > 0) {
					$top_group_starts[$g_pos] = true;
					$cell['group_start']       = true;
				}
				$g_pos += isset($cell['colspan']) ? (int) $cell['colspan'] : 1;
			}
			unset($cell);

			for ($level = 1; $level < $depth; $level++) {
				$leaf_pos = 0;
				foreach ($header_rows[$level] as &$cell) {
					if (isset($top_group_starts[$leaf_pos])) $cell['group_start'] = true;
					$leaf_pos += isset($cell['colspan']) ? (int) $cell['colspan'] : 1;
				}
				unset($cell);
			}

			foreach ($leaf_columns as $i => &$col) {
				if (isset($top_group_starts[$i])) $col['group_start'] = true;
			}
			unset($col);
		}

		return ['header_rows' => $header_rows, 'leaf_columns' => $leaf_columns];
	}

	// ── Private: row body builder ─────────────────────────────────────────────
	// Cell values are stored as raw float|null (not formatted strings).

	private function _build_row_body(array $sorted_row_paths, array $row_dims, array $leaf_columns, array $cells, array $label_map)
	{
		if (!$row_dims) {
			$row_key   = self::SINGLE_ROW_KEY;
			$row_cells = [];
			foreach ($leaf_columns as $col) {
				$ck                     = $row_key . "\x01" . $col['key'];
				$row_cells[$col['key']] = isset($cells[$ck]) ? $cells[$ck] : null;
			}
			return [[
				'section_key'     => '',
				'title'           => '',
				'depth'           => 0,
				'is_group_header' => false,
				'rows'            => [['row_key' => $row_key, 'label' => '', 'cells' => $row_cells]],
			]];
		}

		$use_grouped = count($row_dims) > 1;
		return $this->_build_sections($sorted_row_paths, $row_dims, 0, $leaf_columns, $cells, $label_map, $use_grouped);
	}

	private function _build_sections(array $paths, array $dim_keys, $level, array $leaf_columns, array $cells, array $label_map, $use_grouped)
	{
		if (!$paths) return [];

		$n_dims = count($dim_keys);

		if (!$use_grouped || $n_dims <= 1) {
			$rows = [];
			foreach ($paths as $parts) {
				$row_key = $this->_path_key($parts);
				if ($n_dims === 1) {
					$label = $this->_resolve_label($label_map, $dim_keys[0], isset($parts[0]) ? $parts[0] : '');
				} elseif ($n_dims > 1) {
					$lp = [];
					foreach ($dim_keys as $di => $dk) {
						$lp[] = $this->_resolve_label($label_map, $dk, isset($parts[$di]) ? $parts[$di] : '');
					}
					$label = implode(' · ', array_filter($lp));
				} else {
					$label = '';
				}
				$row_cells = [];
				foreach ($leaf_columns as $col) {
					$ck                     = $row_key . "\x01" . $col['key'];
					$row_cells[$col['key']] = isset($cells[$ck]) ? $cells[$ck] : null;
				}
				$rows[] = ['row_key' => $row_key, 'label' => $label, 'cells' => $row_cells];
			}
			return [['section_key' => '', 'title' => '', 'depth' => 0, 'is_group_header' => false, 'rows' => $rows]];
		}

		if ($level >= $n_dims - 1) {
			$leaf_dim = $dim_keys[$n_dims - 1];
			$rows     = [];
			foreach ($paths as $parts) {
				$row_key   = $this->_path_key($parts);
				$last_val  = !empty($parts) ? end($parts) : '';
				$label     = $this->_resolve_label($label_map, $leaf_dim, $last_val);
				$row_cells = [];
				foreach ($leaf_columns as $col) {
					$ck                     = $row_key . "\x01" . $col['key'];
					$row_cells[$col['key']] = isset($cells[$ck]) ? $cells[$ck] : null;
				}
				$rows[] = ['row_key' => $row_key, 'label' => $label, 'cells' => $row_cells];
			}
			return [['section_key' => '', 'title' => '', 'depth' => $level, 'is_group_header' => false, 'rows' => $rows]];
		}

		$sections = [];
		$i        = 0;
		$n        = count($paths);
		while ($i < $n) {
			$code = isset($paths[$i][$level]) ? $paths[$i][$level] : '';
			$j    = $i + 1;
			while ($j < $n && isset($paths[$j][$level]) && $paths[$j][$level] === $code) $j++;
			$chunk      = array_slice($paths, $i, $j - $i);
			$sections[] = [
				'section_key'     => $this->_path_key(array_slice($chunk[0], 0, $level + 1)),
				'title'           => $this->_resolve_label($label_map, $dim_keys[$level], $code),
				'depth'           => $level,
				'is_group_header' => true,
				'rows'            => [],
			];
			foreach ($this->_build_sections($chunk, $dim_keys, $level + 1, $leaf_columns, $cells, $label_map, $use_grouped) as $s) {
				$sections[] = $s;
			}
			$i = $j;
		}
		return $sections;
	}

	// ── Private: HTML renderer ────────────────────────────────────────────────

	private function _format_value($val)
	{
		if ($val === null) return '';
		$n         = (float) $val;
		$formatted = number_format($n, 6, '.', ',');
		if (strpos($formatted, '.') !== false) {
			$formatted = rtrim(rtrim($formatted, '0'), '.');
		}
		return $formatted;
	}

	private function _h($str)
	{
		return htmlspecialchars((string) $str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	private function _render_html(array $header_rows, array $leaf_columns, array $body_sections, $show_row_labels, $title, $dataset_title)
	{
		$corner_rowspan = count($header_rows);
		$col_count      = count($leaf_columns);
		$total_cols     = $show_row_labels ? $col_count + 1 : $col_count;

		ob_start();
		echo '<!DOCTYPE html>' . "\n";
		echo '<html lang="en">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="UTF-8">' . "\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
		$doc_title = $title ?: ($dataset_title ?: 'Table Export');
		echo '<title>' . $this->_h($doc_title) . '</title>' . "\n";
		echo '<style>' . $this->_get_css() . '</style>' . "\n";
		echo '</head>' . "\n";
		echo '<body>' . "\n";

		if ($dataset_title) {
			echo '<h1 class="export-title">' . $this->_h($dataset_title) . '</h1>' . "\n";
		}
		if ($title && $title !== $dataset_title) {
			echo '<h2 class="export-subtitle">' . $this->_h($title) . '</h2>' . "\n";
		}

		echo '<div class="export-table-wrap">' . "\n";
		echo '<table class="export-table">' . "\n";
		echo '<thead>' . "\n";

		foreach ($header_rows as $row_idx => $header_row) {
			echo '<tr>' . "\n";
			if ($show_row_labels && $row_idx === 0) {
				$rspan = $corner_rowspan > 1 ? ' rowspan="' . $corner_rowspan . '"' : '';
				echo '<th class="export-table__corner"' . $rspan . '></th>' . "\n";
			}
			foreach ($header_row as $cell) {
				$cls     = 'export-table__col-header';
				if (!empty($cell['group_start'])) $cls .= ' export-table__col-header--group-start';
				$colspan = isset($cell['colspan']) && (int) $cell['colspan'] > 1 ? ' colspan="' . (int) $cell['colspan'] . '"' : '';
				$rowspan = isset($cell['rowspan']) && (int) $cell['rowspan'] > 1 ? ' rowspan="' . (int) $cell['rowspan'] . '"' : '';
				echo '<th class="' . $this->_h($cls) . '"' . $colspan . $rowspan . '>' . $this->_h($cell['title']) . '</th>' . "\n";
			}
			echo '</tr>' . "\n";
		}

		echo '</thead>' . "\n";
		echo '<tbody>' . "\n";

		foreach ($body_sections as $section) {
			if (!empty($section['is_group_header'])) {
				$depth_cls = 'export-table__section-header export-table__section-header--depth-' . (int) $section['depth'];
				echo '<tr class="' . $this->_h($depth_cls) . '">' . "\n";
				echo '<th class="export-table__section-title" colspan="' . $total_cols . '">' . $this->_h($section['title']) . '</th>' . "\n";
				echo '</tr>' . "\n";
				continue;
			}
			$row_depth = isset($section['depth']) ? (int) $section['depth'] : 0;
			foreach ($section['rows'] as $row) {
				echo '<tr class="export-table__data-row">' . "\n";
				if ($show_row_labels) {
					$lbl_cls = 'export-table__row-label';
					if ($row_depth > 0) $lbl_cls .= ' export-table__row-label--depth-' . $row_depth;
					echo '<td class="' . $this->_h($lbl_cls) . '">' . $this->_h($row['label']) . '</td>' . "\n";
				}
				foreach ($leaf_columns as $col) {
					$cls = 'export-table__cell';
					if (!empty($col['group_start'])) $cls .= ' export-table__cell--group-start';
					$val = isset($row['cells'][$col['key']]) ? $this->_format_value($row['cells'][$col['key']]) : '';
					echo '<td class="' . $this->_h($cls) . '">' . $this->_h($val) . '</td>' . "\n";
				}
				echo '</tr>' . "\n";
			}
		}

		echo '</tbody>' . "\n";
		echo '</table>' . "\n";
		echo '</div>' . "\n";
		echo '</body>' . "\n";
		echo '</html>' . "\n";

		return ob_get_clean();
	}

	private function _get_css()
	{
		return '
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:13px;color:#212121;margin:16px;background:#fff}
.export-title{font-size:18px;font-weight:600;margin:0 0 4px}
.export-subtitle{font-size:14px;font-weight:400;color:#555;margin:0 0 16px}
.export-table-wrap{overflow-x:auto}
.export-table{border-collapse:collapse;width:100%;font-size:13px}
.export-table th,.export-table td{padding:6px 10px;border:1px solid #e0e0e0;white-space:nowrap}
.export-table__corner{background:#f5f5f5}
.export-table__col-header{background:#f5f5f5;text-align:center;font-weight:600;border-left:1px solid #e0e0e0}
.export-table__col-header--group-start{border-left:2px solid #9e9e9e}
.export-table__row-label{font-weight:500;text-align:left;background:#fafafa;padding-left:8px}
.export-table__row-label--depth-1{padding-left:20px}
.export-table__row-label--depth-2{padding-left:32px}
.export-table__row-label--depth-3{padding-left:44px}
.export-table__cell{text-align:right;font-variant-numeric:tabular-nums}
.export-table__cell--group-start{border-left:2px solid #e0e0e0}
.export-table__section-header{background:#efefef}
.export-table__section-title{font-weight:600;text-align:left;padding-left:8px}
.export-table__section-header--depth-1 .export-table__section-title{padding-left:20px;font-weight:500}
.export-table__section-header--depth-2 .export-table__section-title{padding-left:32px;font-weight:400}
';
	}

	// ── Private: XLSX renderer ────────────────────────────────────────────────

	private function _render_xlsx(array $header_rows, array $leaf_columns, array $body_sections, $show_row_labels, $dataset_title)
	{
		require_once APPPATH . '../modules/fast-excel-writer/vendor/autoload.php';

		$header_depth   = count($header_rows);
		$col_count      = count($leaf_columns);
		$data_col_start = $show_row_labels ? 2 : 1; // 1-based Excel column index for first data column
		$total_cols     = ($data_col_start - 1) + $col_count;
		$last_col_ltr   = \avadim\FastExcelWriter\Excel::colLetter($total_cols);

		// Sheet name: strip chars invalid in Excel sheet names, cap at 31 chars
		$sheet_name = $dataset_title
			? mb_substr(preg_replace('/[:\\\\\/\?\*\[\]]/', '', $dataset_title), 0, 31)
			: 'Data';
		if (!trim($sheet_name)) $sheet_name = 'Data';

		$excel = \avadim\FastExcelWriter\Excel::create([$sheet_name]);
		$sheet = $excel->sheet();

		if ($col_count >= 6) {
			$sheet->pageLandscape();
		}

		// ── Column headers via Area (supports merged cells) ───────────────────
		$hdr_style = [
			'font'           => ['style' => 'bold'],
			'fill'           => '#f5f5f5',
			'text-align'     => 'center',
			'vertical-align' => 'center',
			'border'         => 'thin',
		];
		$hdr_group_style = array_merge($hdr_style, ['border-left' => 'medium']);
		$corner_style    = ['fill' => '#f5f5f5', 'border' => 'thin', 'vertical-align' => 'center'];

		$area = $sheet->beginArea('A1');

		// Corner cell (row-label column header, spans all header rows)
		if ($show_row_labels) {
			$corner_range = $header_depth > 1 ? ('A1:A' . $header_depth) : 'A1';
			$area->setValue($corner_range, '');
			$area->setStyle($corner_range, $corner_style);
		}

		// Column header cells
		foreach ($header_rows as $row_idx => $header_row) {
			$xl_row     = $row_idx + 1;
			$col_cursor = $data_col_start;

			foreach ($header_row as $cell) {
				$colspan   = isset($cell['colspan']) ? max(1, (int) $cell['colspan']) : 1;
				$col_start = \avadim\FastExcelWriter\Excel::colLetter($col_cursor);
				$col_end   = \avadim\FastExcelWriter\Excel::colLetter($col_cursor + $colspan - 1);
				$range     = $colspan > 1
					? ($col_start . $xl_row . ':' . $col_end . $xl_row)
					: ($col_start . $xl_row);
				$style = !empty($cell['group_start']) ? $hdr_group_style : $hdr_style;
				$area->setValue($range, $cell['title']);
				$area->setStyle($range, $style);
				$col_cursor += $colspan;
			}
		}

		$sheet->writeAreas();
		$sheet->setFreezeRows($header_depth);

		// ── Body sections ─────────────────────────────────────────────────────
		$section_style = [
			'font'   => ['style' => 'bold'],
			'fill'   => '#efefef',
			'border' => 'thin',
		];
		$lbl_style = [
			'font'   => ['style' => 'bold'],
			'fill'   => '#fafafa',
			'border' => 'thin',
		];
		$num_style = [
			'text-align' => 'right',
			'border'     => 'thin',
			'format'     => '#,##0.######',
		];
		$num_group_style = array_merge($num_style, ['border-left' => 'medium']);

		// Track the current row number (1-based) to know where to apply mergeCells
		// after writeRow(). After writeAreas() the sheet cursor is past the header rows.
		$body_row = $header_depth + 1;

		foreach ($body_sections as $section) {
			if (!empty($section['is_group_header'])) {
				$indent = str_repeat('  ', (int) $section['depth']);
				$vals   = array_fill(0, $total_cols, '');
				$vals[0] = $indent . $section['title'];
				$sheet->writeRow($vals, $section_style);
				if ($total_cols > 1) {
					$sheet->mergeCells('A' . $body_row . ':' . $last_col_ltr . $body_row);
				}
				$body_row++;
				continue;
			}

			$row_depth = isset($section['depth']) ? (int) $section['depth'] : 0;
			foreach ($section['rows'] as $row) {
				$values      = [];
				$cell_styles = [];

				if ($show_row_labels) {
					$indent        = $row_depth > 0 ? str_repeat('  ', $row_depth) : '';
					$values[]      = $indent . $row['label'];
					$cell_styles[] = $lbl_style;
				}

				foreach ($leaf_columns as $col) {
					$raw          = isset($row['cells'][$col['key']]) ? $row['cells'][$col['key']] : null;
					$values[]     = ($raw !== null) ? (float) $raw : '';
					$cell_styles[] = !empty($col['group_start']) ? $num_group_style : $num_style;
				}

				$sheet->writeRow($values, null, $cell_styles);
				$body_row++;
			}
		}

		// ── Column widths ─────────────────────────────────────────────────────
		if ($show_row_labels) {
			$sheet->setColWidth('A', 20);
		}
		for ($i = 0; $i < $col_count; $i++) {
			$sheet->setColWidth(\avadim\FastExcelWriter\Excel::colLetter($data_col_start + $i), 12);
		}

		// ── Save to temp file and return raw bytes ────────────────────────────
		$tmp_dir = FCPATH . 'datafiles/tmp';
		if (!is_dir($tmp_dir)) {
			mkdir($tmp_dir, 0755, true);
		}
		$tmp_file = $tmp_dir . '/' . uniqid('xlsx_export_') . '.xlsx';
		$excel->save($tmp_file);
		$bytes = file_get_contents($tmp_file);
		@unlink($tmp_file);

		return $bytes;
	}
}
