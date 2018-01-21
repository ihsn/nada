<style>
    .form{
        max-width:500px;
    }
</style>
<div class='container-fluid terms-edit-page'>
    <div class="text-right page-links">
        <a href="<?php echo site_url(); ?>/admin/vocabularies" class="btn btn-default"><span class="glyphicon glyphicon-home ico-add-color right-margin-5" aria-hidden="true"></span> <?php echo t('vocabularies');?></a>
    </div>
	<h1><?php echo $page_title; ?></h1>
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
		$form_action_url=site_url().'/admin/terms/'.$this->uri->segment(3);
		if ($this->uri->segment(4)=='add')
		{
			$form_action_url.='/add';
		}
		else
		{
			$form_action_url.='/edit/'.$this->uri->segment(5);
		}
	?>
	
    <?php echo form_open($form_action_url, array('class'=>'form'));?>
    
    <div class="form-group">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="form-control" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>

	<div class="form-group">
        <label for="pid"><?php echo t('select_term_parent');?></label>
        <?php	echo form_dropdown(array('class'=>'form-control','name'=>'pid'), $this->term_tree, $pid);?>
    </div>    

      <?php echo form_submit('submit',t('update'),array('class'=>'btn btn-primary','id'=>'btnupdate')); ?>
      <?php echo anchor('admin/terms/'.$this->uri->segment(3), t('cancel'),array('class'=>'btn btn-default') );?>
      
    <?php echo form_close();?>

</div>
