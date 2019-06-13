<h1>Log History</h1>
<table class="grid-table table table-striped" width="100%" cellspacing="0" cellpadding="0">
	<thead class="header">
		<th>Identity</th>
        <th>Date</th>
        <th>Status</th>
        <th>Description</th>
     </thead>
     <tbody>
     	<?php foreach ($history as $log): 			
		if (is_object(json_decode($log->comments))       ||
		 strpos($log->comments, '<i>Comment:</i>') === 0 ||
		 // old format
		 strpos($log->comments, 'Comment:')        === 0)
			continue;
 		?>
        <tr>
        	<td><?php echo $log->user_identity; ?></td>
     		<td nowrap="nowrap"><?php echo date('Y-m-d H:i:s', $log->created_on); ?></td>
            <td><?php echo $log->project_status; ?></td>
            <td><?php echo $log->comments;?></td>
        </tr>
        <?php endforeach; ?>
	</tbody>
</table>
	