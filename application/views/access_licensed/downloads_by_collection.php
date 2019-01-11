<?php if ($status=='APPROVED'):?>
<h2><?php echo t('Access granted to the following studies');?></h2>
<p><?php echo t('To download data and other documentation, please click on each study and download the files listed on the study pages.');?></p>
<table class="grid-table">
<?php $k=1;foreach($surveys as $survey):?>
<tr class="row">
	<td><?php echo $k++;?></td>
	<td><a target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['title'];?></a></td>
</tr>
<?php endforeach;?>
</table>
<?php endif;?>
