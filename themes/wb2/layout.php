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

<!-- Google Font Directory: Open Sans -->    
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />

<link rel="stylesheet" type="text/css" href="themes/base/css/bootstrap.buttons.min.css" />

<!-- font awesome -->
<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">

<!--jquery ui-->
<link rel="stylesheet" type="text/css" href="javascript/jquery/themes/base/jquery-ui.css" />
    
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />    
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/home.css" />

<script type="text/javascript" src="javascript/jquery/jquery.js"></script>
<script type="text/javascript" src="javascript/jquery.ba-bbq.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<!--[if IE]>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles-ie.css" />
<![endif]-->
    
<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
   
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}

   
$(document).ready(function()  {
   	/*global ajax error handler */
	$( document ).ajaxError(function(event, jqxhr, settings, exception) {
		if(jqxhr.status==401){
			window.location=CI.base_url+'/auth/login/?destination=catalog/';
		}
		else if (jqxhr.status>=500){
			alert(jqxhr.responseText);
		}
	});	

  });//end-document-ready

</script> 

</head>
    
<?php 
    $is_home_class=($this->uri->segment(1)=='home') ? 'class="is-home"' : ''; 
?>

<body <?php echo $is_home_class; ?>>
    
    <div id="mt-header">
        <div id="mt-information-bar">
            <div class="mt-inner">
                <!--login information bar-->
                <span id="user-container">
                <?php $this->load->view('users/user_bar');?>
                </span>
            </div><!-- /.mt-inner -->
        </div>
        <div id="mt-banner-strip">
            <div class="mt-inner">
                <!-- banner-->
                <div id="banner"><?php echo $this->config->item("website_title");?></div>
                <div id="banner-subtitle">An on-line microdata library</div>
                
                <!--share-bar -->
                <div id="page-tools">
                <?php include 'share.php';?>
                </div>
            </div><!-- /.mt-inner -->
        </div>
        <div id="mt-menu-bar">
            <div class="mt-inner">
                <!-- menu -->
                <?php if ($menu_horizontal===TRUE):?>
                <div class="menu-horizontal">
                    <?php echo isset($sidebar) ? $sidebar : '';?>
                    <br style="clear:both;"/>
                </div>
                <?php endif;?>
            </div>
        </div><!-- /@mt-menu-bar -->
        
        <!--breadcrumbs -->
        <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
        <?php if ($breadcrumbs_str!=''):?>
            <div id="breadcrumb">
                <div class="mt-inner">
                    <?php echo $breadcrumbs_str;?>
                </div>
            </div>
        <?php endif;?><!-- /breadcrumbs -->
        
    </div><!-- /#mt-header -->
    
    
    <div id="custom-doc" class="yui-t7" > 
        <div id="bd" >
            <div id="inner-body">        
                <?php if ($menu_horizontal===TRUE):?>
                    <div id="content"  >        
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
            </div> <!-- / inner-body -->
        </div> <!--/ bd -->	
    </div><!--/ custom-doc -->
    <!-- footer -->
    <div id="ft">
        <div class="mt-inner">
            <?php echo $this->config->item("website_footer");?>
        </div><!-- /.mt-inner -->
    </div><!-- /footer -->
        
    <?php $this->load->view('tracker/js_tracker');?>
</body>
</html>