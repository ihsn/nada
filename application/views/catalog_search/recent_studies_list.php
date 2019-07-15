<h3><?php echo t('latest_additions');?></h3>
<?php if (isset($rows) && count($rows)>0): ?>

    <?php
    $total_rows=count($rows);
    $counter=0;
    foreach($rows as $row):
    $counter++; ?>

        <div class="survey-row">
            <div class="row">
                <div class="col-12 col-lg-12">
                    <h5>
                        <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['title']; ?>" >
                            <?php if(isset($row['nation']) && $row['nation']!=''):?>
                                <?php echo $row['nation']. ' - ';?>
                            <?php endif;?>
                            <?php echo $row['title'];?>
                        </a>
                    </h5>

                    <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                        <div class="sub-title"><?php echo t('catalog_owned_by')?>: <?php echo $row['repo_title'];?></div>
                    <?php endif;?>

                    <div class="survey-stats"><span><?php echo date("M d, Y",$row['created']);?></span>
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
