<?php
header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );
?>
<?php //include_once APPPATH.'/config/site_menus.php'; ?>
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

$this->load->library('site_menu');
$this->load->helper('vite_helper');
// site_menu is attached to the controller (get_instance()), not to $this in this view — $this is CI_Loader
$CI =& get_instance();
$admin_header_config = $CI->site_menu->get_admin_header_config();
$vite_dev_url_header = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev_header = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	  <base href="<?php echo js_base_url(); ?>">
	  <title><?php echo $title; ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">   

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"  crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>

    <link href="<?php echo base_url(); ?>themes/<?php echo $this->template->theme();?>/custom.css?v=bt5" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>themes/<?php echo $this->template->theme();?>/style.css?v=3">
    

    
    <script type="text/javascript"> 
        var CI = {'base_url': '<?php echo site_url(); ?>'}; 
    </script> 

    <?php if (isset($_styles) ){ echo $_styles;} ?>
    <?php if (isset($_scripts) ){ echo $_scripts;} ?>


    <style>
      .sub-header {
          background: #F1F1F1;
          background: -webkit-gradient(radial, 100 36, 0, 100 -40, 120, from(#FAFAFA), to(#F1F1F1)), #F1F1F1;
          border-bottom: 1px solid #666;
          border-color: #E5E5E5;
          height: 100px;
          width: 100%;
          margin-bottom: 20px;
          padding: 10px 25px
      }
    </style>

    <script>
    $(document).ready(function()  {
      /*global ajax error handler */
      $( document ).ajaxError(function(event, jqxhr, settings, exception) {
        if(jqxhr.status==401){
          window.location=CI.base_url+'/auth/login/?destination=admin/';
        }
      });
    });

    </script>

</head>
<body>

<script>
window.ADMIN_HEADER_CONFIG = <?php echo json_encode($admin_header_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
</script>
<div id="admin-app-header"></div>
<?php if ($use_vite_dev_header): ?>
<?php echo render_vite_dev_scripts('admin/header/main.js', $vite_dev_url_header); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_header', 'frontend/dist'); ?>
<?php endif; ?>


<div class="admin-bt4-below-fixed-header">
<?php if(isset($collection)):?>
<div class="sub-header" > <?php echo $collection;?></div>
<?php endif;?>

    <div class="container-fluid">
        <div>             
             <!--breadcrumbs -->
            <?php if (empty($hide_breadcrumb)): ?>
			      <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
            <?php if ($breadcrumbs_str!=''):?>
                <div id="breadcrumb" class="notabs">
                <?php echo $breadcrumbs_str;?>
                </div>
            <?php endif;?>
            <?php endif;?>
                
            <div id="content">
              <?php if (isset($content) ):?>
                  <?php print $content; ?>
              <?php endif;?>
            </div>         
        </div>
    </div>    
    
</div>

  </body>
</html>