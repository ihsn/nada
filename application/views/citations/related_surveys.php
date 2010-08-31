<?php if (isset($related_surveys)): ?>
<?php if (count($related_surveys)>0): ?>
<table class="grid-table" cellpadding="0" cellspacing="0">
	<tr class="header">        
		<th>Title</th>
		<th>Country</th>
		<th>Date</th>
		<th>Action</th>
	</tr>
<?php foreach ($related_surveys as $survey):?>
	<tr align="left">
		<td><?php echo $survey['titl'];?></td>
		<td><?php echo $survey['nation'];?></td>
		<td nowrap="nowrap"><?php echo $survey['proddate'];?></td>
		<td nowrap="nowrap"><a class="remove-citation" href="<?php echo site_url().'/admin/citations/remove_related_survey/'.$survey['citationid'].'/'.$survey['id'];?>"><img src="images/blue-remove.png" border="0" align="absbottom"/>Remove</a></td>
	</tr>
<?php endforeach; ?>
</table>    
<?php else:?>
No related surveys found, click on the add related survey link to add related surveys.
<?php endif;?>
<?php endif;?>