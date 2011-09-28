<h1 class="page-title"><?php echo t('forgot_password');?></h1>
<?php if ($message):?>
	<div class="error"><?php echo $message;?></div>
<?php endif;?>

<p><?php echo t('enter_email_to_reset_password');?></p>

<form  method="post" class="form" autocomplete="off">        

      <div class="field">
	      <label for="email"><?php echo t('email');?></label>
	      <?php echo form_input($email);?>
          <?php echo form_submit('submit', t('submit'));?>
      </div>

<?php echo form_close();?>