<?php

require(APPPATH.'/libraries/REST_Controller.php');

class Datadeposits extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		die("disabled");
		$this->load->model('Catalog_model'); 	
		$this->load->helper("date");
        $this->load->model('DD_project_model');
		$this->load->model('DD_resource_model');
		$this->load->model('DD_citation_model');
	}


	//return raw json input
	private function raw_json_input()
	{
		$data=$this->input->raw_input_stream;				
		//$data = file_get_contents("php://input");

		if(!$data || trim($data)==""){
			return null;
		}
		
		$json=json_decode($data,true);

		if (!$json){
			throw new Exception("INVALID_JSON_INPUT");
		}

		return $json;
	}


	private function get_api_user_id()
	{
		if(isset($this->_apiuser) && isset($this->_apiuser->user_id)){
			return $this->_apiuser->user_id;
		}

		return false;
	}



	/**
	 * 
	 * Check if user has access to project
	 * 
	 * @is_project_locked - true/false - if set to true, check if user can update project or not
	 * 
	 */
	private function get_user_project_or_die($project_id,$is_project_locked=false)
	{
		$user_id=$this->get_api_user_id();
		$project=$this->DD_project_model->select_single($project_id, $user_id);

		if (!$project){
			throw new Exception("PROJECT_NOT_FOUND"); 
		}

		if($is_project_locked){
			if (strtolower(trim($project['status']))!=='draft'){
				throw new Exception("PROJECT_IS_LOCKED"); 
			}
		}

		return $project;
	}


	/**
	 * 
	 * 
	 * Check project status 
	 * 
	 * User can only edit a project in DRAFT mode
	 * 
	 */
	private function is_project_locked($project_id)
	{
		$status=$this->DD_project_model->get_project_status($project_id);
		if (strtolower(trim($status))=='draft'){
			return false;
		}
		return true;
	}




	
	/////////////////////////////////// PROJECT ///////////////////////////////


	/** 
	 * 
	 * List all projects or return a single project if @pid is provided
	 * 
	 * @pid - project id
	 * 
	*/
    public function index_get($pid=null)
    {
		//return a single project if project id is passed
		if ($pid){
			return $this->project_get($pid);
		}

		$user_id=$this->get_api_user_id();   

		//get all projects for current user
        $projects=$this->DD_project_model->get_projects($user_id);

        //convert date fields to GMT
		array_walk($projects, 'unix_date_to_gmt',array('created','changed'));
        
        $response=array(
            'items'=>$projects
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
    }


	/** 
	*
	* Return a single project 
	*
	* @pid - project ID
	*/
	public function project_get($pid=null,$user_id=null)
    {
		try{

			if (!$pid){
				throw new Exception("MISSING_PARAM: PID"); 
			}

			$project=$this->get_user_project_or_die($pid);

			//format dates
			array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'project'=>$project
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
	


	
	/**
	* 
	* 
	* Create new project
	* 
	*/
    public function index_post()
    {
		try{
			//post data
			//$options=$this->input->post();

			//raw data
			$options=$this->raw_json_input();

			$user_id=$this->get_api_user_id();
			$options['created_by']=$user_id;
			$options['changed_by']=$user_id;

			//validate 
			if ($this->DD_project_model->validate_project($options)){
				$project_id=$this->DD_project_model->insert($options);
				$project=$this->DD_project_model->select_single($project_id);

				$response=array(
					'status'=>'success',
					'project'=>$project
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	
	/**
	 * 
	 * Update project
	 * 
	 * Note: PUT method does work with formData
	 * 
	 * 
	 */
	function index_put($project_id=null)
	{
		try{
			if (!$project_id){
				throw new Exception("MISSING_PARAM: PID"); 
			}

			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			$options['changed_by']=$user_id;
						
			$project=$this->get_user_project_or_die($project_id);
					
			//validate 
			if ($this->DD_project_model->validate_project($options,$is_new=false)){
				//update project
				$this->DD_project_model->update($project_id,$options);
				//reload project
				$project=$this->DD_project_model->select_single($project_id);

				$response=array(
					'status'=>'success',
					'project'=>$project
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Delete a project
	 * 
	 * @id	project ID
	 * 
	 */
	function index_delete($id=null)
	{
		try{
			if (!$id){
				throw new Exception("MISSING_PARAM: PID"); 
			}

			$project=$this->get_user_project_or_die($pid);

			$this->DD_project_model->delete($id);
			$result=array(
				'status'=>'success',
				'message'=>'project deleted successfully!!!'
			);
			$this->set_response($result, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	
	

	/** 
	 * 
	 * 
	 * Submit project
	 * 
	 * Note: Submitting a project, locks the project for any further edits
	 * 
	 *
	*
	*/
   function submit_put($project_id=null)
   {
	   try{
		   if (!$project_id){
			   throw new Exception("MISSING_PARAM: PID"); 
		   }

		   $user_id=$this->get_api_user_id();
		   $options['changed_by']=$user_id;
					   
		   $project=$this->get_user_project_or_die($project_id,true);
				   
		   //submit project
		   if ($this->DD_project_model->submit_project($project_id,$email_notifications=true)){
			   //reload project
			   $project=$this->DD_project_model->select_single($project_id);

			   $response=array(
				   'status'=>'success',
				   'project'=>$project
			   );

			   $this->set_response($response, REST_Controller::HTTP_OK);
		   }
	   }
	   catch(Exception $e){
		   $error_output=array(
			   'status'=>'error',
			   'message'=>$e->getMessage()
		   );
		   $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
	   }
   }


   /** 
	 * 
	 * 
	 * update project status
	 * 
	 * TODO: to be removed. Added for testing the api
	 * 
	 *
	*
	*/
	function project_status_put($project_id=null,$status=null)
	{
		try{
			if (!$project_id){
				throw new Exception("MISSING_PARAM: PROJECT_ID"); 
			}
			if (!$status){
				throw new Exception("MISSING_PARAM: STATUS"); 
			}
 
			$user_id=$this->get_api_user_id();
			$options['changed_by']=$user_id;
						
			$project=$this->get_user_project_or_die($project_id);
					
			//submit project
			if ($this->DD_project_model->set_project_status($project_id,$status,$options)){
				//reload project
				$project=$this->DD_project_model->select_single($project_id);
 
				$response=array(
					'status'=>'success',
					'project'=>$project
				);
 
				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Update project access policy
	 * 
	 * 
	 */
	function access_policy_put($project_id=null)
	{
		try{
			if (!$project_id){
				throw new Exception("MISSING_PARAM: PID"); 
			}

			$project=$this->get_user_project_or_die($project_id);

			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			$options['changed_by']=$user_id;						
					
			//validate 
			if ($this->DD_project_model->validate_project($options,$is_new=false)){
				//update project
				$this->DD_project_model->update($project_id,$options);
				//reload project
				$project=$this->DD_project_model->select_single($project_id);

				$response=array(
					'status'=>'success',
					'project'=>$project
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * get project access policy
	 * 
	 * 
	 */
	function access_policy_get($project_id=null)
	{
		try{
			if (!$project_id){
				throw new Exception("MISSING_PARAM: PID"); 
			}
			
			$project=$this->get_user_project_or_die($project_id);

			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			$options['changed_by']=$user_id;
			
			$access_policy=$this->DD_project_model->get_access_policy_info($project_id,$user_id);
					
				$response=array(
					'status'=>'success',
					'access_policy'=>$access_policy
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/** 
	*
	*
	* Get projects by user
	*
	*
	*
	*	
	*/
	public function user_projects_get($user_id=null)
    {
		try{		
			//get current user ID
			if(!$user_id){
				$user_id=$this->get_api_user_id();
			}

			$projects=$this->DD_project_model->get_user_projects($user_id);

			if (!$project){
				throw new Exception("PROJECT_NOT_FOUND"); 
			}

			//convert date fields to GMT
			array_walk($projects, 'unix_date_to_gmt',array('created','changed'));
			
			$response=array(
				'items'=>$projects
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}

    }





	/**
	 * 
	 * 
	 * Update project metadata
	 * 
	 * 
	 * 
	 * */
    public function metadata_post($project_id=null)
    {
		try{
			//raw data
			$data = file_get_contents("php://input");
			
			if (trim($data)==""){
				throw new Exception("NO_INPUT_DATA");
			}

			//decode to JSON to validate JSON is valid
			$json=json_decode($data,true);
			if (!$json){
				throw new Exception("INVALID_JSON_INPUT");
			}
			$project=$this->get_user_project_or_die($project_id);

			//store JSON as is - no encoding/decoding
			$this->DD_project_model->set_metadata($project_id,$data);
			$this->set_response($json, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	

	/** 
	 * 
	 * 
	 * Get project metadata
	 * 
	 * 
	 */
    function metadata_get($project_id)
	{
		try{
			$project=$this->get_user_project_or_die($project_id);
			$metadata=$this->DD_project_model->get_metadata($project_id);

			//when there is no metadata
			if (trim($metadata)==""){
				$metadata='{}';
			}

			header('Content-Type: application/json');
			echo $metadata;
			die();
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}

	}


	///////////////////////////////////////////////////////////////////////
	/// CITATIONS
	///////////////////////////////////////////////////////////////////////
	/**
	 * 
	 *  [GET] 	citations - list of resources
	 * 	[POST]  citations - upload one or more files
	 * 	[PUT]	citations/id - update resource metadata - no file uploads
	 *  [GET] 	citations/id - Get a single resource
	 * 	[DELETE] citations/id - delete a single resource + attachment
	 * 
	 * 
	 */

    /** 
	 * 
	 * Get project citations
	 * 
	 * 
	 */
    public function citations_get($project_id=null, $citation_id=null)
    {
		//get single resource if resource id is provided
		if(is_numeric($project_id) && is_numeric($citation_id)){
			return $this->citation_get($project_id,$citation_id);
		}
		
		try{
			$project=$this->get_user_project_or_die($project_id);
            $citations=$this->DD_citation_model->get_project_citations($project_id);
            array_walk($citations, 'unix_date_to_gmt',array('created','changed'));
            $response=array(
                'items'=>$citations
            );

            $this->set_response($response, REST_Controller::HTTP_OK);    
        }
        catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 * Get a single citation
	 * 
	 * 
	 * 
	 */
	public function citation_get($project_id=null,$citation_id=null)
	{
		try{
			$project=$this->get_user_project_or_die($project_id);
			$citation=$this->DD_citation_model->get_project_single_citation($project_id,$citation_id);
			$this->set_response($citation, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()			
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }
	

    /**
	 * 
	 * 
	 * 
	 * create new citation for the project
	 * 
	 * 
	 * 
	 * */
    public function citations_post($project_id=null)
    {
		try{
			$project=$this->get_user_project_or_die($project_id);			
			$options['citation']=$this->raw_json_input();
			$options['pid']=$project_id;
			$options['created_by']=$this->get_api_user_id();
			$options['changed_by']=$this->get_api_user_id();

			//store JSON as is - no encoding/decoding
			$citation_id=$this->DD_citation_model->insert($options);
			$citation=$this->DD_citation_model->get_project_single_citation($project_id,$citation_id);

			$output=array(
				'status'=>'success',
				'citation'=>$citation
			);

			$this->set_response($output, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }

	/**
	 * 
	 *  Update a single citation
	 * 
	 */
    public function citations_put($pid=null,$cid=null)
    {
		try{			
			$project=$this->get_user_project_or_die($pid);
			
			if(!$cid){
				throw new Exception("CITATION_ID_NOT_SET");
			}

			$citation=$this->DD_citation_model->get_project_single_citation($pid,$cid);
			
			if(!$citation){
				throw new Exception("CITATION_NOT_FOUND");
			}

			$options['citation']=$this->raw_json_input();			
			$options['citation']=$json;
			$options['pid']=$pid;			
			$options['changed_by']=$this->get_api_user_id();

			//update
			$this->DD_citation_model->update($cid,$options);
			$citation=$this->DD_citation_model->get_project_single_citation($pid,$cid);
			
			$output=array(
				'status'=>'success',
				'citation'=>$citation
			);

			$this->set_response($output, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 * 
	 * Delete a citation
	 * 
	 */
    public function citations_delete($pid=null,$cid=null)
    {
		try{
			$project=$this->get_user_project_or_die($pid);

			if(!$cid){
				throw new Exception("CITATION_ID_NOT_SET");
			}
			
			//check citations exists for the project
			$citation=$this->DD_citation_model->get_project_single_citation($pid,$cid);

			if(!$citation){
				throw new Exception('CITATION_NOT_FOUND');
			}
            
			$this->DD_citation_model->delete($cid);
			
			$response=array(
				'status'=>'success',
				'message'=>'Citation removed'
			);
            $this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	

	///////////////////////////////////////////////////////////////////////
	/// RESOURCES
	///////////////////////////////////////////////////////////////////////
	/**
	 * 
	 *  [GET] 	Resources - list of resources
	 * 	[POST]  Resources - upload one or more files
	 * 	[PUT]	Resources/id - update resource metadata - no file uploads
	 *  [GET] 	Resources/id - Get a single resource
	 * 	[DELETE] Resources/id - delete a single resource + attachment
	 * 
	 * 
	 */

    
    /**
	 * 
	 * 
	 * Get all resources by the project
	 * 
	 * @pid - (required) project ID
	 * @resource_id	- (optional) resource ID
	 * 
	 **/ 
    public function resources_get($pid=null, $resource_id=null)
    {
		//get single resource if resource id is provided
		if(is_numeric($pid) && is_numeric($resource_id)){
			return $this->resource_get($pid,$resource_id);
		}		
		
        try{
			$project=$this->get_user_project_or_die($pid);			
            $resources=$this->DD_resource_model->get_project_resources($pid);
			
			array_walk($resources, 'unix_date_to_gmt',array('created','changed'));
			
			$response=array(
                'items'=>$resources
            );

            $this->set_response($response, REST_Controller::HTTP_OK);    
        }
        catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 * Get a single resource
	 * 
	 * 
	 * 
	 */
	public function resource_get($project_id=null,$resource_id=null)
	{
		try{
			$project=$this->get_user_project_or_die($project_id);
			
			$resource=$this->DD_resource_model->get_project_single_resource($project_id,$resource_id);
			$this->set_response($resource, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()			
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 * 
	 * Add new resource by uploading a document
	 * 
	 * 
	 */
	function resources_post($pid=null)
	{
		try{
			if(!$pid){
				throw new Exception("PROJECT_ID_NOT_SET");
			}
			$project=$this->get_user_project_or_die($pid);			
			
            $options=$this->input->post();
            $user_id=$this->get_api_user_id();
            $options['created_by']=$user_id;
            $options['changed_by']=$user_id;
            $options['pid']=$pid;

            //process file upload
			$result=$this->DD_resource_model->upload_file($pid,$file_field_name='file');
			
			$uploaded_file_name=$result['file_name'];
			$uploaded_path=$result['full_path'];			

            $options['filename']=$uploaded_file_name;

            //title is required
            if(!isset($options['title'])){
                $options['title']=$uploaded_file_name;                
            }

            //type is required
            if(!isset($options['resource_type'])){
                $options['resource_type']='other';                
            }

            //validate 
            if ($this->DD_resource_model->validate_resource($options)){
                $resource_id=$this->DD_resource_model->insert($options);
                $resource=$this->DD_resource_model->select_single($resource_id);

                $response=array(
                    'status'=>'success',
                    'resource'=>$resource,
                    'uploaded_file_name'=>$uploaded_file_name,
                );

                $this->set_response($response, REST_Controller::HTTP_OK);
            }
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors(),
				'supported_file_types'=>str_replace(",","|",$this->config->item("allowed_resource_types"))
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage(),
				'supported_file_types'=>$this->config->item("allowed_resource_types")
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	 * 
	 * 
	 * Update a single project resource
	 * 
	 */
    public function resources_put($pid=null, $resource_id=null){
		try{
			if(!$pid){
				throw new Exception("PROJECT_ID_NOT_SET");
			}
			$project=$this->get_user_project_or_die($pid);
			
			if(!$resource_id){
				throw new Exception("RESOURCE_ID_NOT_SET");
			}
			
			//check resource exists for the project
			$resource=$this->DD_resource_model->get_project_single_resource($pid,$resource_id);

			if(!$resource){
				throw new Exception('RESOURCE_NOT_FOUND');
			}
            
            $options=$this->raw_json_input();
            $user_id=$this->get_api_user_id();
            $options['created_by']=$user_id;
            $options['changed_by']=$user_id;
            $options['pid']=$pid;


            //validate 
            if ($this->DD_resource_model->validate_resource($options,$is_new=false)){
                $this->DD_resource_model->update($resource_id,$options);
                $resource=$this->DD_resource_model->select_single($resource_id);

                $response=array(
                    'status'=>'success',
                    'resource'=>$resource
                );

                $this->set_response($response, REST_Controller::HTTP_OK);
            }
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	
	/**
	 * 
	 * Delete a single resource
	 * 
	 */
    public function resources_delete($pid=null,$resource_id=null){

        try{
			if(!$pid){
				throw new Exception("PROJECT_ID_NOT_SET");
			}
			$project=$this->get_user_project_or_die($pid);
			
			if(!$resource_id){
				throw new Exception("RESOURCE_ID_NOT_SET");
			}
			
			//check resource exists for the project
			$resource=$this->DD_resource_model->get_project_single_resource($pid,$resource_id);

			if(!$resource){
				throw new Exception('RESOURCE_NOT_FOUND');
			}
            
			$this->DD_resource_model->delete($resource_id);
			
			$response=array(
				'status'=>'success',
				'message'=>'Resource removed'
			);
            $this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }
    
    
    /////////////// REVIEW ///////////////////////////
    public function review_get($pid)
    {

    }



    /////////////// CONTROLLED  VOCABULARIES ///////////////////

    //return a list of all access policies
    public function list_access_policies_get()
    {

    }

    //list of available catalogs for publishing the project
    public function list_target_catalogs_get()
    {

    }

    //list project types
    public function list_project_types_get()
    {

	}
		
	
}
