<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Password Hashing wrapper class
 *
 * New hashes are bcrypt at a fixed cost. Older phpass hashes ($P$ / $H$)
 * still verify, and the caller replaces them after a successful check.
 *
 * @subpackage	Libraries
 *
 */
class Password_hasher{

	const BCRYPT_COST = 12;
	const MAX_PASSWORD_BYTES = 72;

	private $legacy_hasher=NULL;

	function __construct($params=NULL)
	{
		require_once APPPATH."libraries/PasswordHash.php";
		$this->legacy_hasher = new PasswordHash(8, TRUE);
	}

	//create a hashed password
	public function hash_password($password)
	{
		if (!$this->acceptable_password($password))
		{
			return FALSE;
		}

		return password_hash($password, PASSWORD_BCRYPT, array('cost' => self::BCRYPT_COST));
	}

	//check if password matches the hash
	public function check_password($password,$hash)
	{
		if (!$this->acceptable_password($password) || !is_string($hash) || $hash === '')
		{
			return FALSE;
		}

		if (password_verify($password, $hash))
		{
			return TRUE;
		}

		if ($this->is_legacy_phpass($hash))
		{
			return $this->legacy_hasher->CheckPassword($password, $hash);
		}

		return FALSE;
	}

	/**
	 * True when a verified hash should be stored again with the current algorithm.
	 *
	 * @param string $hash
	 * @return bool
	 */
	public function needs_rehash($hash)
	{
		if (!is_string($hash) || $hash === '')
		{
			return FALSE;
		}

		if ($this->is_legacy_phpass($hash))
		{
			return TRUE;
		}

		return password_needs_rehash($hash, PASSWORD_BCRYPT, array('cost' => self::BCRYPT_COST));
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	private function acceptable_password($password)
	{
		return is_string($password) && $password !== '' && strlen($password) <= self::MAX_PASSWORD_BYTES;
	}

	/**
	 * @param string $hash
	 * @return bool
	 */
	private function is_legacy_phpass($hash)
	{
		return strncmp($hash, '$P$', 3) === 0 || strncmp($hash, '$H$', 3) === 0;
	}

}
// END password_hasher Class

/* End of file password_hasher.php */
/* Location: ./application/libraries/password_hasher.php */
