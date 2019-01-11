<body>
<style>
.public-use td{border:1px solid gainsboro;padding:5px;}
.required{color:red;}
.ures{color:#666666;background-color:#FFFFCC}
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

if (isset($datamatching) & $datamatching==1)
{
	$datamatching=t('yes');
}
else
{
	$datamatching=t('no');
}
?>
<div style="background-color:gainsboro;padding:10px;font-size:14px;">
<?php echo nl2br($this->input->post("body"));?>
</div>
<br /><br  />
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
      <tr class="border" valign="top">
      <td width="200px"><?php echo t('request_information');?></td>
      <td>
          	<div style="margin-bottom:10px;font-weight:bold"><?php echo t('request_status');?>: <em><?php echo t($status); ?></em></div>
      		<span><?php echo t('request_id');?>: <?php echo (isset($id) ? $id : ''); ?></span>, 
            <span><?php echo t('dated');?>: <?php echo date("D, M/d/Y H:i:s",(isset($created) ? $created : '')); ?></span>
      </td>
    </tr>

    <tr class="border">
      <td width="200px"><?php echo t('first_name');?></td>
      <td><span class="ures"><?php echo get_form_value('fname',isset($user['fname']) ? $user['fname'] : ''); ?></span></td>
    </tr>
    <tr class="border">
      <td><?php echo t('last_name');?></td>
      <td><span class="ures"><?php echo get_form_value('lname',isset($user['lname']) ? $user['lname']: ''); ?></span></td>
    </tr>
    <tr  class="border">
      <td><?php echo t('organization');?></td>
      <td><span class="ures"><?php echo get_form_value('organization',isset($user['organization']) ? $user['organization'] : ''); ?></span></td>
    </tr>
    <tr class="border">
      <td><?php echo t('email');?></td>
      <td><span class="ures"><?php echo get_form_value('email',isset($user['email']) ? $user['email'] : ''); ?></span></td>
    </tr>
    <tr class="border">
      <td><?php echo t('dataset_requested');?></td>
      <td>
	      <table class="grid-table" border="0" style="border-collapse:collapsed;">
                <?php $survey_count=count($surveys);$k=1;foreach($surveys as $survey):?>
                <tr class="row">
                	<?php if($survey_count>1):?>
                    <td style=""><?php echo $k++;?></td>
                    <?php endif;?>
                    <td style="">
                    	<div style=""><a target="_blank" href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['title'];?> - <?php echo $survey['year_start'];?></a></div>
                    </td>
                </tr>
                <?php endforeach;?>
         </table>

      </td>
    </tr>
    <tr class="border">
		<td><?php echo t('receiving_organization_name');?></td>
      <td><span class="ures"><?php echo get_form_value('org_rec',isset($org_rec) ? $org_rec : ''); ?></span></td>      
    </tr>
    <?php /*?>
    <tr class="border" valign="top">
    <td><?php echo t('organization_type');?></td>
    <td>
	    <span class="ures">
		<?php if (isset($orgtype_other)): ?>
			<?php echo get_form_value('orgtype_other',isset($orgtype_other) ? $orgtype_other : ''); ?>
        <?php else: ?>
	        <?php echo isset($org_type) ? $org_type: ''; ?>
        <?php endif;?>
        </span>
  </tr>
  <tr class="border">
    <td><?php print t('postal_address');?></td>
    <td><span class="ures"><?php echo get_form_value('address',isset($address) ? $address : ''); ?></span></td>
  </tr>
  <?php */ ?>
  <tr class="border">
    <td><?php print t('telephone');?></td>
    <td><span class="ures"><?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?></span></td>
  </tr>
  <?php /*?>
  <tr class="border">
    <td><?php print t('fax');?></td>
    <td><span class="ures"><?php echo get_form_value('fax',isset($fax) ? $fax : ''); ?></span></td>
  </tr>
  <?php */?>
  <tr class="border">
    <td><div><?php print t('intended_use_of_data');?></div></td>
    <td><span class="ures"><?php echo get_form_value('datause',isset($datause) ? $datause : ''); ?></span></td>
  </tr>
  <tr class="border">
    <td><?php print t('list_expected_output');?></td>
    <td>
    <span class="ures" ><?php echo get_form_value('outputs',isset($outputs) ? $outputs : ''); ?></span></td>
  </tr>
  <tr class="border">
    <td><?php print t('expected_completion_date');?></td>
    <td><span class="ures"><?php echo get_form_value('compdate',isset($compdate) ? $compdate : ''); ?></span></td>
  </tr>
  <?php /* ?>
  <tr class="border">
    <td><?php print t('data_matching');?></td>
    <td>    	
        <?php print t('need_merge_data');?>    
        <?php echo $datamatching;?>
		<br/><br/>        
        <?php print t('specify_other_datasets');?><br />
    <div><span class="ures"><?php echo get_form_value('mergedatasets',isset($mergedatasets) ? $mergedatasets : ''); ?></span></div>
      </td>
  </tr>
  <?php */ ?>
  <tr class="border">
    <td><?php print t('research_team_members');?></td>
    <td>
      <?php print t('provide_names_research_team');?><br/>
    <div ><span class="ures"><?php echo get_form_value('team',isset($team) ? $team : ''); ?></span></div></td>
  </tr>
  <tr class="border">
    <td><?php print t('identification_data_files_and_variables_needed');?></td>
    <td>
		<?php if ( isset($dataset_access) ): ?>
			<?php if ($dataset_access=='whole'): ?>
                <span >
                <input type="checkbox" name="access_whole" id="access_whole" value="1" checked="checked" disabled="disabled"/>
                <?php print t('whole_dataset');?></span>
            <?php else:?>    
                <br/><span>
                <input type="checkbox" name="access_subset" id="access_subset" value="1" checked="checked" disabled="disabled"/>
                <?php print t('subset_dataset');?></span>
            <?php endif;?>
        <?php endif;?>    			    
	</td>
  </tr>
  </table>
<body>  