<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/citations/" class="button"><img src="images/house.png"/><?php echo t('citation_home');?></a> 
</div>

<div class="content-container">

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('import_citation'); ?></h1>

<?php echo form_open_multipart(current_url(), array('class'=>'form') ); ?>
<div class="field">
	<label for="bibtex_string"><?php echo t('paste_bibtex_string');?></label>
	<textarea rows="10" name="bibtex_string" id="bibtex_string" class="input-flex"><?php echo get_form_value('bibtex_string',isset($bibtex_string) ? $bibtex_string : ''); ?></textarea>
</div>

<?php
/*
<div class="field">
	<label for="url"><?php echo t('bibtex_url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>

<div class="field">
	<label for="upload"><?php echo t('upload');?></label>
	<input name="file" type="file" id="file" size="50" class="input-flex"  />
</div>
*/
?>

<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" />
	<?php echo anchor('admin/citations/', t('cancel'), array('class'=>'button'));?>
</div>


<?php echo form_close();?>
</div>