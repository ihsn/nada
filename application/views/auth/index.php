<div class='mainInfo'>

	<h1>Users</h1>
	<p>Below is a list of the users.</p>
	
	<div id="infoMessage"><?php echo $message;?></div>
	
	<table cellpadding=0 cellspacing=10>
		<tr>
			<th>First Name</th>
			<th>Last Name</th>
			<th>Email</th>
			<th>Group</th>
			<th>Status</th>
		</tr>
		<?php foreach ($users as $user):?>
			<tr>
				<td><?php echo $user['first_name']?></td>
				<td><?php echo $user['last_name']?></td>
				<td><?php echo $user['email'];?></td>
				<td><?php echo $user['group_description'];?></td>
				<td>
					<?php if ($user['active']): ?>
						<?php echo form_open('auth/deactivate/'.$user['id'], array('class' => 'd-inline mb-0')); ?>
						<button type="submit" class="btn btn-link p-0">Active</button>
						<?php echo form_close(); ?>
					<?php else: ?>
						<?php echo form_open('auth/activate/'.$user['id'], array('class' => 'd-inline mb-0')); ?>
						<button type="submit" class="btn btn-link p-0">Inactive</button>
						<?php echo form_close(); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach;?>
	</table>
	
	<p><a href="<?php echo site_url('auth/create_user');?>">Create a new user</a></p>
	
</div>
