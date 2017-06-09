<?php
class Dashboard_model extends CI_Model {
 
    public function __construct()
    {	
        parent::__construct();		
		$this->load->config('ion_auth');
		$this->tables  = $this->config->item('tables');
		
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	*
	* User statistics
	* 
	*
	**/
	function get_user_stats(){
	/*
		0	ACTIVE
		9	DISABLED
		3	PENDING
		4	Currently Logged in

		#active
		select * from users where active=1;
		
		#disabled
		select * from users where active=0 and created_on != last_login;
		
		#inactive
		select * from users where active=0 and created_on = last_login;
	*/
		
		//active users
		$this->db->where('active', 1);
		$this->db->from($this->tables['users']);
		$result['active']=$this->db->count_all_results();

		//disabled
		$this->db->where('active', 0);
		$this->db->where('created_on != last_login',NULL,FALSE);
		$this->db->from($this->tables['users']);
		$result['disabled']=$this->db->count_all_results();

		//inactive (never logged in)
		$this->db->where('active', 0);
		$this->db->where('created_on = last_login',NULL,FALSE);
		$this->db->from($this->tables['users']);
		$result['inactive']=$this->db->count_all_results();

		//calc date - n minutes
		$start_date=date("U")-(60*60);//minus 1 hour
		
		//get anonymous user sessions from db
		$this->db->where('last_activity > ',$start_date,FALSE);
		$this->db->from('ci_sessions');
		$result['anonymous_users']=$this->db->count_all_results();

		//get logged in users within last n minutes
		$this->db->select("username");
		$this->db->where('last_login >= ',$start_date,FALSE);
		$active_users=$this->db->get("users")->result_array();
		
		$users=array();
		foreach($active_users as $user)
		{
			$users[]=$user['username'];
		}
		
		$result['loggedin_users']=$users;

		//remove loggedin users from anonymous users count		
		$result['anonymous_users']=$result['anonymous_users'] - count($result['loggedin_users']);

		return $result;
	}
	
	function select_all($sort_by='weight', $sort_order='ASC')
	{
		$this->db->select('id,url,title,target,linktype');	
		$this->db->order_by($sort_by, $sort_order);
		$this->db->where('published', 1); 
		return $this->db->get('menus')->result_array();
	}
	
	/**
	* return number of times email messages were not sent in the last 5 days
	*
	**/
	function get_failed_email_count()
	{
		$start_date=date("U")-(60*60*48);//48 hours
		$this->db->select('count(*) as total');
		$this->db->where('logtype','email-failed');
		$this->db->where('logtime >=',$start_date);
		$query=$this->db->get('sitelogs')->row_array();
		
		return $query['total'];		
	}
	
	function get_sitelog_count()
	{
		$query=$this->db->query('select count(*) as total from sitelogs')->row_array();
		return $query['total'];
	}

}
?>