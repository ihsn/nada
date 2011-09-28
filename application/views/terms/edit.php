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
    
    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="input-flex" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
    </div>

	<div class="field">
        <label for="pid"><?php echo t('select_term_parent');?></label>
        <?php	echo form_dropdown('pid', $this->term_tree, $pid);?>
    </div>    

      <?php echo form_submit('submit', t('update'));?>
      <?php echo anchor('admin/terms/'.$this->uri->segment(3), t('cancel'));?>
      
    <?php echo form_close();?>

</div>