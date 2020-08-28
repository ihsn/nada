<?php if (!isset($row[0]->id) ||  !isset($project[0])): ?>
	<?php show_error('PROJECT_NOT_FOUND');?>
<?php endif; ?>

<?php if (isset($_GET['print']) && $_GET['print'] == 'yes'): ?>
<script type="text/javascript" src="<?php echo site_url(); ?>/../javascript/jquery/jquery.js"></script>
<?php endif; ?>

<?php 
$access_policy_options=$this->config->item('access_policy_options','datadeposit');
$to_catalog_options=$this->config->item('to_catalog_options','datadeposit');
?>

<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>




  
<div id="tabs-2" class="contents page-review-submit">

    <div>
    	<?php echo ($project[0]->access == 'owner') ? t('instructions_project_submit') : t('instructions_project_contributor_review'); ?>
    </div>
 
    <form method="post" class="form clearfix project-submit">

    <div class="field">
        <label for="accesspolicy">            
			<?php echo t('choose_access_policy'); ?>
        </label><br/>
		<?php echo form_dropdown('access_policy', $access_policy_options, set_value('access_policy', @$project[0]->access_policy));?>
        <div class="description"><?php echo t('suggested_access_policy_help'); ?></div>
    </div>


	<?php if(is_array($to_catalog_options)):?>
    <div class="field">
        <label for="to_catalog">            
            <?php echo t('catalog_to_publish'); ?> <span class="required">*</span>
        </label><br/>
    	<?php echo form_dropdown('to_catalog', $to_catalog_options, set_value('to_catalog', @$project[0]->to_catalog));?>
        <div class="description" ><?php echo t('catalog_to_publish_help'); ?></div>    		
	</div>
	<?php endif;?>
    
    <div class="field">
    	<div class="fieldset-embargoed">
            <input id="is_embargoed" class="is_embargoed" type="checkbox" <?php if (get_form_value('is_embargoed', @$project[0]->is_embargoed)) echo 'checked="checked"'; ?>  name="is_embargoed" value="isembargoed">
            <label for="is_embargoed" style="display:inline;">
                <?php echo t('embargoed'); ?> 
            </label>
		<div class="description"><?php echo t('is_embargoed_help'); ?></div>
		

        <div class="embargoed field">
        	<label for="embargoed">
				<?php echo t('notes_to_embargoed'); ?>          
            </label>			
			<textarea name="embargoed"  class="input-flex" ><?php echo get_form_value('embargoed',@$project[0]->embargoed); ?></textarea>
            <div class="description" ><?php echo t('notes_to_embargoed_help'); ?></div>
    	</div>
        </div>
    </div>

    <div class="field">
        <label for="title"><?php echo t('disclosure_risk'); ?></label>
    	<textarea name="disclosure_risk" id="ccsubmit" class="input-flex"><?php echo get_form_value('disclosure_risk',@$project[0]->disclosure_risk); ?></textarea>
        <div class="description" ><?php echo t('disclosure_risk_help'); ?></div>
    </div>


	<div class="field">
        <label for="title"><?php echo t('key_variables'); ?></label>
    	<textarea name="key_variables" id="key_variables" class="input-flex"><?php echo get_form_value('key_variables',@$project[0]->key_variables); ?></textarea>
        <div class="description" ><?php echo t('key_variables_help'); ?></div>
    </div>

	<div class="field">
        <label for="title"><?php echo t('sensitive_variables'); ?></label>
    	<textarea name="sensitive_variables" id="sensitive_variables" class="input-flex"><?php echo get_form_value('sensitive_variables',@$project[0]->sensitive_variables); ?></textarea>
        <div class="description" ><?php echo t('sensitive_variables_help'); ?></div>
    </div>

    <div class="field">
 	   <label for="notes_to_library"><?php echo t('notes_to_library'); ?></label>
    	<textarea name="library_notes"  class="input-flex" ><?php echo get_form_value('library_notes',@$project[0]->library_notes); ?></textarea>
    	<div class="description" ><?php echo t('notes_to_library_help'); ?></div>        
    </div>

    <div class="field">
        <label for="title">CC</label>
    	<input name="cc" type="text" id="ccsubmit" class="input-flex" value="<?php echo get_form_value('cc',@$project[0]->cc); ?>"/>
        <div class="description" ><?php echo t('cc_help'); ?></div>        
    </div>
    
    <div class="field clearfix">
    	<input type="hidden" name="submit_project" value="Save and submit" id="save" class="button <?php echo ($project[0]->access == 'owner') ? t('submit') : t('save'); ?>"/>
	</div>
        
    <?php //if ($project[0]->access == 'owner'): ?>
    	<div id="confirm" style="display:none;" class="confirm-message">
            <p><?php echo t('confirm_submission'); ?></p>
            <input type="button" name="submit_project" value="Submit" class="submit-button" id="final_submit" onclick="$('input#draft').remove();$(this).remove();$('.form').submit();">
                <a id="cancel" href="#cancel" onclick="return false;"><?php echo t('cancel'); ?></a>
            <br /><br />
    	</div>
	<?php //endif; ?>
	
    <?php //if ($project[0]->access == 'owner') :?>
    	<input type="button" name="confirm_submit" value="Submit" class="submit-button" id="first_submit">
    <?php //endif;?>
    
	</form>
</div>


 <script type="text/javascript">
	$(document).ready(function() {
		
		/* Help doing fieldset expand and collapse*/
		$('.field-expanded > legend').click(function(e) {
			e.preventDefault();
			$(this).parent('fieldset').toggleClass("field-collapsed");
			return false;
		});
			
		/*submit click*/
		$("#first_submit").click(function() {
			$(this).css('display', 'none');
			$("#confirm").css('display', 'block');
		});
		$("#cancel").click(function() {
			$("#confirm").css('display', 'none');
			$("#first_submit").css('display', 'block');
		});

		$('form').submit(function() {
			$('.changedInput').removeClass('changedInput');
		});
		
		$('.button').click(function() {
			$('.changedInput').removeClass('changedInput');
		});
		
		$(window).on('load',function() {
			if ($('.is_embargoed').is(':checked')) {
				$('.embargoed').css('display', 'block');
			}
		});

		$('input[name="is_embargoed"]').change(function() {
			if ($('.embargoed').css('display') == 'none') {
				$('.embargoed').css('display', 'block');
			} else {
				$('.embargoed').css('display', 'none');
			}
		});

		$('img[alt="help"]').click(function() {
			var help_item = $(this).parent().parent().next('.HelpMsg');
			if (help_item.css('display') == 'none') {
				help_item.css('display', 'block');
			} else {
				help_item.css('display', 'none');
			}
		});

		
	});//end ready

</script>
