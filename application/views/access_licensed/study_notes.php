<?php 
	$note_groups=array();
	foreach($study_notes as $note)
	{
		$note_groups[$note['type']][]=$note;
	}
?>
<table class="grid-table study-notes">
	<tr class="header">
    	<th><?php echo t('note_type');?></th>
        <th><?php echo t('comment_by');?></th>
        <th><?php echo t('dated');?></th>        
        <th><?php echo t('note');?></th>
    </tr>
<?php foreach($note_groups as $key=>$group):?>
	<?php foreach($group as $note):?>
    <tr style="vertical-align:top">
    	<td><?php echo $key;?></td>
        <td nowrap="nowrap"><?php  $user=$this->ion_auth->get_user($note['userid']); echo $user->username;?></td>
        <td nowrap="nowrap"><?php echo date("m-d-Y",$note['created']);?></td>
        <td><?php echo nl2br(form_prep($note['note']));?></td>
    </tr>
    <?php endforeach; ?>   
<?php endforeach; ?>
</table>