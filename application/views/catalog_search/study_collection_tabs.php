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
           

            <!-- Tab panes -->
            <div class="tab-content">

                    <?php echo $content;?>

            </div>

            <!-- /Tab panes -->

        </div>

    </div>
</div>    
<?php endif; ?>
