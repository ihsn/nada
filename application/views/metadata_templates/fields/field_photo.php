<?php if (isset($data) && is_array($data) && count($data)>0 ):?>
<?php
    //resource fields
    $resource_fields=array(
    "title"=>'text',
    "file_name" =>'text',
    "description" =>'text',
    "resource_id" =>'text',
    "survey_id" =>'text'
);    
?>

<style>
.resource-img{
    max-height:400px;
}
</style>

<div class="field resource-photo field-<?php echo $name;?>">
    <div class="field-value" >
    <?php foreach($data as $index=>$resource):?>
        <?php if (in_array($resource['extension'],array('jpg','jpeg','gif','png'))):?>
        <div class="">            
            <img src="<?php echo $resource['download_link'];?>" title="<?php echo $resource['title'];?>" class="img-fluid rounded shadow-sm resource-img border">
        </div>
        <a href="<?php echo $resource['download_link'];?>" target="_blank"><i class="fa fa-download" aria-hidden="true"></i> <?php echo t('Download');?></a>
        <?php return;?>
        <?php endif;?>
    <?php endforeach;?>
    </div>
</div>
<?php endif;?>