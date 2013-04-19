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

$survey_id=FALSE;
$repository_id=FALSE;
$survey_country='';

//var_dump($this->uri->segment(1));exit;
if (in_array($this->uri->segment(1),array('access_public','catalog','access_licensed')) && is_numeric($this->uri->segment(2)))
{	
	$survey_country=$this->breadcrumb->get_survey_country($this->uri->segment(2));
	$repo_row=$this->breadcrumb->get_survey_owner_repo($this->uri->segment(2));
	if ($repo_row)
	{
		$survey_id=$this->uri->segment(2);
		$repository_id=strtoupper($repo_row['repositoryid']);		
	}	
}
else if($this->uri->segment(1)=='access_licensed' && $this->uri->segment(2)=='track' && is_numeric($this->uri->segment(3)))
{
	$survey_id=$this->breadcrumb->get_study_id_by_licensed_request_id($this->uri->segment(3));
	$survey_country=$this->breadcrumb->get_survey_country($survey_id);
	$repo_row=$this->breadcrumb->get_survey_owner_repo($survey_id);
	if ($repo_row)
	{
		$repository_id=strtoupper($repo_row['repositoryid']);		
	}	
}



?>
<script language="JavaScript" src="javascript/wb/WebStatsUtil.js"></script>
<script language="JavaScript">
<!--
var s_repo=" > CENTRAL"; <?php /*default value in case no repo is set*/?>
<?php if ($repository_id!==FALSE && strtolower($repository_id)!=='central') :?>
		s_repo=" > CENTRAL > <?php echo strtoupper($repository_id);?>";
<?php elseif (strtolower($active_repo)=='central'):?>
	s_repo=" > <?php echo strtoupper($active_repo);?>";
<?php endif;?>
var s_pageName="";
var s_channel="DEC Microdata Library INT"+s_repo;
var s_hier1 = "DEC,DEC Microdata Library INT"; <?php /*hier* variables are not used by bank, atleast don't see them on the sitecatalyst */?>
var s_prop1 = "";
var s_prop2 = "Not Available";  /* Author */
var s_prop3 = "Not Available";  /* Date */
var s_prop4 = "Not Available";  /* Topic */
var s_prop5 = "Not Available";  /* Sub Topic */
var s_prop6 = "Not Available";  /* Region */
var s_prop7 = "<?php echo $survey_country;?>";  /* Country */
var s_prop8 = "Not Available";  /* DocType */
var s_prop9 = "<?php echo $repository_id;?>:<?php echo $survey_id;?>";  /* MDK or unique identifier */
var s_prop10 = "Live";          /* Site Status */
var s_prop11 = "<?php echo $repository_id;?>"; /* Data Source */
var s_prop13 = "DEC";           /* VPU */
var s_prop14 = "window.location";  /* URL */
var s_prop16="English";         /* doc language */
var s_prop17="English";         /* site language */

var omnitureCookieContent= getCookieValueByName('omniture');							 
	var s_prop18 =getVPU(omnitureCookieContent);	
	var s_prop19 =getDutyStation(omnitureCookieContent);
	var s_prop20 =getUserType(omnitureCookieContent);

var sPageNameBase = "Microdata Library > ";
var sTitle = document.title;

s_pageName = sPageNameBase + sTitle;
s_prop1 = sTitle;
s_hier1 += ", " + sTitle;

var s_account="<?php echo $s_account;?>";
//-->
</script>
<script language="JavaScript" src="javascript/wb/s_code_remote.js"></script>

<script language="JavaScript">
$(document).ready(function() 
{	
	//make all download links open in new 
	$(".download").attr('target', '_blank');
	
	//custom links
	$(".download").mousedown(function(e) {	
		s_linkType="d";
		s_linkName=$(this).attr("href")+'/'+$(this).attr("title");
		s_prop1="DL:<?php echo $repository_id;?>:<?php echo $survey_id;?>:"+$(this).attr("title");
		s_lnk=s_co($(this).html()); 
		s_gs("<?php echo $s_account;?>");
	});
});
</script>