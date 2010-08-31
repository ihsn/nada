<?php
	//user group choices
	$user_group_options=array(
					'1'=>t('administrator'),
					'2'=>t('user')
				);
	//countries			
	$options_country=$this->ion_auth_model->get_all_countries();
?>
<div class='content-container'>

	<h1><?php echo $page_title; ?></h1>

	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	
    <?php echo form_open(current_url(), array('class'=>'form'));?>
    
     <div class="field">
	      <label for="username"><?php echo t('username');?><span class="required">*</span></label>
	      <?php echo form_input($username);?> 
      </div>

      <div class="field">
	      <label for="email"><?php echo t('email');?><span class="required">*</span></label>
	      <?php echo form_input($email);?>
      </div>

      <div class="field">
	      <label for="first_name"><?php echo t('first_name');?><span class="required">*</span></label>
	      <?php echo form_input($first_name);?>
      </div>

      <div class="field">
	      <label for="last_name"><?php echo t('last_name');?><span class="required">*</span></label>
	      <?php echo form_input($last_name);?>
      </div>

      <div class="field">
	      <label for="company"><?php echo t('company');?><span class="required">*</span></label>
	      <?php echo form_input($company);?>
      </div>
            
       <div class="field">
	      <label for="phone1"><?php echo t('phone');?><span class="required">*</span></label>
	      <?php echo form_input($phone1);?>
      </div>
      <div class="field">
            <label for="country"><?php echo t('country');?></label>
            <?php echo form_dropdown('country', $options_country, get_form_value("country",isset($country) ? $country : '')); ?>
      </div>
      
      <div class="field">
	      <label for="password"><?php echo t('password');?><span class="required">*</span></label>
	      <?php echo form_input($password);?>
      </div>
      
      <div class="field">
	      <label for="password_confirm"><?php echo t('password_confirmation');?><span class="required">*</span></label>
	      <?php echo form_input($password_confirm);?>
      </div>

      <div class="field">
          <label for="password_confirm"><?php echo t('user_account_status');?></label>	      
           <span style="padding-right:10px;"><?php echo form_radio('active', '1', $active==1);?> <?php echo t('user_active');?> </span>
           <span><?php echo form_radio('active', '0', $active!=1);?> <?php echo t('user_blocked');?> </span>
      </div>

    <div class="field">
        <label for="password_confirm"><?php echo t('select_user_group');?></label>
        <?php	echo form_dropdown('group_id', $user_group_options, get_form_value("group_id",isset($group_id) ? $group_id : ''));?>
    </div>
     
     <?php echo form_submit('submit', t('create'));?>
     <?php echo anchor('admin/users', t('cancel'));?>
      
    <?php echo form_close();?>

</div>