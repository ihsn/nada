<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Oauth2
{
	protected $ci;

	function __construct()
	{
		log_message('debug', "Oauth2 class initialized");
		$this->ci =& get_instance();
		$this->ci->config->load("ion_auth");
		$this->tables  = $this->ci->config->item('tables');
		$this->ci->load->model("Ion_auth_model");
	}

	function login_user($email)
	{
	    if (empty($email) ){
	        return FALSE;
	    }

        $user=$this->ci->ion_auth->get_user_by_email($email);

        if (empty($user)){
            $this->ci->session->set_flashdata('error', t("Login failed. Please check your email and password."));
            redirect("auth/login");
        }

        //check if the user is active
        if ($user->active == 0){
            $this->ci->session->set_flashdata('error', t("user_email_not_verified"));
			$this->ci->session->set_userdata('verify_email',$email);
            redirect("auth/verify");
        }

        $this->update_last_login($user->id);
        $this->ci->session->set_userdata('email',  $user->email);
        $this->ci->session->set_userdata('username',  $user->username);
        $this->ci->session->set_userdata('user_id',  $user->id);
        return TRUE;
	}
	
	
	/**
	 * update_last_login
	 *
	 **/
	public function update_last_login($id)
	{
		$this->ci->load->helper('date');

		if (isset($this->ci->ion_auth) && isset($this->ci->ion_auth->_extra_where)){
			$this->ci->db->where($this->ci->ion_auth->_extra_where);
        }
        
		$this->ci->db->update($this->tables['users'], array('last_login' => now()), array('id' => $id));		
		return $this->ci->db->affected_rows() == 1;
    }
    

    

}