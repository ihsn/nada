<style type="text/css">
.box-style-1{
  border: 1px solid gainsboro;
  overflow: auto;
  clear: both;
  margin-bottom: 30px;
  -webkit-border-radius: 3px;
  -moz-border-radius: 3px;
  border-radius: 3px;
  position: relative;
}

.box-style-1 .header{background:#F8F8F8;border-bottom:1px solid gainsboro;padding:5px 10px;}
.box-style-1 .content{padding:10px;}
.box-style-1 .item{padding-bottom:5px;margin-bottom:5px;border-bottom:1px dashed gainsboro;}
.box-style-1 .item .title{font-size:16px;}
.box-style-1 .item .created{color:#333333}
.box-style-1 h2{margin:0px;padding:0px;font-size:18px;}

.box-style-1 .stats-text{font-size:20px;}
.box-style-1 .stats-surveys{margin-top:10px;font-size:18px;font-weight:bold}
.box-style-1 .item .sub{font-size:12px;color:#999999}

.btn-central-catalog {
  margin-top: 10px;
  padding: 10px;
  background: #039;
  display: block;
  text-align: center;
  font-size: 14px;
  -webkit-border-radius: 3px;
  -moz-border-radius: 3px;
  border-radius: 3px;
  color:white;
}

.btn-central-catalog:hover {
  background:black;
  color:white;
}
</style>

<table style="width:100%;">
<tr valign="top" style="vertical-align:top;">

<td style="padding-right:10px;">

<?php /* remove this line to enable carousel ?>
<!-- carousel bootstrap -->
<div id="myCarousel" class="carousel slide" data-interval="false">
  <!-- Carousel items -->
  <div class="carousel-inner">
    <div class="active item">
    	<div class="carousel-content">
        	<div class="carousel-thumb">
            	<img src="files/ad_microdata.png"/>
            </div>
            <div class="inner-content">
            <h3>Featured Catalog: Global Financial Inclusion (Global Findex) Database</h3>
            <p>Feature dCras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
            </div>
        </div>
    </div>

    <div class="item">
        <div class="carousel-content">
        	<div class="carousel-thumb">
            	<img src="files/lac-fp-01.jpg"/><span>some text</span>
            </div>    
			<div class="inner-content">
            	<h3>Featured Catalog: Global Financial Inclusion (Global Findex) Database</h3>
            	<p>Feature dCras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
            </div>
        </div>
    </div>
    <div class="item">
        <div class="carousel-content">
        	<div class="carousel-thumb">
            	<img src="files/lac-fp-01.jpg"/><span>some text</span>
            </div>    
			<div class="inner-content">
            	<h3>Featured Catalog: Global Financial Inclusion (Global Findex) Database</h3>
            	<p>Feature dCras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
            </div>
        </div>
    </div>
</div>

  <!-- Carousel nav -->
  <a class="carousel-control left" href="#myCarousel" data-slide="prev">&lsaquo;</a>
  <a class="carousel-control right" href="#myCarousel" data-slide="next">&rsaquo;</a>
</div>
<?php */ ?>

<!-- recent studies list -->
<div class="box-style-1" id="sidebar-faq" >
  <div class="header">
    <h2>Latest additions</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" >
		<?php                            
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);//list of repos
            $this->load->view("catalog_search/recent_studies_list",array('rows'=>$latest_surveys));
        ?>
        </div>
    </div>
  </div>
</div>
</td>

<td style="width:250px" class="right-col">

<!-- stats -->
<div class="box-style-1">
  <div class="header">
    <h2>Stats</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
        <div class="content stats" >
        	<div class="bg-star">
                  <div class="stats-text" >As of <?php echo date("F d, Y",date("U")); ?><br /> the Library contains</div>
                  <div class="stats-surveys" ><span class="numb"><?php echo number_format($survey_count);?></span> surveys<br/> 
                  <?php if ($variable_count>0):?>
                  <?php echo number_format($variable_count);?> variables<br/>
                  <?php endif;?>
                  <?php if ($citation_count>0):?>
                  <?php echo number_format($citation_count);?> citations
                  <?php endif;?>
            </div>      
          </div>
          
          <div>
<div><a class="btn-central-catalog" href="<?php echo site_url('catalog/central');?>" title="Return to central catalog">Central Catalog</a></div>
</div>
          
    	</div>
    </div>
  </div>
</div>



<?php if (isset($popular_surveys) && is_array($popular_surveys) && count($popular_surveys)>0):?>
<!-- popular studies -->
<div class="box-style-1" >
  <div class="header">
    <h2>Most popular studies</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" >
              <ul class="bl">
              <?php foreach($popular_surveys as $survey): ?>
                <li class="item">
                	<div><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['titl'];?></a></div>
                    <div class="sub"><?php echo $survey['nation'];?></div>
                </li>
              <?php endforeach;?>
              </ul>
        </div>
    </div>
  </div>
</div>
<?php endif;?>



</td>
</tr>
</table>