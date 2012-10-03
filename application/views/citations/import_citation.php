<?php
$citation_formats = array(
                  'bibtex'  => 'BibTex',
                  'endnote_bibix'    => 'EndNote (Refer/BibIX)',
                  'endnote_ris'   => 'EndNote (RIS)',
				  'nada_serialized'   => 'NADA Serialized Array',
                 // 'endnote_xml' => 'EndNote (XML)',
                );

$publish_options=array(
	'1'=>t('option_publish'),
	'0'=>t('option_do_not_publish')
	);

$flag_options=array(
	''=>'--',
	'ds_unclear'=>t('ds_unclear'),
	'incomplete'=>t('incomplete'),
	'tobe_checked'=>t('tobe_checked'),
	'duplicate'=>t('duplicate'),
	'back_to_editor'=>t('back_to_editor'),
	);

?>
<script>
	//attach related surveys
	$(function() {
		
		//temp session id
		var tmp_id=$("#tmp_id").val();
		
		//add attached surveys to session, needed when editing a citations with survey attached
		var url_add=CI.base_url+'/admin/related_surveys/add/'+tmp_id+'/'+'<?php echo implode(",",$selected_surveys_id_arr);?>/1';
		$.get(url_add);
		
		//attach survey dialog
		$('.add_survey').click(function() {
				var iframe_url=CI.base_url+'/admin/related_surveys/index/'+tmp_id;
				$('<div id="dialog-modal" title="Select Related Surveys"></div>').dialog({ 
					height: 440,
					width: 700,
					resizable: false,
					draggable: false,
					modal: true,
					close: function() {
						$.get(CI.base_url+'/admin/citations/selected_surveys/'+tmp_id, function(data) {
							$('#related-surveys').html(data);
							related_surveys_click();
						});
					}
				}).append('<iframe height="395" width="654" src="'+iframe_url+'" frameborder="0"></iframe>');
		});
		
		related_surveys_click();
	});

	//attach click event handler for survey select/unselect
	function related_surveys_click()
	{
			$('.chk').unbind('click').click(function(e) {
			
			var tmp_id=$("#tmp_id").val();
			
			if($(this).is(':checked')) {
            	url=CI.base_url+'/admin/related_surveys/add/'+tmp_id+'/'+$(this).val()+'/1';
         	}
			else{
				url=CI.base_url+'/admin/related_surveys/remove/'+tmp_id+'/'+$(this).val()+'/1';
			}
			
			$.get(url);
		});
	}
	
</script>
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

<?php echo form_open_multipart(site_url().'/admin/citations/import/', array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>
<input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: 'cit-'.date("U")); ?>"/>
<div class="field">
	<label for="citation_format"><?php echo t('citation_import_format');?></label>
	<?php echo form_dropdown('citation_format', $citation_formats);?>
</div>

<div class="field">
	<label for="citation_string"><?php echo t('paste_citation_string');?></label>
	<textarea rows="10" name="citation_string" id="citation_string" class="input-flex"><?php echo get_form_value('citation_string',isset($citation_string) ? $citation_string : ''); ?></textarea>
</div>

<div class="field">
    <label for="publish"><?php echo t('publish_citation');?></label>
    <?php echo form_dropdown('published', $publish_options, get_form_value("published",isset($published) ? $published : ''),'id="published"'); ?>
</div>

<div class="field">
    <label for="flag"><?php echo t('flag_entry_as');?></label>
    <?php echo form_dropdown('flag', $flag_options, get_form_value("flag",isset($flag) ? $flag : ''),'id="flag"'); ?>
</div>

<fieldset class="field-expanded">
	<legend><?php echo t('related_studies');?></legend>
<div class="field">
    <div id="related-surveys" style="height:200px;overflow:scroll;overflow-x: hidden;border:1px solid gainsboro;padding:5px;margin-bottom:5px;">    	
			<?php echo $survey_list; ?>
    </div> 
	<a style="display:block" class="add_survey" href="javascript:void(0);">Add Surveys</a>   
</div>
</fieldset>
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