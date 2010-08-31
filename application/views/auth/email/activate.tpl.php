<?php 
/*
	User registration email message - sent when a user registers for an account
*/
?>
<html>
<body>
	<p>Thank you for registering with the <em><?php echo $this->config->item('website_title'); ?></em> website. To complete your registration and activate your user account, please visit the following URL:</p>
	<p><b><?php echo anchor('auth/activate/'. $id .'/'. $activation, site_url().'/auth/activate/'. $id .'/'. $activation);?></b></p>
	<p>You account details are:</p>
    <p>Username: <?php echo $email; ?></p>
    <p>Password: <?php echo $password; ?></p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>-----------------------------------------------------------
    <br/><?php echo $this->config->item('website_title'); ?> - <?php echo site_url(); ?></p>
    <p>&nbsp;</p>
    <p>DO NOT REPLY TO THIS EMAIL</p>
</body>
</html>