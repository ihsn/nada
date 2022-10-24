<?php
class Data_access_whitelist_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		$this->load->model("Repository_model");
    }


	function select_all()
	{
		$this->db->select("data_access_whitelist.id,repositories.id as collection_id,repositories.repositoryid as collection_name, users.id as user_id,users.email, users.username");
		$this->db->join('repositories', 'repositories.id= data_access_whitelist.repository_id');
		$this->db->join('users', 'users.id= data_access_whitelist.user_id');
		$result=$this->db->get('data_access_whitelist')->result_array();

		return $result;
	}
	
	function has_access($userid,$sid)
	{
		//get repository id from study
		$this->db->select("repositoryid");
		$this->db->where("id",$sid);
		$study=$this->db->get("surveys")->row_array();

		if(!$study){
			return false;
		}

		$repoid=$study['repositoryid'];
		$repository_uid=$this->Repository_model->get_repositoryid_uid($repoid);

		//check 
		$this->db->select("*");
		$this->db->where("user_id",$userid);
		$this->db->where("repository_id",$repository_uid);
		$result=$this->db->get("data_access_whitelist")->row_array();

		if(!$result){
			return false;
		}

		return true;
	}
	

	function get_data_files($sid)
	{
		$this->load->model('Survey_resource_model');
		$result['resources_microdata']=$this->Survey_resource_model->get_microdata_resources($sid);
		$result['sid']=$sid;
		$result['storage_path']=$this->Dataset_model->get_storage_fullpath($sid);
		return $this->load->view('catalog_search/survey_summary_microdata', $result,TRUE);
	}


	function insert($repository_id, $user_id)
	{	
		if ($this->row_exists($repository_id,$user_id)){
			throw new Exception("User is already whitelisted");
		}
		
		$data=array(
			'user_id'=>$user_id,
			'repository_id'=>$repository_id
		);
		
		$result=$this->db->insert('data_access_whitelist', $data);

		if ($result===false){
			throw new MY_Exception($this->db->_error_message());
		}
		
		return $this->db->insert_id();
	}

	function row_exists($repository_id,$user_id)
	{
		$this->db->select("*");
		$this->db->where('repository_id', $repository_id); 
		$this->db->where('user_id', $user_id); 
		$result=$this->db->get("data_access_whitelist")->result_array(); 

		if ($result)
		{
			return true;
		}

		return false;
	}


	function delete($repository_id,$user_id)
	{
		$this->db->where('repository_id', $repository_id); 
		$this->db->where('user_id', $user_id); 
		return $this->db->delete('data_access_whitelist');
	}

	function delete_by_id($id)
	{
		$this->db->where('id', $id); 
		return $this->db->delete('data_access_whitelist');
	}

	
	function get_repo_id($collection_name){
		return $this->Repository_model->get_repositoryid_uid($collection_name);
	}

	function get_user_id($email)
	{
		$this->db->select("id");
		$this->db->where("email",$email);
		$result=$this->db->get("users")->row_array();

		if ($result){
			return $result['id'];
		}

		return false;
	}

}
