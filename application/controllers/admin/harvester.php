<?php

class Harvester extends MY_Controller {

	var $cache_folder='./harvester_cache/';

	function __construct()
	{	
		parent::__construct();	
		
		//set default template
		$this->template->set_template('admin');
		$this->load->model('repository_model');
		$this->load->model('harvester_model');		
		$this->lang->load('harvester');
		//$this->output->enable_profiler(TRUE);
	}
	
	
	/**
	*
	* Fetch xml from all repositories
	*
	**/
	function index($repo=NULL)
	{	
		//records to show per page
		$per_page = 15;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'repositoryid';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->harvester_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->harvester_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->harvester_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		$data['rows']=$rows;
		
		
		//js & css for jquery window 
		$this->template->add_css('javascript/ceebox/css/ceebox.css');
		$this->template->add_js('javascript/ceebox/js/jquery.ceebox.js');

		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('pagination') );
		
		/*$rows=$this->harvester_model->load_queue();

		$per_page=10;
		$total=count($rows);
		$offset=(int)$this->input->get('offset');
		
		if ($offset>$total)
		{
			$offset=$total-$per_page;
		}	
		
		$data['rows']=$rows;
		*/
		
		$base_url = site_url('admin/harvester');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		
		
		//display items
		$content=$this->load->view('harvester/index',$data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('harvester'),true);
	  	$this->template->render();	

	}

	/**
	*
	* Check if remote file exists
	*
	**/
	function _remote_file_exists($url) 
	{
		//initialize curl
		$curl = curl_init($url);	

		//check if the remote file exists
		curl_setopt($curl, CURLOPT_NOBODY, true);
	
		//send request
		$result = curl_exec($curl);
	
		$ret = false;
	
		//if request did not fail
		if ($result !== false) 
		{
			//if request was ok, check response code
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($statusCode == 200) 
			{
				//log
				$this->db_logger->write_log('harvester','File was found '.$url,'check-remote-file');				
				$ret = true; 
			}
		}
	
		curl_close($curl);
	
		return $ret;
	}
	
	/**
	*
	* Import a downloaded file to the catalog
	*
	**/
	function _import($ddi_file,$repository_id)
	{
		//log
		$this->db_logger->write_log('harvester',$l_keywords="repo[$repository_id], file[$ddi_file]",$l_section='ddi-import');

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

			//log
			$this->db_logger->write_log('harvester',$l_keywords="invalid DDi file[$repository_id], file[$ddi_file]",$l_section='ddi-parse-failed');

			log_message('error', $error);
			return FALSE;
		}
						
		//parse ddi to array	
		$data=$this->ddi_parser->parse();		

		//overwrite?
		$overwrite=TRUE;//(bool)$this->input->post("overwrite");
		
		//set repository id
		$this->DDI_Import->repository_identifier=$repository_id;
						
		//import to db
		$result=$this->DDI_Import->import($data,$ddi_file,$overwrite);

		if ($result===TRUE)
		{
			//display import success 
			log_message('DEBUG', 'Survey imported - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables');
			
			//log
			$this->db_logger->write_log('harvester',$l_keywords="repo[$repository_id], file[$ddi_file] completed",$l_section='ddi-import-complete');
		}
		else
		{
			log_message('DEBUG', 'FAILED - Survey import - <em>'. $data['study']['id']. '</em> with '.$this->DDI_Import->variables_imported .' variables');
			
			//log
			$this->db_logger->write_log('harvester',$l_keywords="repo[$repository_id], file[$ddi_file]",$l_section='ddi-import-failed');
		}		
		
		return $result;
	}
	
	
	

	/**
	*
	* Process a single item from the top of the queue
	*
	* 1. Download remote file
	* 2. Import file to catalog
	* 3. Update queue 
	*
	**/
	function process_single($id=NULL)
	{
		if(is_numeric($id))
		{
			$survey=$this->repository_model->queue_pop($id);
		}
		else
		{
			$survey=$this->repository_model->queue_pop();
		}
		
		if (!$survey)
		{
			echo json_encode(array('error'=>'Skipped - max retries=3'));
			exit;
		}

		$this->ddi_is_updated=FALSE;//indicates if DDI is replaced with new file
		
		//check if survey is already been harvested and imported into the local database
		$is_already_imported=$this->repository_model->survey_exists($survey['repositoryid'],$survey['surveyid']);
		
		$this->messages=array();
		
		//DDI URL for downloading
		$remote_ddi_path= str_replace("/catalog/","/catalog/ddi/", $survey['survey_url']);

		//URL for externla resources (RDF)
		$remote_rdf_path=$survey['survey_url'].'/rdf';		
		
		//check if the file exists on the remote server
		$exists=$this->_remote_file_exists($remote_ddi_path);

		//log
		$this->db_logger->write_log('harvester',$l_keywords='YES',$l_section='remote-file-exists',$id);		

		//ddi file name downloaded by harvester
		$ddi_downloaded_file=FALSE;
		
		//download DDI file from remote server
		if ($exists===TRUE)
		{
			//remote study checksum url
			$remote_ddi_checksum_url=$survey['survey_url'].'/?format=checksum';
			
			//get remote ddi checksum
			$remote_ddi_checksum=$this->_curl_get_content($remote_ddi_checksum_url);
			
			if (strlen($remote_ddi_checksum)!==32)
			{
				//invalid checksum
				$remote_ddi_checksum=FALSE;
			}			
		
			//download and place in the cache folder
			$ddi_downloaded_file=$this->_curl_download_ddi($remote_ddi_path,'',$remote_ddi_checksum);
			$this->db_logger->write_log('harvester',$l_keywords=$ddi_downloaded_file,$l_section='remote-file-downloaded',$id); //log
			$this->messages[]='DDI file downloaded successfully!';
		}
		
		//update survey access policy
		$this->repository_model->update_survey_data_access($survey['repositoryid'],$survey['surveyid'],$survey['accesspolicy']);

		if ($ddi_downloaded_file!==FALSE)
		{		
			set_time_limit(0);
			$is_imported=TRUE;
			
			//if survey is not imported yet, import, otherwise skip import
			if (!$is_already_imported || $this->ddi_is_updated===TRUE)
			{
				//import DDI to database
				$is_imported=$this->_import($this->cache_folder.$ddi_downloaded_file,$survey['repositoryid']);
				
				$this->db_logger->write_log('harvester','DDI is imported and overwritten','ddi-import'); //log
				$this->messages[]='db import ='.$is_imported;
			}
			else
			{
				//import skipped as survey is already been imported before
				$this->messages[]='db import =skipped';
				$this->db_logger->write_log('harvester','DDI already exists in db','ddi-import-skipped'); //log
			}

			//check if survey import was successful			
			if ($is_imported)
			{
				$options=array(
					'survey_url'=>$survey['survey_url'],
					'status'=>'harvested',
					'ddi_local_path'=>$ddi_downloaded_file
					);		
				$this->repository_model->update_queued_survey($survey['survey_url'], $options);	
			}						
			
			set_time_limit(0);
						
			//get survey RDF data
			$local_rdf_file=md5($remote_ddi_path).'.rdf';
			$local_rdf_file=$this->_curl_download_file($remote_rdf_path,$local_rdf_file,true);
			$this->messages[]='rdf file ='.$local_rdf_file;
			
			//get the survey id for the local catalog
			$surveyid=$this->repository_model->get_survey_id($survey['repositoryid'],$survey['surveyid']);
			
			//import RDF file
			$this->_import_rdf($surveyid,unix_path($this->cache_folder.'/'.$local_rdf_file));
			
			//get survey checksum
			$local_checksum=md5($remote_ddi_path).'.chksum';
			$local_checksum=$this->_curl_download_file($survey['survey_url'].'/?format=checksum',$local_checksum);
			$this->messages[]='checksum file ='.$local_checksum;
			
			//$json_data=json_decode(file_get_contents($cache_folder.'/'.$json_local_file));
			
			//echo json_encode(array('success'=>'Survey downloaded successfully!' . ' is imported ='.$is_imported));
			//exit;			
		}
		else
		{
			//failed
			/*$this->repository_model->update_queued_survey( 
							$survey['survey_url'],
							$status='FAILED',
							$retries=$survey['retries']+1);*/
							
						$options=array(
						'survey_url'=>$survey['survey_url'],
						'retries'=>$survey['retries']+1,
						'status'=>'FAILED'
						);		
			$this->repository_model->update_queued_survey($survey['survey_url'], $options);			

			
			//$this->messages[]='Survey download failed....';
			echo json_encode(array('error'=>'Survey download failed....'));
			exit;
		}
	
		//http://localhost/nada3/index.php/catalog/2/?format=json
		echo json_encode(array('success'=>implode(", ", $this->messages)));return;

	
	}
	
	
	
	/**
	*
	* Download remote ddi file to the given folder
	*
	**/
	function _curl_download_ddi($remote_path,$destination,$remote_checksum)
	{
		//create a local file path
		$filename=md5($remote_path).'.xml';
		
		//log
		$this->db_logger->write_log('harvester',$l_keywords=$remote_path,$l_section='Remote DDI file to download');
		
		//create Harvester Cache folder if not already exists		
		if (!file_exists($this->cache_folder))
		{
			$iscreated=@mkdir($this->cache_folder);			
			if (!$iscreated)
			{
				$this->db_logger->write_log('harvester',$l_keywords='Harvester Cache Folder Not Found'. $this->cache_folder);//log
				show_error('Harvester Folder Not Set'. $this->cache_folder);				
			}
		}
		
		//complete local file path where the ddi wil be saved
		$outputfile=$this->cache_folder.$filename;
		
		$this->db_logger->write_log('harvester',$l_keywords=$outputfile,$l_section='local DDI file path');//log
		
		//temporary file for the download
		$tempfile=$outputfile.'.tmp';
		
		//log
		$this->db_logger->write_log('harvester',$l_keywords=$tempfile,$l_section='local Temp file for DDI');
		
		//check if the file has already been downloaded, use the local file
		if (file_exists($outputfile))
		{
			$this->messages[]='Local DDI found';
			$this->db_logger->write_log('harvester',$l_keywords='Local DDI found');	
			
			//create checksum for local ddi file
			$local_ddi_checksum=md5_file($outputfile);
						
			if ($local_ddi_checksum===$remote_checksum || $remote_checksum===FALSE)
			{
				//use local copy
				$this->db_logger->write_log('harvester',$l_keywords="using local copy - local:[$local_ddi_checksum] remote:[$remote_checksum]",$l_section='checksums-matched');
				return $filename;
			}
			else
			{
				//checksums different
				$this->db_logger->write_log('harvester',$l_keywords="local:[$local_ddi_checksum] remote:[$remote_checksum]",$l_section='checksums-diff');
				
				//delete local file
				unlink($outputfile);				
				$this->db_logger->write_log('harvester',$l_keywords='del-local-ddi',$l_section="local ddi file deleted from cache - $outputfile");
			}
		}
		
		//create the temporary file
		$fp = fopen($tempfile, 'a');
	 
	 	//initialize curl
		$ch = curl_init($remote_path);
		
		//set request options
		curl_setopt($ch, CURLOPT_FILE, $fp);
	 
	 	//execute curl
		$data = curl_exec($ch);
		
		//finish and close connection
		curl_close($ch);
		
		//close file handle
		fclose($fp);
		
		if (filesize($tempfile)==0)
		{
			return FALSE;
		}
		
		//rename the temporary file 
		rename($tempfile,$outputfile);
		
		//log
		$this->db_logger->write_log('harvester',$l_keywords=$outputfile,$l_section='file downloaded and saved to harvester_cache folder');		
		
		$this->ddi_is_updated=TRUE; //need this to re-import ddi, otherwise import will skip
		return $filename;
	}

	/**
	*
	* Download remote file
	*
	*	@remote_path	- remote file to download
	*	@local_path		- local file name to store the remote file
	**/
	function _curl_download_file($remote_path,$local_path,$overwrite=TRUE)
	{
		//create a local file path
		//$filename=$remote_path;//md5($remote_path).'.json';
		
		//complete file path
		$outputfile=$this->cache_folder.$local_path;
		
		//temporary file for the download
		$tempfile=$outputfile.'.tmp';
		
		//check if the file has already been downloaded, use the local file
		if (file_exists($outputfile))
		{
			if ($overwrite==TRUE)
			{
				unlink($outputfile);
			}
			else
			{
				$this->messages[]='using local copy';
				return $local_path;
			}	
		}
		
		//create the temporary file
		$fp = fopen($tempfile, 'a');
	 
	 	//initialize curl
		$ch = curl_init($remote_path);
		
		//set request options
		curl_setopt($ch, CURLOPT_FILE, $fp);
	 
	 	//execute curl
		$data = curl_exec($ch);
		
		//finish and close connection
		curl_close($ch);
		
		//close file handle
		fclose($fp);
		
		if (filesize($tempfile)==0)
		{
			return FALSE;
		}
		
		//rename the temporary file 	
		rename($tempfile,$outputfile);	
	
		return $local_path;
	}

	/**
	*
	* Import RDF for a survey
	*
	*
	**/
	function _import_rdf($surveyid,$rdf_path)
	{
		//log
		$this->db_logger->write_log('harvester',$l_keywords="starting to importing [$rdf_path]",$l_section='import-rdf',$surveyid);
	
		//read rdf file contents
		$rdf_contents=file_get_contents($rdf_path);
		
		//load RDF parser class
		$this->load->library('RDF_Parser');
		$this->load->model('Resource_model');
		
		//delete existing resources for the harvested survey 
		$this->Resource_model->delete_all_survey_resources($surveyid);
		
		//parse RDF to array
		$rdf_array=$this->rdf_parser->parse($rdf_contents);
		
		if ($rdf_array===FALSE || $rdf_array==NULL)
		{
			//log
			$this->db_logger->write_log('harvester',$l_keywords="RDF invalid file [$rdf_path]",$l_section='import-rdf-failed',$surveyid);

			$this->errors[]=t('error_import_failed');
			return FALSE;
		}

		$rdf_fields=$this->rdf_parser->fields;
			
		//iterate and import records
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
				if (substr($insert_data['filename'],0,1)=='/')
				{
					$insert_data['filename']=substr($insert_data['filename'],1,255);
				}												
			}
			
			//check if the resource file already exists
			$resource_exists=$this->Resource_model->get_survey_resources_by_filepath($insert_data['survey_id'],$insert_data['filename']);
										
			$resources_imported=0;
			if (!$resource_exists)
			{						
				//log
				$this->db_logger->write_log('harvester',$l_keywords="inserting",$l_section='import-rdf-entry',$surveyid);
				
				//insert into db
				$insert_result=$this->Resource_model->insert($insert_data);

				//log
				$this->db_logger->write_log('harvester',$l_keywords="Resourced Imported -".$insert_data['filename'],$l_section='import-rdf-entry',$surveyid);
				
				$resources_imported++;
			}
			else
			{
				$this->errors[]=t('resource_already_exists').'<b>'. $insert_data['filename'].'</b>';
			}
		}//end-foreach
		
		return $resources_imported;
	}

	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete()
	{			
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');
		$id=$this->input->get_post("id");

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
				redirect('admin/menu',"refresh");
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
				redirect('admin/harvester');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->harvester_model->delete($item);
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
				redirect('admin/harvester');
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

	//update status of queued survey
	function set_status()
	{			
		//array of id
		$id_arr=array();
		$id=$this->input->get_post("id");
		$status=$this->input->get_post("status");

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
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
			}	
		}		
		else
		{
			$id_arr[]=$id;
		}
		
		foreach($id_arr as $item)
		{
			//update status
			$this->harvester_model->update_status($item,$status);
		}

		echo json_encode(array('success'=>"true") );
	}


	/**
	*
	* Get web page content 
	*/
	function _curl_get_content($url)
	{
		//$url="http://www.datafirst.uct.ac.za/catalogue3/index.php/catalog/1/?format=checksum";
	 	//initialize curl
		$ch = curl_init();
		
		//set request options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout=30);
	 
	 	//execute curl
		$data = curl_exec($ch);
		
		//finish and close connection
		curl_close($ch);
		
		//var_dump($data);
		
		return $data;
	}
}

/* End of file harvester.php */
/* Location: ./system/application/controllers/harvester.php */