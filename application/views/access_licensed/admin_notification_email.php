<?php
/**
* Notification email message for the site administrator on receiving a new licensed request
*
*/
?>
<?php if($request_type='study'):?>
<p>
    <p><?php echo sprintf(t('user_has_requested_licensed'),$fname . ' ' . $lname, $surveys[0]['titl']);?></p>
    <p><?php echo t('to_view_request_instructions');?><p>
    <p>-- </p>
    <p><?php echo t('Website');?>: <a href="<?php echo site_url();?>"><?php echo site_url();?></a></p>
</p>
<?php else:?>
<p>
    <p><?php echo sprintf(t('user_has_requested_licensed'),$fname . ' ' . $lname, 'collection['.$collection['title'].']');?></p>
    <p><?php echo t('to_view_request_instructions');?><p>
    <p>-- </p>
    <p><?php echo t('Website');?>: <a href="<?php echo site_url();?>"><?php echo site_url();?></a></p>
</p>
<?php endif;?>