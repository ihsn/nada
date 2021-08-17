<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<?php
    //require fields passed via data
    //video_provider, video_url, embed_url
    
    $columns=array(
        'video_provider',
        'video_url',
        'embed_url'
    );

?>

<style>
</style>

<div class="field video-field field-<?php echo $name;?>">
    <div class="field-value" >
    <div class="embed-responsive embed-responsive-16by9">
        <iframe class="embed-responsive-item" src="<?php echo $data['embed_url'];?>" allowfullscreen></iframe>
    </div>
    </div>
</div>
<?php endif;?>