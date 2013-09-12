<style>
.field{margin-top:10px;}
.single-study{font-weight:normal;}
.by-collection {font-size:12px;display:none;}
/*.study-scroll{height:200px;overflow:auto;}*/
.collapsible {
border: 1px solid #888B8D;
background: #F3F3F3;
}
.study-set {padding:10px;}
.study-set table td{background:white;}
.study-set table {margin-bottom:10px;}
.header td{font-weight:bold;background:none;font-size:smaller;}
.collection-fieldset{
	border-top:1px solid gainsboro;
	margin:7px 18% 0 18%;
}
.collection-fieldset legend{
text-align: center;
color: gray;
padding: 5px;
font-size:10px;
text-transform:uppercase;
}

.set-header{background:#888B8D;color:white;padding:5px;cursor:pointer;}
.set-header label,
.select-all,
.clear-all{cursor:pointer;}
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

<?php
$ds=get_form_value('ds',isset($ds) ? $ds: 'study');
$selected_surveys=isset($_POST['sid']) ? (array)$_POST['sid'] : array();
?>

<div class="data-request-form-container">

<div style="text-align:right">
	<a <?php echo ($this->input->get("ajax") ? 'target="_blank"' : '') ;?>href="<?php echo site_url();?>/auth/profile/" class="button-light"><?php echo t('view_all_requests');?></a> 
    <?php if ($this->input->get("ajax")):?>
    <a target="_blank" href="<?php echo site_url().$this->uri->uri_string();?>" class="button-light"><?php echo t('open_in_new_window');?></a>
    <?php endif;?>
</div>


<h2 class="page-title"><?php echo t('application_access_licensed_dataset');?></h2>
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


<form style="padding:0px;margin:0px" name="orderform" id="orderform" method="post" >

	<input type="hidden" name="surveytitle" value="<?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?>" />
	<input type="hidden" name="surveyid" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?>" />
	<input type="hidden" name="survey_uid" value="<?php echo get_form_value('survey_uid',isset($survey_uid) ? $survey_uid : ''); ?>" />
    
    <?php if (isset($this->ajax)):?>
    	<input type="hidden" name="ajax" value="1" />
    <?php endif;?>
  <table class="grid-table" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
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

    <?php if ($bulk_access==TRUE && isset($collections)):?>
    
    <tr>
    	<td colspan="2">
        		<div class="field-caption">
                    <span class="required">*</span> <?php echo t('Select dataset(s)');?>
                </div>
        		<div class="field single-study collapsible">
                	<div class="set-header">
                    <input type="radio" name="ds" value="study"  id="access_type_study" class="access_type" <?php echo (($ds=='study') ? 'checked="checked"' : ''); ?> /> 
                    <label for="access_type_study">Request data for this study only:
					<?php foreach($surveys as $survey):?>
                		[#<?php echo $survey['id'];?>] <?php echo $survey['nation'];?> - <?php echo $survey['titl']. ' '.$survey['data_coll_start'];?>
                        <input type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" checked="checked" style="display:none;"/>
                	<?php endforeach;?>
                    </label>
                    </div>
                </div>
                
				<?php foreach($collections as $collection):?>   
                	<fieldset class="collection-fieldset">
                    <legend><?php echo t('or');?></legend>
                    </fieldset>
                    <div class="field collection-container collapsible">
                    <div class="set-header">
                	<input type="radio" name="ds" value="<?php echo $collection['cid'];?>" data-cid="<?php echo $collection['cid'];?> " id="da-coll-<?php echo $collection['cid'];?>"  class="access_type" <?php echo (($ds==$collection['cid']) ? 'checked="checked"' : ''); ?>/> 
                    <label for="da-coll-<?php echo $collection['cid'];?>">Request access to data for <b><?php echo count($collection['studies']);?> studies</b> in the collection <b><?php echo $collection['title'];?></b></label>
                    </div>
                    
                    <div class="by-collection <?php echo (count($collection['studies'])>10 ? 'study-scroll' : '');?>">
                    <div class="study-set">
                    <?php if (isset($collection['description'])):?>
                    <p class="about-set"><?php echo $collection['description'];?></p>
                    <?php endif;?>
                    
                    <table class="grid-table  studies-<?php echo $collection['cid'];?>">
                    <tr class="header">
                        <td colspan="2"><span class="select-all">Select all</span> | <span class="clear-all">Clear</span></td>
                    </tr>
					<?php $k=1;foreach($collection['studies'] as $survey):?>
                    <tr class="study-row">
                        <td><?php //echo $k++;?><input type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" <?php if (in_array($survey['id'],$selected_surveys)){ echo 'checked="checked"';} ?>/></td>
                        <td><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?> <?php echo $survey['data_coll_start'];?></td>
                    </tr>
                    <?php endforeach;?>
                    </table>
                    </div>
                    </div>
                    
                    </div>
                    
                <?php endforeach;?>
        </td>
    </tr>
    <?php else:?>
        <tr class="border" >
          <td valign="top"><?php echo t('dataset_requested');?></td>
          <td><div style="color:maroon;font-size:12px;">
		  		<?php foreach($surveys as $survey):?>
                	<a target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>">[#<?php echo $survey['id'];?>] <?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a>
                <?php endforeach;?>
              </div>
          </td>
        </tr>
    <?php endif;?>


    <tr>
    <td class="border" colspan="2"><?php echo t('filled_lead_research');?></td>
    </tr>
    <tr class="border">
      <td class="no-wrap">
      <span class="field-caption">
      	<span class="required">*</span> <?php echo t('receiving_organization_name');?>
      </span>
      </td>
      <td>
      <input type="text" id="org_rec" name="org_rec"   value="<?php echo get_form_value('org_rec',isset($org_rec) ? $org_rec: ''); ?>" style="width:200px" maxlength="100" />
      <?php //echo t('rec_org_refers');?>
      </td>      
    </tr>
    <?php /* ?>
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
	<?php */ ?>
  <tr class="border" >
    <td  class="no-wrap"><span class="field-caption"><span class="required">*</span> <?php print t('telephone');?></span></td>
    <td><input type="text" id="tel" name="tel"   value="<?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <?php /*?>
  <tr class="border">
    <td><span class="field-caption"><?php print t('fax');?></span></td>
    <td><input type="text" id="fax" name="fax"   value="<?php echo get_form_value('fax',isset($fax) ? $fax : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <?php */ ?>
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
 <?php /* ?>
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
  <?php  */ ?>
  <tr class="border">
    <td colspan="2"><span class="field-caption"><?php print t('research_team');?></span><br />
      <br />
      <?php print t('provide_names');?><br/>
    <textarea id="team" name="team" style="width:98%" rows="10"><?php echo get_form_value('team',isset($team) ? $team : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div class="field-caption"><?php print t('ident_needed');?></div>
	<p><?php print t('da_website');?></p>

	<span class="field-caption"><?php echo t('this_request');?>
    	<span class="required">*</span>
    </span> <br/>
	
    <p>
    <input type="radio" name="dataset_access" id="access_whole" value="whole" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='whole' ? 'checked="checked"' : ''; ?> />		
    <label for="access_whole"><?php print t('whole_dataset');?></label><br/>
    
    <input type="radio" name="dataset_access" id="access_subset" value="subset" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='subset' ? 'checked="checked"' : ''; ?>/>
    <label for="access_subset"><?php print t('subset_data');?></label>
    </p>
    
    </td>
    
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

</div>

<script type="text/javascript">
	function isagree(){
		$("#submit").prop('disabled', !$("#chk_agree").prop("checked"))	
	}
	
$(document).ready(function() 
{	
	$(".access_type").click(function() {
		$(".by-collection").hide();
		$(this).closest(".collection-container").find(".by-collection").show();
		$(".collapsible :checkbox").attr("disabled",true);//disable all checkboxes
		$(this).closest(".collapsible").find(":checkbox").attr("disabled",false);//enable checkboxes only for the current/active box
		
	});
	
	$(".select-all").click(function() {
		$(this).closest("table").find(":checkbox").prop('checked',true);
	});
	
	$(".clear-all").click(function() {
		$(this).closest("table").find(":checkbox").prop('checked',false);
	});

});		
</script>