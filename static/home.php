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
      <div class="stats-citations"><?php echo number_format($citation_count);?> citations</div>
      <div class="stats-variables"><?php echo number_format($variable_count);?> variables</div>
	  <!--<div style="margin-top:3px;"><a href="index.php/catalog" title="visit central catalog"><img src="files/catalog-button.gif" alt=""/></a></div>-->
    </div>
  </div>
  <?php /*<div class="wb-box-sub">
    <h3>Related</h3>
    <ul>
      <li><a href="http://go.worldbank.org/BHEH82GTT0">Democratizing Development Economics</a></li>
      <li><a href="http://www.worldbank.org/open/">Open Development</a></li>
    </ul>
  </div>
  */?>
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