<?php
$this->template->add_variable("body_class","container-fluid-n");

//get stats
$survey_count=$this->stats_model->get_survey_count();
$variable_count=$this->stats_model->get_variable_count();
$citation_count=$this->stats_model->get_citation_count();

//get top popular surveys
$popular_surveys=$this->stats_model->get_popular_surveys(5);

//get top n recent acquisitions
$latest_surveys=$this->stats_model->get_latest_surveys(5);	

$this->title='Home';
?>

<style>
    h1,h2,h3, h4, h5{font-family:sans-serif}
    h1{
        font-size: 1.9rem;        
    }
    h5{
        font-size: 1.1rem;
    }
    .breadcrumb{display:none;}
    .wb-box-sidebar h3{font-size:18px;}
    .wb-box-sidebar h5{font-size:16px;font-weight:normal;line-height:115%;}
    .site-header{
        margin-bottom:0px;
    }
    
    .card-title {
        /*font-size:15px;*/        
        line-height:24px;
    }
    .card-text{
        color:#464a4cd6;
        font-size:13px;
    }
    .card{
        /*box-shadow:0px 2px 2px 1px #e5e5e5;
        font-family:Open Sans,sans-serif;*/
        border:none;        
    }

    /*.card-collection {
        height:320px;
        overflow:hidden;
    }*/

    .icon-x i{
        font-size:70px;
        /*border:2px solid gainsboro;*/
        padding:25px 25px;
        /*border-radius: 50% !important;*/
        /*background:white;*/
        /*color:#31b0d5;*/
        margin-bottom:10px;        
        /*-moz-border-radius:99px;
        -webkit-border-radius:99px;*/
    }
    .icon-x i:hover{
        /*background:#5bc0de;
        color:white;*/
        cursor:pointer;
    }

    .container-2 h5{
        margin-bottom:0px;
    }

    .latest-additions h3{font-size:1.5rem}
    /*.latest-additions h5{font-weight:normal;font-size:16px;}*/
    .latest-additions{background:white; padding:15px;}

    .latest-additions .survey-row .survey-stats {
        font-size: 0.85rem;
    }

    .sub-text{
        margin-top:25px;
        color:gray;
        font-size:1.2em;
    }

    .sub-text-collections{
        margin-top:0px;
    }
</style>



  


<div class="text-center" class="container-fluid" style="padding-bottom:50px;padding-top:55px;background:#eceeef">
    <div class="container">
<h1>Microdata Library</h1>
<div class="sub-text">Search in <span style="font-weight:bold;"><?php echo $survey_count;?></span> surveys, <span style="font-weight:bold;"><?php echo $variable_count;?></span> variables and <span style="font-weight:bold;"><?php echo $citation_count;?></span> citations</div>


<div class="row justify-content-center">
    <div class="col-10 col-md-8 ">
        <form class="card card-sm" method="get" action="<?php echo site_url('catalog');?>">
            <div class="card-body row no-gutters align-items-center">
                
                <!--end of col-->
                <div class="col">
                    <input type="hidden" name="sort_by" value="rank">
                    <input type="hidden" name="sort_order" value="desc">
                    <input class="form-control form-control-md form-control-borderless" type="search" placeholder="Keywords..." name="sk" >
                </div>
                <!--end of col-->
                <div class="col-auto">
                    <button class="btn btn-md btn-primary" type="submit">Search</button>                     
                </div>
                <!--end of col-->
                
            </div>
        </form>
        <div>        
        </div>
    </div>
    <!--end of col-->
</div>
</div>


<!--
<div class="d-flex justify-content-center mb-5">
    <div class="col-md-8 col-sm-12">

            <div class="input-group shadow">
                <input type="text" class="form-control" placeholder="Search <?php echo $survey_count;?> surveys..." sxtyle="box-shadow:0 .125rem .25rem rgba(0,0,0,.075)!important">
                <div class="input-group-append" style="margin-left:2px;">
                <button class="btn btn-primary" type="button" xstyle="box-shadow:0 .125rem .25rem rgba(0,0,0,.075)!important;padding:10px;">
                    <i class="fa fa-search"></i>
                </button>      
                </div>    
            </div>
            <div style="color:gray;">3000 surveys, 1029238 variables, and 303940394 citations...</div>            
    </div>
    
