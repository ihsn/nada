<div class="recent-studies">
<?php if (isset($rows) && count($rows)>0): ?>
<ul class="bl">
	<?php 
		$total_rows=count($rows);
		$counter=0;
	?>
	<?php foreach($rows as $row): ?>
    <?php $counter++; ?>
    <li class="item">
            <div class="title">
                <a href="<?php echo site_url(); ?>/catalog/<?php echo $row['id']; ?>"  title="<?php echo $row['titl']; ?>" >
                        <?php echo $row['nation']. ' - '. $row['titl'];?>
                </a>
            </div>
            <!--
            <div class="sub-title"><?php echo t('by');?> 
                <?php $authenty=json_decode($row['authenty']);?>
                <?php if (is_array($authenty)):?>
                    <?php echo implode(", ",$authenty);?>
                <?php else:?>
                    <?php echo $row['authenty'];?>
                <?php endif;?>
            </div>
            -->
            <div class="created"><?php echo date("M d, Y",$row['created']);?></div>
            
            <?php if (isset($row['repo_title']) && $row['repo_title']!=''):?>
                <div class="sub-title"><?php echo t('catalog_owned_by')?>: <?php echo $row['repo_title'];?></div>
            <?php endif;?>
	</li>
	<?php endforeach;?>
</ul>    
<div class="align-right view-more"><a href="<?php echo site_url();?>/catalog/history">View more...</a></div>
<?php else: ?>
	<div><?php echo t('no_records_found');?></div>
<?php endif; ?>
</div>