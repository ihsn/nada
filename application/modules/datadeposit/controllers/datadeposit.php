<?php

class Datadeposit extends MY_Controller {
	
	/**
	  Hold each grid id, to prevent duplicate id's
	  */
	private $_study_grid_ids       = array();

	private $_uploaded_files       = false;
	
	//active project object
	public  $active_project        = false;
 
	public function __construct($SKIP=FALSE, $is_admin=FALSE) {
		parent::__construct(true);
    	$this->load->library('session');
	
		$this->load->model('Projects_model');
		$this->lang->load('general');
		$this->lang->load('projects');
		$this->lang->load('help');
				
    	$this->template->set_template('default');	
		$this->config->load('datadeposit');		
		$this->_get_active_project();
	}

	/**
	*
	* Get active project
	**/
	public function _get_active_project() {
		$segments = array('submit','update', 'study','datafiles','citations','upload','managefiles','summary');
		if (in_array($this->uri->segment(2),$segments)) {
			if ($this->uri->segment(3)) {
				$project_id=$this->uri->segment(3);
				$this->active_project=$this->Projects_model->project_id($project_id);
			}
		}
	}

	public function update($id) {
		$this->load->helper('url');
    	$this->template->set_template('datadeposit');	
		$this->load->model('dd_Resource_model');
			$this->template->add_css('themes/opendata/datadeposit.css');

		// Get record via ID	
		$record['project'] = $this->Projects_model->project_id($id); 
		// This will redirect user to My Project page main page if they don't have access to project they are trying to access
			if (!isset($record['project'][0]->id) || $record['project'][0]->uid !== $this->session->userdata('user_id')) {
				redirect('datadeposit/projects');
		}
		
		// Title field is required		
		$this->form_validation->set_rules('title','Title','trim|required|xss_clean');
		
		
		if($this->input->post('update'))
		{
			$data = array(
				'title'         => $this->input->post('title'),
				'shortname'     => $this->input->post('name'),
				'description'   => $this->input->post('description'),
				'last_modified' => date("Y:m:d H:i:s"),
				'data_type'     => $this->input->post('datatype'),
				'collaborators' => $this->input->post('access')
			);
			
			if($this->form_validation->run() === TRUE){
				$id = $this->input->post('project_id');
				$this->Projects_model->update($id, $data);
				//Data has been updated successfully
				$this->_write_history_entry("Project updated", $record['project'][0]->id, $record['project'][0]->status);
				$this->session->set_flashdata('message', t('submitted'));
				redirect('datadeposit/study/'.$id);
			}
		}
		
		$record['message']        = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		// Access of project model for Title
		$project                  = $this->Projects_model->project_id($id);

		$title                    = $project[0]->title;
		$record['title']          = isset($title)?$title:t('Project Title');
		$record['option_types']   = $this->dd_Resource_model->get_dc_types();
		$record['option_formats'] = $this->dd_Resource_model->get_dc_formats();
		$record['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc');

		$content = $this->load->view('datadeposit/edit', $record , true);


		$tabs    = $this->load->view('datadeposit/tabs2', array('content'=>$content),true);
			
		//page title
		$this->template->write('title', 'Edit Project',true);	
	
		//pass data to the site's template
		$this->template->write('content', $tabs,true);
		
		//render final output
	  	$this->template->render();
	}
	
	public function index() {
		$this->display();
	}
	
	public function export($id) {
		$this->load->library('DDI_Study_Export');
		$this->load->model('Study_model');
		$this->load->model('dd_Resource_model');
		$this->load->helper('download');
		$data['project'] = $this->Projects_model->project_id($id); 
		if ($this->session->userdata('group_id') != 1) {
			redirect('datadeposit/projects');
		}
		if ($this->input->get('format')) {
	
			if ($this->input->get('format') == 'ddi') {
				$data['record'] = $this->Study_model->get_study_array($id);
				$this->ddi_study_export->load_template(dirname(__FILE__) . '/../xslt/ddi_study_template.xml');
				$data['data']   = $this->ddi_study_export->to_ddi($data['record']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->title}_{$data['project'][0]->id}_ddi.xml"));
				$this->_write_history_entry("Study {$data['project'][0]->title} Exported as DDI", $data['project'][0]->id, $data['project'][0]->status);
				force_download($title, $data['data']);
	
			} else if ($this->input->get('format') == 'rdf') {
				$data['files']  = $this->dd_Resource_model->get_project_resources($id);
				$data['data']   = $this->_resources_to_RDF($data['files']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->title}_{$data['project'][0]->id}_rdf.xml"));
				$this->_write_history_entry("Study {$data['project'][0]->title} Exported as RDF", $data['project'][0]->id, $data['project'][0]->status);
				force_download($title, $data['data']);
			}
		} else {
			redirect('datadeposit/projects');
		}
	}

