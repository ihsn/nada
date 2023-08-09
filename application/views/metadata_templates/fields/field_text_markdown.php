<?php //var_dump($data);return;?>
<?php
    $show_empty=false;
    if(isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }
?>
<?php if ( (isset($data) && $data !='') || $show_empty==true ):?>
<div class="field field-markdown field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <?php if (is_array($value)){
                $value=implode(" ",$value);
            }?>
            <span><?php echo ((markdown_parse(xss_clean($value))));?></span>
        <?php endforeach;?>
        <?php else:?>
            <?php if(!empty($data)):?>
                <span><?php echo ((markdown_parse(xss_clean($data))));?></span>
            <?php else: //for empt values when show_empty is true ?>
                -
            <?php endif;?>

        <?php endif;?>
    </div>
</div>
<?php endif;?>