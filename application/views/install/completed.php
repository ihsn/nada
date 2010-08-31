<div class='content-container' style="margin:10px;margin-top:20px;">
<h1><?php echo t('install_completed');?></h1>
<p style="margin-top:10px;"><?php echo t('install_completed_tasks_summary');?></p>

<table cellpadding="3" cellspacing="0" class="grid-table">
<tr class="header">
<th><?php echo t('task');?></th>
<th><?php echo t('status');?></th>
</tr>
<tr>
	<td><?php echo t('database');?></td>
	<td><img src="images/tick.png" /></td>
</tr>
<tr>
	<td><?php echo t('tables');?></td>
	<td><img src="images/tick.png" /></td>
</tr>
<tr>
	<td><?php echo t('admin_account');?></td>
	<td><img src="images/tick.png" /></td>
</tr>
</table>
</div>

<div style="padding-left:10px;">
 	<b><a class="button" href="<?php echo site_url();?>"><?php echo t('click_here_to_launch_application');?></a></b>
</div>     
     
