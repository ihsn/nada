<h2 style="margin-bottom:10px">Datafiles</h2>
<div class="instruction-box"><?php echo t('instructions_datafiles_usage'); ?></div>
<a style="font-size:11pt;margin-bottom:8px;float:right" href="<?php echo site_url(), '/datadeposit/upload/', $id; ?>">Upload files</a>
<style type="text/css">
	.grid-table td {
		margin: 0;
		padding: 0;
		border-collapse:collapse;
	}
</style>
<?php if (!empty($files)): ?>
<table class="grid-table" cellspacing="0" cellpadding="0" style="margin-top:5px;">
<tr valign="top" align="left" style="height:10px" class="header">
	<th style="width:20px"></th>
    <th style="width:200px"><?php echo t('name');?></th>
    <th style="width:500px"><?php echo t('description');?></th>	
    <th style="width:80px"><?php echo t('type');?></th>
    <th style="width:50px"><?php echo t('size');?></th>
    <!--<th>Exists</th>-->
    <th style="width:100px"><?php echo t('actions');?></th>
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
    		<td></td>
            <td><?php echo $file['filename']; ?></td>
			<td><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
            <td><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
            <td><?php echo $records[$file['filename']]['size']; ?></td>
            <td><?php echo anchor('datadeposit/managefiles/'.$file['id'],'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo anchor('datadeposit/delete_resource/'.$file['id'], '<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ');?> 
              <!--  <?php echo anchor('datadeposit/download/'.$file['id'],'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?> -->
            </td>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
            <div style="font-size:10pt;float:right;padding:5px;"><?php echo t('total_files_count');?><?php echo count($files);?></div>
<?php endif; ?>
