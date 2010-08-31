<?php

/**
 * User Class
 *
 * Transforms users table into an object.
 * This is just here for use with the example in the Controllers.
 *
 * @license		MIT License
 * @category	Models
 * @author		Phil DeJarnett
 * @link		http://www.overzealous.com/dmz/
 */
class User extends DataMapper {
	
	// Default to ordering by name
	var $default_order_by = array('username');
	var $local_time = TRUE;
	var $unix_timestamp = TRUE;
	
	
	var $validation = array(
		'username' => array(
			'label' => 'Username',
			'rules' => array('required', 'trim', 'unique',  'min_length' => 3, 'max_length' => 20)
		),
		'email' => array(
			'label' => 'Email',
			'rules' => array('required', 'trim', 'unique', 'valid_email')
		),
		'password' => array(
			'label' => 'Password',
			'rules' => array( 'trim', 'min_length' => 3, 'max_length' => 40, 'encrypt'),
			'type' => 'password'
		),
		'confirm_password' => array(
			'label' => 'Confirm Password',
			'rules' => array( 'encrypt', 'matches' => 'password', 'min_length' => 3, 'max_length' => 40),
			'type' => 'password'
		)
	);
	/**
	 * Encrypt (prep)
	 *
	 * Encrypts this objects password with a random salt.
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _encrypt($field)
	{
		if (!empty($this->{$field}))
		{
			if (empty($this->salt))
			{
				$this->salt = md5(uniqid(rand(), true));
			}

			$this->{$field} = sha1($this->salt . $this->{$field});
		}
	}

	/*
	function __toString()
	{
		return empty($this->name) ? '«New User»' : $this->name;
	}*/
	
}

/* End of file user.php */
/* Location: ./application/models/user.php */