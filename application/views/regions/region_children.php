<?php foreach($children as $row): ?>
	<?php $row=(object)$row;?>        
	<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
	<tr class="<?php echo $tr_class; ?>">
		<td><?php echo $row->id;?></td>
		<td><?php echo $row->pid;?></td>
		<td><a class="child" href="<?php echo site_url();?>/admin/regions/edit/<?php echo $row->id;?>"><?php echo $row->title; ?></a></td>            
		<td>
			<a href="<?php echo site_url();?>/admin/regions/edit/<?php echo $row->id;?>"><?php echo t('edit');?></a> | 
			<a href="<?php echo site_url();?>/admin/regions/delete/<?php echo $row->id;?>"><?php echo t('delete');?></a>
		</td>
	</tr>
<?php endforeach;?>