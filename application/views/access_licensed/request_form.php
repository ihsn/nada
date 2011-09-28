<?php
/**
* Form for collecting data for - Licensed Data Requests
*
*/
?>
<style>
.public-use td{border:1px solid gainsboro;padding:5px;}
.required{color:red;}
.field-caption{font-weight:bold;}
</style>

<?php
//options for the org_type
$options_org_type=array(
	t('Line ministry/public administration')=>t('Line ministry/public administration'),
	t('University')=>t('University'),
	t('Research centre')=>t('Research centre'),
	t('Private company')=>t('Private company'),
	t('International organization')=>t('International organization'),
	t('Non-governmental agency (national)')=>t('Non-governmental agency (national)'),
	t('Non-governmental agency (international)')=>t('Non-governmental agency (international)'),
	t('Other')=>t('Other')
	);	
	
$options_datamatching=array(
	0=>t('no'),
	1=>t('yes')
	);
?>

<div style="text-align:right">
	<a <?php echo ($this->input->get("ajax") ? 'target="_blank"' : '') ;?>href="<?php echo site_url();?>/auth/profile/" class="button-light"><?php echo t('view_all_requests');?></a> 
    <?php if ($this->input->get("ajax")):?>
    <a target="_blank" href="<?php echo site_url().$this->uri->uri_string();?>" class="button-light"><?php echo t('open_in_new_window');?></a>
    <?php endif;?>
</div>


