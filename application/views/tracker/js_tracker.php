<?php
/**
* Place JS based tracking code here
*
* e.g. Google site analytics or Omniture js
**/

//load omniture configurations
$this->load->config('omniture');

//get the active repository name
$active_repo=strtolower($this->session->userdata('active_repository'));

//set dev account by default
$s_account=$this->config->item("omniture_s_account_dev");

//check if running in development or production
if (defined('ENVIRONMENT') && ENVIRONMENT=='production')
{
	$s_account=$this->config->item("omniture_s_account_prod");
}

?>
<script language="JavaScript" src="http://siteresources.worldbank.org/SITEMGR/Resources/WebStatsUtil.js"></script>
<script language="JavaScript">
<!--
var s_repo="";
<?php if ($active_repo):?>
	s_repo=" > <?php echo strtoupper($active_repo);?>";
<?php endif;?>
var s_pageName="";
var s_channel="DEC DDP Microdata Catalog"+s_repo;
var s_hier1 = "DEC,DEC DDP Microdata Catalog";
var s_prop1 = "";
var s_prop2 = "Not Available";  /* Author */
var s_prop3 = "Not Available";  /* Date */
var s_prop4 = "Not Available";  /* Topic */
var s_prop5 = "Not Available";  /* Sub Topic */
var s_prop6 = "Not Available";  /* Region */
var s_prop7 = "Not Available";  /* Country */
var s_prop8 = "Not Available";  /* DocType */
var s_prop9 = "Not Available";  /* MDK or unique identifier */
var s_prop10 = "Live";          /* Site Status */
var s_prop11 = "Not Available"; /* Data Source */
var s_prop13 = "DEC";           /* VPU */
var s_prop14 = "window.location";  /* URL */
var s_prop16="English";         /* doc language */
var s_prop17="English";         /* site language */

var omnitureCookieContent= getCookieValueByName('omniture');							 
	var s_prop18 =getVPU(omnitureCookieContent);	
	var s_prop19 =getDutyStation(omnitureCookieContent);
	var s_prop20 =getUserType(omnitureCookieContent);

var sPageNameBase = "DDP Microdata > ";
var sTitle = document.title;

s_pageName = sPageNameBase + sTitle;
s_prop1 = sTitle;
s_hier1 += ", " + sTitle;

var s_account="<?php echo $s_account;?>";
//-->
</script>
<script language="JavaScript" src="http://siteresources.worldbank.org/scripts/s_code_remote.js"></script>

<script language="JavaScript">

$(document).ready(function() 
{	//custom links
	$(".download").click(function(e) {
		s_linkType="d";
		s_linkName=$(this).attr("href")+'/'+$(this).attr("title");
		s_prop1="prop1";
		s_lnk=s_co($(this).html()); 
		s_gs("<?php echo $s_account;?>");
	});
});
</script>