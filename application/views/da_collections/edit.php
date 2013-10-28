<div class="content-container">
<?php 	$this->load->view('da_collections/navigation_links');  ?>

<h1 class="page-title"><?php echo isset($id) ? t('da_collection_edit') : t('da_collection_add'); ?></h1>
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
    </div>
  
    <div class="field">
        <label for="weight"><?php echo t('description');?></label>
        <textarea class="input-flex" name="description" style="height:100px;"><?php echo get_form_value('description',isset($description) ? $description : ''); ?></textarea>
    </div>
<?php
 //edit user
	echo form_submit('submit',t('update'),'id="btnupdate"'); 
 	echo anchor('admin/da_collections',t('cancel') );	
?>

<? echo form_close(); ?>    
</div>