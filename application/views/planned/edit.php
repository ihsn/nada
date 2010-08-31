<style>
.field-expanded{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;}
.field-expanded .field{padding:5px;}
.field-expanded legend, .field-collapsed legend{background:white;padding-left:5px;padding-right:5px;}
.field-expanded legend, .field-collapsed legend{font-weight:bold; cursor:pointer}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed legend {background-image:url(images/next.gif); background-position:left top; padding-left:20px;background-repeat:no-repeat;}
.field-collapsed .field{display:none;}
.field-expanded .field label{font-weight:normal;}
.page-inline-links {text-align:right;}
.page-inline-links a{text-decoration:none;}
.page-inline-links a:hover{color:red;}
.inline-fields{width:100%;border:0px solid blue;}
.inline-fields .field{width:100%;}
.inline-fields td{padding:5px;}
.inline-fields .input-flex{}
#citation-preview{padding:5px;}
#citation-preview .citation-title{font-weight:bold;text-decoration:underline;}
#citation-preview .citation-subtitle{ font-style:italic}
#citation-preview {background-color:#F0F0F0;border:1px solid gainsboro;display:none;}
.input-flex .input-fixed-long{width:300px;}
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

<h1 class="page-title"><?php echo $form_title; ?></h1>

<?php echo form_open_multipart(current_url(), array('class'=>'form') ); ?>
<input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>




<table border="0" class="inline-fields" width="100%" cellspacing="5" cellspacing="5" >
	<tr>
<td>
<div class="field">
    <label for="country">Select Country <span class="required">*</span></label>
    <?php echo form_dropdown('country', array(0=>'--SELECT--',1=>'TODO'), get_form_value("country",isset($country) ? $country : ''),'style="width:96%"'); ?>
</div>
</td>

<td>
<div class="field">
	<label for="title">Title <span class="required">*</span></label>
	<input name="title" type="text" id="title" class="input-flex" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>
</td>
<td>
<div class="field">
	<label for="abbreviation">Study abbreviation</label>
	<input name="abbreviation" type="text" id="abbreviation" class="input-flex" value="<?php echo get_form_value('abbreviation',isset($abbreviation) ? $abbreviation : ''); ?>"/>
</div>
</td>
<td>
<div class="field" >
    <label for="studytype">Study type (a drop down??)</label>        
    <input name="studytype" type="text" id="studytype" class="input-flex" value="<?php echo get_form_value('studytype',isset($studytype) ? $studytype : ''); ?>"/>
</div>
</td>
</tr>
</table>

<div class="field" >
    <label for="geocoverage">Geographic coverage</label>        
    <input name="geocoverage" type="text" id="geocoverage" class="input-flex" value="<?php echo get_form_value('geocoverage',isset($geocoverage) ? $geocoverage : ''); ?>"/>
</div>


<div class="field" >
    <label for="scope">Description of scope</label>        
    <textarea name="scope" cols="50" rows="4" id="scope" class="input-flex" ><?php echo get_form_value('scope',isset($scope) ? $scope: ''); ?></textarea>
</div>


<div class="field" >
    <label for="pinvestigator">Primary investigator</label>        
    <input name="studytype" type="text" id="studytype" class="input-flex" value="<?php echo get_form_value('studytype',isset($studytype) ? $studytype : ''); ?>"/>
</div>


<table border="0" class="inline-fields" width="100%" >
	<tr>
<td>
<div class="field" >
    <label for="producers">Other producer(s)</label>        
    <textarea name="producers" cols="50" rows="4" id="producers" class="input-flex" ><?php echo get_form_value('producers',isset($producers) ? $producers: ''); ?></textarea>
</div>
</td>
<td>
<div class="field" >
    <label for="sponsors">Sponsor(s)</label>        
    <textarea name="sponsors"  cols="50" rows="4" id="sponsors" class="input-flex" ><?php echo get_form_value('sponsors',isset($sponsors) ? $sponsors: ''); ?></textarea>
</div>
</td>
</tr>
</table>

<table border="0" class="inline-fields">
	<tr>
<td>
<div class="field" >
    <label for="fundingstatus">Funding status (dropdown)</label>        
    <input name="fundingstatus" type="text" id="fundingstatus" class="input-flex" value="<?php echo get_form_value('fundingstatus',isset($fundingstatus) ? $fundingstatus : ''); ?>"/>
</div>


</td>
        <td>	
<div class="field" >
    <label for="samplesize">Sampling size</label>        
    <input name="samplesize" type="text" id="samplesize" class="input-flex" value="<?php echo get_form_value('samplesize',isset($samplesize) ? $samplesize : ''); ?>"/>
</div>
        </td>
        <td>	
<div class="field" >
    <label for="sampleunit">Sampling unit</label>        
    <input name="sampleunit" type="text" id="sampleunit" class="input-flex" value="<?php echo get_form_value('sampleunit',isset($sampleunit) ? $sampleunit : ''); ?>"/>
</div>
        </td>
        <td>	
<div class="field" >
    <label for="datacollstart">Data collection start</label>        
    <input type="text" name="datacollstart" id="datacollstart" class="input-flex" value="<?php echo get_form_value('datacollstart',isset($datacollstart) ? $datacollstart: ''); ?>"/>
</div>
        </td>
        <td>
<div class="field" >
    <label for="datacollend">Data collection end</label>        
    <input type="text" name="datacollend" id="datacollend" class="input-flex" value="<?php echo get_form_value('datacollend',isset($datacollend) ? $datacollend: ''); ?>"/>
</div>
        </td>
	</tr>
    
</table>    

<table border="0" class="inline-fields">
	<tr>
<td>
<div class="field" >
    <label for="expect_rep_date">Expected report date</label>        
    <input type="text" name="expect_rep_date" id="expect_rep_date" class="input-flex" value="<?php echo get_form_value('expect_rep_date',isset($expect_rep_date) ? $expect_rep_date: ''); ?>"/>
</div>
</td>
<td>
<div class="field" >
    <label for="expect_micro_rel_date">Expected microdata release date</label>        
    <input type="text" name="expect_micro_rel_date" id="expect_micro_rel_date" class="input-flex" value="<?php echo get_form_value('expect_micro_rel_date',isset($expect_micro_rel_date) ? $expect_micro_rel_date: ''); ?>"/>
</div>

</td>
</tr>
</table>
<div class="field" >
    <label for="expect_date_policy">Expected data access policy</label>        
    <textarea name="expect_date_policy" cols="50" rows="4" id="expect_date_policy" class="input-flex" ><?php echo get_form_value('expect_date_policy',isset($expect_date_policy) ? $expect_date_policy: ''); ?></textarea>
</div>


<div class="field" >
    <label for="notes">Notes</label>        
    <textarea name="notes" cols="50" rows="4" id="notes" class="input-flex" ><?php echo get_form_value('notes',isset($notes) ? $notes: ''); ?></textarea>
</div>

<div class="field">
	<input type="submit" name="submit" id="submit" value="<?php echo ('Submit'); ?>" />
	<?php echo anchor('admin/citations/', 'Cancel', array('class'=>'button'));?>
</div>
<?php echo form_close();?>
</div>