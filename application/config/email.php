<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
//using php mail

$config['protocol']  = 'mail';
$config['smtp_host'] = '';
$config['smtp_user'] = '';
$config['smtp_pass'] = '';
$config['smtp_port'] = '';
$config['mailtype']  = 'html';
$config['charset']   = 'utf-8';
*/

//using SMTP server with authentication enabled
$config['protocol']  = 'smtp';
$config['smtp_host'] = 'ihsn.org';
$config['smtp_user'] = 'nada@ihsn.org';
$config['smtp_pass'] = 'free001';
$config['smtp_port'] = '25';
$config['mailtype']  = 'html';
$config['charset']   = 'utf-8';



//for gmail or any other SSL
/*
$config['protocol']  = 'smtp';
$config['smtp_host'] = 'ssl://smtp.googlemail.com';
$config['smtp_user'] = 'name@gmail.com';
$config['smtp_pass'] = 'password';
$config['smtp_port'] = '465';
$config['mailtype']  = 'html';
$config['charset']   = 'utf-8';
*/