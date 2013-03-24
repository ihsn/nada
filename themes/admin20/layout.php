<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<?php
//build a list of links for available languages
$languages=$this->config->item("supported_languages");

$lang_list='';
if ($languages!==FALSE)
{
	if (count($languages)>1)
	{
		foreach($languages as $language)
		{
			$lang_list.='| <span> '.anchor('switch_language/'.$language.'/?destination=admin', strtoupper($language)).' </span>';
		}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" 
"http://www.w3.org/TR/html4/strict.dtd">
<html >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<base href="<?php echo js_base_url(); ?>">
<title><?php echo $title; ?></title>
<link rel="stylesheet" type="text/css" href="themes/admin/reset-fonts-grids.css">
<link rel="stylesheet" type="text/css" href="themes/admin/generic.css" />
<link rel="stylesheet" type="text/css" href="themes/admin/forms.css">
<script type="text/javascript" src="javascript/jquery.js"></script>
<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>
    
<style>
#doc3 {
	margin:auto;	
	min-width:960px;/* optional but recommended */
}

#menu {margin-bottom:20px;}
#menu ul{ list-style:none;100px;height:35px;border:1px solid gainsboro; }
#menu li{float:left;margin:0px;margin-top:10px;}
#menu li a{color:black;text-decoration:none;margin-top:5px;margin-right:10px;height:35px;}
#menu li a.selected{background:#D1D4E3;color:black;}
#menu li a:hover{background:#D1D4E3;color:black;}


html, body{background-color:white;font-size:12px;}

#bd{background-color:white;border:4px solid #D1D4E3;margin:20px;margin-top:0px;padding:5px;-moz-border-radius: 5px;
-webkit-border-radius: 5px;	min-height:400px;
}	

#login-box{
	font-size:12px; color:black; background:#D0E1B4;border-top:0px;position:absolute;top:0px;right:30px;padding:5px;
	border-radius: 0px 0px 5px 5px;
	-moz-border-radius: 0px 0px 5px 5px;
	-webkit-border-bottom-right-radius: 5px;
	-webkit-border-bottom-left-radius: 5px;
	}
	
#login-box a{color:black;text-decoration:none;font-size:11px}
#login-box a:hover{text-decoration:underline}
#header{background-color:#90B05A;}
	
#navcontainer
{
padding: 0;
border-bottom:0px solid gainsboro;
margin-bottom:20px;
padding-top:5px;
padding-left:10px;
}

/* to stretch the container div to contain floated list */
#navcontainer:after
{
content: ".";
display: block;
line-height: 1px;
font-size: 1px;
clear: both;
}


ul#navlist
{
list-style: none;
padding: 0;
margin: 0 auto;
width: 100%;
font-size: 0.8em;
}

ul#navlist li
{
display: block;
float: left;
margin: 0;
padding: 0;
}

ul#navlist li a
{
display: block;
padding: 0.5em;
xborder-width: 1px;
xborder-color: #ffe #aaab9c #ccc #fff;
xborder-style: solid;
color: white;
text-decoration: none;
background: black;
padding-left:5px;
padding-right:5px;
font-size:14px;
margin-left:5px;
margin-bottom:-1px;

border-radius: 5px 5px 0px 0px;
-moz-border-radius: 5px 5px 0px 0px;
-webkit-border-top-right-radius: 5px;
-webkit-border-bottom-right-radius: 0px;
-webkit-border-bottom-left-radius: 0px;
}
/*
#navcontainer>ul#navlist li a { width: auto; }
*/

ul#navlist li .active
{
color: black;
background: white;
}

ul#navlist li a:hover, ul#navlist li#active a:hover
{
color: black;
background: white;
border-color: #aaab9c #fff #fff #ccc;
}

.site-admin-title{text-shadow: 1px 1px 1px #000;color:white;font-size:24px;padding:5px;margin-left:20px;margin-top:20px;}
.nada-version{vertical-align:super;font-size:50%;}

</style>

</head>
<body>
<div id="doc3">
   
      
   <div id="hd" style="padding-bottom:10px;">
   
       <div id="header" role="banner" style="padding-top:20px;">
                <span class="site-admin-title" ><?php echo t('nada_administration');?> <span class="nada-version">v4.0-alpha</span></span>

                <?php $selected_page=$this->uri->segment(2); ?>
                <div id="navcontainer">
               		 <ul id="navlist">
						<li><a <?php echo ($selected_page=='') ? 'class="active"' : ''; ?> href="<?php echo site_url("admin");?>"><?php echo t('dashboard');?></a></li>
                        <li><a <?php echo ($selected_page=='repositories') ? 'class="active"' : ''; ?> href="<?php echo site_url("admin/repositories");?>"><?php echo t('repositories');?></a></li>    
						<li><a <?php echo ($selected_page=='catalog') ? 'class="active"' : ''; ?> href="<?php echo site_url("admin/catalog");?>"><?php echo t('catalog_maintenance');?></a></li>                        
                        <li><a <?php echo ($selected_page=='licensed_requests') ? 'class="active"' : ''; ?> href="<?php echo site_url("admin/licensed_requests");?>"><?php echo t('licensed_survey_requests');?></a></li>
                        <li><a <?php echo ($selected_page=='citations') ? 'class="active"' : ''; ?>  href="<?php echo site_url("admin/citations");?>"><?php echo t('citations');?></a></li>
                        <li><a <?php echo (in_array($selected_page,array('vocabularies','terms'))) ? 'class="active"' : ''; ?>  href="<?php echo site_url("admin/vocabularies");?>"><?php echo t('vocabularies');?></a></li>
						<li><a <?php echo ($selected_page=='menu') ? 'class="active"' : ''; ?>  href="<?php echo site_url("admin/menu");?>"><?php echo t('menu');?></a></li>
						<li><a <?php echo ($selected_page=='users') ? 'class="active"' : ''; ?> href="<?php echo site_url("admin/users");?>"><?php echo t('users');?></a></li>
                        <li><a <?php echo ($selected_page=='reports') ? 'class="active"' : ''; ?>  href="<?php echo site_url("admin/reports");?>"><?php echo t('reports');?></a></li>
                        <li><a <?php echo ($selected_page=='configurations') ? 'class="active"' : ''; ?>  href="<?php echo site_url("admin/configurations");?>"><?php echo t('site_configurations');?></a></li>                        
                    </ul>
                </div>
      </div>
      
   </div> 
   
   <div id="login-box">
		<?php $user=strtoupper($this->session->userdata('username'));?>
		<?php if ($user):?>
			<?php echo $user;?> |            
            <?php echo anchor('auth/change_password',t('change_password'));?> | 
            <?php echo anchor('auth/logout',t('logout'));?>
        <?php endif;?>
        <div style="margin-top:5px;">
			<?php echo anchor(site_home(),t('home_page'));?> |
			<?php echo anchor('catalog',t('data_catalog'));?> |
            <?php echo anchor('citations',t('citations'));?>
        <?php echo $lang_list;?>
        </div>       
   </div>
   
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