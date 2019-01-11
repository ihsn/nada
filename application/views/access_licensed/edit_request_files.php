<?php
/*
* List of files for a request 
* For admin to setup download options
*
*/
?>

<?php if ($files): ?>
	<?php $tr_class=""; ?>
    
	<?php if (count($surveys)==1):?>
    
		<?php foreach($files as $key=>$survey_data): ?>
            <?php if($survey_data):?>
            
            	    <!-- single study grid -->
                <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
                <tr class="header">
                    <th><input type="checkbox" id="chk_toggle"/></th>
                    <th><?php echo t('file');?></th>
                    <th><?php echo t('download_limit');?></th>			
                    <th><?php echo t('expiry');?></th>            
                </tr>

            
            <?php foreach($survey_data as $row): ?>
                <?php $row=(object)$row;//echo '<pre>';var_dump($row); ?>
                <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                <tr class="<?php echo $tr_class; ?>">
                    <td><input class="chk" type="checkbox" value="<?php echo $row->resource_id;?>" name="fileid-<?php echo $row->resource_id;?>" <?php  echo isset($row->download['download_limit']) ? 'checked="checked"' : ''; ?>/></td>
                    <td><?php echo basename($row->filename); ?></td>
                    <td><input type="text" class="download-limit" name="download-limit-<?php echo $row->resource_id;?>" maxlength="2" size="2" value="<?php echo isset($row->download['download_limit']) ? $row->download['download_limit'] : 3; ?>"/></td>
                    <td><input maxlength="10" class="expiry" name="expiry-<?php echo $row->resource_id;?>" type="text" size="10" value="<?php echo isset($row->download['expiry']) ? date("m/d/Y",$row->download['expiry']) : date("m/d/Y",date("U")+(60*60*24*5)); ?>"/></td>
                </tr>
            <?php endforeach;?>
            
                <tr class="<?php echo $tr_class; ?> file-settings" >
                    <td>&nbsp;</td>
                    <td><?php echo t('change_all_settings');?></td>
                    <td><input id="download-limit-hd" type="text" maxlength="2" size="2" value="3"/></td>			
                    <td><input id="expiry-hd" class="expiry" type="text" maxlength="10" size="10" value="<?php echo date("m/d/Y",date("U")+(60*60*24*5));?>"/>&nbsp;<input type="button" id="update-all" value="<?php echo t('apply');?>"/></td>
                </tr>
            </table>
            
            <?php else:?>
                <div class="error"><?php echo t('no_microdata_files_found');?></div>
            <?php endif;?>
        <?php endforeach;?>

    <?php else:?>
    
        <!-- multi study grid -->
        <table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
        <tr class="header">
            <th><input type="checkbox" id="chk_toggle"/></th>
            <th><?php echo t('file');?></th>
            <th><?php echo t('download_limit');?></th>			
            <th><?php echo t('expiry');?></th>            
        </tr>

		<?php foreach($surveys as $survey):?>
            <tr>
            	<td colspan="4"><h3><?php echo $survey['nation'];?> - <?php echo $survey['title'];?> <?php echo $survey['year_start'];?></h3></td>
            </tr>

        	<?php if(array_key_exists($survey['id'],$files)):?>
            	<?php foreach($files[$survey['id']] as $file):?>
					<?php $file=(object)$file; ?>
                    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                    <tr class="<?php echo $tr_class; ?>">
                        <td><input class="chk" type="checkbox" value="<?php echo $file->resource_id;?>" name="fileid-<?php echo $file->resource_id;?>" <?php  echo isset($file->download['download_limit']) ? 'checked="checked"' : ''; ?>/></td>
                        <td><?php echo basename($file->filename); ?></td>
                        <td><input type="text" class="download-limit" name="download-limit-<?php echo $file->resource_id;?>" maxlength="2" size="2" value="<?php echo isset($file->download['download_limit']) ? $file->download['download_limit'] : 3; ?>"/></td>
                        <td><input maxlength="10" class="expiry" name="expiry-<?php echo $file->resource_id;?>" type="text" size="10" value="<?php echo isset($file->download['expiry']) ? date("m/d/Y",$file->download['expiry']) : date("m/d/Y",date("U")+(60*60*24*5)); ?>"/></td>
                    </tr>
                <?php endforeach;?>
             <?php else:?>
                    <tr class="<?php echo $tr_class; ?>">
                        <td colspan="4"> <div class="error-msg"><?php echo t('no_microdata_files_found');?></div></td>
                    </tr>
             <?php endif;?>	
		<?php endforeach;?>
         <tr class="<?php echo $tr_class; ?> file-settings" >
                    <td>&nbsp;</td>
                    <td><?php echo t('change_all_settings');?></td>
                    <td><input id="download-limit-hd" type="text" maxlength="2" size="2" value="3"/></td>			
                    <td><input id="expiry-hd" class="expiry" type="text" maxlength="10" size="10" value="<?php echo date("m/d/Y",date("U")+(60*60*24*5));?>"/>&nbsp;<input type="button" id="update-all" value="<?php echo t('apply');?>"/></td>
                </tr>
        </table>
	<?php endif;?>	
    	
<?php else: ?>
	<?php echo t('not_attached_any_licensed_files');?>
<?php endif; ?>

<script type="text/javascript"> 
	$(function() {
		$(".expiry").datepicker();
	});

	function process_request(requestid){
		data=$("#form_request_review").serialize();
		$("#status-text").html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><?php echo t('js_updating_please_wait');?>');
		$.ajax({
			timeout:1000*120,
			dataType: "html",
			data:data,
			type:'POST', 
			url: CI.base_url+'/admin/licensed_requests/update/'+requestid, //+selected+'/?ajax=true',
			success: function(data) {			
				$("#status-text").html(data);
			},
			error: function(XHR,err) {
					$("#status-text").html("Error occured " + XHR.status + " - " + err);
			}
		});	
	}

	function send_mail(requestid){
		$("#form_compose_email_status").html('');
		data=$("#form_compose_email").serialize();
		url=CI.base_url+'/admin/licensed_requests/send_mail/'+requestid;
		$.post(url,data, 
			function (data){
				$("#form_compose_email_status").html('<div>'+data+'</div>');
			});
	}
	
	function forward_mail(requestid){
		$("#form_fw_lic_request_status").html('');
		data=$("#form_fw_lic_request").serialize();
		url=CI.base_url+'/admin/licensed_requests/forward_request/'+requestid;
		$.post(url,data, 
			function (data){
				$("#form_fw_lic_request_status").html('<div>'+data+'</div>');
			});
	}
	
	$(function() {
		$("#update-all").click(function () {
			update_all_settings();return false;
		});

	});
	
	function update_all_settings()
	{
		if ($(".download-limit").val()!=''){
			$(".download-limit").val($("#download-limit-hd").val());
		}
		if ($(".expiry").val()!=''){
			$(".expiry").val($("#expiry-hd").val());
		}
		return false;
	}

//checkbox select/deselect
jQuery(document).ready(function(){
	$("#chk_toggle").click(
			function (e) 
			{
				$('.chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$(".chk").click(
			function (e) 
			{
			   if (this.checked==false){
				$("#chk_toggle").attr('checked', false);
			   }			   
			}
	);
});	
</script>
