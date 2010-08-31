<h1 class="page-title">Forgot Password</h1>
<?php if ($message):?>
	<div class="error"><?php echo $message;?></div>
<?php endif;?>

<p>Please enter your email address so we can send you an email to reset your password.</p>

<?php echo form_open("auth/forgot_password");?>

      <div class="field">
	      <label for="email">Email</label>
	      <?php echo form_input($email);?>
          <?php echo form_submit('submit', 'Submit');?>
      </div>

<?php echo form_close();?>