<?php
/**
* External resources for surveys
*
**/
class Resource_model extends CI_Model {
	
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
	*
	* Return all resources attached to a survey
	**/
	function get_survey_resources($sid)
	{
		$this->db->select('*');
		$this->db->where('survey_id',$sid);
		$this->db->order_by('title','ASC');
		return $this->db->get('resources')->result_array();
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
	
	
	
	
	/**
	*
	* Get a list of all resources by survey id
	*
	*/
	function get_resources_by_survey($surveyid)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}

	/**
	* returns a single row
	*
	*
	**/
	function get_single_resource_by_survey($sid,$resource_id){
		$this->db->where('resource_id', $resource_id); 
		$this->db->where('survey_id', $sid); 
		return $this->db->get('resources')->row_array();
	}

	/**
	*
	* List of resources grouped by resource-type
	*
	*
	*/
	function get_grouped_resources_by_survey($surveyid)
	{
		$output=FALSE;
		
		//questionnaires
		$result=$this->get_resources_by_type($surveyid,'doc/qst]');
		if ($result)
		{
			$output['questionnaires']=$result;
		}	

		//reports
		$result=$this->get_resources_by_type($surveyid,'doc/rep]');
		if ($result)
		{
			$output['reports']=$result;
		}			
			
		//technical documents
		$result=$this->get_resources_by_type($surveyid,'doc/tec]');
		if ($result)
		{
			$output['technical']=$result;
		}					
		
		//other materials
		$result=$this->get_resources_by_type($surveyid,'other');
		if ($result)
		{
			$output['other']=$result;
		}			

		return $output;	
	}


	/**
	*
	* Return resource by survey and resource type
	*
	*/
	function get_resources_by_type($surveyid,$dctype)
	{
		$this->db->select('*');
		$this->db->where('survey_id',$surveyid);
		
		if ($dctype=='other')
		{
			//other materials
			$this->db->not_like('dctype','doc/tec]');
			$this->db->not_like('dctype','doc/rep]');
			$this->db->not_like('dctype','doc/qst]');
			$this->db->not_like('dctype','dat]');
			$this->db->not_like('dctype','dat/micro]');
		}
		else
		{
			$this->db->like('dctype',$dctype);
		}	
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	*
	* Return resources of microdata type
	**/
	function get_microdata_resources($surveyid)
	{
		$this->db->select('*');
		$this->db->where("survey_id=$surveyid AND (dctype like '%dat/micro]%' OR dctype like '%dat]%')",NULL,FALSE);
		return $this->db->get('resources')->result_array();
	}
	
	/**
	*
	* Returns Data Access type set for the Resource
	**/
	function get_resource_da_type($resource_id)
	{
		$this->db->select('forms.model');
		$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
		$this->db->join('forms', 'forms.formid= surveys.formid','inner');
		$this->db->where('resources.resource_id',$resource_id);
		$query=$this->db->get('resources')->row_array();
		
		if ($query)
		{
			return $query['model'];
		}
		
		return FALSE;
	}
	
	/**
	*
	* Check user has access to resource file
	*
	* checks PUF, LIC, and by collection
	**/
	function user_has_download_access($user_id,$survey_id,$resource_id)
	{
		//get survey data access
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);
		
		if ($data_access_type=='licensed')
		{
			//get files request info with requests status
			$req_entries=$this->Licensed_model->get_requests_by_file($resource_id,$user_id);
			
			if (!$req_entries)
			{
				return FALSE;
			}
			
			foreach($req_entries as $req)
			{
				if($req['req_status']=='APPROVED' && $req['expiry']> date("U") && $req['downloads'] < $req['download_limit'])
				{
					//allow download
					//var_dump($req);exit;
					return TRUE;
				}
			}
		}
		else if ($data_access_type=='public')
		{
		
		}
		
	}
	
	
	/**
	*
	* Check if any resources are attached to the study
	*
	*/
	function has_external_resources($surveyid)
	{
		$this->db->select('count(*) as total');
		$this->db->where('survey_id',$surveyid);
		$this->db->not_like('dctype','dat]');
		$this->db->not_like('dctype','dat/micro]');
		$result=$this->db->get('resources')->row_array();		
		return $result['total'];
	}

