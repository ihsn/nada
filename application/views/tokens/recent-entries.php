<style>
    .recent-studies-list-home h5 {
        margin-bottom:0px;
    }
    .survey-row{padding-bottom:0px;}
</style>
<?php

$type_icons=array(
    'survey'=>'fa-database',
    'microdata'=>'fa-database',
    'geospatial'=>'fa-globe-americas',
    'timeseries'=>'fa-chart-line',
    'document'=>'fa-file-alt',
    'table'=>'fa-table',
    'visualization'=>'fa-pie-chart',
    'script'=>'fa-file-code',
    'image'=>'fa-image',
    'video'=>'fa-video',
);
?>
<div class="ui-tabs wb-tab-heading pt-4 pb-2 pr-4 pl-4 mb-4 collection-recent-entries">
<h3><?php echo t('latest_additions');?></h3>
<?php if (isset($rows) && count($rows)>0): ?>

    <?php
    $total_rows=count($rows);
    $counter=0;
    foreach($rows as $row):
    $counter++; ?>

        <div class="survey-row recent-studies-list-home border-bottom">
            <div class="row">
                <div class="col-12 col-lg-12">                    
                    <h5>
                    <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['title']; ?>" >
                        <?php if(isset($row['type'])):?>
                            <i title="<?php echo $row['type'];?>" class="fa <?php echo $type_icons[$row['type']];?> fa-nada-icon wb-title-icon"></i>    
                        <?php endif;?>                        
                            <?php echo $row['title'];?>
                        </a>
                    </h5>
                        <strong><?php echo $row['nation'];?></strong>
                    <?php if (isset($row['authoring_entity']) && $row['authoring_entity']!=''):?>
                        <div class="sub-title"><?php echo $row['authoring_entity'];?></div>
                    <?php endif;?>

                    <div class="survey-stats">                        
                        <span><?php echo date("M d, Y",$row['changed']);?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;?>
    <p>
        <a href="<?php echo site_url();?>/catalog/history" class="btn btn-link btn-sm float-left" >View more »</a>
    </p>
<?php else: ?>
    <div>
        <?php echo t('no_records_found');?>
    </div>
<?php endif; ?>
</div>