</div>
-->
</div>

<?php
$collections=array(
    'findex'=>array(
        'title'=> 'Global Financial Inclusion (Global Findex) Database',
        'url'=>'http://google.com',
        'description'=>'The Global Findex is the first public database of indicators that measures people\'s use of financial services across economies and over time. Based on over 150,000 interviews across more than 140 economies, the database can be used to develop a deeper understanding of how people save, borrow, make payments, and manage risk.',
        'thumb'=>'http://microdata.worldbank.org/files/findex-fp-01.jpg'
    ),
    'lsms'=>array(
        'title'=> 'Living Standards Measurement Study (LSMS)',
        'url'=>'http://google.com',
        'description'=>'The LSMS is a research project that was initiated in 1980. It is a response to a perceived need for policy relevant data that would allow policy makers to move beyond simply measuring rates of unemployment, poverty and health care use, for example, to understanding the determinants of these observed social sector outcomes.',
        'thumb'=>'http://microdata.worldbank.org/files/lsms-fp-01.gif'
    ),
    'ipums'=>array(
        'title'=> 'Integrated Public Use Microdata Series (IPUMS)',
        'url'=>'http://google.com',
        'description'=>'IPUMS provides census and survey data from around the world integrated across time and space. IPUMS integration and documentation makes it easy to study change, conduct comparative research, merge information across data types, and analyze individuals within family and community context. Data and services available free of charge.',
        'thumb'=>'http://microdata.worldbank.org/files/ipums-fp-02.jpg'
    ),
    'mics'=>array(
        'title'=> 'UNICEF Multiple Indicator Cluster Surveys (MICS)',
        'url'=>'http://google.com',
        'description'=>'UNICEF supports countries to collect data on the situation of children and women through the Multiple Indicator Cluster Survey (MICS) program. MICS is designed to collect statistically sound, internationally comparable data on child-related indicators.',
        'thumb'=>'http://microdata.worldbank.org/files/mics-fp-01.jpg'
    )

);

?>

<!-- featured collections -->
<div class="container">
<div class="row">
    <div class="col-md-12" style="padding-top:50px;padding-bottom:10px;">
        <h1 class="text-center">Featured collections</h1>
        <div class="text-center sub-text sub-text-collections">
            The Microdata Library is a collection of datasets from the World Bank and other international, regional and national organizations 
        </div>
    </div>
    <?php foreach($collections as $repoid=>$collection):?>
        <div class="col-md-3">
        <div class="card card-collection"  style="margin-bottom:20px;margin-top:10px;">
        <!--<img class="card-img-top" data-src="holder.js/100px180/?text=Image cap" alt="Image cap [100%x180]" src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22286%22%20height%3D%22180%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20286%20180%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_166c15c23f5%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A14pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_166c15c23f5%22%3E%3Crect%20width%3D%22286%22%20height%3D%22180%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2299.4296875%22%20y%3D%2296.6%22%3EImage%20cap%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" data-holder-rendered="true" style="height: 180px; width: 100%; display: block;">-->
        <a href="<?php echo $collection['url'];?>">
            <img src="<?php echo $collection['thumb'];?>" class="card-img-top" style="height: 180px; width: 100%; display: block;"/>
        </a>
        <div class="card-body"  style="padding:10px 0px;">
            <h5 class="card-title"><a href="<?php echo $collection['url'];?>"><?php echo $collection['title'];?></a></h5>
            <!--<p class="card-text"><?php echo $collection['description'];?></p>-->
        </div>
        </div>
        </div>
<?php endforeach;?>
</div>
</div>


