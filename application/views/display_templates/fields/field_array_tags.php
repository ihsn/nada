<?php
/**
 * 
 * Array to comma seperated list
 *
 *  options
 * 
 *  - hide_column_headings - hide column headings 
 *  - badge class - a single or multiple classes 
 */

 //"field_template": "field_table_tags",
//"tag_column":"name"

 $this->load->helper('display_template');
 $columns=display_template_filter_props($template['props']);
 $badge_class="badge badge-pill badge-light badge-tags";

 if(isset($options['badge_class'])){
    $badge_class=$options['badge_class'];
 }

 //var_dump($template);
 if ($template['type']=='array'){

 }

 $column_name=false;
 if (isset($template['display_options']['tag_column'])){
    $column_name=$template['display_options']['tag_column'];
 }
 else{
    $column_name='name';
 }
 $tag_scalar_def=array(
    'type'=>'string',
    'display_options'=>array('format'=>'plain','linkify'=>false),
 );
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field-<?php echo html_escape($template['key']);?> pb-3">
        <div class="field-title"><?php echo display_template_resolve_title($template['key'],$template['title']);?></div>
        <div class="field-value">
            <?php foreach($data as $row):?>
                <?php
                    $tag_val = isset($row[$column_name]) ? $row[$column_name] : '';
                    $tag_html = display_template_render_scalar_value($tag_val, $tag_scalar_def, array('mode'=>'cell'));
                ?>
                <?php if ($tag_html !== ''):?>
                <span class="<?php echo $badge_class;?>"><?php echo $tag_html;?></span>
                <?php endif;?>
            <?php endforeach;?>
        </div>
</div>
<?php endif;?>