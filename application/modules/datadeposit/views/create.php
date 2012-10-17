<script type="text/javascript">
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
$(function() {
$('div.button').click(function() {
	$('.changedInput').removeClass('changedInput');
});
		
$('input, textarea, select, option').checkChanges('Your data will be unsaved.', false);
$('.button-add').checkChanges('Your data will be unsaved.', true);
});
</script>
<style type="text/css">

textarea{min-height:90px;}
</style>
     
     <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
     
    <h1><?php echo t('add_new_project');?></h1>
    
    <div class="contents">
		<?php echo form_open('datadeposit/create');?>
        
        <div class="field">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" class="input-flex" value="<?php echo get_form_value('title','');?>"/>
        </div>
        
        <div class="field">
            <label for="name">Short name:</label>
            <input type="text" name="name" id="name" class="input-flex" value="<?php echo get_form_value('name','');?>"/>
        </div>	
       <?php /* 
        <div class="field">
            <label for="datatype">Type of Data:</label>
            <?php echo form_dropdown('datatype', $option_types, get_form_value('data_type','')); ?>
        </div>
        */ ?>
        <div class="field">
            <label for="description">Description:</label>
            <textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo get_form_value('description','');?></textarea>
        </div>
        
        <div class="field">
            <label for="collaboration">Collaboration:</label>
            <input type="text" name="collaborators" id="collaboration" cols="30" rows="5" class="input-flex" value="<?php echo get_form_value('collaborators','');?>"/>
        </div>
        <br/>
               <div class="button">
        <span>Save</span>
    </div>
        <div style="text-align:left;">
            <input class="button" type="hidden" name="create" value="Save" />
        <a class="btn_cancel" style="position:relative;top:10px;font-size:14px" href="<?php echo site_url('datadeposit/projects');?>">Cancel</a>
        </div>

        <?php echo form_close(); ?>
	</div>