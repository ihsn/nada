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
    display: flex
;
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
    margin-bottom: 10px;
    padding: 12px 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
    color: #333;
    text-decoration: none;
    display: block;
    text-align: center;
    transition: all 0.3s ease;
}

.social-login-btn:hover {
    background: #f8f9fa;
    text-decoration: none;
    color: #333;
}

.social-login-btn i {
    margin-right: 10px;
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
<form method="post" class="form" autocomplete="off">

<div style="padding:5px;">

    <?php if (!empty($providers)): ?>
    <div class="border p-5 bg-light">
        <h5 class="text-center mb-3"><?php echo t('login_with_social'); ?></h5>
        
        <?php foreach ($providers as $key => $provider): ?>
            <a href="<?php echo site_url($provider['login_url']); ?>" class="social-login-btn">
                <i class="<?php echo $provider['icon']; ?>"></i>
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

        
    <div class="login-footer">
        <btn class="btn btn-light btn-block border" onclick="window.location='<?php echo site_url('auth/nada');?>'">
            <i class="fas fa-envelope float-left" style="font-size:24px;"></i>
            Login with Email
        </btn>
    </div>
</form>
        
<div class="privacy-info mt-4 text-secondary"><?php echo t('site_login_privacy_terms');?></div>
        
</div>    
   

</div>
</div>

<script type="text/javascript">

$(function() {
  $("#email").focus();
});
</script>