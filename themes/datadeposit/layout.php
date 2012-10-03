<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?php
$menu_horizontal=TRUE;

//side menu
$data['menus']= $this->Menu_model->select_all();		
$sidebar=$this->load->view('default_menu', $data,true);

//repositories
$repo_arr=$this->Repository_model->get_repositories($published=TRUE,$system=FALSE);
$repositories_sidebar=$this->load->view("repositories/public_sidebar",array('rows'=>$repo_arr),TRUE);

//load blocks for the current page
$this->blocks=$this->Menu_model->get_blocks($this->uri->segment(1));

//is home page
$is_home=FALSE;
if ($this->uri->segment(1)=="home") {$is_home=TRUE;}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $title;?></title>
<base href="<?php echo js_base_url(); ?>">
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/css/ihpv3_intranet_20110418.css">
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />

<script type="text/javascript" src="javascript/jquery.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<SCRIPT LANGUAGE = javascript>
if (top.frames.length!=0)
top.location=self.document.location;
</SCRIPT>

<style>
#content{padding:10px;}
/*.page-contents{padding-left:250px;position:relative;padding-right:250px;}*/

x.sidebar{float:left;position:absolute;top:0px;left:0px}
.sidebar-content{font-size:13px;padding:10px;}
.siebar ul,.siebar li{list-style:circle;}
.sidebar-caption{color:black;padding:5px;border-bottom:1px solid gray;font-size:12px;text-align:center;}

</style>

<style type="text/css">
.footer{font-size:0.689em;background-color:#f5f3f5;color:#4177bd;overflow:hidden;padding-bottom:0px;clear:both;margin:15px 10px 0;zoom:1;}
.footerLeftCorner{background:#f5f3f5 url(themes/<?php echo $this->template->theme();?>/css/img_sprite.gif) no-repeat left -666px;}
.footerWarning{text-align:center;color:#333; padding-top:7px; padding-bottom:5px;background: url(themes/<?php echo $this->template->theme();?>/css/img_sprite.gif) no-repeat right -710px;}
.footer ul{margin:0 0 8px;padding:0px 9px 0 10px;float:left}
.footer ul.f-lt-links{list-style-type:none}
.footer ul.f-lt-links span{padding: 0 1px 0 3px}
.footer ul.f-rt-links{float:right}
.footer ul.f-rt-links span{padding: 0 2px 0 4px}
.footer li{display:inline}
.footerlinkwidth {margin:0 auto;width:600px;}
</style>


</head>
<body>
<div>
<div id="contentareblk">	
		<div class="width100">
	
	
</div>


	
</div>


<?php //if (!$is_home):?>

    <!--login information bar-->
    <span id="user-container">
    <?php $this->load->view('user_bar2');?>
    </span>

    <!--share-bar -->
    <!--
    <div id="page-tools">
    <?php //include 'share.php';?>
    </div>
    -->
<?php //endif;?>

<div id="doc3" class="yui-t2" >

<?php
	//pages with two columns
	$two_col_urls=array('catalog','citations');
	$sub_notab_segments=array('history');
	$yui_class="";
	//if (in_array($this->uri->segment(1),$two_col_urls))
	if (isset($this->blocks['rightsidebar']))
	{
		$yui_class="yui-ge";
	}
?>

<div id="bd">
		
	<div id="yui-main" >
		<div class="yui-b" >
		  <div class="<?php echo $yui_class;?>">
			  <div class="yui-u first" >
                    <!-- main content area-->
                   <!--page-contents-->
                    <div class="page-contents">
                    <?php if ($this->uri->segment(1)=='catalog' && !in_array($this->uri->segment(2),$sub_notab_segments)):?>
                    <?php else:?>            
                            <?php if (!$is_home):?>

                            <?php endif;?>    
                        <?php echo isset($content) ? $content : '';?>
                    <?php endif;?>
                    </div>                    
			  </div>
			  
              <?php if (isset($this->blocks['rightsidebar'])):?>
              <!-- right sidebar -->	
              <div class="yui-u right-col">
               <?php @include 'right-sidebar.php';?>
			  </div>
              <!--end right sidebar -->
              <?php endif;?>
		</div>		
		</div>
	</div>

	<!-- left side bar -->	
    <div class="yui-b" id="tocWrapper">
    <?php @include 'sidebar.php';?>
    </div>
	<!-- end left side bar -->
	
</div>
<!--end bd-->



<div style="margin-top:10px;clear:both;">&nbsp;</div>

		
<div id="tableBody" style="display: none">

				<table border="0" bgcolor="lightslategray" align="center" id="tableBody"> <thead bgcolor="lightskyblue"> <tr>   <th>S.No</th><th width="200px">Variable Name</th><th width="400px">Value Posted</th></tr> </thead><tbody bgcolor="lemonchiffon"></tbody></table></div>
				<div id="mypopup" name="mypopup" onstyle="position: absolute; width: 600px; height: 400px; display: none; background: #ddd; border: 1px solid #000; right: 50px; top: 500px">
				</div>
				<form name="propForm" id="propForm"><input id="isHStatscode" type="hidden" value="false"><input id="isGStatscode" type="hidden" value="true"></form>
</div>
</div>
<?php $this->load->view("tracker/js_tracker");?>
</body>
</html>