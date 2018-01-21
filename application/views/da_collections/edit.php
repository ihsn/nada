<div class="container-fluid">
<?php 	$this->load->view('da_collections/navigation_links');  ?>

<h1 class="page-title"><?php echo isset($id) ? t('da_collection_edit') : t('da_collection_add'); ?></h1>
<?php if (validation_errors() ) : ?>
    <div class="alert alert-danger">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php echo form_open($this->html_form_url, array('class'=>'form') ); ?>
    <div class="form-group">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="form-control" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>
  
    <div class="form-group">
        <label for="weight"><?php echo t('description');?></label>
        <textarea class="form-control" name="description" ><?php echo get_form_value('description',isset($description) ? $description : ''); ?></textarea>
    </div>
<?php
 //edit user
	//echo form_submit('submit',t('update'),'id="btnupdate"'); 
	echo form_submit('submit',t('update'),array('class'=>'btn btn-primary','id'=>'btnupdate'));
 	echo anchor('admin/da_collections',t('cancel'),array('class'=>'btn btn-default'));	
?>

<? echo form_close(); ?>    
</div>
