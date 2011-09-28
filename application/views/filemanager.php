<style>
.folder{background:url(images/folder.png) no-repeat;padding-left:20px;display:block;margin-bottom:5px;}
.file{background:url(images/page_white.png) no-repeat;padding-left:20px;color:#009933;display:block;margin-bottom:5px;}
table .header th{background-color:#E9E9E9}
.actions{text-align:right;margin-top:15px;}
#file-uploads{text-align:left;background-color:#EFEFEF;padding:10px;display:none;margin-bottom:10px;margin-top:10px;}
.input-file{width:300px;}
.input-link{border:1px solid gainsboro;background:none;font-size:11px;margin-top:3px;cursor: pointer;}
.input-link:hover{background-color:#E4E4E4;}
form{margin:10px;padding:0px;}
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


<form method="post" enctype="multipart/form-data">
<h3><?php echo anchor('filemanager/'.$virtual_root,$virtual_root); ?> /<?php echo $path_in_url; ?></h3>
<div class="actions">
	<div style="float:left;">
    	<input type="submit" class="input-link" name="delete" value="Delete" onclick="return batch_delete();"/>
        <input type="button" class="input-link" value="Upload" onClick="$('#file-uploads').toggle();return false;"/>
    </div>
	<div style="float:right"> 
    	<select name="type">
            <option value="dir">Directory</option>
            <option value="file" disabled="disabled">File</option>
        </select>
        <input type="text" size="20" name="name"/>
        <input type="submit" name="create" value="create"/>
    </div>
    <br style="clear:both;"/>
</div>


<div id="file-uploads">
	<h3>Select files for uploading</h3>
	<?php for($i=0;$i<5;$i++):?>    	
        <input class="input-file" type="file" name="file[]"/><br/>
    <?php endfor;?>    
    <input type="submit" name="upload" value="Upload"/>
    <input type="button" name="cancel" value="Cancel" onClick="$('#file-uploads').toggle();return false;"/>
</div>


<table width="100%">
<tr valign="top" align="left" class="header">
	<th><input type="checkbox" id="chk_toggle"></th>
	<th>Name</th>
    <th>Size</th>
    <th>Permissions</th>
    <th>Date</th>
</tr>
<?php $prefix = $controller.'/'.$virtual_root.'/'.$path_in_url; ?>
<?php if (!empty($dirs)): ?>
	<?php foreach( $dirs as $dir ): ?>
        <tr>
        	<td><input type="checkbox" class="chk" name="filename[]" value="<?php echo $dir['name'];?>"/></td>        
            <td><?php echo anchor($prefix.$dir['name'], $dir['name'],array('class'=>'folder'));?></td>
            <td>-</td>
            <td>-</td>
            <td><?php echo isset($dir['date']) ? date("m/d/Y: H:i:s",$dir['date']) : '-';?></td>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
        	<td><input type="checkbox" name="filename[]" class="chk" value="<?php echo $file['name'];?>"/></td>
            <td><?php echo anchor($prefix.$file['name'], $file['name'],array('class'=>'file'));?></td>
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
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
		alert("You have not selected any items");
		return false;
	}
	if (!confirm("Are you sure you want to delete the selected item(s)?"))
	{
		return false;
	}
}
</script>