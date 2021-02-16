<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php
    //resource fields
    $resource_fields=array(
    "title"=>'text',
    "file_name" =>'text',
    "description" =>'text',
    "resource_id" =>'text',
    "survey_id" =>'text'
);    
?>



<?php foreach($data as $index=>$resource):?>
    <?php //var_dump($resource);?>

    <div>
    <button class="float-right btn btn-primary btn-sm"><i class="fa fa-download" aria-hidden="true"></i> Download</button>
        <strong><?php echo $resource['title'];?></strong>
        <?php echo isset($resource['filename']) ? $resource['filename'] : '' ;?>
        <div><?php echo $resource['description'];?></div>        
    </div>

<?php endforeach;?>  


<?php return;?>


<?php foreach($resource_fields as $field_name=>$field_type):?>
    <?php $value=get_field_value($field_name,$script); ?>
    <?php echo render_field($field_type,'script_file.'.$field_name,$value);?>
<?php endforeach;?>  

<?php return;?>

<h5 class="mb-0 title" >
    <i class="fa float-right" aria-hidden="true"></i>
    <?php echo $script['title'];?>
</h5>



<div class="field field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value">
    <div id="accordion-script-files">
        <?php $k=0;foreach($data as $script):$k++;?>
            <div class="card">

            <div id="resource-download-<?php echo $k;?>" class="collapse show" aria-labelledby="resource-<?php echo $k;?>" data-parent="#accordion-resource-downloads">
                <div class="card-body" style="padding:15px;">
                    <?php foreach($resource_fields as $field_name=>$field_type):?>
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


<script>
$(document).ready(function()  {
    $('.collapse').collapse()
});
</script>

<?php endif;?>