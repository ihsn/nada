<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Auth0\SDK\Auth0 as Auth0Client;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\StateException;

/**
 * Auth0 OAuth2 integration for NADA.
 */
class Auth0Oauth2
{
	protected $ci;
	protected $auth0 = null;
	protected $config = array();
	protected $last_error = '';

	function __construct()
	{
		log_message('debug', 'Auth0Oauth2 class initialized');
		$this->ci =& get_instance();
		$this->ci->config->load('auth');
		$this->ci->config->load('ion_auth');
		$this->tables = $this->ci->config->item('tables');
		$this->ci->load->model('Ion_auth_model');

		$config = $this->ci->config->item('auth0_auth');
		if (is_array($config)) {
			$this->config = $config;
		}
	}

	/**
	 * Get a configured Auth0 SDK client instance.
	 */
	function get_client()
	{
		if ($this->auth0 !== null) {
			return $this->auth0;
		}

		$domain = $this->get_config('domain');
		$client_id = $this->get_config('client_id');
		$client_secret = $this->get_config('client_secret');
		$cookie_secret = $this->get_config('cookie_secret');
		$redirect_uri = $this->get_redirect_uri();

		if (empty($domain) || empty($client_id) || empty($cookie_secret)) {
			show_error('Auth0 is not configured. Set auth0_auth options in application/config/auth.local.php');
		}

		$configuration = new SdkConfiguration(array(
			'domain' => $domain,
			'clientId' => $client_id,
			'clientSecret' => $client_secret,
			'cookieSecret' => $cookie_secret,
			'redirectUri' => $redirect_uri,
			'scope' => array('openid', 'profile', 'email'),
			'queryUserInfo' => true,
			'cookiePath' => $this->get_cookie_path(),
			'cookieSecure' => $this->is_https(),
		));

		// Use SDK cookie storage (default). SessionStore conflicts with CodeIgniter sessions
		// and causes "Invalid state" errors on the OAuth callback.
		$this->auth0 = new Auth0Client($configuration);
		return $this->auth0;
	}

	function get_last_error()
	{
		return $this->last_error;
	}

	private function set_error($message)
	{
		$this->last_error = $message;
		log_message('error', 'Auth0: ' . $message);
	}

	private function get_cookie_path()
	{
		$base = $this->ci->config->item('base_url') ?: base_url();
		$path = parse_url($base, PHP_URL_PATH);

		if (!empty($path) && $path !== '/') {
			return rtrim($path, '/') . '/';
		}

		return '/';
	}

	private function is_https()
	{
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
			return true;
		}

