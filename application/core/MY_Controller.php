<?php
class MY_Controller extends CI_Controller
{	
    public $_ci_plugins = array();
    var $_ci_ob_level;
    public $_ci_view_path      = '';
    var $_ci_library_paths  = array();
    var $_ci_model_paths    = array();
    var $_ci_helper_paths   = array();
    var $_base_classes      = array(); // Set by the controller class
    var $_ci_cached_vars    = array();
    var $_ci_classes        = array();
    var $_ci_loaded_files   = array();
    var $_ci_models         = array();
    var $_ci_helpers        = array();
    var $_ci_varmap         = array('unit_test' => 'unit', 'user_agent' => 'agent');
	
	var $is_admin=TRUE;

	/**
	 * Legacy UI context (repository row or central stub). Controllers that need it set this explicitly
	 * (e.g. Catalog, Citations, Licensed_requests, catalog Vue entrypoints). Not auto-populated.
	 *
	 * @var object|null
	 */
	public $active_repo = NULL;

	/**
	* Manages both admin/non-admin users
	*
	* @skip			skip authentication  (true=skip authentication)
	* @is_admin		requires the user to have admin rights
	*/
	public function __construct($skip=FALSE,$is_admin=TRUE)
	{		
		parent::__construct();
		
		//test if application is installed
		$this->is_app_installed();

		$this->site_maintenance();
	
		//switch language
		$this->_switch_language();
		$this->lang->load("general");
		$this->load->model('Permissions_model');
			
		$this->load->library(array('site_configurations','session','ion_auth','form_validation','acl_manager','CSP_Library'));	
		$this->is_admin=$is_admin;
		
		// Apply Content Security Policy headers
		$this->csp_library->apply_headers();
		
		//require authentication for protected pages e.g. admin	
		if ($skip===FALSE){
		   
		   //apply IP restrictions for site administration
		   $this->apply_ip_restrictions();
		   
		   //apply server host name restrictions for site administration
		   $this->apply_hostname_restrictions();
		   
			//check user is logged in or not
			$this->_auth();
			
			//get user object with all user info
			$user=$this->ion_auth->current_user();
		
			if (!$user){
				return FALSE;
			}
			
			//check user has access to the url
			/*if (!$this->acl->user_has_url_access() ){
				show_error(t('ACCESS_DENIED'));
			}*/
			
			if ($this->config->item("otp_verification")===1){
				if ($this->session->userdata('verify_otp')!==1){
					//otp expired or not set?
					if (date("U")>$user->otp_expiry || !$user->otp_code){
						$this->ion_auth->send_otp_code($user->id);
					}
					redirect('auth/verify_code');
				}
			}
		}
	}

	

	/**
	 * 
	 * Show offline message during maintenance
	 * 
	 * 
	 */
	function site_maintenance()
	{
		$offline=$this->config->item("maintenance_mode");
		
		if($offline!==1){
			return true;
		}

		$allowed_urls=array('auth');

		if ($this->ion_auth->logged_in() 
			//&& $this->ion_auth->is_admin()
		){
			return true;
		}

		if (in_array($this->uri->segment(1), $allowed_urls)){
			return true;
		}

		echo $this->load->view('static/offline',null,true);
		die();
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
	 * 
	 * Restrict access to site administration based on 
	 * HOSTNAME used for accessing the site
	 * 
	 */
	 public function apply_hostname_restrictions()
	 {	 	
		$http_host=$this->input->server("HTTP_HOST");
		$allowed_hosts=$this->config->item("admin_allowed_hosts");
		
		$http_host=explode(":",$http_host);
		$http_host=$http_host[0];
		
		  if (is_array($allowed_hosts) && count($allowed_hosts)>0)
		  {
			  //check host is in the allowed list  
			  if (!in_array($http_host, $allowed_hosts))
			  {
				 //log
				 $this->db_logger->write_log('blocked',sprintf('site access blocked from ip [%s], using host [%s]',$this->input->ip_address(),$http_host),'host-access-blocked');
				 
				 //show page not found  
				 show_404(); 
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
	
	/**
	*
	*
	* check if user is logged in or not
	**/
	function _auth()
	{
		$destination=$this->uri->uri_string();
		
		//check if ajax is set
		if ($this->input->get_post("ajax"))
		{
			$destination.='/?ajax='.$this->input->get_post("ajax");
		}
		//check if print is set
		if ($this->input->get_post("print"))
		{
			$destination.='/?print='.$this->input->get_post("print");
		}
				
		$this->session->set_userdata("destination",$destination);

		//not logged in
    	if (!$this->ion_auth->logged_in()) 
		{
			//check ajax requests
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
			{
				header('HTTP/1.0 401 Unauthorized');
				exit;
			}			
		
			//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
    	}
		elseif (!$this->ion_auth->can_access_site_admin() && $this->is_admin==TRUE ) 
		{
			$this->acl_manager->show_access_denied('shell');
    	}
	}

	//get public site menu
	function _menu()
	{
		$this->load->helper('menu');
		$data['menus']= $this->Menu_model->get_published_menu_tree();
		$content=$this->load->view('default_menu', $data,true);
		return $content;
	}
	
 	/**
	* Test if app is properly installed and can connect to db.
	* Uses a lock file in CATALOG_ROOT as a fast-path check to avoid a DB query
	* on every request. DB is the authoritative source of truth; lock file is
	* a performance optimisation only. Falls back to DB if lock file is absent.
	*/
 	function is_app_installed()
	{
		$this->load->database();

		if ($this->db->dbdriver=='' || $this->db->username=='' || $this->db->database=='')
		{
			show_error('You have not setup a database');
		}

		// Fast path: lock file present means app is installed — skip DB query
		$lock_file = $this->_get_install_lock_path();
		if ($lock_file && file_exists($lock_file))
		{
			return TRUE;
		}

		// Lock file missing — check DB for app_installed row
		$this->db->select('name');
		$this->db->where('name', 'app_installed');
		$query = $this->db->get('configurations');

		if ($query && $query->num_rows() > 0)
		{
			// Installed — recreate lock file for subsequent requests (migration path)
			if ($lock_file)
			{
				@file_put_contents($lock_file, date("U"));
			}
			return TRUE;
		}

		// configurations table missing or app_installed row absent — test raw DB connection
		// to distinguish "not installed" from "DB unreachable"
		$conn_id = FALSE;
		switch ($this->db->dbdriver)
		{
			case 'mysqli':
				$conn_id = @mysqli_connect($this->db->hostname, $this->db->username, $this->db->password);
				break;
			case 'sqlsrv':
				$auth_info = array('UID' => $this->db->username, 'PWD' => $this->db->password);
				$conn_id = @sqlsrv_connect($this->db->hostname, $auth_info);
				break;
			default:
				show_error('MY_CONTROLLER::database not supported '.$this->db->dbdriver);
		}

		if (!$conn_id)
		{
			show_error('Failed to connect to database, check database settings');
		}
		else
		{
			// Can reach DB server but app is not installed
			redirect('install');
		}
	}


	/**
	 * Returns the absolute path to the install lock file, or FALSE if
	 * catalog_root is not configured.
	 */
	function _get_install_lock_path()
	{
		$catalog_root = $this->config->item('catalog_root');
		if (empty($catalog_root))
		{
			return FALSE;
		}

		// Resolve to absolute path if relative (no leading / and no Windows drive letter)
		if ($catalog_root[0] !== '/' && strpos($catalog_root, ':') === FALSE)
		{
			$catalog_root = FCPATH . $catalog_root;
		}

		return rtrim($catalog_root, '/') . '/.nada_installed';
	}


}
