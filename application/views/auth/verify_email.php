<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
.wb-template-blank .wb-page-body.container-fluid{
    margin-top:150px;
}

.login-form{
    width: 100%;
    max-width: 580px;
    padding: 35px;
    margin: auto;
}
.privacy-info{
    font-size:smaller;
}
</style>
<div class="login-form border shadow-sm rounded">

<h1 class="mb-3"><?php echo t('verify_your_email');?></h1>

<?php $reason=$this->session->flashdata('reason');?>
<?php if ($reason!==""):?>
    <?php echo ($reason!="") ? '<div class="reason">'.$reason.'</div>' : '';?>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php if (!empty($message)):?>
    <div class="bg-info text-white border rounded mb-3 mt-4 p-3 "><?php echo $message;?></div>    
<?php endif;?>

<?php if (isset($error) && $error!=''):?>
	<?php $error= '<div class="error">'.$error.'</div>'?>
<?php else:?>
	<?php $error=$this->session->flashdata('error');?>
	<?php $error= ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>            
<?php endif;?>	



<div style="padding:5px;">

    <span class="font-weight-bold"><?php echo $email;?></span>

    <div class="mt-3">
        <p class="font-weight-normal"><?php echo t('user_email_not_verified');?></p>
    </div>
        
    <div class="privacy-info mt-4 text-secondary">
        <a class="btn btn-primary btn-block" href="<?php echo site_url('auth/verify/?resend=1');?>"><?php echo t('resend_verification_email');?></a>
    </div>
        
</div>    
   

</div>
</div>

<script type="text/javascript">

$(function() {
  $("#code").focus();
});
</script>