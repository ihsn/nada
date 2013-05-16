<style type="text/css">
.survey-notes {font-size:smaller;}
.survey-notes .author, .survey-notes .date{font-size:smaller;color:#333333;}
.survey-notes .note{margin-bottom:15px;border:1px solid gainsboro;background:#F1F1F1;margin-top:5px;padding:5px;position:relative;}
.survey-notes .note:hover{background:#F3F3F3;cursor:pointer;border:1px solid gray;}
.survey-notes .text{color:#333333;margin-right:10px;}
.survey-notes .note .note-links{position:absolute;right:0px;top:5px;}
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

	$(document.body).on("click",".notes-container .edit", function(){	
		dialog_study_note($(this).attr("href"),"Edit note");
		return false;
	});

	$(document.body).on("click",".notes-container .add-note", function(){	
		dialog_study_note($(this).attr("href"),"Add note");
		return false;
	});

	$(document.body).on("click",".notes-container .remove", function(){	
		$.get($(this).attr("href"))
		.done(function(data) {						
			reload_notes();
		});
		return false;
	});

	$(document.body).on("click",".notes-container .note", function(){	
		$(this).toggleClass("collapsed");
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

	//related_studies_attach_studies selection dialog
	function dialog_study_note(url,title)
	{
		var dialog_id='dialog-study-notes';
		
		if ($('#'+dialog_id).length==0){
			$("body").append('<div id="'+dialog_id+'" title="'+title+'"></div>');
		}
		
		var dialog=$( "#"+dialog_id ).dialog({
			title: title,
			height: 400,
			position:"center",
			width:650,
			modal: true,
			autoOpen: true,
			buttons: {
				"Cancel": function() {
					$( this ).dialog( "close" );
				},
				"Submit": function() {
					var obj_this=$(this);
					var post_url=$("#dialog-study-notes form").attr("action");
					//$(".notes-container .ajax-status").html("js_loading");
					$.post(post_url, $("#dialog-study-notes form").serialize())
					
					.done(function(data) {						
						reload_notes();
						//$(".notes-container .ajax-status").html("");						
					});
					
					obj_this.dialog( "close" );
				}
			}//end-buttons
		});//end-dialog

		//load dialog content
		$('#'+dialog_id).html("loading...");
		$('#'+dialog_id).load(url, function() {
			console.log("loaded");			
		});
	}	

</script>

<div class="notes-container">

<div class="links" style="text-align:right;">
<a href="<?php echo site_url('admin/catalog_notes/add') . '/' . $this->uri->segment(4); ?>" class="add-note" onclick="javascript:return false;"><?php echo t('add_note');?></a>
</div>

<div class="ajax-status"></div>

<div id="study_notes_list" class="survey-notes" data-sid="<?php echo $id;?>" data-url="<?php echo site_url('admin/catalog_notes/get_notes/'.$id);?>">
<?php $this->load->view('catalog/study_notes_list');?>
</div>
</div>