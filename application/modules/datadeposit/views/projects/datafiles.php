<h2 style="margin-bottom:40px">Datafiles</h2>
<a style="font-size:11pt;margin-bottom:8px;float:right" href="<?php echo site_url(), '/projects/upload/', $id; ?>">Upload files</a>
<table class="grid-table" style="margin-top:5px;">
<tr valign="top" align="left" class="header">
	<th style="width:20px"><input type="checkbox" id="chk_toggle"></th>
    <th style="width:80px"><?php echo t('type');?></th>
    <th style="width:200px"><?php echo t('name');?></th>
    <th style="width:500px"><?php echo t('description');?></th>	
    <th style="width:50px"><?php echo t('size');?></th>
    <!--<th>Exists</th>-->
    <th style="width:100px"><?php echo t('actions');?></th>
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
    		<td><input type="checkbox" disabled="disabled"/></td>
            <td><?php echo (isset($file['dctype'])) ? $file['dctype'] : 'N/A';?></td>
            <td><?php echo $file['filename']; ?></td>
			<td><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
            <td><?php echo $records[$file['filename']]['size']; ?></td>
            <td><?php echo anchor('projects/managefiles/'.$file['id'],'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo anchor('projects/delete_resource/'.$file['id'], '<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ');?> 
                <?php echo anchor('projects/download/'.$file['id'],'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?>
            </td>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
            <div style="float:right;padding:5px;font-style:italic;"><?php echo t('total_files_count');?><?php echo count($files);?></div>