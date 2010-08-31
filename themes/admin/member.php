<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo base_url(); ?>">
<link rel="stylesheet" type="text/css" href="css/admin.css" />

<script type="text/javascript"> 
   var CI = {'base_url': '<?php echo base_url(); ?>'}; 
</script> 

<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>


<style type="text/css">

body{background-color:#F0F0F0;font-size:12px;font-family:Arial, Helvetica, sans-serif;padding:0px;margin:0px;}
.member-header{background-color:#333333;color:white;}
.member-header h1{margin:0px;font-weight:normal;letter-spacing:2px;padding:15px;}
.content{margin:10px;}
</style>
</head>
<body>
    <div class="member-header">
    	<h1><?php echo $this->config->item("website_title"); ?></h1>
    </div>
    <div class="content">
        <?php if (isset($content) ):?>
            <?php print $content; ?>
        <?php endif;?>
    </div>
</body>
</html>