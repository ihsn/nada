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

<p class="text-secondary mb-4"><?php echo t('auth0_alternate_login_message'); ?></p>

<form method="post" class="form" autocomplete="off" action="<?php echo site_url('auth/alternate'); ?>">

<?php if (!empty($csrf)): ?>
<input type="hidden" name="<?php echo $csrf['keys']['name']; ?>" value="<?php echo $csrf['name']; ?>" />
<input type="hidden" name="<?php echo $csrf['keys']['value']; ?>" value="<?php echo $csrf['value']; ?>" />
<?php endif; ?>

<div class="form-group mt-3">
	<input class="form-control" name="email" type="text" id="email" value="<?php echo html_escape($email_value); ?>" placeholder="<?php echo t('email'); ?>" />
</div>

<div class="form-group mt-3">
	<input class="form-control" name="password" type="password" id="password" value="" placeholder="<?php echo t('password'); ?>" />
</div>

<div class="login-footer">
	<input type="submit" name="submit" value="<?php echo t('login'); ?>" class="btn btn-primary btn-block"/>
</div>

<div class="ot clearfix mb-3 mt-3">
	<?php if ($this->config->item("site_user_register") !== 'no' && $this->config->item("site_password_protect") !== 'yes'): ?>
		<span class="lnk first float-left"><?php echo anchor('auth/register', t('register'), 'class="jx btn btn-link btn-sm"'); ?></span>
	<?php endif; ?>
	<span class="lnk float-right"><?php echo anchor('auth/forgot_password', t('forgot_password'), 'class="jx btn btn-link btn-sm"'); ?></span>
</div>

</form>

<?php if (!empty($auth0_login_url)): ?>
<p class="text-center mt-4 mb-0">
	<a href="<?php echo html_escape($auth0_login_url); ?>"><?php echo t('auth0_back_to_sso'); ?></a>
</p>
<?php endif; ?>

</div>

<script type="text/javascript">
$(function() {
	$("#email").focus();
});
</script>
