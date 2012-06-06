<style>
.quick-link{background:url(files/box_wb.png) no-repeat;width:175px;height:175px; cursor:pointer;font-weight:bold;text-align:center;}
.quick-link:hover{background:url(files/box_wb_over.png) no-repeat;}
</style>
<table style="width:100%;">
<tr valign="top" style="vertical-align:top;">

<td style="padding-right:10px;">

<!-- About -->
<div class="grey-module" id="slideshow-container" >
  <div class="m-head">
    <h2>Microdata Library</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" style="padding:15px;position:relative">
		
        <table>
        	<tr style="vertical-align:top">
            <td><img src="files/ad_microdata.png" align="left" width="150px" style="margin-right:15px;"/></td>
            <td>
            <h1>Microdata Library</h1>
        	<p>The Microdata Library provides a searchable catalog and repository of survey microdata and documentation. 
            The Microdata Library is the product of a Bank-wide collaboration between regions and a number of specialized units. <a href="index.php/about" title="Read more">Read more...</a></p>
            
        <form action="<?php echo site_url();?>/catalog" style="">
         <div class="quick-search-box">
            <input id="sk" class="sk" value="Search the Central Microdata Catalog" onfocus="value=''" name="sk">
            <input id="quick-search" type="submit" value="Find" class="submit-button" />
        </div>
        <a style="float:left;display:block;margin-top:8px;" href="<?php echo site_url();?>/catalog/central">View all Surveys &raquo;</a>
        <br/>
        </form>   
        <!--
        <div style="margin-top:20px;text-align:right;padding-right:5px"> <a href="index.php/about" title="Read more">Read more...</a> </div>            
        -->
            </td>
            </tr>
        </table>
        
        <div class="notice">
        	<p>The Microdata Library is currently at the development stage. Refining of the application, migration 
            from the old DDP microdata platform and the addition of new studies is ongoing. If you do not find the study you are looking 
            for you should attempt to find it in the <a target="_blank" href="http://ddp.worldbank.org/microdata/index.jsp">old DDP Microdata Platform</a>.</p>
            <p>We welcome feedback, contact us at: <a href="mailto:microdata@worldbank.org">microdata@worldbank.org</a></p>
        </div>

        
        </div>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>

<?php /* ?>

<!-- notic box-->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Development Status</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    
                    <div>
                        <p>The DDP Microdata Library is currently at the Beta development stage. Refining of the application as well as the migration and addition of new datasets from the old DDP microdata platform is ongoing. As of October 2011 the catalog contains around 20 percent of our total holdings. If you do not find the study you are looking for you should attempt to find it in the old DDP Microdata Platform located at http://ddp.worldbank.org/microdata/index.jsp.</p>
                    </div>
    
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>

<?php */ ?>

<?php /* ?>
<!-- search box-->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Stats</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    

                    <div>
                        <h3><a href="<?php echo site_url();?>/catalog/central"><?php echo t('central_data_catalog');?></a></h3>
                        <p>The <?php echo t('central_data_catalog');?> is a portal for all surveys and datasets held in catalogs maintained by the World Bank and a number of contributing external catalogs.</p>
                        <form action="<?php echo site_url();?>/catalog">
                         <div class="quick-search-box">
                        	<input id="sk" class="sk" value="Search the Central Microdata Catalog" onfocus="value=''" name="sk">
                        	<input id="quick-search" type="submit" value="Find" class="submit-button" />
                        </div>
                        <a style="float:left;display:block;margin-top:5px;" href="<?php echo site_url();?>/catalog/central">View all Surveys &raquo;</a>
                        </form>                        
                    </div>

    
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
<?php */?>
<?php /*?>
<!-- stats -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Stats</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
        <div class="stats" >
          <div class="stats-text" style="font-size:20px;">As of <b><?php echo date("F d, Y",date("U")); ?></b>, Library contains</div>
          <div class="stats-surveys" style="font-size:24px"><?php echo number_format($survey_count);?> surveys, 
          <?php echo number_format($variable_count);?> variables,
          <?php echo number_format($citation_count);?> citations<br/>          
          <div class="block-stats-button" style="margin-left:25%;margin-right:25%;"><a href="index.php/catalog">Visit Catalog</a></div>
          </div>
    	</div>
      <br />
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
<?php */?>

<!-- about central catalog -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Links</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" >

        <table class="f-box-container" style="width:100%;padding-top:10px;">

        	<tr>
            	<td><a class="f-box" href="index.php/contributing-catalogs"><img src="files/list.png" /><br/>Contributing Catalogs</a></td>
                <td><a class="f-box" href="index.php/terms"><img src="files/file3.png" /><br/>Terms of use</a></td>
                <td><a class="f-box" href="index.php/deposit"><img src="files/file plus.png" /><br/>Deposit data</a></td>
                <td><a class="f-box" href="index.php/help"><img src="files/info.png" /><br/>Using the catalog</a></td>
                <td><a class="f-box" href="index.php/resources"><img src="files/book.png" /><br/>Resources</a></td>
            </tr>
