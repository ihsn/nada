<?php

defined('BASEPATH') OR exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| Authentication providers
|--------------------------------------------------------------------------
|
| List of supported authentication providers
|
|
|
*/
$config['authentication_drivers'] = array(
    'DefaultAuth'   => 'application/libraries/Auth/DefaultAuth.php',
    'SsoAuth'       => 'application/libraries/Auth/SsoAuth.php'    
);


/*
|--------------------------------------------------------------------------
| Set active authentication
|--------------------------------------------------------------------------
|
| Set authentication provider to use
|
*/
$config['authentication_driver'] = 'DefaultAuth';
