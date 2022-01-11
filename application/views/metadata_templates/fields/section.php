<?php $output="";?>
<?php foreach($fields as $field_name=>$field_type):?>
    <?php $value=get_field_value($field_name,$metadata); ?> 
    <?php if (is_array($field_type)):?>
        <?php $output.= render_field($field_type[0],$field_name,$value,$options=$field_type['options']);?>
    <?php else:?>
        <?php $output.= render_field($field_type,$field_name,$value,$options);?>
    <?php endif;?>
<?php endforeach;?>

<?php if (trim($output)!=""):?>    
    <div class="section-<?php echo $section_name;?>">
        <?php if (trim($section_name)!=""):?>
            <h2 id="metadata-<?php echo $section_name;?>" class="xsl-subtitle"><?php echo t($section_name);?></h2>
        <?php endif;?>
        <?php echo $output;?>
    </div>
    
<?php endif;?>
