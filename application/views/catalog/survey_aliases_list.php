<div>
	<?php foreach($rows as $alias):?>
        <div class="alias" id="alias-<?php echo $alias['id'];?>">
        	<a href="<?php echo site_url('admin/survey_alias/delete/'.$alias['id']);?>" title="Remove" class="remove" itemid="<?php echo $alias['id'];?>"> <i class="icon-remove-sign"></i> </a> <?php echo form_prep($alias['alternate_id']);?>
		</div>
    <?php endforeach;?>		
</div>

