<!-- admin bar -->
<?php if (!empty($_SESSION['user']['username'])) { ?>
	<div class="userbar" style="text-align:right;font-weight:normal;margin-top:5px;color:white;">
		<?php print getmsg('You are logged in as:');?> <?php echo strtoupper($_SESSION['user']['username']);?> | 
        <a class="login-link" href="?page=profile" title="user profile"><?php print getmsg('Profile');?></a> | 
        <a class="login-link" href="?page=changepass" title="Change password"><?php print getmsg('Password');?></a> | 
        <a class="login-link" href="?page=logout" title="logout"><?php print getmsg('Logout');?></a>
     </div>
<?php } 
else {
?>
	<div class="userbar" style="text-align:right;font-weight:normal;margin-top:5px;margin-right:10px;">
    	<a href="?page=login" title="login"><?php print getmsg('Login');?></a>
    </div>
<?php }?>