	public function process_batch_uploads($id)
	{
		$this->load->model("managefiles_model");
		$this->load->model("dd_Resource_model");
		$this->load->model("form_model");
		$this->load->model("catalog_model");
		
		$this->lang->load("general");
		$this->lang->load("resource_manager");
		//$id=(int) end(explode('/', current_url()));
		$overwrite=$this->input->post("overwrite");
		
		if ($overwrite!=1)
		{
			$overwrite=0;
		}
		


		// HTTP headers for no cache etc
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		//header(": " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: pLast-Modifiedost-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		
		// Settings
		$project   = $this->Projects_model->project_id($id);
		$location  = md5($project[0]->id . $project[0]->created_on);
		
		$targetDir = dirname(__FILE__) . '/../datafiles/datadeposit/' . $location;
		
		//$cleanupTargetDir = false; // Remove old files
		//$maxFileAge = 60 * 60; // Temp file age in seconds
		
		// 5 minutes execution time
		@set_time_limit(15 * 60);
		
		// Uncomment this one to fake upload time
		// usleep(5000);
		
		// Get parameters
		$chunk    = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
		$chunks   = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
		
		// Clean the fileName for security reasons
		//$fileName = preg_replace('/[^\w\._]+/', '', $fileName);
		
		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);
		
			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;
		
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}
		

		// Create target dir
		if (!file_exists($targetDir))
		{
			mkdir($targetDir);
		}
				
		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
		
		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];
		
		$project          = $this->Projects_model->project_id($id);
		
		$project_resource = array(
			'filename'   => $fileName,
			'project_id' => $id,
			'created'    => date('Y:m:d'),
			'author'     => $project[0]->created_by
		);
		
			$this->dd_Resource_model->insert_project_resource($project_resource);
			$this->_write_history_entry("File {$fileName} uploaded", $project[0]->id, $project[0]->status);
		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");
		
					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
		} else {
			// Open temp file
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");
		
				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
		
				fclose($in);
				fclose($out);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}
		
		// Return JSON-RPC response
		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');	
	}
	
	
	public function upload($id) {
    	$this->template->set_template('datadeposit');	

		$this->load->model('dd_Resource_model');
					$this->template->add_css('themes/opendata/datadeposit.css');

		$this->template->add_css("javascript/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css");
		$this->template->add_js("javascript/plupload/plupload.full.js");
		$this->template->add_js("javascript/plupload/jquery.plupload.queue/jquery.plupload.queue.js");
		
		$data['option_formats'] = $this->dd_Resource_model->get_dc_types();
		$data['id']             = $id;
		$content = $this->load->view('datadeposit/upload', $data, true);
	//	$tabs    = $this->load->view('datadeposit/tabs', array('content' => $content), true);
				
		$this->template->write('content', $content, true);
		
		$this->template->render();
	}
	
	public function batch_delete_resource($fid) {
		$this->load->model('dd_Resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
		$fids = explode(',', $fid);
		foreach($fids as $fid) {
			$data['file'] = $this->dd_Resource_model->get_project_resource($fid);
			$project = $this->Projects_model->project_id($data['file'][0]->project_id);
			if (!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) {
				redirect('datadeposit/projects');
			}
			$this->dd_Resource_model->delete_project_resource($fid);
			$dir = str_replace('\\', '/', dirname(__FILE__));
			$this->_write_history_entry("file {$data['file'][0]->filename} deleted", $project[0]->id, $project[0]->status);
			@unlink($dir . "/../datafiles/datadeposit/" . md5($project[0]->id . $project[0]->created_on) . "/{$data['file'][0]->filename}");
		}
	}

	public function delete_resource($fid) {
    	$this->template->set_template('datadeposit');	
		$this->load->helper('form');
					$this->template->add_css('themes/opendata/datadeposit.css');

		$this->load->model('dd_Resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
				
		$data['file'] = $this->dd_Resource_model->get_project_resource($fid);
		
		// check that the user has access
		$project = $this->Projects_model->project_id($data['file'][0]->project_id);
		if (!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) {
			redirect('datadeposit/projects');
		}
		if ($this->input->post('delete')) {
			if ($this->input->post('answer') == 'Yes') {
			$this->dd_Resource_model->delete_project_resource($fid);
			$dir = str_replace('\\', '/', dirname(__FILE__));
			$this->_write_history_entry("file {$data['file'][0]->filename} deleted", $project[0]->id, $project[0]->status);
			@unlink($dir . "/../datafiles/datadeposit/" . md5($project[0]->id . $project[0]->created_on) . "/{$data['file'][0]->filename}");
				redirect('datadeposit/datafiles/' . $data['file'][0]->project_id);
			} else if ($this->input->post('answer') == 'No') {
				redirect('datadeposit/datafiles/' . $data['file'][0]->project_id);
			}
		}
		$content    = $this->load->view('datadeposit/delete_resource', $data, true);
	//	$tabs    = $this->load->view('datadeposit/tabs', array('content' => $content), true);

		$this->template->write('title', 'Confirm delete', true);
		$this->template->write('content', $content, true);
		
		$this->template->render();
	}
	
	public function summary($id) {
    	$this->template->set_template('datadeposit');	
		$this->load->model('dd_Resource_model');
		$this->load->model('Study_model');
		$this->config->load('datadeposit');

					$this->template->add_css('themes/opendata/datadeposit.css');

		$data['project'] = $this->Projects_model->project_id($id); 
		if (!isset($data['project'][0]->id) || $data['project'][0]->uid !== $this->session->userdata('user_id')) {
			redirect('datadeposit/projects');
		}
		$data['row'] = $this->Study_model->get_study($data['project'][0]->id);
		$data['files']          = $this->dd_Resource_model->get_project_resources_to_array($id);

		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_summary_study_grid($grid_id, $grid_data, true, true);
		}	
		$data['fields']  = $this->config->item('datadeposit');
		if ($this->input->get('print') && $this->input->get('print') == 'yes') {

			$this->load->view('datadeposit/summary', $data);
		} else {
			$data['merged']         = $this->config->item('datadeposit');
			$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc;');
			$content     = $this->load->view('datadeposit/summary', $data, true);
			$tabs        = $this->load->view('datadeposit/tabs2', array('content' => $content), true);
			$this->template->write('title', 'Summary', true);

			$this->template->write('content', $tabs, true);
		
			$this->template->render();
		}
	}
	
	public function submit($id) {
		$this->load->model('dd_Resource_model');
    	$this->template->set_template('datadeposit');	
					$this->template->add_css('themes/opendata/datadeposit.css');

		$this->load->model('Study_model');
		$data['project'] = $this->Projects_model->project_id($id); 
		if ($data['project'][0]->uid !== $this->session->userdata('user_id')) {
			redirect('datadeposit/projects');
		}
		$data['study']   = $this->Study_model->get_study($data['project'][0]->id);
		if ($this->input->post('submit_project')) {
			$update = array(
				'access_policy'    => ($this->input->post('access_policy'))    ? $this->input->post('access_policy') : null,
				'library_notes'    => ($this->input->post('library_notes'))    ? $this->input->post('library_notes') : null,
				'submit_contact'   => ($this->input->post('submit_contact'))   ? $this->input->post('submit_contact') : null,
				'submit_on_behalf' => ($this->input->post('submit_on_behalf')) ? $this->input->post('submit_on_behalf') : null,
				'cc'               => ($this->input->post('cc'))               ? $this->input->post('cc') : null,
				'access_authority' => ($this->input->post('access_authority'))  ? $this->input->post('access_authority') : null
			);
			if ($this->input->post('submit_project') == 'Save and submit') {
				$update['status'] = 'submitted';
			$this->session->set_flashdata('message', t('project_submitted'));
			}
			else {
				$this->session->set_flashdata('message', t('project_saved'));
			}
			$this->Projects_model->update($id, $update);
			if ($this->input->post('submit_project') == 'Save and submit') {
				$this->_write_history_entry("Project submitted", $data['project'][0]->id, 'submitted');
				$cc    = $this->input->post('cc');
				if ($cc) {
					$email = $this->_email($id, $cc);
				} else {
					$email = $this->_email($id);
				}
				redirect('datadeposit/projects');
			} else {
				redirect('datadeposit/submit/'.$data['project'][0]->id);
			}
		}
		$data['files']    = $this->dd_Resource_model->get_project_resources_to_array($id);
		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc');
		$content     = $this->load->view('datadeposit/submit', $data, true);
		$tabs        = $this->load->view('datadeposit/tabs2', array('content' => $content), true);
		$this->template->write('title', 'Submit', true);

		$this->template->write('content', $tabs, true);
		
		$this->template->render();
	}
	
	public function download($fid) {
		
		$this->load->helper('download');
		$this->load->model('dd_Resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
				
		$data['file'] = $this->dd_Resource_model->get_project_resource($fid);
		
		// check that the user has access
		$project = $this->Projects_model->project_id($data['file'][0]->project_id);
		if ($this->uri->segment(1) != 'admin') {
			if (!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) {
				redirect('datadeposit/projects');
			}
		}
			$dir  = str_replace('\\', '/', dirname(__FILE__));
			$data['data'] = file_get_contents($dir . "/../datafiles/datadeposit/" . md5($project[0]->id . $project[0]->created_on) . "/{$data['file'][0]->filename}");
			$this->_write_history_entry(sprintf("file %s downloaded", $data['file'][0]->filename), $project[0]->id, $project[0]->status);
			force_download($data['file'][0]->filename, $data['data']);
	}
	
	public function managefiles($fid) {
    	$this->template->set_template('datadeposit');	
		$this->load->model('dd_Resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
							$this->template->add_css('themes/opendata/datadeposit.css');

		$data['file'] = $this->dd_Resource_model->get_project_resource($fid);
		
		// check that the user has access
		$project = $this->Projects_model->project_id($data['file'][0]->project_id);
		if (!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) {
			redirect('datadeposit/projects');
		}
		$data['id']   = $project[0]->id;
		if ($this->input->post('update')) {
			$record = array(
				'title'       => ($this->input->post('title')) ? $this->input->post('title') : $data['file'][0]->title,
				'description' => ($this->input->post('description')) ? $this->input->post('description') : $data['file'][0]->description,
				'author'      => ($this->input->post('author')) ? $this->input->post('author') : $data['file'][0]->author,
				'dctype'      => ($this->input->post('dctype')) ? $this->input->post('dctype') : $data['file'][0]->dctype,
			);
			$this->dd_Resource_model->update_project_resource($fid, $record);
			redirect("datadeposit/datafiles/{$data['file'][0]->project_id}");
		}
		$content = $this->load->view('datadeposit/managefiles', $data, true);	
		//$tabs    = $this->load->view('datadeposit/tabs', array('content' => $content), true);
		$this->template->write('content', $content, true);
		$this->template->write('title', 'Manage files', true);

		$this->template->render();

	}
	
	public function request_reopen($id) {
    	$this->template->set_template('datadeposit');	
		$this->load->library('email');
					$this->template->add_css('themes/opendata/datadeposit.css');

		$this->load->helper('admin_notifications');
		$this->form_validation->set_rules('reason', 'Reason', 'required');
		if ($this->input->post('reopen')) {
			if($this->form_validation->run() === TRUE){
				$subject = "Reopen Request";
				$message = $this->input->post('reason');
				$this->session->set_flashdata('message', t('submitted'));
				notify_admin($subject,$message,$notify_all_admins=false);
				$this->_write_history_entry("Requested reopen", $id, 'submitted/closed');
				redirect('datadeposit/summary/'. (int) $id);
			}
			$data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		}
		$data['id'] = $id;
		$content    = $this->load->view('datadeposit/request_reopen', $data, true);
		//$tabs    = $this->load->view('datadeposit/tabs', array('content' => $content), true);
		$this->template->write('title', 'Request Reopen', true);

		$this->template->write('content', $content, true);		
		
		$this->template->render();
	}

			
	
	public function datafiles($id) {
    	$this->template->set_template('datadeposit');	

					$this->template->add_css('themes/opendata/datadeposit.css');

		$this->load->model('dd_Resource_model');
		$this->load->model('managefiles_model');
		$this->lang->load("resource_manager");
		$this->template->add_css("javascript/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css");
		$this->template->add_js("javascript/plupload/plupload.full.js");
		$this->template->add_js("javascript/plupload/jquery.plupload.queue/jquery.plupload.queue.js");
		
		$project                = $this->Projects_model->project_id($id);
		$data['option_formats'] = $this->dd_Resource_model->get_dc_types();
		$dir                    = dirname(__FILE__) . '/../datafiles/datadeposit/';
		$dir                   .= md5($project[0]->id . $project[0]->created_on);
		$data['records']        = $this->managefiles_model->get_files_non_recursive($dir, '');

		$data['id']             = $id;
		$data['records']        = $data['records']['files'];

		if(!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) { 
				redirect('datadeposit/projects');
			}

		foreach($data['records'] as $key => $value) {
			$data['records'][$value['name']] = $value;
			unset($data['records'][$key]);
		}
		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc;');
		$data['files']          = $this->dd_Resource_model->get_project_resources_to_array($id);
		$content = $this->load->view('datadeposit/datafiles', $data, true);
		$tabs    = $this->load->view('datadeposit/tabs2', array('content' => $content), true);
		$this->template->write('title', 'Datafiles', true);
		
		$this->template->write('content', $tabs, true);
		
		$this->template->render();
	}
	
	
	public function citations($id) {
    	$this->template->set_template('datadeposit');	
		$this->template->add_css('themes/opendata/datadeposit.css');
		$this->lang->load('citations');
		$this->load->model('Study_model');
		$data['project'] = $this->Projects_model->project_id($id);

		if(!isset($data['project'][0]->id) || $data['project'][0]->uid !== $this->session->userdata('user_id')) { 
				redirect('datadeposit/projects');
			}
		
		$data['study']   = $this->Study_model->get_study($data['project'][0]->id);

		if ($this->input->post('update')) {
			$citation = array(
				'citations' => $this->input->post('citations')
			);
			if (isset($data['study'][0]->id)) {
				$this->Study_model->update_study($data['project'][0]->id, $citation);
			} else {
				$this->Study_model->insert_study($citation);
			}
			$this->session->set_flashdata('message', t('submitted'));
			redirect("datadeposit/citations/{$data['project'][0]->id}");
		}

		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc');

		
		$content = $this->load->view('datadeposit/citations', $data, true);
		$tabs    = $this->load->view('datadeposit/tabs2', array('content' => $content), true);
		$this->template->write('title', 'Citations', true);

		$this->template->write('content', $tabs, true);
		
		$this->template->render();
	}
	
	public function study($id) {
    	$this->template->set_template('datadeposit');	
			$this->template->add_css('themes/opendata/datadeposit.css');

		$this->template->add_js("javascript/jquery.treeview/jquery.treeview.js");
		$this->template->add_css("javascript/jquery.treeview/jquery.treeview.css");
		$this->load->helper('url');
		$this->load->helper('form');
		$this->config->load('datadeposit');
		$this->load->model('Study_model');
		$this->load->model('dd_Resource_model');
		$this->lang->load('help');
		$message            = '';
		$data['fields']     = $this->config->item('datadeposit');
		$data['project']    = $this->Projects_model->project_id($id);
		$data['studytype']  = $this->dd_Resource_model->get_study_types();
		$data['kindofdata'] = $this->dd_Resource_model->get_kind_of_data();
		$data['projects']   = $this->Projects_model->projects($this->session->userdata('user_id'));
		$data['methods']    = $this->dd_Resource_model->get_overview_methods();
		$data['row']        = $this->Study_model->get_study($data['project'][0]->id);
		$data['merged']         = $this->config->item('datadeposit');

		if(!isset($data['project'][0]->id) || $data['project'][0]->uid !== $this->session->userdata('user_id')) { 
				redirect('datadeposit/projects');
			}
		
		// Prepare our grid data for presentation 
		
		$grids                         = array();
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['scope_keywords']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_keywords) ? $data['row'][0]->scope_keywords : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Agency'       => 'agency', 
				'Abbreviation' => 'abbr',
				'Grant Number' => 'grant',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			if (empty($grid_data['data'])) {
				// If a grid is empty, provide an empty row
				$count                 = $grid_data['titles'];
				$grid_data['data']    = array();
				$grid_data['data'][0] = array();
				foreach($grid_data['titles'] as $titles) {
					$grid_data['data'][0][] = ' ';
				}
			}
			$data[$grid_id] = $this->_study_grid($grid_id, $grid_data);
		}	

		//$this->form_validation->set_rules('ident_id', 'ID', 'numeric');

		$study = array();
		if($this->input->post('study') || $_SERVER['REQUEST_METHOD'] == 'POST') {
			// In this version of Codeigniter, we cannot return the entire post array (Input.php)
			
			// So we will conventionally load them ourselves and clean/validate each value
			
			foreach($_POST as $key => $value) {
				
				if (!in_array($key, array_keys($grids))) {
					$study[$key] = $this->security->xss_clean($value);
				}
			}
			unset($study['ident_title']);
			$study['ident_title'] = $data['project'][0]->title; 
			unset($study['study']); // <-- submit button
			$study['id']          = $data['project'][0]->id;
			
			// date field
			if (isset($study['ver_prod_date_year'])) {
				$study['ver_prod_date'] = $this->input->post('ver_prod_date_year') . '-' .
					$this->input->post('ver_prod_date_month') . '-' .
					$this->input->post('ver_prod_date_day');
					if (!preg_match('#^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$#', $study['ver_prod_date'])) {
						unset($study['ver_prod_date']);
					}
			}
		/*
			if ($this->input->post('ver_prod_date_year')) {
				if (!$this->_study_validate_date(($study['ver_prod_date']))) {
					$message = "'{$study['ver_prod_date']}' is not a valid date";
					$this->session->set_flashdata('message', $message);
					redirect("datadeposit/study/{$data['project'][0]->id}");
				}
			}
			*/
			unset($study['ver_prod_date_year']);
			unset($study['ver_prod_date_month']);
			unset($study['ver_prod_date_day']);
			
			// grids
			$study['scope_class']             = $this->_grid_data_encode($this->input->post('topic_class'));
			$study['coverage_country']        = $this->_grid_data_encode($this->input->post('country'));
			$study['prod_s_investigator']     = $this->_grid_data_encode($this->input->post('prim_investigator'));
			$study['prod_s_other_prod']       = $this->_grid_data_encode($this->input->post('other_producers'));
			$study['prod_s_funding']          = $this->_grid_data_encode($this->input->post('funding'));
			$study['prod_s_acknowledgements'] = $this->_grid_data_encode($this->input->post('acknowledgements'));
			$study['coll_dates']              = $this->_grid_data_encode($this->input->post('dates_datacollection'));
			$study['coll_periods']            = $this->_grid_data_encode($this->input->post('time_periods'));
			$study['coll_collectors']         = $this->_grid_data_encode($this->input->post('data_collectors'));
			$study['access_authority']        = $this->_grid_data_encode($this->input->post('access_authority'));
			$study['contacts_contacts']       = $this->_grid_data_encode($this->input->post('contacts'));
			$study['scope_keywords']          = $this->_grid_data_encode($this->input->post('scope_keywords'));
			$study['impact_wb_lead']          = $this->_grid_data_encode($this->input->post('impact_wb_lead'));
			$study['impact_wb_members']       = $this->_grid_data_encode($this->input->post('impact_wb_members'));
			
			$recommended = current($data['fields']);
			$filled      = true;
			foreach ($recommended as $needed) {
				if (!isset($study[$needed])) {
					$filled = false;
					break;
				} else if (empty($study[$needed])) {
					$filled = false;
				}
				else if ($study[$needed] == '[]') { // empty grids
					$filled = false;
				}
			}
			if(empty($message)){
				$d = true;
				if ($this->Study_model->get_study($data['project'][0]->id)) {
					$d = $this->Study_model->update_study($data['project'][0]->id, $study);
				} else {
					$d = $this->Study_model->insert_study($study);
				}
				if ($d === false) {
					$this->session->set_flashdata('message', t('study_submission_failed'));
					redirect("datadeposit/study/{$data['project'][0]->id}");
				}
				$this->_write_history_entry("Study description updated", $data['project'][0]->id, $data['project'][0]->status);
				if (!$filled) {
					$this->session->set_flashdata('message', t('submitted_but_omitted'));
				} else {
					$this->session->set_flashdata('message', t('submitted'));	
				}
				redirect("datadeposit/study/{$data['project'][0]->id}");
			}
		}

		$data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc');

	
		$content = $this->load->view('datadeposit/study', $data, true);
		

		$tabs    = $this->load->view('datadeposit/tabs2', array('content'=>$content),true);
		
		//pass data to the site's template
		$this->template->write('content', $tabs, true);
		
		$this->template->write('title', 'Study Description', true);
		$this->template->write('content', $tabs, true);
		$this->template->render();
	}

	public function feedback($id) {
		$this->load->model("feedback_model");

		$project         = $this->Projects_model->project_id($id); 
		
		if(!isset($project[0]->id) || $project[0]->uid !== $this->session->userdata('user_id')) { 
			redirect('datadeposit/projects');
		}

		$record['feed']  = $this->feedback_model->feedback($id);
		$record['title'] = $project[0]->title;

		$content         = $this->load->view('datadeposit/feedback', $record, true);

		$this->template->write('title', t('feedback'), true);
		$this->template->write('content', $content, true);

		$this->template->render();
	}
	
	public function tabs() {
		$content = $this->load->view('datadeposit/tabs', null, true);
		$this->template->write('title', 'Tabs', true);
		$this->template->write('content', $content, true);
		
		$this->template->render();
	}
	
	public function projects() {
    	$this->template->set_template('datadeposit');	
			$this->template->add_css('themes/opendata/datadeposit.css');

		// do not login until this point
		$this->_auth();

		$data['fields']   = array(
			'title'         => 'Title',         
			  
			 'status'     => 'Status'
		);

		$this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
		$this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';
		
		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), $this->sort_by, $this->sort_order);
		if (!isset($data['projects'][0]->title)) {
			redirect('datadeposit/create');
		}
		$content          = $this->load->view('datadeposit/home', $data, true);
		
		$this->template->write('title', t('datadeposit'), true);
		$this->template->write('content', $content, true);		
	  	
	  	$this->template->render();
	}

	
	public function display() {
				$this->template->add_css('themes/opendata/datadeposit.css');

		$data['fields']   = array(
			'title'         => 'Title',         'shortname'  => 'Shortname',
			'created_on'    => 'Created on',    'created_by' => 'Created by',
			'collaborators' => 'Collaborators', 'status'     => 'Status'
		);

		$this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
		$this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';
		
		$data['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), $this->sort_by, $this->sort_order);

		$content          = $this->load->view('datadeposit/display', $data, true);
		
		$this->template->write('title', t('datadeposit'), true);
		$this->template->write('content', $content, true);		
	  	
	  	$this->template->render();
	}

	public function create() {
		$this->load->model('Study_model');
    	$this->template->set_template('datadeposit');	
			$this->template->add_css('themes/opendata/datadeposit.css');
		$this->load->library( array('form_validation','pagination') );

		$this->load->model('dd_Resource_model');

		$record['option_types']   = $this->dd_Resource_model->get_dc_types();
		$record['option_formats'] = $this->dd_Resource_model->get_dc_formats();

		$this->form_validation->set_rules('title','Title','required');

		if($this->input->post('create')) {
			$data = array(
				'uid'           => $this->session->userdata('user_id'),
				'title'         => $this->input->post('title'),
				'shortname'     => $this->input->post('name'),
				'description'   => $this->input->post('description'),
				'created_on'    => date("Y:m:d H:i:s"),
				'last_modified' => date("Y:m:d H:i:s"),
				'created_by'    => ucwords($this->session->userdata('username')),
				'data_type'     => $this->input->post('datatype'),
				'collaborators' => $this->input->post('collaborators')
			);
			
			// Title field is required		

			if($this->form_validation->run() === TRUE){
				$pid = $this->Projects_model->insert($data);
				// Data has been added successfully
				$project = $this->Projects_model->projects($this->session->userdata('user_id'), 'id', 'desc', 1);
				// insert blank study here.
				$study = array(
					'id'          => $project[0]->id,
					'ident_title' => $this->input->post('title')
				);
				$d = $this->Study_model->insert_study($study);
				$this->session->set_flashdata('message', t('submitted'));
				$this->_write_history_entry("Project created", $project[0]->id, $project[0]->status);
				redirect("datadeposit/update/{$project[0]->id}");
			}
		}
		$record['message'] = (validation_errors()) ? "The Title field was not set" : $this->session->flashdata('message');
		$record['projects'] = $this->Projects_model->projects($this->session->userdata('user_id'), 'title', 'asc');
		$content = $this->load->view('datadeposit/create', $record , true);
		
		$tabs    = $this->load->view('datadeposit/tabs2', array('content'=>$content),true);
		
		//page title
		$this->template->write('title', t('create'),true);	
	
		//pass data to the site's template
		$this->template->write('content', $tabs,true);
		
		//render final output
	  	$this->template->render();
	}

	public function confirm($id) {
	    	$this->template->set_template('datadeposit');	
			$this->template->add_css('themes/opendata/datadeposit.css');

		$this->load->helper('form');
		$project = $this->Projects_model->project_id($id);
		if ($this->session->userdata("group_id") !== 1) {
			if(!isset($project[0]->id) || $project[0]->uid != $this->session->userdata('user_id')) { 
				redirect('datadeposit/projects');
			}
		}
		if ($this->input->post('confirm')) {
			if ($this->input->post('answer') == 'Yes') {
				$this->Projects_model->delete($id);
				$this->_write_history_entry("Project deleted", $project[0]->id, $project[0]->status);
				redirect('datadeposit/projects');
			} else if ($this->input->post('answer') == 'No') {
				redirect('datadeposit/projects');
			}
		}
		$data['id'] = $id;
		$content    = $this->load->view('datadeposit/confirm', $data, true);
	//	$tabs    = $this->load->view('datadeposit/tabs', array('content'=>$content),true);
		$this->template->write('title', 'Confirm delete', true);
		$this->template->write('content', $content, true);
		
		$this->template->render();
	}

	public function delete ($id) {
		$project = $this->Project_model->project($id);
		// Check access first
		/*
		if ($this->session->userdata("group_id") !== 1) {
			if($project[0]->uid != $this->session->userdata('user_id')) { 
				return false;
			}
		}
		*/
		//$this->Projects_model->delete($id);
	}
	
	private function _study_validate_date($date) {
		$date = explode('-', $date);
		return @checkdate($date[1], $date[2], $date[0]);
	}

	private function _email($id, $cc=false)
	{			
		$this->load->model('dd_Resource_model');
		$this->load->model('Study_model');
		$this->load->helper('admin_notifications');
		//get user info
		$user=$this->ion_auth->current_user();
		//get request data
		$data['project'] = $this->Projects_model->project_id($id);
		$data['row']     = $this->Study_model->get_study($data['project'][0]->id);
		$data['files']   = $this->dd_Resource_model->get_project_resources_to_array($id);

		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
			);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_study_grid($grid_id, $grid_data, true);
		}	
		
		//for the user
		$subject=t('confirmation_project_submitted');
		$project_url=site_url().'/datadeposit/summary/'.$id;		
		$message=sprintf(t('msg_project_submitted'),$user->email,$project_url);
		$message.=$this->load->view('datadeposit/summary', $data,true);

		$this->load->library('email');
		$this->email->clear();		
		$config['mailtype'] = 'html';
		$this->email->initialize($config);//intialize using the settings in mail
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		$this->email->to($user->email);
		if ($cc !== false) {
			$this->email->cc($cc);
		}
		$this->email->subject($subject );
		$this->email->message($message);
		
		if (@$this->email->send())
		{
			//for the adminstrators
			$subject=t('notice_project_submitted');
			$project_url=site_url().'/datadeposit/summary/'.$id;		
			$message=sprintf(t('msg_project_submitted'),$user->email,$project_url);
			$message.=$this->load->view('datadeposit/summary', $data,true);

			//notify the site admin
			notify_admin($subject,$message,$notify_all_admins=false);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	private function _write_history_entry($comment, $project_id, $status) {
		$data = array(
			'project_id'     => (int) $project_id,
			'user_identity'  => $this->session->userdata('email'),
			'created_on'     => date("Y:m:d H:i:s"),
			'project_status' => $status,
			'comments'       => $comment,
		);
		$this->Projects_model->log_history($data);
	}
			
	private function _resources_to_RDF($resources) {
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
	
	private function _grid_check_rules($input, $column, $rule) {
		$input = grid_data_encode($input, false);
		switch ($rule) {
			case 'uri':
				foreach ($input as $field) {
					if (!preg_match("/^((ht|f)tp(s?)\:\/\/|~/|/)?([w]{2}([\w\-]+\.)+([\w]{2,5}))(:[\d]{1,5})?/", $field[$column])) {
						return false;
					}
				}
			case 'date':
				foreach($input as $field) {
					if (!preg_match("/^(?:\d{4})\/\[1-12]\/\[1-31]$/", $field[$column])) {
						return false;
					}
				}
		}
		return true;
	}
	
	private function _grid_data_encode($input, $json=true) {
		if (!is_array($input)) {
			return '';
		}
		$array = array();
		$x     = 0;
		// here we prepare the post data array back to our documented format
		foreach($input as $columns) {
			foreach($columns as $rows) {
				$array[$x++][] = current($rows);
			}
			$x = 0;
		}
		// if an array (row) has all empty elements, remove it; do this for the entire grid.
		$array = array_filter($array, create_function('&$value', '
			if (is_array($value)) {
			foreach ($value as $vals) {
				if (!empty($vals)) return 1;
			}
			// is empty
			return 0;
		}')
		);

		return ($json) ? json_encode($array) : $array;
	}

	private function _grid_data_decode($data) {
		return ($data) ? (array) json_decode($data) : null;
	}
	
	/* Grid data array:
	 //                      col 1 header, html class
	 $data['titles'] = array('title 1' => 'class1', ...); 
	 $data['data']   = array(
	    // row 1   col 1, col 2, col 3
	 	0 => array($var1, $var2, $var3),
	    // row 2   col 1,  col 2, col 3
	 	1 => array($var1, $var2, $var3)
	  );
	*/
	 
	private function _study_grid($id, array $data, $disabled = false) {
		// validate id
		/* for now */ if (!isset($data['data'])) $data['data'] = array();
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $id)) {
			throw new Exception("id `{$id}' is invalid");
		}
		// prevent duplicate id's
		if (in_array($id, $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s detected');
		} else {
			$this->_study_grid_ids[] = $id;
		}
		// each grid has a unique javascript 'counter' for field additions, along with an id
		$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof($data['data']) . '; </script>' . PHP_EOL;
		$grid   .= '<div class="grid_three">' . PHP_EOL . '		<table class="grid left" id="' . $id . '" name="' . $id . '" width="100%">';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		/* Add the javascript event code */
		$new_cols   = $data['titles'];
		$javascript = '<tr>';
		$x          = 1;
		$count      = sizeof($data['titles']);
		foreach ($new_cols as $title) {
			if ($x === $count) {
				// we will magically use javascript to automate the increment counter for each row
				$index .= '++';
			}
			$is = ($disabled) ? 'disabled="disabled"' : null;
			$javascript .= '<td width="25%"><input ' . $is  .'  name="' . $id . '[' . $title . '][\'+'.$index.'+ \'][]" onkeypress="keyPressTest(event, this);" value="" type="text"></td>';
			$x++;
		}
		if ($disabled !== true) {
			$javascript .= '<td class="last"><div onclick="$(this).parent().parent().remove();" class="button-del">-</div></td></tr>';
		}
		// every grid is custom, so to each respectively thier own javascript 'add row' function
		$grid   .= '<script type="text/javascript"> function ' . $id . '_add() { $(\''. $javascript . '\').insertAfter($(\'#' . $id . ' tbody tr\').last()); }</script>' . PHP_EOL;
		if ($disabled !== true) {
			$grid   .= '<th onclick=\'' . $id . '_add();\' class="last"><div class="button-add overviewaddRow">+</div></th>';	
		}
		$grid   .= PHP_EOL;
	
		$grid   .= '</tr>' . PHP_EOL;
		/* Now we load the data from the database into the grid, if any */
		$check = sizeof($data['titles']) && sizeof(current($data['data'])); // a little housekeeping
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
		if (empty($data)) {
			// This is an empty grid, so allow for user to add data with 0 rows
			$grid .= '</tbody></table></div>' . PHP_EOL;
			return $grid;
		}
		// otherwise, present the data in our tabular grid
		$titles = $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {	
				$is      = ($disabled) ? 'disabled="disabled"' : null;
				$grid   .= "<td width='25%'><input ". $is ." name='" . $id . "[" . array_shift($titles) . "][" . $y . "][]' onkeypress='keyPressTest(event, this);' value='".htmlentities($cols, ENT_QUOTES)."' type='text'></td>";
				$grid   .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($disabled !== true) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}

	/**
	 * Im being lazy, but this entire 'grid' system should be an independent lib
	 *
	 */
	 
	private function _summary_study_grid($id, array $data, $disabled = true) {
		// validate id
		/* for now */ if (!isset($data['data'])) $data['data'] = array();
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $id)) {
			throw new Exception("id `{$id}' is invalid");
		}
		// prevent duplicate id's
		if (in_array($id, $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s detected');
		} else {
			$this->_study_grid_ids[] = $id;
		}
		// each grid has a unique javascript 'counter' for field additions, along with an id
		$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof($data['data']) . '; </script>' . PHP_EOL;
		$grid   .= '<div class="">' . PHP_EOL . '		<table cellspacing="0" cellpadding="0" style="width:100%;margin:10px" class="left" id="' . $id . '" name="' . $id . '" >';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th cellspacing="0" cellpadding="0"  style="border: 1px solid gainsboro;" class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		$grid   .= PHP_EOL;
	
		$grid   .= '</tr>' . PHP_EOL;
		/* Now we load the data from the database into the grid, if any */
		$check = sizeof($data['titles']) && sizeof(current($data['data'])); // a little housekeeping
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
		if (empty($data['data'])) {
			// This is an empty grid, so allow for user to add data with 0 rows
			$grid .= '</tbody></table></div>' . PHP_EOL;
			// If $empty is true and there is no data, just return an empty string
			return '';
		}
		// otherwise, present the data in our tabular grid
		$titles = $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {	
				$is      = ($disabled) ? 'disabled="disabled"' : null;
				$grid   .= "<td cellspacing=\"0\" cellpadding=\"0\" style='border: 1px solid gainsboro;' width='10%'>{$cols}</td>";
				$grid   .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($disabled !== true) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}
}