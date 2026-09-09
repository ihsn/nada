<?php
/**
 * Array or simple_array as badge chips (legacy presentationForm).
 */
$field_key = isset($template['key']) ? (string) $template['key'] : (isset($name) ? (string) $name : 'field');
$field_title = isset($template['title']) ? (string) $template['title'] : $field_key;
$opts = isset($template['display_options']) && is_array($template['display_options'])
	? $template['display_options']
	: (isset($options) && is_array($options) ? $options : array());
$hide_column_headings = !empty($opts['hide_column_headings']);
$badge_class = isset($opts['badge_class']) ? (string) $opts['badge_class'] : 'badge badge-pill badge-light';
?>
<?php if (isset($data) && is_array($data) && count($data) > 0): ?>
<div class="table-responsive field field-<?php echo str_replace('.', '__', html_escape($field_key)); ?>">
	<?php if ($hide_column_headings !== true): ?>
	<div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title); ?></div>
	<?php endif; ?>
	<div class="field-value">
		<?php if (isset($data[0]) && is_array($data[0])): ?>
			<?php
			$columns = isset($columns) ? $columns : array_keys($data[0]);
			$first = isset($columns[0]) ? $columns[0] : null;
			?>
			<?php foreach ($data as $row): ?>
				<?php if ($first !== null && isset($row[$first]) && $row[$first] !== ''): ?>
				<span class="<?php echo html_escape($badge_class); ?>"><?php echo html_escape($row[$first]); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php else: ?>
			<?php foreach ($data as $row): ?>
				<?php if ($row !== '' && $row !== null): ?>
				<span class="<?php echo html_escape($badge_class); ?>"><?php echo html_escape(is_scalar($row) ? $row : ''); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>
