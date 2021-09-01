<style>
    .recent-studies-list-home h5 {
        margin-bottom:0px;
    }
    .survey-row{padding-bottom:0px;}
</style>
<h3><?php echo t('latest_additions');?></h3>
<?php
    $regional_search=($this->config->item("regional_search")===FALSE) ? 'no' : $this->config->item("regional_search");
?>
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
                            <?php echo $row['title'];?>
                        </a>
                        <?php if(isset($row['type'])):?>
                            <span class="dataset-type"><?php echo $row['type']?></span>
                        <?php endif;?>
                    </h5>

                    <?php if($row['nation'] && $regional_search===true) :?>
                        <strong><?php echo $row['nation'];?></strong>
                    <?php endif;?>

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
