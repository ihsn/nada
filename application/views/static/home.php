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

.popular-studies .row{font-size:14px;}
</style>
<?php
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
                    <p><?php echo isset($data_types[$data_type]) ? $data_types[$data_type] : $data_type;?></p>
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