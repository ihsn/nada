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

/* Fallback for providers without specific styling */
.social-login-btn:not(.orcid):not(.google):not(.facebook):not(.github) {
    border-color: #007bff;
    color: #007bff;
}

.social-login-btn:not(.orcid):not(.google):not(.facebook):not(.github):hover {
    background-color: #007bff;
    color: white;
}

.email-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
}

.email-info .email {
    font-weight: bold;
    color: #1976d2;
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

<h1 class="text-center pb-3"><?php echo t('log_in');?></h1>

<div class="email-info">
    <div class="text-center">
        <strong><?php echo t('email');?>:</strong> <span class="email"><?php echo $email; ?></span>
    </div>
    <?php if ($existing_auth_type): ?>
        <div class="text-center mt-2">
            <small class="text-muted"><?php echo sprintf(t('account_exists_with'), strtoupper($existing_auth_type)); ?></small>
        </div>
    <?php else: ?>
        <div class="text-center mt-2">
            <small class="text-muted"><?php echo t('new_account_will_be_created'); ?></small>
        </div>
    <?php endif; ?>
</div>

<div style="padding:5px;">

    <?php if (!empty($providers)): ?>
    <div class="border p-5 bg-light">
        <h5 class="text-center mb-3"><?php echo t('login_with_social'); ?></h5>
        
        <?php foreach ($providers as $key => $provider): ?>
            <a href="<?php echo site_url($provider['login_url']); ?>" class="social-login-btn <?php echo $key; ?>">
                <img src="<?php echo $provider['icon']; ?>" alt="<?php echo $provider['name']; ?>">
                <?php echo t('login_with') . ' ' . $provider['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center my-md">
      <hr class="h-px grow bg-neutral-content-disabled border-none m-0">

      <span class="p-2 m-2">
        OR
      </span>

      <hr class="h-px grow bg-neutral-content-disabled border-none m-0">
    </div>
    <?php endif; ?>

    <?php if ($existing_auth_type): ?>
        <!-- Show password login option only for existing users with built-in auth -->
        <div class="login-footer">
            <btn class="btn btn-light btn-block border" onclick="window.location='<?php echo site_url('auth/nada');?>'">
                <i class="fas fa-envelope float-left" style="font-size:24px;"></i>
                <?php echo t('login_with_password'); ?>
            </btn>
        </div>
    <?php else: ?>
        <!-- Show registration option for new users -->
        <div class="login-footer">
            <btn class="btn btn-light btn-block border" onclick="window.location='<?php echo site_url('auth/create_user');?>'">
                <i class="fas fa-user-plus float-left" style="font-size:24px;"></i>
                <?php echo t('create_account_with_email'); ?>
            </btn>
        </div>
    <?php endif; ?>
</div>
        
<div class="privacy-info mt-4 text-secondary"><?php echo t('site_login_privacy_terms');?></div>
        
</div>    

</div>
</div>

<script type="text/javascript">
$(function() {
  // Focus on first social login button
  $(".social-login-btn").first().focus();
});
</script> 