<style>
.fieldset{padding:10px;width:99%;}
h2{font-weight:bold; font-size:14px;}
.email-fieldset{padding:10px;}
.action{background-color:#EAEAEA;padding:10px; border:1px solid gainsboro;padding-bottom:10px;}
.action label.inline{margin-right:10px;font-weight:bold;display:inline}


/* styles for expand/collapse */
.expand a {
  display:block;
  padding:3px 10px;
  background-color:gainsboro;
  text-decoration:none;
  color:black;
}
.expand a:link, .expand a:visited {
  border:1px solid gainsboro;
  background-image:url(images/down.gif);
  background-repeat:no-repeat;
  background-position:99.5% 50%;
}
.expand a:hover, .expand a:active, .expand a:focus {
  text-decoration:underline
}
.expand a.open:link, .expand a.open:visited {
  border-style:solid;
  background:#eee url(images/up.gif) no-repeat 99.5% 50%
}
.collapse{border:1px solid gainsboro;margin-bottom:10px;padding:5px;}
h3{font-size:1em;font-weight:bold;}
.box-wrapper{margin-bottom:10px;margin-top:10px;}
/* end styles for expand/collapse */

.field{
	margin-top:10px;
	margin-bottom:10px;
}

.field label{font-weight:bold;}
.project-users{font-weight:normal;font-size:small;color:gray;}
</style>
<?php 
	//users who will receive the email notification
	$project_users=implode(", ",array_merge($project->collaborators,$project->owner));
	$project->status=strtolower($project->status);
?>
<div style="margin-bottom:10px;font-weight:bold"><?php echo t('request_status');?>: <em><?php echo t($project->status); ?></em></div>
	<form method="post" id="manage-project-status">
        <div class="field action">
            <div>
                <b style="padding-right:15px;"><?php echo t('select_action');?></b>
                <?php if ($project->status != 'processed'): ?>
                <label class="inline"><input type="radio" name="status" value="draft" <?php echo ($project->status=='draft') ? 'checked="checked"' : ''; ?>/><?php echo t('Draft');?></label>
           		<?php endif; ?>
                <label class="inline"><input type="radio" name="status" value="accepted"	<?php echo ($project->status=='accepted') ? 'checked="checked"' : ''; ?>/><?php echo t('Accepted');?></label>
 				<?php if ($project->status != 'draft'): ?>          
                <label class="inline"><input type="radio" name="status" value="processed"	<?php echo ($project->status=='processed') ? 'checked="checked"' : ''; ?>/><?php echo t('Processed');?></label>
           		<?php endif; ?>
                <label class="inline"><input type="radio" name="status" value="closed"	<?php echo ($project->status=='closed') ? 'checked="checked"' : ''; ?>/><?php echo t('Closed');?></label>                
 				<?php if ($project->status != 'draft'): ?>          
                <label class="inline"><input type="radio" name="status" value="draft" /><?php echo t('Reopen');?></label>
           		<?php endif; ?>
			</div>
        </div>   
        <div class="accepted_id field">
 			<label>Assign study ID<span style="color:#ff0000">*</span></label>
            <input type="text" name="assign_study_id" value="<?php if (isset($study_id)) echo $study_id; ?>" class="input-flex" />
        </div>
        <div class="field">
            <label><b><?php echo t('comments');?></b> <em><?php echo t('comments_visible_to_users');?></em></label>
            <textarea name="comments" rows="4" class="input-flex"><?php //echo isset($project[0]->admin_comments) ? $project[0]->admin_comments : ''; ?></textarea>
        </div>
        
        <div class="field">
               <label for="notify"><input type="checkbox" name="notify" id="notify" value="1"/> <?php echo t('notify_user_by_email');?> <span class="project-users">(<?php echo $project_users;?>)</span></label>
         </div>
                       
        <div id="status-text" style="margin-top:10px;margin-bottom:10px;"></div>
        <input type="hidden" name="project_id" value="<?php echo $project->id;?>"/>
		<input type="button" name="update" id="update_status" value="<?php echo t('update');?>" />        
	</form>

<script type="text/javascript">
$(function() {

//update project status/comments/assign_id
$( "#update_status" ).on( "click", function() {

	$this_obj=$(this);
	$this_obj.attr("disabled", "disabled");

	var form_data = $("#manage-project-status").serialize();
	var url= "<?php echo site_url('admin/datadeposit/tab_process/'.$project->id);?>";
	
	$.ajax({
		type: "POST",
		url: "<?php echo site_url('admin/datadeposit/tab_process/'.$project->id);?>",
		data: form_data,
		dataType: "json",
		success: function(data){
			if (data.status=='success'){
				$("#status-text").html('<div class="success">'+data.message+'</div>');
			}
			else{
				$("#status-text").html('<div class="error">'+data.message+'</div>');
			}
		},
		failure: function(data) {
			$("#status-text").html('<div class="error">Failed to update</div>');
		},
		complete: function() {
 	 		$this_obj.removeAttr("disabled");        
		}
	});
});

});
</script>