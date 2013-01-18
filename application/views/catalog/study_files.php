<?php
//survey id
$survey_id=$this->uri->segment(4);
?>


<style>
.folder{background:url(images/folder.png) no-repeat;padding-left:20px;display:block;margin-bottom:5px;}
.file{background:url(images/page_white.png) no-repeat;padding-left:20px;color:#333333;display:block;margin-bottom:5px;line-height:150%;}
.micro-file{background:url(images/database_table.png) no-repeat;}
.resource-file{background:url(images/page_green.png) no-repeat;}
.locked-file{background:url(images/lock.png) no-repeat;}
.actions{text-align:right;margin-top:15px;}
#file-uploads{text-align:left;background-color:#EFEFEF;padding:10px;display:none;margin-bottom:10px;margin-top:10px;}
.input-file{width:300px;}
.input-link{border:1px solid gainsboro;background:none;font-size:11px;margin-top:3px;cursor: pointer;}
.input-link:hover{background-color:#E4E4E4;}
form{margin:10px;padding:0px;}
.files td{padding:3px;}
.unknown td, .unknown a{color:gray;}
.micro-file-tr td,.micro-file-tr a{color:#6633CC}
.resource-file-tr td,.resource-file-tr a{color:#339933}
.inline label{display:inline;}
.actions a{color:navy;}
.actions a:hover{color:red;}
</style>

<form method="post" enctype="multipart/form-data" class="form manage-files" action="<?php echo site_url('admin/managefiles/'.$survey_id.'/batch_delete');?>">
<input type="hidden" name="ajax" value="1"/>
<div class="actions">
	<div style="float:left;color:gainsboro;">		
    	<input type="image"  src="images/bin_closed.png" class="" title="<?php echo t('delete_selection');?>" name="delete" value="<?php echo t('delete_selection');?>" onclick="return batch_delete();"/> |
        <a href="<?php echo site_url('/admin/resources/upload/'.$survey_id);?>" style="cursor:pointer;" title="<?php echo t('upload_files_hover');?>" ><img src="images/upload.png"/> <?php echo t('upload_files');?></a>
    </div>

	<div style="float:right"> 
    </div>
    <br style="clear:both;"/>
</div>

<table width="100%" class="grid-table" style="margin-top:5px;">
<tr valign="top" align="left" class="header">
	<th><input type="checkbox" id="chk_toggle"></th>
    <th><?php echo t('name');?></th>
    <th><?php echo t('size');?></th>
    <th><?php echo t('permissions');?></th>
    <th><?php echo t('modified');?></th>
    <!--<th>Exists</th>-->
    <th><?php echo t('actions');?></th>
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
    	<?php $isresource=is_array($file['resource']) ? 'resource-file' : '';?>
		<?php 
				$resource_type='';
				if(is_array($file['resource']))
				{
					$ismicro=$file['resource']['ismicro'];

					if ($ismicro==TRUE)	
					{
						$resource_type='micro-file';
					}
					else
					{
						$resource_type='resource-file';
					}
				}
		?>
        <tr valign="top" class="unknown <?php echo $resource_type;?>-tr">
	        <?php if ($file['name'] ==$ddi_file_name):?>
    		<td><input type="checkbox" disabled="disabled"/></td>
            <td><?php echo anchor('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),$file["name"],array('class'=>'file locked-file '.$resource_type ));?></td>
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
            <td><?php echo anchor('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo '<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ';?> 
                <?php echo anchor('admin/managefiles/'.$survey_id.'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?>
            </td>
            <?php else:?>
        	<td><input type="checkbox" name="filename[]" class="chk" value="<?php echo base64_encode(urlencode($file["relative"].'/'.$file["name"]));?>"/></td>            
            <td><?php echo anchor('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),$file["name"],array('class'=>'file '.$resource_type ));?></td>
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
            <td><?php echo anchor('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo anchor('admin/managefiles/'.$survey_id.'/delete/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ','onclick="return delete_confirm();"');?> 
                <?php echo anchor('admin/managefiles/'.$survey_id.'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?>
            </td>            
			<?php endif;?>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
<div style="padding-top:10px;color:#999999;float:left;">
        	<div style="display:inline;"><img src="images/page_white.png"/> <?php echo t('not_linked');?></div>
            <div style="display:inline;margin-left:10px;"><img src="images/database_table.png"/> <?php echo t('data_files');?></div>
            <div style="display:inline;margin-left:10px;"><img src="images/page_green.png"/> <?php echo t('other_resources');?></div>
</div>
            <div style="float:right;padding:5px;font-style:italic;"><?php echo t('total_files_count');?><?php echo count($files);?></div>
</form>

<script type='text/javascript' >
//checkbox select/deselect
jQuery(document).ready(function(){
	$(".manage-files #chk_toggle").click(
			function (e) 
			{
				$('.manage-files .chk').each(function(){ 
                    this.checked = (e.target).checked; 
                }); 
			}
	);
	$(".manage-files .chk").click(
			function (e) 
			{
			   if (this.checked==false){
				$(".manage-files #chk_toggle").attr('checked', false);
			   }			   
			}
	);			
});

function batch_delete(){
	if ($('.manage-files .chk:checked').length==0){
		alert("<?php echo t('js_no_item_selected');?>");
		return false;
	}
	if (!confirm("<?php echo t('js_confirm_delete');?>"))
	{
		return false;
	}
	
	$(".manage-files").submit();return false;
	
	/*
	$k=0;
	$('.manage-files .chk:checked').each(function(){ 
		//console.log(CI.base_url+'/admin/managefiles/<?php echo $survey_id;?>/delete/'+this.value+'?ajax=1');return;
	
		$k++;
			$.ajax({
				timeout:1000*120,
				type:'GET', 
				url: CI.base_url+'/admin/managefiles/<?php echo $survey_id;?>/delete/'+this.value+'?ajax=1',
				error: function(XHR, textStatus, thrownError) {
					alert("Error occured " + XHR.status);
					return false;
				}
			});
     });
	 
	 alert($k + " files were removed");
	 //window.location.reload();
	 return false;
	 */
}


function delete_confirm(){
	if (!confirm("<?php echo t('js_confirm_delete');?>")) {return false;}
}
</script>