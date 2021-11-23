<div class="search-nav-tabs-container">
<ul class="nav nav-tabs nav-tabs-auto-overflow mb-5 search-nav-tabs">
    <li class="nav-item">
        <a class="dataset-type-tab dataset-type-tab-all nav-link <?php echo $tabs['active_tab']=='' ? 'active' : '';?>" data-value="" href="#"><?php echo t('All');?>
        <span class="type-count-all">&nbsp;</span>
        </a>
    </li>

    <?php 
        $type_icons=array(
            'survey'=>'<i class="fas fa-database" aria-hidden="true"></i>',
            'geospatial'=>'<i class="fas fa-globe-americas" aria-hidden="true"></i>',
            'timeseries'=>'<i class="fas fa-chart-line" aria-hidden="true"></i>',
            'document'=>'<i class="fas fa-file-alt" aria-hidden="true"></i>',
            'table'=>'<i class="fa fa-table" aria-hidden="true"></i>',            
            'visualization'=>'<i class="fas fa-chart-pie" aria-hidden="true"></i>',            
            'script'=>'<i class="fas fa-file-code" aria-hidden="true"></i>',
            'image'=>'<i class="fas fa-image" aria-hidden="true"></i>',
            'video'=>'<i class="fas fa-video" aria-hidden="true"></i>'            
        );
    ?>

    <?php foreach($tabs['types'] as $tab):?>
        <?php 
            $tab_target=site_url("catalog/?tab_type={$tab['code']}");
            if(isset($active_repo) && isset($active_repo['repositoryid'])){
                $tab_target=site_url("catalog/".$active_repo['repositoryid']."?tab_type={$tab['code']}");
            }
        ?>
        <li class="nav-item">
            <a class="dataset-type-tab dataset-type-tab-<?php echo $tab['code'];?> nav-link <?php echo $tab['code']==$tabs['active_tab'] ? 'active' : '';?>" data-value="<?php echo $tab['code'];?>" href="<?php echo $tab_target;?>">
                <?php echo @$type_icons[$tab['code']];?>
                <?php echo t('tab_'.$tab['code']);?>
                <?php if(isset($tabs['search_counts_by_type']) ) :?>
                    <?php $count=0;
                        if (array_key_exists($tab['code'],$tabs['search_counts_by_type'])){
                            $count=$tabs['search_counts_by_type'][$tab['code']];
                        }
                    ?>
                    <span class="type-count"> <?php echo @number_format((int)$count);?> </span>
                <?php endif;?>
            </a>
        </li>
    <?php endforeach;?>
        
    </ul>
</div>