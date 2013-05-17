<?php
$options_country=$this->ion_auth_model->get_all_countries();
?>

<div class='content-container'>

	<h1><?php echo t('user_registration');?></h1>

	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	
    <?php echo form_open(site_url().'/auth/register/', array('class'=>'form register','autocomplete'=>'off'));?>    
    <input type="hidden" name="form_token" value="<?php echo form_prep($this->form_token); ?>"/>
        
      <div class="field">
	      <label for="first_name"><?php echo t('first_name');?><span class="required">*</span></label>
	      <?php echo form_input($first_name,'','class="input-flex"');?>
      </div>

      <div class="field">
	      <label for="last_name"><?php echo t('last_name');?><span class="required">*</span></label>
	      <?php echo form_input($last_name,'','class="input-flex"');?>
      </div>
      
      <div class="field">
	      <label for="email"><?php echo t('email');?><span class="required">*</span></label>
	      <?php echo form_input($email,'','class="input-flex"');?>
      </div>

	<?php /* ?>	
      <div class="field">
	      <label for="company"><?php echo t('company');?></label>
	      <?php echo form_input($company,'','class="input-flex"');?>
      </div>

      <div class="field">
	      <label for="phone1"><?php echo t('phone');?></label>
	      <?php echo form_input($phone1,'','class="input-flex"');?>
      </div>
    <?php */ ?>	
      
        <div class="field">
            <label for="country"><?php echo t('country');?><span class="required">*</span></label>
            <?php echo form_dropdown('country', $options_country, get_form_value("country",isset($country) ? $country : '')); ?>
        </div>
	

      <div class="field">
	      <label for="password"><?php echo t('password');?><span class="required">*</span></label>
	      <?php echo form_input($password,'','class="input-flex" autocomplete="off"');?>
      </div>
      
      <div class="field">
	      <label for="password_confirm"><?php echo t('password_confirmation');?><span class="required">*</span></label>
	      <?php echo form_input($password_confirm,'','class="input-flex" autocomplete="off"');?>
      </div>

     <div class="captcha_container">
	     <?php echo $captcha_question;?>
     </div>
      
      <?php echo form_submit('submit', t('register'));?>
      <?php echo anchor('',t('cancel'), array('class'=>'')); ?>
    <?php echo form_close();?>

</div>
