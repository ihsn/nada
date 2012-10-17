<style type="text/css">
.contents{
		width:100%;
		min-height: 500px;
	}
.contents .field{
	margin:15px 2px;	
}
.field input, .field textarea {
    margin-left: 20px;
    width:       45%;
}

div.width p, div.width em {
	margin:0;float:right;font-size:12pt
}

.field label {
			font-weight: bold;
			font-size: 10pt;
}

.contents label{
		background:#CCC;
		display:block;
		margin:5px 0px;
		padding:3px;
		font-weight:bold;
	}
textarea{min-height:90px;}
</style>
    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
    <h2><?php echo t('project_request_reopen'); ?></h2>
	<?php echo form_open("projects/request_reopen/{$id}");?>
    <div class="field">
		<label style="clear:both" for="reason">Request Reopen:</label>
		<textarea rows="5" cols="40" name="reason"></textarea>
	</div>  
 	<div style="text-align:right;margin:5px 20px;">
		<input class="button" type="submit" name="reopen" value="Reopen" id="submit"/>
	</div>
	<?php echo form_close(); ?>

