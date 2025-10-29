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
$option_types = array_merge($select_,$this->Survey_resource_model->get_dc_types());
$option_formats=array_merge($select_,$this->Survey_resource_model->get_dc_formats());

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
	<?php 
	// Flexible back link - works for both Resources and Managefiles controllers
	$back_url = isset($back_link) ? $back_link : 'admin/catalog/edit/'.$this->uri->segment(5).'/resources';
	$back_text = isset($back_text) ? $back_text : t('link_resource_home');
	echo anchor($back_url, $back_text, array('class'=>'button'));
	?>	
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
<input name="resource_id" type="hidden" id="resource_id" value="<?php echo get_form_value('resource_id',isset($resource_id) ? $resource_id: ''); ?>"/>
<div class="form-group">
	<label for="dctype"><?php echo t('type');?></label> 
    <?php echo form_dropdown('dctype', $option_types, get_form_value("dctype",isset($dctype) ? $dctype : ''),'class="form-control" id="dctype"'); ?>
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

<?php if (!isset($simple_mode) || !$simple_mode): ?>
<div class="form-group">
	<label><?php echo t('resource_url_filepath');?></label>
	
	<div style="margin-bottom:10px;">
		<label class="radio-inline" style="margin-right:20px;">
			<input type="radio" name="resource_type" value="url" id="radio_url" checked onclick="toggle_file_url('url_field','file_field')"> 
			<?php echo t('url');?>
		</label>
		<label class="radio-inline">
			<input type="radio" name="resource_type" value="file" id="radio_file" onclick="toggle_file_url('file_field','url_field')"> 
			<?php echo t('file');?>
		</label>
	</div>
	
	<div id="url_field">
		<input name="url" type="text" id="url" class="form-control" placeholder="https://example.com/document.pdf" value="<?php echo get_form_value('url',isset($filename) ? $filename : ''); ?>"/>		
	</div>
	
	<div id="file_field" style="display:none;">
		<input name="url_file" type="text" id="url_file" class="form-control" list="file_list" placeholder="<?php echo t('select_or_type_filename');?>" value="<?php echo get_form_value('url',isset($filename) ? $filename : ''); ?>"/>
		<datalist id="file_list">
			<?php if(isset($this->js_files) && !empty($this->js_files)): ?>
				<?php 
				$files = explode(' ', $this->js_files);
				foreach($files as $file): 
					$file = trim($file);
					if(!empty($file)):
				?>
					<option value="<?php echo htmlspecialchars($file); ?>">
				<?php 
					endif;
				endforeach; 
				?>
			<?php endif; ?>
		</datalist>		
	</div>
</div>
<?php else: ?>
<div class="form-group">
	<label for="filename"><?php echo t('resource_url_filepath');?></label>
	<input name="filename" type="text" id="filename" class="form-control" value="<?php echo get_form_value('filename',isset($filename) ? $filename : ''); ?>"/>
</div>
<?php endif; ?>

<fieldset class="field-expanded">
<legend><?php echo t('additional_info');?></legend>

<div class="form-group">
	<label for="resource_idno"><?php echo t('resource_identifier');?></label>
	<input name="resource_idno" type="text" id="resource_idno" class="form-control" maxlength="100" pattern="[a-zA-Z0-9_\-]+" value="<?php echo get_form_value('resource_idno',isset($resource_idno) ? $resource_idno : ''); ?>"/>
	<small class="form-text text-muted"><?php echo t('resource_idno_help');?></small>
</div>

<?php /* DISABLED: Data file selection field
<div class="form-group data-file-field" style="display:none;">
	<label for="data_file_id"><?php echo t('data_file');?></label>
	<?php 
		$data_files_list = isset($data_files) && is_array($data_files) ? $data_files : array('' => t('__select__'));
	?>
	<?php echo form_dropdown('data_file_id', $data_files_list, get_form_value("data_file_id",isset($data_file_id) ? $data_file_id : ''),'class="form-control" id="data_file_id"'); ?>
	<small class="form-text text-muted"><?php echo t('data_file_help');?></small>
</div>
*/ ?>

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
	<?php 
	$cancel_url = isset($back_link) ? $back_link : 'admin/catalog/edit/'.$this->uri->segment(5).'/resources';
	?>
	<a href="<?php echo site_url($cancel_url);?>" class="btn btn-link"><?php echo t('cancel');?></a>
</div>
<?php echo form_close();?>
</div>

<script type="text/javascript">
function toggle_file_url(field_show,field_hide){
	$('#'+field_show).show();
	$('#'+field_hide).hide();
}

/* DISABLED: Data file field toggle
// Function to check if dctype is microdata and show/hide data file field
function toggle_data_file_field() {
	var dctype = $('#dctype').val();
	if (dctype && (dctype.indexOf('[dat/micro]') !== -1 || dctype.indexOf('[dat]') !== -1)) {
		$('.data-file-field').show();
	} else {
		$('.data-file-field').hide();
	}
}
*/

$(document).ready(function(){
	//sync the two fields on form submit
	$('form').on('submit', function() {
		if ($('#radio_file').is(':checked')) {
			$('#url').val($('#url_file').val());
		}
	});
	
	//detect if editing existing resource and set appropriate radio button
	var existingValue = $('#url').val();
	if (existingValue) {
		//check if it's a URL or file path
		if (existingValue.indexOf('http://') === 0 || existingValue.indexOf('https://') === 0 || existingValue.indexOf('ftp://') === 0) {
			$('#radio_url').prop('checked', true);
			$('#url_field').show();
			$('#file_field').hide();
		} else {
			//it's a file path
			$('#radio_file').prop('checked', true);
			$('#url_file').val(existingValue);
			$('#url_field').hide();
			$('#file_field').show();
		}
	}
	
	/* DISABLED: Data file field toggle
	//show/hide data file field based on resource type
	toggle_data_file_field();
	
	//listen for changes to dctype dropdown
	$('#dctype').on('change', function() {
		toggle_data_file_field();
	});
	*/
	
	//expand/collapse additional info
	$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');
	
	$('.field-expanded > legend').click(function(e) {
		e.preventDefault();
		$(this).parent('fieldset').toggleClass("field-collapsed");
		return false;
	});
});
</script>