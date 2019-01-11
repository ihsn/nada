<style>
.info{border:1px solid gainsboro;}
.info td{
	border:1px solid gainsboro;padding:5px;
}
.info{
	border-collapse:collapsed;
	border-top:1px solid gainsboro;
}
.status-codes{
	margin-top:20px;padding:5px;
	color:gray;font-family:Arial;font-size:11px;
	}
.study-row{
	border:1px solid gainsboro;padding:5px;font-size:12px;margin-bottom:3px;
}	
.survey-count-1 .study-row{border:0px;margin:0px;padding:0px;}

.show-scroll{height:200px;overflow:auto;}
</style>

<div style="text-align:right;">
<a <?php echo ($this->input->get("ajax") ? 'target="_blank"' : '') ;?> href="<?php echo site_url();?>/auth/profile" class="button-light"><?php echo t('view_all_requests');?></a>
</div>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<h2><?php echo t('licensed_dataset_request_status');?></h2>
<?php 
    $css_class='class="success"';
    
    if ($status=='')
    {
        $status='PENDING';
    }
    if (strtolower($status)=='denied'){
        $css_class='class="error"';
    } 
    
?>

<table border="0" class="info" cellspacing="0" cellpadding="5" width="100%">
	<?php if ($surveys):?>
    <tr>
    <td><?php echo t('request_title');?></td>
    <td><?php echo $request_title;?></td>
    </tr>
    <!--
	<tr valign="top">
		<td style="width:150px;">
		<?php echo t('datasets_requested');?> <?php $count=count($surveys); echo ($count>1) ? ': '.$count : '';?>
        </td>
		<td class="survey-count-<?php echo count($surveys);?> ">
        <div class="<?php echo count($surveys)>5 ? 'show-scroll' : '';?>">
		<?php foreach($surveys as $survey):?>
            <div class="study-row">
				<?php echo $survey['nation'];?> - <?php echo $survey['title'] ?> - <?php echo $survey['year_start'];?></div>
        <?php endforeach;?>
        </div>
        </td>
	</tr>
    -->
    <?php endif;?>
	<tr>
		<td nowrap="nowrap"><?php echo t('date_requested');?></td>
		<td><?php echo date($this->config->item('date_format'),$created);?></td>
	</tr>
	<tr>
		<td nowrap="nowrap"><?php echo t('status');?></td>
		<td style="font-weight:bold;"><?php echo t($status);?></td>
	</tr>	
<?php if (isset($comments)): ?>
<?php if ($comments!=""): ?>
	<tr valign="top">
		<td><?php echo t('comments');?></td>
		<td style="color:red;"><?php echo $comments;?></td>
	</tr>	
<?php endif; ?>	
<?php endif; ?>	

<?php if (strtolower($status)=='moreinfo'):?>
	<tr>
		<td nowrap="nowrap"></td>
		<td style="font-weight:bold;">
        <form method="post" action="<?php echo site_url('access_licensed/additional_info/'.$id);?>" style="margin-top:10px;">
        	<label><?php echo t('provide_additonal_info_for_your_request');?></label>
			<textarea style="width:100%;height:200px;" name="moreinfo"></textarea>        
            <div style="margin-top:10px;"><input type="submit" name="submit" value="Submit"/></div>
        </form>
        </td>
	</tr>	
<?php endif;?>

</table>
<p>&nbsp;</p>

