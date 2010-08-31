<?php
class Dashboard_model extends Model {
 
    public function __construct()
    {
        parent::__construct();
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
		$this->db->from('users');
		$result['active']=$this->db->count_all_results();

		//disabled
		$this->db->where('active', 0);
		$this->db->where('created_on != last_login',NULL,FALSE);
		$this->db->from('users');
		$result['disabled']=$this->db->count_all_results();

		//inactive (never logged in)
		$this->db->where('active', 0);
		$this->db->where('created_on = last_login',NULL,FALSE);
		$this->db->from('users');
		$result['inactive']=$this->db->count_all_results();

		return $result;
	}
	
	function select_all($sort_by='weight', $sort_order='ASC')
	{
		$this->db->select('id,url,title,target,linktype');	
		$this->db->order_by($sort_by, $sort_order);
		$this->db->where('published', 1); 
		return $this->db->get('menus')->result_array();
	}

}
?>