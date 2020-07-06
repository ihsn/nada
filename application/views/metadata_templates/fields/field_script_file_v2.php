<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php
    //script file template
    $script_file_template=array(
    //"title"=>'text',
    "file_name" =>'text',
    "zip_package" =>'text',
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



    <?php
        $zip_packages=array_filter(array_unique(array_column($data,'zip_package')));
    ?>
    
    <?php if(!empty($zip_packages) && !empty($options['resources'])):?>

        <div class="script-zip-packages"></div>
            <div class="field-value">
                <table class="table table-sm table-striped">
                <?php foreach($zip_packages as $zip_package):?>
                    <?php if (isset($options['resources']) &&                 
                            array_key_exists($zip_package,$options['resources'])):?>                        
                        <?php 
                            $resource = $options['resources'][$zip_package];
                        ?>
                        <tr>
                            <td><?php echo $zip_package;?></td>                            
                            <td><a 
                                        href="<?php echo site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}");?>" 
                                        class="btn btn-danger btn-sm btn-xs float-right"
                                        >
                                        <i class="fa fa-download" aria-hidden="true"></i>
                                        <?php echo t('download');?>
                                </a>
                            </td>
                        </tr>
                    <?php endif;?>
                <?php endforeach;?>
                </table>
            </div>        

    <?php endif;?>



    <div id="accordion-script-files">
        <?php $k=0;foreach($data as $script):$k++;?>
            <div class="card">
            
                <h6 class="mt-1 border-bottom pl-2 pb-2 pt-2" >
                    <?php echo $script['title'];?>
                </h6>                
            

            <div id="script-body-<?php echo $k;?>" xclass="collapse show" aria-labelledby="script-<?php echo $k;?>" data-parent="#accordion-script-files">
                <div class="card-body" style="padding:15px;padding-top:0px;">
                    <?php foreach($script_file_template as $field_name=>$field_type):?>
                        <?php $value=get_field_value($field_name,$script); ?>
                        <?php //echo render_field($field_type,'metadata.project_desc.scripts.'.$field_name,$value);?>
                        <?php //var_dump($field_name);?>

                        <?php if(empty($value)){continue;}?>
                        <div class="row">
                            <div class="col-md-2">
                                <strong><?php echo t('metadata.project_desc.scripts.'.$field_name);?></strong>
                            </div>
                            <div class="col">
                                <?php if(is_array($value)):?>
                                    <?php echo render_field($field_type,'metadata.project_desc.scripts.'.$field_name,$value,array('hide_field_title'=>true));?>
                                <?php else:?>
                                    <?php print_r($value);?>
                                <?php endif;?>
                            </div>
                        </div>
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