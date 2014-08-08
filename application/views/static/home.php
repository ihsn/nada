<style type="text/css">

.carousel {
  position: relative;
  margin-bottom: 20px;
  line-height: 1;
}

.carousel-inner {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.carousel-inner > .item {
  position: relative;
  display: none;
  -webkit-transition: 0.6s ease-in-out left;
     -moz-transition: 0.6s ease-in-out left;
       -o-transition: 0.6s ease-in-out left;
          transition: 0.6s ease-in-out left;
}

.carousel-inner > .item > img,
.carousel-inner > .item > a > img {
  display: block;
  line-height: 1;
}

.carousel-inner > .active,
.carousel-inner > .next,
.carousel-inner > .prev {
  display: block;
}

.carousel-inner > .active {
  left: 0;
}

.carousel-inner > .next,
.carousel-inner > .prev {
  position: absolute;
  top: 0;
  width: 100%;
}

.carousel-inner > .next {
  left: 100%;
}

.carousel-inner > .prev {
  left: -100%;
}

.carousel-inner > .next.left,
.carousel-inner > .prev.right {
  left: 0;
}

.carousel-inner > .active.left {
  left: -100%;
}

.carousel-inner > .active.right {
  left: 100%;
}
.carousel-control.left {
left: 20px;
}

.carousel-control {
position: absolute;
top: 72%;
left: 15px;
width: 30px;
height: 30px;
margin-top: 4px;
font-size: 35px;
font-weight: 100;
line-height: 23px;
color: white;
text-align: center;
background: #222;
border: 3px solid white;
-webkit-border-radius: 23px;
-moz-border-radius: 23px;
border-radius: 23px;
opacity: 0.5;
filter: alpha(opacity=50);
}

.carousel-control.right {
  right: 15px;
  left: auto;
}

.carousel-control:hover,
.carousel-control:focus {
  color: #ffffff;
  text-decoration: none;
  opacity: 0.9;
  filter: alpha(opacity=90);
}

.carousel-indicators {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 5;
  margin: 0;
  list-style: none;
}

.carousel-indicators li {
  display: block;
  float: left;
  width: 10px;
  height: 10px;
  margin-left: 5px;
  text-indent: -999px;
  background-color: #ccc;
  background-color: rgba(255, 255, 255, 0.25);
  border-radius: 5px;
}

.carousel-indicators .active {
  background-color: #fff;
}

.carousel-caption {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  padding: 15px;
  background: #333333;
  background: rgba(0, 0, 0, 0.75);
}

.carousel-caption h4,
.carousel-caption p {
  line-height: 20px;
  color: #ffffff;
}

.carousel-caption h4 {
  margin: 0 0 5px;
}

.carousel-caption p {
  margin-bottom: 0;
}

.carousel-content {height:200px;background:gainsboro;overflow:hidden;padding:15px;}
.carousel-content .carousel-thumb{float:left;margin-right:10px;overflow:hidden;height:200px;width:200px;}
.carousel-content .inner-content{overflow:hidden;}
.carousel-content h3{font-size:20px;margin-bottom:10px;line-height:120%;}

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
background:black;color:white;
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

<?php remove this line to enable carousel */ ?>


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
                  <?php echo number_format($variable_count);?> variables<br/>
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

<?php /* ?>
<!-- faqs -->
<div class="box-style-1" >
  <div class="header">
    <h2>FAQs</h2>
  </div>
  <div class="m-body">
    <div class="right-border content">
      <ul>
        <li><a href="index.php/faqs#nodata">Why is there no data available for some studies?</a></li>
        <li><a href="index.php/faqs#contribute">I want to contribute a dataset or a survey catalog to the Microdata Library. How do I do this? </a></li>
        <li><a href="index.php/faqs#purchase">Can the Microdata Library purchase data from an outside source for me?</a></li>
        <li><a href="index.php/faqs#error">I have found an error in a data file. What do I do?</a></li>
      </ul>
      <div style="text-align:right;padding-right:5px"> <a href="index.php/faqs" title="View more">View more...</a> </div>
    </div>
  </div>
</div>
<?php */ ?>


<?php if (isset($popular_surveys) && is_array($popular_surveys)):?>
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
