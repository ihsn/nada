<?php
/**
* Form for collecting data for the Public Use Access Requests
*
*/
?>
<style>
.public-use td{border:1px solid gainsboro;padding:5px;}
.bullet{list-style:disc inside;}
.public-use .grid-table{border-collapse:collapse;border:0;}
.public-use .grid-table td{border:0;}
</style>

<h1 class="page-title"><?php echo t('application_for_access_to_public_use_dataset');?></h1>
<div style="font-style:italic;color:red;"><?php echo t('fields_marked_mandatory');?></div>

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
	<input type="hidden" name="id" value="<?php echo get_form_value('id',isset($id) ? $id : ''); ?>" />
    <?php if (isset($this->ajax)):?>
    	<input type="hidden" name="ajax" value="true" />
    <?php endif;?>
    
  <table class="public-use" border="0" width="100%" style="border-collapse:collapse;border:1px solid gainsboro;">
  	<tr>
    	<td colspan="2" class="note">
        <div><?php echo t('msg_information_confidential');?></div>
        </td>
    </tr>
    <tr class="border">
      <td><?php echo t('first_name');?></td>
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
    <tr class="border" valign="top">
      <td><?php echo t('collection_requested');?></td>
      <td>
          <b><?php echo sprintf(t('surveys_in_collection'),$collection['title']);?></b>
          <table class="grid-table">
          <?php $k=1;foreach($surveys as $survey):?>
            <tr class="row">
                <td><?php echo $k++;?></td>
                <td><a href="<?php echo site_url('catalog/'.$survey['id']);?>"><?php echo $survey['nation'];?> - <?php echo $survey['title'];?></a></td>
            </tr>
          <?php endforeach;?>
          </table>
      </td>
    </tr>
    <tr class="border">
      <td colspan="2"><div style="font-weight:bold;"><span style="color:red;">*</span> <?php echo t('intended_use_of_data');?>:<br />
          <br />
        </div>
        <div style="font-style:italic"> <?php echo t('describe_your_project');?> </div>
        <textarea id="abstract" name="abstract" class="input-flex" rows=10><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
        <div>
          <div style="margin-top:5px;font-weight:bold;"><?php echo t('terms_and_conditions');?></div>
			<div class="bull-list"><?php echo t('terms_text');?></div>
        </div></td>
    </tr>
    <tr class="border">
      <td colspan="2" class="note" align="right"><input type="checkbox" title="I Agree" id="chk_agree" name="chk_agree" onClick="isagree()"/>
        <label for="chk_agree"><?php echo t('i_agree');?>&nbsp;&nbsp;</label>
        <input type="submit" value="<?php echo t('submit');?>" id="submit" name="submit"  onClick="submitform()">
      </td>
    </tr>
  </table>
</form>
<script type="text/javascript">
	function isagree(){
		$("#submit").attr('disabled', !$("#chk_agree").is(":checked"))	
	}
</script>