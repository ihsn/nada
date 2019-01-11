<?php
$report_lang=array(
		'en'	=> 'English',
		'es'	=> 'Spanish',
		'fr'	=> 'French',
		'ru'	=> 'Russian',
		'zh-CN'	=> 'Chinese (华语)',
		'ar'	=> 'Arabic',
		'mn'	=> 'Mongolian',
        'pt'    => 'Portuguese'
	);
?>
<script>
$(document).ready(function () { 
		$("#submit").click(function(e){
			$(".action-submit").hide();
			$(".processing").show();
		});
});		
</script>
<style>
.form .field-inline label{font-weight:normal;}
.processing{display:none;background:gainsboro;padding:15px;border:1px solid gainsboro;font-size:18px;}
</style>

<div class="container-fluid">

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
<div class="form-group">
    <label for="website_title"><?php echo t('website_title');?></label>
    <input name="website_title" type="text" id="website_title" size="50" class="form-control"  value="<?php echo get_form_value('website_title',isset($website_title) ? $website_title : ''); ?>"/>
</div>


<div class="form-group">
    <label for="study_title"><?php echo t('study_title');?></label>
    <input name="study_title" type="text" id="study_title" size="50" class="form-control"  value="<?php echo get_form_value('study_title',isset($study_title) ? $study_title : ''); ?>"/>
</div>


<div class="form-group">
    <label for="publisher"><?php echo t('publisher');?></label>
    <input name="publisher" type="text" id="publisher" size="50" class="form-control"  value="<?php echo get_form_value('publisher',isset($publisher) ? $publisher : ''); ?>"/>
</div>

<div class="form-group">
    <label for="website_url"><?php echo t('website_url');?></label>
    <input name="website_url" type="text" id="website_url" size="50" class="form-control"  value="<?php echo get_form_value('website_url',isset($website_url) ? $website_url : ''); ?>"/>
</div>

<div class="form-group">
    <label for="report_lang"><?php echo t('report_lang');?></label>
    <?php echo form_dropdown('report_lang', get_form_value('report_lang',isset($report_lang) ? $report_lang : '') );?>
</div> 

<div class="form-group">
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

<div class="field-inline action-submit" style="margin-top:15px;">
	<input class="btn btn-primary" type="submit" name="submit" id="submit" value="<?php echo t('generate_pdf'); ?>" />
	<?php echo anchor('admin/catalog/edit/'.$id, t('cancel'));?>
</div>

<div class="processing">
<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> <?php echo t('processing_pdf_report');?>
</div>

</div>

</form>

</div>

