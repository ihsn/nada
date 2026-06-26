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
    'SsoAuth'       => 'application/libraries/Auth/SsoAuth.php',
    'AzureAuth'     => 'application/libraries/Auth/AzureAuth.php',
    'Auth0'         => 'application/libraries/Auth/Auth0.php',
    'SocialAuth'    => 'application/libraries/Auth/SocialAuth.php',
    'ZeroAuth'      => 'application/libraries/Auth/ZeroAuth.php',
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


/*
|--------------------------------------------------------------------------
| Social Login Providers for default authentication
|--------------------------------------------------------------------------
|
| List of social login providers to be used for authentication
| This configuration is used to enable social login functionality.
| Each provider should have the following keys:
| - name: The name of the provider (e.g., 'Google', 'Facebook').
| - icon: relative path to the image icon
| - enabled: A boolean indicating whether the provider is enabled.
| - client_id: The client ID for the provider.
| - client_secret: The client secret for the provider.
| - authorize_url: The URL to redirect users for authorization.
| - access_token_url: The URL to exchange the authorization code for an access token.
| - callback_url: The URL to redirect users back to after authorization.
|
*/

// Enable/disable default email authentication alongside social login
// When disabled, users can only authenticate using social providers
$config['social_auth']['enable_email_auth'] = true;


$config['social_login_providers'] = array(
    'orcid' => array(
        'name' => 'ORCID',
        'icon' => 'images/social_icons/orcid-logo.svg',
        'enabled' => true,
        'client_id' => 'your-orcid-client-id',
        'client_secret' => 'your-orcid-client-secret',
        'authorize_url' => 'https://orcid.org/oauth/authorize',
        'access_token_url' => 'https://orcid.org/oauth/token',
        'callback_url' => 'https://nada-catalog-url/index.php/auth/callback/orcid',
    ),
    'google' => array(
        'name' => 'Google',
        'icon' => 'images/social_icons/google.svg',
        'enabled' => true,
        'client_id' => 'your-google-client-id',
        'client_secret' => 'your-google-client-secret',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/auth',
        'access_token_url' => 'https://oauth2.googleapis.com/token',
        'callback_url' => 'http://m2.localhost/nada-sec2/index.php/auth/callback/google',
    ),
    'facebook' => array(
        'name' => 'Facebook',
        'icon' => 'images/social_icons/facebook.svg',
        'enabled' => false,
        'client_id' => 'your-facebook-client-id',
        'client_secret' => 'your-facebook-client-secret',
        'authorize_url' => 'https://www.facebook.com/dialog/oauth',
        'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
        'callback_url' => 'http://m2.localhost/nada-sec2/index.php/auth/callback/facebook',
    ),
    'github' => array(
        'name' => 'GitHub',
        'icon' => 'images/social_icons/github.svg',
        'enabled' => true,
        'client_id' => 'your-github-client-id',
        'client_secret' => 'your-github-client-secret',
        'authorize_url' => 'https://github.com/login/oauth/authorize',
        'access_token_url' => 'https://github.com/login/oauth/access_token',
        'callback_url' => 'http://m2.localhost/nada-sec2/index.php/auth/callback/github',
    ),
    'linkedin' => array(
        'name' => 'LinkedIn',
        'icon' => 'images/social_icons/linkedin.svg',
        'enabled' => true, 
        'client_id' => 'your-linkedin-client-id',
        'client_secret' => 'your-linkedin-client-secret',
        'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
        'access_token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'callback_url' => 'http://m2.localhost/nada-sec2/index.php/auth/callback/linkedin',
    ),
    // Add more providers as needed
);



/*
|--------------------------------------------------------------------------
| AzureAuth Config options
|--------------------------------------------------------------------------
|
| Configurations for AzureAuth
|

* OAuth 2.0 Endpoints  
*
* Authorization endpoint (v1) 
* https://login.microsoftonline.com/{tenent-id}/oauth2/authorize  
* 
* Token endpoint (v1) 
* https://login.microsoftonline.com/{tenent-id}/oauth2/token 
*
* Logout endpoint (v1) 
* https://login.microsoftonline.com/{tenent-id}/oauth2/logout 
*
* Authorization endpoint (v2) 
* https://login.microsoftonline.com/{tenent-id}/oauth2/v2.0/authorize  
*
*
* Token endpoint (v2) 
* https://login.microsoftonline.com/{tenent-id}/oauth2/v2.0/token 
*
* Microsoft Graph API endpoint 
* https://graph.microsoft.com 

* Login request format
* https://login.microsoftonline.com/{tenant-id}/oauth2/authorize?client_id={client-id}&response_mode=form_post&response_type=code%20id_token&nonce=any-random-value


*/

/*
|--------------------------------------------------------------------------
| Auth0 Config options
|--------------------------------------------------------------------------
|
| Configurations for Auth0 authentication driver.
| Set authentication_driver to 'Auth0' to enable.
|
| Register the callback URL in your Auth0 Application settings:
|   {base_url}/index.php/auth/callback
|
| Generate a cookie_secret with: openssl rand -hex 64
|
| federated_logout: clear Auth0 SSO session on logout
| enable_alternate_login: allow built-in email/password login at alternate_login_url
| alternate_login_url: default auth/alternate (useful when auto_redirect is true)
|
*/

$config['auth0_auth'] = array(
    'domain'         => '',
    'client_id'      => '',
    'client_secret'  => '',
    'cookie_secret'  => '',
    'redirect_uri'   => '', // leave empty to use site_url('auth/callback')
    'auto_redirect'  => true, // when true, /auth/login redirects immediately to Auth0
    'federated_logout' => true, // when true, logout also clears the Auth0 SSO session
    'enable_alternate_login' => true, // when true, built-in email/password login is available
    'alternate_login_url' => 'auth/alternate', // route for built-in login (relative to site_url)
);


$config['azure_auth']['client_id']='your-azure-client-id';
$config['azure_auth']['tenant_id']='your-azure-tenant-id';
$config['azure_auth']['authorize_endpoint']='https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/authorize';
$config['azure_auth']['token_endpoint'] = 'https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/token';
$config['azure_auth']['logout_endpoint'] = 'https://login.microsoftonline.com/'.$config['azure_auth']['tenant_id'].'/oauth2/logout';