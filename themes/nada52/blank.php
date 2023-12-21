<?php
header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );
$menu_horizontal=TRUE;
$bootstrap_theme = 'themes/'.$this->template->theme();

$data=array();
//side menu
$data['menus']= [];//$this->Menu_model->select_all();
$sidebar='';//$this->load->view('default_menu', $data,true);

//default page content wrapper class
$content_wrap_class="container";

if (isset($body_class)){
    $content_wrap_class=$body_class;
}

$use_cdn=false;

?>
<!DOCTYPE html>
<html>

<head>    
<?php require_once 'head.php';?>

<style>
    .site-header .login-bar{display:none!important;}
</style>
</head>
<body class="wb-template-blank">

<!-- site header -->
<?php include_once 'header.php';?>

<!-- page body -->
<div class="wb-page-body container-fluid">
    <?php if (isset($content) ):?>
        <?php print $content; ?>
    <?php endif;?>
</div>

<!-- page footer -->
<?php //include_once 'footer.php';?>
</body>

</html>