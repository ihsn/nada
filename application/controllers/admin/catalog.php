<?php
/**
 * Catalog Maintenance Controller
 *
 * handles all Catalog Maintenance pages
 *
 * @package		NADA 3.1
 * @author		Mehmood Asghar
 * @link		http://ihsn.org/nada/
 */
class Catalog extends MY_Controller {

	var $active_repo=NULL; //active repo object
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Catalog_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		$this->load->helper("catalog");
		$this->template->set_template('admin');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');

		//$this->output->enable_profiler(TRUE);	
		
		//test if active repo is set
		if (!$this->acl->user_active_repo())
		{
			redirect('admin/repositories/select');
		}
		else
		{
			//get repository basic info
			$repo_obj=$this->acl->get_repo($this->acl->user_active_repo());
			
			//set active repo
			$this->active_repo=$repo_obj;
		}
	}
 
	/**
	 * Default page
	 *
	 */
	function index(){	

		//css files
		$this->template->add_css('themes/admin/catalog_admin.css');
		
		//js files
		$this->template->add_js('var site_url="'.site_url().'";','embed');
		$this->template->add_js('javascript/catalog_admin.js');
		
		//js & css for jquery window 
		//$this->template->add_css('javascript/ceebox/css/ceebox.css');
		//$this->template->add_js('javascript/ceebox/js/jquery.ceebox.js');
		
		//set filter on active repo
		if (isset($this->active_repo) && $this->active_repo!=null)
		{
			//$filter=$this->Catalog_model->filter;
//			$this->Catalog_model->filter['repositoryid=']=$this->active_repo->repositoryid;
			$this->Catalog_model->active_repo=$this->active_repo->repositoryid;
		}
		
		//get citations		
		$db_rows=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('catalog/index', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 * @return string
	 * @author IHSN
	 **/
	function _search()
	{
		//records to show per page
		$per_page = $this->input->get("ps");
		
		if($per_page===FALSE || !is_numeric($per_page))
		{
			$per_page=15;
		}
				
		//current page
		$curr_page=$this->input->get('per_page');//$this->uri->segment(4);

		//filter to further limit search
		$filter=array();
		
		/*if (isset($this->active_repo) && $this->active_repo!=null)
		{
			$filter=array('repositoryid'=>$this->active_repo->repositoryid);
		}*/
		
		//records
		$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);

		//total records in the db
		$total = $this->Catalog_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Catalog_model->search($per_page, $curr_page,$filter);
		}
		
		//set pagination options
		$base_url = site_url('admin/catalog');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('sort_by','sort_order','keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $data;		
	}
	
	
	/**
	* returns a list of citations without the site template
	*
	* NOTE: to be used for loading citations using ajax
	* TODO://check if not used, remove it
	*/
	function getcitations()
	{
		$db_rows=$this->_search();
		
		//get citations		
		$db_rows=$this->_search();
		
		//hides the search form
		$db_rows['hide_form']=TRUE;
		
		//render the page without the template
		$this->load->view('catalog/index', $db_rows);
	}
		
	/**
	 * Survey - get survey row by id
	 *
	 * @return array
	 **/
	function survey($id=NULL)
	{
		if ( !is_numeric($id) )
		{
			show_error('Invalid parameters were passed');
		}
		
		$active_repository=FALSE;
		//get active repository
		if (isset($this->active_repo) && $this->active_repo!=NULL)
		{
			$active_repository=$this->active_repo->repositoryid;
		}
		
		//get the survey info from db
       	$survey_row=$this->Catalog_model->select_single($id,$active_repository);
		
		//check if survey has citations
		$survey_row['has_citations']=$this->Catalog_model->has_citations($id);
		
		if (!empty($survey_row) )
		{
			$this->load->view('catalog/survey_info', $survey_row);
		}
		else
		{
			show_error('Survey was not found');
		}
	}
	
	

	/**
	*
	* Enable/Disable ddi sharing
	*
	* NOTE: updated through ajax request and no output is needed 
	*/
	function shareddi($surveyid=NULL,$share=true)
	{
		if (!is_numeric($surveyid))
		{
			show_404();
		}
		
		if (!is_numeric($share))
		{
			show_404();
		}
		
		$options=array('id'=>$surveyid, 'isshared'=>$share);	
		
		//update db
		$result=$this->Catalog_model->update_survey_options($options);
		
		if($result)
		{
			echo 'updated';
		}
	}
	

	/**
	 * upload files to survey folder
	 * used for uploading survey reports,technical docs
	 * 
	 * TODO: NO LONGER IN USE. REMOVE
	 *
	 * @return array
	 */
	function __upload_file($key,$destination)
	{
		if ($_FILES[$key]['size']==0)
		{
			return false;
		}
		
		$config['upload_path'] = $destination;
		$config['disallowed_types'] = 'exe|php|js|asp|aspx';
		$config['overwrite'] = true;
		
		$this->load->library('upload', $config);
	
		if ( ! $this->upload->do_upload($key))
		{
			//failed
			throw new Exception( $this->upload->display_errors() );
		}	
		else
		{
			$data = $this->upload->data();
			return $data;
		}
	}



	/**
	 * Upload form for DDI (xml) file
	 *
	 * @return void
	 **/
	function upload()
	{		
		$this->template->set_template('admin');			
		$this->load->library('upload');
	
		 //load the upload form
		$content=$this->load->view('catalog/ddi_upload_form', NULL,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	


	/**
	 * Process uploaded DDI file
	 *
	 * @return void
	 **/	
	function do_upload()
	{	
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//if not fixed path, use a relative path
		if (!file_exists($catalog_root) )
		{
			$catalog_root=FCPATH.$catalog_root;
		}		
		
		//upload class configurations
		$config['upload_path'] = $catalog_root;
		$config['overwrite'] = TRUE; //overwrite the file, if already exists
		$config['allowed_types'] = '*'; //format: xml|zip
		
		//load upload library
		$this->load->library('upload', $config);
		
		//process uploaded file
		$upload_result=$this->upload->do_upload();
		
		if (!$upload_result)
		{
			//get errors while uploading
			$error = $this->upload->display_errors();
			
			//log to database
			$this->db_logger->write_log('ddi-upload',$error,'catalog');

			//set error
			$this->session->set_flashdata('error', $error);
			
			//redirect back to the upload page
			redirect('admin/catalog/upload','refresh');
		}	
		else //successful upload
		{			
			//get uploaded file information
			$data = array('upload_data' => $this->upload->data());									
			$ddi_progress['ddi_progress']=array('status'=>'uploaded', 'upload_data'=>$this->upload->data());
			
			//log to database
			$this->db_logger->write_log('ddi-upload','success','catalog');
			
			//save progress to session
			$this->session->set_userdata($ddi_progress);
			
			$this->import();
		}
	}

	/**
	* Imports an uploaded DDI file or batch import
	*
	*/
	function __replace_ddi()
	{
		//survey id
		$survey_id=$this->uri->segment(4);
				
		if (!is_numeric($survey_id))
		{
			show_error("INVALID_SURVEY_PARAM");
		}
		
		//get survey info
		$survey=$this->Catalog_model->select_single($survey_id);
				
		if (!$survey)
		{
			show_error("SURVEY_NOT_FOUND");
		}
		
		//get ddi path
		$survey_ddi_path=$this->Catalog_model->get_survey_path_full($survey_id);
		
		//get uploaded file information
		$data = array('upload_data' => $this->upload->data());									
		$session_data=array('status'=>'uploaded', 'upload_data'=>$this->upload->data());
		$this->db_logger->write_log('ddi-replace','success','catalog');
		
		$ddi_file=$session_data['upload_data']['full_path'];
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			//log import error
			$error= t('invalid_ddi_file').' '.$ddi_file;
			log_message('error', $error);
			$this->db_logger->write_log('ddi-import',$error,'catalog');
			$error.=$this->load->view('catalog/upload_file_info', $session_data, true);
			$this->session->set_flashdata('error', $error);
			
			redirect('admin/catalog/replace','refresh');
		}
						
		//parse ddi to array	
		$data=$this->ddi_parser->parse();

		//overwrite?
		$overwrite=TRUE;
		
		//get repository ownership details for the study
		$survey_repo_arr=$this->Catalog_model->get_repo_ownership($survey_id);
		
		//repository
		$repositoryid="central";;
		$repositoryid=$survey["repositoryid"];

		/*if (count($survey_repo_arr)>0)
		{
			$repositoryid=$survey["repositoryid"];
		}*/		
		
		/*		
		//validate if user has access to the selected repository
		$user_repositories=$this->ion_auth->get_user_repositories();
				
		$user_repo_access=FALSE;
		foreach($user_repositories as $repo)
		{
			if ($repo["repositoryid"]==$repositoryid)
			{
				$user_repo_access=TRUE;
				break;
			}
		}
		
		if ($user_repo_access===FALSE)
		{
			show_error(t("REPO_ACCESS_DENIED"));
		}
		*/
		
		//set the repository where the ddi will be uploaded to	
		$this->DDI_Import->repository_identifier=$repositoryid;
						
		//import to db
		$result=$this->DDI_Import->replace($data,$ddi_file,$target_survey_id=$survey_id);

		if ($result===TRUE)
		{
			//display import success 
			$success=$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']),true);
			$success_msg='Survey imported - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables';
			log_message('DEBUG', $success_msg);
			$this->db_logger->write_log('ddi-import',$success_msg,'catalog');
			$this->session->set_flashdata('message', $success);
			
			//delete uploaded file
			@unlink($ddi_file);
			
			//remove old ddi file
			@unlink($survey_ddi_path);
			log_message('INFO', "removed old ddi file: ".$survey_ddi_path);
			
			//update survey info
			$survey_options=array(
						'id'=>$survey_id,
						'published'=>$survey['published']
						);
			
			$this->Catalog_model->update_survey_options($survey_options);
			log_message('INFO', "updated survey options: ".$survey_ddi_path);
		}
		else
		{
			$failed_msg='FAILED - Survey import - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables';
			log_message('ERROR', $failed_msg);		
			$this->db_logger->write_log('ddi-import',$failed_msg,'catalog');			
			$import_failed=$this->load->view('catalog/ddi_import_fail', array('errors'=>$this->DDI_Import->errors),TRUE);
			$this->session->set_flashdata('error', $import_failed);
		}		

		//remove session
		$this->session->unset_userdata('ddi_progress');
		
		//redirect
		redirect('admin/catalog/replace_ddi/'.$survey_id,'refresh');
	}



	/**
	* Imports an uploaded DDI file or batch import
	*
	*/
	function import()
	{
		$session_data=$this->session->userdata('ddi_progress');
		
		if (!is_array($session_data) )
		{
			show_error(t('nothing_to_process'));
		}
		
		$ddi_file=$session_data['upload_data']['full_path'];
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;
		
		//only available for xml_reader
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			//log import error
			$error= t('invalid_ddi_file').' '.$ddi_file;
			log_message('error', $error);

			//log to database
			$this->db_logger->write_log('ddi-import',$error,'catalog');

			$error.=$this->load->view('catalog/upload_file_info', $session_data, true);			

			$this->session->set_flashdata('error', $error);
			redirect('admin/catalog/upload','refresh');
		}
						
		//parse ddi to array	
		$data=$this->ddi_parser->parse();

		//overwrite?
		$overwrite=(bool)$this->input->post("overwrite");
		
		//repository
		$repositoryid=$this->input->post("repositoryid");
		
		//validate if user has access to the selected repository
		$user_repositories=$this->ion_auth->get_user_repositories();
		
		$user_repo_access=FALSE;
		foreach($user_repositories as $repo)
		{
			if ($repo["repositoryid"]==$repositoryid)
			{
				$user_repo_access=TRUE;
				break;
			}
		}
		
		if ($user_repo_access===FALSE)
		{
			show_error(t("REPO_ACCESS_DENIED"));
		}
		
		//set the repository where the ddi will be uploaded to	
		$this->DDI_Import->repository_identifier=$repositoryid;
						
		//import to db
		$result=$this->DDI_Import->import($data,$ddi_file,$overwrite);

		if ($result===TRUE)
		{
			//display import success 
			$success=$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']),true);
			$success_msg='Survey imported - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables';
			log_message('DEBUG', $success_msg);
			
			//log
			$this->db_logger->write_log('ddi-import',$success_msg,'catalog');

			$this->session->set_flashdata('message', $success);
			
			//delete uploaded file
			unlink($ddi_file);
		}
		else
		{
			$failed_msg='FAILED - Survey import - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables';
			
			//log
			log_message('DEBUG', $failed_msg);
			$this->db_logger->write_log('ddi-import',$failed_msg,'catalog');
			
			$import_failed=$this->load->view('catalog/ddi_import_fail', array('errors'=>$this->DDI_Import->errors),TRUE);
			$this->session->set_flashdata('error', $import_failed);
		}		

		//remove session
		$this->session->unset_userdata('ddi_progress');
		redirect('admin/catalog/upload','refresh');
	}
	
	
	/**
	*
	* Refresh DDI Information in the database
	* 
	* Note: Useful for updating study information in the database for existing DDIs
	**/
	function refresh($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_404();
		}
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//get survey ddi file path by id
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);
		
		if ($ddi_file===FALSE)
		{
			show_error('DDI_NOT_FOUND');
		}
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;
		
		//only available for xml_reader
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			$error= 'Invalid DDI file: '.$ddi_file;
			log_message('error', $error);
			$error.=$this->load->view('catalog/upload_file_info', $session_data, true);			

			$this->session->set_flashdata('error', $error);
			redirect('admin/catalog','refresh');
		}
						
		//parse ddi study to array	
		$data['study']=$this->ddi_parser->get_study_array();

		//pass study data
		$this->DDI_Import->ddi_array=$data;			
		
		//import to study data to db
		$result=$this->DDI_Import->import_study();

		//import failed
		if ($result===FALSE)
		{
			log_message('DEBUG', 'FAILED - Survey import - <em>'. $data['study']['id']. '</em>');
			$import_failed=$this->load->view('catalog/ddi_import_fail', array('errors'=>$this->DDI_Import->errors),TRUE);
			$this->session->set_flashdata('error', $import_failed);
		}

		//display import success 
		$success=$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']),true);
		log_message('DEBUG', 'Survey imported - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables');
			
		$this->session->set_flashdata('message', $success);			
		redirect('admin/catalog','refresh');		
	}
	

	/**
	 * Imports multiple ddi files from the server folder
	 *
	 * 
	 * @return void
	 **/
	function batch_import()
	{
		$this->load->helper('file');
		
		//import folder path
		$import_folder=$this->config->item('ddi_import_folder');
		
		if (!file_exists($import_folder) )
		{
			$import_folder="/datasets";
		}
		
		//read files
		$files['files']=get_dir_file_info($import_folder);

		if ( $files['files'])
		{
			foreach($files['files'] as $key=>$value)
			{
				//var_dump($value);exit;
				if (substr($value['name'],-4)!='.xml') 
				{
					unset($files['files'][$key]);
				}
			}
		}	

		$content=$this->load->view('catalog/ddi_batch_import', $files, true);	

		$this->template->write('content', $content,true);
	  	$this->template->render();	
	}
	
	/**
	* Imports a ddi file using batch import
	* 
	* returns the output in JSON format
	*/
	function do_batch_import()
	{
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');

		//get the encoded file path from post
		$encoded_filepath=$this->input->post("id");
		$overwrite=(bool)$this->input->post("overwrite");

		//decode
		$ddi_file=base64_decode($encoded_filepath);
		
		//check file exists
		if (!file_exists($ddi_file))
		{
			echo json_encode(array('error'=>t("file_not_found")) );
			exit;
		}

		//repository
		$repositoryid=$this->input->post("repositoryid");
		
		//validate if user has access to the selected repository
		$user_repositories=$this->ion_auth->get_user_repositories();	
		$user_repo_access=FALSE;
		foreach($user_repositories as $repo)
		{
			if ($repo["repositoryid"]==$repositoryid)
			{
				$user_repo_access=TRUE;
				break;
			}
		}
		
		if ($user_repo_access===FALSE)
		{
			//show_error("REPO_ACCESS_DENIED");
			echo json_encode(array('error'=>t('REPO_ACCESS_DENIED')) );
			exit;
		}
		
		//set the repository where the ddi will be uploaded to	
		$this->DDI_Import->repository_identifier=$repositoryid;

		
		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;
		
		//only available for xml_reader
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			$error= t('invalid_ddi_file').$ddi_file;
			log_message('error', $error);
			$this->session->set_flashdata('error', $error);
			
			echo json_encode(array('error'=>$error) );
			exit;
		}
						
		//parse ddi to array	
		$data=$this->ddi_parser->parse();		
				
		//import to db
		$result=$this->DDI_Import->import($data,$ddi_file,$overwrite);
		
		if ($result===TRUE)
		{
			//display import success 
			$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']));
			$msg='<strong>'. $data['study']['titl']. '</strong> - <em>'.$this->DDI_Import->variables_imported.' '.t('variables').'</em>';
			log_message('info', $msg);
		
			//try importing the RDF if exists. The RDF must match the XML file name
			$this->_import_rdf($this->DDI_Import->id,str_replace(".xml",".rdf",$ddi_file));
			
			//return the json success message
			echo json_encode(array('success'=>$msg) );
			exit;

			//delete uploaded file
			//unlink($ddi_file);
		}
		else
		{
			$error=$this->load->view('catalog/ddi_import_fail', array('errors'=>$this->DDI_Import->errors),true);			
			echo json_encode(array('error'=>$error) );
			exit;
		}
	}

	/**
	*
	* Import RDF file
	**/
	function _import_rdf($surveyid,$filepath)
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
		$this->load->model('Resource_model');
			
		//parse RDF to array
		$rdf_array=$this->rdf_parser->parse($rdf_contents);

		if ($rdf_array===FALSE || $rdf_array==NULL)
		{
			return FALSE;
		}

		//Import
		$rdf_fields=$this->rdf_parser->fields;
			
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
			
			//check if it is not a URL
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
				$this->Resource_model->insert($insert_data);				
			}
		}
	}

	function delete($id)
	{	
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/catalog');
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		//test user has permission to delete study or not
		$is_owner=$this->ion_auth->is_study_owner($id);

		if (!$is_owner)
		{
			show_error("MSG_USER_ACCESS_DENIED");
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);
				
				//delete if exists
				if ($survey)
				{
					//delete survey and related data from other tables
					$this->Catalog_model->delete($item);
										
					//log deletion
					$survey_name=$survey['surveyid']. ' - '.$survey['titl'].' - '. $survey['proddate'].' - '. $survey['nation'];
					$this->db_logger->write_log('study-deleted',$survey_name,'catalog',$item);
				}	
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				//redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}	
		}
		else
		{
			$items=array(); //list of deleted items
			
			foreach($delete_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);
				
				//exists
				if ($survey)
				{
					//log deletion
					$survey_name=$survey['surveyid']. ' - '.$survey['titl'].' - '. $survey['proddate'].' - '. $survey['nation'];
					$items[]=$survey_name;
				}	
			}
			
			//ask for confirmation
			$content=$this->load->view('resources/delete', array('deleted_items'=>$items),true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	/**
	*
	* Export External Resources as RDF
	**/
	function export_rdf($id=NULL)
	{	
		$this->load->helper('download');
		$data=$this->Catalog_model->get_survey_rdf($id);
		force_download('rdf-'.$id.'.rdf', $data);
		//application/rdf+xml
	}
	
	
	/**
	*
	* Update survey data collection dates to use the survey_years table
	*
	* TODO://REMOVE
	*/
	function update_years()
	{
		$this->Catalog_model->batch_update_collection_dates();		
	}
	
	
	
	/**
	* Returns survey DDI file
	* as .xml or .zip
	* 
	*/
	function ddi($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_404();
		}
	
		$format=$this->input->get("format");
		
		//required for getting ddi file path
		$this->load->model('Catalog_model');
		$this->load->helper('download');
			
		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);
		
		if ($ddi_file===FALSE)
		{
			show_404();
		}

		if (file_exists($ddi_file))
		{
			if($format=='zip')
			{
				$this->load->library('zip');

				//zip file path
				$zip_file=$ddi_file.'.zip';
			
				//create zip if not created already
				if (!file_exists($zip_file))
				{			
					$this->zip->read_file($ddi_file);
					$this->zip->archive($zip_file); 
				}
				
				//download zip file
				if (file_exists($zip_file))
				{
					force_download2($zip_file);
					return;
				}
			}
			
			//download the xml file
			force_download2($ddi_file);
			return;
		}
		else
		{
			show_404();
		}		
	}



	/**
	*
	* Replace a DDI
	*
	**/	
	function replace_ddi($surveyid=NULL)
	{
	
		if (!is_numeric($surveyid))
		{
			show_error("ID_INVALID");
		}
		
		//atleast one rule require for validation class to work
		$this->form_validation->set_rules('target', t('study_to_replace'), 'trim|required|isnumeric');
		
		if ($this->form_validation->run() == TRUE)
		{
			//catalog folder path
			$catalog_root=$this->config->item("catalog_root");
			
			if (!file_exists($catalog_root) )
			{
				show_error("CATALOG_ROOT_NOT_FOUND");
			}		

			//upload the ddi
			$upload_result=$this->__upload_file($key='userfile',$destination=$catalog_root);

			if (!$upload_result)
			{
				//get errors while uploading
				$error = $this->upload->display_errors();
				
				//log to database
				$this->db_logger->write_log('ddi-upload',$error,'catalog');
	
				//set error
				$this->session->set_flashdata('error', $error);
				
				//redirect back to the upload page
				redirect('admin/catalog/replace_ddi/'.$surveyid,'refresh');
				
				return FALSE;
			}	
			else //successful upload
			{
				$this->__replace_ddi();
			}			
		}

		
		//get a compact list of citations
		$citations=$this->Catalog_model->select_all_compact();
		
		$output=array('"0"'=>'--SELECT--');
		foreach($citations as $row)
		{
			$output[(string)$row['id']]=$row['nation']. ' - '. substr($row['titl'],0,150). '['.$row['surveyid'].']';
		}

		$data['citations']=$output;
		$data['id']=$surveyid;
						
		$content=$this->load->view('catalog/replace_ddi',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	
	
	
	/**
	*
	* Export citations as serialized array
	*
	**/
	function export_citations($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_404();
		}
		
		$this->load->model('Citation_model');
		
		//get citations by survey id
		$citations=$this->Citation_model->serialize_citations_by_survey($id);
		
		echo $citations;
	}
	
	
	function copy_study()
	{	
		//css files
		$this->template->add_css('themes/admin/catalog_admin.css');
		
		//js files
		$this->template->add_js('var site_url="'.site_url().'";','embed');
		
		//set filter on active repo
		if (isset($this->active_repo) && $this->active_repo!=null)
		{
			//$filter=$this->Catalog_model->filter;
			$this->Catalog_model->active_repo=$this->active_repo->repositoryid;
			$this->Catalog_model->active_repo_negate=TRUE;
		}
		
		//get citations		
		$db_rows=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('catalog/copy_studies', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	function do_copy_study($repositoryid=NULL,$sid=NULL)
	{
		if ($repositoryid==NULL || !is_numeric($sid))
		{
			show_404();
		}
	
		$this->Catalog_model->copy_study($repositoryid,$sid);
		echo json_encode(array('success'=>$repositoryid));
		exit;
	}
	
	
	/**
	*
	* Transfer Ownership of a study to another catalog
	*
	* @surveyid  number | string in case of multiple IDs seperated by comma
	**/
	function transfer($surveyid=NULL)
	{
		if ($surveyid==NULL && !$this->input->post("sid"))
		{
			show_error("PARAM_MISSING");
		}
		
		//active repositoryid
		$active_repo=$this->active_repo->repositoryid;

		if (!$active_repo)
		{
			show_error("NO_ACTIVE_REPO_SET");
		}

		if ($this->input->post("sid"))
		{
			$citations_arr=$this->input->post("sid");
		}
		else
		{	
			$citations_arr=explode(",",$surveyid);
		}		
		
		$citations=array();
		
		//get survey info by id
		foreach($citations_arr as $id)
		{
			if (is_numeric($id))
			{
				$survey_row=$this->Catalog_model->get_survey($id);
				if ($survey_row)
				{
					$citations[$id]=$this->Catalog_model->get_survey($id);
				}	
			}	
		}
		
		//postback?
		if ($this->input->post("submit"))
		{
			$repositoryid=$this->input->post("repositoryid");
			
			//validate repository
			$exists=$this->Catalog_model->repository_exists($repositoryid);			

			if (!$exists)
			{
				$this->form_validation->set_error(t('error_invalid_repositoryid'));
			}
			
			foreach($citations as $key=>$value)
			{
				//transfer ownership
				$this->Catalog_model->transfer_ownership($repositoryid,$key);				
			}
			$this->session->set_flashdata('message', t('msg_study_ownership_has_changed'));
			redirect('admin/catalog');
		}
				
		$content=$this->load->view('catalog/transfer_ownership',array('citations'=>$citations),TRUE);
		$this->template->write('content', $content,true);
  		$this->template->render();
	}
	
	
	/**
	*
	* Unlink a study from a repository
	*
	**/
	function unlink($repositoryid,$surveyid)
	{
		if (!is_numeric($surveyid))
		{
			show_error("INVALID_ID");
		}
		
		$result=$this->Catalog_model->unlink_study($repositoryid,$surveyid);
		
		if ($result!==FALSE)
		{
			$content='Study link was removed successfully!';
		}
		else
		{
			$content='Error: Failed to remove study link';
		}

		$this->session->set_flashdata('message', $content);
		
		redirect('admin/catalog');
		return;
		/*
		$content=$this->load->view('catalog/study_unlink_confirm',array('result'=>$result),TRUE);
		$this->template->write('content', $content,true);
  		$this->template->render();*/		
	}
	
	
	/**
	*
	* Attach admin/reviewer note to a study
	**/
	function attach_note($sid,$type)
	{
		//$this->output->enable_profiler(TRUE);	
		if (!is_numeric($sid))
		{
			show_404();
		}
		
		$note=$this->input->post("note");
		
		$result=$this->Catalog_model->attach_note($sid,$note, $note_type=$type);
		
		if ($result)
		{
			$this->output
    			->set_content_type('application/json')
			    ->set_output(json_encode(array('success'=>"updated")));
			return TRUE;	
		}
		
			$this->output
    			->set_content_type('application/json')
			    ->set_output(json_encode(array('error'=>"failed")));
	}
	

	/**
	*
	* Publish/Unpublish studies
	*
	* $id single numeric value or a comma seperated list of IDs
	**/
	function publish($id,$publish=1)
	{	
		if (!in_array($publish,array(0,1)))
		{
			$publish=1;
		}
	
		//array of id to be published
		$id_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$id_arr[]=$value;
				}
			}
			
			if (count($id_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/catalog');
			}	
		}		
		else
		{
			$id_arr[]=$id;
		}
		
		//test user has permissions
		$is_owner=$this->ion_auth->is_study_owner($id);

		if (!$is_owner)
		{
			show_error("MSG_USER_ACCESS_DENIED");
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($id_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);
				
				//if exists
				if ($survey)
				{
					//publish/unpublish a study
					$result=$this->Catalog_model->publish_study($item,$publish);
					
					if ($result)
					{
						$this->session->set_flashdata('message', t('form_update_success'));
					}
					else
					{
						$this->session->set_flashdata('error', t('form_update_failed'));
					}
					
					//log
					$survey_name=$survey['surveyid']. ' - '.$survey['titl'].' - '. $survey['proddate'].' - '. $survey['nation'];
					$this->db_logger->write_log('study-published',$survey_name,'catalog',$item);
				}	
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				//redirect($destination);
			}
			else
			{
				
				redirect('admin/catalog');
			}	
		}
		else
		{
			$items=array(); //list of deleted items
			
			foreach($id_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);
				
				//exists
				if ($survey)
				{
					$survey_name=$survey['surveyid']. ' - '.$survey['titl'].' - '. $survey['proddate'].' - '. $survey['nation'];
					$items[]=$survey_name;
				}	
			}
			
			//ask for confirmation
			$content=$this->load->view('catalog/publish_confirm', array('items'=>$items,'publish'=>$publish),true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		

	}



	/**
	 * Edit survey - by id
	 *
	 * @return void
	 *
	 **/
	function edit($id=NULL)
	{
		$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		$this->template->add_js('javascript/jquery/ui/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
       	$this->load->model('Citation_model');
		$this->load->model('Catalog_Notes_model');
       	$this->load->model('Catalog_Tags_model');
       	$this->load->model('Catalog_Ids_model');
        $this->load->library('ion_auth');
		
		if ( !is_numeric($id) )
		{
			show_error('Invalid parameters were passed');
		}

		$this->load->library("catalog_admin");
		
		$active_repository=FALSE;

		//get active repository
		if (isset($this->active_repo) && $this->active_repo!=NULL)
		{
			$active_repository=$this->active_repo->repositoryid;
		}
		
		//get the survey info from db
       	$survey_row=$this->Catalog_model->select_single($id,$active_repository);
		
		if (!$survey_row)
		{
			show_error('Survey was not found');
		}

		//var_dump($survey_row);
		
		//check if survey has citations
		$survey_row['has_citations']=$this->Catalog_model->has_citations($id);
		
		//get survey files
		$survey_row['files']=$this->catalog_admin->managefiles($id);
		
		//get resources
		//$resources['rows']=$this->catalog_admin->resources($id);		
		//$survey_row['resources']=$this->load->view('catalog/study_resources', $resources,true);
		
		//survey collections for current survey
		$survey_row['collections']=$this->catalog_admin->get_formatted_collections($id);
		
		//formatted list of external resources
		$survey_row['resources']=$this->catalog_admin->resources($id);
		
		if ($id != NULL) {
			$notes['notes'] = $this->Catalog_Notes_model->notes_from_catelog_id($id, 'admin');
			$survey_row['admin_notes']=$this->load->view('catalog/admin_notes', $notes, true);
			$notes['notes'] = $this->Catalog_Notes_model->notes_from_catelog_id($id, 'reviewer');
			$survey_row['reviewer_notes']=$this->load->view('catalog/reviewer_notes', $notes, true);
			$tags['tags'] = $this->Catalog_Tags_model->tags_from_catelog_id($id);
			$survey_row['tags']=$this->load->view('catalog/admin_tags', $tags, true);
			$ids['ids'] = $this->Catalog_Ids_model->ids_from_catelog_id($id);
			$survey_row['ids']=$this->load->view('catalog/admin_ids', $ids, true);
			
//attached survey from the postback data
	//	if ($this->input->post("sid"))
	//	{
			//get the selected citations from sid
			$survey_id_arr=$id;
		
			//get survey info from db
			$selected_citations=$this->Citation_model->get_citations_by_survey($id);
//		}
//		else
			//see if the edited citation has citations attached, otherwise assign empty array
			//$selected_citations=isset($survey_row['related_citations']) ? $survey_row['related_citations'] : array();
			$survey_row['selected_citations_id_arr']=$this->_get_related_citations_array($selected_citations);
			$survey_row['selected_citations'] = $selected_citations;
		}
		

		//data access form list
		$this->load->model('Form_model');
		$this->forms_list=array('0'=>'Select');		
		
		//create a list of choices for the drop down
		foreach($this->Form_model->get_all()  as $value)
		{
			$this->forms_list[$value['formid']]=$value['fname'];
		}

		$content=$this->load->view('catalog/edit_study', $survey_row,TRUE);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}


/*
	*
	* Update various study options
	*/
	function update()
	{		
		//study id
		$id=$this->input->post("sid");
		
		if (!is_numeric($id))
		{
			show_404();
		}
		
		//is ajax call
		$ajax=$this->input->get_post('ajax');
		
		//allowed fields
		$allowed_keys=array('published','formid','link_indicator','link_study','link_da');

		foreach($allowed_keys as $key)
		{
			$options=array();
			$options['id']=$id;
			if ($this->input->post($key)!==FALSE)
			{
					$options[$key]=$this->input->post($key);
					$result=$this->Catalog_model->update_survey_options($options);

					if ($result)
					{
						$this->session->set_flashdata('message', t('form_update_success'));
					}
					else
					{
						$this->session->set_flashdata('error', t('form_update_failed'));
					}
			}
		}
		
		redirect('admin/catalog/edit/'.$id);
	}


	/**
	*
	* Returns formatted selected survey list from session
	**/
	function selected_citations($skey,$isajax=1)
	{
       	$this->load->model('Citation_model');
		//get survey id array from session
		$survey_id_arr=(array)$this->session->userdata($skey);
		//get survey info from db
		$data['selected_citations']=$this->Citation_model->get_citations_by_survey($skey);
		
		//load formatted list
		$output=$this->load->view("catalog/selected_citations",$data,TRUE);
		
		if ($isajax==1)
		{
			echo $output;
		}
		else
		{
			return $output;
		}
		
	}

	/**
	*
	* Returns an array of survey IDs
	*
	**/
	function _get_related_citations_array($citations)
	{
		$result=array();
		foreach($citations as $survey)
		{
			$result[]=$survey['id'];
		}
		return $result;
	}


}
/* End of file catalog.php */
/* Location: ./controllers/admin/catalog.php */