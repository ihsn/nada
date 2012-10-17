<script type="text/javascript">
/*
$(function(){
(function($) {
    $.fn.checkChanges = function(message, grid) {
        var _self  = this;
		var events = (grid) ? 'click' : 'keyup change keydown'; 
        $(_self).bind(events, function(e) {
            $(this).addClass('changedInput');
        });
        $(window).bind('beforeunload ', function() {
            if ($('.changedInput').length) {
                return message;
            }
        });
    };
})(jQuery);

$('textarea')
	.checkChanges('Your data will be unsaved.', false);
});*/
</script>
	<h2><?php echo t('title_citations'); ?></h2>
    <div class="instructions-box"><?php echo t('instructions_citations'); ?></div>
	<?php $message=$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
	<?php echo form_open("datadeposit/citations/{$project[0]->id}");?>
	<div style="text-align:left">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="submit" name="update" value="Save" id="submit"/>
        
	</div>
	<textarea name="citations" rows="25" id="citation" class="input-flex"><?php if (isset($study[0]->citations)) echo $study[0]->citations; ?></textarea>
	<div style="text-align:left">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="submit" name="update" value="Save" id="submit"/>
        
	</div>    
    <?php echo form_close(); ?>