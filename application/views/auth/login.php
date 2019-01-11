<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
.login-form{
    width: 100%;
    max-width: 420px;
    padding: 15px;
    margin: auto;
}
.privacy-info{
    font-size:smaller;
}
</style>
<div class="login-form">

<table>
<?php $reason=$this->session->flashdata('reason');?>
<?php if ($reason!==""):?>
<tr>
	<td colspan="3"><?php echo ($reason!="") ? '<div class="reason">'.$reason.'</div>' : '';?></td>
</tr>
<?php endif;?>

<?php $message=$this->session->flashdata('message');?>
<?php if (isset($error) && $error!=''):?>
	<?php $error= '<div class="error">'.$error.'</div>'?>
<?php else:?>
	<?php $error=$this->session->flashdata('error');?>
	<?php $error= ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>            
<?php endif;?>	

<?php if ($error!='' || $message!=''):?> 
<tr>
	<td colspan="3"><?php echo $message.$error;?></td>
</tr>
<?php endif;?>
</table>



        <h1><?php echo t('log_in');?></h1>
        <form method="post" class="form" autocomplete="off">        
        <div class="pageTitleBorder"></div>
        
        <div style="padding:5px;">
        
            <div class="form-group">
                <!--<label for="email"><?php echo t('email');?>:</label>-->
                <input class="form-control"  name="email" type="text" id="email"  value="" placeholder="<?php echo t('email');?>" />
            </div>
        
            <div class="form-group">
                <!--<label for="password"><?php echo t('password');?>:</label>-->
                <input class="form-control"  name="password" type="password" id="password"  value="" placeholder="<?php echo t('password');?>"/>
            </div>    
        
        	<?php /*
            <div class="field-inline">            
                <?php echo form_checkbox('remember', '1', FALSE,'id="remember"');?>
                <label for="remember"><?php echo t('remember_me');?></label>
            </div> */ ?>
        
            <div class="login-footer">
                <input type="submit" name="submit" value="<?php echo t('login');?>" class="btn btn-primary btn-block"/>
                <?php /* <input type="button" name="cancel" id="cancel" value="<?php echo t('cancel');?>" class="button jx" onclick="history.back();"/> */?>
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
  $("#email").focus();
});
</script>