<style>
.input-fixed-1{width:300px;}
</style>
<div class='container-fluid'>
    <div class="text-right page-links">
        <a href="<?php echo site_url(); ?>/admin/countries" class="btn btn-default">
    	<span class="glyphicon glyphicon-home ico-add-color right-margin-5" aria-hidden="true"></span> <?php echo t('home');?></a>
    </div>
	<h1><?php echo t('edit_country'); ?></h1>
	<?php if (validation_errors() ) : ?>
        <div class="alert alert-danger">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

	<?php
		//form action url
		$uri_arr=$this->uri->segment_array();
		$form_action_url=site_url().'/admin/countries';
		$countryid=$this->uri->segment(4);
		if (is_numeric($countryid))
		{
			$form_action_url.='/edit/'.$countryid;
		}
		else
		{
			$form_action_url.='/add/';
		}
		
		$aliases=get_form_value('alias',isset($aliases) ? $aliases: array('') );
	?>
    
    <div class="row">
    <div class="col-md-6">
    <?php echo form_open($form_action_url, array('class'=>'form'));?>
    
    <div class="form-group">
        <label for="title"><?php echo t('name');?><span class="required">*</span></label>
        <input class="form-control" name="name" type="text" id="name"  value="<?php echo get_form_value('name',isset($name) ? $name : ''); ?>"/>
    </div>

    <div class="form-group">
        <label for="iso"><?php echo t('iso');?><span class="required">*</span></label>
        <input class="form-control" name="iso" type="text" id="iso"  value="<?php echo get_form_value('iso',isset($iso) ? $iso : ''); ?>"/>
    </div>

    <div class="form-group">
	    <label><?php echo t('Aliases');?></label>
    	<?php for($k=0;$k<5;$k++):?>
            <div class="alias">
            <input class="form-control" style="margin-bottom:2px;" name="alias[]" type="text" id="alias-<?php echo $k;?>"  value="<?php echo (isset($aliases[$k]) ? $aliases[$k] : ''); ?>"/>
            </div>
	    <?php endfor;?>
    </div>
	
	
      <?php echo form_submit('submit', t('update'),'class="btn btn-primary"');?>
      <?php echo anchor('admin/countries/', t('cancel'),'class="btn btn-default"' );?>
      
    <?php echo form_close();?>
    </div>
    </div>

</div>
