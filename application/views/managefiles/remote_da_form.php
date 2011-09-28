<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<div class="field">
<label for="link_da"><?php echo t('remote_data_access_url');?></label>
<input name="link_da" type="text" id="link_da" class="input-flex" value="<?php echo get_form_value('link_da',isset($link_da) ? $link_da : ''); ?>"/>
</div>

<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" />
</div>
