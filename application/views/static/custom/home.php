<?php
$this->template->add_variable("body_class","container-fluid-n");

//get stats
$survey_count=$this->stats_model->get_survey_count();
$variable_count=$this->stats_model->get_variable_count();
$citation_count=$this->stats_model->get_citation_count();

//get top popular surveys
$popular_surveys=$this->stats_model->get_popular_surveys(5);

//get top n recent acquisitions
$latest_surveys=$this->stats_model->get_latest_surveys(6);	

$this->title='Home';
?>

<style>    
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

    .card .card-body{padding:0px;}

    /*.card-collection {
        height:320px;
        overflow:hidden;
    }*/

    .icon-x i{
        font-size:80px;
        /*border:2px solid gainsboro;*/
        padding:25px 32px;
        border-radius: 50% !important;
        background:white;
        color:#31b0d5;
        margin-bottom:25px;        
        -moz-border-radius:99px;
        -webkit-border-radius:99px;
    }
    .icon-x i:hover{
        background:#5bc0de;
        color:white;
        cursor:pointer;
    }

    .container-2 h5{
        margin-bottom:0px;
        color:#31b0d5
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

        <div style="color: #0071bc;margin-top: 10px;"> 
			<a href="<?php echo site_url('catalog');?>"><i class="fa fa-list"></i> Browse Catalog </a> 
		</div>

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
    'eap'=>array(
        'title'=> 'East Asia and Pacific',
        'url'=>site_url('catalog/eap'),
        'description'=>'The Global Findex is the first public database of indicators that measures people\'s use of financial services across economies and over time. Based on over 150,000 interviews across more than 140 economies, the database can be used to develop a deeper understanding of how people save, borrow, make payments, and manage risk.',
        'thumb'=>base_url().'files/public/collections/findex-sm.jpg'
    ),
    'eca'=>array(
        'title'=> 'Europe and Central Asia',
        'url'=>site_url('catalog/eca'),
        'description'=>'The LSMS is a research project that was initiated in 1980. It is a response to a perceived need for policy relevant data that would allow policy makers to move beyond simply measuring rates of unemployment, poverty and health care use, for example, to understanding the determinants of these observed social sector outcomes.',
        'thumb'=>base_url().'files/public/collections/lsms-sm.jpg'
    ),
    'lac'=>array(
        'title'=> 'Latin America and the Caribbean',
        'url'=>site_url('catalog/lac'),
        'description'=>'IPUMS provides census and survey data from around the world integrated across time and space. IPUMS integration and documentation makes it easy to study change, conduct comparative research, merge information across data types, and analyze individuals within family and community context. Data and services available free of charge.',
        'thumb'=>base_url().'files/public/collections/ipums-sm.jpg'
    ),
    'mena'=>array(
        'title'=> 'Middle East and North Africa',
        'url'=>site_url('catalog/mena'),
        'description'=>'UNICEF supports countries to collect data on the situation of children and women through the Multiple Indicator Cluster Survey (MICS) program. MICS is designed to collect statistically sound, internationally comparable data on child-related indicators.',
        'thumb'=>base_url().'files/public/collections/mics-sm.jpg'
    ),
    'sar'=>array(
        'title'=> 'South Asia',
        'url'=>site_url('catalog/sar'),
        'description'=>'The LSMS is a research project that was initiated in 1980. It is a response to a perceived need for policy relevant data that would allow policy makers to move beyond simply measuring rates of unemployment, poverty and health care use, for example, to understanding the determinants of these observed social sector outcomes.',
        'thumb'=>base_url().'files/public/collections/lsms-sm.jpg'
    ),
    'afr'=>array(
        'title'=> 'Sub-Saharan Africa',
        'url'=>site_url('catalog/afr'),
        'description'=>'IPUMS provides census and survey data from around the world integrated across time and space. IPUMS integration and documentation makes it easy to study change, conduct comparative research, merge information across data types, and analyze individuals within family and community context. Data and services available free of charge.',
        'thumb'=>base_url().'files/public/collections/ipums-sm.jpg'
    )

);
?>



