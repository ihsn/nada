<div class="survey-notes">
<?php if(count($study_notes)>0):?>
<?php foreach($study_notes as $note):?>
<div class="node survey-note collapsed type-<?php echo $note['type'];?>">
	<?php if($user_obj->id==$note['userid']):?>
    <div class="note-links">
		<a class="edit" href="<?php echo site_url('catalog/'.$study_id.'/review/get-edit-form/'.$note['id']); ?>" title="Edit note"><?php echo t('edit');?></a> |
		<a class="remove" href="<?php echo site_url('catalog/'.$study_id.'/review/delete-note/'.$note['id']); ?>" title="Remove note"><?php echo t('delete');?></a>
	</div>
	<?php endif;?>
    
    <div class="author-info">
	    <span class="author"><?php  $user=$this->ion_auth->get_user($note['userid']); echo $user->username;?></span> on
    	<span class="date"><?php echo date("m-d-Y",$note['created']);?></span>    	
    </div>
	<div class="text"><?php echo nl2br(form_prep($note['note']));?></div>	
</div>            
<?php endforeach; ?>
<?php else:?>
	<?php echo t('no_reviewer_notes_found');?>
<?php endif;?>
</div>