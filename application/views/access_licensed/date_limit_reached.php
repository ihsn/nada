<h1><?php echo t('title_download_expired');?></h1>
<p>
    <?php echo sprintf(t('msg_date_limit_reached'), $expiry, mailto($email));?>
</p>
