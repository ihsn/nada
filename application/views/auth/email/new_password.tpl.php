<html>
<body>
	<p>You password has been reset to <b><?php echo $new_password;?></b>. To login to the website, your account details are:</p>
    <p>	Username: <?php echo $identity; ?><br/>
    	Password: <?php echo $new_password; ?></p>
    <p>&nbsp;</p>    
    <p>-----------------------------------------------------------
    <br/><?php echo $this->config->item('website_title'); ?> - <?php echo site_url(); ?></p>
    <p>&nbsp;</p>
    <p>DO NOT REPLY TO THIS EMAIL</p>    
</body>
</html>