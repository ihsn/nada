<?php

require_once 'application/libraries/Auth/AuthInterface.php';
require_once 'application/libraries/Auth/DefaultAuth.php';

class Auth0 extends DefaultAuth implements AuthInterface {

	function __construct()
	{
		parent::__construct($skip_auth=TRUE);
		$this->ci =& get_instance();
		$this->ci->load->config('auth');
	}

	private function get_auth0_config($key, $default = null)
	{
		$config = $this->ci->config->item('auth0_auth');
		if (!is_array($config)) {
			return $default;
		}
		return isset($config[$key]) ? $config[$key] : $default;
	}

	private function get_alternate_login_url()
	{
		$url = $this->get_auth0_config('alternate_login_url', 'auth/alternate');

		if (strpos($url, 'http') === 0) {
			return $url;
		}

		return site_url($url);
	}

	private function redirect_after_login()
	{
		$destination = $this->ci->session->userdata('destination');

		if ($destination !== '' && $destination !== null) {
			$this->ci->session->unset_userdata('destination');
			redirect($destination, 'refresh');
		}

		redirect($this->ci->config->item('base_url'), 'refresh');
	}

	function login()
	{
		if ($this->ci->ion_auth->logged_in()) {
			$this->redirect_after_login();
		}

		if ($this->ci->input->get('error')) {
			$error_description = $this->ci->input->get('error_description');
			$this->ci->session->set_flashdata(
				'error',
				$error_description ? $error_description : t('login_failed')
			);
		}

		if ($this->ci->input->get('start') === '1' || $this->ci->input->post('auth0_login') === '1') {
			$this->ci->load->library('Auth0Oauth2');
			redirect($this->ci->auth0oauth2->get_login_url(), 'refresh');
		}

		if ($this->get_auth0_config('auto_redirect', false)) {
			$this->ci->load->library('Auth0Oauth2');
			redirect($this->ci->auth0oauth2->get_login_url(), 'refresh');
		}

		$this->ci->template->set_template('blank');
		$this->data['title'] = t('login');
		$this->data['error'] = $this->ci->session->flashdata('error');
		$this->data['message'] = $this->ci->session->flashdata('message');
		$this->data['enable_alternate_login'] = $this->get_auth0_config('enable_alternate_login', false);
		$this->data['alternate_login_url'] = $this->get_alternate_login_url();

		$content = $this->ci->load->view('auth/login_auth0', $this->data, true);
		$this->ci->template->write('content', $content, true);
		$this->ci->template->write('title', $this->data['title'], true);
		$this->ci->template->render();
	}

	/**
	 * Built-in NADA login (email + password) at a separate URL.
	 */
	function alternate()
	{
		if (!$this->get_auth0_config('enable_alternate_login', false)) {
			show_404();
		}

		if ($this->ci->ion_auth->logged_in()) {
			$this->redirect_after_login();
		}

		$this->ci->template->set_template('blank');
		$this->data['title'] = t('login');
		$csrf = $this->ci->nada_csrf->generate_token();

		$this->ci->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]');
		$this->ci->form_validation->set_rules('password', t('password'), 'required|max_length[100]');
		$this->ci->form_validation->set_rules('csrf_token', 'CSRF TOKEN', 'trim|callback_validate_token');

		if ($this->ci->form_validation->run() == true) {
			$email = $this->ci->input->post('email');
			$remember = $this->ci->input->post('remember') == 1;

			if ($this->ci->config->item('track_login_attempts') === TRUE) {
				$max_login_limit = $this->ci->ion_auth->is_max_login_attempts_exceeded($email);

				if ($max_login_limit) {
					$this->ci->session->set_flashdata('error', t('max_login_attempted'));
					sleep(3);
					redirect('auth/alternate', 'refresh');
				}
			}

			if ($this->ci->ion_auth->login($email, $this->ci->input->post('password'), $remember)) {
				$this->ci->db_logger->write_log('login', $email);
				$this->redirect_after_login();
			}

			$this->ci->session->set_flashdata('error', t('login_failed'));
			$this->ci->db_logger->write_log('login-failed', $email);
			redirect('auth/alternate', 'refresh');
		}

		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->ci->session->flashdata('error');
		$this->data['message'] = $this->ci->session->flashdata('message');
		$this->data['email_value'] = $this->ci->form_validation->set_value('email');
		$this->data['csrf'] = $csrf;
		$this->data['auth0_login_url'] = site_url('auth/login?start=1');

		$content = $this->ci->load->view('auth/login_alternate', $this->data, true);
		$this->ci->template->write('content', $content, true);
		$this->ci->template->write('title', $this->data['title'], true);
		$this->ci->template->render();
	}

	/**
	 * Send built-in password step users to the alternate login page.
	 */
	function password()
	{
		if ($this->get_auth0_config('enable_alternate_login', false)) {
			redirect('auth/alternate', 'refresh');
		}

		parent::password();
	}

	function callback()
	{
		if ($this->ci->ion_auth->logged_in()) {
			redirect('home', 'refresh');
		}

		if ($this->ci->input->get('error')) {
			$error_description = $this->ci->input->get('error_description');
			$this->ci->session->set_flashdata(
				'error',
				$error_description ? $error_description : t('login_failed')
			);
			redirect('auth/login', 'refresh');
		}

		$this->ci->load->library('Auth0Oauth2');

		if (!$this->ci->auth0oauth2->authenticate_from_callback()) {
			if (!$this->ci->session->flashdata('error')) {
				$error = t('login_failed');
				if (ENVIRONMENT === 'development') {
					$auth0_error = $this->ci->auth0oauth2->get_last_error();
					if ($auth0_error) {
						$error = $auth0_error;
					}
				}
				$this->ci->session->set_flashdata('error', $error);
			}
			redirect('auth/login', 'refresh');
		}

		$email = $this->ci->session->userdata('email');
		$this->ci->db_logger->write_log('login', $email);
		$this->redirect_after_login();
	}

	function logout()
	{
		$federated_logout = $this->get_auth0_config('federated_logout', true);
		$this->ci->ion_auth->logout();

		if ($federated_logout) {
			try {
				$this->ci->load->library('Auth0Oauth2');
				$this->ci->auth0oauth2->clear();
				redirect($this->ci->auth0oauth2->get_logout_url(site_url()), 'refresh');
			} catch (Exception $e) {
				log_message('error', 'Auth0 federated logout failed: ' . $e->getMessage());
			}
		}

		redirect('', 'refresh');
	}

}