<?php /* ?>
           <tr>
            	<td>
                    <a class="f-box" href="index.php/terms">
                    <div class="quick-link">
                    <img style="margin-top:50px;" src="files/file3.png" />
                    <p>Terms of use</p>
                    </div>
                    </a>
                </td>
                <td>
                    <a class="f-box" href="index.php/help">
                    <div class="quick-link">
                    <img style="margin-top:50px;" src="files/info.png" />
                    <p>Using the catalog</p>
                    </div>
                    </a>
                </td>
                <td>
                <a class="f-box" href="index.php/deposit">
                <div class="quick-link">
                <img style="margin-top:50px;" src="files/file plus.png" />
                <p>Deposit data</p>
                </div>
                </a>
                </td>
                <td>
                <a class="f-box" href="index.php/resources">
                <div class="quick-link">
                <img style="margin-top:50px;" src="files/book.png" />
                <p>Resources</p>
                </div>
                </a>
                </td>
            </tr>
<?php */ ?>
        </table>
        </div>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>




<!-- recent studies list -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
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
      <br/>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
</td>

<td style="width:250px" class="right-col">

<!-- stats -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Stats</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
        <div class="stats" >
        	<div class="bg-star">
                  <div class="stats-text" >As of <?php echo date("F d, Y",date("U")); ?><br /> the Library contains</div>
                  <div class="stats-surveys" ><?php echo number_format($survey_count);?> surveys<br/> 
                  <?php echo number_format($variable_count);?> variables<br/>
                  <?php echo number_format($citation_count);?> citations
            </div>      
          </div>
    	</div>
      <br />
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>

<?php /* ?>
<!-- recent studies list -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Latest additions</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" >
		<?php                            
            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);//list of repos
            //$this->load->view("microdata.worldbank.org/home/index_public",$data);
            $this->load->view("catalog_search/recent_studies_list",array('rows'=>$latest_surveys));
        ?>
        </div>
      <br/>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
<?php */ ?>

<?php if (isset($popular_surveys) && is_array($popular_surveys)):?>
<!-- popular studies -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>Most popular studies</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
    	<div class="content" >
              <ul class="bl">
              <?php foreach($popular_surveys as $survey): ?>
                <li><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></li>
              <?php endforeach;?>
              </ul>
        </div>
      <br/>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
<?php endif;?>

<!-- faqs -->
<div class="grey-module" id="sidebar-faq" >
  <div class="m-head">
    <h2>FAQs</h2>
  </div>
  <div class="m-body">
    <div class="right-border">
      <ul>
        <li><a href="index.php/faqs#nodata">Why is there no data available for some studies?</a></li>
        <li><a href="index.php/faqs#contribute">I want to contribute a dataset or a survey catalog to the Microdata Library. How do I do this? </a></li>
        <li><a href="index.php/faqs#purchase">Can the Microdata Library purchase data from an outside source for me?</a></li>
        <li><a href="index.php/faqs#error">I have found an error in a data file. What do I do?</a></li>
      </ul>
      <div style="text-align:right;padding-right:5px"> <a href="index.php/faqs" title="View more">View more...</a> </div>
      <br>
    </div>
  </div>
  <div class="m-footer"><span>&nbsp;</span></div>
</div>
</td>
</tr>
</table>


<script> 
	$(function() {
		//adjust_sidebar();
		//disable hyperlinks
		//$(".no-action a").click(function(){return false;});		
		$("#quick-search").click(function(){
			if ($("#sk").val()=="Search the Central Microdata Catalog")
			{
				$("#sk").val("");
				return false;
			}
		});
	});
	</script> 


<?php return;?>

