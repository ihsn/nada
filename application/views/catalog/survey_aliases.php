<?php
/**
*
* Survey Aliases
**/
?>

<script type="text/javascript">
$(function() {
	$("#survey-aliases").on('click','.remove', function() {
		url=$(this).attr('href');
		$.get(url);
		$(this).parent().remove();
		return false;
	});
	
	$("#btn_survey_alias").on('click',null, function() {
		data = { alternate_id: $("input[name='txt_survey_alias']").val() };
		$.post("<?php echo site_url('admin/survey_alias/add/'.$this->uri->segment(4)); ?>", data, function(data) {	
			$("#survey-aliases").html(data);
		});
		$("#txt_survey_alias").val('');
		return false;
	});
});
</script>

<div class="form-inline">
<div class="field form-group" style="margin-bottom:15px;">
    <input id="txt_survey_alias" type="text" name="txt_survey_alias" class="form-control" placeholder="Type alias here"  >
    <input type="button" value="+" id="btn_survey_alias" name="btn_survey_alias" class="btn btn-default">
</div>
</div>
<div>
	<div id="survey-aliases">
    <?php $this->load->view('catalog/survey_aliases_list');?>
	</div>
</div>
