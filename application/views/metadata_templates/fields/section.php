<?php $output="";?>
<?php foreach($fields as $field_name=>$field_type):?>
    <?php $value=get_field_value($field_name,$metadata); ?>
    <?php $output.= render_field($field_type,$field_name,$value,$options);?>
<?php endforeach;?>

<?php if (trim($output)!=""):?>
    <div class="section-<?php echo $section_name;?>">
        <h2 id="metadata-<?php echo $section_name;?>" class="xsl-subtitle"><?php echo t($section_name);?></h2>
        <?php echo $output;?>
    </div>
<?php endif;?>
