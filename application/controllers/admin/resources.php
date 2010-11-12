<?php
class Resources extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		$this->template->set_template('blank');		
       	
		$this->load->model('Resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('managefiles_model');
		$this->load->helper(array ('querystring_helper','url', 'form','file') );		
		$this->load->library('pagination');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');

		//$this->output->enable_profiler(TRUE);
	}
 
	function index(){	

		//required
		$surveyid=$this->uri->segment(3);
		
		if (!is_numeric($surveyid) )
		{
			show_error('Invalid or missing parameters');
		}
		
		//get survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		
		//set parent survey
		$this->Resource_model->surveyid=$this->uri->segment(3);
				
		//get records		
		$result['rows']=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('resources/index', $result,true);
	
		//page title
		$this->template->write('title', t('resource_manager'),true);

		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	/**
	* returns the paginated resources
	* 
	* supports: sorting, searching, pagination
	*/
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

		//records
		$rows=$this->Resource_model->search($per_page, $curr_page);

		//total records in the db
		$total = $this->Resource_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$rows=$this->Resource_model->search($per_page, $curr_page);
		}
		
		//set pagination options
		$base_url = site_url("admin/catalog/{$this->Resource_model->surveyid}/resources");
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('page_last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}

	/**
	* show single resource on the page
	* 
	*/
	function view($resourceid)
	{
		//get db row by id
		$row=$this->Resource_model->select_single($resourceid);
		
		$data['row']=$row;
		$data['textarea_fields']=array('abstract','toc');
		//load the contents of the page into a variable
		$content=$this->load->view('resources/single_record', $data,true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();		
	}
	
	/**
	*
	* Delete one or more resources
	**/
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
				//confirm delete	
				$this->Resource_model->delete($item);
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
				redirect('admin/catalog');
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
	*
	* Edit Resource
	*/
	function edit($id='add')
	{
		//check survey id 
		$survey_id=$this->uri->segment(3);
		
		if (!is_numeric($survey_id) && $survey_id<1)
		{
			$this->session->set_flashdata('error', 'Invalid id was provided.');
			redirect('admin/catalog');			
		}
		
		//js auto complete file list
		$files_array=$this->get_files_array($survey_id);
		$this->js_files='';
		
		if (is_array($files_array))
		{
			$this->js_files=implode(' ', $files_array);
		}
		
		//js and css for auto complete field
		$this->template->add_css('javascript/autocomplete/jquery.autocomplete.css');
		$this->template->add_js('javascript/autocomplete/jquery.autocomplete.pack.js');		

		//check id
		if (!is_numeric($id) && $id!='add')
		{
			$this->session->set_flashdata('error', 'Invalid id was provided.');
			redirect("admin/catalog/$survey_id/resources");
		}
	
		//redirect on Cancel
		if ( $this->input->post("cancel")!="" )
		{
			redirect("admin/catalog/$survey_id/resources");
		}	
		
		$this->load->library('form_validation');
		
		$data[]=NULL;
		
		//atleast one rule require for validation class to work
		$this->form_validation->set_rules('title', t('title'), 'required|trim');		
				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array();
			$post_arr=$_POST;
			
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}
			
			if ($id=='add')
			{
				//set survey_id
				$options['survey_id']=$survey_id;				
			}
			else
			{
				//set resource id			
				$options['resource_id']=$id;
			}
							
			//map form names with db names
			$options['filename']=$options['url'];
						
			if ($id=='add')
			{
				$db_result=$this->Resource_model->insert($options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-added',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}	
			}
			else
			{
				//update db
				$db_result=$this->Resource_model->update($id,$options);
				
				//log
				if ($db_result)
				{
					$this->db_logger->write_log('resource-updated',$options['title'].'-'.$options['filename'],'resources',$survey_id);
				}
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success') );
				
				//redirect back to the list
				redirect("admin/catalog/$survey_id/resources");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));				
			}
		}
		else
		{
			if (is_numeric($id) && $id>0)
			{
				//load values from db
				$data=$this->Resource_model->select_single($id);
			}	
		}
				
		if (is_numeric($id))
		{
			$data['form_title']=t('edit_external_resource');
		}
		else
		{
			$data['form_title']=t('add_external_resource');
		}
		
		//load form
		$content=$this->load->view('resources/edit', $data,true);
		
		$this->template->write('content', $content,true);
	  	$this->template->render();		
	}



	/**
	* validate for file or url
	*
	*/
	function _valid_file_url($str)
	{
		
		if (trim($str)=="")
		{
			return TRUE;
		}
		
		//get survey id
	 	$surveyid=$this->uri->segment(3);		
		
		//get relative survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);
		
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//complete survey folder path
		$survey_folder=$catalog_root.'/'.$survey_folder;
		
		//file path
		$file_path=$survey_folder.'/'.$str;
	 
	 	//validate as file
	 	if (file_exists($file_path) )
		{
			return TRUE;
		}
		
		//validate as a URL
		if ($this->form_validation->valid_url($str)===FALSE)
		{
			$this->form_validation->set_message('_valid_file_url', lang('valid_url'));
			return FALSE;
		}
		
		return TRUE;				
	}
	
	/**
	 * upload files to survey folder
	 * used for uploading survey reports,technical docs
	 * 
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
	

	function add()
	{
		$this->edit("add");
	}
	
	function import()
	{
		$this->load->library('form_validation');
		
		if ($this->input->post('submit'))
		{
			$this->do_import();
			//echo 'importing';
		}
		else
		{
			$data['form_title']=t('import_external_resources');
			//import form
			$content=$this->load->view('resources/import', $data,true);			
			$this->template->write('content', $content,true);
			$this->template->render();	
		}
	}
	
	function do_import()
	{
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//upload class configurations
		$config['upload_path'] = $catalog_root;
		//$config['allowed_types'] = 'xml|rdf'; //format: xml|zip
		$config['overwrite'] = TRUE; //overwrite the file, if already exists
		
		//load upload library
		$this->load->library('upload', $config);

		//process uploaded file
		if ( ! $this->upload->do_upload())
		{
			//get errors while uploading
			$error = $this->upload->display_errors();
			
			//set error
			$this->session->set_flashdata('error', $error);
			
			//redirect back to the upload page
			redirect('admin/catalog/'.$this->uri->segment(3).'/resources/import','refresh');
		}	
		else //successful upload
		{			
			//get uploaded file information
			$data = $this->upload->data();
																			
			//read rdf file contents
			$rdf_contents=file_get_contents($data['full_path']);
			
			//load RDF parser class
			$this->load->library('RDF_Parser');
			
			//parse RDF to array
			$rdf_array=$this->rdf_parser->parse($rdf_contents);
			
			if ($rdf_array===FALSE || $rdf_array==NULL)
			{
				$this->session->set_flashdata('error', t('error_import_failed') );
			}
			else
			{
				$rdf_fields=$this->rdf_parser->fields;
				
				//success
				foreach($rdf_array as $rdf_rec)
				{
					$insert_data['survey_id']=$this->uri->segment(3);
					
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
						//fix html entities e.g &amp; &quot;
						$insert_data['filename']=htmlspecialchars_decode($insert_data['filename'],ENT_QUOTES);
						
						//clean file paths
						$insert_data['filename']=unix_path($insert_data['filename']);

						//remove slash before the file path otherwise can't link the path to the file
						if (substr($insert_data['filename'],0,1)=='/')
						{
							$insert_data['filename']=substr($insert_data['filename'],1,255);
						}												
					}
					
					//check if the resource file already exists
					$resource_exists=$this->Resource_model->get_survey_resources_by_filepath($insert_data['survey_id'],$insert_data['filename']);
					
					if (!$resource_exists)
					{										
						//insert into db
						$this->Resource_model->insert($insert_data);
						
						//log
						$this->db_logger->write_log('resource-imported',$insert_data['title'].'-'.$insert_data['filename'],'resources',$insert_data['survey_id']);
											
						//create resources folder structure
						if ($this->input->post('folder_structure')=='yes')
						{						
							$this->_create_folder_structure($insert_data['filename']);
						}
					}
					else
					{
						$this->errors[]=t('resource_already_exists').'<b>'. $insert_data['filename'].'</b>';
					}
				}

				if (isset($this->errors) )
				{
						$this->session->set_flashdata('error', implode('<BR>',$this->errors));
				}
				
				$this->session->set_flashdata('message', sprintf(t('n_resources_imported'),count($rdf_array)) );	
			}
			
			redirect('admin/catalog/'.$this->uri->segment(3).'/resources','refresh');
		}
	}	

	/**
	* Create folder structure for imported external resources
	*
	**/
	function _create_folder_structure($str)
	{				
		if ($str=='' || strpos($str,'http:') || strpos($str,'https:') || strpos($str,'ftp:') ||  strpos($str,'../') ||  strpos($str,'./') )
		{
			return FALSE;
		}

		//change file slash
		$str=unix_path($str);
		
		//strip filename from the path
		$path_arr=explode('/', $str);

		//assuming the last part is always going to be the filename
		unset($path_arr[count($path_arr)-1]);

		//folder path without filename
		$folder_structure=trim(implode('/',$path_arr));

		if ($folder_structure=='')
		{
			return FALSE;
		}
				
		//get survey id
	 	$surveyid=$this->uri->segment(3);		
		
		//get relative survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);
		
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//complete survey folder path
		$survey_folder=$catalog_root.'/'.$survey_folder;
		
		if (!file_exists($survey_folder.'/'.$folder_structure))
		{
			log_message('debug', 'Creating folder for external resources - '.$survey_folder.'/'.$folder_structure);
			if (!@mkdir($survey_folder.'/'.$folder_structure, 0777, true)) 
			{
				log_message('debug', 'Failed creating folder for external resources - '.$survey_folder.'/'.$folder_structure);
			}
		}
	}


	/**
	*
	* Fix broken file links. It updates the resource paths in the database if the 
	* files exists in the survey folder
	*
	*/
	function fixlinks($surveyid=NULL)
	{
		if ($this->input->post("submit"))
		{
			$content=$this->_fix_links($surveyid);					
		}
		else
		{
			$content=$this->load->view('managefiles/fixlinks',array('links'=>array()),TRUE );
		}
		
		$this->template->write('content', $content,true);
		$this->template->render();	
			
		return;
		echo '<pre>';
		var_dump($files['files']);
		var_dump($broken_links);
		return;
		
	}
	
	function _fix_links($surveyid)
	{
	//get survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		
		//get survey resources
		$resources=$this->Resource_model->get_survey_resource_files($surveyid);
		
		//hold broken resources
		$broken_links=array();
		
		//build an array of broken resources, ignore the resources with correct paths
		foreach($resources as $resource)
		{
			//check if the resource file found on disk
			if(!is_url($resource['filename']))
			{
				if(!file_exists( unix_path($this->survey_folder.'/'.$resource['filename'])))
				{
					$broken_links[]=$resource;
				}
			}
		}
		
		//get a list of all files in the survey folder
		$files=$this->managefiles_model->get_files_recursive($this->survey_folder,$this->survey_folder);

		//number of links fixed
		$this->fixed_count=0;
		
		//find matching files in the filesystem for the broken links
		foreach($broken_links as $key=>$resource)
		{			
			$match=FALSE;
			
			//search files array and return the relative path to the file if found 
			foreach($files['files'] as $file)
			{
				//match found
				if(strtolower($file['name'])==strtolower(basename($resource['filename'])) )
				{					
					$match=$file['relative'];
					
					//update path in database
					$this->Resource_model->update($resource['resource_id'],array('filename'=>$file['relative'].'/'.$file['name']));
					
					//update the count
					$this->fixed_count++;
					
					break;
				}
			}
			
			//add path for the resources
			$broken_links[$key]['match']=$match;
		}
		
		$content=$this->load->view('managefiles/fixlinks',array('links'=>$broken_links),TRUE);	
		return $content;
	}
	
	/**
	*
	* Returns an array of all files in the survey folder
	*
	**/
	function get_files_array($surveyid=NULL)
	{
		if (!is_numeric($surveyid))
		{
			return NULL;
		}
		
		//get survey folder path
		$folderpath=$this->managefiles_model->get_survey_path($surveyid);
		
		//name of the file to lock for deletion
		$this->ddi_file_name=basename($this->Catalog_model->get_survey_ddi_path($surveyid));
			
		//get all survey files
		$data=$this->managefiles_model->get_files_recursive($folderpath,$folderpath);
		
		$files=array();
		
		if (isset($data['files']))
		{
			foreach($data['files'] as $file)
			{
				$files[]=$file['relative'].'/'.$file['name'];
			}
		}		
		return $files;
	}	
}
/* End of file resources.php */
/* Location: ./controllers/resources.php */