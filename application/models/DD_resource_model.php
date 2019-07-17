<?php
/**
* External resources for surveys
*
**/
class DD_resource_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array('dctype','title','author', 'dcdate','country', 'language', 'contributor','publisher','toc', 'abstract', 'filename','dcformat','description');
	
	//surveyid of the survey to show external resources
	var $surveyid;
	
			
    public function __construct()
    {
        // model constructor
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
		
	/**
	* searche database
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL)
    {
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		$this->db->start_cache();		
		
		//select survey fields
		$this->db->select('*');
		
		//build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	//$this->db->or_like($field,$where['keywords']);
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND survey_id='.$this->surveyid;
		}
		else
		{
			$where_clause='survey_id='.$this->db->escape($this->surveyid);
		}
		
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause, NULL, FALSE);

		//set order by
		if ($sort_by!='' && $sort_order!=''){
			$this->db->order_by($sort_by, $sort_order); 
		}
		
	  	$this->db->limit($limit, $offset);
		$this->db->from('resources');
		$this->db->stop_cache();

        $result= $this->db->get()->result_array();		
		return $result;
    }
	
	//builds where clause using the variables from GET
	function _build_search_query()
	{		
		$fields=$this->input->get("field");
		$keywords=$this->input->get("keywords");
		
		$allowed_fields=$this->allowed_fields;
		
		if ($keywords=='')
		{
			return NULL;
		}
		
		if ($fields=='')
		{
			return NULL;
		}
		else if ($fields=='all')
		{			
			$where['field']=$allowed_fields;
			$where['keywords']=$keywords;
			
			return $where;
		}
		else if (in_array($fields, $allowed_fields) )
		{
			$where['field']=array($fields);
			$where['keywords']=$keywords;
			
			return $where;
		}
		
		return NULL;
	}

	//returns the search result count  	
    function search_count()
    {
        //build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	//$this->db->or_like($field,$where['keywords']);
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND survey_id='.$this->surveyid;
		}
		else
		{
			$where_clause='survey_id='.$this->db->escape($this->surveyid);
		}
		//print $where_clause;
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause,NULL,FALSE);
		$result=$this->db->count_all_results('resources');
		return $result;
    }


	function insert_project_resource($pid,$data) 
	{		
			$allowed_fields=array('project_id','title','author','created','description','filename','filesize','dctype','dcformat');
			
			$options=array();
			foreach($data as $key=>$value)
			{
				if(in_array($key,$allowed_fields))
				{
					$options[$key]=$value;
				}	
			}
	
			//check resource already exists for the project
			$resource_id=$this->resource_exists($pid,$data['filename']);
			if (!$resource_id)
			{
				//insert new
				return $this->db->insert('dd_project_resources', $options);
			}
			else //update resource
			{
				$options['description']='found andu dpate';
				return $this->update_project_resource($resource_id,$options);
			}	
	}
	
	
	//check if a resource already exists for a project
	function resource_exists($pid,$filename)
	{
		$this->db->where('project_id',$pid);
		$this->db->where('filename',$filename);
		$this->db->select('id');
		$this->db->limit(1);
		$query=$this->db->get('dd_project_resources')->row_array();
		
		if ($query)
		{
			return $query['id'];
		}
		
		return FALSE;
	}
	
	function update_project_resource($id, $data) 
	{
		return $this->db->where('id', $id)
				->update('dd_project_resources', $data);
	}
	
	function get_project_resources_to_array($pid) 
	{
		$result = $this->get_project_resources($pid);
		$list=array();
		foreach($result as $row)
		{
			$list[] = $row;
		}
		return $list;
	}
	
	function delete_project_resource($fid) {
		$this->db->delete('dd_project_resources', array('id' => $fid));
		$file = $this->get_project_resource($fid);
	}
	
	function get_project_resources($pid) {
		$q= $this->db->select('*')
			->from('dd_project_resources')
			->where('project_id', $pid);
		return $q->get()->result_array();
	}
	
	function get_project_resource($id) {
		$q = $this->db->select('*')
			->from('dd_project_resources')
			->where('id', $id);
	
		return $q->get()->result();
	}
	

	/**
	* returns resource filenames by survey id
	*
	*
	**/
	function get_survey_resource_files($surveyid){
		$this->db->select("resource_id,filename,title");
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	* returns a single row
	*
	*
	**/
	function select_single($id){
		$this->db->where('resource_id', $id); 
		return $this->db->get('resources')->row_array();
	}

	function delete($id)
	{
		$this->db->where('resource_id', $id); 
		return $this->db->delete('resources');
	}
	
	/**
	*
	* Delete all resources by survey id
	**/
	function delete_all_survey_resources($survey_id)
	{
		$this->db->where('survey_id', $survey_id); 
		return $this->db->delete('resources');
	}
	
	/**
	* returns DC Types
	*
	*
	**/
	function get_dc_types(){
		$result= $this->db->get('dctypes')->result_array();

		$list=array();
		foreach($result as $row)
		{
			$list[$row['title']]=$row['title'];
		}
		
		return $list;
	}
	
	function get_study_types() {
		$result = $this->db->get('dd_study_type')->result_array();
		$list   = array();
		foreach ($result as $row) {
			$list[preg_replace('#\[.*?\]#', '', $row['studytype'])] = preg_replace('#\[.*?\]#', '', $row['studytype']);
		}
		return $list;
	}
	

	function get_kind_of_data() {
		$result = $this->db->get('dd_kind_of_data')->result_array();
		$list   = array();
		foreach ($result as $row) {
			$list[$row['kindofdata']] = $row['kindofdata'];
		}
		return $list;
	}

	/**
	* returns DC Formats
	*
	*
	**/
	function get_dc_formats(){
		$result= $this->db->get('dcformats')->result_array();

		$list=array();
		foreach($result as $row)
		{
			$list[$row['title']]=$row['title'];
		}
		
		return $list;
	}
	
	/**
	* overview_methods
	*
	*
	**/
	function get_overview_methods(){
		$result= $this->db->get('dd_overview_methods')->result_array();

		$list=array();
		foreach($result as $row)
		{
			$list[$row['method']]=$row['method'];
		}
		
		return $list;
	}
	
	/**
	* Returns the type ID by type-name
	*
	*/
	function get_dctype_id_by_name($type_name)
	{
		$type_arr=explode(' ', $type_name);
		
		$type=NULL;
		
		if (!$type_arr)
		{
			return 0;
		}
		
		foreach($type_arr as $str)
		{
			$str=trim($str);
			if ($str[0]=='[' && $str[strlen($str)-1]==']')
			{
				$type=$str;
			}
		}
		
		//Type not found
		if ($type==NULL)
		{
			return 0;
		}
		
		//search db
		$this->db->select('id'); 
		$this->db->like('title', $type); 
		$result= $this->db->get('dctypes')->row_array();
		
		if ($result)
		{
			return $result['id'];
		}
		else
		{
			return 0;
		}	
	}
	
	/**
	* Returns the DC Format ID by Format-name
	*
	*/
	function get_dcformat_id_by_name($type_name)
	{
		$type_arr=explode(' ', $type_name);

		if (!$type_arr)
		{
			return 0;
		}
		
		$type=NULL;
		foreach($type_arr as $str)
		{
			$str=trim($str);
			if (isset($str[0]))
			{
				if ($str[0]=='[' && $str[strlen($str)-1]==']')
				{
					$type=$str;
				}
			}
		}
		
		//Type not found
		if ($type==NULL)
		{
			return 0;
		}
		
		//search db
		$this->db->select('id'); 
		$this->db->like('title', $type); 
		$result= $this->db->get('dcformats')->row_array();
		
		if ($result)
		{
			return $result['id'];
		}
		else
		{
			return 0;
		}	
	}
	
	/**
	* update external resource
	*
	*	resource_id		int
	* 	options			array
	**/
	function update($resource_id,$options)
	{
		//allowed fields
		$valid_fields=array(
			//'resource_id',
			//'survey_id',
			'dctype',
			'title',
			'subtitle',
			'author',
			'dcdate',
			'country',
			'language',
			//'id_number',
			'contributor',
			'publisher',
			'rights',
			'description',
			'abstract',
			'toc',
			'subjects',
			'filename',
			'dcformat',
			'changed');

		//add date modified
		$options['changed']=date("U");
					
		//remove slash before the file path otherwise can't link the path to the file
		if (isset($options['filename']))
		{
			if (substr($options['filename'],0,1)=='/')
			{
				$options['filename']=substr($options['filename'],1,255);
			}
		}
		
		//pk field name
		$key_field='resource_id';
		
		$update_arr=array();

		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$update_arr[$key]=$value;
			}
		}
		
		//update db
		$this->db->where($key_field, $resource_id);
		$result=$this->db->update('resources', $update_arr); 
		
		return $result;		
	}
	
	
	/**
	* add external resource
	*
	*	resource_id		int
	* 	options			array
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			//'resource_id',
			'survey_id',
			'dctype',
			'title',
			'subtitle',
			'author',
			'dcdate',
			'country',
			'language',
			//'id_number',
			'contributor',
			'publisher',
			'rights',
			'description',
			'abstract',
			'toc',
			'subjects',
			'filename',
			'dcformat',
			'changed');

		//add date modified
		$options['changed']=date("U");

		//remove slash before the file path otherwise can't link the path to the file
		if (isset($options['filename']))
		{
			if (substr($options['filename'],0,1)=='/')
			{
				$options['filename']=substr($options['filename'],1,255);
			}
		}
		
		if (isset($options['type']))
		{
			$options['dctype']=$options['type'];
		}
		if (isset($options['format']))
		{
			$options['dcformat']=$options['format'];
		}
		
		$data=array();
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//insert record into db
		$result=$this->db->insert('resources', $data); 
		
		return $result;		
	}
	
	
	/**
	*
	* Get a resource by filepath
	*
	* @filepath	relative path to the resource
	*/
	function get_resources_by_filepath($filepath)
	{
		$this->db->where('filename', $filepath); 
		return $this->db->get('resources')->result_array();
	}
	
	/**
	*
	* Get a resource by filepath
	*
	* @filepath	relative path to the resource
	*/
	function get_survey_resources_by_filepath($surveyid,$filepath)
	{
		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath); 		
		return $this->db->get('resources')->result_array();
	}
		
}
?>