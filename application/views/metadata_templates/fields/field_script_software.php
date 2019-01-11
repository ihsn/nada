<?php if (isset($data) && is_array($data) && count($data)>0 ):?>


<?php
    //script file template
    $template=array(
    "name"=>'text',
    "version" =>'text',
    "library" =>'array',
);    
?>

<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
    <div id="accordion-sofware-items">
        <?php $k=0;foreach($data as $row):$k++;?>
            <div class="card">
            <div class="card-header" id="software-<?php echo $k;?>">
                <h5 class="mb-0 accordion-title" data-toggle="collapse" data-target="#software-body-<?php echo $k;?>" aria-expanded="true" aria-controls="software-body-<?php echo $k;?>">                
                <i class="fa float-right" aria-hidden="true"></i>
                <?php echo $row['name'];?>                
                </h5>
            </div>

            <div id="software-body-<?php echo $k;?>" class="collapse show" aria-labelledby="software-<?php echo $k;?>" data-parent="#accordion-software-items">
                <div class="card-body" style="padding:15px;">
                    <?php foreach($template as $field_name=>$field_type):?>
                        <?php $value=get_field_value($field_name,$row); ?>
                        <?php echo render_field($field_type,'software.'.$field_name,$value);?>
                    <?php endforeach;?>        
                </div>
            </div>
            </div>
        <?php endforeach;?>
    </div>
    </div>
</div>
<?php endif;?>

<script>
$(document).ready(function()  {
    $('.collapse').collapse()
});
</script>