<h1>Microdata Library</h1>
<div style="float:left;border:none;">
    
    <div class="wb-box-main with-bottom-spacing">
      <div class="wb-box">
        <div id="slides" class="slide-show">
          <div class="slides_container">
          	<?php if (isset($slides)):?>
            	<?php foreach($slides as $slide):?>
                	<?php 
						$text=str_replace("[site_url]",site_url(),$slide['text']);
						$text=str_replace("[base_url]",base_url(),$text);
						echo $text;	
					?>
                <?php endforeach;?>
            <?php endif;?>
            <!--
            <?php if (isset($popular_surveys) && is_array($popular_surveys)):?>
            <div>
              <h1>Most popular surveys in the last 30 days</h1>
              <ul class="bl">
              <?php foreach($popular_surveys as $survey): ?>
                <li><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></li>
              <?php endforeach;?>
              </ul>
            </div>
            <?php endif;?>
            <?php if (isset($latest_surveys) && is_array($latest_surveys)):?>
            <div>
              <h1>Recent additions</h1>
              <ul class="bl no-action">
              <?php foreach($latest_surveys as $survey): ?>
                <li><a href="<?php echo site_url();?>/catalog/<?php echo $survey['id'];?>"><?php echo $survey['nation'];?> - <?php echo $survey['titl'];?></a></li>
              <?php endforeach;?>
              </ul>
            </div>
            <?php endif;?>
            -->
          </div>
        </div>
      </div>
    </div>

		<br style="clear:left;"/>
			<!--
            <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabsx" style="width:590px;" >
                <div id="tabs-central-catalog" class="ui-tabs-panel ui-widget-content ui-corner-bottom central-repo" style="position:relative;">
                    <div>
                        <a title="Microdata Library" href="<?php echo site_url();?>/catalog/central"><img style="float: left; display: block; margin-right: 10px;" src="files/logo-central.gif" alt="Microdata Library"></a>
                        <h3><a href="<?php echo site_url();?>/catalog/central"><?php echo t('central_data_catalog');?></a></h3>
                        <p><a href="<?php echo site_url();?>/catalog/central">The <?php echo t('central_data_catalog');?> is a portal for all datasets held in repositories maintained by the World Bank and a number of contributing external repositories. </a></p>
                        <div style="margin-left:95px;"><a href="<?php echo site_url();?>/catalog/central"><img src="files/catalog-button.gif" alt="Search the Repository"/></a></div>
                    </div>
				</div>
            </div>
            -->



            
             <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabsx" style="width:590px;" >
                <div id="tabs-central-catalog" class="ui-tabs-panel ui-widget-content ui-corner-bottom central-repo" style="position:relative;">
                    <div>
                        <h3><a href="<?php echo site_url();?>/catalog/central"><?php echo t('central_data_catalog');?></a></h3>
                        <p>The <?php echo t('central_data_catalog');?> is a portal for all surveys and datasets held in catalogs maintained by the World Bank and a number of contributing external catalogs.</p>
                        <form action="<?php echo site_url();?>/catalog">
                         <div class="quick-search-box">
                        	<input id="sk" size="14" style="outline:none;border:none;background:none;float:left;height:19px;FONT-SIZE:  11px; WIDTH: 220px; COLOR: #666;  padding:2px 5px 0px; FONT-FAMILY: Arial;margin:0px;" value="Search the Central Microdata Catalog" onfocus="value=''" name="sk">
                        	<input id="quick-search" type="submit" value="" class="submit-button" />
                        </div>
                        <a style="float:left;display:block;margin-top:5px;" href="<?php echo site_url();?>/catalog/central">View all Surveys &raquo;</a>
                        </form>
                        
                    </div>
				</div>
            </div>



			<!-- tabs -->
            <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs" style="width:590px;" >
            <?php /* ?>
				<div class="tab-heading">&nbsp;</div>
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                    <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tabs-about">About</a></li>
                    <li class="ui-state-default ui-corner-top"><a href="<?php echo site_url();?>/catalog">Datasets</a></li>
                </ul>
			<?php */ ?>
                <div id="tabs-about" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
					<?php                            
                            $data['rows']=$this->repository_model->get_repositories($published=TRUE, $system=FALSE);//list of repos
                            //$this->load->view("microdata.worldbank.org/home/index_public",$data);
							$this->load->view("catalog_search/recent_studies",array('rows'=>$latest_surveys));
                    ?>
				</div>
            </div>
</div>




<div class="wb-box-sidebar">

  <div class="wb-box">
    <div class="stats">
      <div class="stats-text">As of <?php echo date("F d, Y",date("U")); ?> the Library contains</div>
      <div class="stats-surveys"><?php echo number_format($survey_count);?> surveys</div>
      <div class="stats-variables"><?php echo number_format($variable_count);?> variables</div>
      <div class="stats-citations"><?php echo number_format($citation_count);?> citations</div>      
	  <!--<div style="margin-top:3px;"><a href="index.php/catalog" title="visit central catalog"><img src="files/catalog-button.gif" alt=""/></a></div>-->
    </div>
  </div>

  <div class="wb-box-sub">
    <h3>FAQ's</h3>
    <ul>
      <li><a href="<?php echo site_url();?>/faqs#improve">How can I contribute to improving the catalog?</a></li>
      <li><a href="<?php echo site_url();?>/faqs#analyze">Can you help with analyzing the data?</a></li>
      <li><a href="<?php echo site_url();?>/faqs#tools">Can I get help in implementing a survey catalog in my agency?</a></li>	  
	</ul>
	<div style="text-align:right;padding-top:8px;">
	 <a href="<?php echo site_url();?>/faqs">Click here for more...</a>
	</div>
  </div>
  
  <div class="wb-box-sub">
	<div class="box-item" style="color:gray;">
	<img src="files/logo-ihsn.gif" align="left" style="padding-right:10px;padding-bottom:5px;" />Developed in collaboration with the <a target="_blank" href="http://www.ihsn.org/">International Household Survey Network</a>.
	</div>
  </div>
  <div class="wb-box-sub wb-box-last">
	<div class="box-item" style="color:gray;text-align:center;">Powered by the <br/>Data Documentation Initiative metadata standard</div>
	<div style="text-align:center;padding-top:10px;"><img src="files/ddi-logo.gif"  /></div>	
  </div>
  
  
</div>
<br style="clear:left;"/>

<script> 
	$(function() {
		//adjust_sidebar();
		//disable hyperlinks
		//$(".no-action a").click(function(){return false;});
		
		$("#quick-search").click(function(){
			if ($("#sk").val()=="Search the Central Microdata Catalog")
			{
				$("#sk").val("");
			}
		});
	});
	</script> 