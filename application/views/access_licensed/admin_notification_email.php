<?php
/**
* Notification email message for the site administrator on receiving a new licensed request
*
*/
?>

<?php if($request_type='study'):?>

<?php

$study_years=array(
	$surveys[0]['data_coll_start'],
	$surveys[0]['data_coll_end']			
);
$study_years=array_unique($study_years);
$study_years=implode(" - ",$study_years);

$study_title=$surveys[0]['nation']. ' - '.$surveys[0]['titl']; //. ' - '.$study_years;

?>

<p>
    <p><?php echo sprintf(t('user_has_requested_licensed'),$fname . ' ' . $lname, $study_title);?></p>
    <div style="margin:10px;padding:10px;border:1px solid orange;">
    	<h3 style="margin:0px;"><a href="<?php echo site_url('admin/licensed_requests/edit/'.$id);?>"><?php echo $surveys[0]['titl'];?></a></h3>
        <div><?php echo $surveys[0]['nation'];?>, <?php echo $study_years;?></div>
    </div>
    
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