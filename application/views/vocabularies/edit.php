<div class='content-container'>
    <div class="page-links">
        <a href="<?php echo site_url(); ?>/admin/vocabularies" class="button"><img src="images/house.png"/><?php echo t('home');?></a> 
    </div>
	<h1><?php echo $page_title; ?></h1>
	<?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>
    
    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	
    <?php echo form_open($this->html_form_url, array('class'=>'form'));?>
    
    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="input-flex" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>

      <?php echo form_submit('submit', t('update'));?>
      <?php echo anchor('admin/vocabularies', t('cancel'));?>
      
    <?php echo form_close();?>

</div>