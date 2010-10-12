<?php

//user account activation email messages [activate.tpl.php]
$lang['thank_you_for_registering'] = "Gracias por registrarse con <em><?php echo $this->config->item('website_title'); ?></em> Sitio Web. Para completar su registro y activar su cuenta de usuario, por favor visite la siguiente URL:";
$lang['your_account_details'] = "Los detalles de su cuentea son:";
$lang['username'] = "Nombre de usuario:";
$lang['password'] = "Contraseña:";
$lang['do_not_reply'] = "NO RESPONDA A ESTE MENSAJE";


//forgot_password.tpl.php
$lang['last_name'] = "Para restablecer su contraseña, haga clic en el siguiente enlace o abra el URL en un navegador web:";
$lang['phone'] = "Si no ha solicitado restablecer la contraseña, favor omita este mensaje.";
$lang['company'] = "NO RESPONDA A ESTE MENSAJE";

//Mehmood ... check to see is the following are the correct  variables since the three listed above are not correct in the original file

//$lang['last_name'] = "Apellido:";
//$lang['phone'] = "Teléfono.";
//$lang['company'] = "Empresa";


//new_password.tpl.php
$lang['password_reset_msg'] = "Su Contraseña se ha restablecido a <b><?php echo $new_password;?></b>. Para acceder a la página web, los datos de su cuenta son:";

//create_user_confirm.php
$lang['account_created_confirmation'] = "Su cuenta ha sido creada, pero antes de iniciar la sesión, necesitamos confirmar su dirección de correo electrónico. Le hemos enviado por correo electrónico las instrucciones para activar tu cuenta de usuario.";

//forgot_pass_confirm.php
$lang['email_is_sent'] = "Un mensaje de correo electrónico ha sido enviado a su dirección de correo electrónico, por favor revise su bandeja de entrada.";

//forgot_pass_success.php
$lang['new_password_sent'] = "Su contraseña se ha restablecido y una nueva contraseña ha sido enviado a su dirección de correo electrónico. Haga clic aquí para ingresar con su nueva contraseña.";

//forgot_password.php
$lang['forgot_password_enter_email'] = "Por favor, ingrese su dirección de correo electrónico para que podamos enviarle un correo electrónico para restablecer su contraseña.";

/* End of file users_additional_lang.php */
/* Location: ./system/language/english/users_additional_lang.php */