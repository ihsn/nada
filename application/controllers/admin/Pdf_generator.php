<?php
class Pdf_generator extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		$this->template->set_template('admin');
		$this->lang->load("general");
		$this->lang->load("resource_manager");
		$this->lang->load("ddibrowser");
		$this->lang->load("catalog_search");
		$this->load->model('Catalog_model');
		$this->load->model('Dataset_model');
		//$this->output->enable_profiler(TRUE);
    }
 
	function index()
	{	
		$content="PDF Generator";
		$this->template->write('title', "PDF Generator",TRUE);
		$this->template->write('content', $content,TRUE);
	  	$this->template->render();
	}
	
	//setup pdf options
	function setup($sid=NULL)
	{
		if(!is_numeric($sid))
		{
			show_error("INVALID ID");
		}
		
		$this->acl->user_has_study_access($sid);
		
		$this->form_validation->set_rules('website_title', t('website_title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('study_title', t('study_title'), 'xss_clean|trim|required|max_length[400]');
		$this->form_validation->set_rules('publisher', t('publisher'), 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('website_url', t('website_url'), 'xss_clean|trim|required|max_length[255]');

		$data=array();
		if ($this->form_validation->run() == TRUE)
		{		
				$options=array(
					'publisher'=>$this->input->post("publisher"),
					'website_title'=>$this->input->post("website_title"),
					'study_title'=>$this->input->post("study_title"),
					'website_url'=>$this->input->post("website_url"),
					'toc_variable'=>(int)$this->input->post("toc_variable"),
					'data_dic_desc'=>(int)$this->input->post("data_dic_desc"),
					'ext_resources'=>(int)$this->input->post("ext_resources"),
					'report_lang'=>$this->input->post("report_lang",TRUE),
				);
				
				if($options['ext_resources']===1)
				{
					$this->load->helper('Resource_helper');
					$this->load->model('Resource_model');					
					$survey['resources']=$this->Resource_model->get_grouped_resources_by_survey($sid);
					$survey['survey_folder']=$this->Catalog_model->get_survey_path_full($sid);
					$options['ext_resources_html']=$this->load->view('ddibrowser/report_external_resource',$survey,TRUE);
				}
				//echo $options['ext_resources_html'];exit;
				$this->_export_pdf($sid,$options);
		}
		else{
			$survey=$this->Catalog_model->select_single($sid);			
			$data['publisher']=$survey['authoring_entity'];
			if (@json_decode($data['publisher']))
			{
				$data['publisher']=is_array($data['publisher']) ? implode(", ",$data['publisher']) : '';
			}
			$data['website_title']=$this->config->item("website_title");
			$data['website_url']=site_url();
			$data['study_title']=$survey['nation'].' - '.$survey['title'];
			$data['study_id']=$survey['idno'];
			$data['id']=$survey['id'];
			$data['varcount']=$survey['varcount'];
		}

		$content=$this->load->view("ddibrowser/pdf_report_options",$data,TRUE);
		$this->template->write('title', "PDF Generator",TRUE);
		$this->template->write('content', $content,TRUE);
	  	$this->template->render();
	}
	
	
	/**
	*
	* Export DDI to PDF and start Download
	**/
	function _export_pdf($surveyid,$options=NULL)
	{
		$this->load->helper('url_filter');
		$log_threshold= $this->config->item("log_threshold");
		$this->config->set_item("log_threshold",0);	//disable logging temporarily
		
		$report_link='';		
		$params=array('codepage'=>$options['report_lang']);

		$this->load->library('pdf_report',$params);// e.g. 'codepage' = 'zh-CN';
		$this->load->library('DDI_Browser','','DDI_Browser');
			
		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($surveyid);
		$survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		//$survey_folder=$this->Dataset_model->get_storage_fullpath($surveyid);		
		
		if ($ddi_file===FALSE || !file_exists($ddi_file)){
			show_error(t('file_not_found'. $ddi_file));
		}
	
		//output report file name
		$report_file=unix_path($survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$surveyid.'.pdf');
		
		/*
		if (file_exists($report_file))
		{
			//check if the file was created after the ddi creation date
			if (filemtime($report_file) > filemtime($ddi_file))
			{
				$report_link=$report_file;
			}	
		}*/
			
		if ($report_link=='')
		{			
			//change error logging to 0	
			$log_threshold= $this->config->item("log_threshold");
			$this->config->set_item("log_threshold",0);

			$start_time=date("H:i:s",date("U"));

			//write PDF report to a file
			$this->pdf_report->generate($report_file,$ddi_file,$options);
			$end_time=date("H:i:s",date("U"));
			
			//log
			$this->db_logger->write_log('survey','report generated '.$start_time.' -  '. $end_time,'ddi-report',$surveyid);

			//reset threshold level			
			$this->config->set_item("log_threshold",$log_threshold);
			
			$report_link=$report_file;
		}
		
		if ($report_link!='')
		{
			//$this->load->helper('download');
			//log_message('info','Downloading file <em>'.$report_link.'</em>');
			//force_download2($report_link);return;
			$this->session->set_flashdata('message', t('PDF report generated successfully.').$report_link);			
		}
		else
		{
			$this->session->set_flashdata('error', t('PDF report failed'));
		}

		//$this->config->set_item("log_threshold",$log_threshold);
		
		redirect('admin/catalog/edit/'.$surveyid);
	}
	
	
	//delete pdf file
	function delete($sid=NULL)
	{
		if(!is_numeric($sid))
		{
			show_error("INVALID ID");
		}
		
		$this->acl->user_has_study_access($sid);
		
		$this->load->library("catalog_admin");
		$this->catalog_admin->delete_study_pdf($sid);
		
		redirect('admin/catalog/edit/'.$sid);
	}
}
/* End of file pdf_generator.php */
/* Location: ./controllers/admin/pdf_generator.php */