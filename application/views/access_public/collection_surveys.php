<h1 class="page-title"><?php echo t('public_use_data_access_by_collection');?></h1>
<p><?php echo t('public_use_data_access_by_collection_message');?></p>
<table class="grid-table">
<?php $k=1;foreach($surveys as $survey):?>
<tr class="row">
	<td><?php echo $k++;?></td>
	<td><a target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['title'];?></a></td>
</tr>
<?php endforeach;?>
</table>