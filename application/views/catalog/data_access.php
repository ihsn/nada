<?php
    $selected_data_class= get_form_value("data_class_id",isset($data_class_id) ? $data_class_id : '');
    $selected_formid= get_form_value("formid",isset($formid) ? $formid : '');

?>


<?php $data_access_list=array();?>

<style>
.info{
    font-style:italic;
    color:gray;
    font-size:smaller;
}
</style>

<?php echo form_open(site_url('/admin/catalog/update'), 'id="form-data-classification" ');?>

    <div class="row">
    <?php if ($data_classifications_enabled!==false):?>
        <div class="col-md-6" >
            <input type="hidden" name="sid" value="<?php echo $id;?>"/>

            <div class="form-group"> 
                <label><?php echo t('data_classification');?></label>
                
                <select name="data-classification-code" id="data-classifications" class="form-control">            
                    <option value="" data-target="select-none" >- Select -</option>
                    <?php foreach($data_classifications as $class_code=>$class_row):?>
                        <option 
                            value="<?php echo $class_row['id'];?>" 
                            data-code="<?php echo $class_code;?>"
                            <?php if ($class_row['id']==$data_class_id){ echo 'selected="selected"'; }?>
                        ><?php echo t($class_row['title']);?></option>                
                    <?php endforeach;?>
                </select>

                <?php if (isset($data_class_id)):?>
                    <?php foreach($data_classifications as $data_class_row):?>
                        <?php if ($data_class_row['id']==$data_class_id) :?>
                            <span class="info" ><?php echo  $data_class_row['title'];?></span>
                        <?php endif;?>
                    <?php endforeach;?>
                <?php endif;?>
            </div>
        </div>
    <?php endif;?>

    <div class="col-md-6">
        <label><?php echo t('data_access');?></label>
        <div class="data-access-container">
            <?php echo $data_access_dropdown;?>
        </div>
                
        <?php if(isset($formid) && isset($data_licenses[$formid])):?>
            <span class="info"><?php echo $data_licenses[$formid];?></span>
        <?php endif;?>
    </div>

    </div>

    <div class="row">
        <div class="col-md-12 form-group link-da">
            <label for="link_da"><?php echo t('remote_data_access_url');?></label>
            <input name="link_da" type="text" id="link_da" class="form-control" value="<?php echo get_form_value('link_da',isset($link_da) ? $link_da : ''); ?>"/>
        </div>

        <div class="col-md-12 study-microdata model-<?php echo $model;?>">
        <?php if (count($microdata_files)>0):?>
                <div class="microdata-applies-to"><?php echo t('data_selection_apply_to_files');?></div>
                        <ul>
                        <?php foreach($microdata_files as $mf):?>
                        <li><?php echo basename($mf['filename']);?></li>
                        <?php endforeach;?>
                        </ul>
        <?php else:?>
                <div class="text-danger"><span class="glyphicon glyphicon-alert red" aria-hidden="true"></span> <?php echo sprintf(t('study_no_data_files_assigned'),'');?></div>
        <?php endif;?>
        </div>
    </div>

    <div class="form-group" style="margin-top:25px;">            
            <div>
            <input type="submit" id="data-access-submit" value="<?php echo t('update');?>" name="submit" class="btn btn-primary"/>
            <!--<a href="<?php echo site_url('admin/catalog/edit/'.$id);?>" class="btn btn-link"><?php echo t('cancel');?></a>-->
            </div>
        </div>

    

<?php form_close();?>


<script>

//show/hide remote data access text box
function sh_remote_da_link()
{
    if ($(".data-access-dropdown").val()==5){
		$(".link-da").show();
	}
	else{
		$(".link-da").hide();
	}
}

function load_data_access_dropdown()
{
    let url=CI.base_url + '/admin/catalog/da_by_class/' + $('#data-classifications').val();
    $.get(url, function( data ) {
        $(".data-access-container").html(data);
    });   
}

$(document).ready(function(){
    //show/hide remote da url depending on the da form selected
    $( "#form-data-classification" ).on( "change", ".data-access-dropdown", function() {
        sh_remote_da_link();
    });    
    

    //show/hide da
    //sh_remote_da_link();
    $('#data-classifications').on('change', function() {
        load_data_access_dropdown();
    });
    

    $('#data-access-submit').on('click', function() {
       set_data_access();
       return false;
    });


    function set_data_access(){
        data = {
            sid: <?php echo $id;?>,
            data_class_id: $("#data-classifications").val(),
            formid: $('#select-data-access').val(),
            link_da: $("#link_da").val(),
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        };

        url=CI.base_url+'/admin/catalog/update';
        $.ajax({
            type: "POST",
            url: url,
            cache: false,
            timeout:30000,
            data:data,
            success: function(data) {
                alert("success");
                return false;
            },
            error: function(XMLHttpRequest, textStatus, errorThrow) {
                alert(XMLHttpRequest.responseText);
                return false;
            }
        });
    }
});
</script>