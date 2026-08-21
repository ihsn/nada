<?php
/**
 * Table field.
 *
 * Nested array / nested_array / object columns render as a compact inner table
 * in the cell, with column headings (unless hide_column_headings is set on that prop).
 *
 * Template flags:
 *  - hide_column_headings
 *  - hide_field_title
 *  - is_nested_cell — compact cell sub-table (no field title / outer chrome)
 *  - display_options.scrollable
 */
if (!isset($data) || !is_array($data) || count($data) < 1) {
	return false;
}
if (!isset($template['props'])) {
	return false;
}

$this->load->helper('display_template');
$this->load->helper('display_date');
$columns = display_template_filter_props($template['props']);
if (count($columns) < 1) {
	return false;
}

$is_nested_cell = !empty($template['is_nested_cell']);
$hide_field_title = isset($template['hide_field_title']) ? $template['hide_field_title'] : $is_nested_cell;
$hide_column_headings = isset($template['hide_column_headings']) ? $template['hide_column_headings'] : false;

$non_empty_columns = array();
foreach ($columns as $column) {
	$column_data = array_filter(array_column($data, $column['key']));
	if (!empty($column_data)) {
		$non_empty_columns[] = $column;
	}
}
if (count($non_empty_columns) < 1) {
	return false;
}
$columns = $non_empty_columns;
$data = array_remove_empty($data);
if (count($data) < 1) {
	return false;
}

$table_scrollable = !$is_nested_cell && !empty($template['display_options']['scrollable']);
$table_container_class = $table_scrollable ? 'table-overflow-max-height-400' : '';
$field_key = isset($template['key']) ? (string) $template['key'] : 'field';
$wrapper_class = $is_nested_cell
	? 'field-array-nested'
	: 'table-responsive field field-' . str_replace('.', '_', $field_key);
$table_class = 'table table-sm table-bordered table-striped table-condensed xsl-table table-grid';
if ($is_nested_cell) {
	$table_class .= ' mb-0';
}

$nested_column_types = array('array', 'nested_array', 'simple_array', 'object');
?>
<div class="<?php echo html_escape($wrapper_class); ?>">
<?php if ($hide_field_title != true): ?>
	<div class="field-title"><?php echo tt('metadata.' . $template['key'], $template['title']); ?></div>
<?php endif; ?>
<div class="<?php echo html_escape($table_container_class); ?>">
<table class="<?php echo html_escape($table_class); ?>">
	<?php if ($hide_column_headings != true): ?>
	<tr>
		<?php foreach ($columns as $column): ?>
			<th><?php echo display_template_field_label($column); ?></th>
		<?php endforeach; ?>
	</tr>
	<?php endif; ?>
	<?php foreach ($data as $row): ?>
	<tr>
		<?php foreach ($columns as $column): ?>
		<td>
			<?php if (in_array($column['type'], $nested_column_types, true)): ?>
				<?php
					$nested_data = isset($row[$column['key']]) ? $row[$column['key']] : array();
					if (display_template_is_pdf_context()) {
						echo display_template_render_pdf_nested_cell($nested_data, $column);
					} else {
						$nested_template = $column;
						$nested_template['hide_field_title'] = true;
						$nested_template['is_nested_cell'] = true;
						$nested_opts = isset($column['display_options']) && is_array($column['display_options'])
							? $column['display_options']
							: array();
						if (array_key_exists('hide_column_headings', $column)) {
							$nested_template['hide_column_headings'] = (bool) $column['hide_column_headings'];
						} elseif (array_key_exists('hide_column_headings', $nested_opts)) {
							$nested_template['hide_column_headings'] = (bool) $nested_opts['hide_column_headings'];
						} else {
							$nested_template['hide_column_headings'] = false;
						}
						echo $this->load->view(
							'display_templates/fields/field_array',
							array('data' => $nested_data, 'template' => $nested_template),
							true
						);
					}
				?>
			<?php else: ?>
				<?php
					$cell_val = isset($row[$column['key']]) ? $row[$column['key']] : '';
					echo display_template_render_scalar_value($cell_val, $column, array('mode' => 'cell'));
				?>
			<?php endif; ?>
		</td>
		<?php endforeach; ?>
	</tr>
	<?php endforeach; ?>
</table>
</div>
</div>
