<?php
/**
* licensed request form print view
*
*/
?>
<style>
.public-use td{border:1px solid gainsboro;padding:5px;}
.required{color:red;}
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
	0=>'No',
	1=>'Yes'
	);
?>

<p>
    <p><?php echo t('dear').' '. $fname. ' '. $lname; ?>,</p>
    <p><?php echo sprintf(t('received_licensed_request'),$this->config->item('website_title'));?></p>
    <p><?php echo t('to_view_request_status');?><br/>
    <b><?php echo site_url(); ?>/access_licensed/track/<?php echo $id; ?></b></p>
    <p><?php echo t('for_further_information');?> <?php echo $this->config->item('website_webmaster_email'); ?></p>
    <p>--<br/><?php echo $this->config->item('website_title');?></p>
    <br/>
    <br/>
    <br/>
    <br/>
</p>

<h2 class="page-title"><?php echo t('application_access_licensed_dataset');?></h2>
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
  	<tr>
    	<td colspan="2" class="note">
        <div><?php echo t('info_kept_confidential');?></div>
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
   <?php if ($request_type=='study'):?>
        <tr class="border" >
          <td valign="top"><?php echo t('dataset_requested');?></td>
          <td>
          	<div style="color:maroon;font-size:12px;">
		  	<?php echo $surveys[0]['surveyid']; ?> - <?php echo $surveys[0]['titl']; ?> <br/><?php echo $surveys[0]['proddate']; ?></div></td>
        </tr>
    <?php elseif ($request_type='collection'):?>
        <tr class="border" valign="top">
          <td><?php echo t('dataset_requested');?></td>
          <td>
                <table class="grid-table">
                <?php $k=1;foreach($surveys as $survey):?>
                <tr class="row">
                    <td><?php echo $k++;?></td>
                    <td><a target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></td>
                </tr>
                <?php endforeach;?>
                </table>
          </td>
        </tr>    
    <?php endif;?>
    <tr class="border">
      <td><?php echo t('date_requested');?></td>      
      <td><?php echo date("M/d/Y",$created);?></td>
    </tr>

    <tr class="border">
      <td colspan="2">
				<p><?php echo t('filled_lead_research');?></p>
      </td>      
    </tr>
    
    <tr>
    	<td><span class="required">*</span> <?php echo t('receiving_org');?></td>
        <td><span class="response"><?php echo isset($org_rec) ? $org_rec : ''; ?></span></td>
    </tr>
  
  <tr class="border">
    <td><span class="required">*</span> <?php echo t('telephone');?></td>
    <td><?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?></td>
  </tr>
  <tr class="border">
    <td><span class="required">*</span> <?php print t('intended_use');?></td>
    <td><?php echo get_form_value('datause',isset($datause) ? $datause : ''); ?></td>
  </tr>
  <tr class="border">
    <td><div style="font-weight:bold;"><?php print t('expected_output');?></div></td> 
    <td><?php echo get_form_value('outputs',isset($outputs) ? $outputs : ''); ?></td>
  </tr>
  <tr class="border">
    <td><strong><?php print t('expected_completion');?></strong> </td>
    <td><?php echo get_form_value('compdate',isset($compdate) ? $compdate : ''); ?></td>
  </tr>
  <?php /*?>
  <tr class="border">
    <td colspan="2">
    	<div style="font-weight:bold;"><?php print t('data_matching');?></div>
        <?php print t('merge_dataset');?>    
        <?php echo form_dropdown('datamatching', $options_datamatching, isset($datamaching) ? $datamatching : '');?>
      </td>
  </tr>
  <tr class="border">
    <td colspan="2"><?php print t('other_data_merge');?><br />
    <textarea id="mergedatasets" name="mergedatasets" style="width:98%" rows="10"><?php echo get_form_value('mergedatasets',isset($mergedatasets) ? $mergedatasets : ''); ?></textarea></td>
  </tr>
  <?php */ ?>
  <tr class="border">
    <td><strong><?php print t('research_team');?></strong><br /><?php print t('provide_names');?></td>
    <td><?php echo get_form_value('team',isset($team) ? $team : ''); ?></td>
  </tr>
  <tr class="border">
    <td colspan="2"><strong><?php print t('ident_needed');?></strong><br />
      <br/>
	<?php print t('da_website');?>
    <br/><br/>

<span style="font-weight:bold"><?php print t('this_request');?></span><span class="required">*</span> <br/>
<input type="radio" name="dataset_access" id="access_whole" value="whole" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='whole' ? 'checked="checked"' : ''; ?> />
<label for="access_whole"><?php print t('whole_dataset');?></label><br/>
<input type="radio" name="dataset_access" id="access_subset" value="subset" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='subset' ? 'checked="checked"' : ''; ?>/>
<label for="access_subset"><?php print t('subset_data');?></label></td>
  </tr>
  <tr class="border">
    <td colspan="2">
        <div>
          <div style="margin-top:5px;font-weight:bold;"><?php print t('data_access_agreement');?></div>
          <?php print t('agreement_text');?>
        </div></td>
  </tr>
  </table>