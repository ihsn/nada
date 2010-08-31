<?php
/**
* Notification email message for the user when request is updated by the administrator
*
*/
?>
<p>
	<p>Dear <?php echo $fname . ' ' . $lname; ?>,</p>
    <p>Your request for the licensed dataset [<b><?php echo $survey_title; ?></b>] has been reviewed. To view the review outcome, please visit:</p>
    <p><b><?php echo anchor('access_licensed/track/'.$requestid.'/', site_url().'/access_licensed/track/'.$requestid);?></b>.</p>
    <p>-- NADA </p>
</p>