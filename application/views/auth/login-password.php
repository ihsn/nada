<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
.login-form{
    width: 100%;
    max-width: 520px;
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

/* Email display styling for long addresses */
.email-display {
    display: inline-block;
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    word-break: break-all;
}

.email-display:hover {
    white-space: normal;
    word-wrap: break-word;
    overflow: visible;
    position: relative;
    z-index: 10;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 4px;
    padding: 4px;
}

</style>
<div class="login-form border roudned p-5 shadow" >


<?php $reason=$this->session->flashdata('reason');?>
<?php if ($reason!==""):?>
    <?php echo ($reason!="") ? '<div class="reason">'.$reason.'</div>' : '';?>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php if (isset($error) && $error!=''):?>
	<?php $error= '<div class="alert alert-danger">'.$error.'</div>'?>
<?php else:?>
	<?php $error=$this->session->flashdata('error');?>
	<?php $error= ($error!="") ? '<div class="alert-danger">'.$error.'</div>' : '';?>            
<?php endif;?>	

<?php if ($error!=''):?> 
	<div><?php echo $error;?></div>
<?php endif;?>

<?php if ($message!=''):?> 
	<div class="alert alert-primary"><?php echo $message;?></div>
<?php endif;?>


<h1><?php echo t('log_in');?></h1>
<form method="post" class="form" autocomplete="off">

<input type="hidden" name="<?php echo $csrf['keys']['name']; ?>" value="<?php echo $csrf['name']; ?>" />
<input type="hidden" name="<?php echo $csrf['keys']['value']; ?>" value="<?php echo $csrf['value']; ?>" />

<div style="padding:5px;">

    <div class="form-group mt-3">
        <span class="border rounded p-1 email-display" title="<?php echo htmlspecialchars($email);?>"><?php echo htmlspecialchars($email);?></span>
        <a href="<?php echo site_url('auth/login');?>" class="btn btn-link btn-sm"><?php echo t('change_email');?></a>
    </div>
    
    <div class="form-group">
        <input class="form-control"  name="password" type="password" id="password"  value="" placeholder="<?php echo t('password');?>"/>
    </div>
    
    
    <div class="login-footer">
        <input type="submit" name="submit" value="<?php echo t('login');?>" class="btn btn-primary btn-block"/>                
        <div class="ot clearfix">
        <?php if ($this->config->item("site_user_register")!=='no' && $this->config->item("site_password_protect")!=='yes'):?>	
            <span class="lnk first float-left"><?php echo anchor('auth/register',t('register'),'class="jx btn btn-link btn-sm"'); ?></span>
        <?php endif;?>
        <span class="lnk float-right"><?php echo anchor('auth/forgot_password',t('forgot_password'),'class="jx btn btn-link btn-sm"'); ?></span>
        </div>
    </div>
</form>
        
<div class="privacy-info mt-4 text-secondary"><?php echo t('site_login_privacy_terms');?></div>
        
</div>    
   

</div>
</div>

<script type="text/javascript">

$(function() {
  $("#password").focus();
});
</script>