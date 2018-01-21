<?php
	$note_groups=array();
	foreach($study_notes as $note)
	{
		$note_groups[$note['type']][]=$note;
	}
?>


<?php foreach($note_groups as $key=>$group):?>

	<div class="panel panel-default">
	  <div class="panel-heading"><h3 class="panel-title"><?php echo t('type_'.$key);?></h3></div>
	  <div class="panel-body">
	<?php foreach($group as $note):?>
    <div class="row note type-<?php echo $note['type'];?>">
				<div class="col-md-1">
					<div style="background:gainsboro;padding:10px;text-align:center" class="img-circle">
					<span class="glyphicon glyphicon-user" style="font-size:20px;color:whitesmoke;" aria-hidden="true"></span>
					</div>
				</div>
				<div class="col-md-11">
					<div class="note-links pull-right">
							<!--<a class="edit" href="<?php echo site_url('admin/catalog_notes/edit/'.$note['id']); ?>" title="Edit note">
								<span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a>-->
							<a class="remove" href="<?php echo site_url('admin/catalog_notes/delete/'.$note['id']); ?>" title="Remove note">
								<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
							</a>
					</div>
        <div class="author">
					<?php
					$user=$this->ion_auth->get_user($note['userid']);
					echo ucwords($user->username);?>
					~  <span class="date"><?php echo date("M d, Y",$note['created']);?></span>
				</div>
        <div class="text"><?php echo nl2br(form_prep($note['note']));?></div>
			</div>
    </div>
    <?php endforeach; ?>
	</div>
	</div>
<?php endforeach; ?>
