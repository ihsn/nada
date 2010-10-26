<?php
class DDIbrowser extends MY_Controller {
 
    public function __construct()
    {
        	parent::__construct($skip_auth=TRUE);
    		
			$this->load->library('cache');
    		$this->lang->load('general');
    		$this->lang->load('ddibrowser');
    		$this->template->set_template('ddibrowser');
    				
		    $this->lang->load("general");
			//$this->output->enable_profiler(TRUE);
    }
 
 	//TODO remove, not in use
	function index()
	{	
		$page_contents='content not set';
		$this->template->write('title', t('title_ddi_browser'),true);
		
		//pass data to the site's template
		$this->template->write('content', $page_contents,true);
		$this->template->write('sidebar', 'sidebar',true);
		
		//render final output
		$this->template->render();
	}

	
	/**
	*
	* Variable Search
	*
	**/
	function search($id)
	{
		//search
		$this->load->helper('form');
		$params=array(
			'study_keywords'=>$this->input->get_post('sk'),
			'variable_keywords'=>$this->input->get_post('vk'),
			'variable_fields'=>$this->input->get_post('vf'),
			'countries'=>$this->input->get_post('country'),
			'topics'=>$this->input->get_post('topic'),
			'from'=>$this->input->get_post('from'),
			'to'=>$this->input->get_post('to'),
			'sort_by'=>$this->input->get_post('sort_by'),
			'sort_order'=>$this->input->get_post('sort_order')			
		);		
		$this->load->library('catalog_search',$params);
			
		$html='';
		if($this->uri->segment(4)!='ajax')
		{
			//show the search box
			$html=$this->load->view('ddibrowser/search',NULL,TRUE);
		}
				
		if ($this->input->get('vk')!='')
		{
			//search
			$data['variables']=$this->catalog_search->v_quick_search($id,$limit=50);

			//show the search result
			$html.=$this->load->view('ddibrowser/variable_list',$data,TRUE);
		}
		
		if($this->uri->segment(4)!='ajax')
		{
			return $html;
		}
		else
		{
			echo $html;exit;
		}
		
	}



	/**
	*
	* Export any page to PDF
	*
	**/
	function generate_pdf($contents)
	{	
		$this->load->library('MY_mpdf');
		$stylesheet = file_get_contents('themes/ddibrowser/ddi.css');
		$this->my_mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

		//$this->my_mpdf->useOnlyCoreFonts = true;
		$this->my_mpdf->SetHTMLFooter('<div style="border-top:1px solid gray;">'.site_url().$this->uri->uri_string().' - <b>page {PAGENO}</b></div>');
		//$this->my_mpdf->setFooter(site_url().$this->uri->uri_string().' - page {PAGENO}');
//		$contents=file_get_contents(site_url().$this->uri->uri_string().'?print=yes');
		//echo $contents;exit;
		$this->my_mpdf->WriteHTML($contents);
		$filename='test-report'.date("U").'.pdf';	
		//$this->my_mpdf->Output($filename,"F");
		$this->my_mpdf->Output(); 
		exit;
	}
	


	/**
	*
	* Show DDI page by section
	*
	**/
	function _section($id,$section)
	{
		//$section=$this->input->get("section");
		
		$this->load->model('Catalog_model');
		$this->load->library('DDI_Browser','','DDI_Browser');
		
		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);
		
		//survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);
		
		if ($ddi_file===FALSE)
		{
			show_error(t('file_not_found'));
			return;
		}

		//log
		$this->db_logger->write_log('survey',$this->uri->segment(4),$section,$id);

		//get survey info
		$survey=$this->Catalog_model->select_single($id);
		$this->survey=$survey;
		$this->ddi_file=$ddi_file;

		//language
		$language=array('lang'=>$this->config->item("language"));
		
		if(!$language)
		{
			//default language
			$language=array('lang'=>"english");
		}

		//get the xml translation file path
		$language_file=$this->DDI_Browser->get_language_path($language['lang']);
		
		if ($language_file)
		{
			//change to the language file (without .xml) in cache
			$language['lang']=unix_path(FCPATH.$language_file);
		}		
			
		//page URLs
		$current_url=site_url().'/ddibrowser/'.$id.'/';
		
		//section shown
		$section_url=$current_url.$section;
    		
		$html=NULL;
		switch($section)
		{
			case '':
			case 'overview':
			case 'home':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_overview_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}
				
				//check if it is a harvested survey
				$is_harvested=$this->Catalog_model->is_harvested($id);

				if ($is_harvested===TRUE)
				{
					//get repository data
					$repo=$this->Catalog_model->get_repository_by_survey($id);
					$repo['surveyid']=$id;
					
					if ($repo!==FALSE)
					{
						//create box
						$repo_box=$this->load->view('ddibrowser/repository',array('repository'=>$repo),TRUE);
						
						//add info to page output
						$html=$repo_box.$html;
					}	
				}
			break;
			
			case 'summary':
			break;

