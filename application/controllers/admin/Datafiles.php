<?php
class Datafiles extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
       	
        $this->load->helper(array('url'));		
		//$this->load->library( array('pagination') );
		
		$this->load->model('Form_model');
		$this->load->model('Datafiles_model');
		$this->load->model('Catalog_model');

       	$this->template->set_template('blank');		
		//$this->output->enable_profiler(TRUE);
    }
    
    public function index($surveyid=NULL)		
    {    			
		//validate survey
		if (!is_numeric($surveyid))
		{
			echo 'invalid id provided';exit;
			//show_404();
		}
		
		//survey folder  path
		$this->survey_folder=$this->_get_survey_folder($surveyid);
		
		//show the access form selection box
    	$content=$this->_select_access_method($surveyid);

		//get form information
		$forminfo=$this->Datafiles_model->get_survey_access_form($surveyid);
		
		if ($forminfo!==FALSE)
		{		
			//get files associated with the survey	
			$result['rows']=$this->Datafiles_model->get_files($surveyid);
			
			//show listing
			$content.=$this->load->view('datafiles/index', $result,true);
		}
				    	
    	//set page title
		$this->template->write('title', 'Survey files',true);				
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
    }
	
	/**
	*
	* Let user select the Access Method (public,direct, etc)
	*
	*/
	function _select_access_method($surveyid)
	{	
		//update the form if user has changed the selection
		$this->_update_form($surveyid);		
		
		try
		{
			//get the survey active access form
			$selected_form=$this->Datafiles_model->get_survey_access_form($surveyid);
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
			return FALSE;
		}
				
		//choice when no other form is selected
		$this->forms_list=array('0'=>'Select');		
		
		//create a list of choices for the drop down
		foreach($this->Form_model->get_all()  as $value)
		{
			$this->forms_list[$value['formid']]=$value['fname'];
		}
		
		//load the view
		return $this->load->view('datafiles/select_form',$selected_form,TRUE);
	}

	function _update_form($surveyid)
	{
		//update the form
		$formid=$this->input->post("formid");	
		
		//check if need to change form selection
		if (!is_numeric($formid))
		{
			return FALSE;
		}			
		
		//update form selection in db
		$this->Datafiles_model->update_form($surveyid,$formid);
		
		//get request form model information
		$forminfo=$this->Datafiles_model->get_survey_access_form($surveyid);
				
		//set message for display
		switch($forminfo['model'])
		{
			case 'public':
				$this->form_message='For users to download files, they will be required to register on website.';
			break;
			
			case 'direct':
				$this->form_message='Users can download the files without any restrictions.';
			break;

			case 'licensed':
				$this->form_message='Users are required to register on the website and will be rquired to request access to the survey data files. The site administrator is required to allow individual files for each request to be available for user to download.';
			break;
			
			case 'data_enclave':
				$this->form_message='No files will be provided to the user to download.';
			break;
			
			default:
				if ($formid==0)
				{
					$this->form_message='You have not chosen any data access method. The survey files will not be available for users to download.';
				}
		}
	}

	
	/**
	* process uploaded files
	*
	*/
	function edit($surveyid=NULL,$fileid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			echo 'invalid id';exit;
		}
		
		//validate form input
		$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('description', 'Description', 'xss_clean|trim|max_length[255]');

		$edit_data=NULL;
		
		//survey folder  path
		$survey_folder=$this->_get_survey_folder($surveyid);
		
		//data folder path
		$data_folder_path=$survey_folder.'/datafiles';
		
		//for passing it to the edit view
		$edit_data['survey_folder_path']=$survey_folder;
		$edit_data['surveyid']=$surveyid;

		$process_form=FALSE;
		if (is_array($_FILES) || is_numeric($fileid))
		{
			$process_form=TRUE;
		}

        if ($this->form_validation->run() === TRUE && $process_form===TRUE)
		{
			$options=NULL;
			$form_valid=TRUE;
			//process uploaded files
			try
			{					
				if (!file_exists($data_folder_path))
				{
					//create the folder
					@mkdir($data_folder_path);
				}					
				
				//upload files if found in the files array
				if ($_FILES)
				{
					//copy uploaded file to the survey folder
					$_file=$this->_upload_file('userfile',$data_folder_path);
					
					//success
					if ($_file!==FALSE)
					{
						$options['filepath']=str_replace($survey_folder,'',$_file['full_path']);
					}								
				}
			}
			catch(Exception $e)
			{
				$this->form_validation->set_error($e->getMessage());
				$form_valid=FALSE;
			}				
			
			if ($form_valid!=FALSE)
			{
				$options['title']=$this->input->post("title");
				$options['description']=$this->input->post("description");
		
				if (!is_numeric($fileid))
				{
					//add to database
					$this->Datafiles_model->insert($surveyid,$options);
				}
				else
				{
					//get the old filename in case we are uploading a new file
					$oldfilename=$this->Datafiles_model->select_single($fileid);
					$oldfilename=$oldfilename['filepath'];
					
					//update database
					$this->Datafiles_model->update($surveyid,$fileid,$options);
					
					//delete the old file if new file is added
					if (isset($options['filepath']))
					{
						$delete_file=unix_path($survey_folder.'/'.$oldfilename);
						
						//only if it is not a folder
						if(!is_dir($delete_file))
						{
							unlink($delete_file);
						}	
					}
					
				}
				//redirect back to the index page after uploading the files
				$this->session->set_flashdata('message', 'Form was updated successfully!');
				redirect('admin/datafiles/'.$surveyid,'refresh');				
			}			
		}
		else//loading the form for the first time
		{
			if (is_numeric($fileid))
			{
				//get file info for edit
				$filedata=$this->Datafiles_model->select_single($fileid);
				$edit_data=array_merge($edit_data,$filedata);			
			}
		}

		//show the upload file controls
		$content=$this->load->view('datafiles/upload_file',$edit_data,TRUE);
		$this->template->write('title', 'Upload data file',true);				
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	/**
	 * upload files to specified folder
	 * @return array
	 */
	function _upload_file($key,$destination)
	{
		if ($_FILES[$key]['size']==0)
		{
			return false;
		}
		
		$config['upload_path'] = $destination;
		$config['disallowed_types'] = 'exe|php|js|asp|aspx';
		$config['overwrite'] = false;
		
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
	* Returns the survey folder path
	*
	*/
	function _get_survey_folder($surveyid)
	{
	
		//build fixed survey folder path
		$catalog_root=$this->config->item("catalog_root");
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);
					
		//survey folder path
		$survey_folder=unix_path($catalog_root.'/'.$survey_folder);
		
		return $survey_folder;
	}
			
	function add($id=NULL)
	{
		$this->edit($id);
	}
	
	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($surveyid,$id)
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
				redirect('admin/datafiles/'.$surveyid,"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
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
				redirect('admin/datafiles/'.$surveyid,"refresh");
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Datafiles_model->delete($item);
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
				redirect($destination);
			}
			else
			{
				redirect('admin/datafiles/'.$surveyid,"refresh");
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
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
		$per_page = 10;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'posted';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->Lic_files_model->search($per_page, $offset);

		//total records in the db
		$total = $this->Lic_files_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Lic_files_model->search_requests($per_page, $offset);
		}
		
		//set pagination options
		$base_url = site_url('admin/licensed_requests');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field'));//pass any additional querystrings
		$config['num_links'] = 1;
		//$config['next_link'] = 'Next';		
		//$config['prev_link'] = 'Prev';
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	/**
	*
	* Download the data file
	*/	
	function download($surveyid=NULL,$fileid=NULL)
	{
		$this->load->helper('download');

		//get file information
		$fileinfo=$this->Datafiles_model->select_single($fileid);
		
		//get survey folder path
		$survey_folder=$this->_get_survey_folder($surveyid);
		
		if (!$fileinfo)
		{
			show_error("file was not found");
		}
		
		//build complete filepath to be downloaded
		$file_path=unix_path($survey_folder.'/'.$fileinfo['filepath']);
		
		if (!file_exists($file_path))
		{
			show_error("File was not found");
		}
		
		//force download
		force_download2($file_path);
	}
}