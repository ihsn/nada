<form class="form" name="form_compose_email" id="form-communicate">
  <div class="field">
    <label><?php echo t('compose_email');?></label>
  </div>
  <div class="field">
    <label for="to"><?php echo t('to');?></label>
    <input name="to" type="text" class="input-flex" value="<?php echo implode(", ",$project->owner); ?>"/>
  </div>
  <div class="field">
    <label><?php echo t('cc');?> <?php echo t('use_comma_to_seperate_email');?></label>
    <input name="cc" type="text" value="<?php echo implode(", ",$project->collaborators); ?>" class="input-flex"/>
  </div>
  <div class="field">
    <label><?php echo t('subject');?></label>
    <input name="subject" type="text" class="input-flex" value="RE: [#<?php echo $project->title; ?>]"/>
  </div>
  <div class="field">
    <label><?php echo t('body');?></label>
    <textarea name="body" rows="5" class="input-flex" placeholder="your email message to the user..."></textarea>
  </div>
  
  <div class="status-text" style="margin-top:10px;margin-bottom:10px;"></div>
  
  <div class="field">
    <input type="button" name="submit" id="communicate-submit" value="<?php echo t('send');?>" />
  </div>
</form>


<script type="text/javascript">
$(function() {

//submit email
$( "#communicate-submit" ).on( "click", function() {
	$this_obj=$(this);
	$this_obj.attr("disabled", "disabled");

	var form_data = $("#form-communicate").serialize();
	var url= "<?php echo site_url('admin/datadeposit/tab_communicate/'.$project->id);?>";
	
	$.ajax({
		type: "POST",
		url: "<?php echo site_url('admin/datadeposit/tab_communicate/'.$project->id);?>",
		data: form_data,
		dataType: "json",
		success: function(data){
			if (data.status=='success'){
				$("#form-communicate .status-text").html('<div class="success">'+data.message+'</div>');
			}
			else{
				$("#form-communicate .status-text").html('<div class="error">'+data.message+'</div>');
			}
		},
		failure: function(data) {
			$("#form-communicate .status-text").html('<div class="error">Failed to update</div>');
		},
		complete: function() {
 	 		$this_obj.removeAttr("disabled");        
		}
	});
});

});
</script>