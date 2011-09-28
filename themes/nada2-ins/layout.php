<?php header('Content-Type: text/html; charset=utf-8');	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir='rtl'  xmlns="http://www.w3.org/1999/xhtml" >

<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" /> 
<title><?php echo $page_title; ?></title>
<link href="templates/themeins/default.css" rel="stylesheet" type="text/css" />
<?php 
	if (isset($page_js_css) ){ 
		echo $page_js_css;
	} 
?>

</head>

<body class=" yui-skin-sam">

<!--document layout-->
<table border="0" style="width:780px;border-collapse:collapse" cellspacing="0"  cellpadding="0" align="center">
	<!--login-->
    <tr>
        <td colspan="3" ><?php require_once 'user_bar.php';?></td>        
    </tr>

	<!--header-->
    <tr>
        <td colspan="3" >
            <div style="border:1px solid silver;margin-bottom:5px;padding:0px;margin-top:5px;background-image:">
            <img src="<?php echo THEME_FOLDER_REL.'/'.THEME; ?>/images/banner4.gif" border="" title="<?php echo WEBSITE_TITLE; ?>" alt="Site banner"/>
            </div>
        </td>        
    </tr>
    <!--content/menu-->
    <tr style="height:400px;" valign="top">
    	<!--left menu -->
        <td style="width:200px" class="menu-container">
	        <div>  <?php include 'menu.php'; ?></div>
        </td>
        <td style="width:5px;"></td>
        <!--content -->
        <td class="content-container" style="width:575px">
        	<div style="margin:5px;margin-bottom:20px;" id="page-content">
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
          </div>
        </td>
    </tr>
    <!--footer-->
    <tr class="footer-container" >
        <td colspan="3" >
            <div class="footer" id="footer" style="border:1px solid #2d5590;padding:5px;margin-top:5px;background-color:#5880bb;">
                <div style="text-align:center;color:white"><strong><font color="#ffffff" size="2">Site de la diffusion de la&nbsp;documentation des enqu&ecirc;tes</font></strong></div>
            </div>
        </td>        
    </tr>
</table>
</body>
</html>