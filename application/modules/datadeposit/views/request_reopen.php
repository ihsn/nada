<style type="text/css">

textarea{min-height:90px;}
</style>
    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
    <h2><?php echo t('project_request_reopen'); ?></h2>
	<?php echo form_open("datadeposit/request_reopen/{$id}");?>
    <div class="field">
		<label style="clear:both" for="reason">Request Reopen:</label>
        <br />
		<textarea rows="5" cols="40" name="reason"></textarea>
	</div>  
 	<div style="text-align:left">
		<input class="button" type="hidden" name="reopen" value="Request" />
	</div>
	<div class="button">
        <span>Send</span>
    </div>
	<?php echo form_close(); ?>

