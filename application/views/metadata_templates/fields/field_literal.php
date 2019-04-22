<?php
/**
 * 
 *  render HTML
 * 
 * options - css_class, style 
 * 
 */

$css_class='';
$style='';

if(isset($options)){
    if(isset($options['css_class'])){
        $css_class=$options['css_class'];
    }
    if(isset($options['css_style'])){
        $style='style="'.$options['css_style'].'"';
    }
}
?>

<?php if (isset($data) && $data !=''):?>
<div class="field field-html field-<?php echo $name;?> <?php echo $css_class;?>" <?php echo $style;?>>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <?php if (is_array($value)){
                $value=implode(" ",$value);
            }?>
            <span><?php echo ($value);?></span>
        <?php endforeach;?>
        <?php else:?>
            <span><?php echo ($data);?></span>
        <?php endif;?>
    </div>
</div>
<?php endif;?>
