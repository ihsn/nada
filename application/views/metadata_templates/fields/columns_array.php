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
        <?php foreach($fields as $value):?>
            <?php if ($show_empty==true || ($show_empty==false && !empty($value))):?>
            <div class="<?php echo $column_class;?>">
                <?php echo $value;?>
            </div>
            <?php endif;?>
        <?php endforeach;?>
    </div>
    
</div>
    