<?php /*
<div class="container-fluid" style="padding-top:30px;padding-bottom:40px;background:#f7f7f9;">
<div class="container" >

<!-- featured publications -->
<div class="row">
    <div class="col-md-12" style="display:none;">
        Featured publications
    </div>
    <div class="col-md-8" >
        <div class="row">
        <?php for($i=0;$i<3;$i++):?>
            <div class="col-md-4">
            <div class="card"  style="margin-bottom:20px;margin-top:10px;">
            <!--<img class="card-img-top" data-src="holder.js/100px180/?text=Image cap" alt="Image cap [100%x180]" src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22286%22%20height%3D%22180%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20286%20180%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_166c15c23f5%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A14pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_166c15c23f5%22%3E%3Crect%20width%3D%22286%22%20height%3D%22180%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2299.4296875%22%20y%3D%2296.6%22%3EImage%20cap%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" data-holder-rendered="true" style="height: 180px; width: 100%; display: block;">-->
            <img src="files/working-paper.jpg" class="card-img-top rounded-circle" style="width: 100%; display: block;"/>
            <div class="card-body"  style="padding:10px;">
                <h5 class="card-title">Quick reference Guide for Data Archivists</h5>
                <p style="display:none;" class="card-text">Recommendations for documenting datasets</p>    
            </div>
            </div>
            </div>
        <?php endfor;?>
        </div>        
    </div>
    <div class="col-md-4" >
        <div class="latest-additions">
            <?php
            //list of repos
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
            $this->load->view("catalog_search/recent_studies_list",array('rows'=>$latest_surveys));
            ?>
        </div>
    </div>
</div>

</div>
</div>
*/?>



<!-- apis, knowledge base, terms of use -->
<div class="container-fluid" style="margin-top:20px;padding-top:50px;padding-bottom:40px;background:#eceeef">
<div class="container container-2" >


<div class="row justify-content-md-center">  
    
    <div class="col-md-3 text-center" >
        <a href="api-documentation/catalog" target="_blank">
        <div class="icon-x">
            <i class="fa fa-rocket" aria-hidden="true"></i>
        </div>
       <h5>API</h5>
       <p>Search, browse and export metadata using our API</p>
        </a>
    </div>
    <div class="col-md-3 text-center" >
    <a href="<?php echo site_url('knowledge-base');?>">
    <div class="icon-x"><i class="fa fa-universal-access" aria-hidden="true"></i></div>
      <h5>Knowledge base</h5>
      <p>Find answers to common questions</p>
    </div>
</a>
    <div class="col-md-3 text-center" >
    <a href="<?php echo site_url('terms-of-use');?>">   
    <div class="icon-x"><i class="fa fa-file-text-o" aria-hidden="true"></i></div>
        <h5>Terms of use</h5>
        <p>Find out how you can use microdata</p>
    </div>
</a>    
</div>

</div>
</div>







<div class="container-fluid" style="padding-top:30px;padding-bottom:40px;backgroundx:#eceeef;">
<div class="container" >

<!-- featured publications -->
<div class="row">
    <div class="col-md-12" style="display:none;">
        Featured publications v2
    </div>
    <div class="col-md-8" >
        <div class="latest-additions">
            <?php
            //list of repos
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);
            $this->load->view("catalog_search/recent_studies_list",array('rows'=>$latest_surveys));
            ?>
        </div>
    </div>
    <div class="col-md-4" >
        <div class="row ">
        <?php for($i=0;$i<3;$i++):?>
            <div class="col-md-12">
            
            <!--<div class="card"  style="margin-bottom:20px;margin-top:10px;">            
                <div class="card-body"  style="padding:10px;">
                    <img src="files/working-paper.jpg" class="card-img-left rounded-circle" style="width: 150px; "/>
                    <h5 class="card-title">Quick reference Guide for Data Archivists</h5>
                    <p style="display:none;" class="card-text">Recommendations for documenting datasets</p>    
                </div>
            </div>-->

            <div class="xcard" style="margin-bottom:15px;">
            <div class="row no-gutters">
            <div class="col-auto">
                <img src="files/working-paper.jpg" style="max-width:100px;" class="img-fluid" alt="">
            </div>
            <div class="col">
                <div class="card-block" style="margin-top:0px;padding-top:0px;">
                    <h5 class="card-title">Quick reference Guide for Data Archivists</h5>
                    <p class="card-text">Formulation of microdata dissemination policies and protocols</p>
                </div>
            </div>
            </div>
            </div>

            </div>
        <?php endfor;?>
        </div>        
    </div>
    
</div>

</div>
</div>




<?php return;?>







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