			case 'accesspolicy':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_access_policy_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}	
			break;

			case 'sampling':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_sampling_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}
		        $section_url=$current_url.'/sampling';	
			break;
			
			case 'questionnaires':
			case 'questionnaire':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_questionnaires_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}	
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'[doc/qst]');
				$data['title']=t('title_forms');
				$html.=$this->load->view("ddibrowser/resources",$data,TRUE);
        		$section_url=$current_url.'/questionnaires';
			break;

			case 'dataprocessing':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_dataprocessing_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}	
        		$section_url=$current_url.'/dataprocessing';
			break;

			case 'datacollection':
				
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_datacollection_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}        
			break;

			case 'dataappraisal':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_dataappraisal_html($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}				
			break;

			case 'technicaldocuments':				
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'[doc/tec]');
				$data['title']=t('title_technical_documents');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'reports':
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'[doc/rep]');
				$data['title']=t('title_reports');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'stat_tables':
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'[tbl]');
				$data['title']=t('title_statistical_tables');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;
			
			case 'othermaterials':
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'other');
				$data['title']=t('title_other_materials');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'search':
				$html=$this->search($id);
			break;

			case 'datafile':
				$fileid=$this->uri->segment(4);
				$html= $this->cache->get( md5($section.$ddi_file.$fileid.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_datafile_html($ddi_file,$fileid,$language);
					$this->cache->write($html, md5($section.$ddi_file.$fileid.$language['lang']), 100);
				}
        		$section_url=$current_url.'/datafile/'.$fileid;				
			break;
			
			case 'datafiles':				
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_datafiles_array($ddi_file,$language);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}				
			break;

			case 'vargrp_list':
				$html= $this->cache->get( md5($section.$ddi_file.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_variable_groups_array($ddi_file);
					$this->cache->write($html, md5($section.$ddi_file.$language['lang']), 100);
				}								
			break;

			case 'vargrp':
				$groupid=$this->uri->segment(4);
				$html= $this->cache->get( md5($section.$ddi_file.$groupid.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_variables_by_group($ddi_file,$groupid,$language);
					$this->cache->write($html, md5($section.$ddi_file.$groupid.$language['lang']), 100);
				}        
        		$section_url=$current_url.'/vargrp/'.$groupid;
			break;

			case 'variable':
				$variable_id=$this->uri->segment(4);
				$html= $this->cache->get( md5($section.$ddi_file.$variable_id.$language['lang']));
				if ($html===FALSE)
				{	
					$html=$this->DDI_Browser->get_variable_html($ddi_file,$variable_id,$language);
					$this->cache->write($html, md5($section.$ddi_file.$variable_id.$language['lang']), 100);
				}								
        		$section_url=$current_url.'/variable/'.$variable_id;
			break;			
		
			case 'get_sidebar_options':
				$html=$this->DDI_Browser->get_sidebar_options($ddi_file);
			break;
			
			case 'ddi':
				$this->_download_ddi($ddi_file);exit;
			break;
			
			case 'download':
				$this->download($this->uri->segment(4));exit;
			break;
						
			case 'export':
				//$html=$this->load->view('ddibrowser/export',NULL,TRUE);
				$html=$this->export();
			break;
			
			case 'print'://export any page to PDF
				$this->generate_pdf($ddi_file);exit;
			break;			
		}

		//For ajax requests, exclude the template
		if ($this->input->get('ajax'))
		{
			$this->load->view("ddibrowser/ajax_view",array('html'=>$html, 'section_url'=>$section_url));
			exit;
		}
		else if($this->input->get('print'))
		{
			$this->template->set_template('blank');
			$this->template->add_css('themes/ddibrowser/ddi.css');
			$this->template->write('content', $html,true);
			$this->template->render();
		}
		else if($this->input->get('pdf'))
		{
			$this->generate_pdf($html);
			exit;
		}
		else
		{	
			//get side bar sections with data
			$options['sidebar']=$this->DDI_Browser->get_sidebar_options($ddi_file);
			$options['vargrp']=$this->DDI_Browser->get_variable_groups_array($ddi_file);
			$options['resources']=$this->DDI_Browser->get_available_resources($id);
			
			$this->template->set_template('ddibrowser');
			$this->template->write_view('sidebar', 'ddibrowser/sidebar', $options, TRUE);			
			$this->template->write('survey_title', $survey['nation']. ' - '. $survey['titl'],true);
			$this->template->write('section_url', $section_url,true);
			$this->template->write('content', $html,true);
			$this->template->render();	
		}		
	}
	
	
	function export()
	{
		$surveyid=$this->uri->segment(2);
		
		if (!is_numeric($surveyid))
		{
			show_404();
		}
		
		$html=$this->load->view('ddibrowser/export',NULL,TRUE);

		$report_link='';

		//check if post back
		if ($this->input->post('generate'))
		{
			if ($this->input->post("format")=='pdf')
			{
				$html.=$this->_export_pdf();
			}
			else
			{
				$html.=$this->_export_word();
			}	
			
		}
		
		return $html;
	}
	
	function _export_pdf()
	{
		$surveyid=$this->uri->segment(2);
		
		$html=$this->load->view('ddibrowser/export',NULL,TRUE);

		$report_link='';

		$this->load->library('pdf_report');
		$this->load->model('Catalog_model');
		$this->load->library('DDI_Browser','','DDI_Browser');
			
		//get ddi file path from db
		$ddi_file=$this->ddi_file;
		
		if ($ddi_file===FALSE || !file_exists($ddi_file))
		{
			show_error(t('file_not_found'));
		}
	
		//output report file name
		$report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$this->survey['id'].'.pdf');
		
		if (file_exists($report_file))
		{
			//check if the file was created after the ddi creation date
			if (filemtime($report_file) > filemtime($ddi_file))
			{
				$report_link=$report_file;
			}	
		}
			
		if ($report_link=='')
		{			
			//report header
			$this->survey['report_title']=$this->survey['titl']. ' - '.$this->survey['nation'];						
			
			//change error logging to 0	
			$log_threshold= $this->config->item("log_threshold");
			$this->config->set_item("log_threshold",0);

			$start_time=date("H:i:s",date("U"));
			//write PDF report to a file			
			$this->pdf_report->generate($report_file,$ddi_file,$this->survey);			
			$end_time=date("H:i:s",date("U"));
			
			//log
			$this->db_logger->write_log('survey','report generated '.$start_time.' -  '. $end_time,'ddi-report',$surveyid);

			//reset threshold level			
			$this->config->set_item("log_threshold",$log_threshold);
			
			$report_link=$report_file;
		}
		
		if ($report_link!='')
		{
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$report_link.'</em>');
			force_download2($report_link);return;
		}
		
		return 'Documentation could not be generated.';		
	}


	function _export_word()
	{
		$surveyid=$this->uri->segment(2);
		
		$report_link='';

		$this->load->library('word_report');
		$this->load->model('Catalog_model');
		$this->load->library('DDI_Browser','','DDI_Browser');
			
		//get ddi file path from db
		$ddi_file=$this->ddi_file;
		
		if ($ddi_file===FALSE || !file_exists($ddi_file))
		{
			show_error(t('file_not_found'));
		}
	
		//output report file name
		$report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$this->survey['id'].'.html');
			
		if (file_exists($report_file))
		{
			//check if the file was created after the ddi creation date
			if (filemtime($report_file) > filemtime($ddi_file))
			{
				$report_link=$report_file;
			}	
		}
			
		if ($report_link=='')
		{			
			//report header
			$this->survey['report_title']=$this->survey['titl']. ' - '.$this->survey['nation'];						
			
			//change error logging to 0	
			$log_threshold= $this->config->item("log_threshold");
			$this->config->set_item("log_threshold",0);

			$start_time=date("H:i:s",date("U"));
			//write PDF report to a file			
			$this->word_report->generate($report_file,$ddi_file,$this->survey);			
			$end_time=date("H:i:s",date("U"));
			
			//log
			$this->db_logger->write_log('survey','report generated '.$start_time.' -  '. $end_time,'ddi-report',$surveyid);

			//reset threshold level			
			$this->config->set_item("log_threshold",$log_threshold);
			
			$report_link=$report_file;
		}
		
		if ($report_link!='')
		{
			//echo $report_link;exit;
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$report_link.'</em>');
			force_download2($report_link); return;
		}
		
		return 'Documentation could not be generated.';
	}
	

	function _remap()
	{	
       $segment_array = $this->uri->segment_array();
	   
	   //required
	   $surveyid=$this->uri->segment(2);
	   
	   if (!is_numeric($surveyid))
	   {
	   		show_404();
	   }
	   
	   $section=$this->uri->segment(3);
	   $this->_section($surveyid,$section);
	}

	
	
	/**
	* Download DDI file
	*
	*/
	function _download_ddi($ddi_path)
	{
		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$ddi_path.'</em>');
		force_download2($ddi_path);
	}
	


	/**
	* Download external resources by resource_id
	*
	*/
	function download($resource_id)
	{		
		
		if (!is_numeric($resource_id))
		{
			show_404();
		}
				
		$this->load->model('Resource_model');
		
		//get resource record
		$resource=$this->Resource_model->select_single($resource_id);
		
		if ($resource===FALSE)
		{
			show_error(t('file_not_found'));exit;
		}
		
		//resource file name
		$file_name=trim($resource['filename']);
		
		if ($file_name=='')
		{
			show_error('Resource not available');exit;
		}
		
		//full path to the resource
		$resource_path=unix_path($this->survey_folder.'/'.$file_name);
		
		//check if file actually exists
		if (!file_exists($resource_path))
		{
			show_error('Resource not available');exit;
		}
		
		//finally start the file download
		$this->load->helper('download');		
		log_message('info','Downloading file <em>'.$resource_path.'</em>');		
		force_download2($resource_path);
	}
	
	
}
/* End of file ddibrowser.php */
/* Location: ./controllers/ddibrowser.php */