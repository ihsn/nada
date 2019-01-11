<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php
    //script file template
    $script_file_template=array(
    "title"=>'text',
    "file_name" =>'text',
    "description" =>'text',
    "authors" =>'array',
    "date" =>'text',
    "format" =>'text',
    "software" =>'text',                    
    "methods" =>'text',
    "dependencies" =>'text',
    "instructions" =>'text',
    "format" =>'text',
    "source_code_repo" =>'text',
    "notes" =>'text',
);    
?>

<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
    <div id="accordion-script-files">
        <?php $k=0;foreach($data as $script):$k++;?>
            <div class="card">
            <div class="card-header" id="script-<?php echo $k;?>">
                <h5 class="mb-0 accordion-title" data-toggle="collapse" data-target="#script-body-<?php echo $k;?>" aria-expanded="true" aria-controls="script-body-<?php echo $k;?>">                
                <i class="fa float-right" aria-hidden="true"></i>
    
                <?php echo $script['title'];?>                
                </h5>
            </div>

            <div id="script-body-<?php echo $k;?>" class="collapse show" aria-labelledby="script-<?php echo $k;?>" data-parent="#accordion-script-files">
                <div class="card-body" style="padding:15px;">
                    <?php foreach($script_file_template as $field_name=>$field_type):?>
                        <?php $value=get_field_value($field_name,$script); ?>
                        <?php echo render_field($field_type,'script_file.'.$field_name,$value);?>
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