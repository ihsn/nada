<?php
    $this->load->helper('display_date');
    $show_empty=false;
    if(isset($options['show_empty'])){
        $show_empty=$options['show_empty'];
    }
    $display_options=isset($template['display_options']) && is_array($template['display_options'])
        ? $template['display_options']
        : array();
?>
<?php if ( (isset($data) && $data !='') || $show_empty==true ):?>
<div class="mb-2 field field-<?php echo str_replace(".'","-",$template['key']);?>">
    <div class="font-weight-bold field-title"><?php echo display_template_resolve_title($template['key'],$template['title']);?></div>
    <div class="field-value">
        <?php if (is_array($data)):?>
        <?php foreach($data as $value):?>
            <span><?php echo format_display_template_date($value, $display_options);?></span>
        <?php endforeach;?>
        <?php else:?>
            <?php if(!empty($data) || $show_empty==true):?>
                <span><?php echo !empty($data) ? format_display_template_date($data, $display_options) : '-';?></span>
            <?php endif;?>
        <?php endif;?>
    </div>
</div>
<?php endif;?>
