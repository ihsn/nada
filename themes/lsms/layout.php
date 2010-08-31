<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?php
//load blocks for the current page
$this->blocks=$this->Menu_model->get_blocks($this->uri->segment(1));

$menu_horizontal=TRUE;
$secondary_menu=FALSE;

//get menu info by url
$menu=$this->Menu_model->get_menu_by_url($this->uri->segment(1));

if ($menu)
{
	//child page, get submenu
	if ($menu['pid']>0)
	{
		//get parent+sub-menu items
		$secondary_menu=$this->Menu_model->get_secondary_menu($menu['pid']);
	}
	else
	{	
		//get parent+sub-menu items
		$secondary_menu=$this->Menu_model->get_secondary_menu($menu['id']);
	}		
}

$secondary_menu_formatted=FALSE;

//create a formatted listed of sub menus
if ($secondary_menu!==FALSE && count($secondary_menu)>1)
{
	$secondary_menu_formatted='<ul>';
	foreach($secondary_menu as $item)
	{
		$selected="";
		if ($this->uri->segment(1)==$item['url'])
		{
			$selected=' class="selected"';
		}
		$secondary_menu_formatted.='<li '.$selected.'>'.anchor($item['url'],$item['title']).'</li>';
	}
	$secondary_menu_formatted.='</ul>';	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo t($title);?></title>
<base href="<?php echo base_url(); ?>" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" href="javascript/superfish/css/superfish.css" media="screen">
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />
<script type="text/javascript" src="javascript/jquery.js"></script>

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<!--superfish menu-->
<script type="text/javascript" src="javascript/superfish/js/hoverIntent.js"></script>
<script type="text/javascript" src="javascript/superfish/js/superfish.js"></script>

<script type="text/javascript">
//initialize menu
jQuery(function(){
	jQuery('ul.sf-menu').superfish();
});

//collapse/expand div
function toggle_banner(e){
	if ($("#site-banner-body").is(":visible")){
		$("#site-banner-body").slideUp("slow");
		$('#'+e.id).attr('src','themes/<?php echo $this->template->theme();?>/arrow-open.gif');
	}
	else{
		$("#site-banner-body").slideDown("slow");
		$('#'+e.id).attr('src','themes/<?php echo $this->template->theme();?>/arrow-close.gif');
	}
}	
</script>
</head>
<body>
<div id="custom-doc" class="<?php echo ($secondary_menu_formatted!==FALSE) ? 'yui-t1' : ''; ?>" > 
	<!--login information bar-->
    <?php $this->load->view('user_bar');?>	
    
    <!-- header -->
    <div id="hd">
    
       	<!-- bank logo -->
        <div class="site-logo">
        	<a title="<?php echo $this->config->item("website_title");?> - Home Page"  href="<?php echo site_url();?>">
            <img src="themes/<?php echo $this->template->theme();?>/wb-logo.gif"  border="0" alt="Logo"/>
            </a>
        </div>
    </div>
    
    <div id="bd" >
    
       	<!-- home -->
        <div style="background-color:#666666;margin-bottom:5px;">
        	<span style="color:white;background-color:#999999;border-right:4px solid white;padding:5px;display:inline-block">Home</span>
        </div>        

    	<!-- banner-->
        
		<div id="site-banner" style="height:35px;padding:10px;margin-top:5px;background:maroon;">
                <div style="float:left;width:70%;margin-top:5px;">
	                <div id="banner"><?php echo $this->config->item("website_title");?></div>
                </div>
                <div style="float:right;width:25%;text-align:right;color:white;padding:10px;"><img style="cursor:pointer" id="toggle-img" src="themes/<?php echo $this->template->theme();?>/arrow-open.gif" alt="collapse/expand" onclick="toggle_banner(this)"/></div>
                <br style="clear:both"/>                
        </div>
		<div id="site-banner-body" style="display:none;background-color:gainsboro;padding:20px;line-height:200%;">
	            The Living Standards Measurement Study (LSMS) was established by the Development Economics Research Group (DECRG) to explore ways of improving the type and quality of household data collected by statistical offices in developing countries. Its goal is to foster increased use of household data as a basis for policy decision making. 
        </div>

        <div id="inner-body">
            <!-- menu -->
            <?php if ($menu_horizontal===TRUE):?>
                <div class="menu-horizontal">
                        <?php echo ($this->Menu_model->get_menu_tree());?>
                 </div>
             
            	<?php if($secondary_menu_formatted!==FALSE):?>
                  <!-- side bar -->
                  <div id="sidebar" class="yui-b">
                        <div class="sidebar"><?php echo $secondary_menu_formatted;?></div>
                  </div>
                <?php endif;?>

                 <!-- page contents --> 
                <div id="yui-main">
                        <div id="content" class="yui-b"><?php echo isset($content) ? $content : '';?></div>
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
    <!-- highlights -->
    <?php if (isset($this->blocks['highlights'])):?>
			<?php echo $this->blocks['highlights'];?>
    <?php endif;?>


    <!-- footer -->
    <div id="ft">Demo site developed using IHSN NADA 3.0</div>
	<!--end bd-->

</div>
<div style="padding-bottom:100px;">&nbsp;</div>
</body>
</html>