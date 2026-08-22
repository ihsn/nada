<?php
/**
 * Nested array as collapsed cards.
 * Columns use the same display_options as top-level fields.
 *
 * display_options.header_fields — column keys for each card title (first non-empty).
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
<div id="<?php echo html_escape($css_id);?>" class="mb-3 field field-accordion field-<?php echo html_escape($css_id);?>">
	<div class="field-title"><?php echo $root_title;?></div>
	<?php foreach ($data as $idx => $row):?>
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
			if ($record_heading === '') {
				$record_heading = isset($template['title']) ? (string) $template['title'] : $root_title;
			}
			$collapse_id = $css_id . '_' . (int) $idx;
		?>
		<div class="card mb-1">
			<div class="card-header-x card-heading bg-light border-bottom p-2" id="heading-<?php echo html_escape($collapse_id);?>">
				<a
					href="#collapse-<?php echo html_escape($collapse_id);?>"
					class="collapsed d-block"
					data-toggle="collapse"
					data-target="#collapse-<?php echo html_escape($collapse_id);?>"
					aria-expanded="false"
					aria-controls="collapse-<?php echo html_escape($collapse_id);?>"
				>
					<i class="fa float-right mt-1" aria-hidden="true"></i>
					<?php echo html_escape($record_heading);?>
				</a>
			</div>
			<div
				id="collapse-<?php echo html_escape($collapse_id);?>"
				class="collapse"
				aria-labelledby="heading-<?php echo html_escape($collapse_id);?>"
			>
				<div class="card-body">
					<?php echo implode('', $prop_html);?>
				</div>
			</div>
		</div>
	<?php endforeach;?>
</div>
