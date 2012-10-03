<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<?php
$menu_horizontal=FALSE;
$secondary_menu=FALSE;
$secondary_menu_formatted=FALSE;

//active repo
$active_repo=$this->session->userdata('active_repository');
$active_repo_citation_count=0;

if ($active_repo=='' || $active_repo=='central')
{
	$active_repo='central';
	$active_repo_title=t('central_data_catalog'); 
	$active_repo_citation_count=0;//always hide citations tab for central
}
else
{
	$active_repo_title=$this->breadcrumb->get_repository_title($active_repo);
	
	//check if current repo has citations
	$active_repo_citation_count=$this->Repository_model->has_citations($active_repo);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
          "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title;?></title>
<base href="<?php echo js_base_url(); ?>" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="javascript/superfish/css/superfish.css" media="screen">
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />

<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/ui-lightness/ui.core.css" />
<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/ui-lightness/ui.base.css" />
<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/ui-lightness/ui.accordion.css" />
<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/ui-lightness/ui.theme.css" />

<script src="javascript/jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="javascript/ui.core.js"></script>
<script type="text/javascript" src="javascript/jquery/ui/ui.tabs.js"></script>
<script src="javascript/slidesjs/slides.min.jquery.js"></script>
	<script>
		$(function(){
			$('#slides').slides({
				preload: true,
				play: 10000,
				pause: 2500,
				hoverPause: true,
				fadeSpeed: 650, 
				slideSpeed: 950
			});
		});
	</script>


<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/opendata.css" />

<?php /*?>
<!--sharing toolbar-->
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/wb-share.css" />
<script src="themes/<?php echo $this->template->theme();?>/wb-share.js"></script>
<?php */?>

<!--[if lt IE 8]>
<style type="text/css">
    .ui-tabs .ui-tabs-nav li{padding-bottom:4px;}
    .ui-tabs .ui-tabs-nav li.ui-tabs-selected{padding-bottom:4px;}
    .ui-tabs .ui-tabs-nav li a{padding:5px;padding-bottom:4px;}
</style>
<![endif]-->

<script type="text/javascript"> 
 /* <![CDATA[ */
 if(top.frames.length > 0) top.location.href=self.location;
 /* ]]> */
</script> 

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 


<script type="text/javascript">
function adjust_sidebar(){
	return;
	$("#sidebar-wrap").height("auto");
  if ($("#sidebar-wrap").height()<$("#yui-main").height()) {
  	$("#sidebar-wrap").height($("#yui-main").height());//fix sidebar height
	}
}

jQuery(function(){
	$("#footer-glob-links").hide();
	$("#glob-nav-toggle").click(function(e){toggle_global_nav();});
	//adjust_sidebar();
});

$.ajax({
   complete: function(){
	 //adjust_sidebar();
   }
});
 
$(document).ajaxComplete(function() {
//adjust_sidebar();
});

//collapse/expand div
function toggle_global_nav(e){
	if ($("#footer-glob-links").is(":visible")){
		$("#footer-glob-links").slideUp("slow");
		$("#glob-nav-toggle img").attr('src','themes/<?php echo $this->template->theme();?>/plus.png');
	}
	else{
		$("#footer-glob-links").slideDown("slow");
		$('#glob-nav-toggle img').attr('src','themes/<?php echo $this->template->theme();?>/minus.png');
	}
}	
</script>
</head>
<body>
<div id="custom-doc" class="<?php echo ($secondary_menu_formatted!==FALSE) ? 'yui-t1' : 'yui-t1'; ?>" > 
	<!--login information bar-->
    <?php $this->load->view('users/user_bar');?>	
    
    <!-- masthead -->
    <div id="hd">    
       	<!-- logo -->
        <div class="site-logo">
        	<a title="<?php echo $this->config->item("website_title");?> - Home Page"  href="<?php echo site_url();?>">
            <img src="themes/<?php echo $this->template->theme();?>/wb-logo.gif"  border="0" alt="Logo"/>
            </a>
        </div>
      	
        <!-- global search -->
        <div id="global-search">
        <form name="searchformIntranet" method="get" action='http://search.worldbank.org/all'>
          <label for="Search" accesskey="S"></label>
          <label for="qterm" accesskey="K"></label>
          <input id="qterm" type="text" name="qterm" size="15" class="search-input">
          <input type="submit" value="go" name="Search" id="global-search-button"/>
        </form>
      </div>
      
    </div>
    
    <!--global navigation-->    
    <div id="global-navigation">
    <table cellpadding="0" cellspacing="0">
    <tr>
        <td class="home-icon"><a href="http://www.worldbank.org/" class="home"><img alt="HOME" src="themes/<?php echo $this->template->theme();?>/home-icon.png" title="World Bank home"></a></td>    
        <td class="pri-links">
        <ul class="links">
            <li class="about first"><a href="http://www.worldbank.org/about">About</a></li>
            <li class="data"><a href="http://www.worldbank.org/data" class="active" >Data</a></li>
            <li class="research"><a href="http://econ.worldbank.org/">Research</a></li>
            <li class="learning"><a href="http://www.worldbank.org/wbi">Learning</a></li>
            <li class="news"><a href="http://www.worldbank.org/news">News</a></li>            
            <li class="project"><a href="http://www.worldbank.org/projects">Projects & Operations</a></li>
            <li class="publications last"><a href="http://www.worldbank.org/reference">Publications</a></li>
        </ul>    
        </td>
        <td class="sec-links">
        <ul class="links">
            <li class="countries first"><a href="http://www.worldbank.org/countries">Countries</a></li>
            <li class="topics last"><a href="http://www.worldbank.org/topics">Topics</a></li>
        </ul>
        </td>
    </tr>
    </table>
    </div>      

    <!-- title bar-->        
    <div id="title-bar" >
            <div class="site-name"><a href="http://data.worldbank.org/">Data</a></div>
    </div>
    
    <!-- data bar -->
    <div id="navigation">
    	<div class="limiter clear-block">
        	<ul class="links primary-links">
            <li ><a href="http://data.worldbank.org/country" class="spaces-menu-editable">By Country</a></li>
            <li ><a href="http://data.worldbank.org/topic">By Topic</a></li>
            <li ><a href="http://data.worldbank.org/indicator">Indicators</a></li>
            <li class=""><a href="http://data.worldbank.org/data-catalog">Data Catalog</a></li>
            <li class="last active"><a href="<?php echo site_url();?>/home">Microdata</a></li>
    		</ul>
            <ul class="links secondary-links">
            <li class="first"><a href="http://data.worldbank.org/news">News</a></li>
            <li class=""><a href="http://data.worldbank.org/about">About</a></li>
            <li class=""><a href="http://data.worldbank.org/developers">For Developers</a></li>
            <li class="last"><a href="http://data.worldbank.org/products">Products</a></li>
    		</ul>
        </div>
	</div>
    
	<div style="clear:both;"></div>
    
    <!-- content wrapper -->
    <div id="bd" >
        <div id="inner-body">           
            
            <!-- page contents -->	
            <div id="yui-main">            	
				<div id="content" class="yui-b">
                	&nbsp;
                    <?php /*?>
                    <!--share-bar -->
                    <div id="page-tools">
                    <?php include 'wb-share.php';?>
                    </div>
					<?php */?>
                
                    <!--breadcrumbs -->
                    <?php if (count($this->breadcrumb->to_array())>1):?>
					   <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
                        <?php if ($breadcrumbs_str!=''):?>
                            <div id="breadcrumb">
                            <?php echo $breadcrumbs_str;?>
                            </div>
                        <?php endif;?>
                    <?php endif;?>
            
            <?php 
				$tab_urls=array('microdata-catalogs','catalog','citations');
				$catalog_tabs=array('catalog');
			?>	
			<?php if (in_array($this->uri->segment(1),$tab_urls)) :?>
			<!-- tabs -->
            <h1><?php echo $active_repo_title;?></h1>
            <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs">
            	<?php if ($active_repo!=='central'):?>
                <div class="tab-heading">&nbsp;</div>
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                	<?php if ($this->uri->segment(1)=='microdata-catalogs'):?>                    
	                    <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>/about">About</a></li>
                    <?php elseif ($this->uri->segment(1)=='catalog' && $this->uri->segment(3)=='about'):?>
                    	<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>">About</a></li>
                    <?php else:?>
                        <li class="ui-state-default ui-corner-top"><a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>/about">About</a></li>
                    <?php endif;?>
                    <li class="ui-state-default ui-corner-top <?php echo (in_array($this->uri->segment(1),$catalog_tabs) && $this->uri->segment(3)!=='about') ? 'ui-tabs-selected ui-state-active' : '';?>"><a href="<?php echo site_url();?>/catalog/<?php echo $active_repo;?>">Datasets</a></li>
                    <?php if ($active_repo_citation_count>0):?>
                    <li class="ui-state-default ui-corner-top <?php echo ($this->uri->segment(1)=='citations') ? 'ui-tabs-selected ui-state-active' : '';?>"><a href="<?php echo site_url();?>/citations">Citations</a></li>
                    <?php endif;?>
                </ul>
                <?php endif;?>
                <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="tabs-1">
					<!--breadcrumbs -->
					<?php if (isset($breadcrumb) && $breadcrumb!==''):?>
						<div id="nada-breadcrumb">
						<?php echo $breadcrumb;?>
						</div>
					<?php endif;?>
                    <div style="clear:both;">
                    <!-- page content area -->
                    <?php echo isset($content) ? $content : '';?>
                    </div>
                </div>            
            </div>
            <?php else:?>
				<!--breadcrumbs -->
                <?php if (isset($breadcrumb) && $breadcrumb!==''):?>
                	<div id="nada-breadcrumb">
                	<?php echo $breadcrumb;?>
                    </div>
                <?php endif;?>
            	<?php echo isset($content) ? $content : '';?>
      		<?php endif;?>
            
      </div>
      </div>

            <!-- side bar -->
            <div id="sidebar" class="yui-b">
            <div class="sidebar-wrap" id="sidebar-wrap">            
                <div class="sidebar-nav">
				<?php $microdata_url=site_url();?>
                    <ul>
                    <li <?php echo ($this->uri->segment(1)=='home') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/home">Microdata Home</a></li>                    
                    <li <?php echo ($this->uri->segment(1)=='catalog') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/catalog/central">Central Microdata Catalog</a></li>
                    <li <?php echo ($this->uri->segment(1)=='contributing-catalogs') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/contributing-catalogs">Contributing Catalogs</a></li>
                    <li <?php echo ($this->uri->segment(1)=='about') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/about">About</a></li>
					<?php /* ?>
                    <li <?php echo (in_array($this->uri->segment(1),array('microdata-catalogs','catalog','citations'))) ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/microdata-catalogs">Microdata catalogs</a></li>
					<?php */ ?>
                    <?php /* ?>
                    <li <?php echo ($this->uri->segment(1)=='using-our-catalog') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/using-our-catalog">Using our Library</a></li>
					<?php */ ?>
                    <li <?php echo ($this->uri->segment(1)=='terms-of-use') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/terms-of-use">Terms of Use</a></li>
                    <li <?php echo ($this->uri->segment(1)=='practices-and-tools') ? 'class="selected"' : '';?>><a href="<?php echo $microdata_url;?>/practices-and-tools">Practices & Tools</a></li>
                    <li class="<?php echo ($this->uri->segment(1)=='faqs') ? 'selected' : '';?>"><a href="<?php echo $microdata_url;?>/faqs">Frequently Asked Questions</a></li>
                    </ul>
                </div>
                
                <div class="contact-us" >              		
                    <a href="mailto:microdata@worldbank.org">Contact us</a>
                </div>
                
                <div class="help-us">
                <h3>Help us to help you</h3>
                <p>The Microdata Library is a collaborative effort by data producers, curators, and users. The quality and completeness of the data and metadata we provide depend on their, and your contribution. There are various ways <a href="<?php echo $microdata_url; ?>/faqs#improve">you can participate</a>.</p>
                </div>
            </div>
			</div>            
		</div>
	</div>
    <!-- highlights -->
    <?php if (isset($this->blocks['highlights'])):?>
			<?php echo $this->blocks['highlights'];?>
    <?php endif;?>

	<!-- footer breadcrumb -->
	<div id="footer-breadcrumb">
    	<div class="limiter">
    		<?php $breadcrumbs= $this->breadcrumb->to_array();?>            
            <span class="breadcrumb-link">You are here</span>
            <span class="breadcrumb-link"><a href="http://data.worldbank.org/">Data</a></span>
            <span class="breadcrumb-link"><a href="http://microdata.worldbank.org" class="active">Microdata</a></span>
            <?php if (count($breadcrumbs)==1):?>
            <span class="breadcrumb-link breadcrumb-last"><?php echo $title;?></span>
            <?php endif;?>
			<?php /*
			//using slash as a seperator	
			<span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-item"><a href="http://microdata.worldbank.org" class="active">Microdata</a></span>
			*/ ?>
                        
            <?php $last_key=end(array_keys($breadcrumbs));?>
            <?php foreach($breadcrumbs as $link=>$title):?>
            	<?php if (strtolower($title)=="home"){continue;}?>
                <?php if ($link==$last_key):?>
                	<span class="breadcrumb-link breadcrumb-last"><a href="<?php echo site_url().'/'.$link;?>" class="active"><?php echo $title;?></a></span>
                <?php else:?>
                	<span class="breadcrumb-link"><a href="<?php echo site_url().'/'.$link;?>" class="active"><?php echo $title;?></a></span>
                <?php endif;?>
            <?php endforeach;?>
        </div>
        <div class="toggle" id="glob-nav-toggle"><img src="themes/<?php echo $this->template->theme();?>/plus.png" alt="Collapse/Expand" title="Collapse / Expand navigation"/></div>
	</div>

	<!--footer global navigation links -->
    <div id="footer-glob-links">
            <div class="columns">
              <h6><a href="http://www.worldbank.org/about">About</a></h6>
              <ul>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20040565~menuPK:1696892~pagePK:51123644~piPK:329829~theSitePK:29708,00.html">Mission</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20653660~menuPK:72312~pagePK:51123644~piPK:329829~theSitePK:29708,00.html">History</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20040913~menuPK:1696997~pagePK:51123644~piPK:329829~theSitePK:29708,00.html">Leadership</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20040580~menuPK:1696997~pagePK:51123644~piPK:329829~theSitePK:29708,00.html">Organization &amp;
                  Affiliates</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20437854~menuPK:1697253~pagePK:51123644~piPK:329829~theSitePK:29708,00.html">Public Outreach</a></li>
                <li><a href="http://www.worldbank.org/events">Events</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/NEWS/0,,contentMDK:20041497~menuPK:34489~pagePK:116743~piPK:36693~theSitePK:4607,00.html">People</a></li>
                <li><a href="http://www.worldbank.org/jobs">Jobs</a></li>
                <li><a href="http://www.worldbank.org/annualreport">Annual Reports</a></li>
              </ul>
            </div>

            <div class="columns">
              <h6><a href="http://www.worldbank.org/data">Data</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/data">Search Data</a></li>
                <li><a href="http://databank.worldbank.org/ddp/home.do?Step=12&amp;id=4&amp;CNO=2">Data Bank</a></li>
                <li><a href="http://data.worldbank.org/data-catalog">Catalog</a></li>
                <li><a href="http://data.worldbank.org/products">Data Publications &amp; Products</a></li>
                <li><a href="http://data.worldbank.org/developers">API-For Developers</a></li>
              </ul>
              <h6><a href="http://econ.worldbank.org">Research</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/research">Search Research</a></li>
                <li><a href="http://econ.worldbank.org/datasets">Tools &amp; Tables</a></li>
                <li><a href="http://www.worldbank.org/reference/">Research Publications &amp; Products</a></li>
              </ul>
            </div>

			<div class="columns">
              <h6><a href="http://wbi.worldbank.org/wbi/">Learning</a></h6>
              <ul>
                <li><a href="http://gdln.org">Global Development Learning Network</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/TOPICS/EXTCDRC/0,,menuPK:64169181~pagePK:64169192~piPK:64169180~theSitePK:489952,00.html">Capacity Development Resource Center</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/DATASTATISTICS/SCBEXTERNAL/0,,contentMDK:20100922~menuPK:982273~pagePK:229544~piPK:229605~theSitePK:239427,00.html">Statistical Capacity Building</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/WBI/EXTWBISFP/0,,menuPK:551559~pagePK:64168427~piPK:64168435~theSitePK:551553,00.html">Scholarships &amp; Fellowships</a></li>
              </ul>
            </div>
            
            <div class="columns">
              <h6><a href="http://www.worldbank.org/news">News</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/newsviews/news">Search for News</a></li>
                <li><a href="http://blogs.worldbank.org">Blogs</a></li>
                <li><a href="http://www.worldbank.org/multimedia">Multimedia</a></li>
                <li><a href="http://media.worldbank.org/">Media Briefing Center</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/NEWS/0,,contentMDK:20035595~menuPK:36691~pagePK:116743~piPK:36693~theSitePK:4607,00.html">Media Contacts</a></li>
              </ul>
            </div>

			<div class="columns">
              <h6><a href="http://www.worldbank.org/operations">Projects &amp; Operations</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/projects">Search Projects</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,contentMDK:21790401~menuPK:5119395~pagePK:41367~piPK:51533~theSitePK:40941,00.html">Our Focus</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,contentMDK:20120721~menuPK:232467~pagePK:41367~piPK:51533~theSitePK:40941,00.html">Product &amp; Services</a></li>
                <li><a href="http://www.worldbank.org/projects">Projects</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,menuPK:64383817~pagePK:64387457~piPK:64387543~theSitePK:40941,00.html">Country Lending</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/PROCUREMENT/0,,pagePK:84271~theSitePK:84266,00.html">Procurement</a></li>
                <li><a href="http://www.worldbank.org/ieg/">Project Evaluations</a></li>
                <li><a href="http://www.worldbank.org/results">Results</a></li>
              </ul>
            </div>
            
            <div class="columns">
              <h6><a href="http://www.worldbank.org/reference/">Publications</a></h6>
              <ul>
                <li><a href="http://publications.worldbank.org/">Bookstore</a></li>
                <li><a href="http://www-wds.worldbank.org/WBSITE/EXTERNAL/EXTWDS/0,,detailPagemenuPK:64187510~menuPK:64187513~pagePK:64187848~piPK:64187934~searchPagemenuPK:64187283~siteName:WDS~theSitePK:523679,00.html">Documents &amp; Reports</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/EXTARCHIVES/0,,pagePK:38167~theSitePK:29506,00.html">Archives</a></li>
                <li><a href="http://jolis.worldbankimflib.org/external.htm">Libraries</a></li>
              </ul>
            </div>

            <div class="columns border-vert">
              <h6><a href="http://www.worldbank.org/countries">Countries</a></h6>
              <h6><a href="http://www.worldbank.org/topics">Topics</a></h6>
              <h6>Resources for</h6>
              <ul>
                <li><a href="http://www.worldbank.org/civilsociety">Civil Society</a></li>
                <li><a href="http://clientconnection.worldbank.org">Parliamentarians</a></li>
                <li><a href="http://treasury.worldbank.org/">Investors</a></li>
                <li><a href="http://www.worldbank.org/news">Journalists</a></li>
                <li><a href="http://youthink.worldbank.org">Students &amp; Teachers</a></li>
              </ul>
            </div>
          </div>

	<!-- footer network -->
    <div id="footer-network">
        <div class="limiter clear-block">
        <a href="http://www.worldbank.org" class="network-name">The World Bank</a>
		<div class="footer-nav">
          	<ul class="links footer-links">
                <li class="sitemap first"><a href="http://www.worldbank.org/sitemap">IBRD</a></li>
                <li class="index"><a href="http://www.worldbank.org/siteindex">IDA</a></li>
                <li class="faq"><a href="http://www.worldbank.org/help">IFC</a></li>
                <li class="contact"><a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/0,,contentMDK:20041066~menuPK:34582~pagePK:43912~piPK:44037~theSitePK:29708,00.html">MGA</a></li>
                <li class="search"><a href="http://www.worldbank.org/search.htm">ICSID</a></li>
        	</ul>
        </div>
        <div class="footer-nav-right">
	        <ul class="links footer-links">
                <li class="first"><a href="http://www.worldbank.org/sitemap">Events</a></li>
                <li ><a href="http://www.worldbank.org/siteindex">Publications</a></li>
                <li ><a href="http://www.worldbank.org/help">Bookstore</a></li>
        	</ul>
        </div>    
        </div>
    </div>

	<!-- footer legal & copyrighs -->
    <div id="footer-legal">
        <div class="limiter clear-block">
		<div class="footer-nav" style="float:left;">
			<a class="first" href="http://www.worldbank.org/legalinfo">Legal</a> | 
            <a href="http://www.worldbank.org/sitemap">Site Map</a> | 
            <a href="http://go.worldbank.org/NLCQMY3UZ0">Job & Scholarships</a> | 
            <a href="http://go.worldbank.org/TRCDVYJ440">Get Involved</a> |
            <a href="http://go.worldbank.org/TRCDVYJ440">Stay Connected</a> |
            <a href="http://www.worldbank.org/contacts">Contact</a>
            <div>©2011 The World Bank Group, All Rights Reserved</div>
        </div>
        <div class="footer-nav-right">
			<a href="http://web.worldbank.org/WBSITE/EXTERNAL/EXTABOUTUS/ORGANIZATION/ORGUNITS/EXTDOII/0,,contentMDK:20659616~menuPK:1702202~pagePK:64168445~piPK:64168309~theSitePK:588921,00.html"><strong>Fraud &amp; Corruption Hotline</strong></a>
            <br />1-800-831-0463
        </div>    
        </div>
    </div>

    <!-- footer -->    
	<div id="ft">&nbsp;</div>

</div>
<div style="padding-bottom:100px;">&nbsp;</div>
<?php $this->load->view("tracker/js_tracker");?>
</body>
</html>