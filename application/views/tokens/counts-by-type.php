<?php

$data_types=array(
    'survey'=>'Microdata',
    'table'=>'Tables',
    'document'=>'Documents',
    'script'=>'Scripts',
    'geospatial'=>'Geospatial',
    'video'=>'Videos',
    'image'=>'Images',
    'timeseries'=>'Timeseries'
);

?>

<?php if (is_array($counts)):?>
<div class="wb-box-sidebar wb-tab-heading pt-3 pb-3 pr-4 pl-4 text-center mb-3">

    <p>As of <strong><?php echo date("F d, Y",date("U")); ?></strong><br> <?php echo !empty($repositoryid) ? t('the collection contains') : t('the catalog contains');?> </p>
    <?php foreach($counts as $data_type=>$count):?>
        <?php if($count>0):?>
            <h3 class="mb-0"><?php echo number_format($count);?></h3>
            <p><a href="<?php echo site_url('catalog/'.$repositoryid.'?tab_type='.$data_type);?>"><?php echo isset($data_types[$data_type]) ? $data_types[$data_type] : $data_type;?></a></p>
        <?php endif;?>
    <?php endforeach;?>
    
    <a class="btn btn-primary btn-block" href="<?php echo site_url('catalog/'.$repositoryid);?>" title="Data catalog"><?php echo !empty($repositoryid) ? t('Browse collection') : t('Browse catalog');?></a>
</div>
<?php endif;?>