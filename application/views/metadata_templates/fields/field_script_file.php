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
    <div id="accordion-script-files">
        <?php $k=0;foreach($data as $script):$k++;?>
            <div class="card">
            <div class="card-header" id="script-<?php echo $k;?>">
                <h6 class="mt-1 mb-0 accordion-title float-left" 
                    data-toggle="collapse" 
                    data-target="#script-body-<?php echo $k;?>" 
                    aria-expanded="true" 
                    aria-controls="script-body-<?php echo $k;?>"
                    >                
                    <i class="fa" aria-hidden="true"></i>
                    <?php echo $script['title'];?>
                </h6>
                <?php if (isset($script['file_name'])):?> 
                    <?php if (isset($options['resources']) && array_key_exists($script['file_name'],$options['resources'])):?>                        
                        <?php 
                            $resource = $options['resources'][basename($script['file_name'])];
                        ?>
                        <a 
                            href="<?php echo site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}");?>" 
                            class="btn btn-primary btn-sm float-right"
                            >
                            <i class="fa fa-download" aria-hidden="true"></i>
                            <?php echo t('download');?>
                        </a>
                    <?php endif;?>
                    <?php //for zip packages ?>
                    <?php if (isset($options['resources']) && 
                            isset($script['zip_package']) &&                             
                            !array_key_exists($script['file_name'],$options['resources']) &&
                            array_key_exists(basename($script['zip_package']),$options['resources'])):?>                        
                        <?php 
                            $resource = $options['resources'][basename($script['zip_package'])];                            
                        ?>
                        <a 
                            href="<?php echo site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}");?>" 
                            class="btn btn-primary btn-sm float-right"
                            >
                            <i class="fa fa-download" aria-hidden="true"></i>
                            <?php echo t('download');?>
                        </a>
                    <?php endif;?>
                <?php endif;?>
            </div>

            <div id="script-body-<?php echo $k;?>" class="collapse show" aria-labelledby="script-<?php echo $k;?>" xdata-parent="#accordion-script-files">
                <div class="card-body" style="padding:15px;">
                <?php foreach($script_file_template as $field_name=>$field_type):?>
                            <?php $value=get_field_value($field_name,$script); ?>

                            <?php if(empty($value) || $value==''){continue;}?>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-secondary"><?php echo t('metadata.project_desc.scripts.'.$field_name);?></div>
                                </div>
                                <div class="col">
                                    <?php if($field_name=='zip_package' || $field_name=='file_name'):?>
                                        <?php
                                            $value=basename($value);
                                        ?>
                                        <?php if (isset($options['resources']) &&                 
                                                array_key_exists($value,$options['resources'])):?>                        
                                            <?php 
                                                $resource = $options['resources'][$value];
                                            ?>
                                            <a class="download_script download_<?php echo $field_name;?>" href="<?php echo site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}");?>" >
                                            <i class="fa fa-download" aria-hidden="true"></i> <?php print_r($value);?>
                                            </a>
                                        <?php else:?>
                                            <?php echo $value;?>
                                        <?php endif;?>
                                    <?php elseif(is_array($value)):?>
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