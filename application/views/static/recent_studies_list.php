<section class="home-latest">
    <div class="home-section-header">
        <h2 class="home-section-title"><?php echo t('latest_additions');?></h2>
        <a class="home-section-link" href="<?php echo site_url('catalog/history');?>"><?php echo t('home_view_all');?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>

<?php if (isset($rows) && count($rows)>0): ?>
    <ul class="home-latest-list">
    <?php foreach($rows as $row): ?>
        <?php
            $dates=implode(" - ", array_unique(array_filter(array($row['year_start'],$row['year_end']))));
            $coverage=implode(", ", array_filter(array($row['nation'],$dates)));
            $thumbnail=study_thumbnail_url($row);
        ?>
        <li class="home-latest-item">
            <span class="home-latest-media">
                <?php if($thumbnail):?>
                    <img src="<?php echo $thumbnail;?>" alt="" loading="lazy">
                <?php else:?>
                    <i class="fas <?php echo isset($type_icons[$row['type']]) ? $type_icons[$row['type']] : 'fa-folder';?>" aria-hidden="true"></i>
                <?php endif;?>
            </span>
            <div class="home-latest-body">
                <a class="home-latest-title" href="<?php echo site_url('catalog/'.$row['id']); ?>"><?php echo html_escape($row['title']);?></a>
                <?php if(!empty($row['subtitle'])):?>
                    <div class="home-latest-subtitle"><?php echo html_escape($row['subtitle']);?></div>
                <?php endif;?>
                <div class="home-latest-meta">
                    <span class="home-latest-type">
                        <i class="fas <?php echo isset($type_icons[$row['type']]) ? $type_icons[$row['type']] : 'fa-folder';?>" aria-hidden="true"></i>
                        <?php echo t('tab_'.$row['type']);?>
                    </span>
                    <?php if($coverage!==''):?>
                        <span><?php echo html_escape($coverage);?></span>
                    <?php endif;?>
                    <?php if(!empty($row['authoring_entity'])):?>
                        <span class="home-latest-authors"><?php echo html_escape($row['authoring_entity']);?></span>
                    <?php endif;?>
                    <span class="home-latest-date"><?php echo date("M d, Y",$row['created']);?></span>
                </div>
            </div>
        </li>
    <?php endforeach;?>
    </ul>
<?php else: ?>
    <p><?php echo t('no_records_found');?></p>
<?php endif; ?>
</section>
