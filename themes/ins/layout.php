<?php header('Content-Type: text/html; charset=utf-8');	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir='rtl'  xmlns="http://www.w3.org/1999/xhtml" >

<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" /> 
<title><?php echo $title; ?></title>
<base href="<?php echo base_url(); ?>" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/default.css" />
<script type="text/javascript" src="javascript/jquery.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

</head>

<body class=" yui-skin-sam">

<!--document layout-->
<table border="0" style="width:780px;border-collapse:collapse" cellspacing="0"  cellpadding="0" align="center">
	<!--login-->
    <tr>
        <td colspan="3" ><?php $this->load->view('user_bar');?></td>        
    </tr>

	<!--header-->
    <tr>
        <td colspan="3" >
            <div style="border:1px solid silver;margin-bottom:5px;padding:0px;margin-top:5px;background-image:">
            <img src="themes/<?php echo $this->template->theme();?>/images/banner4.gif" border="" title="<?php echo $this->config->item("website_title");?>" alt="Site banner"/>
            </div>
        </td>        
    </tr>
    <!--content/menu-->
    <tr style="height:400px;" valign="top">
    	<!--left menu -->
        <td style="width:200px" class="menu-container">
	        <div> <?php echo isset($sidebar) ? $sidebar : '';?></div>
        </td>
        <td style="width:5px;"></td>
        <!--content -->
        <td class="content-container" style="width:575px">
        	<div style="margin:5px;margin-bottom:20px;" id="page-content">
                <?php echo isset($content) ? $content : '';?>
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