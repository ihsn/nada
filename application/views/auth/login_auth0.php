<script type="text/javascript">
	if (top.frames.length != 0) {
		top.location = self.document.location;
	}
</script>
<style>
.login-form {
	width: 100%;
	max-width: 500px;
	padding: 30px;
	margin: auto;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	background-color: #fff;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.wb-template-blank .wb-page-body.container-fluid {
	margin-top: 150px;
}
</style>

<div class="login-form mt-5">

<?php if (!empty($error)): ?>
	<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
	<div class="alert alert-primary"><?php echo $message; ?></div>
<?php endif; ?>

<h1><?php echo t('log_in'); ?></h1>

<p class="text-secondary mb-4"><?php echo t('auth0_login_message'); ?></p>

<form method="post" class="form" autocomplete="off" action="<?php echo site_url('auth/login'); ?>">
	<input type="hidden" name="auth0_login" value="1">
	<div class="login-footer">
		<input type="submit" name="submit" value="<?php echo t('auth0_login_button'); ?>" class="btn btn-primary btn-block"/>
	</div>
</form>

<?php if (!empty($enable_alternate_login) && !empty($alternate_login_url)): ?>
<p class="text-center mt-4 mb-0">
	<a href="<?php echo html_escape($alternate_login_url); ?>"><?php echo t('auth0_alternate_login'); ?></a>
</p>
<?php endif; ?>

</div>
