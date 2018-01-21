<?php
/**
* Form for collecting data for the Public Use Access Requests
*
*/
?>
<style>
.public-use td{border:1px solid gainsboro;padding:5px;}
</style>

<h1 class="page-title">Application for Access to a Public Use Dataset</h1>
<div style="font-style:italic;color:red;">Fields marked with <span class="style1">*</span> are mandatory.</div>

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
	<input type="hidden" name="id" value="<?php echo get_form_value('id',isset($id) ? $id : ''); ?>" />
    
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
  	<tr>
    	<td colspan="2" class="note">
        <div>The information provided on this page will be kept confidential and will be used for internal purposes only. </div>
        </td>
    </tr>
    <tr class="border">
      <td>First name</td>
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
      <td colspan="2"><div style="font-weight:bold;"><span style="color:red;">*</span> Intended use of the data:<br />
          <br />
        </div>
        <div style="font-style:italic"> Please provide a short description of your research project (project question, objectives, methods, expected outputs, partners) </div>
        <textarea id="abstract" name="abstract" class="input-flex" rows=10><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
        <div>
          <div style="margin-top:5px;font-weight:bold;">Terms and conditions:</div>
          <ol>
            <li>The data and other materials provided by the National Data Archive will not
	        be redistributed or sold to other individuals, institutions, or organizations
	        without the written agreement of the National Data Archive. </li>
            <li>The data will be used for statistical and scientific research purposes only. They will be used solely for reporting of aggregated information, and not for
           investigation of specific individuals or organizations.</li>
            <li>No attempt will be made to re-identify respondents, and no use will be made
           of the identity of any person or establishment discovered inadvertently. Any
           such discovery would immediately be reported to the National Data Archive.</li>
            <li>No attempt will be made to produce links among datasets provided by the
           National Data Archive, or among data from the National Data Archive and other
           datasets that could identify individuals or organizations.</li>
            <li>Any books, articles, conference papers, theses, dissertations, reports, or other
			publications that employ data obtained from the National Data Archive will cite
			the source of data in accordance with the Citation Requirement provided with each
			dataset.</li>
            <li>An electronic copy of all reports and publications based on the requested
           data will be sent to the National Data Archive.</li>
            <li>The original collector of the data, the National Data Archive, and the
           relevant funding agencies bear no responsibility for use of the data or for
           interpretations or inferences based upon such uses.</li>
          </ol>
          <p>By continuing past this point to the data retrieval process, you signify your
agreement to comply with the above-stated terms and conditions and give your
assurance that the use of statistical data obtained from the National Data
Archive will conform to widely-accepted standards of practice and legal
restrictions that are intended to protect the confidentiality of respondents.</p>   		  
        </div></td>
    </tr>
    <tr class="border">
      <td colspan="2" class="note" align="right"><input type="checkbox" title="I Agree" id="chk_agree" name="chk_agree" onClick="isagree()"/>
        <label for="chk_agree">I agree&nbsp;&nbsp;</label>
        <input type="submit" value="submit" id="submit" name="submit"  onClick="submitform()">
      </td>
    </tr>
  </table>
</form>
<script type="text/javascript">
	function isagree(){
		$("#submit").attr('disabled', !$("#chk_agree").is(":checked"))	
	}
</script>