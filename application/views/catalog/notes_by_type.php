<div>
<?php foreach($notes as $note):?>
<div class="node">
	<div class="links"><a class="remove" href="<?php echo site_url('admin/catalog_notes/delete/'.$note['id']); ?>" title="Remove note"><span class=" icon-remove"></span></a></div>
	<div class="text"><?php echo form_prep($note['note']);?></div>
	<div class="author"><?php  $user=$this->ion_auth->get_user($note['userid']); echo $user->username;?> on <span class="date"><?php echo date("m-d-Y",$note['created']);?></span></div>	
</div>            
<?php endforeach; ?>
</div>