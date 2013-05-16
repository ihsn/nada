<?php 
	$note_groups=array();
	foreach($study_notes as $note)
	{
		$note_groups[$note['type']][]=$note;
	}
?>

<?php foreach($note_groups as $key=>$group):?>
	<div class="note-type"><?php echo t('type_'.$key);?></div>
	<?php foreach($group as $note):?>
    <div class="node note collapsed type-<?php echo $note['type'];?>">
        <div class="note-links">
            <a class="edit" href="<?php echo site_url('admin/catalog_notes/edit/'.$note['id']); ?>" title="Edit note"><span class=" icon-edit"></span></a>
            <a class="remove" href="<?php echo site_url('admin/catalog_notes/delete/'.$note['id']); ?>" title="Remove note"><span class=" icon-trash"></span></a>
        </div>
        <div class="author"><?php  $user=$this->ion_auth->get_user($note['userid']); echo $user->username;?> on <span class="date"><?php echo date("m-d-Y",$note['created']);?></span></div>		
        <div class="text"><?php echo nl2br(form_prep($note['note']));?></div>	
    </div>            
    <?php endforeach; ?>
<?php endforeach; ?>