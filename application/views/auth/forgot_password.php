<style>
	.image_captcha {
    padding-top:15px;
	padding-bottom:15px;
    margin-bottom:20px;
	max-width: 400px;
	}
.image_captcha input{
    display:block;
}

.image_captch label{
	padding-top:20px;
}

.captcha_container{
	max-width: 300px;
}

.captcha_container input{
	width:100%
}

</style>

<div class='container'>

	<h1 class="page-title"><?php echo t('forgot_password'); ?></h1>
	<?php $reason = $this->session->flashdata('reason'); ?>
	<?php if ($reason !== "") : ?>
		<?php echo ($reason != "") ? '<div class="reason">' . $reason . '</div>' : ''; ?>
	<?php endif; ?>

	<?php if (isset($error) && $error != '') : ?>
		<?php $error = '<div class="alert-danger">' . $error . '</div>' ?>
	<?php else : ?>
		<?php $error = $this->session->flashdata('error'); ?>
		<?php $error = ($error != "") ? '<div class="alert-danger">' . $error . '</div>' : ''; ?>
	<?php endif; ?>

	<?php if ($error != '') : ?>
		<div class="error"><?php echo $error; ?></div>
	<?php endif; ?>

	<?php if ($message != '') : ?>
		<div class="alert alert-primary"><?php echo $message; ?></div>
	<?php endif; ?>

	<p><?php echo t('enter_email_to_reset_password'); ?></p>

	<form method="post" class="form" autocomplete="off">

		<div class="field">
			<?php if (!$captcha_question) : ?>
				<label for="email"><?php echo t('email'); ?></label>
				<?php echo form_input($email); ?>							
				<?php echo form_submit('submit', t('submit')); ?>
			<?php endif; ?>
		</div>
		<?php if ($captcha_question) : ?>
			<div class="captcha_container">
				<label for="email"><?php echo t('email'); ?></label><br/>
				<?php echo form_input($email); ?>

				<?php echo $captcha_question; ?>				
				<?php echo form_submit('submit', t('submit')); ?>
			</div>
		<?php endif; ?>

		<?php echo form_close(); ?>

</div> <!-- /.container -->