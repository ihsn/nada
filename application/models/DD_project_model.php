<?php
/**
* Data deposit projects
*
**/
class DD_project_model extends CI_Model {
	
	//database column names
	var $fields=array(
		'title',
		'project_type',
		'status', 
		'description',
		'shortname', 
		'collaborators', 
		'access_policy',
		'library_notes',
		'submit_contact', 
		'submit_on_behalf', 
		'cc',
		'to_catalog',
		'access_authority',
		'submitted_by',
		'submitted_date',
		'created',
		'changed',
		'created_by',
		'changed_by',
		'admin_date',
		'admin_by',
		'is_embargoed',
		'embargoed_notes',
		'disclosure_risk',
		'metadata'
	);

	private $project_types=array(
		'survey'		=> 'Survey',
		'geospatial'	=> 'Geospatial'
	);
	
			
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	

	/**
	* 
	*
	* returns a single row
	*
	**/
	function select_single($id,$user_id=null)
	{
		$this->db->select("id,project_type,title,description,shortname,created,created_by,changed,changed_by,status,access_policy,library_notes,cc,to_catalog,is_embargoed,embargoed_notes,disclosure_risk");
		$this->db->where('id', $id); 
		
		if($user_id){
			$this->db->where('created_by',$user_id);
		}
		
		$project=$this->db->get('dd_projects')->row_array();

		//get project collaborators
		if($project){
			$project['collaborators']=$this->get_project_collaborators($id);
		}

		//remove metadata field
		if(isset($project['metadata'])){
			unset($project['metadata']);
		}

		return $project;
	}


	/**
	* 
	*
	* Get project access policy information 
	*
	**/
	function get_access_policy_info($id,$user_id=null)
	{
		$this->db->select("id,access_policy,library_notes,cc,to_catalog,is_embargoed,embargoed_notes,disclosure_risk");
		$this->db->where('id', $id); 
		
		if($user_id){
			$this->db->where('created_by',$user_id);
		}
		
		$project=$this->db->get('dd_projects')->row_array();
		return $project;
	}


	/**
	* 
	* Check if a project exists
	*
	* returns true or false
	*
	**/
	function project_exists($id)
	{
		$this->db->select("id");
		$this->db->where('id', $id); 
		$project=$this->db->get('dd_projects')->row_array();

		if (isset($project['id'])){
			return true;
		}
		return false;
	}

	/**
	* 
	* Return project status
	*
	*
	**/
	function get_project_status($id)
	{
		$this->db->select("status");
		$this->db->where('id', $id); 
		$project=$this->db->get('dd_projects')->row_array();

		if (isset($project['status'])){
			return $project['status'];
		}
		return false;
	}


	//get all user created + collobarated projects list
	function get_user_projects($user_id)
	{
		$this->db->select('id,project_type,title,shortname,status,created,changed,created_by,changed_by');
		$this->db->where('created_by',$user_id);
		$this->db->or_where('changed_by',$user_id);
		$this->db->order_by('created','desc');
		return $this->db->get("dd_projects")->result_array();
	}


	//get all user created + collobarated projects list
	function get_projects($user_id=null)
	{
		$this->db->select('id,project_type,title,shortname,status,created,changed,created_by,changed_by');
		$this->db->order_by('created','desc');
		if($user_id){
			$this->db->where('created_by',$user_id);
		}
		return $this->db->get("dd_projects")->result_array();
	}


	function submit_project($project_id)
	{
		$options=array(
			'changed'=>date("U"),
			'submitted_date'=>date("U")
		);

		return $this->set_project_status($project_id,'submitted',$options);
	}


	function set_project_status($project_id,$status,$options)
	{
		$valid_fields=array('status','changed','submitted_date');

		//default options
		$data=array(
			'status'=>$status,
			'changed'=>date("U"),
			'submitted_date'=>date("U")
		);

		//merge with options param
		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}

		$this->db->where('id',$project_id);
		$result=$this->db->update('dd_projects',$data);

