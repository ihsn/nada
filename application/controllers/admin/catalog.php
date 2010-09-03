<?php
/**
 * Catalog Maintenance Controller
 *
 * handles all Catalog Maintenance pages
 *
 * @package		NADA 2.1
 * @author		Mehmood
 * @link		http://ihsn.org/nada/
 */
class Catalog extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Catalog_model');		
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		$this->template->set_template('admin');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');

		//$this->output->enable_profiler(TRUE);	
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
		$this->template->add_css('javascript/ceebox/css/ceebox.css');
		$this->template->add_js('javascript/ceebox/js/jquery.ceebox.js');
				
		//get surveys		
		$db_rows=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('catalog/catalog_admin', $db_rows,true);
	
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

		//records
		$data['rows']=$this->Catalog_model->search($per_page, $curr_page);

		//total records in the db
		$total = $this->Catalog_model->search_count;

		if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Catalog_model->search($per_page, $curr_page);
		}
		
		//set pagination options
		$base_url = site_url('admin/catalog');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
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
	* returns a list of surveys without the site template
	*
	* NOTE: to be used for loading surveys using ajax
	* TODO://check if not used, remove it
	*/
	function getsurveys()
	{
		$db_rows=$this->_search();
		
		//get surveys		
		$db_rows=$this->_search();
		
		//hides the search form
		$db_rows['hide_form']=TRUE;
		
		//render the page without the template
		$this->load->view('catalog/catalog_admin', $db_rows);
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
		
		//get the survey info from db
       	$survey_row=$this->Catalog_model->select_single($id);
		
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
	 * Edit survey - by id
	 *
	 * @return void
	 *
	 **/
	function edit($id=NULL)
	{	
		if (!is_numeric($id))
		{
			show_error('Invalid survey id was provided.');
		}
	
		//redirect on Cancel
		if ( $this->input->post("cancel")!="" )
		{
			redirect('admin/catalog/edit/'.$this->uri->segment(4),'refresh');
		}		

		$this->load->library('form_validation');
				
		//atleast one rule require for validation class to work
		$this->form_validation->set_rules('link_report', 'link report', 'trim');

		//set template
		$this->template->set_template('blank');
		
		$db_values=array();
		
		//track form is valid
		$form_valid=TRUE;
		
		if ($this->form_validation->run() == TRUE)
		{			
			$options=array('id'=>$id);
			foreach($_POST as $key=>$value)
			{
				$options[$key]=get_form_value($key);
			}
			
			$catalog_root=$this->config->item("catalog_root");
			$survey_folder=$this->Catalog_model->get_survey_path($id);
			
			if($survey_folder!==false)
			{
				$survey_folder=$catalog_root.'/'.$survey_folder;
			}
						
			if ($form_valid===TRUE)
			{
				//update db
				$update_result=$this->Catalog_model->update_survey_options($options);
				
				if ($update_result===TRUE)
				{
					//update successful
					$this->session->set_flashdata('message', t('form_update_success'));
					
					//redirect back to the list
					redirect('admin/catalog/'.$this->uri->segment(3).'/edit','refresh');
				}
				else
				{
					//update failed
					$this->form_validation->set_error(t('form_update_fail'));
				}
			}
		}
		else
		{
			//load form values from db
			$db_values=$this->Catalog_model->select_single($id);
		}

		 //load the contents of the page into a variable
		$content=$this->load->view('catalog/edit', $db_values,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
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
		//$config['allowed_types'] = 'xml'; //format: xml|zip
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
			redirect('admin/catalog/upload','refresh');
		}	
		else //successful upload
		{			
			//get uploaded file information
			$data = array('upload_data' => $this->upload->data());
									
			$ddi_progress['ddi_progress']=array('status'=>'uploaded', 'upload_data'=>$this->upload->data());
			
			//save progress to session
			$this->session->set_userdata($ddi_progress);
			
			//Show import message on the page
			//echo $this->load->view('catalog/ddi_import_process',NULL,true);

			$this->import();
			//import the file
			//redirect('admin/catalog/import','refresh');
		}
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
			show_error('Nothing to process');
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
			$error= 'Invalid DDI file: '.$ddi_file;

			log_message('error', $error);

			$error.=$this->load->view('catalog/upload_file_info', $session_data, true);			

			$this->session->set_flashdata('error', $error);
			redirect('admin/catalog/upload','refresh');
		}
						
		//parse ddi to array	
		$data=$this->ddi_parser->parse();		

		//overwrite?
		$overwrite=(bool)$this->input->post("overwrite");
						
		//import to db
		$result=$this->DDI_Import->import($data,$ddi_file,$overwrite);

		if ($result===TRUE)
		{
			//display import success 
			$success=$this->load->view('catalog/ddi_import_success', array('info'=>$data['study']),true);
			log_message('DEBUG', 'Survey imported - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables');
			
			$this->session->set_flashdata('message', $success);
			
			//delete uploaded file
			unlink($ddi_file);
		}
		else
		{
			log_message('DEBUG', 'FAILED - Survey import - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables');
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
		//get the encoded file path from post
		$encoded_filepath=$this->input->post("id");
		$overwrite=(bool)$this->input->post("overwrite");

		//decode
		$ddi_file=base64_decode($encoded_filepath);
		
		//check file exists
		if (!file_exists($ddi_file))
		{
			echo json_encode(array('error'=>"File was not found") );
			exit;
		}
		//show_error($ddi_file);
		
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
			$msg='Survey imported - <em>'. $data['study']['titl']. '</em> with '.$this->DDI_Import->variables_imported .' variables';
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
				//delete survey and related data from other tables
				//$this->Catalog_model->update_survey_options(array('id'=>$item,'isdeleted'=>1) );
				$this->Catalog_model->delete($item);
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
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
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
}
/* End of file catalog.php */
/* Location: ./controllers/admin/catalog.php */