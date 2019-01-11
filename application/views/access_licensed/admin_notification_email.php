<?php
/**
* Notification email message for the site administrator on receiving a new licensed request
*
*/
?>

<p>
    <p><?php echo sprintf(t('user_has_requested_licensed'),$fname . ' ' . $lname, $request_title);?></p>
    <div style="margin:10px;padding:10px;border:1px solid orange;">
		<h3 style="margin:0px;"><a href="<?php echo site_url('admin/licensed_requests/edit/'.$id);?>"><?php echo $request_title;?></a></h3>
		<?php if (count($surveys)==1):?>
		<?php $survey=reset($surveys);?>
			<div><?php echo $survey['nation'];?>, <?php echo $survey['year_start'];?> <?php echo isset($survey['year_end']) ? ' - '.$survey['year_end'] : '';?></div>
		<?php else:?>
		<ul>
			<?php foreach($surveys as $survey):?>
				<li><b><?php echo $survey['title'];?></b><br/>
					<?php echo $survey['nation'];?>, <?php echo $survey['year_start'];?> <?php echo isset($survey['year_end']) ? ' - '.$survey['year_end'] : '';?>
					<br/><br/>
				</li>
			<?php endforeach;?>
		</ul>
		<?php endif;?>
    </div>
    
    <p><?php echo t('to_view_request_instructions');?><p>
    <p>-- </p>
    <p><?php echo t('Website');?>: <a href="<?php echo site_url();?>"><?php echo site_url();?></a></p>
</p>
