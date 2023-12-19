<?php //var_dump($data);return;?>
<?php
    $show_empty=false;
    if(isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }
?>
<?php if ( (isset($data) && $data !='') || $show_empty==true ):?>
<div class="mb-2 field field-<?php echo str_replace(".'","-",$template['key']);?>">
    <div class="font-weight-bold field-title"><?php echo tt('metadata.'.$template['key'],$template['title']);?></div>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <?php if (is_array($value)){
                $value=implode(" ",$value);
            }?>
            <span><?php echo ((markdown_parse(xss_clean($data))));?></span>
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
