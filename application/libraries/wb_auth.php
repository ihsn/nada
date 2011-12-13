<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  WB Passkey Auth
* 
* Author: Mehmood Asghar
* Created:  12.01.2011 
* 
* Description:  Function to login user using World Bank's SecurID authentication
* 
*/
 
class WB_auth
{
	protected $ci;

	function __construct()
	{
		log_message('debug', "WB-AUTH class initialized");
		$this->ci =& get_instance();
		$this->ci->conig->load("ion_auth");
		$this->tables  = $this->ci->config->item('tables');
	}

	public function passkey_login($email,$upi)
	{
	    if (empty($email) || empty($upi) )
	    {
	        return FALSE;
	    }

	    $query = $this->ci->db->select('username,email, id, password, group_id')
						  ->where($this->identity_column, $email)
						  ->where($this->ion_auth->_extra_where)
						  ->where('active', 1)
						  //->limit(1)
						  ->get($this->tables['users']);
						  
        $result = $query->row();

        if ($query->num_rows() == 1)
        {
        		$this->update_last_login($result->id);
    		    $this->ci->session->set_userdata('email',  $result->email);
				$this->ci->session->set_userdata('username',  $result->username);
    		    $this->ci->session->set_userdata('user_id',  $result->id); //everyone likes to overwrite id so we'll use user_id
    		    $this->ci->session->set_userdata('group_id',  $result->group_id);
				$this->ci->session->set_userdata('site_user_roles', $this->ci->Ion_auth_model->get_user_site_roles($result->id));//array of user roles per site
    		    
    		    $group_row = $this->ci->db->select('name')->where('id', $result->group_id)->get($this->tables['groups'])->row();

    		    $this->ci->session->set_userdata('group',  $group_row->name);
   		    	$this->remember_user($result->id);
    		    return TRUE;
        }
        
		return FALSE;		
	}
}
