<html>
<body>
    <p>To reset your password, click on the link below or open the url in a web browser:</p>
    <p><?php echo anchor('auth/reset_password/'. $forgotten_password_code);?></p>
    <p>&nbsp;</p>
    <p>If you did not request password reset, ignore this message.</p>
    <p>&nbsp;</p>
    <p>-----------------------------------------------------------
    <br/><?php echo $this->config->item('website_title'); ?> - <?php echo site_url(); ?></p>
    <p>&nbsp;</p>
    <p>DO NOT REPLY TO THIS EMAIL</p>    
</body>
</html>