<style>
.dataset-type {
    border: 1px solid #ced4da;
    padding: 3px 6px;
    /*margin-bottom: 5px;*/
    color: #6c757d;
    font-size: 10px;
    text-transform: uppercase;
    font-weight:normal;
}
.survey-row .survey-stats span{
    font-size:12px;
}

h5{
    font-weight:bold!important;
}

.home-page-search-container{
    background:#dee2e6;
    padding-bottom:30px;
}

.popular-studies .row{font-size:14px;}
</style>
<?php
$this->template->add_variable("body_class","container-fluid-full");
//get stats
$survey_count=$this->stats_model->get_survey_count();
$variable_count=$this->stats_model->get_variable_count();
$citation_count=$this->stats_model->get_citation_count();

$counts=$this->stats_model->get_counts_by_type();

//get top popular surveys
$popular_surveys=$this->stats_model->get_popular_surveys(5);

//get top n recent acquisitions
$latest_surveys=$this->stats_model->get_latest_surveys(10);	

$this->title='Home';

$data_types=array(
    'survey'=>'Microdata',
    'table'=>'Tables',
    'document'=>'Documents',
    'script'=>'Scripts',
    'geospatial'=>'Geospatial',
    'video'=>'Videos',
    'image'=>'Images',
    'timeseries'=>'Timeseries'
);

?>


<div class="text-center  wb-library-search mb-2 py-2 pt-3">
    <div class="home-page-search-container">
        <h1 class="wb-library-search-title pt-5 pb-3"><?php echo $this->config->item("website_title"); ?></h1>
        <div class="sub-text mt-3 mb-0">Search in <strong><?php echo $survey_count; ?></strong> datasets</div>

        <div class="row justify-content-center">
            <div class="col-10 col-md-8 ">
                <form class="wb-search" method="get" action="<?php echo site_url('catalog'); ?>">
                    <div class="row no-gutters align-items-center wb-controls-wrapper">

                        <!--end of col-->
                        <div class="col">
                            <input type="hidden" name="sort_by" value="rank">
                            <input type="hidden" name="sort_order" value="desc">
                            <input class="form-control form-control-lg form-control-borderless" type="search" placeholder="Keywords..." name="sk">
                        </div>
                        <!--end of col-->
                        <div class="col-auto">
                            <button class="btn btn-lg btn-primary" type="submit">Search</button>
                        </div>
                        <!--end of col-->

                    </div>
                </form>

                <div class="wb-library-search--browse my-3">
                    <a href="<?php echo site_url('catalog'); ?>"><i class="fa fa-list"></i> Browse Catalog </a>
                </div>

                <div>
                </div>
            </div>
            <!--end of col-->
        </div>
    </div>
</div>


<?php /*
//text intro block
<div class="container">
    <div class="text-center  py-3" >
        <p class="text-dark" style="font-size:16px;">NADA is an open-source application ...</p>
    </div>
</div>
*/ ?>

<?php /*
<div class="container-fluid wb-features-list mb-5">
    <div class="container container-2">
        <div class="row justify-content-md-center">
            <div class="col-4">
                <div class="card wb-card--solid text-center wb-features-item">
                    <a href="<?php echo site_url('catalog');?>">
                        <div class="icon-x">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5>Browse Catalog</h5>
                    </a>
                </div>
            </div>


            <div class="col-4">
                <div class="card wb-card--solid text-center wb-features-item">
                    <a href="<?php echo base_url();?>api-documentation/catalog">
                        <div class="icon-x">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h5>API</h5>
                    </a>
                </div>
            </div>

        
            <div class="col-4">
                <div class="card wb-card--solid text-center wb-features-item">
                    <a href="https://github.com/ihsn/nada" target="_blank">
                        <div class="icon-x">
                        <i class="fas fa-download"></i>
                        </div>
                        <h5>Download NADA</h5>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

*/?>

<div class="container">
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9">
        <div class="ui-tabs wb-tab-heading pt-4 pb-2 pr-4 pl-4 mb-4">

            <?php
            //list of repos
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
            $this->load->view("static/recent_studies_list",array('rows'=>$latest_surveys));
            ?>

        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <!-- *****  Stats ***** -->
        <?php if (is_array($counts)):?>
        <div class="wb-box-sidebar wb-tab-heading pt-3 pb-3 pr-4 pl-4 text-center mb-3">

            <p>As of <strong><?php echo date("F d, Y",date("U")); ?></strong><br> the catalog contains</p>
            <?php foreach($counts as $data_type=>$count):?>
                <?php if($count>0):?>
                    <h3 class="mb-0"><?php echo number_format($count);?></h3>
                    <p><a href="<?php echo site_url('catalog/?tab_type='.$data_type);?>"><?php echo isset($data_types[$data_type]) ? $data_types[$data_type] : $data_type;?></a></p>
                <?php endif;?>
            <?php endforeach;?>
            
            <a class="btn btn-primary btn-block" href="<?php echo site_url('catalog/central');?>" title="Data catalog">Catalog</a>
        </div>
        <?php endif;?>
        <?php if (isset($popular_surveys) && is_array($popular_surveys) && count($popular_surveys)>0):?>
            <!-- **** popular studies **** -->
            <div class="wb-box-sidebar popular-studies wb-tab-heading pt-3 pb-3 pr-4 pl-4">
                <h5 class="pb-3">Most popular studies</h5>
                <?php foreach($popular_surveys as $survey): ?>
                    <div class="study-row mb-3 pb-3 border-bottom" data-url="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>">
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
</div>