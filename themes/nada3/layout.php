<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?php
$menu_horizontal=FALSE;

//side menu
$data['menus']= $this->Menu_model->select_all();		
$sidebar=$this->load->view('default_menu', $data,true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo t($title);?></title>
<base href="<?php echo base_url(); ?>" />

<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />
<script type="text/javascript" src="javascript/jquery.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 
</head>

<body>
<div id="custom-doc" class="yui-t2" > 
	<!--login information bar-->
    <span id="user-container">
    <?php $this->load->view('user_bar');?>
    </span>
    
    <!-- header -->
    <div id="hd" >
        <div style="background:url(themes/<?php echo $this->template->theme();?>/banner-bg.gif) ;background-color:#497EA6;height:120px;">
            <!-- logo -->
            <div class="site-logo" >
                <a title="<?php echo $this->config->item("website_title");?> - Home Page"  href="<?php echo site_url();?>">
                	<img src="themes/<?php echo $this->template->theme();?>/logo.gif"/>
                    <span class="site-logo-title"><?php echo $this->config->item("website_title");?></span>
                </a>
            </div>        
        </div>   
    </div>
    
    <div id="bd" >
        
        <div id="inner-body">

          <!-- side bar -->
          <div id="sidebar" class="yui-b">
                <div class="sidebar"><?php echo isset($sidebar) ? $sidebar : '';?></div>
          </div>

            <div id="yui-main">
                <div id="content" class="yui-b"><?php echo isset($content) ? $content : '';?></div>
            </div>
          
		</div>
	
    </div>
    
    <!-- footer -->
    <div id="ft"><?php echo $this->config->item("website_footer");?></div>
	<div>&nbsp;</div>
</div>
</div>

</body>
</html>