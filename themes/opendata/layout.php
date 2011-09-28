<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<?php
//load blocks for the current page
$this->blocks=$this->Menu_model->get_blocks($this->uri->segment(1));

$menu_horizontal=FALSE;
$secondary_menu=FALSE;

//get menu info by url
$menu=$this->Menu_model->get_menu_by_url($this->uri->segment(1));

if ($menu)
{
	//child page, get submenu
	if ($menu['pid']>0)
	{
		//get parent+sub-menu items
		$secondary_menu=$this->Menu_model->get_secondary_menu($menu['pid']);
	}
	else
	{	
		//get parent+sub-menu items
		$secondary_menu=$this->Menu_model->get_secondary_menu($menu['id']);
	}		
}

$secondary_menu_formatted=FALSE;

//create a formatted listed of sub menus
if ($secondary_menu!==FALSE && count($secondary_menu)>1)
{
	$secondary_menu_formatted='<ul>';
	foreach($secondary_menu as $item)
	{
		$selected="";
		if ($this->uri->segment(1)==$item['url'])
		{
			$selected=' class="selected"';
		}
		$secondary_menu_formatted.='<li '.$selected.'>'.anchor($item['url'],$item['title']).'</li>';
	}
	$secondary_menu_formatted.='</ul>';	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo t($title);?></title>
<base href="<?php echo base_url(); ?>" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="javascript/superfish/css/superfish.css" media="screen">
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />

<script type="text/javascript" src="javascript/jquery.js"></script>
<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>
<script type="text/javascript" src="http://localhost/nada3/javascript/jquery/ui/ui.tabs.js"></script>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/opendata.css" />

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<!--superfish menu-->
<script type="text/javascript" src="javascript/superfish/js/hoverIntent.js"></script>
<script type="text/javascript" src="javascript/superfish/js/superfish.js"></script>

<script type="text/javascript">
jQuery(function(){
	jQuery('ul.sf-menu').superfish();
	$("#tabs").tabs();
	$("#footer-glob-links").hide();
	$("#glob-nav-toggle").click(function(e){toggle_global_nav();});
	$("#sidebar-wrap").height($("#yui-main").height());//fix sidebar height	
});

$(this).ajaxComplete(function() {
  $("#sidebar-wrap").height("100px");
  $("#sidebar-wrap").height($("#yui-main").height());//fix sidebar height
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
<!--[if IE]>
<style type="text/css">
	x.ui-tabs .ui-tabs-nav li.ui-tabs-selected{height:32px;}
    .ui-tabs .ui-tabs-nav li.ui-tabs-selected, .ui-tabs .ui-tabs-nav li{position:relative;top:4px;height:30px;}
    .ui-tabs .ui-tabs-nav li a{padding:5px;}
</style>
<!<[endif]-->

</head>
<body>
<div id="custom-doc" class="<?php echo ($secondary_menu_formatted!==FALSE) ? 'yui-t1' : 'yui-t1'; ?>" > 
	<!--login information bar-->
    <?php //$this->load->view('user_bar');?>	
    
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
        <td class="home-icon"><a href="http://www.worldbank.org/" class="home"><img alt="HOME" src="http://www.worldbank.org/wb/images/cache30/homepage/home-icon-red.png" title="World Bank home"></a></td>    
        <td class="pri-links">
        <ul class="links">
            <li class="about first"><a href="http://www.worldbank.org/about">Who we are</a></li>
            <li class="research"><a href="http://econ.worldbank.org">Operations</a></li>
            <li class="data"><a href="http://www.worldbank.org/data" class="active" >Data</a></li>            
            <li class="learning"><a href="http://www.worldbank.org/wbi">Research</a></li>
            <li class="news"><a href="http://www.worldbank.org/news">Ideas</a></li>
            <li class="projects last"><a href="http://www.worldbank.org/projects">News & views</a></li>
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
	<div style="clear:both;"></div>
    
    <!-- content wrapper -->
    <div id="bd" >
        <div id="inner-body">           
            
            <!-- page contents -->	
            <div id="yui-main">            	
				<div id="content" class="yui-b">
                	<!-- bread crumbs -->
					<div id="breadcrumb">
                    	<div id="page-tools"></div>
                    </div>
      
      		<h1>Microdata Library</h1>
			<!-- page tabs -->
            <div id="tabs"> 
                <div class="tab-heading">&nbsp;</div>
                <ul> 
                    <li><a href="#tabs-1">Home</a></li>
                    <li><a href="#data-catalog">Datasets</a></li> 
                    <li><a href="#tabs-3">Citations</a></li> 
                </ul> 
            
                <div id="tabs-1"> 
                    <p>Proin elit arcu, rutrum commodo, vehicula tempus, commodo a, risus. Curabitur nec arcu. Donec sollicitudin mi sit amet mauris. Nam elementum quam ullamcorper ante. Etiam aliquet massa et lorem. Mauris dapibus lacus auctor risus. Aenean tempor ullamcorper leo. Vivamus sed magna quis ligula eleifend adipiscing. Duis orci. Aliquam sodales tortor vitae ipsum. Aliquam nulla. Duis aliquam molestie erat. Ut et mauris vel pede varius sollicitudin. Sed ut dolor nec orci tincidunt interdum. Phasellus ipsum. Nunc tristique tempus lectus.</p> 
                </div> 
                <div id="data-catalog"> 
                     <!-- page content area -->
					<?php echo isset($content) ? $content : '';?>
                </div> 
                <div id="tabs-3"> 
                    <p>Mauris eleifend est et turpis. Duis id erat. Suspendisse potenti. Aliquam vulputate, pede vel vehicula accumsan, mi neque rutrum erat, eu congue orci lorem eget lorem. Vestibulum non ante. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Fusce sodales. Quisque eu urna vel enim commodo pellentesque. Praesent eu risus hendrerit ligula tempus pretium. Curabitur lorem enim, pretium nec, feugiat nec, luctus a, lacus.</p> 
                    <p>Duis cursus. Maecenas ligula eros, blandit nec, pharetra at, semper at, magna. Nullam ac lacus. Nulla facilisi. Praesent viverra justo vitae neque. Praesent blandit adipiscing velit. Suspendisse potenti. Donec mattis, pede vel pharetra blandit, magna ligula faucibus eros, id euismod lacus dolor eget odio. Nam scelerisque. Donec non libero sed nulla mattis commodo. Ut sagittis. Donec nisi lectus, feugiat porttitor, tempor ac, tempor vitae, pede. Aenean vehicula velit eu tellus interdum rutrum. Maecenas commodo. Pellentesque nec elit. Fusce in lacus. Vivamus a libero vitae lectus hendrerit hendrerit.</p> 
                </div> 
            </div> 
                                 
                </div>
            </div>
            
            <!-- side bar -->
            <div id="sidebar" class="yui-b">
            <div class="sidebar-wrap" id="sidebar-wrap">            
                <div class="sidebar-nav">
					<?php //echo isset($sidebar) ? $sidebar : '';?>
                    <ul>
                    <li>
                        <a href="http://localhost/nada3/index.php/home">Microdata library</a>
                    </li>
                    <li class="selected">
                        <a href="http://localhost/nada3/index.php/catalog">Home</a>
                    </li>
                    <li>
                        <a href="http://localhost/nada3/index.php/citations">Data portal</a>
                    </li>
                    <li>
                        <a target="_blank" href="http://localhost/nada3/index.php/another-test">Contributing catalogs</a>
                    </li>
                    <li>
                        <a target="_blank" href="http://www.google.com">Practices and standards</a>
                    </li>
                    <li class="last">
                        <a target="_blank" href="http://www.google.com">Terms of use</a>
                    </li>
                    </ul>
                </div>
                <div>some stuff here</div>
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
    		<span class="breadcrumb-link">You are here</span>
            <span class="breadcrumb-item"><a href="http://data.worldbank.org/">Data</a></span>
            <span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-item"><a href="http://data.worldbank.org/data-catalog" class="active">Data Catalog</a></span>      
        </div>
        <div class="toggle" id="glob-nav-toggle"><img src="themes/<?php echo $this->template->theme();?>/plus.png" alt="Collapse/Expand" title="Collapse / Expand navigation"/></div>
	</div>

	<!--footer global navigation links -->
    <div id="footer-glob-links">
            <div class="columns">
              <h6><a href="http://www.worldbank.org/about">Who We Are</a></h6>
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
              <h6><a href="http://www.worldbank.org/operations">Lending & Advice</a></h6>
              <ul>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,contentMDK:21790401~menuPK:5119395~pagePK:41367~piPK:51533~theSitePK:40941,00.html">Our Focus</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,contentMDK:20120721~menuPK:232467~pagePK:41367~piPK:51533~theSitePK:40941,00.html">Product &amp; Services</a></li>
                <li><a href="http://www.worldbank.org/projects">Projects</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/0,,menuPK:64383817~pagePK:64387457~piPK:64387543~theSitePK:40941,00.html">Country Lending</a></li>
                <li><a href="http://web.worldbank.org/WBSITE/EXTERNAL/PROJECTS/PROCUREMENT/0,,pagePK:84271~theSitePK:84266,00.html">Procurement</a></li>
                <li><a href="http://www.worldbank.org/ieg/">Project Evaluations</a></li>
              </ul>
            </div>

            <div class="columns">
              <h6><a href="http://www.worldbank.org/data">Data</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/data">Search Data & Statistics</a></li>
                <li><a href="">References & Tables</a></li>
                <li><a href="http://data.worldbank.org/products">Data Publications &amp; Products</a></li>
                <li><a href="">About Our Data</a></li>
              </ul>
            </div>

            <div class="columns">
              <h6><a href="http://econ.worldbank.org">Projecsts & Operations</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/research">Search Research & Analysis</a></li>
                <li><a href="http://econ.worldbank.org/datasets">Analysis Tools &amp; Tables</a></li>
                <li><a href="http://www.worldbank.org/reference/">Research & Analysis</a></li>
                <li><a href="http://search.worldbank.org/research">Publications & Products</a></li>
                <li><a href="http://econ.worldbank.org/datasets">About Our Research</a></li>
              </ul>
              <h6><a href="">Results</a></h6>
            </div>
            
            <div class="columns">
              <h6><a href="http://www.worldbank.org/news">News & Views</a></h6>
              <ul>
                <li><a href="http://search.worldbank.org/newsviews/news">News</a></li>
                <li><a href="http://blogs.worldbank.org">Blogs</a></li>
                <li><a href="http://www.worldbank.org/multimedia">Multimedia</a></li>
                <li><a href="http://media.worldbank.org/">Get Involved</a></li>
                <li><a href="">Stay Connected</a></li>
                <li><a href="">Embargoed News for the Media</a></li>
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
</body>
</html>