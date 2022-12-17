<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Datadeposits extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Catalog_model'); 	
		$this->load->helper("date");
        $this->load->model('DD_project_model');
		$this->load->model('DD_resource_model');
		$this->load->model('DD_citation_model');
		$this->is_admin_or_die();
	}

	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}

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
        $projects=$this->DD_project_model->all_projects($user_id);
        
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

			$project=$this->DD_project_model->get_by_id($pid);

			//format dates
			array_walk($project, 'unix_date_to_gmt_row',array('created_on','last_modified','submitted_date','administer_date'));

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
			$project=$this->DD_project_model->get_by_id($pid);
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
			$project=$this->DD_project_model->get_by_id($project_id);

			if(!$project){
				throw new Exception("Project not found");
			}
			
			$resource=$this->DD_resource_model->get_project_resource($resource_id);
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

	function download_get($project_id=null,$resource_id=null)
	{
		try{
			$project=$this->DD_project_model->get_by_id($project_id);

			if(!$project){
				throw new Exception("Project not found");
			}

			$user=$this->api_user();

			if(!$user){
				throw new Exception("USER_NOT_LOGGEDIN");
			}

			if(!$resource_id){
				throw new Exception("PARAM_NOT_SET: resource_id");
			}

				//get project data folder path
			$project_folder_path=$this->DD_project_model->get_project_fullpath($project_id);
			
			if (!$project_folder_path){
				throw new Exception("PROJECT_DATA_FOLDER_NOT_SET");
			}
			
			$this->load->helper('download');
			$this->load->model('DD_resource_model');
			$this->lang->load("resource_manager");
			$this->load->model('managefiles_model');
					
			$resource = $this->DD_resource_model->get_project_resource($resource_id);
			
			if (!$resource){
				throw new Exception("FILE_NOT_FOUND");
			}
			
			$resource_path=unix_path($project_folder_path.'/'.$resource[0]->filename);
			
			if (!file_exists($resource_path)){
				throw new Exception("FILE_NOT_FOUND:".$resource_path);
			}

			force_download3($resource_path,$resource[0]->filename);
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	} 
	
	
	public function export_get($format,$id) 
	{
		try{
			$this->load->library('DDI_Study_Export');
			$this->load->model('DD_study_model');
			$this->load->model('DD_resource_model');
			$this->load->helper('download');

			$data['project'] = $this->DD_project_model->project_id2($id,null);

			if (!$data['project']){
				throw  new Exception("Project was not found");
			}

			if ($format == 'ddi') {
				$data['record'] = $this->DD_study_model->get_study_array($id);
				$this->ddi_study_export->load_template('application/templates/ddi_export_template.xml');
				
				$data['data']   = $this->ddi_study_export->to_ddi($data['record']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->id}_".date('d_m_y')."_ddi.xml"));
				force_download($title, $data['data']);
				die();
	
			} else if ($format == 'rdf') {
				$data['files']  = $this->DD_resource_model->get_project_resources($id);
				$data['data']   = $this->_resources_to_RDF($data['files']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->id}_".date('d_m_y')."_rdf.xml"));
				force_download($title, $data['data']);
				die();
			}
			
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	private function _resources_to_RDF($resources) 
	{
		$content = '';;
		$RDF     = "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
		$RDF    .= "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:dcterms=\"http://purl.org/dc/terms/\">" . PHP_EOL;
		foreach ($resources as $resource) {
			$content .= "<rdf:Description rdf:about=\"{$resource['filename']}\">\n<rdf:label>\n{$resource['title']}\n</rdf:label>\n<rdf:title>\n{$resource['title']}\n</rdf:title>\n<dc:format>\n{$resource['dcformat']}\n</dc:format>\n<dc:creator>\n{$resource['author']}\n</dc:creator>\n<dc:type>\n{$resource['dctype']}\n</dc:type>\n<dcterms:created>\n{$resource['created']}\n</dcterms:created>\n<dc:description>\n{$resource['filename']}\n</dc:description>\n</rdf:Description>\n";
		}
		$RDF    .= $content;
		$RDF    .= "</rdf:RDF>";
		return $RDF;
	}

	
}
