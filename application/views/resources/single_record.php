<?php 
/*
	displays single resource
*/
?>
<style>
	td{border:1px solid gainsboro;}
</style>
<h1 class="left-pad">External Resources</h1>
<table width="100%">
	<?php foreach($row as $key=>$value):?>
		<?php if ( in_array($key,$textarea_fields) && $value!=''): ?>
			<tr valign="top">
				<td><?php echo $key; ?></td>
				<td><div style="height:100px;overflow:scroll;border:1px solid gainsboro;"><?php echo nl2br($value); ?></div></td>
			</tr>
		<?php else: ?>	
			<tr valign="top">
				<td><?php echo $key; ?></td>
				<td><?php echo nl2br($value); ?></td>
			</tr>
		<?php endif; ?>
	<?php endforeach;?>
</table>