<?php
/**
*
* Survey Aliases
**/
?>

<script type="text/javascript">
$(function() {
	$("#survey-aliases .remove").live('click', function() {
		url=$(this).attr('href');
		$.get(url);
		$(this).parent().remove();
		return false;
	});
	
	$("#btn_survey_alias").live('click', function() {
		data = { alternate_id: $("input[name='txt_survey_alias']").val() };
		$.post("<?php echo site_url('admin/survey_alias/add/'.$this->uri->segment(4)); ?>", data, function(data) {	
			$("#survey-aliases").html(data);
		});
		$("#txt_survey_alias").val('');
		return false;
	});
});
</script>

<div class="field" style="margin-bottom:15px;">
    <input id="txt_survey_alias" type="text" name="txt_survey_alias" class="input-flex" style="width:70%;">
    <input type="button" value="+" id="btn_survey_alias" name="btn_survey_alias" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
</div>

<div>
	<div id="survey-aliases">
    <?php $this->load->view('catalog/survey_aliases_list');?>
	</div>
</div>
