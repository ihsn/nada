<div class="content-container">
<?php
	//menu breadcrumbs
	//include 'menu_breadcrumb.php'; 
?>

<h1 class="page-title"><?php echo isset($id) ? t('block_edit') : t('block_add'); ?></h1>
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

    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <input class="input-flex" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
        <input type="hidden" name="bid" value="<?php echo get_form_value('bid',isset($bid) ? $bid : ''); ?>"/>
    </div>

    <div class="field">
        <label for="block_name"><?php echo t('block_name');?><span class="required">*</span></label>
        <input class="input-flex" name="block_name" type="text" id="block_name"  value="<?php echo get_form_value('block_name',isset($block_name) ? $block_name : ''); ?>"/>
    </div>
           
    <div class="field">
        <label for="body"><?php echo t('body');?></label>
        <textarea id="body" class="input-flex"  name="body" rows="30"><?php echo get_form_value('body',isset($body) ? $body : ''); ?></textarea>
    </div>

    <div class="field">
        <label for="published"><?php echo t('publish');?><span class="required">*</span></label>
        <?php echo form_dropdown('published', array(1=>t('yes'),0=>t('no')), get_form_value("published",isset($published) ? $published : '')); ?>
    </div>
    
    <div class="field">
        <label for="block_format"><?php echo t('open_in');?><span class="required">*</span></label>
        <?php echo form_dropdown('block_format', array('php'=>t('PHP'),'html'=>t('HTML')), get_form_value("block_format",isset($block_format) ? $block_format : '')); ?>
    </div>

    <div class="field">
        <label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
        <input class="input-flex" style="width:50px" name="weight" type="text" id="weight" maxlength="3"  value="<?php echo get_form_value('weight',isset($weight) ? $weight : ''); ?>"/>
    </div>

    <div class="field">
        <label for="pages"><?php echo t('pages');?></label>
        <textarea id="pages" class="input-flex"  name="pages" rows="5"><?php echo get_form_value('pages',isset($pages) ? $pages : ''); ?></textarea>
    </div>

<?php
	echo form_submit('submit',t('update'),'id="btnupdate"'); 
 	echo anchor('admin/blocks',t('cancel'),array('class'=>'button') );	
?>

<? echo form_close(); ?>    
</div>