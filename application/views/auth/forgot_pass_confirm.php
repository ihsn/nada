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
.wb-template-blank .wb-page-body.container-fluid{
    margin-top:150px;
}
</style>
<div class="login-form border shadow rounded">

<h1 class="text-center pb-3 pt-4"><?php echo t('forgot_password');?></h1>

<div style="padding:5px;">
    <div class="alert alert-info text-center">
        <?php echo t('message_sent_to_your_email');?>
    </div>
    
    <div class="text-center">
        <?php echo anchor('auth/login', t('back_to_login'), 'class="btn btn-primary"'); ?>
    </div>
</div>

</div>