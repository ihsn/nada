<form method="post" class="form">
<h2>Confirm delete</h2>
<div class="field">
	<div><?php echo t('confirm_delete_records');?></div>
	<input type="submit" name="submit" id="submit" value="<?php echo t('yes'); ?>" />
	<input type="submit" name="cancel" id="cancel" value="<?php echo t('no'); ?>" />
    <input type="hidden" name="destination"  value="<?php echo form_prep($this->input->get_post('destination')); ?>"/>
</div>
</form>

