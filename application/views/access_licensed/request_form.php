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
<div style="font-size:12px;color:red;"><?php echo t('required_fields');?></div>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<form style="padding:0px;margin:0px" name="orderform" id="orderform" method="post" class="lic-request-form">

	<input type="hidden" name="surveytitle" value="<?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?>" />
	<input type="hidden" name="surveyid" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?>" />
	<input type="hidden" name="survey_uid" value="<?php echo get_form_value('survey_uid',isset($survey_uid) ? $survey_uid : ''); ?>" />
    
    <?php if (isset($this->ajax)):?>
    	<input type="hidden" name="ajax" value="1" />
    <?php endif;?>
  
    <div><?php echo t('info_kept_confidential');?> </div>

  <table class="table table-bordered  table-striped grid-table" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
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
	</table>

	
    <table class="table table-bordered table-striped grid-table" border="0" width="100%" style="margin-top:15px;border-collapse:collapse;border:1px solid gainsboro;">
    <?php if ($bulk_access==TRUE && isset($collections)):?>
    
    <tr>
    	<td colspan="2">
        		<div class="field-caption">
                    <span class="required">*</span> <?php echo t('Select dataset(s)');?>
                </div>
        		<div class="field single-study collapsible">
                	<div class="set-header">
                    <input type="radio" name="ds" value="study"  id="access_type_study" class="access_type" <?php echo (($ds=='study') ? 'checked="checked"' : ''); ?> /> 
                    <label for="access_type_study">Request access to data for this study only:
					          <?php foreach($surveys as $survey):?>
                		  <b><?php echo $survey['nation'];?> - <?php echo $survey['title']. ' '.$survey['year_start'];?></b>
                        <input type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" checked="checked" style="display:none;"/>
                	  <?php endforeach;?>
                    </label>
                    </div>
                </div>
                
				        <?php foreach($collections as $collection):?>   
                    <div class="field collection-container collapsible">
                    <div class="set-header">
                	<input type="radio" name="ds" value="<?php echo $collection['cid'];?>" data-cid="<?php echo $collection['cid'];?> " id="da-coll-<?php echo $collection['cid'];?>"  class="access_type" <?php echo (($ds==$collection['cid']) ? 'checked="checked"' : ''); ?>/> 
                    <label for="da-coll-<?php echo $collection['cid'];?>">Request access to data for <b><?php echo count($collection['studies']);?> studies</b> in the collection <b><?php echo $collection['title'];?></b></label>
                    </div>
                    
                    <div class="by-collection <?php echo (count($collection['studies'])>10 ? 'study-scroll' : '');?>">
                    <div class="study-set">
                    <?php if (isset($collection['description'])):?>
                      <!--<p class="about-set"><?php echo $collection['description'];?></p>-->
                    <?php endif;?>
                    
                    <table class="table table-striped grid-table  studies-<?php echo $collection['cid'];?>">
                    <tr class="header">
                        <td colspan="2"><span class="btn btn-sm btn-link select-all">
                            <?php echo t('link_select_all');?></span> | 
                            <span class="btn btn-sm btn-link clear-all"><?php echo t('clear');?></span></td>
                    </tr>
					          <?php $k=1;foreach($collection['studies'] as $survey):?>
                    <tr class="study-row">
                        <td><?php //echo $k++;?><input type="checkbox" name="sid[]" value="<?php echo $survey['id'];?>" <?php if ($ds==$collection['cid'] && in_array($survey['id'],$selected_surveys)){ echo 'checked="checked"';} ?>/></td>
                        <td><?php echo $survey['nation'];?> - <?php echo $survey['title'];?> <?php echo $survey['year_start'];?></td>
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
                	<a class="survey-title" target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['title'];?> - <?php echo $survey['year_start']; ?></a>
                     <input type="hidden" name="sid[]" value="<?php echo $survey['id'];?>" />
                <?php endforeach;?>
              </div>
          </td>
        </tr>
    <?php endif;?>

	</table>
    
	<table class="table table-bordered table-striped grid-table" border="0" width="100%" style="margin-top:15px;border-collapse:collapse;border:1px solid gainsboro;">

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
      <input class="form-control" type="text" id="org_rec" name="org_rec"   value="<?php echo get_form_value('org_rec',isset($org_rec) ? $org_rec: ''); ?>"  maxlength="100" />
      <?php //echo t('rec_org_refers');?>
      </td>      
    </tr>   
  <tr class="border" >
    <td  class="no-wrap"><span class="field-caption"><span class="required">*</span> <?php print t('telephone');?></span></td>
    <td><input class="form-control" type="text" id="tel" name="tel"   value="<?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?>"  maxlength="100" /></td>
  </tr>  
  <tr class="border">
    <td colspan="2">
    	<div class="field-caption"><span class="required">*</span> <?php print t('intended_use');?></div>
        <div class="field-notes"> <?php print t('provide_short_desc');?> </div>
    <textarea id="datause" name="datause" class="form-control" rows="10"><?php echo get_form_value('datause',isset($datause) ? $datause : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div class="field-caption expected_output"><span class="required">*</span> <?php print t('expected_output');?></div> 
    <textarea id="outputs" name="outputs" class="form-control" rows="10"><?php echo get_form_value('outputs',isset($outputs) ? $outputs : ''); ?></textarea>     </td>
  </tr>
  <tr class="border">
    <td><span class="field-caption"><span class="required">*</span> <?php print t('expected_completion');?></span></td>
    <td><input class="form-control" class="form-control" type="text" id="compdate" name="compdate"   value="<?php echo get_form_value('compdate',isset($compdate) ? $compdate : ''); ?>"  maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td colspan="2">
    	<div class="field-caption"><span class="required">*</span> <?php print t('research_team');?></div>
      	<div class="field-notes"><?php print t('provide_names');?></div>
    <textarea id="team" name="team" class="form-control"  rows="10"><?php echo get_form_value('team',isset($team) ? $team : ''); ?></textarea></td>
  </tr>

  <?php if (isset($form_options['dataset_access']) && $form_options['dataset_access']!=false ):?>
  <tr class="border">
    <td colspan="2"><div class="field-caption"><?php print t('ident_needed');?></div>
	<p><?php print t('da_website');?></p>

	<p><?php echo t('this_request');?></p>
	
    <div class="form-group form-check">
      <input class="form-check-input-x" type="radio" name="dataset_access" id="access_whole" value="whole" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='whole' ? 'checked="checked"' : ''; ?> />		
      <label class="form-check-label" for="access_whole"><?php print t('whole_dataset');?></label>
    </div>

    <div class="form-group form-check">
      <input class="form-check-input-x" type="radio" name="dataset_access" id="access_subset" value="subset" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='subset' ? 'checked="checked"' : ''; ?>/>
      <label class="form-check-label" style="display:inline"  for="access_subset"><?php print t('subset_data');?></label>
    </div>
    </td>
  </tr>
  <?php endif;?>

  <tr class="border">
    <td colspan="2">
        <div>
          <div class="field-caption"><?php print t('data_access_agreement');?></div>          
          <div class="bull-list"><?php print t('agreement_text');?></div>
        </div></td>
  </tr>
    <tr class="border">
      <td colspan="2" class="note" align="right"><input type="checkbox" title="I Agree" id="chk_agree" name="chk_agree" />
        <label for="chk_agree"><?php echo t('i_read_and_agree');?></label>&nbsp;&nbsp;
        <input type="submit" class="btn btn-primary" value="<?php echo t('submit');?>" id="submit" name="submit"  >
      </td>
    </tr>
  </table>
</form>

</div>

<script type="text/javascript">
$(document).ready(function() 
{	
  $(".by-collection").hide();
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
	
	$("#chk_agree").click(function() {
		$("#submit").prop('disabled', !$("#chk_agree").prop("checked"))	
	});
	
	//disable submit button
	$("#submit").prop('disabled','disabled');
	
});		
</script>
