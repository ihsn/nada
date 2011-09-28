<?php header('Content-Type: text/html; charset=utf-8');	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<title><?php echo $page_title; ?></title>
<?php 
	if (isset($page_js_css) ){ 
		echo $page_js_css;
	} 
?>
<link href="templates/enterprise/default.css" rel="stylesheet" type="text/css" />
<style>

div#footer {
border-top:1px solid #A8B8CD;
clear:both;
float:left;
font-size:0.8em;
height:2.8em;
margin-top:1em;
padding:0.2em 0 0;
width:100%;
}
ul.hNavMenu li {
float:left;
padding:0 0.8em;
}
ul.hNavMenu {
list-style:none outside none;
margin:0;
padding:0;
}
div#footer ul.footer li {
border-right:1px solid Gray;
height:1em;
padding:0 0.5em;
}

div#footer div#worldbanklogo a {
background:url("http://www.enterprisesurveys.org/content/images/portalheader/WorldBankLogo.gif") no-repeat scroll left center transparent;
float:left;
height:2.8em;
margin-top:-0.2em;
width:2.5em;
}

div#footer div.IFCLogo a {
background:url("http://www.enterprisesurveys.org/content/images/portalheader/IFCFullLogo.gif") no-repeat scroll right top transparent;
border-bottom:medium none;
float:right;
height:2.8em;
margin-top:-2.5em;
width:15em;
}



.header-menu {background-color:#5E5E5E;padding:0px;height:35px;}
#NavMenu1{list-style:none;float:left;padding:0px;padding-left:5px;}
#NavMenu1 li{list-style:none;float:left;color:white;padding-right:15px;}
#NavMenu1 li a{color:white;font-size:11px; }

</style>
</head>
<body class=" yui-skin-sam">
<!--document layout-->
<table border="0" style="width:780px;border-collapse:collapse" cellspacing="0"  cellpadding="0" >
<?php /*
  <!--login-->
  <tr>
    <td colspan="3" >< ?php require_once 'user_bar.php';? ></td>
  </tr>
  */
  ?>
  <!--header-->
  <tr>
    <td colspan="3" >
    <div style="border:0px solid silver;margin-bottom:0px;padding:0px;margin-top:5px;"> <img src="<?php echo THEME_FOLDER_REL.'/'.THEME; ?>/images/banner.png" border="0" title="<?php echo WEBSITE_TITLE; ?>" alt="Site banner"/> </div>
    <div class="header-menu">
    <ul id="NavMenu1" style="margin-top:10px;"><li key="true"><a href="https://www.enterprisesurveys.org/">Home</a></li><li key="true"><a href="https://www.enterprisesurveys.org/meetteam/">Meet the Team</a></li><li class="active" key="true"><a href="https://www.enterprisesurveys.org/portal/">Full Survey Data</a></li><li key="true"><a href="https://www.enterprisesurveys.org/researchpapers/">Research</a></li><li key="true"><a href="https://www.enterprisesurveys.org/custom/">Do Your Own Analysis</a></li><li key="true"><a href="https://www.enterprisesurveys.org/financialcrisis/">Financial Crisis</a></li><li key="true"><a href="https://www.enterprisesurveys.org/countryprofiles/">Country Notes</a></li><li key="true"><a href="https://www.enterprisesurveys.org/methodology/">Methodology</a></li><li key="true"><a href="https://www.enterprisesurveys.org/downloads/">Ask a Question</a></li></ul>
    </div>
    </td>    
  </tr>
  <!--content/menu-->
  <tr style="height:400px;" valign="top">
    <!--content -->
    <td class="content-container" style="width:661px"><div style="margin:5px;margin-bottom:20px;" id="page-content">
        <div id="content-title-container">
          <div class="page-title" id="xpage-title">
            <?php 				 
                            //global $page_title, $page_title_custom;
                            if ($page_title_custom==null){
                                echo $page_title; 
                            }
                            else{
                                echo $page_title_custom;
                            }
                     ?>
          </div>
          <div id="page-actions"><?php echo $page_links;?></div>
          <br/>
        </div>
        <div><?php echo $page_content; ?></div>
      </div></td>
    <td style="width:15px;"></td>
    <td style="background-color:#F5F5F5;padding:10px;"><?php include 'pages/list.html';?></td>
  </tr>
  <!--footer-->
  <tr class="footer-container" >
    <td colspan="3" ><div id="ctl00_dvFooter">
        <div id='footer'>
          <div id='worldbanklogo'><a href= 'http://www.worldbank.org'></a></div>
          <div id="ctl00_ctl07_englishFooterText">
            <ul id="HTMLMenuInserter1" class="hNavMenu footer">
              <li key="true"><a href="./">Home</a></li>
              <li key="true"><a href="MeetTeam/">Meet the Team</a></li>
              <li key="true"><a href="Portal/">Full Survey Data</a></li>
              <li key="true"><a href="ResearchPapers/">Research</a></li>
              <li key="true"><a href="Custom/">Do Your Own Analysis</a></li>
              <li key="true"><a href="CountryProfiles/">Country Notes</a></li>
              <li key="true"><a href="Methodology/">Methodology</a></li>
              <li key="true"><a href="Downloads/">Ask a Question</a></li>
            </ul>
            <br />
            &nbsp;&nbsp;&nbsp;&#169;2010 The World Bank Group, All Rights Reserved. <a href="http://web.worldbank.org/WBSITE/EXTERNAL/0,,contentMDK:20130471~menuPK:1041850~pagePK:50016803~piPK:50016805~theSitePK:13,00.html">Terms and Conditions.</a> <a href="http://web.worldbank.org/WBSITE/EXTERNAL/0,,contentMDK:20130472~menuPK:1041850~pagePK:50016803~piPK:50016805~theSitePK:13,00.html">Privacy Policy.</a>
            <div class='IFCLogo'><a href= 'http://www.ifc.org'></a></div>
          </div>
        </div>
      </div>
      </div>
</td>
  </tr>
</table>
</body>
</html>
