<div>
	<?php foreach($rows as $alias):?>
        <div class="alias" id="alias-<?php echo $alias['id'];?>">
        	<a href="<?php echo site_url('admin/survey_alias/delete/'.$alias['id']);?>" title="Remove" class="remove" itemid="<?php echo $alias['id'];?>">
			<?php echo form_prep($alias['alternate_id']);?>	
					<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
					</a> 
		</div>
    <?php endforeach;?>
</div>

