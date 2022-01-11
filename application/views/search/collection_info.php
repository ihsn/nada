<div id="collection-info" class="collectino-info-box wb-ihsn-sidebar-filter mb-2" xstyle="background:#6c757d57;">
    <div class="collection-info-body">
    <a href="<?php echo site_url('catalog/'.$repo['repositoryid'].'/about');?>">
        <div>
            <img alt="" src="<?php echo base_url();?>/<?php echo $repo['thumbnail'];?>" class="img-fluid roundedx mx-auto d-block img-thumbnailx"/>
        </div>
        <h6 class="mt-2"><?php echo $repo['title'];?></h6>
    </a>
        <?php /* <p class="" style="font-size:small"><?php echo $repo['short_text'];?></p> */?>
        
        
        <div class="text-center mt-3">
            <a href="<?php echo site_url('catalog/'.$repo['repositoryid'].'/about');?>" class="btn btn-sm btn-primary btn-block"> <i class="fa fa-info-circle" aria-hidden="true"></i> About collection</a>
        </div>

        <div class="text-center mt-2">
            <a href="<?php echo site_url('catalog');?>" class="btn btn-sm btn-outline-secondary btn-block"> <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> Reset</a>
        </div>

    </div>
</div>