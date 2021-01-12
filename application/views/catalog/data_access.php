<?php
    $selected_data_class= get_form_value("data_class_id",isset($data_class_id) ? $data_class_id : '');
    $selected_formid= get_form_value("formid",isset($formid) ? $formid : '');
?>
<style>
.info{
    font-style:italic;
    color:gray;
    font-size:smaller;
}
</style>
<form method="post" id="form-data-classification" action="<?php echo site_url('/admin/catalog/update');?>">

    <div class="row">
    <div class="col-md-4">
    <input type="hidden" name="sid" value="<?php echo $id;?>"/>

    <div class="form-group"> 
        <label><?php echo t('data_classification');?></label>
        
        <select name="data-classification-code" id="data-classifications" class="form-control">            
            <option value="" data-target="select-none" >- Select -</option>
            <?php foreach($data_classifications as $class_code=>$class_row):?>
                <option value="<?php echo $class_row['id'];?>" data-target="select-<?php echo $class_code;?>" ><?php echo t($class_row['title']);?></option>                
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
    <div class="col-md-4">
        <label><?php echo t('data_access');?></label>
        <select  id="select-none" class="form-control data-access-dropdown">
            <option>- Select -</option>
        </select>
        <?php foreach($data_access_options as $classification_code=>$data_licenses):?>            
            <select style="display:none"  name="<?php echo $classification_code;?>" id="select-<?php echo $classification_code;?>" class="data-access-dropdown form-control">
                <option> - Select - </option>
                <?php foreach($data_licenses as $license_):?>
                    <?php if(isset($data_access_types[$license_])):?>
                    <option 
                        value="<?php echo $data_access_types[$license_]['formid'];?>"
                        <?php echo ($selected_formid==$data_access_types[$license_]['formid']) ? 'selected="selected"' :'';?>
                    ><?php echo t($data_access_types[$license_]['fname']);?></option>
                    <?php endif;?>
                <?php endforeach;?>
            </select>
        <?php endforeach;?>
        
        <?php if(isset($formid)):?>
            <span class="info"><?php echo $this->forms_list[$formid];?></span>
        <?php endif;?>
    </div>

    <div class="col-md-4">
        <div class="form-group" style="margin-top:25px;">            
            <div>
            <input type="submit" id="data-access-submit" value="<?php echo t('update');?>" name="submit" class="btn btn-primary"/>
            <!--<a href="<?php echo site_url('admin/catalog/edit/'.$id);?>" class="btn btn-link"><?php echo t('cancel');?></a>-->
            </div>
        </div>
    </div>

    </div>
</form>



<script>
$(document).ready(function(){
    $('#data-classifications').on('change', function() {
       var target=$(this).find(":selected").attr("data-target");
	   var id=$(this).attr("id");
       console.log(target);
      $(".data-access-dropdown").hide();
     $("#"+target).show();
    });

    <?php if($selected_data_class>0):?>
        //set initial selection
        $("#data-classifications").val(<?php echo $selected_data_class;?>).change()
    <?php endif;?>

    $('#data-access-submit').on('click', function() {
       set_data_access();
       return false;
    });


    function set_data_access(){
        let data_class_target=$("#data-classifications").find(":selected").attr("data-target");
        data = {
            sid: <?php echo $id;?>,
            data_class_id: $("#data-classifications").val(),
            formid: $('#'+data_class_target).val()
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