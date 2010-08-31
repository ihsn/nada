<?php
class MY_Controller extends Controller
{	
	
	var $is_admin=TRUE;
	
	/**
	* Manages both admin/non-admin users
	*
	* @skip			skip authentication  (true=skip authentication)
	* @is_admin		requires the user to have admin rights
	*/
  public function MY_Controller($skip=FALSE,$is_admin=TRUE)
  {		
	    parent::Controller();

      //switch language
	    $this->_switch_language();
	    $this->lang->load("general");
	
      $this->load->library(array('site_configurations','ion_auth','session','form_validation'));
      //$this->load->database();
      //$this->load->helper('url');

	    $this->is_admin=$is_admin;
  		//load default site menu
  		//$this->load->model('Menu_model');       	
  		//$this->template->write('sidebar', $this->_menu(),true);
  		
  		if ($skip===FALSE)
      {
  		   //apply IP restrictions for site administration
  	 	   $this->apply_ip_restrictions();
      }
      
  		//skip authentication
      if ($skip!==TRUE)
  		{
  			$this->_auth();
  		}
  }

/**
 * 
 * Apply IP restrictions for Site Admin
 * 
 */
 public function apply_ip_restrictions()
 {
    $user_ip=$this->input->ip_address();  
    
    $ip_list=$this->config->item("admin_allowed_ip");
    
    if ($ip_list!==FALSE)
    {
      if (is_array($ip_list) && count($ip_list)>0)
      {
          //check ip is in the allowed list  
          if (!in_array($user_ip, $ip_list))
          {
             //log
             $this->db_logger->write_log('blocked','site access blocked from ip:'.$user_ip,'access-blocked');
             
             //show page not found  
             show_404(); 
          }  
      }     
    } 
 }
    
	/**
	* Switch site language using cookies
	*
	**/
	function _switch_language()
	{
		if($this->session->userdata('language'))
		{	
	        //switch language
			$this->config->set_item('language',$this->session->userdata('language'));
		}
	}
	
	function _auth()
	{
		$destination=$this->uri->uri_string();
		$this->session->set_userdata("destination",$destination);

    	if (!$this->ion_auth->logged_in()) 
		{
	    	//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
    	}
    	elseif (!$this->ion_auth->is_admin() && $this->is_admin==TRUE ) 
		{
    		//redirect them to the home page because they must be an administrator to view this
			//redirect($this->config->item('base_url'), 'refresh');
			redirect("auth/login/?destination=$destination", 'refresh');
    	}
	}

	//get public site menu
	function _menu()
	{
		$data['menus']= $this->Menu_model->select_all();		
		$content=$this->load->view('default_menu', $data,true);
		return $content;
	}
	
}	