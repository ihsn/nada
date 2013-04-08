<style>
.form .field-inline label{font-weight:normal;}
</style>

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

<h1 class="page-title"><?php echo t('Generate Study PDF');?></h1>

<?php if($varcount>2000):?>
<div class="alert alert-warning" style="color:black;"><?php echo sprintf(t('study_contains_too_many_variables'),$varcount);?></div>
<?php endif;?>

<form method="post" class="form">
<div class="field">
    <label for="website_title"><?php echo t('website_title');?></label>
    <input name="website_title" type="text" id="website_title" size="50" class="input-flex"  value="<?php echo get_form_value('website_title',isset($website_title) ? $website_title : ''); ?>"/>
</div>


<div class="field">
    <label for="study_title"><?php echo t('study_title');?></label>
    <input name="study_title" type="text" id="study_title" size="50" class="input-flex"  value="<?php echo get_form_value('study_title',isset($study_title) ? $study_title : ''); ?>"/>
</div>


<div class="field">
    <label for="publisher"><?php echo t('publisher');?></label>
    <input name="publisher" type="text" id="publisher" size="50" class="input-flex"  value="<?php echo get_form_value('publisher',isset($publisher) ? $publisher : ''); ?>"/>
</div>

<div class="field">
    <label for="website_url"><?php echo t('website_url');?></label>
    <input name="website_url" type="text" id="website_url" size="50" class="input-flex"  value="<?php echo get_form_value('website_url',isset($website_url) ? $website_url : ''); ?>"/>
</div>

<div class="field-group">
	<label style="padding-bottom:10px;display:block;"><strong><?php echo t('Report options');?></strong></label>
<div class="field-inline">
    <input name="toc_variable" id="toc_variable" type="checkbox" value="1" <?php echo ($this->input->post("toc_variable")==1 ? 'checked="checked"' : '');?> />
    <label for="toc_variable"><?php echo t('include_variable_toc');?> (<?php echo (int)$varcount;?>)</label>
</div>

<div class="field-inline">
    <input name="data_dic_desc"  id="data_dic_desc"  type="checkbox" value="1"  <?php echo ($this->input->post("data_dic_desc")==1 ? 'checked="checked"' : '');?> />
    <label for="data_dic_desc"><?php echo t('include_variable_desc');?></label>    
</div>

<div class="field-inline">
    <input name="ext_resources" type="checkbox" value="1"  id="ext_resources"  <?php echo ($this->input->post("ext_resources")==1 ? 'checked="checked"' : '');?> />
    <label for="ext_resources"><?php echo t('include_external_resources');?></label>    
</div>

<div class="field-inline">
	<input type="submit" name="submit" id="submit" value="<?php echo t('Generate PDF'); ?>" />
	<?php echo anchor('admin/catalog/edit/'.$id, t('cancel'));?>
</div>

</div>

</form>



</div>
