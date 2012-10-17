<style type="text/css">
	div#survey_ids {
		width: 100%;
		height: 200px;
	}

	div#admin_ids form {
	}
	
	ul#survey_ids {
		height: 200px;
		width: 100%;
		float: left;
		list-style-type: square !important;
	}
	
	ul#survey_ids li {
		padding: 5px 0;
		float: left;
		clear:both;
		height: auto;
		margin-left: 15px;
		list-style-type: square !important;	
	}
	ul#survey_ids li small {
		color: #666;
		font-size:7pt;
	}
</style>
<form method="post" action="" class="survey-other-ids">
<div class="field">
            <input id="survey_id" type="text" name="admin_survey_id" class="input-flex" >
            <input type="button" value="+" name="admin_survey_id_submit" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
        </div>
</form>
<div style="overflow:auto;margin-left:20px">
<script type="text/javascript">
$(function() {
	$("ul#survey_ids li a").live('click', function() {
		id=$(this).parent().attr('id');
		$.get("<?php echo site_url('admin/catalog_ids/delete'); ?>/"+id);
		$(this).parent().remove();
	});
	$("input[name='admin_survey_id_submit']").click(function(e) {
		data = {
			survey_id: $("input[name='admin_survey_id']").val(),
		};
		$.post("<?php echo site_url('admin/catalog_ids/add') . '/' . $this->uri->segment(4); ?>", data, function(data) {
			$("ul#survey_ids").html(data);
		});
		$("input[name='admin_survey_id']").val('');
	
		return false;
	});
});
</script>
<ul id="survey_ids">
	<?php foreach($ids as $survey_id) {
		echo "	<li id='{$survey_id['id']}'>{$survey_id['survey_id']}&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
	} ?>
</ul>
</div>
