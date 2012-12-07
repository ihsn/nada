<div class="content-container">
<?php 	$this->load->view('user_groups/navigation_links');  ?>

<h1 class="page-name"><?php echo isset($id) ? t('user_groups_edit') : t('user_groups_add'); ?></h1>
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php echo form_open($this->html_form_url, array('class'=>'form') ); ?>
    <div class="field">
        <label for="name"><?php echo t('name');?><span class="required">*</span></label>
        <input class="input-flex" name="name" type="text" id="name"  value="<?php echo get_form_value('name',isset($name) ? $name : ''); ?>"/>
        <input type="hidden" name="pid" value="<?php echo get_form_value('pid',isset($pid) ? $pid : ''); ?>"/>
    </div>

 
           
    <div class="field">
        <label for="description"><?php echo t('description');?></label>
        <textarea id="body" class="input-flex"  name="description" rows="10"><?php echo get_form_value('description',isset($description) ? $description : ''); ?></textarea>        
    </div>

    <div class="field">
        <label for="group_type"><?php echo t('group_type');?></label>
		<select name="group_type">
       		<option selected="selected" value="<?php echo get_form_value('group_type',isset($group_type) ? $group_type : ''); ?>"><?php echo get_form_value('group_type',ucfirst(isset($group_type) ? $group_type : '')); ?></option>
  			<option value="admin">Admin</option>
            <option value="reviewer">Reviewer</option>
  			<option value="user">User</option>

		</select>
    </div>
    
    <div class="field">
        <label for="access_type"><?php echo t('access_type');?></label>
		<select name="access_type">
       		<option value="<?php echo get_form_value('access_type',isset($access_type) ? $access_type : ''); ?>"><?php echo get_form_value('type',ucfirst(isset($access_type) ? $access_type : '')); ?></option>
  			<option value="none">None</option>
  			<option value="limited">Limited</option>
  			<option value="unlimited">Unlimited</option>

		</select>
    </div>
<?php
 //edit user
	echo form_submit('submit',t('update'),'id="btnupdate"'); 
 	echo anchor('admin/user_groups',t('cancel'));	
?>

<? echo form_close(); ?>    
</div>