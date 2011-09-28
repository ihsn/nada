<?php
	$folder_options['']='--ROOT--';
	if (!empty($dirs))
	{	
		foreach( $dirs as $dir )
		{
			$folder_options[$dir]=$dir;
		}    
	}
?>


<style>
.folder{background:url(images/folder.png) no-repeat;padding-left:20px;display:block;margin-bottom:5px;}
.file{background:url(images/page_white.png) no-repeat;padding-left:20px;color:#333333;display:block;margin-bottom:5px;}
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
</style>
<?php if (isset($this->errors)):?>
<div class="error">
	<?php 
    foreach ($this->errors as $e)
    {
        echo '<p>'.$e.'</p>';
    }
	?>
</div>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php include 'tabs.php';?>

<form method="post" enctype="multipart/form-data" class="form">
<div class="actions">
	<div style="float:left;color:gainsboro;">		
    	<input type="image"  src="images/bin_closed.png" class="" title="<?php echo t('delete_selection');?>" name="delete" value="<?php echo t('delete_selection');?>" onclick="return batch_delete();"/> |
        <a class="" title="<?php echo t('home_folder_hover');?>" href="<?php echo current_url(); ?>"><img src="images/house.png"/> <?php echo t('home_folder');?></a>  |
        <a class="" title="<?php echo t('folder_view_hover');?>" href="<?php echo current_url(); ?>/?view=folder"><img src="images/folder_table.png"/> <?php echo t('switch_view');?></a> |
        <a style="cursor:pointer;" title="<?php echo t('upload_files_hover');?>" onClick="$('#file-uploads').toggle();return false;"><img src="images/upload.png"/> <?php echo t('upload_files');?></a>
    </div>

	<div style="float:right"> 
    	<select name="type">
            <option value="dir"><?php echo t('folder');?></option>
        </select>
        <input type="text" size="20" name="name"/>
        <input type="submit" name="create" value="<?php echo t('create');?>"/>
    </div>
    <br style="clear:both;"/>
</div>


<div id="file-uploads">

	<table>
    <tr>
    <td>
    <div class="field-inline">
        <label for="upload_folder"><?php echo t('select_upload_folder');?></label>
        <?php echo form_dropdown('upload_folder', $folder_options,'','id="upload_folder"'); ?>
    </div>
	</td>
    <td style="width:30px;">&nbsp;</td>
    <td>
    <div class="field-inline">
        <input type="checkbox" name="overwrite" id="overwrite" value="1"/>
        <label for="overwrite"><?php echo t('overwrite_if_exists');?></label>        
    </div>
    </td>
    </tr>
    </table>
    
	<?php $this->load->view("managefiles/plupload");?>

	<div id="uploader">
		<p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p>

    <div class="field">
        <label for="upload_folder"><?php echo t('select_upload_files');?></label>
		<?php for($i=0;$i<5;$i++):?>    	
            <input class="input-flex" type="file" name="file[]" /><br/>
        <?php endfor;?>    
	</div>

    <div style="margin-top:5px;">
    <input type="submit" name="upload" value="<?php echo t('upload');?>"/>
    <input type="button" name="cancel" value="<?php echo t('cancel');?>" onClick="$('#file-uploads').toggle();return false;"/>
    </div>

	</div>
</div>


<table width="100%" class="grid-table" style="margin-top:5px;">
<tr valign="top" align="left" class="header">
	<th><input type="checkbox" id="chk_toggle"></th>
    <th><?php echo t('name');?></th>
    <th><?php echo t('folder');?></th>	
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
	        <?php if ($file['name'] ==$this->ddi_file_name):?>
    		<td><input type="checkbox" disabled="disabled"/></td>
            <td><?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),$file["name"],array('class'=>'file locked-file '.$resource_type ));?></td>
			<td><?php echo ($file["relative"])=='' ? '-' : $file["relative"];?></td>            
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
            <td><?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo '<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ';?> 
                <?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?>
            </td>
            <?php else:?>
        	<td><input type="checkbox" name="filename[]" class="chk" value="<?php echo $file["relative"].'/'.$file["name"];?>"/></td>            
            <td><?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),$file["name"],array('class'=>'file '.$resource_type ));?></td>
			<td><?php echo ($file["relative"])=='' ? '-' : $file["relative"];?></td>            
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
            <td><?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/delete/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ','onclick="return delete_confirm();"');?> 
                <?php echo anchor('admin/managefiles/'.$this->uri->segment(3).'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?>
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
function batch_delete(){
	if ($('.chk:checked').length==0){
		alert("<?php echo t('js_no_item_selected');?>");
		return false;
	}
	if (!confirm("<?php echo t('js_confirm_delete');?>"))
	{
		return false;
	}
}
function delete_confirm(){
	if (!confirm("<?php echo t('js_confirm_delete');?>")) {return false;}
}
</script>