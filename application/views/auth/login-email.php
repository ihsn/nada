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

.items-center {
    align-items: center;
}
.flex {
    display: flex;
}
.my-md {
    margin-bottom: 1rem;
    margin-top: 1rem;
}

.bg-neutral-content-disabled {
    background-color: gray;
}
.border-none {
    border-style: none;
}
.grow {
    flex-grow: 1;
}
.h-px {
    height: 1px;
}
.m-0 {
    margin: 0;
}
hr {
    border: 0;
    border-bottom: 1px solid #e2e8f0;
    background-color: transparent;
    margin: var(--misc-divider-margin);
}

.social-login-btn {
    margin-bottom: 12px;
    padding: 14px 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
    color: #333;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.social-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    text-decoration: none;
    color: #333;
}

.social-login-btn img {
    margin-right: 12px;
    width: 20px;
    height: 20px;
    object-fit: contain;
}

/* Provider-specific colors */
.social-login-btn.orcid {
    border-color: #a6ce39;
    color: #a6ce39;
}

.social-login-btn.orcid:hover {
    background-color: #a6ce39;
    color: white;
}

.social-login-btn.google {
    border-color: #4285f4;
    color: #4285f4;
}

.social-login-btn.google:hover {
    background-color: #4285f4;
    color: white;
}

.social-login-btn.facebook {
    border-color: #1877f2;
    color: #1877f2;
}

.social-login-btn.facebook:hover {
    background-color: #1877f2;
    color: white;
}

.social-login-btn.github {
    border-color: #333;
    color: #333;
}

.social-login-btn.github:hover {
    background-color: #333;
    color: white;
}

.social-login-btn.linkedin {
    border-color: #0077b5;
    color: #0077b5;
}

.social-login-btn.linkedin:hover {
    background-color: #0077b5;
    color: white;
}

/* Fallback for providers without specific styling */
.social-login-btn:not(.orcid):not(.google):not(.facebook):not(.github):not(.linkedin) {
    border-color: #007bff;
    color: #007bff;
}

.social-login-btn:not(.orcid):not(.google):not(.facebook):not(.github):not(.linkedin):hover {
    background-color: #007bff;
    color: white;
}
</style>
<div class="login-form border shadow rounded">

<?php $reason=$this->session->flashdata('reason');?>
<?php if ($reason!==""):?>
    <?php echo ($reason!="") ? '<div class="reason p-3 mb-2">'.$reason.'</div>' : '';?>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php if (isset($error) && $error!=''):?>
	<?php $error= '<div class="alert alert-danger p-3 mb-2">'.$error.'</div>'?>
<?php else:?>
	<?php $error=$this->session->flashdata('error');?>
	<?php $error= ($error!="") ? '<div class="alert-danger p-3 mb-2">'.$error.'</div>' : '';?>            
<?php endif;?>	

<?php if ($error!=''):?> 
	<div><?php echo $error;?></div>
<?php endif;?>

<?php if ($message!=''):?> 
	<div class="alert alert-primary"><?php echo $message;?></div>
<?php endif;?>

<h1 class="text-center pb-3 pt-4"><?php echo t('log_in');?></h1>

<div style="padding:5px;">

    <?php if (isset($enable_email_auth) && $enable_email_auth): ?>
    <div class="p-3">
        <form method="post" class="form" autocomplete="off">
            <div class="form-group">
                <!--<label for="email"><?php echo t('email');?></label>-->
                <?php echo form_input($email, '', 'class="form-control" placeholder="'.t('enter_your_email').'"'); ?>
            </div>
            
            <div class="form-group">
                <input type="hidden" name="<?php echo $csrf['keys']['name']; ?>" value="<?php echo $csrf['name']; ?>" />
                <input type="hidden" name="<?php echo $csrf['keys']['value']; ?>" value="<?php echo $csrf['value']; ?>" />
                <button type="submit" class="btn btn-primary btn-block"><?php echo t('continue');?></button>
            </div>
        </form>
    </div>

    <?php if (!empty($providers)): ?>
    <div class="flex items-center my-md">
      <hr class="h-px grow bg-neutral-content-disabled border-none m-0">

      <span class="p-2 m-2">
        OR
      </span>

      <hr class="h-px grow bg-neutral-content-disabled border-none m-0">
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($providers)): ?>
    <div class="p-2">
        <?php if (!isset($enable_email_auth) || !$enable_email_auth): ?>
            
        <?php endif; ?>
        
        <?php foreach ($providers as $key => $provider): ?>
            <a href="<?php echo site_url($provider['login_url']); ?>" class="social-login-btn <?php echo $key; ?>">
                <img src="<?php echo $provider['icon']; ?>" alt="<?php echo $provider['name']; ?>">
                <?php echo t('login_with') . ' ' . $provider['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($enable_email_auth) && $enable_email_auth): ?>
    <div class="login-footer mt-3">
        <div class="ot clearfix">
        <?php if ($this->config->item("site_user_register")!=='no' && $this->config->item("site_password_protect")!=='yes'):?>	
            <span class="lnk first float-left"><?php echo anchor('auth/register',t('register'),'class="jx btn btn-link btn-sm"'); ?></span>
        <?php endif;?>
        <span class="lnk float-right"><?php echo anchor('auth/forgot_password',t('forgot_password'),'class="jx btn-link btn-sm"'); ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>
        
<div class="privacy-info mt-4 text-secondary"><?php echo t('site_login_privacy_terms');?></div>
        
</div>    

</div>
</div>

<script type="text/javascript">
$(function() {
  $("#email").focus();
});
</script> 