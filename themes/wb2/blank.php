<?php
    $menu_horizontal=TRUE;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo base_url(); ?>">
    
    <!-- Google Font Directory: Open Sans -->    
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/reset-fonts-grids.css" />
    
    <link rel="stylesheet" type="text/css" href="themes/base/css/bootstrap.buttons.min.css" />

    <!--jquery ui-->
    <link rel="stylesheet" type="text/css" href="javascript/jquery/themes/base/jquery-ui.css" />
    
    
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />    
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/home.css" />
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/compare-variables.css" />
    

<script type="text/javascript" src="javascript/jquery.js"></script>
<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<style>
#custom-doc-blank{text-align:left;}
</style>
</head>
<body>
    
    <div id="custom-doc-blank" > 
        <?php if (isset($content) ):?>
            <?php print $content; ?>
        <?php endif;?>
    </div><!--/ custom-doc -->

    
</body>
</html>