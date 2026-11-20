<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<?php
/**
 *
 * Simple array (list of scalars)
 *
 *  display_options
 *   - linkify / is_uri / format - same as scalar fields, applied to each value
 *   - hide_field_title - hide the field caption
 *
 * A single value is shown as plain text; several values as a list.
 */
 $this->load->helper('display_template');

 $data = array_remove_empty($data);
 $display_opts = isset($template['display_options']) && is_array($template['display_options']) ? $template['display_options'] : array();
 $hide_field_title = !empty($template['hide_field_title']) || !empty($display_opts['hide_field_title']);

 // each value is rendered like a scalar field with the same display options
 $value_def = array('type' => 'string', 'display_options' => $display_opts);
 unset($value_def['display_options']['renderer'], $value_def['display_options']['field_template']);

 $items = array();
 foreach ($data as $row) {
     if (is_array($row)) {
         $row = implode(' ', array_filter($row, 'is_scalar'));
     }
     $html = display_template_render_scalar_value($row, $value_def, array('mode' => 'cell'));
     if ($html !== '') {
         $items[] = $html;
     }
 }
 $field_key = isset($template['key']) ? (string) $template['key'] : '';
?>
<?php if (count($items) > 0):?>
<div class="mb-2 field field-<?php echo html_escape(str_replace('.', '-', $field_key));?>">
    <?php if (!$hide_field_title):?>
        <div class="font-weight-bold field-title"><?php echo display_template_resolve_title($field_key, isset($template['title']) ? $template['title'] : '');?></div>
    <?php endif;?>
    <div class="field-value">
        <?php if (count($items) === 1):?>
            <?php echo $items[0];?>
        <?php else:?>
        <ul class="mb-0">
        <?php foreach($items as $item):?>
            <li><?php echo $item;?></li>
        <?php endforeach;?>
        </ul>
        <?php endif;?>
    </div>
</div>
<?php endif;?>
<?php endif;?>
