<?php
/**
* Add/edit page link [internal/external]
*/
?>
<div class="content-container">

<?php 
	//menu breadcrumbs
	include 'menu_breadcrumb.php'; 
?>

<h1 class="page-title"><?php echo $form_title; ?></h1>

<?php if (validation_errors() ) : ?>
    <div class="error">
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
    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="input-flex" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>

    <div class="field" style="display:block;">
     	<label for="url"><?php echo t('url');?><span class="required">*</span></label>
        <input class="input-flex"  name="url" type="text" id="url"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
    </div>
               
    <div class="field">
        <label for="published"><?php echo t('publish');?><span class="required">*</span></label>
        <?php echo form_dropdown('published', array(1=>t('yes'),0=>t('no')), get_form_value("published",isset($published) ? $published : '')); ?>
    </div>

    <div class="field">
        <label for="target"><?php echo t('open_in');?><span class="required">*</span></label>
        <?php echo form_dropdown('target', array(0=>t('same_window'),1=>t('new_window')), get_form_value("target",isset($target) ? $target : '')); ?>
    </div>

    <div class="field">
        <label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
        <input class="input-flex" style="width:50px" name="weight" type="text" id="weight" maxlength="3"  value="<?php echo get_form_value('weight',isset($weight) ? $weight : ''); ?>"/>
    </div>
    
<?php
	echo form_submit('submit',t('update')); 
 	echo anchor('admin/menu',t('cancel'),array('class'=>'button') );	
	echo form_close(); 
?>
</div>