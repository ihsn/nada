<div class="content-container">
<?php 	$this->load->view('repository_sections/navigation_links');  ?>

<h1 class="page-title"><?php echo isset($id) ? t('repository_section_edit') : t('repository_section_add'); ?></h1>
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
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="input-flex" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
        <input type="hidden" name="pid" value="<?php echo get_form_value('pid',isset($pid) ? $pid : ''); ?>"/>
    </div>
  
    <div class="field">
        <label for="weight"><?php echo t('weight');?></label>
        <input class="input-flex" style="width:50px" name="weight" type="text" id="weight" maxlength="3"  value="<?php echo get_form_value('weight',isset($weight) ? $weight : ''); ?>"/>
    </div>
<?php
 //edit user
	echo form_submit('submit',t('update'),'id="btnupdate"'); 
 	echo anchor('admin/repository_sections',t('cancel'),array('class'=>'button') );	
?>

<? echo form_close(); ?>    
</div>