	/**
	*
	* Returns resource counts group by dctype
	**/
	function get_grouped_resources_count($surveyid)
	{
		$this->db->select('dctype,count(*) as total');
		$this->db->where('survey_id',$surveyid);
		$this->db->group_by('dctype');
		$result=$this->db->get('resources')->result_array();
		return $result;
	}

	
	
	function get_resource_download_path($resource_id)
	{
		$this->load->model('catalog_model');
		
		//resource info
		$resource=$this->select_single($resource_id);
		
		if (!$resource)
		{
			return FALSE;
		}
		
		//get survey folder path
		$survey_folder=$this->catalog_model->get_survey_path_full($resource['survey_id']);
						
		//build complete filepath to be downloaded
		$file_path=unix_path($survey_folder.'/'.$resource['filename']);

		return $file_path;		
	}
	
	
	/**
	*
	* Check if resource already exists for a study
	*
	* @filepath	relative path to the resource
	*/
	function survey_resource_exists($sid,$title,$dctype,$filename)
	{
		$this->db->select('count(*) as found');
		$this->db->where('survey_id', $sid); 
		$this->db->where('filename', $filename);
		$this->db->where('dctype', $dctype); 
		$query=$this->db->get('resources')->row_array();
		
		if ($query['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}


	/**
	*
	* Import RDF file
	**/
	public function import_rdf($surveyid,$filepath)
	{
		//check file exists
		if (!file_exists($filepath))
		{
			return FALSE;
		}
		
		//read rdf file contents
		$rdf_contents=file_get_contents($filepath);
			
		//load RDF parser class
		$this->load->library('RDF_Parser');
			
		//parse RDF to array
		$rdf_array=$this->rdf_parser->parse($rdf_contents);

		if ($rdf_array===FALSE || $rdf_array==NULL){
			return FALSE;
		}

		//Import
		$rdf_fields=$this->rdf_parser->fields;

		$output=array(
			'added'=>0,
			'skipped'=>0
		);
			
		//success
		foreach($rdf_array as $rdf_rec)
		{
			$insert_data['survey_id']=$surveyid;
			
			foreach($rdf_fields as $key=>$value)
			{
				if ( isset($rdf_rec[$rdf_fields[$key]]))
				{
					$insert_data[$key]=trim($rdf_rec[$rdf_fields[$key]]);
				}	
			}										
			
			//check filenam is URL?
			if (!is_url($insert_data['filename']))
			{
				//clean file paths
				$insert_data['filename']=unix_path($insert_data['filename']);

				//remove slash before the file path otherwise can't link the path to the file
				if (substr($insert_data['filename'],1,1)=='/')
				{
					$insert_data['filename']=substr($insert_data['filename'],2,255);
				}												
			}
			
			//check if the resource file already exists
			$resource_exists=$this->Resource_model->get_resources_by_filepath($insert_data['filename']);
			
			if (!$resource_exists)
			{										
				//insert into db
				$this->insert($insert_data);
				$output['added']++;
			}
			else{
				$output['skipped']++;
			}
		}
	
		return $output;
	}

	/**
     *
     * Count resources by survey
     *
     */
    function get_resources_count_by_survey($sid)
    {
        $this->db->select('count(resource_id) as total');
        $this->db->where('survey_id', $sid);
        $result=$this->db->get('resources')->row_array();
        return $result['total'];
	}
	

	/**
	 * 
	 * 
	 * Upload an RDF and return path to the file
	 * 
	 * @tmp_path - folder where to upload the file
	 * @file_field - FILE field name
	 * 
	 */
	function upload_rdf($tmp_path,$file_field='rdf')
	{
		//upload class configurations for RDF
		$config['upload_path'] = $tmp_path;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload($file_field);

		if(!$rdf_upload_result){
			$error = $this->upload->display_errors();
			throw new Exception("RDF_UPLOAD::".$error);
		}
		
		$upload = $this->upload->data();

		//path to the uploaded rdf file
		return $upload['full_path'];
	}

	/**
	 * 
	 * Upload an RDF file and import resources
	 * 
	 * 
	 */
	function import_uploaded_rdf($sid,$tmp_path,$file_field='rdf')
	{
		//upload RDF file
		$uploaded_rdf_path=$this->upload_rdf($tmp_path,$file_field);
		
		//import rdf entries
		$rdf_import_result=$this->import_rdf($sid,$uploaded_rdf_path);

		//delete rdf
		@unlink($uploaded_rdf_path);

		return $rdf_import_result;
	}

}