<?php
	//get all groups/roles
	$user_groups=$this->ion_auth_model->get_user_groups();
	$user_group_options=array();
	foreach($user_groups as $group)
	{
		$user_group_options[$group['id']]=$group['description'];
	}
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
	
     <?php
	
		$form_action_url=site_url().'/admin/users';
		if($this->uri->segment(3)=='add')
		{
			$form_action_url.='/add';
		}
		else
		{
			$form_action_url.='/edit/'.$this->uri->segment(4);
		}
	
	?>
    
    <?php echo form_open($form_action_url, array('class'=>'form register','autocomplete'=>'off'));?>
    
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
	      <label for="company"><?php echo t('company');?></label>
	      <?php echo form_input($company);?>
      </div>
            
       <div class="field">
	      <label for="phone1"><?php echo t('phone');?></label>
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

	<?php /* ?>
    <div class="field">
        <label for="user_groups"><?php echo t('select_user_group');?></label>
        <div class="user_groups">
        <?php foreach($user_groups as $group):?>
        	<?php $checked=(in_array($group['id'],$groups)) ? 'checked="checked"' : '';?>
        	<div class="group">
	            <label for="group_id-<?php echo $group['id'];?>" style="font-weight:normal;">
				<input <?php echo $checked;?> type="checkbox" id="group_id-<?php echo $group['id'];?>" name="group_id[]" value="<?php echo $group['id'];?>"/> <?php echo $group['name'];?> <span class="description">[<?php echo $group['description'];?>]</span>
                </label>
            </div>    
		<?php endforeach;?>
        </div>        
    </div>
	<?php */ ?>
    
     <?php echo form_submit('submit', t('create'));?>
     <?php echo anchor('admin/users', t('cancel'));?>
      
    <?php echo form_close();?>

</div>