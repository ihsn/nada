<h1><?php echo t('change_password');?></h1>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<form method="post" autocomplete="off" class="form">
<input type="hidden" name="form_token" value="<?php echo form_prep($this->form_token); ?>"/>
      <p><?php echo t('old_password');?>:<br />
      <?php echo form_input($old_password);?>
      </p>
      
      <p><?php echo t('new_password');?>:<br />
      <?php echo form_input($new_password);?>
      </p>
      
      <p><?php echo t('confirm_new_password');?>:<br />
      <?php echo form_input($new_password_confirm);?>
      </p>
      
      <?php echo form_input($user_id);?>
      <p><?php echo form_submit('submit', t('change'));?></p>
      
<?php echo form_close();?>