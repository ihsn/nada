<?php
/**
* Form for collecting data for - Licensed Data Requests
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
    <p>Dear <?php echo $fname. ' '. $lname; ?>,</p>
    <p>The <b><?php echo $this->config->item('website_title'); ?></b> has received your request for the licensed datafiles. We will notify you via email once your application has been reviewed.</p>
    <p>To view the status of your request, please visit:<br/>
    <b><?php echo site_url(); ?>/access_licensed/track/<?php echo $id; ?></b></p>
    <p>For further information, please contact us at <?php echo $this->config->item('website_webmaster_email'); ?></p>
    <p>-- the data archive</p>
    <br/>
    <br/>
    <br/>
    <br/>
</p>

<h2 class="page-title">Application for Access to a Licensed Dataset</h2>
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
  	<tr>
    	<td colspan="2" class="note">
        <div>The information provided on this page will be kept confidential and will be used for internal purposes only. </div>
        </td>
    </tr>
    <tr class="border">
      <td width="200px">First name</td>
      <td><?php echo get_form_value('fname',isset($fname) ? $fname : ''); ?></td>
    </tr>
    <tr class="border">
      <td>Last name</td>
      <td><?php echo get_form_value('lname',isset($lname) ? $lname: ''); ?></td>
    </tr>
    <tr  class="border">
      <td>Organization</td>
      <td><?php echo get_form_value('organization',isset($organization) ? $organization : ''); ?></td>
    </tr>
    <tr class="border">
      <td>E-mail</td>
      <td><?php echo get_form_value('email',isset($email) ? $email : ''); ?></td>
    </tr>
    <tr class="border">
      <td>Dataset requested:</td>
      <td><div style="color:maroon;font-size:12px;"><?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?> - <?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?> <br/><?php echo get_form_value('proddate',isset($proddate) ? $proddate : ''); ?></div></td>
    </tr>
    <tr class="border">
      <td colspan="2">
       <p><?php echo ('This form must be filled and submitted by the Lead Researcher. Lead Researcher refers to the person who serves as the main point of contact for all communications involving this agreement. Access to licensed datasets will only be granted when the Lead Researcher is an employee of a legally registered receiving agency (university, company, research centre, national or international organization, etc.) on behalf of which access to the data is requested. The Lead Researcher assumes all responsibility for compliance with all terms of this Data Access Agreement by employees of the receiving organization.');?></p>
    <p><?php echo ('This request will be reviewed by a data release committee, who may decide to approve the request, to deny access to the data, or to request additional information from the Lead Researcher. A signed copy of this request form may also be requested. If approved, data and documentation will be provided on CD-ROM/DVD or through secure ftp server.');?> </p>
    <p><?php echo ('This request is submitted on behalf of:');?><br />
      <br />
      <span class="required">*</span> <?php echo ('Receiving Organization name:');?>
      <input type="text" id="org_rec" name="org_rec"   value="<?php //echo $data['org_rec']; ?>" style="width:200px" maxlength="100" />
      <br />
      <?php echo ('Receiving Organization refers to the organization/university/establishment which employs the Lead Researcher.');?></p>
      </td>      
    </tr>
    <tr class="border" valign="top">
    <td><?php print t('Organization Type');?></td>
    <td><?php echo form_dropdown('org_type', $options_org_type, isset($org_type) ? $org_type : '');?>
      <br />
      <br />
<?php print t('If other, specify:');?><br/>
<input type="text" id="orgtype_other" name="orgtype_other"   value="<?php echo get_form_value('orgtype_other',isset($orgtype_other) ? $orgtype_other : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><span class="required">*</span> <?php print t('Postal address');?></td>
    <td><input type="text" id="address" name="address"   value="<?php echo get_form_value('address',isset($address) ? $address : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><p>      <span class="required">*</span> <?php print t('Telephone (with country code)');?><br />
    </p></td>
    <td><input type="text" id="tel" name="tel"   value="<?php echo get_form_value('tel',isset($tel) ? $tel : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td><?php print t('Fax (with country code)');?></td>
    <td><input type="text" id="fax" name="fax"   value="<?php echo get_form_value('fax',isset($fax) ? $fax : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div style="font-weight:bold;"><span class="required">*</span> <?php print t('Intended use of the data:');?><br />
          <br />
    </div>
      <div style="font-style:italic"> <?php print t('Please provide a short description of your research project (project question, objectives, methods, expected outputs, partners)');?> </div>
    <textarea id="datause" name="datause" style="width:98%" rows="10"><?php echo get_form_value('datause',isset($datause) ? $datause : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><div style="font-weight:bold;"><?php print t('List of expected output(s) and dissemination policy');?></div> 
    <textarea id="outputs" name="outputs" style="width:98%" rows="10"><?php echo get_form_value('outputs',isset($outputs) ? $outputs : ''); ?></textarea>     </td>
  </tr>
  <tr class="border">
    <td><strong><?php print t('Expected completion date (DD-MM-YYYY) of the research project:');?></strong> </td>
    <td><input type="text" id="compdate" name="compdate"   value="<?php echo get_form_value('compdate',isset($compdate) ? $compdate : ''); ?>" style="width:200px" maxlength="100" /></td>
  </tr>
  <tr class="border">
    <td colspan="2">
    	<div style="font-weight:bold;"><?php print t('Data matching');?></div>
        <?php print t('Will you need to merge the dataset with other data?');?>    
        <?php echo form_dropdown('datamatching', $options_datamatching, isset($datamaching) ? $datamatching : '');?>
      </td>
  </tr>
  <tr class="border">
    <td colspan="2"><?php print t('If YES specify all other datasets that will need to be merged');?><br />
    <textarea id="mergedatasets" name="mergedatasets" style="width:98%" rows="10"><?php echo get_form_value('mergedatasets',isset($mergedatasets) ? $mergedatasets : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><strong><?php print t('Research team members (other than the Lead Researcher)');?></strong><br />
      <br />
      <?php print t('Provide names, titles, and affiliations of any other members of the research team who will have access to the restricted data.');?><br/>
    <textarea id="team" name="team" style="width:98%" rows="10"><?php echo get_form_value('team',isset($team) ? $team : ''); ?></textarea></td>
  </tr>
  <tr class="border">
    <td colspan="2"><strong><?php print t('Identification of data files and variables needed');?></strong><br />
      <br/>
	<?php print t('The Data Archive provides detailed metadata on its website, including a description of data files and variables for each dataset. Researchers who do not need access to the whole dataset may indicate which subset of variables or cases they are interested in. As this reduces the disclosure risk, providing us with such information may increase the probability that the data will be provided.');?>
    <br/><br/>

<?php print t('This request if submitted to access:');?><span class="required">*</span> <br/>
<input type="radio" name="dataset_access" id="access_whole" value="whole" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='whole' ? 'checked="checked"' : ''; ?> />
<label for="access_whole"><?php print t('The whole dataset (all files, all cases)');?></label><br/>
<input type="radio" name="dataset_access" id="access_subset" value="subset" <?php echo get_form_value('dataset_access',isset($dataset_access) ? $dataset_access: '')=='subset' ? 'checked="checked"' : ''; ?>/>
<label for="access_subset"><?php print t('A subset of variables and/or cases as described below (note that variables such as the sample weighting coefficients and records identifiers will always be included in subsets):');?></label></td>
  </tr>
  <tr class="border">
    <td colspan="2">
        <div>
          <div style="margin-top:5px;font-weight:bold;"><?php print t('Data access agreement');?></div>
          <?php print t('The representative of the Receiving Organization agrees to comply with the following conditions:');?>
          <ol>
            <li><?php print t('Access to the restricted data will be limited to the Lead Researcher and other members of the research team listed in this request.');?></li>
            <li><?php print t('Copies of the restricted data or any data created on the basis of the original data will not be copied or made available to anyone other than those mentioned in this Data Access Agreement, unless formally authorized by the Data Archive.');?></li>
            <li><?php print t('The data will only be processed for the stated statistical and research purpose. They will be used for solely for reporting of aggregated information, and not for investigation of specific individuals or organizations. Data will not in any way be used for any administrative, proprietary or law enforcement purposes.');?> </li>
            <li><?php print t('The Lead Researcher must state if it is their intention to match the restricted microdata with any other micro-dataset. If any matching is to take place, details must be provided of the datasets to be matched and of the reasons for the matching. Any datasets created as a result of matching will be considered to be restricted and must comply with the terms of this Data Access Agreement.');?></li>
            <li><?php print t('The Lead Researcher undertakes that no attempt will be made to identify any individual person, family, business, enterprise or organization. If such a unique disclosure is made inadvertently, no use will be made of the identity of any person or establishment discovered and full details will be reported to the Data Archive. The identification will not be revealed to any other person not included in the Data Access Agreement.');?></li>
            <li><?php print t('The Lead Researcher will implement security measures to prevent unauthorized access to licensed microdata acquired from the Data Archive. The microdata must be destroyed upon the completion of this research, unless the Data Archive obtains satisfactory guarantee that the data can be secured and provides written authorization to the Receiving Organization to retain them. Destruction of the microdata will be confirmed in writing by the Lead Researcher to the Data Archive.');?></li>
            <li><?php print t('Any books, articles, conference papers, theses, dissertations, reports, or other publications that employ data obtained from the Data Archive will cite the source of data in accordance with the citation requirement provided with the dataset.');?></li>
            <li><?php print t('An electronic copy of all reports and publications based on the requested data will be sent to the Data Archive.');?></li>
            <li><?php print t('The original collector of the data, the Data Archive, and the relevant funding agencies bear no responsibility for use of the data or for interpretations or inferences based upon such uses.');?></li>
            <li><?php print t('This agreement will come into force on the date that approval is given for access to the restricted dataset and remain in force until the completion date of the project or an earlier date if the project is completed ahead of time.');?></li>
            <li><?php print t('If there are any changes to the project specification, security arrangements, personnel or organization detailed in this application form, it is the responsibility of the Lead Researcher to seek the agreement of the Data Archive to these changes. Where there is a change to the employer organization of the Lead Researcher this will involve a new application being made and termination of the original project.');?></li>
            <li><?php print t('Breaches of the agreement will be taken seriously and the Data Archive will take action against those responsible for the lapse if willful or accidental. Failure to comply with the directions of the Data Archive will be deemed to be a major breach of the agreement and may involve recourse to legal proceedings. The Data Archive will maintain and share with partner data archives a register of those individuals and organizations which are responsible for breaching the terms of the Data Access Agreement and will impose sanctions on release of future data to these parties.');?> </li>
          </ol>
        </div></td>
  </tr>
  </table>