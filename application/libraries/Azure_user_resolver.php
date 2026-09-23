<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resolves NADA users for Azure AD SSO and AzureAuth alternate (password) login.
 * Uses authtype/authtype_id (AAD + oid) with optional org email domain equivalence.
 */
class Azure_user_resolver {

	const AUTH_TYPE = 'AAD';

	const ERROR_AMBIGUOUS_LOCAL_PART = 'ambiguous_local_part';
	const ERROR_IDENTITY_CONFLICT = 'identity_conflict';
	const ERROR_USER_NOT_FOUND = 'user_not_found';

	/** @var CI_Controller */
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('Ion_auth_model');
	}

	/**
	 * @param array $claims Decoded ID token claims
	 * @param string $token_email Email extracted from claims
	 * @return array
	 */
	public function resolve_for_azure($claims, $token_email)
	{
		$oid = $this->extract_oid_from_claims($claims);
		$token_email = $this->normalize_email($token_email);

		if ($oid !== '') {
			$query = $this->ci->Ion_auth_model->get_user_by_auth_type(self::AUTH_TYPE, $oid);
			if ($query && $query->num_rows() > 0) {
				return $this->success_result($query->row(), 'identity', $oid);
			}
		}

		if ($token_email !== '') {
			$by_email = $this->resolve_by_email_rules($token_email, $oid, true);
			if ($by_email['status'] === 'error') {
				return $by_email;
			}
			if ($by_email['status'] === 'found') {
				return $by_email;
			}
		}

		return array(
			'status' => 'register',
			'user' => null,
			'matched_by' => null,
			'oid' => $oid,
			'error' => null,
		);
	}

	/**
	 * Password login for AzureAuth::alternate() — resolve typed email only.
	 *
	 * @param string $email
	 * @return array
	 */
	public function resolve_for_password_login($email)
	{
		$email = $this->normalize_email($email);
		if ($email === '') {
			return $this->error_result(self::ERROR_USER_NOT_FOUND, '');
		}

		$result = $this->resolve_by_email_rules($email, '', false);
		if ($result['status'] === 'not_found') {
			return $this->error_result(self::ERROR_USER_NOT_FOUND, '');
		}
		return $result;
	}

	/**
	 * @param array $claims
	 * @return array oid, email, first_name, last_name
	 */
	public function extract_profile_from_claims($claims)
	{
		$claims = json_decode(json_encode($claims), true);
		if (!is_array($claims)) {
			$claims = array();
		}

		$azure_config = $this->ci->config->item('azure_auth');
		$oid_claim = 'oid';
		if (is_array($azure_config) && !empty($azure_config['identity_claim'])) {
			$oid_claim = $azure_config['identity_claim'];
		}

		$email_claims = array('email', 'preferred_username', 'upn', 'unique_name');
		if (is_array($azure_config) && !empty($azure_config['email_claims']) && is_array($azure_config['email_claims'])) {
			$email_claims = $azure_config['email_claims'];
		}

		$email = '';
		foreach ($email_claims as $claim_name) {
			if (!empty($claims[$claim_name])) {
				$email = $this->normalize_email($claims[$claim_name]);
				if ($email !== '') {
					break;
				}
			}
		}

		$first_name = isset($claims['given_name']) ? (string) $claims['given_name'] : '';
		$last_name = isset($claims['family_name']) ? (string) $claims['family_name'] : '';
		if ($first_name === '' && !empty($claims['name'])) {
			$parts = explode(' ', (string) $claims['name'], 2);
			$first_name = $parts[0];
			$last_name = isset($parts[1]) ? $parts[1] : '';
		}

		return array(
			'oid' => isset($claims[$oid_claim]) ? (string) $claims[$oid_claim] : '',
			'email' => $email,
			'first_name' => $first_name,
			'last_name' => $last_name,
		);
	}

	/**
	 * Persist Azure identity and refresh email from token.
	 *
	 * @param int $user_id
	 * @param string $oid
	 * @param string $email
	 * @return bool
	 * @throws Exception
	 */
	public function link_azure_user($user_id, $oid, $email)
	{
		if ($oid === '') {
			return true;
		}

		$query = $this->ci->Ion_auth_model->get_user_by_auth_type(self::AUTH_TYPE, $oid);
		if ($query && $query->num_rows() > 0) {
			$existing = $query->row();
			if ((int) $existing->id !== (int) $user_id) {
				throw new Exception('Azure identity is already linked to another account');
			}
		}

		$user = $this->ci->Ion_auth_model->get_user((int) $user_id);
		if (!$user) {
			throw new Exception('User not found');
		}

		if (!$this->user_azure_identity_is_empty($user) && (string) $user->authtype_id !== (string) $oid) {
			throw new Exception('Account already linked to a different Azure identity');
		}

		$email = $this->normalize_email($email);
		if ($email !== '' && $this->email_owned_by_other_user($email, $user_id)) {
			throw new Exception('Email address is already used by another account');
		}

		return $this->ci->Ion_auth_model->link_auth_identity(
			$user_id,
			self::AUTH_TYPE,
			$oid,
			$email !== '' ? $email : null
		);
	}

	/**
	 * @param string $posted_email
	 * @param string $password
	 * @param bool $remember
	 * @return bool
	 */
	public function attempt_password_login($posted_email, $password, $remember = false)
	{
		$this->ci->load->library('ion_auth');
		$resolved = $this->resolve_for_password_login($posted_email);

		if ($resolved['status'] === 'error') {
			if ($resolved['error'] === self::ERROR_AMBIGUOUS_LOCAL_PART) {
				$this->ci->session->set_flashdata(
					'error',
					$this->error_message_for_code($resolved['error'])
				);
				return false;
			}
			return $this->ci->ion_auth->login($posted_email, $password, $remember);
		}

		if ($resolved['status'] === 'found' && !empty($resolved['user']->email)) {
			return $this->ci->ion_auth->login($resolved['user']->email, $password, $remember);
		}

		return $this->ci->ion_auth->login($posted_email, $password, $remember);
	}

	public function error_message_for_code($error_code)
	{
		if ($error_code === self::ERROR_AMBIGUOUS_LOCAL_PART) {
			return 'Multiple accounts match this sign-in. Contact an administrator to merge duplicate accounts.';
		}
		if ($error_code === self::ERROR_IDENTITY_CONFLICT) {
			return 'This Azure sign-in could not be linked to your account. Contact an administrator.';
		}
		return 'Sign-in failed.';
	}

	public function normalize_email($email)
	{
		return strtolower(trim((string) $email));
	}

	protected function extract_oid_from_claims($claims)
	{
		return $this->extract_profile_from_claims($claims)['oid'];
	}

	protected function success_result($user, $matched_by, $oid)
	{
		return array(
			'status' => 'found',
			'user' => $user,
			'matched_by' => $matched_by,
			'oid' => $oid,
			'error' => null,
		);
	}

	protected function error_result($code, $oid)
	{
		return array(
			'status' => 'error',
			'user' => null,
			'matched_by' => null,
			'oid' => $oid,
			'error' => $code,
		);
	}

	protected function resolve_by_email_rules($email, $oid, $azure_context)
	{
		$user = $this->ci->Ion_auth_model->get_user_by_email_normalized($email);
		if ($user) {
			if ($azure_context && $oid !== '') {
				if ($this->check_azure_identity_conflict($user, $oid)) {
					return $this->error_result(self::ERROR_IDENTITY_CONFLICT, $oid);
				}
			}
			return $this->success_result($user, 'email', $oid);
		}

		$equiv = $this->get_domain_equivalence_config();
		if (empty($equiv['enabled']) || empty($equiv['local_part_cross_domain'])) {
			return array(
				'status' => 'not_found',
				'user' => null,
				'matched_by' => null,
				'oid' => $oid,
				'error' => null,
			);
		}

		$parts = $this->parse_email_parts($email);
		if (!$parts || !in_array($parts['domain'], $equiv['domains'], true)) {
			return array(
				'status' => 'not_found',
				'user' => null,
				'matched_by' => null,
				'oid' => $oid,
				'error' => null,
			);
		}

		$candidates = $this->ci->Ion_auth_model->find_users_by_local_part_in_domains(
			$parts['local_part'],
			$equiv['domains']
		);

		if (count($candidates) === 0) {
			return array(
				'status' => 'not_found',
				'user' => null,
				'matched_by' => null,
				'oid' => $oid,
				'error' => null,
			);
		}

		if (count($candidates) > 1 && !empty($equiv['require_unique_local_part'])) {
			log_message('error', 'Azure SSO resolve: ambiguous local_part "' . $parts['local_part'] . '"');
			return $this->error_result(self::ERROR_AMBIGUOUS_LOCAL_PART, $oid);
		}

		$user = $candidates[0];
		if ($azure_context && $oid !== '') {
			if ($this->check_azure_identity_conflict($user, $oid)) {
				return $this->error_result(self::ERROR_IDENTITY_CONFLICT, $oid);
			}
		}

		return $this->success_result($user, 'local_part', $oid);
	}

	protected function get_domain_equivalence_config()
	{
		$cfg = $this->ci->config->item('email_domain_equivalence');
		if (!is_array($cfg)) {
			$cfg = array();
		}

		$domains = isset($cfg['domains']) && is_array($cfg['domains']) ? $cfg['domains'] : array();
		$normalized_domains = array();
		foreach ($domains as $d) {
			$d = strtolower(trim((string) $d));
			if ($d !== '') {
				$normalized_domains[] = $d;
			}
		}

		return array(
			'enabled' => !empty($cfg['enabled']),
			'domains' => array_values(array_unique($normalized_domains)),
			'local_part_cross_domain' => !isset($cfg['local_part_cross_domain']) || $cfg['local_part_cross_domain'],
			'require_unique_local_part' => !isset($cfg['require_unique_local_part']) || $cfg['require_unique_local_part'],
		);
	}

	protected function parse_email_parts($email)
	{
		$email = $this->normalize_email($email);
		$pos = strrpos($email, '@');
		if ($pos === false || $pos === 0 || $pos === strlen($email) - 1) {
			return null;
		}
		return array(
			'local_part' => substr($email, 0, $pos),
			'domain' => substr($email, $pos + 1),
		);
	}

	protected function user_azure_identity_is_empty($user)
	{
		return empty($user->authtype_id);
	}

	protected function check_azure_identity_conflict($user, $oid)
	{
		if ($this->user_azure_identity_is_empty($user)) {
			return false;
		}
		return (string) $user->authtype_id !== (string) $oid;
	}

	protected function email_owned_by_other_user($email, $user_id)
	{
		$other = $this->ci->Ion_auth_model->get_user_by_email_normalized($email);
		return $other && (int) $other->id !== (int) $user_id;
	}
}
