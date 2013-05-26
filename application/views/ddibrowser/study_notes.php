<style type="text/css">
.survey-notes .author{font-weight:bold; text-transform:uppercase}
.survey-notes .survey-note .note-links{position:absolute;right:5px;top:10px;font-size:11px;}
.survey-notes .survey-note{border:1px solid gainsboro;margin-bottom:15px;position:relative;}
.notes-container .author-info{
padding: 10px;
border-bottom: 1px solid #CCC;
background-color: #E1E1E1;
background-image: -moz-linear-gradient(#F8F8F8, #E1E1E1);
background-image: -webkit-linear-gradient(#F8F8F8, #E1E1E1);
background-image: linear-gradient(#F8F8F8, #E1E1E1);
background-repeat: repeat-x;
font-size: 12px;
}
.notes-container {position:relative;}
.notes-container .text{padding: 10px;
color: #333;
font-size: 12px;
background: #FBFBFB;
line-height:140%;
}
.notes-container .links{
position:absolute;
top:0px;
right:0px;
font-size: 12px;
}
.dialog-study-notes .ui-dialog-titlebar {
margin-bottom: 5px;
height: 28px;
padding-top: 5px;
}
.dialog-study-notes .note_body_text{height:260px;font-size:12px;line-height:150%;}
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
			$("body").append('<div  id="'+dialog_id+'" title="'+title+'"></div>');
		}
		
		var dialog=$( "#"+dialog_id ).dialog({
			title: title,
			height: 400,
			position:"center",
			width:650,
			modal: true,
			autoOpen: true,
			dialogClass: 'dialog-study-notes',
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
			//console.log("loaded");
		});
	}	

</script>
<div class="notes-container">
<h2><?php echo t('reviewer_notes');?></h2>
<div class="links" style="text-align:right;" >
<a href="<?php echo site_url('catalog/'.$study_id.'/review/get-add-form/'); ?>" class="add-note btn-add-note btn btn-small" onclick="javascript:return false;"><i class="icon-plus-sign"></i> <?php echo t('add_note');?></a>
</div>

<div class="ajax-status"></div>

<div id="study_notes_list" class="survey-notes" data-sid="<?php echo $study_id;?>" data-url="<?php echo site_url('catalog/'.$study_id.'/review/get-notes/');?>">
<?php $this->load->view('ddibrowser/study_notes_list');?>
</div>
</div>