<div class="content-container">

<div class="page-links">
	<?php echo anchor('admin/catalog/'.$this->uri->segment(3).'/resources',t('link_resource_home'),array('class'=>'button') );	?>
</div>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo $form_title; ?></h1>

<?php echo form_open_multipart(current_url(), array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>

<div class="field">
    <label for="userfile"><?php echo t('select_rdf_file');?></label>
    <input  type="file" name="userfile" id="userfile" size="60"/>
</div>

<?php /* no longer needed
<div class="field">
    <label for="folder_structure" class="desc"><input  type="checkbox" name="folder_structure" id="folder_structure" checked="checked"  value="yes"/> <?php echo t('create_folder_structure'); ?></label>
</div>
*/?>
    
<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" />
	<?php echo anchor('admin/catalog/edit/'.$this->uri->segment(4).'/resources',t('cancel') );	?>
</div>
<?php echo form_close();?>
</div>