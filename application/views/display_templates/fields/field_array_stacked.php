<?php
/**
 * Nested array as stacked records: root heading, then each row as a sub-block.
 * Columns use the same display_options as top-level fields.
 *
 * display_options.header_fields — column keys for each record heading (first non-empty).
 */
$this->load->helper('display_template');
$this->load->helper('display_date');

$columns = display_template_filter_props(isset($template['props']) ? $template['props'] : array());
if (count($columns) < 1) {
	return false;
}

if (!isset($data) || !is_array($data)) {
	return false;
}

$data = array_remove_empty($data);
if (count($data) < 1) {
	return false;
}

$field_key = isset($template['key']) ? (string) $template['key'] : 'field';
$css_id = str_replace('.', '_', $field_key);
$root_title = display_template_resolve_title($field_key, isset($template['title']) ? $template['title'] : '');
$view_data = array(
	'resources' => isset($resources) ? $resources : array(),
);
?>
<div id="<?php echo html_escape($css_id);?>" class="mb-3 field field-array-stacked field-<?php echo html_escape($css_id);?>">
	<div class="field-title"><?php echo $root_title;?></div>
	<?php foreach ($data as $row):?>
		<?php if (!is_array($row)) { continue; } ?>
		<?php
			$prop_html = array();
			foreach ($columns as $column) {
				$html = display_template_render_nested_row_prop($row, $column, $view_data);
				if ($html !== '') {
					$prop_html[] = $html;
				}
			}
			if (count($prop_html) < 1) {
				continue;
			}
			$record_heading = display_template_nested_record_heading($row, $template);
		?>
		<div class="field-stacked-record mb-3">
			<?php if ($record_heading !== ''):?>
				<h5 class="field-stacked-record-heading"><?php echo html_escape($record_heading);?></h5>
			<?php endif;?>
			<?php echo implode('', $prop_html);?>
		</div>
	<?php endforeach;?>
</div>
