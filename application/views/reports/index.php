<style>
.report-table{border-collapse:collapse;border:1px solid gainsboro;}
.report-table tr th{background-color:gainsboro;border-bottom:2px solid gainsboro;font-weight:bold;padding:5px;}
.report-table td{border-bottom:1px solid gainsboro;padding:5px;}
.report-table .section-title td {font-weight:bold;font-size:14px;margin-top:10px;background-color:gainsboro;padding:5px;}
.report-table .sub-section td {font-weight:bold;font-size:14px; font-style:italic;margin-top:10px;background-color:#F4F4F4;padding:5px;}
label{font-weight:bold;}
.form-title{font-size:14px; font-weight:bold;padding-top:5px; padding-bottom:5px;}
</style>

<div class="container-fluid">
<h1><?php echo t('reports');?></h1>
<form style="background-color:#E6E6E6;padding:10px;margin-bottom:20px;" id="form_report">	
	<div class="form-title"><?php echo t('select_reporting_period');?></div>
    <label for="from"><?php echo t('from');?></label>
    <input type="text" name="from" id="from" size="30" maxlength="15" class="date" value="<?php echo form_prep($this->input->get("from")); ?>"/>
    
    <label for="to"><?php echo t('to');?></label>
    <input type="text" name="to" id="to" size="30" maxlength="15" class="date" value="<?php echo form_prep($this->input->get("to")); ?>"/>
	<label for="report"><?php echo t('select_report');?></label>
    <select id="report" name="report">
        <option value="top-keywords"><?php echo t('top_search_keywords');?></option>
        <option value="survey-summary"><?php echo t('most_viewed_studies_summary');?></option>
        <option value="survey-detailed"><?php echo t('most_viewed_studies_detailed');?></option>
        <option value="downloads-detailed"><?php echo t('downloads_detailed');?></option>
        <option value="licensed-requests"><?php echo t('licensed_requests');?></option>
        <option value="public-requests"><?php echo t('public_requests');?></option>
        <option value="study-statistics"><?php echo t('study_statistics');?></option>
        <option value="users-statistics"><?php echo t('users_statistics');?></option>
        <option value="study-data-access"><?php echo t('studies_data_access');?></option>
        <option value="broken-resources"><?php echo t('broken_resources');?></option>
    </select>
    <input type="submit" name="Submit" value="<?php echo t('submit');?>" onClick="do_report();return false;"/>
    
    <div style="padding-top:10px;"><a href="<?php echo site_url();?>/admin/logs/"><?php echo t('view_complete_site_logs');?></a></div>
</form>
<div id="report-body"></div>
</div>
<script type="text/javascript">
	$(function() {
		var dates = $('#from, #to').datepicker({
			defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 1,
			onSelect: function(selectedDate) {
				var option = this.id == "from" ? "minDate" : "maxDate";
				var instance = $(this).data("datepicker");
				var date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
				dates.not(this).datepicker("option", option, date);
			}
		});
	});
	
	function do_report()
	{
		$("#report-body").html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> <?php echo t('searching_please_wait');?>');
		var url=CI.base_url+'/admin/reports/?ajax=1';
		$.get(url,$("#form_report").serialize(), 
			function (data) {
				//success
				$("#report-body").html(data);
			});
	}
</script>