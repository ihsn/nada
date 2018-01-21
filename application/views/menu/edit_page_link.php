<?php
/**
* Add/edit page link [internal/external]
*/
?>
<div class="container-fluid page-menu-link-edit">

<?php 
	//menu breadcrumbs
	include 'menu_breadcrumb.php'; 
?>

<h1 class="page-title"><?php echo $form_title; ?></h1>

<?php if (validation_errors() ) : ?>
    <div class="alert alert-danger">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php echo form_open(current_url(), array('class'=>'form') ); ?>
	<input type="hidden" name="linktype" value="1"/>
	<input type="hidden" name="pid" value="<?php echo get_form_value('pid',isset($pid) ? $pid : ''); ?>"/>
    <div class="form-group">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="form-control" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>

    <div class="form-group">
     	<label for="url"><?php echo t('url');?><span class="required">*</span></label>
        <input class="form-control"  name="url" type="text" id="url"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
    </div>

    <div class="form-group form-inline form-inline-with-spacing">    

    <div class="form-group">
        <label for="target"><?php echo t('open_in');?><span class="required">*</span></label>
        <?php echo form_dropdown('target', array(0=>t('same_window'),1=>t('new_window')), get_form_value("target",isset($target) ? $target : '')); ?>
    </div>

    <div class="form-group">
        <label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
        <input class="form-control" name="weight" type="text" id="weight" size="3" maxlength="3"  value="<?php echo get_form_value('weight',isset($weight) ? $weight : ''); ?>"/>
    </div>
    
    <div class="form-group field">
        <label for="published"><?php echo t('publish');?><span class="required">*</span></label>
        <?php echo form_dropdown('published', array(1=>t('yes'),0=>t('no')), get_form_value("published",isset($published) ? $published : '')); ?>
    </div>

    </div>

<?php
	echo form_submit('submit',t('update'),array('class'=>'btn btn-primary')); 
 	echo anchor('admin/menu',t('cancel'),array('class'=>'btn btn-default') );	
	echo form_close(); 
?>
</div>
