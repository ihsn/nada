<?php
//active tab=active_tab
$active_class="active";
if(!isset($active_tab))
{
	$active_tab='catalog';
}
if (isset($repo) && isset($repo['repositoryid'])){
}
else{
	$repo=array(
        'repositoryid'	=>'central',
        'title'			=>t('central_data_catalog')
    );
}

?>


<?php if($active_tab == 'catalog'): ?>
    <div class="col-12 col-md-8 col-lg-9 mt-3 catalog-container">
        <?php if(isset($repo['ispublished']) && intval($repo['ispublished'])===0):?>
            <div class="content-unpublished"><?php echo t('content_is_not_published');?></div>
        <?php endif;?>
    <div class="row">
        <div class="col-sm-12">
            <h1 class="desktop-viewport"><?php echo $repo['title'];?></h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs wb-nav-tab-space mb-5" role="tablist">
                <?php if (isset($repo) && isset($repo['repositoryid']) && $repo['repositoryid']=='central'):?>
                    <?php if(isset($repositories) && count($repositories)>0):?>
                    <li class="nav-item tab-about">
                        <a class="nav-link <?php echo ($active_tab=='about') ? $active_class : '' ?>"   href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" role="presentation"><?php echo t('tab_collections');?></a>
                    </li>
                    <?php endif;?>
                <?php else:?>
                    <li class="nav-item tab-about">
                        <a class="nav-link <?php echo ($active_tab=='about') ? $active_class : '' ?>"  href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" role="presentation"><?php echo t('tab_about');?></a>
                    </li>
                <?php endif;?>
                    <li class="nav-item tab-catalog">
                        <a class="nav-link <?php echo ($active_tab=='catalog') ? $active_class : '' ?>"  href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>" role="presentation"><?php echo t('tab_datasets');?></a>
                    </li>
                <?php if (isset($repo_citations_count) && $repo_citations_count > 0):?>
                    <li class="nav-item tab-citations">
                        <a class="nav-link <?php echo ($active_tab=='citations') ? $active_class : '' ?>"  href="<?php echo site_url('citations/?collection='.$repo['repositoryid']);?>" role="presentation"><?php echo t('tab_citations');?></a>

                    </li>
                <?php endif;?>
            </ul>
            <!-- / Nav Tabs -->

            <!-- Tab panes -->
            <div class="tab-content">
                <!-- Datasets Tab -->
                <div id="surveys" class="tab-pane active" role="tabpanel">
                    <?php echo $content;?>
                </div>
                <!-- / Datasets Tab -->
            </div>
            <!-- /Tab panes -->

        </div>
    </div>
</div>
<?php else: ?>
<div class="<?php echo ($active_tab=='citations') ? 'col-md-9' : 'col-sm-12';?> mt-3">
    <div class="row">
        <div class="col-sm-12">
            <?php if(isset($repo['ispublished']) && intval($repo['ispublished'])===0):?>
                <div class="content-unpublished"><?php echo t('content_is_not_published');?></div>
            <?php endif;?>
            <h1><?php echo $repo['title'];?></h1>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs wb-nav-tab-space mb-5" role="tablist">

                <?php if (isset($repo) && isset($repo['repositoryid']) && $repo['repositoryid']=='central'):?>
                    <?php /* ?>
                    <li class="nav-item tab-about">
                        <a class="nav-link <?php echo ($active_tab=='about') ? $active_class : '' ?>"   href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" role="presentation"><?php echo t('tab_collections');?></a>
                    </li>
                    <?php */?>
                <?php else:?>
                    <li class="nav-item tab-about">
                        <a class="nav-link <?php echo ($active_tab=='about') ? $active_class : '' ?>"  href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>/about" role="presentation"><?php echo t('tab_about');?></a>
                    </li>
                <?php endif;?>
                <li class="nav-item tab-catalog">
                    <a class="nav-link <?php echo ($active_tab=='catalog') ? $active_class : '' ?>"  href="<?php echo site_url('catalog/'.$repo['repositoryid']);?>" role="presentation"><?php echo t('tab_datasets');?></a>
                </li>
                <?php //if (isset($repo_citations_count) && $repo_citations_count > 0):?>
                    <li class="nav-item tab-citations">
                        <a class="nav-link <?php echo ($active_tab=='citations') ? $active_class : '' ?>"  href="<?php echo site_url('citations/?collection='.$repo['repositoryid']);?>" role="presentation"><?php echo t('tab_citations');?></a>
                    </li>
                <?php //endif;?>
            </ul>
            <!-- / Nav Tabs -->

            <!-- Tab panes -->
            <div class="tab-content">

                    <?php echo $content;?>

            </div>

            <!-- /Tab panes -->

        </div>

    </div>
</div>    
<?php endif; ?>
