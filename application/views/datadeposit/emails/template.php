<?php /* data deposit email template */ ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<div class="email-content-container">
<div class="email-header">
<div class="email-header-text"><?php echo $this->config->item('website_title');?></div> 
</div>

<?php if (isset($message)):?>
<div class="email-body email-message" >
	<?php echo $message;?>
</div>
<?php endif;?>

<?php if (isset($content)):?>
<div class="email-body" >
	<?php echo $content;?>
</div>
<?php endif;?>

<div class="email-footer">
	<div class="email-footer-text">
	<div style="padding-bottom:4px;font-weight:bold;"><?php echo $this->config->item('website_title');?> - <a style="font-weight:normal;color:#666666" href="<?php echo site_url();?>"><?php echo site_url();?></a></div>
    </div>
</div>

</div>
</body>
</html>