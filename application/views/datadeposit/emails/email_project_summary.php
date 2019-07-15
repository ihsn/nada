<?php /* data deposit email template */ ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>themes/datadeposit/styles.css" />
<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>themes/datadeposit/print.css" />
<style>
<?php $css= file_get_contents(APPPATH.'../themes/datadeposit/styles.css');?>
table {background:red;width:100%;font-size:12px;}
body {font-family:Arial, Helvetica, sans-serif;font-size:12px;}
legend{font-weight:bold;margin-top:40px;font-size:16px;}
.td-label{width:150px;}
</style>
</head>
<body>
<center>
<?php //echo $content;?>
<div style="width:600px;">
<?php 
echo $content;
//$this->load->library('CssToInlineStyles');
//$this->csstoinlinestyles->setCSS($css);
//$this->csstoinlinestyles->setHTML($content);
//echo $this->csstoinlinestyles->convert();

?>
</div>
</center>
</body>
</html>