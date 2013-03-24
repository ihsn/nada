<style>
	.info td{
		border-bottom:1px solid gainsboro;padding:5px;
	}
	.info{
		border-collapse:collapsed;
		border-top:1px solid gainsboro;
	}
.status-codes{
	margin-top:20px;padding:5px;
	color:gray;font-family:Arial;font-size:11px;
	}
</style>

<div style="text-align:right;">
<a <?php echo ($this->input->get("ajax") ? 'target="_blank"' : '') ;?> href="<?php echo site_url();?>/auth/profile" class="button-light"><?php echo t('view_all_requests');?></a>
</div>

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
	<?php if ($request_type=='study'):?>
	<tr>
		<td style="width:150px;"><?php echo t('survey_title');?></td>
		<td><?php echo $survey['titl'];?></td>
	</tr>
    <?php else:?>
	<tr>
		<td style="width:150px;"><?php echo t('Collection');?></td>
		<td><?php echo $collection['title'];?></td>
	</tr>    
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
</table>
<p>&nbsp;</p>