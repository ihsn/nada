<?php
/**
 * Array as bullet list (first column or flat list).
 */
$this->load->helper('display_template');

$hide_column_headings = false;
if (isset($options['hide_column_headings'])) {
	$hide_column_headings = $options['hide_column_headings'];
}

$field_title = isset($template['title']) ? (string) $template['title'] : '';
$field_key = isset($template['key']) ? (string) $template['key'] : 'field';
$list_scalar_def = array(
	'type' => 'string',
	'display_options' => array('format' => 'plain', 'linkify' => false),
);
?>
<?php if (isset($data) && is_array($data) && count($data) > 0):?>
<div class="table-responsive field field-<?php echo str_replace('.', '_', html_escape($field_key));?>">
    <?php if ($hide_column_headings !== true):?>
    <div class="field-title"><?php echo display_template_resolve_title($field_key, $field_title);?></div>
    <?php endif;?>
    <div class="field-value">
        <?php if (isset($data[0]) && is_array($data[0])):?>
            <?php
            if (!isset($columns)) {
                $columns = array_keys($data[0]);
            }
            ?>
            <ul>
            <?php foreach ($data as $row):?>
                <?php
                $cell_val = isset($row[$columns[0]]) ? $row[$columns[0]] : '';
                $cell_html = display_template_render_scalar_value($cell_val, $list_scalar_def, array('mode' => 'cell'));
                ?>
                <?php if ($cell_html !== ''):?>
                <li><?php echo $cell_html;?></li>
                <?php endif;?>
            <?php endforeach;?>
            </ul>
        <?php else:?>
            <ul>
            <?php foreach ($data as $entry):?>
                <?php $cell_html = display_template_render_scalar_value($entry, $list_scalar_def, array('mode' => 'cell')); ?>
                <?php if ($cell_html !== ''):?>
                <li><?php echo $cell_html;?></li>
                <?php endif;?>
            <?php endforeach;?>
            </ul>
        <?php endif;?>
    </div>
</div>
<?php endif;?>
