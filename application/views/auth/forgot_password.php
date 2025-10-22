<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
.login-form{
    width: 100%;
    max-width: 500px;
    padding: 15px;
    margin: auto;
}
.privacy-info{
    font-size:smaller;
}

.image_captcha {
    background:gainsboro;
    padding:10px;
    margin-bottom:20px;
}
.image_captcha input{
    display:block;
}
.wb-template-blank .wb-page-body.container-fluid{
    margin-top:150px;
}

.captcha_container{
    max-width: 300px;
}

.captcha_container input{
    width:100%
}
</style>
<div class="login-form border shadow rounded">

	<h1 class="text-center pb-3 pt-4"><?php echo t('forgot_password'); ?></h1>
	<p class="text-center mb-4"><?php echo t('enter_email_to_reset_password'); ?></p>

<?php $reason = $this->session->flashdata('reason'); ?>
<?php if ($reason !== "") : ?>
	<?php echo ($reason != "") ? '<div class="reason p-3 mb-2">' . $reason . '</div>' : ''; ?>
<?php endif; ?>

<?php if (isset($error) && $error != '') : ?>
	<?php $error = '<div class="alert alert-danger p-3 mb-2">' . $error . '</div>' ?>
<?php else : ?>
	<?php $error = $this->session->flashdata('error'); ?>
	<?php $error = ($error != "") ? '<div class="alert-danger p-3 mb-2">' . $error . '</div>' : ''; ?>
<?php endif; ?>

<?php if ($error != '') : ?>
	<div><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($message) && $message != '') : ?>
	<div class="alert alert-primary"><?php echo $message; ?></div>
<?php endif; ?>

<div style="padding:5px;">
	<form method="post" class="form" autocomplete="off">
		<?php if (!$captcha_question) : ?>
			<div class="form-group">
				<?php echo form_input($email, '', 'class="form-control" placeholder="'.t('enter_your_email').'"'); ?>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary btn-block"><?php echo t('submit'); ?></button>
			</div>
		<?php endif; ?>
		
		<?php if ($captcha_question) : ?>
			<div class="form-group">
				<?php echo form_input($email, '', 'class="form-control" placeholder="'.t('enter_your_email').'"'); ?>
			</div>
			<div class="form-group">
				<?php echo $captcha_question; ?>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary btn-block"><?php echo t('submit'); ?></button>
			</div>
		<?php endif; ?>
	</form>
	
	<div class="login-footer mt-3">
		<div class="ot clearfix">
			<span class="lnk float-left"><?php echo anchor('auth/login', t('back_to_login'), 'class="jx btn btn-link btn-sm"'); ?></span>
		</div>
	</div>
</div>


</div>