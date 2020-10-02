<style>
.field-expanded,.always-visible{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;}
.always-visible{padding:10px;}
.field-expanded legend, .field-collapsed legend, .always-visible legend{background:white;padding-left:5px;padding-right:5px;font-weight:bold; cursor:pointer}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed legend {padding-left:20px;background-repeat:no-repeat;}
.field-collapsed .form-group{display:none;}
.form{ max-width:800px;}
</style>

<?php
$select_[0]=t('__select__');
$option_types = array_merge($select_,$this->Resource_model->get_dc_types());
$option_formats=array_merge($select_,$this->Resource_model->get_dc_formats());

//translate types
foreach($option_types as $key=>$value)
{
	$option_types[$key]=t($value);
}

//translate formats
foreach($option_formats as $key=>$value)
{
	$option_formats[$key]=t($value);
}
?>

<div class="container-fluid page-resources-edit">
<div class="page-links">
	<?php echo anchor('admin/catalog/edit/'.$this->uri->segment(5).'/resources',t('link_resource_home'),array('class'=>'button') );	?>	
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
<div class="form-group">
	<label for="dctype"><?php echo t('type');?></label>
    <?php echo form_dropdown('dctype', $option_types, get_form_value("dctype",isset($dctype) ? $dctype : ''),'class="form-control"'); ?>
</div>

<div class="form-group">
<label for="title"><?php echo t('title');?></label>
<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<div class="form-group">
	<label for="description"><?php echo t('description');?></label>
	<textarea name="description" cols="50" rows="4" id="description" class="form-control" ><?php echo get_form_value('description',isset($description) ? $description : ''); ?></textarea>
</div>

<div class="form-group">
	<label for="dcformat"><?php echo t('format');?></label>
	<?php echo form_dropdown('dcformat', $option_formats, get_form_value("dcformat",isset($dcformat) ? $dcformat : ''),'class="form-control"'); ?>
</div>

<div class="form-group">
	<label for="url"><?php echo t('resource_url_filepath');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($filename) ? $filename : ''); ?>"/>
</div>

<fieldset class="field-expanded">
<legend><?php echo t('additional_info');?></legend>

<div class="form-group">
<label for="author"><?php echo t('authors');?></label>
<input name="author" type="text" id="author" class="form-control" value="<?php echo get_form_value('author',isset($author) ? $author: ''); ?>"/>
</div>

<div class="form-group">
<label for="dcdate"><?php echo t('date');?></label>
<input name="dcdate" type="text" id="dcdate" size="50" class="form-control"  value="<?php echo get_form_value('dcdate',isset($dcdate) ? $dcdate: ''); ?>"/>
</div>

<div class="form-group">
<label for="country"><?php echo t('country');?></label>
<input name="country" type="text" id="country" size="50" class="form-control"  value="<?php echo get_form_value('country',isset($country) ? $country : ''); ?>"/>
</div>

<div class="form-group">
<label for="language"><?php echo t('language');?></label>
<input name="language" type="text" id="language" size="50" class="form-control" value="<?php echo get_form_value('language',isset($language) ? $language : ''); ?>"/>
</div>


<div class="form-group">
	<label for="contributer"><?php echo t('contributors');?></label>
	<input name="contributor" type="text" id="contributor" class="form-control" value="<?php echo get_form_value('contributor',isset($contributor) ? $contributor : ''); ?>"/>
</div>

<div class="form-group">
	<label for="publisher"><?php echo t('publishers');?></label>
	<input name="publisher" type="text" id="publisher" class="form-control" value="<?php echo get_form_value('publisher',isset($publisher) ? $publisher : ''); ?>"/></td>
</div>


<div class="form-group">
	<label for="abstract"><?php echo t('abstract');?></label>
	<textarea name="abstract" cols="50" rows="4" id="abstract" class="form-control" ><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
</div>

<div class="form-group">
<label for="toc"><?php echo t('table_of_contents');?></label>
<textarea name="toc" cols="50" rows="6" id="toc" class="form-control"><?php echo get_form_value('toc',isset($toc) ? $toc : ''); ?></textarea>
</div>

</fieldset>

<div class="form-group">
	<input type="submit" name="submit" id="submit" value="<?php echo t('submit'); ?>" class="btn btn-primary" />
	<a href="<?php echo site_url('admin/catalog/edit/'.$this->uri->segment(5).'/resources');?>" class="btn btn-link"><?php echo t('cancel');?></a>
</div>
<?php echo form_close();?>
</div>

<script type="text/javascript">
function toggle_file_url(field_show,field_hide){
	$('#'+field_show).show();
	$('#'+field_hide).hide();
}

//auto complete
/*$(document).ready(function(){
    var data = "<?php echo $this->js_files;?>".split(" ");
	$("#url").autocomplete(data);
});*/

//expand/collapse
$(document).ready(function() {
	$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');
	
	$('.field-expanded > legend').click(function(e) {
			e.preventDefault();
			$(this).parent('fieldset').toggleClass("field-collapsed");
			return false;
	});
});
</script>