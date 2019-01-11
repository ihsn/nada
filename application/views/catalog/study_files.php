<?php
//survey id
$survey_id=$this->uri->segment(4);
?>


<style>
.folder{background:url(images/folder.png) no-repeat;padding-left:20px;display:block;margin-bottom:5px;}
.file:before {
    content: "\f127";
    font-family: FontAwesome;
	padding-right:5px;
	font-size:14px;
}
.micro-file:before {
    content: "\f1c0";
    font-family: FontAwesome;
}

.resource-file:before {
    content: "\f016";
}
.locked-file:before {
    content: "\f023";
}
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

.table-survey-files .glyphicon {
	font-size:14px;margin:3px;
}
</style>

<form method="post" enctype="multipart/form-data" class="form manage-files" action="<?php echo site_url('admin/managefiles/'.$survey_id.'/batch_delete');?>">
<input type="hidden" name="ajax" value="1"/>
<div class="actions">
	<div style="float:left">

		<a href="#"
						class="btn btn-default btn-sm"
						aria-label="Left Align"
						title="<?php echo t('delete_selection');?>"
						name="delete"
						value="<?php echo t('delete_selection');?>"
						onclick="return batch_delete();">
		  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
		</a>

    <a href="<?php echo site_url('/admin/resources/upload/'.$survey_id);?>"
			class="btn btn-default"
			title="<?php echo t('upload_files_hover');?>" >
			<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo t('upload_files_hover');?>
		</a>
    </div>

	<div style="float:right">
    </div>
    <br style="clear:both;"/>
</div>

<table width="100%" class="table table-striped table-survey-files" style="margin-top:5px;">
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
            <td>
							<a href="<?php echo site_url('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])));?>">
								<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
							</a>

							<a href="<?php echo site_url('admin/managefiles/'.$survey_id.'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])));?>">
								<span class="glyphicon glyphicon-download" aria-hidden="true"></span>
							</a>

							<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>

            </td>
            <?php else:?>
        	<td><input type="checkbox" name="filename[]" class="chk" value="<?php echo base64_encode(urlencode($file["relative"].'/'.$file["name"]));?>"/></td>
            <td><?php echo anchor('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])),$file["name"],array('class'=>'file '.$resource_type ));?></td>
            <td><?php echo $file['size'];?></td>
            <td><?php echo $file['fileperms'];?></td>
            <td><?php echo date("m/d/Y: H:i:s",$file['date']);?></td>
            <td>

							<a href="<?php echo site_url('admin/managefiles/'.$survey_id.'/edit/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])));?>">
								<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
							</a>

							<a href="<?php echo site_url('admin/managefiles/'.$survey_id.'/download/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])));?>">
								<span class="glyphicon glyphicon-download" aria-hidden="true"></span>
							</a>

							<a href="<?php echo site_url('admin/managefiles/'.$survey_id.'/delete/'.base64_encode(urlencode($file["relative"].'/'.$file["name"])));?>">
								<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
							</a>
						</td>
			<?php endif;?>
        </tr>
    <?php endforeach;?>
<?php endif;?>
</table>
<div style="padding-top:10px;color:#999999;float:left;">
        	<div style="display:inline;"><i class="fa fa-chain-broken" aria-hidden="true"></i> <?php echo t('not_linked');?></div>
            <div style="display:inline;margin-left:10px;"><i class="fa fa-database" aria-hidden="true"></i> <?php echo t('data_files');?></div>
            <div style="display:inline;margin-left:10px;"><i class="fa fa-file-o" aria-hidden="true"></i> <?php echo t('other_resources');?></div>
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
	
}


function delete_confirm(){
	if (!confirm("<?php echo t('js_confirm_delete');?>")) {return false;}
}
</script>
