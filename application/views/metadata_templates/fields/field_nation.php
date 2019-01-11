<?php //var_dump($data);return;?>
<?php if ($data):?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
        <?php /*<span><?php echo implode(", ",array_column($data,'name'));?></span>*/?>
        <span><?php echo implode(", ", $data);?></span>
    </div>
</div>
<?php endif;?>
