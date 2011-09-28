<?php
$options_country=$this->ion_auth_model->get_all_countries();
?>

<h1><?php echo t('edit_profile');?> - <?php echo $user->first_name. ' ' . $user->last_name; ?></h1>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<form method="post" autocomplete="off">
<input type="hidden" name="form_token" value="<?php echo form_prep($this->form_token); ?>"/>
<table class="grid-table" cellspacing="0">
	<tr>
    	<td><?php echo t('first_name');?></td>
        <td><?php echo form_input('first_name',get_form_value('first_name',isset($user->first_name) ? $user->first_name: '') ) ;?></td>
    </tr>

	<tr>
    	<td><?php echo t('last_name');?></td>
        <td><?php echo form_input('last_name',get_form_value('last_name',isset($user->last_name) ? $user->last_name: '') ) ;?></td>
    </tr>
	<tr>
    	<td><?php echo t('email');?></td>
        <td><?php echo $user->email; ?></td>
    </tr>

	<tr>
    	<td><?php echo t('company');?></td>
        <td><?php echo form_input('company',get_form_value('company',isset($user->company) ? $user->company: '') ) ;?></td>
    </tr>

	<tr>
    	<td><?php echo t('phone');?></td>
        <td><?php echo form_input('phone',get_form_value('phone',isset($user->phone) ? $user->phone: '') ) ;?></td>
    </tr>    
	<tr>
    	<td><?php echo t('country');?></td>
        <td><?php echo form_dropdown('country', $options_country, get_form_value("country",isset($user->country) ? $user->country : '')); ?></td>
    </tr>
</table>
<div style="margin-top:10px;">
	<?php echo form_submit('submit', t('update'));?> 
	<?php echo anchor('auth/profile',t('cancel'), array('class'=>'')); ?>
</div>    
</form>