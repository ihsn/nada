<?php

//user account activation email messages [activate.tpl.php]
$lang['thank_you_for_registering'] = "Thank you for registering with the <em><?php echo $this->config->item('website_title'); ?></em> website. To complete your registration and activate your user account, please visit the following URL:";
$lang['your_account_details'] = "You account details are:";
$lang['username'] = "Username:";
$lang['password'] = "Password:";
$lang['do_not_reply'] = "DO NOT REPLY TO THIS EMAIL";


//forgot_password.tpl.php
$lang['last_name'] = "To reset your password, click on the link below or open the url in a web browser:";
$lang['phone'] = "If you did not request password reset, ignore this message.";
$lang['company'] = "DO NOT REPLY TO THIS EMAIL";

//new_password.tpl.php
$lang['password_reset_msg'] = "You password has been reset to <b><?php echo $new_password;?></b>. To login to the website, your account details are:";

//create_user_confirm.php
$lang['account_created_confirmation'] = "You account has been created but before you can login, we need to confirm your email address. We have emailed you the instructions to activate your user account.";

//forgot_pass_confirm.php
$lang['email_is_sent'] = "An email message has been sent to your email address, please check your inbox.";

//forgot_pass_success.php
$lang['new_password_sent'] = "Your password has been reset and a new password is sent to your email address. Click here to login with your new password.";

//forgot_password.php
$lang['forgot_password_enter_email'] = "Please enter your email address so we can send you an email to reset your password.";

/* End of file users_additional_lang.php */
/* Location: ./system/language/english/users_additional_lang.php */