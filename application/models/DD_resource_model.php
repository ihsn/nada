<?php
/**
* Data deposit projects
*
**/
class DD_resource_model extends CI_Model {
	
	//database column names
	var $fields=array(
		'pid',
		'title',
		'resource_type', 
		'description',
		'filename', 		
		'created',
		'changed',
		'created_by',
		'changed_by'
	);

	private $resource_types=array(
		'questionnaire'	=>'Questionnaire',
		'report'		=>'Report',
		'microdata'		=>'Microdata file',
		'other'			=>'Other'
	);
	
			
    public function __construct()
    {
		parent::__construct();
		$this->load->model('DD_project_model');
		//$this->output->enable_profiler(TRUE);
    }
	

	/**
	* 
	*
	* returns a single row
	*
	**/
	function select_single($id)
	{
		$this->db->where('id', $id); 
		$result=$this->db->get('dd_resources')->row_array();

		return $result;
	}

	/**
	 * 
	 * 
	 * Get a single resource by project ID and resource ID
	 * 
	 */
	function get_project_single_resource($project_id,$resource_id)
	{
		$this->db->select('*');
		$this->db->where('pid',$project_id);
		$this->db->where('id',$resource_id);
		return $this->db->get("dd_resources")->row_array();
	}

	//get resources by project
	function get_project_resources($project_id)
	{
		$this->db->select('*');
		$this->db->where('pid',$project_id);
		return $this->db->get("dd_resources")->result_array();
	}

	function delete($resource_id)
	{
		//remove attached file
		$this->delete_resource_file($resource_id);

		//remove from db
		$this->db->where('id', $resource_id); 
		return $this->db->delete('dd_resources');
	}


	/**
	* 
	*	Create resource
	*
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=$this->fields;

		$options['changed']=date("U");
		$options['created']=date("U");
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}
		
		$result=$this->db->insert('dd_resources', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
			
		$resource_id=$this->db->insert_id();
		return $resource_id;
	}


	/**
	* update external resource
	*
	*	resource_id		int
	* 	options			array
	**/
	function update($resource_id,$options)
	{
		$valid_fields=$this->fields;		
		$options['changed']=date("U");
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}
		
		$this->db->where('id', $resource_id);
		$result=$this->db->update('dd_resources', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
		
		return $result;
	}
	
	

	//validate resource type
	public function validate_resource_type($type)
	{		
		if (!array_key_exists($type,$this->resource_types)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid. Supported types are: '. implode(", ", array_keys($this->resource_types)));
			return false;
		}
		return true;
	}



	/**
	 * 
	 * 
	 * Validate resource
	 * @options - array of resource fields
	 * 
	 **/
	function validate_resource($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		if($is_new){
			//validation rules for adding new record				
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');

			//project type validation rule
			$this->form_validation->set_rules(
				'resource_type', 
				'Resource Type',
				array(
					"required",
					array('validate_resource_type',array($this, 'validate_resource_type')),				
				)				
			);
		}
		elseif ($is_new==false){
			//for updating - only validate fields that are filled in
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]');
			
			if (isset($options['resource_type'])){
				//project type validation rule
				$this->form_validation->set_rules(
					'resource_type', 
					'Resource Type',
					array(
						array('validate_resource_type',array($this, 'validate_resource_type')),				
					)				
				);
			}
		}
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}



	function get_resource_filename($resource_id)
	{
		$row=$this->select_single($resource_id);
		
		if (!isset($row['filename'])){
			throw new Exception("RESOURCE_NOT_FOUND");
		}

		return $row['filename'];
	}

	function delete_resource_file($resource_id)
	{
		$resource_file=$this->get_resource_filename($resource_id);
		$storage_path=$this->DD_project_model->get_datadeposit_storage_path();
		$resource_fullpath=unix_path($storage_path.'/'.$resource_file);

		if (file_exists($resource_fullpath)){
			unlink($resource_fullpath);
		}
		
		return true;
	}


	/**
	 * 	
	 *
	 * upload external resource file
	 *
	 * @id - project id
	 * @file_field_name 	- name of POST file variable
	 *  
	 **/ 
	function upload_file($pid,$file_field_name='file')
	{
		if(!$this->DD_project_model->project_folder_exists($pid)){
			//try creating the folder
			$this->DD_project_model->setup_project_folder($pid);
		}

		$resource_folder=$this->DD_project_model->get_project_path_full($pid);

		if (!file_exists($resource_folder)){
			throw new Exception('PROJECT_STORAGE_FOLDER_NOT_FOUND');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $resource_folder;
		$config['overwrite'] = false;
		$config['encrypt_name']=false;
		$config['allowed_types'] = str_replace(",","|",$this->config->item("allowed_resource_types"));
		
		$this->load->library('upload', $config);
		$upload_result=$this->upload->do_upload($file_field_name);

		if (!$upload_result){
			throw new Exception($this->upload->display_errors('',''));
		}

		return $this->upload->data();		
	}


	/**
	 * 
	 * 
	 * Check if a resource file already exists
	 * 
	 * 
	 */
	function resource_file_exists($project_id,$filename)
	{	
		$this->db->select("id");
		$this->db->where('pid',$project_id);
		$this->db->where('filename',$filename);
		$result=$this->db->get("dd_resources")->row_array();

		if(isset($result['id'])){
			return $result['id'];
		}

		return false;
	}


	////////////////////////////////////////////////////////////////////////////////////////////


	

	


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