<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" 
"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<base href="<?php echo base_url(); ?>">
	<title><?php echo $title; ?></title>
	<!-- Source File -->
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.8.0r4/build/reset-fonts-grids/reset-fonts-grids.css">
   	<link rel="stylesheet" type="text/css" href="themes/admin/forms.css">
	<script type="text/javascript" src="javascript/jquery.js"></script>
    <script type="text/javascript"> 
       var CI = {'base_url': '<?php echo site_url(); ?>'}; 
    </script> 

	<?php if (isset($_styles) ){ echo $_styles;} ?>
    <?php if (isset($_scripts) ){ echo $_scripts;} ?>
    
    <style>
	#doc3 {
		xmargin:auto;	
		min-width:800px;/* optional but recommended */
	}
	
	.header{xbackground:black;}
	#menu {padding-top:80px;}
	#menu ul{ list-style:none; float:left;}
	#menu li{float:left;margin:0px;margin-left:1px;padding:5px;}
	#menu li a{color:white;padding:5px;text-decoration:none;}
	#menu li a.selected{background:#D1D4E3;color:black;}
	#menu li a:hover{background:#D1D4E3;color:black;}
	html, body{background-color:#12536D;font-size:12px;}
	
	#bd{background-color:white;border:4px solid #D1D4E3;margin:20px;margin-top:0px;padding:5px;-moz-border-radius: 5px;
	-webkit-border-radius: 5px;	
	}	

	#login-box{font-size:.8em; color:white; background:#8099A3;border:1px solid gray;border-top:0px;position:absolute;top:0px;right:30px;padding:5px;}
	</style>
</head>
<body>
<div id="doc3">
   
   <div id="hd">
   
       <div class="yui-gf header" role="banner">
             <div class="yui-u first">
                <div style="color:white;font-size:18px;padding:5px;margin-left:20px;margin-top:20px;"><img src="images/logo.gif" align="absbottom"/>Site Administration</div>
             </div>
             <div class="yui-u">
             	<?php $selected_page=$this->uri->segment(2); ?>
                <div id="menu">
               		 <ul>
						<li><a href="<?php echo site_url("admin/catalog");?>">Catalog Maintenance</a></li>
						<li><a href="<?php echo site_url("admin/menu");?>">Menu</a></li>                        
                        <li><a href="<?php echo site_url("admin/licensed_files");?>">Licensed survey files</a></li>                        
                        <li><a href="<?php echo site_url("admin/licensed_requests");?>">Licensed survey requests</a></li>                        
                        <!--<li><a href="<?php //echo site_url("admin/request-forms");?>">Request forms</a></li>-->
						<li><a href="<?php echo site_url("admin/users");?>">Users</a></li>
                        <li><a href="<?php echo site_url("admin/citations");?>">Citations</a></li>
                        <li><a href="<?php echo site_url("admin/configurations");?>">Site configurations</a></li>
                    </ul>
                </div>
             </div>
      </div>
   </div> 
   
   <div id="login-box">Administrator | Change password | Logout</div>
   
   <div id="bd">
       <!-- body -->
       
        <div id="content">
        <?php if (isset($content) ):?>
            <?php print $content; ?>
        <?php endif;?>
        </div>   
   </div> 
   <div id="ft">
	   <!-- footer -->
		<?php if ($footer): ?>
        <div id="footer">
            <?php print $footer; ?>
        </div>
        <?php endif;?>
   </div> 
</div>
</body>
</html>