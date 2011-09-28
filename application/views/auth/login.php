<style>
body,html{background-color:#F0F0F0;margin:0px;padding:0px;}
</style>

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
        <div class='login-box' >
        <h1>Log in</h1>
        <form method="post" class="form" autocomplete="off">        
        <div class="pageTitleBorder"></div>
        
        <div style="padding:5px;">
        
            <div class="field">
                <label for="email"><?php echo t('email');?>:</label>
                <?php echo form_input(get_form_value('email',isset($email) ? $email : ''),NULL,'class="input-flex"' );?>
            </div>
        
            <div class="field">
                <label for="password"><?php echo t('password');?>:</label>
                <?php echo form_input(get_form_value('password',isset($password) ? $password : ''),NULL, 'class="input-flex" autocomplete="off"');?>
            </div>    
        
            <div class="field-inline">            
                <?php echo form_checkbox('remember', '1', FALSE,'id="remember"');?>
                <label for="remember"><?php echo t('remember_me');?></label>
            </div>
        
            <div>
                <input type="submit" name="submit" value="<?php echo t('login');?>" class="button"/>
                <input type="button" name="cancel" id="cancel" value="<?php echo t('cancel');?>" class="button jx" onclick="history.back();"/>
                <?php if (!$this->config->item("site_user_register")=='no' || !$this->config->item("site_password_protect")=='yes'):?>	
                    <?php echo anchor('auth/register',t('register'),'class="jx"'); ?>
                <?php endif;?>
                <?php echo anchor('auth/forgot_password',t('forgot_password'),'class="jx"'); ?>
            </div>
        <?php echo form_close();?>
        </div>    
    </td>	
</table>

    
</div>
<script type="text/javascript">

$(function() {
  $("#email").focus();
});
</script>