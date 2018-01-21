<style type="text/css">

.survey-notes .author{
	font-weight:bold;font-size:smaller;
	color:#337ab7;
	}
.survey-notes	.author .date{
		font-weight:normal;color:gray;
}

.survey-notes .note{margin-bottom:15px;border-bottom:1px solid gainsboro;margin-top:5px;padding:5px;padding-bottom:20px;position:relative;}
.survey-notes .text{color:#333333;margin-right:10px;}
.notes-container .form{background:#F1EDED;padding:10px;}
.survey-notes .note .text{font-size:12px;}
.survey-notes .collapsed {height:38px;overflow:hidden;}
.notes-container .survey-notes .type-admin{}
.survey-notes .type-reviewer{}
.survey-notes .type-public{}
.survey-notes .note-type{font-weight:bold;font-size:14px;border-bottom:2px solid gainsboro; text-transform:uppercase}
.notes-container .ajax-status{border:1px solid green;padding:5px;margin:10px;color:green;display:none;}
</style>

<script type="text/javascript">
$(function() {

	//submit note
	$(document.body).on("click",".form-post-note #submit-form", function(){
		var post_url=$(".form-post-note").attr("action");
		$.post(post_url, $(".form-post-note").serialize())
		.done(function(data) {
			reload_notes();
			$('.form-post-note').trigger("reset");

		});
	});

	$(document.body).on("click",".notes-container .remove", function(){
		$.get($(this).attr("href"))
		.done(function(data) {
			reload_notes();
		});
		return false;
	});

});

	function reload_notes()
	{
		$(".notes-container .ajax-status").show().html("<?php echo t('js_refreshing_page');?>");
		var notes_url=$("#study_notes_list").attr("data-url");
		$.get(notes_url)
		.done(function(data) {
			$("#study_notes_list").html(data);
			$(".notes-container .ajax-status").fadeOut(2000);
		});
	}

</script>


<div class="panel panel-default">
  <div class="panel-body" style="background:#eee;">

		<form method="post" class="form-post-note" action="<?php echo site_url('admin/catalog_notes/add/'.$id); ?>" style="padding:10px;">

		<div class="form-group">
		    <label class="inline"><?php echo t('select_note_type');?>
		    <?php echo form_dropdown('type', array('admin'=>t('admin_note'),'reviewer'=>t('reviewer_note'),'public'=>t('public_note')),null,'class="edit_note_type"'); ?>
		    </label>
		</div>

		<div class="form-group">
		    <textarea name="note" class="form-control note_body_text" rows="4" placeholder="<?php echo t('Type note...');?>" ></textarea>
		</div>
		<div class="form-group">
				<button id="submit-form" type="button" class="btn btn-default"><?php echo t('Submit');?></button>
		</div>
		</form>


</div>
</div>

<div class="notes-container">
	<div class="ajax-status"></div>
	<div id="study_notes_list" class="survey-notes" data-sid="<?php echo $id;?>" data-url="<?php echo site_url('admin/catalog_notes/get_notes/'.$id);?>">
		<?php $this->load->view('catalog/study_notes_list');?>
	</div>
</div>
