<div class="content-container">
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('confirm_delete'); ?></h1>

<?php echo form_open(current_url(), array('class'=>'form') ); ?>
<div class="field">
	<div><?php echo t('confirm_delete_records');?></div>
	<input type="submit" name="submit" id="submit" value="<?php echo t('yes'); ?>" />
	<input type="submit" name="cancel" id="cancel" value="<?php echo t('no'); ?>" />
    <input type="hidden" name="destination"  value="<?php echo $this->input->get_post('destination'); ?>"/>
</div>
<?php form_close(); ?>
</div>