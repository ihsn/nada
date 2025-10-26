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

<h1 class="text-center pb-3 pt-4"><?php echo t('reset_password');?></h1>

<?php if (validation_errors() ) : ?>
    <div class="alert alert-danger p-3 mb-2">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php if ($error!="") : ?>
    <div class="alert alert-danger p-3 mb-2"><?php echo $error; ?></div>
<?php endif; ?>

<?php $message=$this->session->flashdata('message');?>
<?php if ($message!="") : ?>
    <div class="alert alert-success p-3 mb-2"><?php echo $message; ?></div>
<?php endif; ?>

<div style="padding:5px;">
    <form method="post" autocomplete="off" class="form">          
        <div class="form-group">
            <label for="new_password"><?php echo t('new_password');?></label>
            <?php echo form_input($new_password, '', 'class="form-control"'); ?>
        </div>
        
        <div class="form-group">
            <label for="new_password_confirm"><?php echo t('confirm_new_password');?></label>
            <?php echo form_input($new_password_confirm, '', 'class="form-control"'); ?>
        </div>
                
        <div class="form-group">
            <button class="btn btn-primary btn-block" type="submit"><?php echo t('submit');?></button>
        </div>
    </form>
    
    <div class="login-footer mt-3">
        <div class="ot clearfix">
            <span class="lnk float-left"><?php echo anchor('auth/login', t('back_to_login'), 'class="jx btn btn-link btn-sm"'); ?></span>
        </div>
    </div>
</div>

<div class="privacy-info mt-4 text-secondary"><?php echo t('site_login_privacy_terms');?></div>
</div>