<h1 class="page-title"><?php echo t('application_access_licensed_dataset');?></h1>
<div style="font-style:italic;color:red;"><?php echo t('required_fields');?></div>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<form style="padding:0px;margin:0px" name="orderform" id="orderform" method="post" action="<?php echo $_SERVER["PHP_SELF"] . '?'. $_SERVER["QUERY_STRING"] ?>">

	<input type="hidden" name="surveytitle" value="<?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?>" />
	<input type="hidden" name="surveyid" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?>" />
	<input type="hidden" name="survey_uid" value="<?php echo get_form_value('survey_uid',isset($survey_uid) ? $survey_uid : ''); ?>" />
    <?php if (isset($this->ajax)):?>
    	<input type="hidden" name="ajax" value="1" />
    <?php endif;?>
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
  	<tr>
    	<td colspan="2" class="note">
        <div><?php echo t('info_kept_confidential');?> </div>
        </td>
    </tr>
    <tr class="border">
      <td width="200px"><?php echo t('first_name');?></td>
      <td><?php echo get_form_value('fname',isset($fname) ? $fname : ''); ?></td>
    </tr>
    <tr class="border">
      <td><?php echo t('last_name');?></td>
      <td><?php echo get_form_value('lname',isset($lname) ? $lname: ''); ?></td>
    </tr>
    <tr  class="border">
      <td><?php echo t('organization');?></td>
      <td><?php echo get_form_value('organization',isset($organization) ? $organization : ''); ?></td>
    </tr>
    <tr class="border">
      <td><?php echo t('email');?></td>
      <td><?php echo get_form_value('email',isset($email) ? $email : ''); ?></td>
    </tr>
    <tr class="border">
      <td><?php echo t('dataset_requested');?></td>
      <td><div style="color:maroon;font-size:12px;"><?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?> - <?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?> <br/><?php echo get_form_value('proddate',isset($proddate) ? $proddate : ''); ?></div></td>
    </tr>
    <tr class="border">
      <td colspan="2">
      <?php echo t('filled_lead_research');?>
      <br />
      <span class="field-caption">
      	<span class="required">*</span> <?php echo t('receiving_organization_name');?>
      </span>
      <input type="text" id="org_rec" name="org_rec"   value="<?php echo get_form_value('org_rec',isset($org_rec) ? $org_rec: ''); ?>" style="width:200px" maxlength="100" />
      <br />
      <?php echo t('rec_org_refers');?></p>
      </td>      
    </tr>
    <tr class="border" valign="top">
    <td><span class="field-caption"><?php echo t('org_type');?></span></td>
    <td><?php echo form_dropdown('org_type', $options_org_type, get_form_value('org_type',isset($org_type) ? $org_type : ''));?>
      <br />
      <br />
		<span class="field-caption"><?php print t('other');?></span><br/>
		<input type="text" id="orgtype_other" name="orgtype_other"   value="<?php echo get_form_value('orgtype_other',isset($orgtype_other) ? $orgtype_other : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><span class="field-caption"><span class="required">*</span> <?php print t('post_add');?></span></td>
    <td><input type="text" id="address" name="address"  value="<?php echo get_form_value('address',isset($address) ? $address : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><span class="field-caption"><span class="required">*</span> <?php print t('telephone');?></span></td>
    <td><input type="text" id="tel" name="tel"   value="<?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><span class="field-caption"><?php print t('fax');?></span></td>
    <td><input type="text" id="fax" name="fax"   value="<?php echo get_form_value('fax',isset($fax) ? $fax : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div style="font-weight:bold;"><span class="required">*</span> <?php print t('intended_use');?><br />
          <br />
    </div>
      <div style="font-style:italic"> <?php print t('provide_short_desc');?> </div>
    <textarea id="datause" name="datause" style="width:98%" rows="10"><?php echo get_form_value('datause',isset($datause) ? $datause : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div style="font-weight:bold;"><?php print t('expected_output');?></div> 
    <textarea id="outputs" name="outputs" style="width:98%" rows="10"><?php echo get_form_value('outputs',isset($outputs) ? $outputs : ''); ?></textarea>     </td>
  </tr>
  <tr class="border">
    <td><span class="field-caption"><?php print t('expected_completion');?></span></td>
    <td><input type="text" id="compdate" name="compdate"   value="<?php echo get_form_value('compdate',isset($compdate) ? $compdate : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td colspan="2">
    	<div style="font-weight:bold;"><?php print t('data_matching');?></div>
        <?php print t('merge_dataset');?>    
        <?php echo form_dropdown('datamatching', $options_datamatching, isset($datamaching) ? $datamatching : '');?>
      </td>
  </tr>
  <tr class="border">
    <td colspan="2"><span class="field-caption"><?php print t('other_data_merge');?></span><br />
    <textarea id="mergedatasets" name="mergedatasets" style="width:98%" rows="10"><?php echo get_form_value('mergedatasets',isset($mergedatasets) ? $mergedatasets : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><span class="field-caption"><?php print t('research_team');?></span><br />
      <br />
      <?php print t('provide_names');?><br/>
    <textarea id="team" name="team" style="width:98%" rows="10"><?php echo get_form_value('team',isset($team) ? $team : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><span class="field-caption"><?php print t('ident_needed');?></span><br />
      <br/>
	<?php print t('da_website');?>
    <br/><br/>

	<span class="field-caption"><?php echo t('this_request');?><span class="required">*</span></span> <br/>
<input type="radio" name="dataset_access" id="access_whole" value="whole" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='whole' ? 'checked="checked"' : ''; ?> />
<label for="access_whole"><?php print t('whole_dataset');?></label><br/>
<input type="radio" name="dataset_access" id="access_subset" value="subset" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='subset' ? 'checked="checked"' : ''; ?>/>
<label for="access_subset"><?php print t('subset_data');?></label></td>
  </tr>
  <tr class="border">
    <td colspan="2">
        <div>
          <div style="margin-top:5px;font-weight:bold;"><?php print t('data_access_agreement');?></div>          
          <div class="bull-list"><?php print t('agreement_text');?></div>
        </div></td>
  </tr>
    <tr class="border">
      <td colspan="2" class="note" align="right"><input type="checkbox" title="I Agree" id="chk_agree" name="chk_agree" onClick="isagree()"/>
        <label for="chk_agree"><?php echo t('i_read_and_agree');?></label>&nbsp;&nbsp;
        <input type="submit" disabled="disabled" value="<?php echo t('submit');?>" id="submit" name="submit"  onClick="submitform()">
      </td>
    </tr>
  </table>
</form>
<script type="text/javascript">
	function isagree(){
		$("#submit").attr('disabled', !$("#chk_agree").attr("checked"))	
	}
</script>