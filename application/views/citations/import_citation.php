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

<div class="field">
    <label for="survey"><?php echo t('attach_to_survey');?></label>
    <?php echo form_multiselect('survey[]', $surveys, get_form_value("survey",isset($survey) ? $survey : ''),'id="survey"'); ?>
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