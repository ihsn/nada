<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo base_url(); ?>">
<link rel="stylesheet" type="text/css" href="css/admin.css" />
<script type="text/javascript" src="javascript/jquery.js"></script>
<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo site_url(); ?>'}; 
</script> 

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<style type="text/css">
	body{background-color:white;font-size:12px;font-family:Arial, Helvetica, sans-serif;padding:0px;margin:0px;}
</style>
</head>
<body>
    <div>
        <?php if (isset($content) ):?>
            <?php print $content; ?>
        <?php endif;?>
    </div>
</body>
</html>