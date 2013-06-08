<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
body,html{background-color:#F0F0F0;margin:0px;padding:0px;}
</style>
<div class="nada-login">

<div class="login-header">
	<div class="title"><?php echo anchor ("",$this->config->item('website_title'),'class="jx"'); ?></div>
</div>

<table style="width:400px;margin-top:50px;margin-left:100px;" >
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


<tr valign="top">
	<td style="width:400px;">
        <div class="login-box">
        <h1><?php echo t('log_in');?></h1>
        <form method="post" class="form" autocomplete="off">        
        <div class="pageTitleBorder"></div>
        
        <div style="padding:5px;">
        
            <div class="field">
                <label for="email"><?php echo t('email');?>:</label>
                <?php //echo form_input(get_form_value('email',isset($email) ? $email : ''),NULL,'class="input-flex"' );?>
                <input class="input-flex"  name="email" type="text" id="email"  value=""/>
            </div>
        
            <div class="field">
                <label for="password"><?php echo t('password');?>:</label>
                <input class="input-flex"  name="password" type="password" id="password"  value=""/>
            </div>    
        
        	<?php /*
            <div class="field-inline">            
                <?php echo form_checkbox('remember', '1', FALSE,'id="remember"');?>
                <label for="remember"><?php echo t('remember_me');?></label>
            </div> */ ?>
        
            <div class="login-footer">
                <input type="submit" name="submit" value="<?php echo t('login');?>" class="btn-style-2"/>
                <?php /* <input type="button" name="cancel" id="cancel" value="<?php echo t('cancel');?>" class="button jx" onclick="history.back();"/> */?>
                <span class="ot">
                <?php if ($this->config->item("site_user_register")!=='no' && $this->config->item("site_password_protect")!=='yes'):?>	
                    <span class="lnk first"><?php echo anchor('auth/register',t('register'),'class="jx"'); ?></span>
                <?php endif;?>
                <span class="lnk"><?php echo anchor('auth/forgot_password',t('forgot_password'),'class="jx"'); ?></span>
                </span>
            </div>
        </form>
        
        <div class="privacy-info"><?php echo t('site_login_privacy_terms');?></div>
        </div>    
    </td>	
</table>

</div>
</div>
<script type="text/javascript">

$(function() {
  $("#email").focus();
});
</script>