		return $result;
	}



	function set_metadata($project_id,$metadata_json)
	{
		$options=array(
			'metadata'=>base64_encode($metadata_json)
		);

		$this->db->where('id',$project_id);
		$this->db->update('dd_projects',$options);
	}


	function get_metadata($project_id)
	{
		$this->db->select("metadata");
		$this->db->where('id',$project_id);
		$metadata=$this->db->get("dd_projects")->row_array();
		return base64_decode($metadata['metadata']);
	}


	//encode metadata for db storage
    public function encode_metadata($metadata_array)
    {
        return base64_encode(serialize($metadata_array));
    }

    //decode metadata to array
    public function decode_metadata($metadata_encoded)
    {
        return unserialize(base64_decode($metadata_encoded));
    }

	/**
	* 
	*	Create project
	*
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=$this->fields;

		$options['changed']=date("U");
		$options['created']=date("U");
		$options['status']='draft';
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}

		//collaborators
		if (isset($data['collaborators'])){
			$data['collaborators']=$this->encode_metadata($data['collaborators']);	
		}
		
		$result=$this->db->insert('dd_projects', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
			
		$project_id=$this->db->insert_id();
		
		//collaborators
		if ($project_id && isset($options['collaborators'])){
			$this->set_project_collaborators($project_id,$options['collaborators']);
		}

		//project folder
		$this->setup_project_folder($project_id);	

		return $project_id;
	}



	/**
	* 
	*	update project
	*
	**/
	function update($project_id,$options)
	{
		//allowed fields
		$valid_fields=$this->fields;
		$options['created']=date("U");

		//unset status field if set
		if(isset($options['status'])){
			unset($options['status']);
		}
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}

		//collaborators
		if (isset($data['collaborators'])){
			$data['collaborators']=$this->encode_metadata($data['collaborators']);	
		}
		
		$this->db->where('id',$project_id);
		$result=$this->db->update('dd_projects', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
			
		//collaborators
		if (isset($options['collaborators'])){
			$this->set_project_collaborators($project_id,$options['collaborators']);
		}

		//project folder
		//$this->setup_project_folder($project_id);	

		return $result;
	}




	//set project data files folder path
	public function setup_project_folder($project_id,$folder_name=null)
	{
		//create folder if not already exists
		if (!$folder_name){
			$folder_name='P-'.$project_id.'-'.date("U");
		}

		$this->create_project_folder($folder_name);		

		$options=array(
			'data_folder_path'=> $folder_name
		);
		
		$this->db->where('id',$project_id);
		$this->db->update('dd_projects',$options);
	}


	//create new project folder
	private function create_project_folder($folder_name)
	{	
		//full path to the project folder
		$folder_full_path=$this->get_datadeposit_storage_path().'/'.$folder_name;		

		if(!file_exists($folder_full_path)){
			mkdir($folder_full_path);
		}

		if(!file_exists($folder_full_path)){
			throw new exception("ERROR_CREATING_PROJECT_FOLDER:".$folder_full_path);
		}
	}

	//checks if a project folder exists
	public function project_folder_exists($project_id)
	{
		$folder=$this->get_project_path_full($project_id);

		if(!file_exists($folder)){
			return false;
		}

		return true;
	}

	
	//partial path to the project folder
	public function get_project_path_partial($project_id)
	{
		$this->db->select("data_folder_path");
		$this->db->where('id', $project_id); 
		$row=$this->db->get('dd_projects')->row_array();		
		return $row['data_folder_path'];
	}



	//full path to the project folder
	public function get_project_path_full($project_id)
	{
		$this->db->select("data_folder_path");
		$this->db->where('id', $project_id); 
		$row=$this->db->get('dd_projects')->row_array();		

		if ($row){
			$project_folder=$row['data_folder_path'];

			if(empty($project_folder) || trim($project_folder)==""){
				return false;
			}
			
			return unix_path($this->get_datadeposit_storage_path().'/'.$row['data_folder_path']);
		}
	}

	//get path to data deposit root folder where all projects are stored
	public function get_datadeposit_storage_path()
	{
		//root storage folder path
		$storage_folder=$this->config->item("catalog_root");
		
		if(!$storage_folder){
			throw new Exception("STORAGE_PATH_NOT_SET:catalog_root");
		}

		//data deposit full path
		$datadeposit_folder=unix_path($storage_folder.'/datadeposits');
		
		//make folder if not already exists
		if(!file_exists($datadeposit_folder)){
			@mkdir($datadeposit_folder);
		}

		if(!file_exists($datadeposit_folder)){
			throw new Exception("DATA_DEPOSIT_STORAGE_FOLDER_MISSING");
		}

		return $datadeposit_folder;
	}


	public function set_project_collaborators($project_id,$collaborators)
	{	
		//remove all collaborators
		$this->remove_all_collaborators($project_id);
		
		foreach($collaborators as $email){
			$options=array(
				'pid'=>$project_id,
				'email'=>$email
			);
			$this->db->insert('dd_collaborators',$options);
		}
	}

	//remove all collaborators for a project
	public function remove_all_collaborators($project_id)
	{
		$this->db->where('pid',$project_id);
		$this->db->delete('dd_collaborators');
	}


	//get project collaborators array
	public function get_project_collaborators($project_id)
	{
		$this->db->where('pid',$project_id);
		$result=(array)$this->db->get('dd_collaborators')->result_array();

		$output=array();
		foreach($result as $row){
			$output[]=$row['email'];
		}

		return $output;
	}

	


	//validate project type
	public function validate_project_type($type)
	{		
		if (!array_key_exists($type,$this->project_types)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid. Supported types are: '. implode(", ", array_keys($this->project_types)));
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
	function validate_project($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for updating a record
		if($is_new){				
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
			$this->form_validation->set_rules('description', 'Description', 'required|xss_clean|trim|max_length[255]');	

			//project type validation rule
			$this->form_validation->set_rules(
				'project_type', 
				'Project Type',
				array(
					"required",
					array('validate_project_type',array($this, 'validate_project_type')),				
				)		
			);
		}
		else{
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]');
			$this->form_validation->set_rules('description', 'Description', 'xss_clean|trim|max_length[255]');

			if(isset($options['project_type'])){
			//project type validation rule
			$this->form_validation->set_rules(
				'project_type', 
				'Project Type',
				array(
					array('validate_project_type',array($this, 'validate_project_type')),				
				)		
			);
			}
		}

		$this->form_validation->set_rules('shortname', 'Short Name', 'xss_clean|trim|max_length[255]');				
		$this->form_validation->set_rules('collaborators[]', 'Collaborators', 'xss_clean|trim|max_length[255]|valid_email');
		
		
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
	 * 
	 *  Delete project and related info
	 * 
	 * 
	 */
	function delete($id)
	{
		//delete collaborators
		$this->remove_all_collaborators($id);

		//delete project
		$this->db->where('id', $id); 
		return $this->db->delete('dd_projects');		
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
	function upload_file($sid,$file_field_name='file')
	{
		
		$this->load->model("Survey_model");
		$this->load->model("Catalog_model");

		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $survey_folder;
		$config['overwrite'] = true;
		$config['encrypt_name']=false;
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
		$fullpath=unix_path($survey_folder.'/'.$filepath);
		
		//log download
		$this->db_logger->write_log('download',$fullpath,'external-resource');
		
		if (is_file($fullpath) && file_exists($fullpath)){
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$fullpath.'</em>');
			force_download2($fullpath);
		}
		else {
			throw new Exception("FILE_NOT_FOUND: ".urlencode($filepath));
		}
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

}