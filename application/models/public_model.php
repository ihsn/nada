<?php
class Public_model extends Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns single request info by request ID
	*
	*/
	function select_single($request_id)
	{
		$this->db->select('p.*,s.titl,s.surveyid,s.proddate, s.nation, u.username,u.email,m.first_name, m.last_name,m.company, m.phone,m.country',FALSE);
		$this->db->join('surveys s', 's.id = p.surveyid');
		$this->db->join('users u', 'u.id = p.userid','left');
		$this->db->join('meta m', 'u.id = m.user_id','left');
		$this->db->from('public_requests p');
		$this->db->where('p.id',$request_id);
		
		$result=$this->db->get()->row_array();		
		
		if ($result)
		{
			return $result;
		}
		
		return FALSE;
	}
	

}
?>