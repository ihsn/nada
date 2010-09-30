<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo base_url(); ?>">
<link rel="stylesheet" type="text/css" href="css/admin.css" />
<xscript xtype="text/javascript" xsrc="http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js" ></xscript>
<script type="text/javascript" src="javascript/jquery.js"></script>
<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>
</head>

<body>
<div id="container">
<div id="header">
	<table border="0" style="width:98%;">
    	<tr style="vertical-align:top;">
        <td style="font-size:16px;"><?php echo ('Website Administration');?></td>
        <td align="right">
		        <!-- login/logout -->
				<?php if (!empty($_SESSION['user']['username'])) { ?>
                    <div style="text-align:right;font-weight:normal;margin-top:5px;color:#6666FF;"><?php echo ('You are logged in as:'). ' ' .strtoupper($_SESSION['user']['username']);?> | <a class="login-link" href="../?page=profile" title="user profile"><?php print ('Profile');?></a> | <a class="login-link" href="../?page=changepass" title="<?php print ('Change password');?>"><?php print ('Password');?></a> | <a class="login-link" href="logout.php" title="<?php print ('Logout');?>"><?php print ('Logout');?></a></div>
                <?php } 
                else {
                ?>
                    <div style="text-align:right;font-weight:normal;margin-top:5px;"><a style="color:#6666FF" href="?page=login" title="<?php print ('Login');?>"><?php print ('Login here');?></a></div>
                <?php }?>
        </td>
        </tr>
    </table>	
</div>
<!-- left menu -->
<div id="left-menu">
            		<ul>
						<li><a href="<?php echo site_url("admin/catalog");?>">Catalog Maintenance</a></li>
						<li><a href="<?php echo site_url("admin/menu");?>">Menu</a></li>                        
                        <li><a href="<?php echo site_url("admin/licensed_files");?>">Licensed survey files</a></li>                        
                        <li><a href="<?php echo site_url("admin/licensed_requests");?>">Licensed survey requests</a></li>                        
                        <!--<li><a href="<?php //echo site_url("admin/request-forms");?>">Request forms</a></li>-->
						<li><a href="<?php echo site_url("admin/users");?>">Users</a></li>
                        <li><a href="<?php echo site_url("admin/citations");?>">Citations</a></li>
                        <!--<li><a href="<?php //echo site_url("admin/configurations");?>">Site configurations</a></li> -->
                    </ul>
</div>
<div id="content" >
    <?php if (isset($content) ):?>
        <?php print $content; ?>
    <?php endif;?>
</div>

<?php if ($footer): ?>
<div id="footer">
	<?php print $footer; ?>
</div>
<?php endif;?>
</body>
</html>