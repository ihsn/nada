<?php
class Data_file_model extends CI_Model {

    private $db_fields=array(
		'id',
		'sid',
		'file_id',
		'file_name',
		'description', 
		'case_count',
		'var_count',
		'producer',
		'data_checks',
		'missing_data',
		'version',
		'notes'
	);

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	//get data file by id
    function select_single($id)
    {
        $this->db->select("*");
        $this->db->where("id",$id);
        return $this->db->get("data_files")->row_array();
	}

	//get an array of all file IDs e.g. F1, F2, ...
    function list_fileid($sid)
    {
        $this->db->select("file_id");
        $this->db->where("sid",$sid);
		$result=$this->db->get("data_files")->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[]=$row['file_id'];
		}

		return $output;
	}
	

    //get data file by file_id
    function get_file_by_id($sid,$file_id)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        $this->db->where("file_id",$file_id);
        return $this->db->get("data_files")->row_array();
	}


	//get fid by file_id
	function get_fid_by_fileid($sid,$file_id)
	{
		$file=$this->get_file_by_id($sid,$file_id);
		
		if($file){
			return $file['id'];
		}

		return false;
	}

	


	//get datafile id by VAR-ID
    function get_fid_by_varid($sid,$vid)
    {
        $this->db->select("fid");
        $this->db->where("sid",$sid);
        $this->db->where("vid",$vid);
		$result= $this->db->get("variables")->row_array();
		
		if($result){
			return $result['fid'];
		}

		return false;
	}


	//get datafile info by VAR-ID
    function get_file_by_varid($sid,$vid)
    {
		$file_id=$this->get_fid_by_varid($sid,$vid);		
		return $this->get_file_by_id($sid,$file_id);
	}


	//get file_id by varid - F1283
    function get_fileid_by_varid($sid,$vid)
    {
		throw new exception("deprecated::use Data_file_model->get_fid_varid");
		$this->db->select("file_id");
		$this->db->join('data_files','data_files.id=variables.fid');
        $this->db->where("variables.sid",$sid);
        $this->db->where("variables.vid",$vid);
		$result= $this->db->get("variables")->row_array();
		
		if($result){
			return $result['file_id'];
		}

		return false;
	}
	

    //returns files by survey
    function get_all_by_survey($sid)
    {
        $this->db->select("*");
		$this->db->where("sid",$sid);
		$this->db->order_by('file_name');
		$files=$this->db->get("data_files")->result_array();

		if(empty($files)){
			return false;
		}
		
		//add file_id as key
		$output=array();
		foreach($files as $file){
			$output[$file['file_id']]=$file;
		}

		//apply sorting to keep files in the order - F1, F2...F9, F10, F11
		$file_keys = array_keys($output);
  		natsort($file_keys);

		$sorted_files=array();

  		foreach ($file_keys as $key_){
			$sorted_files[$key_] = $output[$key_];
		}

  		return $sorted_files;
	}
	
	

	/**
	*
	* insert new file and return the new file id
	*
	* @options - array()
	*/
	function insert($sid,$options)
	{		
		$data=array();
		//$data['created']=date("U");
		//$data['changed']=date("U");
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$this->db_fields) )
			{
				$data[$key]=$value;
			}
		}

		$data['sid']=$sid;
		
		$result=$this->db->insert('data_files', $data);

		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}
		
		return $this->db->insert_id();
	}
	
	
	/**
	*
	* update file
	*
	* @options - array()
	*/
	function update($id,$options)
	{
		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$this->db_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		$this->db->where('id',$id);
		$result=$this->db->update('data_files', $data);

		if ($result===false)
		{
			throw new MY_Exception($this->db->_error_message());
		}
		
		return TRUE;
	}
	
	
	/**
	* Delete single file
	*
	*
	*/
	function delete($id)
	{
		$this->db->where('id', $id); 
		$deleted=$this->db->delete('data_files');
		
		/*if ($deleted)
		{
			//remove variables
			$this->db->where('file_id', $id);
			$this->db->delete('variables');
		}*/
	}
	

	/**
	 * 
	 * 
	 * Validate data file
	 * @options - array of fields
	 * @is_new - boolean - for new records
	 * 
	 **/
	function validate_data_file($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for a new record
		if($is_new){				
			#$this->form_validation->set_rules('surveyid', 'IDNO', 'xss_clean|trim|max_length[255]|required');
			//$this->form_validation->set_rules('file_id', 'File ID', 'required|xss_clean|trim|max_length[50]');	
			$this->form_validation->set_rules('file_name', 'File name', 'required|xss_clean|trim|max_length[200]');	
			$this->form_validation->set_rules('case_count', 'Case count', 'xss_clean|trim|max_length[10]');	
			$this->form_validation->set_rules('var_count', 'Variable count', 'xss_clean|trim|max_length[10]');	

			
			//file id
			$this->form_validation->set_rules(
				'file_id', 
				'File ID',
				array(
					"required",
					"max_length[50]",
					"trim",
					"alpha_dash",
					"xss_clean",
					array('validate_file_id',array($this, 'validate_file_id')),				
				)		
			);

		}
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}

	//validate data file ID
	public function validate_file_id($file_id)
	{	
		$sid=null;
		if(array_key_exists('sid',$this->form_validation->validation_data)){
			$sid=$this->form_validation->validation_data['sid'];
		}

		//list of all existing FileIDs
		$files=$this->list_fileid($sid);

		if(in_array($file_id,$files)){
			$this->form_validation->set_message(__FUNCTION__, 'FILE_ID already exists. The FILE_ID should be unique.' );
			return false;
		}

		return true;
	}
}
	
