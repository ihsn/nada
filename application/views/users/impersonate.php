<style type="text/css">
.user-row .user-name{font-weight:bold;margin-right:5px;}
</style>
<div class='content-container'>

	<h1><?php echo t('impersonate_user'); ?></h1>

	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	
    <?php echo form_open();?>
    
    <div class=""><?php echo t('impersonate_msg');?>:</div>
    <?php foreach($users as $user):?>
    	<div class="user-row">
		<input type="radio" name="user" value="<?php echo $user['id'];?>"/>
		<span class="user-name"><?php echo $user['first_name']," ",$user['last_name'];?></span>
        <span class="email"><?php echo $user['email'];?></span>
        </div>
    <?php endforeach;?>
     
    <div style="margin-top:20px;">
        <input class="btn btn-primary" type="submit" name="submit" value="<?php echo t('impersonate');?>"/>
        <a class="btn btn-default" href="<?php echo site_url('admin/users');?>"><?php echo t('cancel');?></a>
    </div>
      
    <?php echo form_close();?>

</div>