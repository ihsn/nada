<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//this file overwrites email settings loaded from the database

$config['email_driver']     = 'smtp';                    // Email driver: 'smtp', 'sendgrid', 'microsoft_graph'
$config['useragent']        = 'PHPMailer';              // Mail engine switcher: 'CodeIgniter' or 'PHPMailer'
$config['protocol']         = 'smtp';                   // 'mail', 'sendmail', or 'smtp'
$config['mailpath']         = '/usr/sbin/sendmail';
$config['smtp_host']        = '';
$config['smtp_auth']        = NULL;                     // Whether to use SMTP authentication, boolean TRUE/FALSE. If this option is omited or if it is NULL, then SMTP authentication is used when both $config['smtp_user'] and $config['smtp_pass'] are non-empty strings.
$config['smtp_user']        = '';                 
$config['smtp_pass']        = '';
$config['smtp_port']        = 25;
$config['smtp_timeout']     = 30;                       // (in seconds)
$config['smtp_crypto']      = '';                       // '' or 'tls' or 'ssl'
$config['smtp_debug']       = 0;                        // PHPMailer's SMTP debug info level: 0 = off, 1 = commands, 2 = commands and data, 3 = as 2 plus connection status, 4 = low level data output.
$config['debug_output']     = 'codeigniter';                // PHPMailer's SMTP debug output: 'html', 'echo', 'error_log', 'codeigniter' or user defined function with parameter $str and $level. NULL or '' means 'echo' on CLI, 'html' otherwise.
$config['smtp_auto_tls']    = false;                     // Whether to enable TLS encryption automatically if a server supports it, even if `smtp_crypto` is not set to 'tls'.
$config['smtp_conn_options'] = array();                 // SMTP connection options, an array passed to the function stream_context_create() when connecting via SMTP.
$config['wordwrap']         = true;
$config['wrapchars']        = 76;
$config['mailtype']         = 'html';                   // 'text' or 'html'
$config['charset']          = null;                     // 'UTF-8', 'ISO-8859-15', ...; NULL (preferable) means config_item('charset'), i.e. the character set of the site.
$config['validate']         = true;
$config['priority']         = 3;                        // 1, 2, 3, 4, 5; on PHPMailer useragent NULL is a possible option, it means that X-priority header is not set at all, see https://github.com/PHPMailer/PHPMailer/issues/449
$config['crlf']             = "\n";                     // "\r\n" or "\n" or "\r"
$config['newline']          = "\n";                     // "\r\n" or "\n" or "\r"
$config['bcc_batch_mode']   = false;
$config['bcc_batch_size']   = 200;
$config['encoding']         = '8bit';                   // The body encoding. For CodeIgniter: '8bit' or '7bit'. For PHPMailer: '8bit', '7bit', 'binary', 'base64', or 'quoted-printable'.



/*
| -------------------------------------------------------------------
| SSL verification or self-signed certificates
| -------------------------------------------------------------------
| If you are using a secure connection for SMTP with a local self signed certificate for the SMTP server,
| you might not be able to authenticate unless the server certificate is added to your PHP.
|
| If the self signed certificate cannot be added to your PHP installation, you can disable
| ssl certificate verification by enabling the lines below. This is not recommended 
| and should only be used as a last resort. 
|
*/

/*
$config['smtp_conn_options'] = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);*/


/*
| -------------------------------------------------------------------
| Example configurations - simple SMTP with authentication enabled
| -------------------------------------------------------------------
|
*/
/*
$config['email_driver']     = 'smtp';
$config['useragent']        = 'PHPMailer';
$config['smtp_host']        = 'your-smtp-server-name';
$config['smtp_auth']        = true; //authentication is required
$config['smtp_user']        = 'user@your-domain.com';
$config['smtp_pass']        = 'password-here';
$config['smtp_port']        = 25;
$config['smtp_crypto']      = '';
$config['smtp_email']       = 'user@your-domain.com';
$config['smtp_display_name'] = 'Your Name';
*/



/*
| -------------------------------------------------------------------
| Example configurations - using GMAIL with SSL on port 465
| -------------------------------------------------------------------
|
| Note: TO use Gmail, first login to your gmail account and then 
| visit https://myaccount.google.com/lesssecureapps page to enable
| access by less secure apps. Gmail will not work, unless this is done.
|
*/
/*
$config['email_driver']    = 'smtp';
$config['smtp_host']        = 'smtp.gmail.com';
$config['smtp_auth']        = true;
$config['smtp_user']        = 'user@gmail.com';
$config['smtp_pass']        = 'password';
$config['smtp_port']        = 465;
$config['smtp_crypto']      = 'ssl';
$config['smtp_email']       = 'user@gmail.com';
$config['smtp_display_name'] = 'Your Name';
*/


/*
| -------------------------------------------------------------------
| Example configurations - using GMAIL with TLS on port 587
| -------------------------------------------------------------------
|
| Enable less secure apps - https://myaccount.google.com/lesssecureapps
| 
*/

/*
$config['email_driver']    = 'smtp';
$config['smtp_host']        = 'smtp.gmail.com';
$config['smtp_auth']        = true;
$config['smtp_user']        = 'user@gmail.com';
$config['smtp_pass']        = 'password';
$config['smtp_port']        = 587;
$config['smtp_crypto']      = 'tls';
$config['smtp_email']       = 'user@gmail.com';
$config['smtp_display_name'] = 'Your Name';
*/




/*
| -------------------------------------------------------------------
| Configurations for Microsoft Graph
| -------------------------------------------------------------------
|
| 
*/

// Microsoft Graph Configuration (when email_driver = 'microsoft_graph')
/*
$config['email_driver']                  = 'microsoft_graph';
$config['microsoft_graph_client_id']     = '';            // Microsoft Graph Client ID
$config['microsoft_graph_client_secret'] = '';            // Microsoft Graph Client Secret
$config['microsoft_graph_tenant_id']     = '';            // Microsoft Graph Tenant ID


/*
| -------------------------------------------------------------------
| Example configurations - using SendGrid
| -------------------------------------------------------------------
|
| SendGrid API key - https://app.sendgrid.com/settings/credentials
| 
*/

/*
$config['email_driver']     = 'sendgrid'; 
$config['smtp_host']        = 'smtp.sendgrid.net';
$config['smtp_auth']        = true;
$config['smtp_user']        = 'your-sendgrid-username'; //email or username
$config['smtp_pass']        = 'your-sendgrid-api-key'; // SendGrid API key
$config['smtp_display_name'] = 'NADA Administrator'; //display name for sender
$config['smtp_email']       = 'your-email@your-domain.com'; //email address to send from
$config['smtp_port']        = 587;
$config['smtp_crypto']      = 'tls';
*/
