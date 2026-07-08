<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Local authentication settings
|--------------------------------------------------------------------------
|
| Copy this file to auth.local.php and configure for your environment.
| auth.local.php is not tracked in git.
|
*/


/*
|--------------------------------------------------------------------------
| Set active authentication
|--------------------------------------------------------------------------
|
| Set authentication provider to use (must be a key from authentication_drivers
| in auth.php): DefaultAuth, SsoAuth, AzureAuth, Auth0, SocialAuth, ZeroAuth
|
*/
$config['authentication_driver'] = 'DefaultAuth';


/*
|--------------------------------------------------------------------------
| Social Login Providers (SocialAuth driver)
|--------------------------------------------------------------------------
|
| Each provider should have the following keys:
| - name: The name of the provider (e.g., 'Google', 'Facebook').
| - icon: relative path to the image icon
| - enabled: A boolean indicating whether the provider is enabled.
| - client_id: The client ID for the provider.
| - client_secret: The client secret for the provider.
| - authorize_url: The URL to redirect users for authorization.
| - access_token_url: The URL to exchange the authorization code for an access token.
| - callback_url: Optional. Leave empty to auto-generate from base_url as
|   index.php/auth/callback/{provider}
|
*/

// Enable/disable default email authentication alongside social login
$config['social_auth']['enable_email_auth'] = true;

$config['social_login_providers'] = array(
    'orcid' => array(
        'name' => 'ORCID',
        'icon' => 'images/social_icons/orcid-logo.svg',
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'authorize_url' => 'https://orcid.org/oauth/authorize',
        'access_token_url' => 'https://orcid.org/oauth/token',
    ),
    'google' => array(
        'name' => 'Google',
        'icon' => 'images/social_icons/google.svg',
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/auth',
        'access_token_url' => 'https://oauth2.googleapis.com/token',
    ),
    'facebook' => array(
        'name' => 'Facebook',
        'icon' => 'images/social_icons/facebook.svg',
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'authorize_url' => 'https://www.facebook.com/dialog/oauth',
        'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
    ),
    'github' => array(
        'name' => 'GitHub',
        'icon' => 'images/social_icons/github.svg',
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'authorize_url' => 'https://github.com/login/oauth/authorize',
        'access_token_url' => 'https://github.com/login/oauth/access_token',
    ),
    'linkedin' => array(
        'name' => 'LinkedIn',
        'icon' => 'images/social_icons/linkedin.svg',
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
        'access_token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
    ),
);


/*
|--------------------------------------------------------------------------
| AzureAuth
|--------------------------------------------------------------------------
|
| Set authentication_driver to 'AzureAuth' to enable.
|
| OAuth 2.0 endpoints (v1):
|   https://login.microsoftonline.com/{tenant-id}/oauth2/authorize
|   https://login.microsoftonline.com/{tenant-id}/oauth2/token
|   https://login.microsoftonline.com/{tenant-id}/oauth2/logout
|
*/
$config['azure_auth']['client_id'] = '';
$config['azure_auth']['tenant_id'] = '';
$config['azure_auth']['authorize_endpoint'] = 'https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/authorize';
$config['azure_auth']['token_endpoint'] = 'https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/token';
$config['azure_auth']['logout_endpoint'] = 'https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/logout';


/*
|--------------------------------------------------------------------------
| Auth0
|--------------------------------------------------------------------------
|
| Set authentication_driver to 'Auth0' to enable.
|
| Register the callback URL in your Auth0 Application settings:
|   {base_url}/index.php/auth/callback
|
| Generate a cookie_secret with: openssl rand -hex 64
|
*/
$config['auth0_auth'] = array(
    'domain'         => '',
    'client_id'      => '',
    'client_secret'  => '',
    'cookie_secret'  => '',
    'redirect_uri'   => '', // leave empty to use site_url('auth/callback')
    'auto_redirect'  => true,
    'federated_logout' => true,
    'enable_alternate_login' => true,
    'alternate_login_url' => 'auth/alternate',
);


/*
|--------------------------------------------------------------------------
| ZeroAuth (local/desktop mode)
|--------------------------------------------------------------------------
|
| Set authentication_driver to 'ZeroAuth' to enable.
|
*/
$config['zero_auth'] = array(
    'admin_email' => 'admin@localhost',
    'admin_name'  => 'Editor Admin',
);
