<?php
    $show_empty=false;
    if(isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }

    //$key_part=explode(".",$template['key']);
    //$key_part=$key_part[count($key_part)-1];
?>
<?php if ( (isset($data) && $data !='') || $show_empty==true ):?>
<div class="field field-<?php echo str_replace(".'","-",$template['key']);?>">

    <div class="row border-bottom">
        <div class="col-md-4">
            <div class="field-title-inline p-1"><?php echo tt($template['title']);?></div>
            <?php  /* <div class="text-secondary"><?php echo $key_part;?></div> */ ?>
        </div>
        <div class="col-md-8 border-left">
            <div class="field-value">
                <?php if (is_array($data)):?>
                <?php foreach($data as $value):?>
                    <?php if (is_array($value)){
                        $value=implode(" ",$value);
                    }?>
                    <span><?php echo nl2br(html_escape(trim($value)));?></span>
                <?php endforeach;?>
                <?php else:?>
                    <?php if(!empty($data)):?>
                        <span><?php echo nl2br(html_escape(trim($data)));?></span>
                    <?php else: //for empty values when show_empty is true ?>
                        -
                    <?php endif;?>

                <?php endif;?>
            </div>
        </div>
    </div>
</div>
<?php endif;?>
