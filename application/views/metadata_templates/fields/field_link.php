<?php //var_dump($data);return;?>
<?php
    $show_empty=false;
    $link_text="Link";
    if(isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }
    if(isset($options['link_text'])){
        $link_text=$options['link_text'];
    }
?>
<?php if ( (isset($data) && $data !='') || $show_empty==true ):?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <?php if (is_array($value)){
                $value=implode(" ",$value);
            }?>
            <span><a href="<?php echo nl2br(html_escape($value));?>"><?php echo $link_text;?></a></span>
        <?php endforeach;?>
        <?php else:?>
            <?php if(!empty($data)):?>
                <span><a href="<?php echo nl2br(html_escape($data));?>"><?php echo $link_text;?></a></span>
            <?php else: //for empty values when show_empty is true ?>
                -
            <?php endif;?>

        <?php endif;?>
    </div>
</div>
<?php endif;?>
