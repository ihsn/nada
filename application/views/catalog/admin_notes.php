<style type="text/css">
.survey-notes {font-size:smaller;}
.survey-notes .author, .survey-notes .date{font-size:smaller;color:gray;}
.survey-notes .node{margin-bottom:15px;border:1px solid gainsboro;background:#F1F1F1;margin-top:5px;padding:5px;}
.survey-notes .text{color:#333333;margin-right:10px;}
.survey-notes .node .links{float:right;}
.survey-notes .node .links .remove{width:16px;height:16px;display:block;margin:0px;padding:0px;}
</style>

<script type="text/javascript">
$(function() {
	$("#admin_notes_container .remove").live('click', function() {
		url=$(this).attr('href');
		$.get(url);
		$(this).parent().parent().remove();
		return false;
	});
	
	$("#btn_admin_note").live('click', function() {
		data = {
			note: $("input[name='admin_note']").val(),
			type: 'admin'
		};
		$.post("<?php echo site_url('admin/catalog_notes/add') . '/' . $this->uri->segment(4); ?>", data, function(data) {	
			$("#admin_notes_container").html(data);
		});
		$("#admin_note").val('');
	
		return false;
	});
});
</script>

<div class="field">
    <input id="admin_note" type="text" name="admin_note" class="input-flex" style="width:70%;">
    <input type="button" value="+" id="btn_admin_note" name="admin_note_submit" style="border:1px solid gainsboro;padding:3px 5px 3px 5px;">
</div>

<div id="admin_notes_container" class="survey-notes">
	<?php $this->load->view('catalog/notes_by_type');?>
</div>
