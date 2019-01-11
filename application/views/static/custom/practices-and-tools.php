<h1>Practices and tools</h1>
<p>Operating the Microdata Library involves the acquisition, preparation, documentation, cataloging, dissemination and preservation of datasets and their associated material describing how the data were collected and compiled - the metadata. For all these tasks, we follow agreed international standards and good practices. In particular we have made use of the various tools and guidelines developed in collaboration with the <a href="http://www.ihsn.org" target="_blank">International Household Survey Network (IHSN)</a>.  These are freely available to other agencies to manage their own data cataloging and archiving activities.</p>


<div style="float:left;" class="tab-style-1">
        <!-- tabs -->
        <div id="tabs" style="width:590px;" >
        <div class="tab-heading">&nbsp;</div>
            <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" style="margin-top:-47px;">
                <li class="ui-state-default ui-corner-top"><a href="<?php echo current_url();?>#acquisition">Acquisition</a></li>
                <li class="ui-state-default ui-corner-top"><a href="<?php echo current_url();?>#preparation">Preparation</a></li>
                <li class="ui-state-default ui-corner-top"><a href="<?php echo current_url();?>#documentation">Documentation</a></li>
                <li class="ui-state-default ui-corner-top"><a href="<?php echo current_url();?>#cataloging">Cataloging</a></li>
                <!--<li class="ui-state-default ui-corner-top"><a href="#dissemination">Dissemination</a></li>-->
                <li class="ui-state-default ui-corner-top"><a href="<?php echo current_url();?>#preservation">Preservation</a></li>
            </ul>
            <div id="acquisition">
                <p>Initially, preparing datasets for inclusion in the Microdata Library has focused on datasets managed or held by the World Bank and a few other agencies where the preparation, documentation and cataloging have been carried out in accordance with the Data Documentation Initiative (DDI) standard.  The World Bank datasets consist of household surveys - carried out for specific operations, for example, to complete an impact evaluation, evaluate the impact, research studies, and surveys carried out as part of the living standard measurement study (LSMS)  - and other surveys carried out as part of global programs to assess the impact of migration and to monitor the investment climate. </p>
                <p>The policy in developing the Library has been to only include those data sets where complete metadata are available and where access to data sets can also be provided.  Procedures for getting access to the data are set out in the Terms of Use.  For those data sets held and managed by the World Bank, open access will be provided as far as possible in line with the open data initiative.  For other datasets, where the Bank does not have ownership of the data, the procedures for obtaining access and the conditions of use are determined by the owner.  </p>
				<p>The Library will continue to acquire new data sets for inclusion as they become available and as other agencies are prepared to publish metadata in the catalog.  </p>
				<p>Future acquisitions will focus on the backlog of datasets held and managed by the World Bank and discussions will be held with other dataset owners to identify material that will be suitable for inclusion.  It is expected that new acquisitions will be added to the Library on a regular basis.  </p>
            </div>            

            <div id="preparation">
               <p>The Microdata Library receives datasets from many different sources. As a general rule, we do not modify the datasets unless we work directly with the producer, except to apply statistical disclosure control and to format the data files for the convenience of users.  Data files are always preserved in their original format, as well as in a Stata-consistent format.  For dissemination the Library provides for data files to be converted into other commonly used formats.  Documents are stored in their original format, but are usually disseminated as PDF files.</p>
                <h3>Statistical Disclosure Control</h3>
                <p>When disseminating microdata files, the data producer must safeguard the confidentiality of information about individual respondents. Processes aimed at protecting confidentiality are referred to as Statistical Disclosure Control (SDC). SDC techniques include the removal of direct identifiers (names, phone numbers, addresses, etc) and indirect identifiers (detailed geographic information, names of organizations to which the respondent belongs, exact occupations, exact dates of events such as birth, death and marriage) from the data files.</p>
                <h3>Data and metadata quality</h3>
                <p>The World Bank works with various data producers to promote better practices of data management including variable and file naming rules and the use of labels.  Because the Library has no control over the data collection or management procedures used, there is no guarantee that these practices will have been used in any specific survey.  </p>
                <p>Datasets come from a large number of countries, as a consequence, metadata come in multiple languages. We do not translate these metadata. </p>
                <p>The Microdata Library cannot guarantee the quality of the data. Data are provided "as is". We make all possible efforts to ensure that the metadata are as comprehensive as possible. This documentation includes, whenever possible, identification of problems and weaknesses in the datasets. It is, however ,the responsibility of the researcher to make his/her own assessment of the reliability and suitability of the data for his/her specific purpose, based on all the information provided. The World Bank and other contributors to our catalogs take no responsibility for analysis done using data from our catalog. We welcome feedback, and will attempt to solve problems reported by users, but we are not in a position to provide support to users of the data.</p>
            </div>            

            <div id="documentation">
                <p>For the documentation of datasets, the Microdata Library has adopted the Data Documentation Initiative (DDI) metadata standard. The DDI is an international XML based standard for microdata documentation. Its aim is to provide a straightforward means of recording and communicating to others all the salient characteristics of micro-datasets. The DDI specification permits all aspects of a survey to be described in detail: the methodology, responsibilities, files and variables. It provides a structured and comprehensive list of hundreds of elements and attributes that may be used to document a dataset. We also comply with the XML Dublin Core Metadata Specification (DCMI) for documenting external resources (questionnaires, reports, programs, etc)</p>
                <p>We make use of version 2.1 (also known as the "DDI Codebook) of the DDI standard. When available, we will upgrade to version 2.5. We do not use the more complex DDI 3 (also known as the "DDI Life Cycle") as suitable tools are not available for supporting its use by our partners in many countries. We do not require the  specific features of DDI 3 for our purpose.</p>
                <p>To document the datasets in compliance with these XML standards, we use the <a href="http://ihsn.org/home/index.php?q=tools/toolkit" target="_blank">IHSN Metadata Editor</a> (also known as the "Nesstar Publisher"), free software produced by the <a href="http://www.nsd.uib.no/nsd/english/index.html" target="_blank">Norwegian Social Science Data Services (NSD)</a>.</p>
            </div>            

            <div id="cataloging">
                <p>The Library is cataloged, which supports data discovery, with users able to search for items by country, topic, variable and many other characteristics. For more information see the <a href="<?php echo $this->config->item("microdata_site_url");?>/using-our-catalog">Using our Catalog page</a>.</p>
				<p>The catalog also includes a bibliography of publications that have used datasets in the catalog. The bibliography includes journal articles, books, book chapters, working papers, dissertations, conference papers and unpublished manuscripts. </p>
            </div>            

			<!--
            <div id="dissemination">			
            </div>            -->

            <div id="preservation">
                <p>One important objective of the Microdata Library is to preserve the data for the long term.  Preservation requires more than just the creation and storage of backups, it also implies effective management of the migration of formats and storage media.  The World Bank has advanced IT systems which guarantee physical preservation, with backups created on a systematic basis stored at headquarters and in remote locations.  </p>
			  <p>For the time being, the obsolescence of media is not thought to be a major issue. Stata is used as a standard internal format and a policy on managing migration to later versions is under development.  In addition the Library will always preserve both the data and the metadata in the original format.</p>
			  <p>The objective of the Library is to comply with the de facto standard which has emerged in the area of digital preservation - the Reference Model for an Open Archival Information System (OAIS) - produced by the NASA Consultative Committee for Space Data Systems.  OAIS is an ISO standard that provides a functional framework for sustaining digital objects in managed repositories. </p>
            </div>            

        </div>
</div>




<div class="wb-box-sidebar">
<div class="wb-box">

<div class="box-item">
<img src="files/logo_toolkit_generic.gif"/>
<h3><a href="http://ihsn.org/software/ddi-metadata-editor" target="_blank">IHSN Microdata Management Toolkit</a></h3>
<div class="box-text">A free DDI compliant metadata editor</div>
</div>

<div class="box-item">
<img src="files/logo_nada.gif"/>
<h3><a href="http://ihsn.org/nada/">NADA</a></h3>
<div class="box-text">Our open source survey cataloguing system</div>
</div>

<div class="box-item">
<img src="files/wp.png"/>
<h3><a href="http://ihsn.org/quick-reference-guide-for-data-archivists" target="_blank">Quick reference Guide for Data Archivists</a></h3>
<div class="box-text">Recommendations for documenting datasets</div>
</div>

<div class="box-item">
<img src="files/wp.png"/>
<h3><a href="http://ihsn.org/dissemination-of-microdata-files" target="_blank">Dissemination of Microdata Files</a></h3>
<div class="box-text">Formulation of microdata dissemination policies and protocols</div>
</div>

<div class="box-item">
<img src="files/wp.png"/>
<h3><a href="http://ihsn.org/principles-and-good-practice-for-preserving-data" target="_blank">Preserving Data</a></h3>
<div class="box-text">Principles and standards of good practice as applied to data preservation.</div>
</div>

</div>

<div class="wb-box-sub">
<img src="files/ddi-logo-tagline1.png"/>
</div>


</div>

<script> 
	$(function() {
		$( "#tabs" ).tabs();
		$("#tabs div.ui-tabs-panel").css('height', $("#acquisition").height()+200);
		adjust_sidebar();
	});
	</script> 