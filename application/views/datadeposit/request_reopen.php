<style type="text/css">
*+html .goback {
	margin-top:-15px !important;
}
textarea{min-height:90px;}
.project-reopen{
background: #D3E0EB;
padding:15px;
}

.project-reopen .info-box{padding:0px;margin:0px;margin-bottom:20px;}
</style>
<?php $message=isset($message)?$message:$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
<h2><?php echo t('project_request_reopen'); ?></h2>

<?php 
	$show_form=false;
	$show_status_codes=array('closed','submitted','accepted');
	if (in_array($project[0]->status,$show_status_codes))
	{
		$show_form=true;
	}
?>

<div class="project-reopen project-status-<?php echo $project[0]->status;?>">

<?php if ($project[0]->status=='processed'):?>
	<div class="info-box"><?php echo t('project_processed');?></div>
<?php endif;?>

<?php if (in_array($project[0]->status,$show_status_codes)):?>
	<div class="info-box"><?php echo sprintf(t('project_locked_message'), date('M d, Y', $project[0]->submitted_on));?></div>
<?php endif;?>


<?php if ($show_form):?>
<form method="post" action="<?php echo site_url('datadeposit/request_reopen/'.$id);?>" >
    <div class="field">
		<label style="clear:both" for="reason"><span style="color:red;">*</span> Request reopen reason:</label>
        <br />
		<textarea rows="5" cols="90" name="reason"></textarea>
	</div>  
 	<div style="text-align:left">
		<input class="button" type="hidden" name="reopen" value="Request" />
	</div>
    
	<input type="submit" name="submit" value="Submit" class="submit-button" id="submit">    
    <a href="<?php echo site_url('datadeposit/projects');?>">Cancel</a>
</form>
<?php endif;?>
</div>