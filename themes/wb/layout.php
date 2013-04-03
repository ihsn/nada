<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?php
$menu_horizontal=TRUE;

//side menu
$data['menus']= $this->Menu_model->select_all();		
$sidebar=$this->load->view('default_menu', $data,true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo t($title);?></title>
<base href="<?php echo js_base_url(); ?>" />

<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />

<!--jquery ui-->
<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/base/jquery-ui.css" />


<script type="text/javascript" src="javascript/jquery/jquery.js"></script>
<script type="text/javascript" src="javascript/jquery.ba-bbq.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<!--[if lt IE 9]>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles-ie.css" />
<![endif]-->

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<style>
.yui-t7 .yui-b {
float: left;
width: 16.8461em;
}
.yui-t7 #yui-main .yui-b {
margin-left: 17.8461em;
}
.yui-t7 #yui-main {
float: right;
margin-left: -25em;
}
</style>

</head>
<body>

<div id="custom-doc" class="yui-t7" > 
	<!--login information bar-->
    <span id="user-container">
    <?php $this->load->view('users/user_bar');?>
    </span>
    
    <!-- header -->
<?php /*    <div id="hd">
       	<!-- logo -->
        <div class="site-logo">
        	<a title="<?php echo $this->config->item("website_title");?> - Home Page"  href="<?php echo site_url();?>">
            <img src="themes/<?php echo $this->template->theme();?>/logo.gif"  border="0" alt="Logo"/>
            </a>
        </div>
    </div>
*/?>    
    <div id="bd" >
    	<!-- banner-->
        <div id="banner"><?php echo $this->config->item("website_title");?></div>
        
        <div id="inner-body">
            <!-- menu -->
            <?php if ($menu_horizontal===TRUE):?>
            <div class="menu-horizontal">
                    <?php echo isset($sidebar) ? $sidebar : '';?>
                    <br style="clear:both;"/>
             </div>
            <?php endif;?>
        
            <?php if ($menu_horizontal===TRUE):?>
                <div id="content"  >
                
                	<!--share-bar -->
                    <div id="page-tools">
					<?php include 'share.php';?>
                	</div>
                
                    <!--breadcrumbs -->
                    <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
                    <?php if ($breadcrumbs_str!=''):?>
                        <div id="breadcrumb">
                        <?php echo $breadcrumbs_str;?>
                        </div>
                    <?php endif;?>
                    
					<?php if (isset($search_filters) && $search_filters!==false):?>
                    	<div class="main-body-container">
                        <div id="yui-main">
                            <div  class="yui-b"><?php echo isset($content) ? $content : '';?></div>
                        </div>
                                                
                        <div id="facets" class="yui-b">
						<?php echo $search_filters;?>
                        </div>
                        </div>
                    <?php else:?>
                    	<?php echo isset($content) ? $content : '';?>
					<?php endif;?>
                </div>
            <?php else:?>
            <div id="yui-main">
             <div id="content" class="yui-b"><?php echo isset($content) ? $content : '';?></div>
          </div>
          <!-- side bar -->
          <div id="sidebar" class="yui-b">
                <div class="sidebar"><?php echo isset($sidebar) ? $sidebar : '';?></div>
          </div>
          <?php endif; ?>
		</div>
</div>
    <!-- footer -->
    <div id="ft"><?php echo $this->config->item("website_footer");?> </div>
	<!--end bd-->
    </div>

<div style="padding-bottom:100px;">&nbsp;</div>
<?php $this->load->view('tracker/js_tracker');?>
</body>
</html>