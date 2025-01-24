<?php
/**
* External resources for surveys
*
**/
class Survey_resource_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array(
		'dctype',
		'title',
		'author', 
		'dcdate',
		'country', 
		'language', 
		'contributor',
		'publisher',
		'toc', 
		'abstract', 
		'filename',
		'dcformat',
		'description'
	);

	private $dctype_groups=array();
	
			
    public function __construct()
    {
		parent::__construct();
		$this->load->model("Dataset_model");
		$this->load->model("Catalog_model");
		$this->load->config("external_resources");

		$this->dctype_groups=$this->config->item("dctype_groups","external_resources");
		//$this->output->enable_profiler(TRUE);
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
					
		if (isset($options['filename'])){
			$options['filename']=$this->normalize_filename($options['filename']);
		}
		
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
		$this->db->where('resource_id', $resource_id);
		$result=$this->db->update('resources', $update_arr); 
		
		return $result;		
	}
	
	
	/**
	* 
	*	Add external resource
	*
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

		$options['changed']=date("U");

		//remove slash before the file path otherwise can't link the path to the file
		if (isset($options['filename'])){
			if (substr($options['filename'],0,1)=='/'){
				$options['filename']=substr($options['filename'],1,255);
			}
		}
		
		if (isset($options['type'])){
			$options['dctype']=$options['type'];
		}
		if (isset($options['format'])){
			$options['dcformat']=$options['format'];
		}
		
		if (isset($options['filename'])){
			$options['filename']=$this->normalize_filename($options['filename']);
		}

		$data=array();

		//build update statement
		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}

		$this->db->insert('resources', $data); 		
		return $this->db->insert_id();
	}


	public function validate_study_exists($sid)
	{
		if (!$this->Dataset_model->get_idno($sid)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid.');
			return false;
		}
		return true;
	}


	public function validate_resource_exists($resource_id)
	{		
		if (!$this->select_single($resource_id)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid.');
			return false;
		}
		return true;
	}


	/**
	 * 
	 * 
	 * Validate resource
	 * @options - array of resource fields
	 * @is_new - boolean - if set to true, requires resource_id field to be set
	 * 
	 **/
	function validate_resource($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validate form input
		if(!$is_new){
			//rule for resource_id
			$this->form_validation->set_rules(
				'resource_id', 
				'Resource ID',
				array(
					"required",
					"is_numeric",
					array('validate_resource_exists',array($this, 'validate_resource_exists')),				
				)				
			);
		}

		//below rules only get applied if inserting a new record or filled in when updating a record
		if($is_new || (!$is_new && isset($options['dctype']) )) {
			$this->form_validation->set_rules('dctype', 'Resource Type', 'xss_clean|trim|max_length[100]|required');
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
			$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');	
		}
		//survey_id validation rule
		$this->form_validation->set_rules(
			'survey_id', 
			'Survey ID',
			array(
				"required",
				"is_numeric",
				array('validate_study_exists',array($this, 'validate_study_exists')),				
			)				
		);
		
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}



	/**
	*
	* Return all resources attached to a survey
	*
	* @fields - comma seperated list of field names
	*
	**/
	function get_survey_resources($sid,$fields=null)
	{
		if(!empty($fields)){
			$this->db->select($fields);
		}else{
			$this->db->select('*');
		}
		$this->db->where('survey_id',$sid);
		$this->db->order_by('title','ASC');
		return $this->db->get('resources')->result_array();
	}
		


	/**
	* returns resource filenames by survey id
	*
	*
	**/
	function get_survey_resource_files($surveyid)
	{
		$this->db->select("resource_id,filename,title");
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	* 
	*
	* returns a single row
	*
	**/
	function select_single($id)
	{
		$this->db->where('resource_id', $id); 
		return $this->db->get('resources')->row_array();
	}


	function delete($id)
	{
		$this->db->where('resource_id', $id); 
		return $this->db->delete('resources');
	}


	//delete a single resource by dataset
	function delete_single($sid,$resource_id)
	{
		if (!$this->check_survey_resource($sid,$resource_id)){
			return false;
		}

		$this->db->where('survey_id', $sid);  
		$this->db->where('resource_id', $resource_id); 
		$this->db->delete('resources');

		return true;
	}

	function check_survey_resource($sid,$resource_id)
	{
		$this->db->select("resource_id");
		$this->db->where('survey_id', $sid);  
		$this->db->where('resource_id', $resource_id);
		$result=$this->db->get("resources")->result_array();

		if(empty($result)){
			return false;
		}
		return true;
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
	function get_dc_types()
	{
		$result= $this->db->get('dctypes')->result_array();

		$list=array();
		foreach($result as $row){
			$list[$row['title']]=$row['title'];
		}
		
		return $list;
	}
	
	/**
	* returns DC Formats
	*
	*
	**/
	function get_dc_formats()
	{
		$result= $this->db->get('dcformats')->result_array();

		$list=array();
		foreach($result as $row){
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
		$filepath=$this->normalize_filename($filepath);

		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath); 		
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	*
	* Check if resource is a duplicate
	*
	* @filepath	relative path to the resource
	*/
	function check_duplicate($surveyid,$filepath,$title,$dctype=null)
	{
		$filepath=$this->normalize_filename($filepath);

		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath);
		$this->db->where('title', $title);
		$resources=$this->db->get('resources')->result_array();

		$dctype=$this->get_dctype_code_from_string($dctype);
		if ($resources){
			foreach($resources as $idx=>$resource){
				$res_dctype=$this->get_dctype_code_from_string($resource['dctype']);
				if ($dctype!=$res_dctype){
					unset($resources[$idx]);
				}
			}
		}
		return $resources;
	}

	/**
	 * 
	 * 
	 * Return the dctype code from text
	 * 
	 * e.g. Document [doc/adm] will return doc/adm
	 * 
	 */
	function get_dctype_code_from_string($dctype)
	{
		preg_match_all("/\[([^\]]*)\]/", $dctype, $matches);
		$result= $matches[1];
		if ($result){
			return $result[0];
		}
		return $dctype;
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
	function get_single_resource_by_survey($sid,$resource_id)
	{
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
		$dctypes_exclude=[];
		
		if ($this->dctype_groups){
			foreach($this->dctype_groups as $group_name=>$dctypes){
				foreach($dctypes as $dctype){
					$dctypes_exclude[]=$dctype;
					$result=$this->get_resources_by_type($surveyid,$dctype.']');
					if ($result){
						if (!isset($output[$group_name])){
							$output[$group_name]=$result;
						}else{
							$output[$group_name]= array_merge($output[$group_name],$result);
						}
					}

				}
			}

			//other materials
			$output['other_materials']=$this->get_resources_other_materials($surveyid,$dctypes_exclude);
			return $output;
		}

		
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
		$this->db->select('resources.*,surveys.idno as dataset_idno');
		$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
		$this->db->where('survey_id',$surveyid);
		
		if ($dctype=='other')
		{
			//other materials
			$this->db->not_like('dctype','doc/tec]');
			$this->db->not_like('dctype','doc/rep]');
			$this->db->not_like('dctype','doc/qst]');
			$this->db->not_like('dctype','dat]');
			$this->db->not_like('dctype','dat/micro]');
			$this->db->not_like('dctype','[dat/');
		}
		else
		{
			$this->db->like('dctype',$dctype);
		}	
		
		$result= $this->db->get('resources')->result_array();
		foreach($result as $row_idx=>$row){
			$result[$row_idx]['is_microdata']=$this->is_microdata_resource($row['dctype']);
		}
		return $result;
	}

	/**
	*
	* 
	*
	*/
	function get_resources_other_materials($surveyid,$dctypes_exclude) 
	{
		$this->db->select('resources.*,surveys.idno as dataset_idno');
		$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
		$this->db->where('survey_id',$surveyid);
		
		//exclude dctypes
		$this->db->not_like('dctype','dat]');
		$this->db->not_like('dctype','dat/micro]');
		$this->db->not_like('dctype','[dat/');

		foreach($dctypes_exclude as $exclude){
			$this->db->not_like('dctype','['.$exclude);
		}
		
		$result= $this->db->get('resources')->result_array();
		foreach($result as $row_idx=>$row){
			$result[$row_idx]['is_microdata']=$this->is_microdata_resource($row['dctype']);
		}
		return $result;
	}

	/**
	 * 
	 * 
	 * Return true or false if type is microdata
	 */
	function is_microdata_resource($dctype)
	{
		$microdata_types=array('[dat/micro]','[dat]','[dat/');

		foreach($microdata_types as $type){
			if (stripos($dctype,$type)!==FALSE){
				return true;
			}
		}

		return false;

	}
	
	
	/**
	*
	* Return resources of microdata type
	**/
	function get_microdata_resources($surveyid)
	{
		$this->db->select('*');
		$this->db->where("survey_id=$surveyid AND (dctype like '%dat/micro]%' OR dctype like '%dat]%' OR dctype like '%[dat/%')",NULL,FALSE);		
		$this->db->order_by("dcdate","desc");
		$this->db->order_by("title","asc");
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
	* @resource_obj - resource record 
	* 
	* 
	**/
	function user_has_download_access($user_id,$survey_id,$resource_obj) 
	{
		$this->load->model("Licensed_model");
		$this->load->model("Public_model");

		$microdata_types=array('[dat/micro]','[dat]');
		$resource_is_microdata=false;

		foreach($microdata_types as $type){
			if (stripos($resource_obj['dctype'],$type)!==FALSE){
				$resource_is_microdata=TRUE;	
			}
		}

		if ($resource_is_microdata===false){
			return true;
		}
		
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		switch($data_access_type){
			case 'direct':
			case 'open':
			case 'public':
				return true;
				break;
			case 'licensed':

				if (!$user_id){
					throw new Exception(t("reason_login_licensed_access"));
				}

				$req_entries=$this->Licensed_model->get_requests_by_file($resource_obj['resource_id'],$user_id);

				foreach($req_entries as $req){
					if(strtoupper(trim($req['status']))=='APPROVED' 
						&& $req['expiry']> date("U") 
						&& (int)$req['downloads'] < (int)$req['download_limit'])
					{
						return true;
					}
				}
			
				throw new Exception("Access expired or you have reached the download limit for the file.");
				break;

			/*case 'public':
				$puf_request=$this->Public_model->check_user_has_data_access($user_id,$survey_id);

				if ($puf_request===FALSE){
					throw new Exception("For PUF files, user must accept terms of use.");
				}
				
				return true;
				break;
			*/
			default:
				throw new Exception("Unsupported");
		}
	}

	/**
	 * 
	 * Same as user_has_download_access except it returns an array with access info
	 */
	function get_user_download_access_info($user_id,$survey_id,$resource_obj) 
	{
		$this->load->model("Licensed_model");
		$this->load->model("Public_model");

		$microdata_types=array('[dat/micro]','[dat]');
		$resource_is_microdata=false;

		foreach($microdata_types as $type){
			if (stripos($resource_obj['dctype'],$type)!==FALSE){
				$resource_is_microdata=TRUE;	
			}
		}

		if ($resource_is_microdata===false){
			return array(
				'access'=>true,
				'is_microdata'=>false
			);
		}
		
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		switch($data_access_type){
			case 'direct':
			case 'open':
				return array(
					'access'=>true,
					'is_microdata'=>true,
					'license'=>$data_access_type
				);
				break;
			case 'licensed':

				if (!$user_id){
					throw new Exception(t("reason_login_licensed_access"));
				}

				$req_entries=$this->Licensed_model->get_requests_by_file($resource_obj['resource_id'],$user_id);

				foreach($req_entries as $req){
					if(strtoupper(trim($req['status']))=='APPROVED' 
						&& $req['expiry']> date("U") 
						&& (int)$req['downloads'] < (int)$req['download_limit'])
					{
						return array(
							'access'=>true,
							'is_microdata'=>true,
							'license'=>$data_access_type,
							'access_request'=>$req
						);
					}
				}
			
				return false;
				break;

			case 'public':

				//TODO: remove - for api access, at the time of api key creation, user must accept terms of use
				$puf_request=$this->Public_model->check_user_has_data_access($user_id,$survey_id);
				
				/*if ($puf_request===FALSE){
					return array(
						'access'=>false,
						'is_microdata'=>true,
						'license'=>$data_access_type,
						'error'=>'puf'
					);
				}*/
				
				return array(
					'access'=>true,
					'is_microdata'=>true,
					'license'=>$data_access_type,
					'access_request'=>$puf_request
				);

				break;
			default:
				return false;	
		}		
	}


	function whitelist_download($user_id,$survey_id,$resource)
	{
		$this->load->model("Data_access_whitelist_model");
		$user_whitelisted=$this->Data_access_whitelist_model->has_access($user_id,$survey_id);

		if(!$user_whitelisted){
			return false;
		}

		$resource_path=$this->get_resource_download_path($resource['resource_id']);

		if (!file_exists($resource_path)){
			throw new Exception ('RESOURCE_FILE_NOT_FOUND');
		}

		$is_microdata=$this->is_microdata_resource($resource);
		
		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$resource_path.'</em>');
		$this->db_logger->write_log('download',basename($resource_path),($is_microdata ? 'microdata': 'resource'),$survey_id);
		$this->db_logger->increment_study_download_count($survey_id);
		force_download2($resource_path);
	}
	

	function download($user,$survey_id,$resource_id)
	{
		//get resource
		$resource=$this->select_single($resource_id);

		if(!$resource){
			throw new Exception("RESOURCE_NOT_FOUND");
		}

		$user_id=isset($user->id) ? $user->id : false;
		
		$this->whitelist_download($user_id,$survey_id,$resource);
		
		$download_req=$this->get_user_download_access_info($user_id,$survey_id,$resource);

		if (!$download_req){
			throw new Exception("FILE_NOT_AVAILABLE");
		}

		//for public use
		if ($download_req['is_microdata']===true && $download_req['license']=='public'){
			if (!$user_id){
				throw new Exception(t("USER_NOT_LOGGED_IN"));
			}
		}				
		
		//licensed access increment download count
		if ($download_req['is_microdata']===true && $download_req['license']=='licensed'){
			$lic_request_info=$download_req['access_request'];

			//increment the download count for licensed file
			$this->Licensed_model->update_download_stats($resource_id,$lic_request_info['requestid'],$user->email);
		}


		//full path to the resource
		$resource_path=$this->get_resource_download_path($resource_id);

		if (!file_exists($resource_path)){
			throw new Exception ('RESOURCE_FILE_NOT_FOUND');
		}

		//download file
		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$resource_path.'</em>');
		$this->db_logger->write_log('download',basename($resource_path),($download_req['is_microdata'] ? 'microdata': 'resource'),$survey_id);
		$this->db_logger->increment_study_download_count($survey_id);
		force_download2($resource_path);		
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

	
	/**
	 * 
	 * Get the resource filename
	 * 
	 */
	function get_resource_filename($resource_id)
	{
		$resource=$this->select_single($resource_id);
		
		if (!$resource)
		{
			return FALSE;
		}
		
		return $resource['filename'];
	}

	
	function get_resource_download_path($resource_id)
	{		
		//resource info
		$resource=$this->select_single($resource_id);
		
		if (!$resource){
			return FALSE;
		}
		
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($resource['survey_id']);
						
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
		if (!file_exists($filepath)){
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
			$insert_data['filename']=$this->normalize_filename($insert_data['filename']);
			
			//check if the resource file already exists
			$resource_exists=$this->get_survey_resources_by_filepath($surveyid,$insert_data['filename']);
			
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

	function normalize_filename($filename)
	{
		//check filenam is URL?
		if (!is_url($filename))
		{
			//clean file paths
			$filename=unix_path($filename);
			
			//keep only the filename, remove path
			return basename($filename);
		}

		return $filename;
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

	function get_microdata_resources_count_by_survey($sid)
	{
		$this->db->select('count(resource_id) as total');
        $this->db->where('survey_id', $sid);
		$this->db->where("survey_id=$sid AND (dctype like '%dat/micro]%' OR dctype like '%dat]%' OR dctype like '%[dat/%')",NULL,FALSE);		
        $result=$this->db->get('resources')->row_array();
        return $result['total'];
	}


	/**
	*
	* Returns an array of all files in the survey folder
	*
	**/
	function get_files_array($sid)
	{	
		$this->load->model('Catalog_model');
		$this->load->model('Managefiles_model');

		//get survey folder path
		$folderpath=$this->Catalog_model->get_survey_path_full($sid);
					
		//get all survey files
		$data=$this->get_files_recursive($folderpath,$folderpath);
		$files=array();
		
		if (isset($data['files'])){
			foreach($data['files'] as $file){				
				$file_rel_path=$file['relative'].'/'.$file['name'];
				$files[]=array(					
					'name'=>$file['name'],
					'rel_path'=>$file_rel_path,
					'base64'=>base64_encode($file['name']),
					'date'=>$file['date'],
					'fileperms'=>$file['fileperms'],
					'size'=>$file['size']
				);
			}
		}		
		return $files;
	}



	/**
	 * 	
	 *
	 * upload external resource file
	 *
	 * @sid - survey id
	 * @file_field_name 	- name of POST file variable
	 *  
	 **/ 
	function upload_file($sid,$file_field_name='file',$remove_spaces=true)
	{
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $survey_folder;
		$config['overwrite'] = true;
		$config['encrypt_name']=false;
		$config['remove_spaces'] = $remove_spaces;//convert spaces or not
		$config['allowed_types'] = str_replace(",","|",$this->config->item("allowed_resource_types"));
		
		$this->load->library('upload', $config);
		//$this->upload->initialize($config);

		//process uploaded rdf file
		$upload_result=$this->upload->do_upload($file_field_name);

		if (!$upload_result){
			throw new Exception($this->upload->display_errors());
		}

		return $this->upload->data();		
	}
	

	/**
	 * 	
	 *
	 * upload rdf file
	 *
	 * @file_field 	- name of POST file variable
	 *  
	 **/ 
	function upload_rdf($tmp_path,$file_field='file')
	{		
		if (!$tmp_path){
			$tmp_path=get_catalog_root().'/tmp';
		
			if (!file_exists($tmp_path)){
				@mkdir($tmp_path);
			}
		}
		
		if (!file_exists($tmp_path)){
			throw new Exception('TEMP-FOLDER-NOT-SET: '.$tmp_path);
		}
						
		//upload class configurations for RDF
		$config['upload_path'] = $tmp_path;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf|xml';

		$this->load->library('upload', $config);
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



	/*
	* Delete a single file
	*
	*/
	function delete_file($sid, $base64_filepath)
	{
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
				
		$filepath=urldecode(base64_decode($base64_filepath));		
		$fullpath=unix_path($survey_folder.'/'.$filepath);
		
		//log deletion
		$this->db_logger->write_log('resource-delete',$fullpath,'external-resource',$sid);
		
		if(!file_exists($fullpath)){
			throw new Exception("FILE_NOT_FOUND: ".urlencode($filepath));
		}
		elseif (is_file($fullpath) && file_exists($fullpath)){
			$isdeleted=silent_unlink($fullpath);
			
			if($isdeleted===FALSE){
				throw new Exception("file_delete_failed");
			}
		}
		return true;
	}


	/**
	 * 
	 * Download a file
	 * 
	 */
	function download_file($sid, $base64_filepath) 
	{
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
		
		$filepath=urldecode(base64_decode($base64_filepath));		
		$fullpath=unix_path($survey_folder.'/'.basename($filepath));
		
		//log download
		$this->db_logger->write_log('download',$fullpath,'external-resource');
		
		if (is_file($fullpath) && file_exists($fullpath)){
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$fullpath.'</em>');
			force_download2($fullpath);
		}
		else {
			throw new Exception("FILE_NOT_FOUND: ".urlencode(basename($filepath)));
		}
	}




	/**
	*
	* Fix file paths for external resources 
	**/
	function fix_resource_links($surveyid)
	{		
		$this->load->model('Catalog_model');

		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		
		//get survey resources
		$resources=$this->get_survey_resource_files($surveyid);
		
		//hold broken resources
		$broken_links=array();
		
		//build an array of broken resources, ignore the resources with correct paths
		foreach($resources as $resource){
			//check if the resource file found on disk
			if(!is_url($resource['filename'])){
				if(!file_exists( unix_path($survey_folder.'/'.$resource['filename']))){
					$broken_links[]=$resource;
				}
			}
		}
		
		//get a list of all files in the survey folder
		$files=$this->get_files_recursive($survey_folder,$survey_folder);

		//number of links fixed
		$fixed_count=0;
		
		//find matching files in the filesystem for the broken links
		foreach($broken_links as $key=>$resource){			
			$match=FALSE;
			
			//search files array and return the relative path to the file if found 
			foreach($files['files'] as $file){
				//match found
				if(strtolower($file['name'])==strtolower(basename($resource['filename'])) ){					
					$match=$file['relative'];
					
					//update path in database
					$this->update($resource['resource_id'],array('filename'=>$file['relative'].'/'.$file['name']));
					
					//update the count
					$fixed_count++;					
					break;
				}
			}			
			//add path for the resources
			$broken_links[$key]['match']=$match;
		}
		
		return $fixed_count;
	}



	/**
	*
	* Return all files including subfolders
	* 
	*	@make_relative_to	make the file path relative to this path
	*/	
	function get_files_recursive($absolute_path,$make_relative_to)
	{
		$dirs = array();
		$files = array();
		//traverse folder
		if ( $handle = @opendir( $absolute_path )){
			while ( false !== ($file = readdir( $handle ))){
				if (( $file != "." && $file != ".." )){
					if ( is_dir( $absolute_path.'/'.$file )){
						$tmp=$this->get_files_recursive($absolute_path.'/'.$file,$make_relative_to);
						foreach($tmp['files'] as $arr){
							if (isset($arr["name"])){
								$files[]=$arr;
							}
						}
						foreach($tmp['dirs'] as $arr){
							if (isset($arr["name"])){
								$dirs[]=$arr;
							}
						}
						$dirs[]=$this->get_file_relative_path($make_relative_to,$absolute_path.'/'.$file);
					}
					else{
						$tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));
						$tmp['name']=$file;
						$tmp['size']=format_bytes($tmp['size']);
						$tmp['fileperms']=symbolic_permissions($tmp['fileperms']);
						$tmp['path']=$absolute_path;
						$tmp['relative']=$this->get_file_relative_path($make_relative_to,$absolute_path);
						$files[]=$tmp;
					}
				}
			}
			closedir( $handle );
			sort( $dirs );
		}
		return array('files'=>$files, 'dirs'=>$dirs);
	}

	/**
	*
	* Get file relative path excluding the survey folder path
	*
	*/
	function get_file_relative_path($survey_path,$file_path)
	{
		$survey_path=unix_path($survey_path);
		$file_path=unix_path($file_path);		
		return str_replace($survey_path,"",$file_path);
	}



	/**
	 * 
	 * 
	 * Return the DCTYPE label by code
	 * 
	 * 
	 */
	function get_dctype_label_by_code($dctype)
	{
		$codes=array(
			'doc/adm'=>'Document, Administrative [doc/adm]',
			'doc/anl'=>'Document, Analytical [doc/anl]',
			'doc/oth'=>'Document, Other [doc/oth]',
			'doc/qst'=>'Document, Questionnaire [doc/qst]',
			'doc/ref'=>'Document, Reference [doc/ref]',
			'doc/rep'=>'Document, Report [doc/rep]',
			'doc/tec'=>'Document, Technical [doc/tec]',
			'aud'=>'Audio [aud]',
			'dat'=>'Database [dat]',
			'map'=>'Map [map]',
			'dat/micro'=>'Microdata File [dat/micro]',
			'pic'=>'Photo [pic]',
			'prg'=>'Program [prg]',
			'tbl'=>'Table [tbl]',
			'vid'=>'Video [vid]',
			'web'=>'Web Site [web]'
		);

		if(array_key_exists($dctype,$codes)){
			return $codes[$dctype];
		}
		
		return $dctype;
	}

	/**
	 * 
	 * 
	 * Return the dcformat label by code
	 * 
	 * 
	 */
	function get_dcformat_label_by_code($dcformat)
	{
		$codes=array(
			'application/x-compressed'=>'Compressed, Generic []',
			'application/zip'=>'Compressed, ZIP',
			'application/x-cspro'=>'Data, CSPro',
			'application/dbase'=>'Data, dBase',
			'application/msaccess'=>'Data, Microsoft Access',
			'application/x-sas'=>'Data, SAS',
			'application/x-spss'=>'Data, SPSS',
			'application/x-stata'=>'Data, Stata',
			'text'=>'Document, Generic',
			'text/html'=>'Document, HTML',
			'application/msexcel'=>'Document, Microsoft Excel',
			'application/mspowerpoint'=>'Document, Microsoft PowerPoint',
			'application/msword'=>'Document, Microsoft Word',
			'application/pdf'=>'Document, PDF',
			'application/postscript'=>'Document, Postscript',
			'text/plain'=>'Document, Plain',
			'text/wordperfect'=>'Document, WordPerfect',
			'image/gif'=>'Image, GIF',
			'image/jpeg'=>'Image, JPEG',
			'image/png'=>'Image, PNG',
			'image/tiff'=>'Image, TIFF'
		);

		if(array_key_exists($dcformat,$codes)){
			return $codes[$dcformat] . ' ['.$dcformat.']';
		}
		
		return $dcformat;
	}


	/**
	 * 
	 * Get resources downloadable links by Study IDNO
	 * 
	 */
	function get_download_links($idno_arr)
	{
		if (!is_array($idno_arr)){
			throw new Exception("idno_arr is is not an array");
		}

		//default fields
		$fields="resource_id,survey_id,filename,surveys.idno";

		foreach (array_chunk($idno_arr, 100, true) as $chunk) {
			$this->db->select($fields);
			$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
			$this->db->where_in("surveys.idno",$chunk);
			$resources=$this->db->get('resources')->result_array();
			
			if ($resources){
				$output=array();
				foreach($resources as $resource){
					$link='';
					if($this->form_validation->valid_url($resource['filename'])){
						$link=$resource['filename'];
					}else{
						$link=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
					}  

					yield [
						'idno'=>$resource['idno'],
						'link'=>$link
					];
				}				
			}
		}
	}


	/**
	 * 
	 * Get resources by studies
	 * 
	 * @additional_fields = array of additional fields to include
	 * 
	 */
	function get_resources_by_studies($idno_arr,$additional_fields=null)
	{
		if (!is_array($idno_arr)){
			throw new Exception("idno_arr is is not an array");
		}

		//default fields
		$fields="resource_id,survey_id,filename,surveys.idno";

		if(is_array($additional_fields)){
			$fields=$fields . ',' . implode(",",$additional_fields);
		}

		foreach (array_chunk($idno_arr, 100, true) as $chunk) {
			$this->db->select($fields);
			$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
			$this->db->where_in("surveys.idno",$chunk);
			$resources=$this->db->get('resources')->result_array();
			
			if ($resources){
				$output=array();
				foreach($resources as $resource){
					$link='';
					if($this->form_validation->valid_url($resource['filename'])){
						$link=$resource['filename'];
					}else{
						$link=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
					}
					
					$resource['link']=$link;
					$resource['ext']=strtolower(pathinfo($resource['filename'],PATHINFO_EXTENSION));
					
					yield $resource;
				}				
			}
		}
	}


	/**
	 * 
	 * Format resources
	 * 
	 * add download link + file sizes
	 * 
	 */
	function format_resources($resources)
	{
		if (empty($resources)){
			return false;
		}

		$output=array();
		foreach($resources as $resource){
			$link='';
			if($this->form_validation->valid_url($resource['filename'])){
				$link=$resource['filename'];
				$resource['is_url']=true;
			}else{
				$link=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
				$resource['filesize']=$this->get_resource_filesize($resource);
				$resource['is_url']=false;				
			}
			
			$resource['link']=$link;
			$resource['ext']=strtolower(pathinfo($resource['filename'],PATHINFO_EXTENSION));
			
			$output[]=$resource;
		}
		
		return $output;
	}

	function resource_attach_zip_preview($survey_folder,$resources)
	{	
		foreach($resources as $key=>$resource){
			$filepath=unix_path($survey_folder.'/'.$resource['filename']);
			$resources[$key]['zip_preview']=$this->get_zip_archive_info($filepath);
		}

		return $resources;
	}

	
	function get_zip_archive_info($filepath)
	{
		$zip_content=get_zip_archive_list($filepath);

		if (!$zip_content){
			return false;
		}

		$output=[];

		//convert to a nested array
		foreach($zip_content as $key=>$value){

			//remove last slash
			if (substr($key, -1) == '/'){				
				$key=substr($key, 0, -1);
			}
			set_array_nested_value($output, $parents=$key, $value, $glue = '/');
		}

		return $output;
	}


	function get_resource_filesize($resource)
	{
		if (!$resource){
			return FALSE;
		}
		
		$survey_folder=$this->Catalog_model->get_survey_path_full($resource['survey_id']);						
		$file_path=unix_path($survey_folder.'/'.$resource['filename']);

		if (file_exists($file_path)){
			return filesize($file_path);
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Add download links for resources
	 * 
	 */
	function generate_download_link($resources)
	{
		foreach($resources as $idx => $resource){
			if($this->form_validation->valid_url($resource['filename'])){
				$resources[$idx]['_links']=array(
					'download'=>$resource['filename'],
					'type'=>'link'
				);				
			}else{
				if(!empty($resource['filename'])){
					$resources[$idx]['_links']=array(
						'download'=> site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename'])),
						'type'=>'download'
					);
				}
			}  
		}

		return $resources;
	}

	/**
	 * 
	 * 
	 * Add download links for resources
	 * 
	 */
	function generate_api_download_link($resources)
	{
		foreach($resources as $idx => $resource){
			if($this->form_validation->valid_url($resource['filename'])){
				$resources[$idx]['_links']=array(
					'download'=>$resource['filename'],
					'type'=>'link'
				);				
			}else{
				if(!empty($resource['filename'])){
					$resources[$idx]['_links']=array(
						'download'=> site_url("api/resources/download/{$resource['survey_id']}/{$resource['resource_id']}/".rawurlencode($resource['filename']).'?id_format=id'),
						'type'=>'download'
					);
				}
			}  
		}

		return $resources;
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
	 * 
	 * Return an associated array using filename as the key
	 * 
	 * 
	 */
	function get_survey_resources_group_by_filename($sid)
	{
		$resources=$this->get_survey_resources($sid);
		$output=array();
		
		foreach($resources as $resource)
		{
			$output[$resource['filename']]=$resource;
		}

		return $output;
	}

}