<?php

defined('BASEPATH') OR exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| Authentication drivers
|--------------------------------------------------------------------------
|
| Maps driver names to library paths.
|
| Site-specific settings (active driver, OAuth credentials, etc.) belong in
| auth.local.php. Copy application/config/auth.local.sample.php to
| auth.local.php and edit for your environment.
|
*/
$config['authentication_drivers'] = array(
    'DefaultAuth'   => 'application/libraries/Auth/DefaultAuth.php',
    'SsoAuth'       => 'application/libraries/Auth/SsoAuth.php',
    'AzureAuth'     => 'application/libraries/Auth/AzureAuth.php',
    'Auth0'         => 'application/libraries/Auth/Auth0.php',
    'SocialAuth'    => 'application/libraries/Auth/SocialAuth.php',
    'ZeroAuth'      => 'application/libraries/Auth/ZeroAuth.php',
);

$local_config = APPPATH . 'config/auth.local.php';
if (file_exists($local_config)) {
    include $local_config;
} else {
    $config['authentication_driver'] = 'DefaultAuth';
}
