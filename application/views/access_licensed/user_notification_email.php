<?php
/**
* Notification email message for the user when request is updated by the administrator
*
*/
?>
<p>
	<p><?php echo t('dear');?> <?php echo $fname . ' ' . $lname; ?>,</p>
    <p><?php echo sprintf(t('request_licensed_reviewed'),$survey_title);?></p>
    <p><b><?php echo anchor('access_licensed/track/'.$requestid.'/', site_url().'/access_licensed/track/'.$requestid);?></b>.</p>
    <p>-- NADA </p>
</p>