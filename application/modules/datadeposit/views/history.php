<h1 style="margin: 5px 0 15px 5px">Log History</h1>
<table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
	<thead class="header">
		<th>Identity</th>
        <th>Date</th>
        <th>Status</th>
        <th>Description</th>
     </thead>
     <tbody>
     	<?php foreach ($history as $log): ?>
        <tr>
        	<td><?php echo $log->user_identity; ?></td>
     		<td><?php echo $log->created_on; ?></td>
            <td><?php echo $log->project_status; ?></td>
            <td><?php echo $log->comments; ?></td>
        </tr>
        <?php endforeach; ?>
	</tbody>
</table>
	