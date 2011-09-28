<?php
$options_country=$this->ion_auth_model->get_all_countries();
?>

<?php echo form_open(site_url().'/install/create_user/', array('class'=>'form register'));?>   
<div class='content-container' style="margin:10px;">

	<h1 style="margin-bottom:10px;"><?php echo t('create_admin_account');?></h1>

	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php if ( isset($this->error)) : ?>
        <div class="error">
            <p><?php echo $this->error; ?></p>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	
        
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

      <div class="field">
	      <label for="company"><?php echo t('company');?></label>
	      <?php echo form_input($company,'','class="input-flex"');?>
      </div>

      <div class="field">
	      <label for="phone1"><?php echo t('phone');?></label>
	      <?php echo form_input($phone1,'','class="input-flex"');?>
      </div>
      
        <div class="field">
            <label for="country"><?php echo t('country');?></label>
            <?php echo form_dropdown('country', $options_country, get_form_value("country",isset($country) ? $country : '')); ?>
        </div>

      <div class="field">
	      <label for="password"><?php echo t('password');?><span class="required">*</span></label>
	      <?php echo form_input($password,'','class="input-flex"');?>
      </div>
      
      <div class="field">
	      <label for="password_confirm"><?php echo t('password_confirmation');?><span class="required">*</span></label>
	      <?php echo form_input($password_confirm,'','class="input-flex"');?>
      </div> 
 </div>

	<div style="background-color:#CCCCCC;margin:10px;padding:10px;text-align:right;">
 		<?php echo form_submit('submit', t('create_user'));?>
	</div>     
     
<?php echo form_close();?>