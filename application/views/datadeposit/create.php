<style type="text/css">
	#project_name {
		margin-top:90px;
		margin-bottom: -109px;
	}
	
	#color1 span {
		top: 15px !important;
	}
	#color2 span {
		top: 15px !important;
	}
	#color3 span {
		top: 4px !important;
		left: 25px !important;
		color: #CACACA !important;
	}
	#color4 span {
		top: 15px !important;
		left: 25px !important;
	}

	#color5 span {
		top: 15px !important;
		left: 20px !important;
	}
	
	#color6 span {
		top: 22px !important;
		left: 30px !important;
	}

	
</style>
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
$('#color1 a').click(function() {
	return false;
});
$('form').submit(function() {
	$('.changedInput').removeClass('changedInput');
});
$('input, textarea, select, option').checkChanges('Your data will be unsaved.', false);
$('.button-add').checkChanges('Your data will be unsaved.', true);
</script>
<style type="text/css">

textarea{min-height:90px;}
</style>
<?php $message=isset($message)?$message:$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="error">'.$message.'</div>' : '';?> 
         
    <div class="contents">
		<?php echo form_open('datadeposit/create');?>
        
        <div class="field">
            <label for="title"><span class="required">*</span>Title:</label>
            <p class="text_help"><?php echo t('create_title'); ?></p>
            <input type="text" name="title"  class="input-flex" value="<?php echo get_form_value('title','');?>"/>
        </div>
        
        <div class="field">
            <label for="name"><span class="required">*</span>Short name:</label>
            <p class="text_help"><?php echo t('create_short'); ?></p>            
            <input type="text" name="name" style="width:30%" class="input-flex" value="<?php echo get_form_value('name','');?>"/>
        </div>	
       <?php /* 
        <div class="field">
            <label for="datatype">Type of Data:</label>
            <?php echo form_dropdown('datatype', $option_types, get_form_value('data_type','')); ?>
        </div>
        */ ?>
        <div class="field">
            <label for="description">Description:</label>
            <p class="text_help"><?php echo t('create_desc'); ?></p>                        
            <textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo get_form_value('description','');?></textarea>
        </div>
        
        <div class="field">
            <label for="collaboration">Collaboration:</label>
            <p class="text_help"><?php echo t('create_collab'); ?></p>            
            <input type="text" name="collaborators[]" value="<?php echo get_form_value('collaborators[]','');?>" class="input-flex" style="width:25%" /> <input type="text" name="collaborators[]" class="input-flex" value="<?php echo get_form_value('collaborators[]','');?>" style="width:25%" />
            <br />
         	<input type="text" value="<?php echo get_form_value('collaborators[]','');?>" name="collaborators[]" class="input-flex" style="width:25%" /> <input type="text" name="collaborators[]" class="input-flex" value="<?php echo get_form_value('collaborators[]','');?>" style="width:25%" />
        </div>
        <br/>
               <div onclick="$('.changedInput').removeClass('changedInput');" class="button">
        <span>Save</span>
    </div>
        <div style="text-align:left;">
            <input class="button" type="hidden" name="create" value="Save" />
        <a class="btn_cancel" style="position:relative;top:5px;font-size:14px" href="<?php echo site_url('datadeposit/projects');?>">Cancel</a>
        </div>

        <?php echo form_close(); ?>
	</div>