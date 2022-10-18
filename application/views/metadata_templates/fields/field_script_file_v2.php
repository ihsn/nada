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


    <?php /* ?>
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
    <?php */ ?>



    <div id="accordion-script-files">
        
        <?php $k=0;foreach($data as $script):$k++;?>
            <div class="script-file-container border-top">
                    <div class="mb-0" >
                        <label class="my-0 font-weight-bold" for="chk_<?php echo $k;?>"><?php echo $script['title'];?></label>
                    </div>                
                
                <div id="script-body-<?php echo $k;?>" xclass="collapse show" aria-labelledby="script-<?php echo $k;?>" data-parent="#accordion-script-files">
                    <div class="card-bodyx" style="padding:15px;padding-left:0px;">
                        <?php foreach($script_file_template as $field_name=>$field_type):?>
                            <?php $value=get_field_value($field_name,$script); ?>
                            <?php //echo render_field($field_type,'metadata.project_desc.scripts.'.$field_name,$value);?>
                            <?php //var_dump($field_name);?>
                            <?php //var_dump($value);?>

                            <?php if(empty($value) || $value==''){continue;}?>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-secondary"><?php echo t('metadata.project_desc.scripts.'.$field_name);?></div>
                                </div>
                                <div class="col">
                                    <?php if($field_name=='zip_package' || $field_name=='file_name'):?>
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
        </div>
        <?php endforeach;?>
        
    </div>
    </div>
</div>


<script>
$(document).ready(function()  {
    $('.collapse').collapse();
    script_selection_status();
});


$( "#script_chk_toggle" ).on( "click", function(e) {
   var $toggle=$(this).prop("checked");
   $(".chk_script_download").prop( "checked", $toggle);
   script_selection_status();
});


$( ".chk_script_download" ).on( "click", function(e) {
    script_selection_status();
});

$( ".script_batch_download" ).on( "click", function(e) {
    download_script_files();
});

function script_selection_status(){
    var $selection=$('.chk_script_download:checked').length;

    if ($selection>0){
        //$(".script_download_selection").html($selection + " files selected");
        $(".script_batch_download").show();
    }else{
        $(".script_download_selection").html("");
        $(".script_batch_download").hide();
    }
}

function download_script_files()
{
    var links = [];
    $(".chk_script_download:checked").each(function() {
        links.push( $(this).closest(".script-file-container").find(".download_script").attr("href"));
    });

    //unique urls
    links = $.grep(links, function(v, k){
        return $.inArray(v ,links) === k;
    });

    //start downloads
    for(i=0;i<links.length;i++){
        console.log(links[i]);
        window.open(links[i]);
    };
}
</script>

<?php endif;?>