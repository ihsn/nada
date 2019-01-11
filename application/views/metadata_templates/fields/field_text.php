<?php //var_dump($data);return;?>
<?php if (isset($data) && $data !=''):?>
<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <?php if (is_array($value)){
                $value=implode(" ",$value);
            }?>
            <span><?php echo nl2br(html_escape($value));?></span>
        <?php endforeach;?>
        <?php else:?>
            <span><?php echo nl2br(html_escape($data));?></span>
        <?php endif;?>
    </div>
</div>
<?php endif;?>
