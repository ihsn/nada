<?php 

    $column_class='col-3';//set boostrap column size
    $show_empty=false; //show empty fields

    if (isset($options['column_class'])){
        $column_class=$options['column_class'];
    }

    if (isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }
?>

<div class="section-<?php echo $section_name;?>">
    <?php if (trim($section_name)!=""):?>
        <h2 id="metadata-<?php echo $section_name;?>" class="xsl-subtitle"><?php echo t($section_name);?></h2>
    <?php endif;?>

    <div class="row">
        <?php foreach($fields as $field_name=>$field_type_info):?>
            <?php $value=get_field_value($field_name,$metadata); ?>
            <?php 
                if(is_array($field_type_info)){
                    $field_type=$field_type_info['type'];
                    $options=$field_type_info['options'];
                }else{
                    $field_type=$field_type_info;
                }
            ?>
            <?php if ($show_empty==true || ($show_empty==false && !empty($value))):?>
            <div class="<?php echo $column_class;?>">
                <?php echo render_field($field_type,$field_name,$value,$options);?>
            </div>
            <?php endif;?>
        <?php endforeach;?>
    </div>
    
</div>
    