		if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
			return true;
		}

		return false;
	}

	private function get_config($key, $default = '')
	{
		return isset($this->config[$key]) ? $this->config[$key] : $default;
	}

	/**
	 * OAuth callback URL registered with Auth0.
	 */
	function get_redirect_uri()
	{
		$uri = $this->get_config('redirect_uri');

		if (!empty($uri)) {
			if (strpos($uri, 'http') !== 0) {
				$base = $this->ci->config->item('base_url') ?: base_url();
				return rtrim($base, '/') . '/' . ltrim($uri, '/');
			}
			return $uri;
		}

		return site_url('auth/callback');
	}

	/**
	 * Build the Auth0 Universal Login URL.
	 */
	function get_login_url()
	{
		return $this->get_client()->login();
	}

	/**
	 * Exchange authorization code for tokens after Auth0 callback.
	 */
	function exchange()
	{
		try {
			$this->get_client()->exchange();
			return true;
		} catch (StateException $e) {
			$this->set_error('Auth0 exchange failed: ' . $e->getMessage());
			return false;
		} catch (Exception $e) {
			$this->set_error('Auth0 exchange failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get the authenticated Auth0 user profile.
	 */
	function get_user()
	{
		return $this->get_client()->getUser();
	}

	/**
	 * Complete login from Auth0 callback and provision NADA user if needed.
	 */
	function authenticate_from_callback()
	{
		if (!$this->ci->input->get('code') || !$this->ci->input->get('state')) {
			$this->set_error('Auth0 callback missing code or state parameter');
			return false;
		}

		if (!$this->exchange()) {
			return false;
		}

		return $this->process_user($this->get_user());
	}

	/**
	 * Find or create a NADA user from Auth0 profile data.
	 */
	function process_user($auth0_user)
	{
		if (!is_array($auth0_user) || empty($auth0_user['sub'])) {
			$this->set_error('Auth0 user profile missing sub claim');
			return false;
		}

		$sub = $auth0_user['sub'];
		$email = isset($auth0_user['email']) ? trim($auth0_user['email']) : '';

		if ($email === '') {
			$this->set_error('Auth0 user missing email for sub: ' . $sub);
			return false;
		}

		$first_name = isset($auth0_user['given_name']) ? $auth0_user['given_name'] : '';
		$last_name = isset($auth0_user['family_name']) ? $auth0_user['family_name'] : '';

		if ($first_name === '' && !empty($auth0_user['name'])) {
			$parts = explode(' ', $auth0_user['name'], 2);
			$first_name = $parts[0];
			$last_name = isset($parts[1]) ? $parts[1] : '';
		}

		$user = null;
		$user_query = $this->ci->ion_auth_model->get_user_by_auth_type('AUTH0', $sub);

		if ($user_query && $user_query->num_rows() > 0) {
			$user = $user_query->row();
		}

		if (!$user) {
			$email_query = $this->ci->ion_auth_model->get_user_by_email($email);

			if ($email_query && $email_query->num_rows() > 0) {
				$user = $email_query->row();
			}
		}

		if (!$user) {
			$username = trim($first_name . ' ' . $last_name);
			if ($username === '') {
				$username = $email;
			}

			$additional_data = array(
				'first_name' => $first_name,
				'last_name' => $last_name,
				'email' => $email,
				'identity' => $username,
			);

			$user_id = $this->ci->ion_auth_model->register(
				$username,
				md5((string) date('U')),
				$email,
				$additional_data,
				'user',
				'AUTH0',
				$sub
			);

			if (!$user_id) {
				$errors = $this->ci->ion_auth_model->errors;
				$this->set_error('Auth0 user registration failed for ' . $email . (!empty($errors) ? ': ' . implode(', ', $errors) : ''));
				return false;
			}
		} else {
			$this->link_auth0_user($user->id, $sub);
		}

		return $this->login_user($email, true);
	}

	/**
	 * Link an existing NADA account to Auth0 and mark email as verified.
	 */
	private function link_auth0_user($user_id, $sub)
	{
		$update = array(
			'authtype' => 'AUTH0',
			'authtype_id' => $sub,
			'active' => 1,
		);

		if (!empty($this->ci->ion_auth->_extra_where)) {
			$this->ci->db->where($this->ci->ion_auth->_extra_where);
		}

		$this->ci->db->update($this->tables['users'], $update, array('id' => (int) $user_id));
	}

	/**
	 * Set NADA session for an active user.
	 */
	function login_user($email, $email_verified_by_auth0 = false)
	{
		if (empty($email)) {
			return false;
		}

		$query = $this->ci->db->select('username, email, id, active')
			->where('email', $email);

		if (!empty($this->ci->ion_auth->_extra_where)) {
			$query->where($this->ci->ion_auth->_extra_where);
		}

		$query = $query->get($this->tables['users']);

		$result = $query->row();

		if ($query->num_rows() !== 1) {
			$this->set_error('NADA user not found for email: ' . $email);
			return false;
		}

		if ((int) $result->active !== 1 && !$email_verified_by_auth0) {
			$this->ci->session->set_flashdata('error', t('user_email_not_verified'));
			$this->ci->session->set_userdata('verify_email', $email);
			return false;
		}

		$this->update_last_login($result->id);

		$identity = $this->ci->config->item('identity');
		if ($identity) {
			$this->ci->session->set_userdata($identity, $result->{$identity});
		}

		$this->ci->session->set_userdata('email', $result->email);
		$this->ci->session->set_userdata('username', $result->username);
		$this->ci->session->set_userdata('user_id', $result->id);

		return true;
	}

	public function update_last_login($id)
	{
		$this->ci->load->helper('date');

		if (isset($this->ci->ion_auth) && isset($this->ci->ion_auth->_extra_where)) {
			$this->ci->db->where($this->ci->ion_auth->_extra_where);
		}

		$this->ci->db->update($this->tables['users'], array('last_login' => now()), array('id' => $id));
		return $this->ci->db->affected_rows() == 1;
	}

	/**
	 * Build Auth0 logout URL and clear SDK session state.
	 */
	function get_logout_url($return_to = null)
	{
		$return_to = $return_to ?: site_url();
		return $this->get_client()->logout($return_to);
	}

	function clear()
	{
		$this->get_client()->clear();
	}
}
