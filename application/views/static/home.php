<?php
//get stats
$survey_count=$this->stats_model->get_survey_count();
$variable_count=$this->stats_model->get_variable_count();
$citation_count=$this->stats_model->get_citation_count();

//get top popular surveys
$popular_surveys=$this->stats_model->get_popular_surveys(5);

//get top n recent acquisitions
$latest_surveys=$this->stats_model->get_latest_surveys(10);	

$this->title='Home';
?>


<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9">
        <div class="ui-tabs wb-tab-heading pt-4 pb-2 pr-4 pl-4 mb-4">

            <?php
            //list of repos
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
            $this->load->view("catalog_search/recent_studies_list",array('rows'=>$latest_surveys));
            ?>

        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <!-- *****  Stats ***** -->
        <div class="wb-box-sidebar wb-tab-heading pt-3 pb-3 pr-4 pl-4 text-center">

            <p>As of <strong><?php echo date("F d, Y",date("U")); ?></strong><br> the catalog contains</p>
            <h3 class="mb-0"><?php echo number_format($survey_count);?></h3>
            <p>Surveys</p>
            <?php if ($variable_count>0):?>
                <h3 class="mb-0"><?php echo number_format($variable_count);?> </h3>
                <p>Variables</p>
            <?php endif;?>
            <?php if ($citation_count>0):?>
            <h3 class="mb-0"><?php echo number_format($citation_count);?></h3>
            <p>Citations</p>
            <?php endif;?>
            <a class="btn btn-primary" href="<?php echo site_url('catalog/central');?>" title="Data catalog">Data Catalog</a>

        </div>
        <?php if (isset($popular_surveys) && is_array($popular_surveys) && count($popular_surveys)>0):?>
            <!-- **** popular studies **** -->
            <div class="wb-box-sidebar wb-tab-heading mt-4 pt-3 pb-3 pr-4 pl-4">
                <h5>Most popular studies</h5>
                <?php foreach($popular_surveys as $survey): ?>
                    <div class="citation-row" data-url="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>">
                        <div class="row">
                            <div class="col-12 row-body">
                                    <span class="sub-title">
                                        <a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>" title="<?php echo $survey['title'];?>">
                                            <?php echo $survey['title'];?>
                                        </a>
                                    </span>
                                <br><span><?php echo $survey['nation'];?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
        <?php endif;?>
    </div>
</div>
