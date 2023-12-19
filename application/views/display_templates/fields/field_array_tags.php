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

 $columns=$template['props'];
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
    echo $this->load->view('display_templates/fields/field_array',null,true);
    return;
 }
?>
<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<div class="field-<?php echo $template['key'];?> pb-3">
        <div class="field-title"><?php echo tt('metadata.'.$template['key'],$template['title']);?></div>
        <div class="field-value">
            <?php foreach($data as $row):?>
                <span class="<?php echo $badge_class;?>"><?php echo $row[$column_name];?></span>
            <?php endforeach;?>
        </div>
</div>
<?php endif;?>