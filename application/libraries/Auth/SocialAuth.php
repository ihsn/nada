<?php

require_once 'application/libraries/Auth/AuthInterface.php';
require_once 'application/libraries/Auth/DefaultAuth.php';

class SocialAuth extends DefaultAuth implements AuthInterface {

	private $providers = [];
	private $current_provider = null;
	private $provider_config = [];

    function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->ci =& get_instance();
		$this->ci->load->config('auth');		
		$this->ci->load->library('oauth2');
				
		if (!$this->ci->session->userdata('social_provider') && !$this->ci->session->userdata('social_id')) {
			$this->clear_social_session();
		}

		$this->init_providers();		
    }

	/**
	 * Clear social authentication session data
	 */
	private function clear_social_session()
	{
		$this->ci->session->unset_userdata('social_provider');
		$this->ci->session->unset_userdata('social_id');
		$this->ci->session->unset_userdata('oauth2state');
		$this->ci->session->unset_userdata('oauth2provider');
		$this->ci->session->unset_userdata('oauth2pkceCode');		
	}

	/**
	 * Initialize all configured OAuth providers
	 */
	private function init_providers()
	{
		$social_providers = $this->ci->config->item('social_login_providers');
		
		if (!$social_providers) {
			log_message('error', 'No social login providers configured');
			return;
		}

		foreach ($social_providers as $provider_key => $provider_config) {
			if (!$provider_config['enabled']) {
				log_message('debug', 'Provider ' . $provider_key . ' is disabled');
				continue;
			}

			try {
				$callback_url = $this->get_callback_url($provider_key);
				log_message('debug', 'Initializing provider ' . $provider_key . ' with callback URL: ' . $callback_url);
				
				$this->providers[$provider_key] = new \League\OAuth2\Client\Provider\GenericProvider([
					'clientId'                => $provider_config['client_id'],
					'clientSecret'            => $provider_config['client_secret'],
					'redirectUri'             => $callback_url,
					'urlAuthorize'            => $provider_config['authorize_url'],
					'urlAccessToken'          => $provider_config['access_token_url'],
					'urlResourceOwnerDetails' => $provider_config['access_token_url']
				]);
				
				$this->provider_config[$provider_key] = $provider_config;
				log_message('debug', 'Successfully initialized provider ' . $provider_key);
				
			} catch (Exception $e) {
				log_message('error', 'Failed to initialize provider ' . $provider_key . ': ' . $e->getMessage());
			}
		}
	}

	/**
	 * Get callback URL for a specific provider
	 */
	private function get_callback_url($provider_key)
	{
		$social_providers = $this->ci->config->item('social_login_providers');
		
		if (isset($social_providers[$provider_key]['callback_url'])) {
			$callback_url = $social_providers[$provider_key]['callback_url'];
			
			// If it's a relative URL, make it absolute
			if (strpos($callback_url, 'http') !== 0) {
				$base_url = $this->ci->config->item('base_url') ?: base_url();
				return $base_url . $callback_url;
			}
			
			return $callback_url;
		}
		
		// Fallback to default format if not configured
		$base_url = $this->ci->config->item('base_url') ?: base_url();
		return $base_url . 'index.php/auth/callback/' . $provider_key;
	}

	/**
	 * Get all available providers
	 */
	public function get_available_providers()
	{
		return array_keys($this->providers);
	}

	/**
	 * Get provider configuration
	 */
	public function get_provider_config($provider_key)
	{
		return isset($this->provider_config[$provider_key]) ? $this->provider_config[$provider_key] : null;
	}

	function login($provider_key = null)
	{
		if ($provider_key) {
			$this->provider_login($provider_key);
			return;
		}

		$this->ci->template->set_template('blank');
        $this->data['title'] = t("login");
		$csrf = $this->ci->nada_csrf->generate_token();

        // Get available providers for the login page
        $this->data['providers'] = [];
        foreach ($this->providers as $key => $provider) {
            $config = $this->provider_config[$key];
            $this->data['providers'][$key] = [
                'name' => $config['name'],
                'icon' => base_url() . $config['icon'],
                'login_url' => 'auth/login/' . $key
            ];
        }

        // Check if email authentication is enabled
        $enable_email_auth = $this->ci->config->item('social_auth')['enable_email_auth'] ?? true;
        $this->data['enable_email_auth'] = $enable_email_auth;

        if ($enable_email_auth) {
            // Validate form input
        	$this->ci->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]');
    		$this->ci->form_validation->set_rules('csrf_token', 'CSRF TOKEN', 'trim|callback_validate_token');

            if ($this->ci->form_validation->run() == true) {
    			$email = $this->ci->input->post('email');
    			
    			// Check if user exists
    			$user = $this->ci->ion_auth->get_user_by_email($email);
    			
    			if ($user) {
    				// User exists, check their auth type
    				if (isset($user->authtype) && $user->authtype != '') {
    					// User has a social auth type, check if it's a supported social auth type
    					if (in_array(strtolower($user->authtype), array_keys($this->providers))) {
    						// User has a supported social auth type, redirect to that provider's login
    						$this->ci->session->set_userdata('login_email', $email);
    						redirect("auth/login/" . $user->authtype, 'refresh');
    					} else {
    						// User has an unsupported social auth type, treat as built-in auth
    						$this->ci->session->set_userdata('login_email', $email);
    						redirect("auth/password", 'refresh');
    					}
    				} else {
    					// User has built-in auth, redirect to password page
    					$this->ci->session->set_userdata('login_email', $email);
    					redirect("auth/password", 'refresh');
    				}
    			} else {
    				// User doesn't exist, show social login options for registration
    				$this->show_social_login_options($email, null);
    				return;
    			}
            }
        }

        // Show login form (with or without email authentication)
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->ci->session->flashdata('error');
        $this->data['email'] = array(
            'name'    => 'email',
            'id'      => 'email',
            'type'    => 'text',
            'value'   => $this->ci->form_validation->set_value('email'),
        );
        $this->data['csrf'] = $csrf;
        
        $content = $this->ci->load->view('auth/login-email', $this->data, TRUE);
        $this->ci->template->write('content', $content, true);
        $this->ci->template->write('title', t('login'), true);
        $this->ci->template->render();
	}

	/**
	 * Show social login options for existing or new users
	 */
	private function show_social_login_options($email, $existing_auth_type = null)
	{
		$this->ci->template->set_template('blank');
        $this->data['title'] = t("login");
        
        // Get available providers for the login page
        $this->data['providers'] = [];
        foreach ($this->providers as $key => $provider) {
            $config = $this->provider_config[$key];
            $this->data['providers'][$key] = [
                'name' => $config['name'],
                'icon' => base_url() . $config['icon'],
                'login_url' => 'auth/login/' . $key
            ];
        }
        
        $this->data['email'] = $email;
        $this->data['existing_auth_type'] = $existing_auth_type;
        
		$content = $this->ci->load->view('oauth/login-options', $this->data, TRUE);
		$this->ci->template->write('content', $content, true);
		$this->ci->template->write('title', t('login'), true);
		$this->ci->template->render();
	}

	/**
	 * Generic login method for any provider
	 */
	function provider_login($provider_key)
	{
		$provider_key = strtolower($provider_key);

		if (!isset($this->providers[$provider_key])) {
			log_message('error', 'Provider not found: ' . $provider_key);
			show_404();
		}

		// Clear any existing social session data
		$this->ci->session->unset_userdata('social_provider');
		$this->ci->session->unset_userdata('social_id');

		$this->current_provider = $provider_key;
		$provider = $this->providers[$provider_key];

		// Get authorization code
		if (!$this->ci->input->get('code')) {			
			// Get provider-specific scopes
			$scopes = $this->get_provider_scopes($provider_key);
			
			// Fetch the authorization URL from the provider
			$authorizationUrl = $provider->getAuthorizationUrl($scopes);

			// Get the state generated for you and store it to the session.
			$this->ci->session->set_userdata('oauth2state', $provider->getState());
			$this->ci->session->set_userdata('oauth2provider', $provider_key);

			// Optional, only required when PKCE is enabled.
			// Get the PKCE code generated for you and store it to the session.
			$this->ci->session->set_userdata('oauth2pkceCode', $provider->getPkceCode());

			// Redirect the user to the authorization URL.
			redirect($authorizationUrl);
		} else {
			$this->callback($provider_key);
		}
	}

	/**
	 * Get provider-specific scopes
	 */
	private function get_provider_scopes($provider_key)
	{
		$scopes = [];
		
		switch ($provider_key) {
			case 'orcid':
				$scopes = ['scope' => ['/authenticate']];
				break;
			case 'google':
				$scopes = ['scope' => ['openid', 'email', 'profile']];
				break;
			case 'facebook':
				$scopes = ['scope' => ['email', 'public_profile']];
				break;
			case 'github':
				$scopes = ['scope' => ['user:email']];
				break;
			case 'linkedin':
				$scopes = ['scope' => ['r_liteprofile', 'r_emailaddress']];
				break;
			default:
				$scopes = ['scope' => ['openid', 'email']];
		}
		
		log_message('debug', 'Scopes for ' . $provider_key . ': ' . json_encode($scopes));
		return $scopes;
	}

	/**
	 * Generic callback method for any provider
	 */
	function callback($provider_key = null)
	{
		if (!$provider_key) {
			$provider_key = $this->ci->uri->segment(3);
		}

		if (!isset($this->providers[$provider_key])) {
			show_error('Provider not found: ' . $provider_key);
		}

		$provider = $this->providers[$provider_key];
		$provider_config = $this->provider_config[$provider_key];

		try {
			// Check if user is already fully logged in (has user_id in session)
			if ($this->ci->session->userdata('user_id')) {
				$this->ci->session->set_flashdata('error', 'You are already logged in.');
				redirect('home');
			}

			if (!$this->ci->input->get('code')) {
				$this->ci->session->set_flashdata('error', 'Failed to get authorization code. Please try again.');
				redirect('auth/login');
			}

			// Get access token
			$tokens = $provider->getAccessToken('authorization_code', [
				'code' => $this->ci->input->get('code')
			]);

			$values = $tokens->getValues();

			// Extract user information based on provider
			$user_info = $this->extract_user_info($provider_key, $values, $tokens->getToken());

			if (!$user_info) {
				show_error("Failed to get user information from " . $provider_config['name'] . ". Please try again.");
			}

			// Clear any existing social session data before setting new ones
			$this->ci->session->unset_userdata('social_provider');
			$this->ci->session->unset_userdata('social_id');
			
			// Store in session
			$this->ci->session->set_userdata('social_provider', $provider_key);
			$this->ci->session->set_userdata('social_id', $user_info['id']);
			
			log_message('debug', 'Session data set - social_provider: ' . $provider_key . ', social_id: ' . $user_info['id']);

			// Check if user is already registered with this provider
			$existing_user = $this->ci->ion_auth_model->get_user_by_auth_type(
				strtoupper($provider_key), 
				$user_info['id']
			)->row_array();

			if (is_array($existing_user) && count($existing_user) > 0) {
				// Login user
				$status = $this->ci->oauth2->login_user($existing_user['email']);

				if ($status) {
					// Log
					$this->ci->db_logger->write_log('login', $existing_user['email']);
					// Clear session data
					$this->ci->session->unset_userdata('social_provider');
					$this->ci->session->unset_userdata('social_id');
					// Redirect to home page
					redirect('home');
				} else {
					redirect('auth/login');
				}
				return;
			}
			
			// If not registered, redirect to registration
			log_message('debug', 'Redirecting to social_register with session data intact');
			redirect('auth/social_register');
						
		} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
			show_error($e->getMessage());
			die();
		}
	}

	/**
	 * Extract user information based on provider
	 */
	private function extract_user_info($provider_key, $values, $access_token)
	{
		$user_info = null;
		
		switch ($provider_key) {
			case 'orcid':
				$user_info = $this->extract_orcid_info($values, $access_token);
				break;
			case 'google':
				$user_info = $this->extract_google_info($values);
				break;
			case 'facebook':
				$user_info = $this->extract_facebook_info($values);
				break;
			case 'github':
				$user_info = $this->extract_github_info($values, $access_token);
				break;
			case 'linkedin':
				$user_info = $this->extract_linkedin_info($values, $access_token);
				break;
			default:
				$user_info = $this->extract_generic_info($values);
		}
		
		// Ensure we have at least an ID, even if email is null
		if ($user_info && isset($user_info['id']) && $user_info['id']) {
			return $user_info;
		}
		
		return null;
	}

	/**
	 * Extract ORCID user information
	 */
	private function extract_orcid_info($values, $access_token)
	{
		$orcid = isset($values['orcid']) ? $values['orcid'] : null;
		$name = isset($values['name']) ? $values['name'] : null;
		
		if ($orcid === null) {
			return null;
		}

		// Try to get email from ORCID API, but don't fail if it's not available
		$email = null;
		try {
			$email = $this->orcid_get_email($access_token, $orcid);
		} catch (Exception $e) {
			log_message('warning', 'Failed to get email from ORCID API: ' . $e->getMessage());
		}

		return [
			'id' => $orcid,
			'email' => $email, // Can be null, user will provide during registration
			'name' => $name,
			'first_name' => $name ? explode(' ', $name)[0] : '',
			'last_name' => $name ? implode(' ', array_slice(explode(' ', $name), 1)) : ''
		];
	}

	/**
	 * Extract Google user information
	 */
	private function extract_google_info($values)
	{
		return [
			'id' => $values['sub'] ?? null,
			'email' => $values['email'] ?? null,
			'name' => $values['name'] ?? null,
			'first_name' => $values['given_name'] ?? '',
			'last_name' => $values['family_name'] ?? ''
		];
	}

	/**
	 * Extract Facebook user information
	 */
	private function extract_facebook_info($values)
	{
		return [
			'id' => $values['id'] ?? null,
			'email' => $values['email'] ?? null,
			'name' => $values['name'] ?? null,
			'first_name' => $values['first_name'] ?? '',
			'last_name' => $values['last_name'] ?? ''
		];
	}

	/**
	 * Extract GitHub user information
	 */
	private function extract_github_info($values, $access_token)
	{
		// Get user profile from GitHub API
		$user_profile = $this->github_get_user_profile($access_token);
		
		if (!$user_profile) {
			log_message('error', 'Failed to get GitHub user profile');
			return null;
		}
		
		$user_id = $user_profile['id'] ?? null;
		
		// Try to get email from GitHub API, but don't fail if it's not available
		$email = null;
		try {
			$email = $this->github_get_email($access_token);
		} catch (Exception $e) {
			log_message('warning', 'Failed to get email from GitHub API: ' . $e->getMessage());
		}

		// Get name safely
		$name = $user_profile['name'] ?? $user_profile['login'] ?? null;
		
		// Calculate first and last name safely
		$first_name = '';
		$last_name = '';
		if ($name) {
			$name_parts = explode(' ', $name);
			$first_name = $name_parts[0] ?? '';
			$last_name = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';
		}

		return [
			'id' => $user_id,
			'email' => $email, // Can be null, user will provide during registration
			'name' => $name,
			'first_name' => $first_name,
			'last_name' => $last_name
		];
	}

	/**
	 * Get GitHub user profile
	 */
	private function github_get_user_profile($access_token)
	{
		try {
			$client = new \GuzzleHttp\Client([
				'timeout' => 30,
				'verify' => false
			]);

			$response = $client->get('https://api.github.com/user', [
				'headers' => [
					'Authorization' => 'token ' . $access_token,
					'Accept' => 'application/vnd.github.v3+json',
					'User-Agent' => 'NADA-SocialAuth'
				]
			]);

			if ($response->getStatusCode() === 200) {
				$user_data = json_decode($response->getBody()->getContents(), true);
				if (is_array($user_data) && isset($user_data['id'])) {
					log_message('debug', 'GitHub user profile retrieved successfully');
					return $user_data;
				} else {
					log_message('error', 'Invalid GitHub user profile response: ' . $response->getBody()->getContents());
				}
			} else {
				log_message('error', 'GitHub API returned HTTP code: ' . $response->getStatusCode());
			}
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			log_message('error', 'GitHub user profile request failed: ' . $e->getMessage());
		} catch (Exception $e) {
			log_message('error', 'GitHub user profile extraction failed: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Extract LinkedIn user information
	 */
	private function extract_linkedin_info($values, $access_token)
	{
		// Get user profile from LinkedIn API
		$user_profile = $this->linkedin_get_user_profile($access_token);
		
		if (!$user_profile) {
			log_message('error', 'Failed to get LinkedIn user profile');
			return null;
		}
		
		$user_id = $user_profile['id'] ?? null;
		
		// Try to get email from LinkedIn API, but don't fail if it's not available
		$email = null;
		try {
			$email = $this->linkedin_get_email($access_token);
		} catch (Exception $e) {
			log_message('warning', 'Failed to get email from LinkedIn API: ' . $e->getMessage());
		}

		// Get name safely
		$first_name = $user_profile['localizedFirstName'] ?? '';
		$last_name = $user_profile['localizedLastName'] ?? '';
		$name = trim($first_name . ' ' . $last_name);

		return [
			'id' => $user_id,
			'email' => $email, // Can be null, user will provide during registration
			'name' => $name,
			'first_name' => $first_name,
			'last_name' => $last_name
		];
	}

	/**
	 * Get LinkedIn user profile
	 */
	private function linkedin_get_user_profile($access_token)
	{
		try {
			$client = new \GuzzleHttp\Client([
				'timeout' => 30,
				'verify' => false
			]);

			$response = $client->get('https://api.linkedin.com/v2/me', [
				'headers' => [
					'Authorization' => 'Bearer ' . $access_token,
					'Accept' => 'application/json',
					'User-Agent' => 'NADA-SocialAuth'
				]
			]);

			if ($response->getStatusCode() === 200) {
				$user_data = json_decode($response->getBody()->getContents(), true);
				if (is_array($user_data) && isset($user_data['id'])) {
					log_message('debug', 'LinkedIn user profile retrieved successfully');
					return $user_data;
				} else {
					log_message('error', 'Invalid LinkedIn user profile response: ' . $response->getBody()->getContents());
				}
			} else {
				log_message('error', 'LinkedIn API returned HTTP code: ' . $response->getStatusCode());
			}
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			log_message('error', 'LinkedIn user profile request failed: ' . $e->getMessage());
		} catch (Exception $e) {
			log_message('error', 'LinkedIn user profile extraction failed: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Get email from LinkedIn API
	 */
	private function linkedin_get_email($access_token)
	{
		try {
			$client = new \GuzzleHttp\Client([
				'timeout' => 30,
				'verify' => false
			]);

			$response = $client->get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', [
				'headers' => [
					'Authorization' => 'Bearer ' . $access_token,
					'Accept' => 'application/json',
					'User-Agent' => 'NADA-SocialAuth'
				]
			]);

			if ($response->getStatusCode() === 200) {
				$email_data = json_decode($response->getBody()->getContents(), true);

				if (is_array($email_data) && isset($email_data['elements'][0]['handle~']['emailAddress'])) {
					$email = $email_data['elements'][0]['handle~']['emailAddress'];
					log_message('debug', 'LinkedIn email found: ' . $email);
					return $email;
				} else {
					log_message('debug', 'No email found in LinkedIn response');
				}
			} else {
				log_message('error', 'LinkedIn email API returned HTTP code: ' . $response->getStatusCode());
			}
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			log_message('error', 'LinkedIn email request failed: ' . $e->getMessage());
		} catch (Exception $e) {
			log_message('warning', 'LinkedIn email extraction failed: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Extract generic user information
	 */
	private function extract_generic_info($values)
	{
		return [
			'id' => $values['id'] ?? $values['sub'] ?? null,
			'email' => $values['email'] ?? null,
			'name' => $values['name'] ?? null,
			'first_name' => $values['first_name'] ?? $values['given_name'] ?? '',
			'last_name' => $values['last_name'] ?? $values['family_name'] ?? ''
		];
	}

	/**
	 * Get email from ORCID API
	 */
	private function orcid_get_email($accessToken, $orcId)
	{
		try {
			$resourceUrl = 'https://pub.orcid.org/v3.0/' . $orcId . '/email';

			// Get the user's details
			$request = $this->providers['orcid']->getAuthenticatedRequest(
				'GET',
				$resourceUrl,
				$accessToken,
				['headers' => ['Accept' => 'application/json']]
			);

			$response = $this->providers['orcid']->getResponse($request);
			$result = json_decode($response->getBody()->getContents(), true);

			if (isset($result['email'][0]['email'])) {
				return $result['email'][0]['email'];
			}
		} catch (Exception $e) {
			log_message('warning', 'ORCID email extraction failed: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Get email from GitHub API
	 */
	private function github_get_email($access_token)
	{
		try {
			$client = new \GuzzleHttp\Client([
				'timeout' => 30,
				'verify' => false
			]);

			$response = $client->get('https://api.github.com/user/emails', [
				'headers' => [
					'Authorization' => 'token ' . $access_token,
					'Accept' => 'application/vnd.github.v3+json',
					'User-Agent' => 'NADA-SocialAuth'
				]
			]);

			if ($response->getStatusCode() === 200) {
				$emails = json_decode($response->getBody()->getContents(), true);

				if (is_array($emails)) {
					foreach ($emails as $email) {
						if ($email['primary'] && $email['verified']) {
							log_message('debug', 'GitHub primary email found: ' . $email['email']);
							return $email['email'];
						}
					}
					log_message('debug', 'No primary verified email found in GitHub response');
				} else {
					log_message('error', 'Invalid GitHub email response: ' . $response->getBody()->getContents());
				}
			} else {
				log_message('error', 'GitHub email API returned HTTP code: ' . $response->getStatusCode());
			}
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			log_message('error', 'GitHub email request failed: ' . $e->getMessage());
		} catch (Exception $e) {
			log_message('warning', 'GitHub email extraction failed: ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Generic registration method for social users
	 */
	function social_register()
	{
		log_message('debug', 'Social register method called');
		log_message('debug', 'Session social_provider: ' . $this->ci->session->userdata('social_provider'));
		log_message('debug', 'Session social_id: ' . $this->ci->session->userdata('social_id'));
		log_message('debug', 'Session user_id: ' . $this->ci->session->userdata('user_id'));
		log_message('debug', 'Session email: ' . $this->ci->session->userdata('email'));
		
		if (!$this->ci->session->userdata('social_provider') || !$this->ci->session->userdata('social_id')) {
			log_message('error', 'Missing session data for social registration');
			log_message('error', 'social_provider: ' . $this->ci->session->userdata('social_provider'));
			log_message('error', 'social_id: ' . $this->ci->session->userdata('social_id'));
			$this->ci->session->set_flashdata('error', 'You are not logged in.');
			redirect('auth/login');
		}

		$provider_key = $this->ci->session->userdata('social_provider');
		log_message('debug', 'Provider key: ' . $provider_key);
		
		if (!isset($this->provider_config[$provider_key])) {
			log_message('error', 'Provider config not found for: ' . $provider_key);
			show_error('Provider configuration not found');
		}
		
		$provider_config = $this->provider_config[$provider_key];

		$this->ci->template->set_template('blank');
		$this->data['title'] = t("register");		
		$content = NULL;

		$use_complex_password = $this->ci->config->item("require_complex_password");
		$csrf = $this->ci->nada_csrf->generate_token();

        // Validate form input
    	$this->ci->form_validation->set_rules('first_name', t('first_name'), 'trim|disable_html_tags|validate_name|required|xss_clean|max_length[50]');
    	$this->ci->form_validation->set_rules('last_name', t('last_name'), 'trim|disable_html_tags|validate_name|required|xss_clean|max_length[50]');
    	$this->ci->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]|check_user_email_exists');
    	$this->ci->form_validation->set_rules('country', t('country'), 'trim|disable_html_tags|xss_clean|max_length[150]|check_user_country_valid');
		$this->ci->form_validation->set_rules('csrf_token', 'CSRF TOKEN', 'trim|callback_validate_token');
    	$this->ci->form_validation->set_rules($this->ci->captcha_lib->get_question_field(), t('captcha'), 'trim|required|callback_validate_captcha');

        if ($this->ci->form_validation->run() === TRUE) {
			log_message('debug', 'Form validation passed for social registration');
			
			// Get email from form input
			$email = $this->ci->input->post('email');
			
			// Log
			$this->ci->db_logger->write_log('register', $email);

			// Check to see if we are creating the user
			$username = $this->ci->input->post('first_name') . ' ' . $this->ci->input->post('last_name');

        	$additional_data = array(
        		'first_name' => $this->ci->input->post('first_name'),
        		'last_name'  => $this->ci->input->post('last_name'),
				'country'   => $this->ci->input->post('country'),
				'email'     => $email,
				'identity'  => $username
        	);

			// Check if user is already registered
			$user_info = $this->ci->ion_auth_model->get_user_by_auth_type(
				strtoupper($provider_key), 
				$this->ci->session->userdata('social_id')
			)->row_array();

			if (is_array($user_info) && count($user_info) > 0) {
				show_error('User already registered. Please login.');
			}

			$user_id = $this->ci->ion_auth->register(
				$username, 
				md5(date("U")),
				$email, 
				$additional_data, 
				$group_name = 'user', 
				$auth_type = strtoupper($provider_key),
				$social_id = $this->ci->session->userdata('social_id')
			);

			if (!$user_id) {
				$this->ci->session->set_flashdata('error', 'Registration failed. Please try again.');
				redirect('auth/social_register');
			}

			// Unset session
			$this->ci->session->unset_userdata('social_provider');
			$this->ci->session->unset_userdata('social_id');

			$content = $this->ci->load->view('auth/create_user_confirm', NULL, TRUE);

			// Notify admins
			$subject = sprintf('[%s] - %s', t('notification'), t('new_user_registration')) . ' - ' . $username;
			$message = $this->ci->load->view('auth/email/admin_notice_new_registration', $additional_data, true);
			notify_admin($subject, $message);
			
			$this->ci->session->set_userdata('email', $email);
			redirect('auth/registration_complete');
		} else {
			log_message('debug', 'Form validation failed for social registration');
			log_message('debug', 'Validation errors: ' . validation_errors());
			// Set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->ci->session->flashdata('message');

			$this->data['captcha_question'] = $this->ci->captcha_lib->get_html();
			$this->data['provider_name'] = $provider_config['name'];
			$this->data['social_id'] = $this->ci->session->userdata('social_id');

			$this->data['first_name'] = array(
				'name'   => 'first_name',
				'id'      => 'first_name',
				'type'    => 'text',
				'value'   => $this->ci->form_validation->set_value('first_name'),
			);
            $this->data['last_name'] = array(
				'name'   => 'last_name',
				'id'      => 'last_name',
				'type'    => 'text',
				'value'   => $this->ci->form_validation->set_value('last_name'),
			);
			$this->data['email'] = array(
				'name'   => 'email',
				'id'      => 'email',
				'type'    => 'email',
				'value'   => $this->ci->form_validation->set_value('email'),
			);
			$this->data['csrf'] = $csrf;	
			$content = $this->ci->load->view('auth/social_create_user', $this->data, TRUE);
		}

		// Render final output
		$this->ci->template->write('content', $content, true);
		$this->ci->template->write('title', $this->data['title'], true);
		$this->ci->template->render();
	}

	// Legacy methods for backward compatibility
	function orcid_login() {
		return $this->provider_login('orcid');
	}

	function orcid_register() {
		return $this->social_register();
	}

	//log the user in
    function nada()
    {
		//try authenticating with default parent
		$this->ci->load->library('DefaultAuth');
		return $this->ci->defaultauth->login();
    }

	/**
	 * Handle password login for existing users
	 */
	function password()
	{
		// Use the parent DefaultAuth password method
		$this->ci->load->library('DefaultAuth');
		return $this->ci->defaultauth->password();
	}

	/**
	 * Logout method with social session cleanup
	 */
	function logout()
	{
		// Clear social session data
		$this->clear_social_session();
		
		// Use the parent DefaultAuth logout method
		$this->ci->load->library('DefaultAuth');
		return $this->ci->defaultauth->logout();
	}

}//end-class
