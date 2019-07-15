<style>

.storage-location-container{
	background:beige;
	padding:15px;
	margin-top:15px;
	margin-bottom:20px;
}

</style>

<h2 style="margin-bottom:15px">Files in project folder</h2>

<div class="storage-location-container">
	<label>Project files located at:</label>
	<span class="storage-location"><?php echo $project_storage_location ? $project_storage_location : 'NOT-SET';?></span>
</div>
<?php if ($files):?>

<table class="grid-table table table-striped">
	<tbody>
    <tr valign="top" align="left" class="header">
        <th>Name</th>
        <th>Type</th>
        <th>Download</th>
    </tr>
	<?php foreach($files as $file): $file=(object)$file;?>
    <tr valign="top">
        <td><?php echo ($file->title) ? $file->title.'('.$file->filename.')' : $file->filename;?></td>
        <td><?php echo ($file->dctype) ? $file->dctype: 'N/A';?></td>
        <td><a href="<?php echo site_url('admin/datadeposit/download/'.$file->id.'/'.$file->project_id);?>">Download</a></td> 
    </tr>
	<?php endforeach;?>
	</tbody>
    </table>
    
<?php else: ?>
<p>There are no files attached to this project</p>
<?php endif; ?>