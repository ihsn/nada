<h1>Microdata Library</h1>
<div style="float:left;border:none;">
    
    <div class="wb-box-main with-bottom-spacing">
      <div class="wb-box">
        <div id="slides" class="slide-show">
          <div class="slides_container">
            <div><img src="files/education.jpg" alt="" />
              <h1>Wholesaling and democratizing research</h1>
              <p>The Microdata Library is a service established to increase the value of survey datasets by facilitating their access and use by the research community.</p>
              <p>It participates in the Bank's commitment to wholesale and democratize research, in line with the Bank’s philosophy of Open Data, Open Knowledge, and Open Solutions.</p>
            </div>
            
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
          </div>
        </div>
      </div>
    </div>

		<br style="clear:left;"/>
			<!-- tabs -->
            <div id="tabs" style="width:590px;" >
            <div class="tab-heading">&nbsp;</div>
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                    <li class="ui-state-default ui-corner-top"><a href="#tabs-rationale">Rationale</a></li>
                    <li class="ui-state-default ui-corner-top"><a href="#tabs-objective">Objective</a></li>
                    <li class="ui-state-default ui-corner-top"><a href="#target-audience">Target Audience</a></li>
                    <li class="ui-state-default ui-corner-top"><a href="#scope-and-coverage">Scope and coverage</a></li>
                </ul>
                <div id="tabs-rationale">
                    <p>The  Microdata Library is a service established to facilitate access to microdata  sets that  provide information about people living in developing countries, their  institutions, their environment, their communities and the operation of their  economies.</p>
                    <p>We  use the term microdata to refer to information about specific characteristics  of individual people or entities such as households, business enterprises,  facilities, farms or even geographical areas such as villages or towns. They  may be obtained from statistical sample surveys and censuses, or as a  by-product of an administrative process. Microdata are the inputs for  quantitative analysis, supporting an in-depth understanding of socio-economic  issues by identifying relationships and by estimating interactions between  phenomena. Microdata, therefore, provide the basic evidence for designing  projects and formulating policies, for targeting interventions and for  monitoring and measuring the impacts and results of past actions. </p>
                    <p>The  Library activities include the acquisition of microdata, the detailed  documentation of how the data have been collected and compiled, the cataloguing  of the information, the preservation of the data, and their dissemination.<p/>
                    <p>The  Library  includes datasets that have been  produced by and which belong to the World Bank, as well as a substantial number  that have been collected and compiled by different international, regional and  national agencies. Our microdata catalog operates as a portal for microdata  documented in compliance with international standards and practices.</p>
				</div>            

                <div id="tabs-objective">                   
                    <p>The main objective of the Microdata Library is to make microdata available to as wide a range of  users as possible, in order to promote research and analysis that will contribute  to achieve our vision of a world without poverty. </p>
                    <p> We also hope to  increase the credibility of data and research work by the World Bank and other  development agencies by supporting independent research that can replicate and  challenge existing results and which can help to generate new understanding.</p>
                    <p> Our  aim is to provide access to as much information as possible, free of charge, to  all users. In some situations, however, especially where the datasets have been  provided to the World Bank by other agencies, the ownership of the data remains  with that agency and there may be some limitations on how they can be accessed  and used. More information about how users can access the material in the  Library can be found in out terms of use.</p>
				</div>            

                <div id="target-audience">
                   	<p>While  anyone is free to use the data, subject to the terms use, we expect that most  users will be researchers and analysts with experience in the use of  statistical techniques. </p>
					<p> Microdata  are the inputs for advanced quantitative analysis. Most are obtained from  sample surveys. Users, therefore, will need to have an understanding of  sampling and will also need to have access to and expertise in the use of  statistical analysis software packages such as Stata, SPSS or SAS.</p>
                </div>            

                <div id="scope-and-coverage">                    
                    <p>The Microdata Library contains datasets that include information about a  wide range of economic, social and other topics. </p>
                    <p>Much of the microdata in the Library have been collected through sample surveys  of households, business establishments or other facilities. Datasets may also originate  from population, housing or agricultural censuses, or administrative data  collection processes.</p>
                    <p>The World Bank has been involved in collecting  data for a number of different purposes, such as impact evaluation of the  Bank’s operations or research on development issues. As a result we have a  large and diverse range of microdata sets in almost all areas of the Bank’s  activities. The Library also includes data sets that have been produced by  other international organizations and by statistical and other agencies in  developing countries. </p>
                </div>            

            </div>
</div>


<div class="wb-box-sidebar">
  <div class="wb-box">
    <div class="stats">
      <div class="stats-text">As of <?php echo date("F d, Y",date("U")); ?> our catalog contains</div>
      <div class="stats-surveys"><?php echo number_format($survey_count);?> surveys</div>
      <div class="stats-citations"><?php echo number_format($citation_count);?> citations</div>
      <div class="stats-variables"><?php echo number_format($variable_count);?> variables</div>
    </div>
  </div>
  <div class="wb-box-sub">
    <h3>Related</h3>
    <ul>
      <li><a href="http://go.worldbank.org/BHEH82GTT0">Democratizing Development Economics</a></li>
      <li><a href="http://www.worldbank.org/open/">Open Knowledge</a></li>
    </ul>
  </div>
  <div class="wb-box-sub wb-box-last">
    <h3>FAQ's</h3>
    <ul>
      
      <li><a href="<?php echo site_url();?>/faqs#topics">I need help to analyze the data. Can  I get help from the World Bank?</a></li>
      <li><a href="<?php echo site_url();?>/faqs#stata">Can I obtain the data in a format other than Stata?</a></li>
      <li><a href="<?php echo site_url();?>/faqs#programs">I did analysis of a dataset and would like to share my programs. Are you interested?</a></li>
	  <!--<li><a href="<?php echo site_url();?>/faqs#notify">I would like to be notified when new datasets of interest are made available. Is this possible?</a></li>-->
      <!--<li><a href="<?php echo site_url();?>/faqs#info">What do you do with the registration information I provide?</a></li>-->
    <!--<li><a href="<?php echo site_url();?>/faqs#problems">I found problems in the data. To whom do I send my feedback?</a></li>-->
	</ul>
  </div>
</div>
<br style="clear:left;"/>

<script> 
	$(function() {
		$( "#tabs" ).tabs();
		$("#tabs div.ui-tabs-panel").css('height', $("#tabs-rationale").height());		
		adjust_sidebar();
		//disable hyperlinks
		//$(".no-action a").click(function(){return false;});
	});
	</script> 