<!-- featured collections -->
<div class="container">
<div class="col-md-12" style="padding-top:50px;padding-bottom:10px;">
        <h1 class="text-center">Regional collections</h1>       
    </div>
<div class="row">
    <?php foreach($collections as $repoid=>$collection):?>
        <div class="col">
        <div class="card card-collection"  style="margin-bottom:20px;margin-top:10px;">
        <a href="<?php echo $collection['url'];?>">
            <img src="<?php echo $collection['thumb'];?>" class="img-fluid xcard-img-top" xstyle="height: 180px; width: 100%; display: block;"/>
        </a>
        <div class="card-body"  style="padding:10px 0px;">
            <h5 class="card-title"><a href="<?php echo $collection['url'];?>"><?php echo $collection['title'];?></a></h5>
        </div>
        </div>
        </div>
<?php endforeach;?>
</div>
</div>

<?php /*?>
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
        <a href="<?php echo $collection['url'];?>">
            <img src="<?php echo $collection['thumb'];?>" class="img-fluid xcard-img-top" xstyle="height: 180px; width: 100%; display: block;"/>
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
<?php */ ?>

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
            <img src="http://www.ihsn.org/sites/default/files/styles/thumbnail/public/resources/quick-guide-data-archivist.gif?itok=3pw5WawG" class="card-img-top rounded-circle" style="width: 100%; display: block;"/>
            <div class="card-body"  style="padding:10px;">
                <h5 class="card-title"><a target="_blank" href="http://www.ihsn.org/quick-reference-guide-for-data-archivists">Quick reference Guide for Data Archivists</a></h5>
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
    
<div class="col text-center" >
        
        <a href="<?php echo site_url('datadeposit');?>">
        <div class="icon-x">
            <i class="fa fa-upload" aria-hidden="true"></i>
        </div>
       <h5>Deposit Data</h5>
       <p>Deposit microdata or metadata</p>
        </a>
    
    </div>

    <div class="col text-center" >
        
        <a href="<?php echo base_url();?>api-documentation/catalog">
        <div class="icon-x">
            <i class="fa fa-rocket" aria-hidden="true"></i>
        </div>
       <h5>API</h5>
       <p>Search, browse and export metadata using our API</p>
        </a>
    
    </div>
<!--
    <div class="col text-center" >
    
        <a href="<?php echo site_url('knowledge-base');?>">
        <div class="icon-x"><i class="fa fa-universal-access" aria-hidden="true"></i></div>
        <h5>Knowledge base</h5>
        <p>Find answers to common questions</p>
        </a>
    </div>
    -->

    <div class="col text-center" >
        <a href="https://spappscsec.worldbank.org/sites/ppf3/Pages/previewpage.aspx?DocID=18ec8892-2cd1-458f-8797-50a83313dcac" target="_blank">
        <div class="icon-x"><i class="fa fa-file-text-o" aria-hidden="true"></i></div>
            <h5>Data Access Policy</h5>
            <p>World Bank Access to Information Policy</p>
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
        <?php /* for($i=0;$i<3;$i++): */?>
            <div class="col-md-12">
            <h4>Specialized collections</h4>

            <ul class="list-group list-group-flush">

            <?php foreach($collections as $repoid=>$collection):?>
                <li class="list-group-item"><a href=""><?php echo $collection['title'];?> <i class="fa fa-angle-right" aria-hidden="true"></i></a></li>
            <?php endforeach;?>
            <?php foreach($collections as $repoid=>$collection):?>
                <li class="list-group-item"><a href=""><?php echo $collection['title'];?> <i class="fa fa-angle-right" aria-hidden="true"></i></a></li>
            <?php endforeach;?>

            
                
            </ul>

            <div>
           
        <?php /* endfor;*/?>
        </div>        
    </div>
    
</div>

</div>
</div>
