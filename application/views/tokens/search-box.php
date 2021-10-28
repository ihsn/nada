<div class="text-center">
    <div class="collection-search-container">
        <div class="sub-text mt-3 mb-0"><strong><?php echo $total_entries;?></strong> <?php echo t('datasets');?></div>

        <div class="row justify-content-center">
            <div class="col-10 col-md-8 ">
                <form class="wb-search" method="get" action="<?php echo site_url('catalog/'.$repositoryid);?>">
                    <div class="row no-gutters align-items-center wb-controls-wrapper">

                        <!--end of col-->
                        <div class="col">
                            <input type="hidden" name="sort_by" value="rank">
                            <input type="hidden" name="sort_order" value="desc">
                            <input class="form-control form-control-lg form-control-borderless" type="search" placeholder="Keywords..." name="sk">
                        </div>
                        <!--end of col-->
                        <div class="col-auto">
                            <button class="btn btn-lg btn-primary" type="submit"><?php echo t('Search');?></button>
                        </div>
                        <!--end of col-->

                    </div>
                </form>

                <div class="wb-library-search--browse my-3">
                    <a href="<?php echo site_url('catalog/'.$repositoryid);?>"><i class="fa fa-list"></i> <?php echo t('Browse collection');?> </a>
                </div>

                <div>
                </div>
            </div>
            <!--end of col-->
        </div>
    </div>
</div>