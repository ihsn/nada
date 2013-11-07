<?php
class DDIbrowser extends MY_Controller {
 
	private $user=FALSE;

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		
		$this->load->driver('cache', array('adapter' => 'db', 'backup' => 'file'));
		$this->lang->load('general');
		$this->lang->load('ddibrowser');
		$this->template->set_template('default');
				
		$this->lang->load("general");
		$this->lang->load("catalog_search");
		$this->load->helper('catalog');
		//$this->output->enable_profiler(TRUE);
			
		if ($this->ion_auth->logged_in())
		{
			$this->user=$this->ion_auth->current_user();
		}
    }
 
 	//TODO remove, not in use
	function index()
	{	
		$page_contents='content not set';
		$this->template->write('title', t('title_ddi_browser'),true);
		$this->template->write('content', $page_contents,true);
		$this->template->write('sidebar', 'sidebar',true);
		$this->template->render();
	}

	
	/**
	*
	* Variable Search
	*
	**/
	function search($id)
	{
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
			//show search result
			$data['variables']=$this->catalog_search->v_quick_search($id,$limit=50);
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
		$contents= html_entity_decode(url_filter($contents));
		$codepage=$this->config->item("pdf_codepage");		
		$this->load->library('my_mpdf',array('codepage'=>$codepage));
		
		$this->template->add_css('themes/ddibrowser/ddi.css');
		$stylesheet = file_get_contents(APPPATH.'../themes/ddibrowser/ddi.css');

		//original value set in config
		$log_threshold= $this->config->item("log_threshold");
		
		//disable loggin
		$this->config->set_item("log_threshold",0);			
		
		$this->my_mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		//$this->my_mpdf->useOnlyCoreFonts = true;
		$this->my_mpdf->SetHTMLFooter('<div style="border-top:1px solid gray;">'.site_url().$this->uri->uri_string().' - <b>page {PAGENO}</b></div>');
		$this->my_mpdf->WriteHTML($contents);		
		$this->my_mpdf->Output(); 
		
		//reset threshold level to whatever was set in config
		$this->config->set_item("log_threshold",$log_threshold);
		exit;
	}
	


	/**
	*
	* Show DDI page by section
	*
	**/
	function _section($id,$section)
	{
		$this->load->model('Catalog_model');
		$this->load->model('Repository_model');
		$this->load->library('DDI_Browser','','DDI_Browser');
		$this->load->helper('url_filter');
		$this->lang->load("resource_manager");
		$this->load->helper("resource_helper");

		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		//survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);
		
		if ($ddi_file===FALSE)
		{
			show_error(t('file_not_found'));
			return;
		}

		//get survey info
		$survey=$this->Catalog_model->select_single($id);
		
		//logged in user
		$user=$this->ion_auth->current_user();
		
		//log
		$this->db_logger->write_log('survey',$this->uri->segment(4),$section,$id);
		$this->db_logger->increment_study_view_count($id);

		//check user has review permissions
		$review_study_enabled=$this->acl->user_can_review($id);
		
		//study is unpublished
		if (!$user && intval($survey['published'])===0)
		{
			show_error('CONTENT_NOT_AVAILABLE');
		}		
				
		$this->survey=$survey;
		$this->ddi_file=$ddi_file;

		//language
		$language=$this->get_language();
		
		//get study data access type
		$data_access_type=$this->Catalog_model->get_survey_form_model($id);
			
		//page URLs
		$current_url=site_url().'/catalog/'.$id;
		
		//section shown
		$section_url=$current_url.'/'.$section;
		
		//page title
		$this->page_title=$this->survey['nation']. ' - '.$this->survey['titl'];
    	
		$this->template->add_css('themes/wb/datacatalog.css');

		$html=NULL;
		$show_data_menu=FALSE;
		$show_study_menu=TRUE;		
		$cache_key=$id.'-'.$section.'-'.$language['lang_name'];
		
		$cache_ttl=(int)$this->config->item("cache_default_expires");
		$cache_disabled=(int)$this->config->item("cache_disabled");
		
		switch($section)
		{
			case 'info':
			case 'overview':
			case 'study-description':
				$this->page_title.=' - '.t('overview');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_overview_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get($cache_key);
					if ($html===FALSE)
					{
						$html=$this->DDI_Browser->get_overview_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}
				}	
			break;
			
			case '':
			case 'home':
			case 'related_materials':
				$this->load->model('Resource_model');
				$survey['resources']=$this->Resource_model->get_grouped_resources_by_survey($id);
				
				if (!$survey['resources'])
				{
					redirect('catalog/'.$id.'/study-description');exit;
				}
				
				$html=$this->load->view('catalog_search/survey_summary_resources',$survey,TRUE);
				$show_data_menu=FALSE;
				$show_study_menu=FALSE;
			break;
			
			case 'link':
				$link_type=$this->uri->segment(4);
				
				if (!in_array($link_type,array('interactive','study-website')) )
				{
					show_404();
				}
				
				$link=NULL;
				
				switch ($link_type)
				{
					case 'interactive':
						$link=$survey['link_indicator'];
					break;
					
					case 'study-website':
						$link=$survey['link_study'];
					break;				
				}
				
				if (trim($link)!="")
				{
							redirect($link);exit;
				}	
				show_404();				
				
			break;
			
			case 'review':
				//user has no review access
				if(!$review_study_enabled)
				{
					show_404();
				}				
				
				$show_study_menu=FALSE;
				$html=$this->review_study($id);
			break;

			case 'accesspolicy':
				$this->page_title.=' - '.t('access_policy');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_access_policy_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get($cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_access_policy_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}	
				}
			break;

			case 'sampling':
				$this->page_title.=' - '.t('sampling');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_sampling_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get(  $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_sampling_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}
		        }
				$section_url=$current_url.'/sampling';	
			break;
			
			case 'questionnaires':
			case 'questionnaire':
				$this->page_title.=' - '.t('questionnaires');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_questionnaires_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get(  $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_questionnaires_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}	
				}	

				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'doc/qst]');
				$data['title']=t('title_forms');
				$html.=$this->load->view("ddibrowser/resources",$data,TRUE);
				$section_url=$current_url.'/questionnaires';
			break;

			case 'dataprocessing':
				$this->page_title.=' - '.t('data_processing');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_dataprocessing_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get(  $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_dataprocessing_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}	
        		}
				$section_url=$current_url.'/dataprocessing';
			break;

			case 'datacollection':
				$this->page_title.=' - '.t('data_collection');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_datacollection_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{					
					$html= $this->cache->get(  $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_datacollection_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}
				}	
			break;

			case 'dataappraisal':
				$this->page_title.=' - '.t('data_appraisal');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_dataappraisal_html($ddi_file,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get( $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_dataappraisal_html($ddi_file,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key,$html,$cache_ttl);
					}				
				}
			break;

			case 'technicaldocuments':
				$this->page_title.=' - '.t('title_technical_documents');
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'doc/tec]');
				$data['title']=t('title_technical_documents');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'reports':
				$this->page_title.=' - '.t('reports');
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'doc/rep]');
				$data['title']=t('title_reports');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;
			
			case 'analytical':
				$this->page_title.=' - '.t('title_analytical');
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'doc/anl]');
				$data['title']=t('title_analytical');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'stat_tables':
				$this->page_title.=' - '.t('title_statistical_tables');
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'tbl]');
				$data['title']=t('title_statistical_tables');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;
			
			case 'othermaterials':
				$this->page_title.=' - '.t('title_other_materials');
				$data['resources']=$this->DDI_Browser->get_resources_by_type($id,'other');
				$data['title']=t('title_other_materials');
				$html=$this->load->view("ddibrowser/resources",$data,TRUE);
			break;

			case 'search':
				$this->page_title.=' - '.t('search');
				$html=$this->search($id);
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;

			case 'datafile':
				
				//Show variable info
				if ($this->uri->segment(5)!='')
				{
					$variable_id=$this->uri->segment(5);
					$this->page_title.=' - '.t('variable')." - $variable_id";
					
					if ($cache_disabled===1)
					{
						$html=$this->DDI_Browser->get_variable_html($ddi_file,$variable_id,$language);
						$html=html_entity_decode(url_filter($html));
					}
					else
					{
						$html= $this->cache->get( $cache_key.'-'.$variable_id);
						
						if ($html===FALSE)
						{	
							$html=$this->DDI_Browser->get_variable_html($ddi_file,$variable_id,$language);
							$html=html_entity_decode(url_filter($html));
							$this->cache->save($cache_key.'-'.$variable_id, $html, $cache_ttl);
						}								
					}
					$section_url=$current_url.'/variable/'.$variable_id;					
				}
				//show data file info	
				else
				{
					$offset=$this->input->get('offset');
					$limit=$this->input->get('limit');
					
					//set default offset
					if (!is_numeric($offset))
					{
						$offset=0;
					}
					//set default limit
					if (!is_numeric($limit))
					{
						$limit=100;
					}
					
					$fileid=$this->uri->segment(4);
					$this->page_title.=' - '.t('data_file').' - '.$fileid;
					
					if ($cache_disabled===1)
					{
						$html=$this->DDI_Browser->get_datafile_html($ddi_file,$fileid,$language);
					}
					else
					{
						$html= $this->cache->get( $cache_key.'-'.$fileid.$offset.'.'.$limit );
						
						if ($html===FALSE)
						{	
							$html=$this->DDI_Browser->get_datafile_html($ddi_file,$fileid,$language);
							$this->cache->save($cache_key.'-'.$fileid.$offset.'.'.$limit,$html, $cache_ttl);
						}
					}
					
					$section_url=$current_url.'/datafile/'.$fileid;
				}
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;
			
			case 'data_dictionary':
			case 'data-dictionary':
			case 'datafiles':
				$this->page_title.=' - '.t('Data Dictionary');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_datafiles_html($ddi_file,$language);
				}
				else
				{
					$html= $this->cache->get( $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_datafiles_html($ddi_file,$language);
						$this->cache->save($cache_key,$html,$cache_ttl);
					}
				}
				
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;

			case 'vargrp_list':
				$this->page_title.=' - '.t('variable_group_list');
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_variable_groups_array($ddi_file);
				}
				else
				{
					$html= $this->cache->get( $cache_key);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_variable_groups_array($ddi_file);
						$this->cache->save($cache_key,$html,$cache_ttl);
					}
				}
				
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;

			case 'vargrp':
				$groupid=$this->uri->segment(4);
				$this->page_title.=' - '.t('variable_group')." - $groupid";
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_variables_by_group($ddi_file,$groupid,$language);
				}
				else
				{				
					$html= $this->cache->get($cache_key.'-grp-'.$groupid);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_variables_by_group($ddi_file,$groupid,$language);
						$this->cache->save($cache_key.'-grp-'.$groupid,$html);
					}        
        		}
				
				$section_url=$current_url.'/vargrp/'.$groupid;
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;

			case 'variable':				
				$variable_id=$this->uri->segment(4);
				$this->page_title.=' - '.t('variable')." - $variable_id";
				
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_variable_html($ddi_file,$variable_id,$language);
					$html=html_entity_decode(url_filter($html));
				}
				else
				{
					$html= $this->cache->get( $cache_key.'-var-'.$variable_id);
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_variable_html($ddi_file,$variable_id,$language);
						$html=html_entity_decode(url_filter($html));
						$this->cache->save($cache_key.'-var-'.$variable_id,$html);
					}								
        		}
				
				$section_url=$current_url.'/variable/'.$variable_id;
				$show_data_menu=TRUE;
				$show_study_menu=FALSE;
			break;			
		
			case 'get_sidebar_options':
			
				if ($cache_disabled===1)
				{
					$html=$this->DDI_Browser->get_sidebar_options($ddi_file);
				}
				else
				{			
					$html= $this->cache->get( $cache_key);
					
					if ($html===FALSE)
					{	
						$html=$this->DDI_Browser->get_sidebar_options($ddi_file);
						$this->cache->save($cache_key,$html,$cache_ttl);
					}								
				}	
			
			break;
			
			case 'ddi':
				//$this->db_logger->increment_study_download_count($id);
				$this->_download_ddi($ddi_file);exit;
			break;
			
			case 'download':
				//$this->db_logger->increment_study_download_count($id);
				$this->download($this->uri->segment(4),$this->uri->segment(2));exit;
			break;
			
			case 'download-microdata':
			case 'download_microdata':
				$this->db_logger->increment_study_download_count($id);
				$this->download_microdata($this->uri->segment(4),$this->uri->segment(2));exit;
			break;
						
			case 'export':
				$html=$this->export();
			break;
			
			case 'print'://export any page to PDF
				$this->generate_pdf($ddi_file);exit;
			break;						
						
			case 'related_citations':
				$this->load->model('Citation_model');				
				$this->load->model('Repository_model');
				$this->load->library('chicago_citation');
				$this->lang->load("resource_manager");
				$this->load->helper("resource_helper");
				$this->load->model('Resource_model');//get survey related citations
				$survey['citations']=$this->Citation_model->get_citations_by_survey($id,$this->input->get('sort_by'),$this->input->get('sort_order'));
				$html=$this->load->view('catalog_search/survey_summary_citations',$survey,TRUE);
				$show_data_menu=FALSE;
				$show_study_menu=FALSE;
			break;
			
			case 'export-metadata':			
				$this->load->model('Resource_model');
				$this->page_title.=' - '.t('export_metadata');
				$data['has_resources']=$this->Resource_model->has_external_resources($id);
				$data['title']=t('export_metadata');
				$data['id']=$id;
				$html=$this->load->view("ddibrowser/export_metadata",$data,TRUE);
			break;
			
			case 'get_microdata':
			case 'get-microdata':
				
				if($data_access_type=='data_enclave')
				{
					$data_access_type='enclave';
				}
			
				$this->load->driver('data_access',array('adapter'=>$data_access_type));
				
				if ($this->data_access->is_supported($data_access_type))
				{
					$html=$this->data_access->process_form($id,$user=$this->ion_auth->current_user());
				}
				else
				{
					$html="Data Access Not Available";
				}
					
				$show_data_menu=FALSE;
				$show_study_menu=FALSE;
			break;
		}
		
		/*
		if (isset($cache_key))
		{
			$cache_meta=$this->cache->get_metadata($cache_key);
			$html.=sprintf("<div style='color:red;'>page created on %s, expires on %s</div>",date("M-d-Y H:i:s",$cache_meta['created']),date("m-d-Y H:i:s",$cache_meta['expiry']));
		}
		*/
		
		if (trim(strip_tags($html))=='')
		{
			$html='Content not available';
			$this->db_logger->write_log('survey',$section_url,'404-NOT-FOUND');
		}
		
		$this->template->write('title',$this->page_title,TRUE);

		//For ajax requests, exclude the template
		if ($this->input->get('ajax') && $this->input->get('title') )
		{
			$this->template->set_template('blank');
			$this->template->add_css('themes/ddibrowser/ddi.css');
			$this->template->write('content', $this->load->view("ddibrowser/ajax_view",array('html'=>$html, 'section_url'=>$section_url, 'title'=>$this->page_title),TRUE ),true);
			$this->template->render();
		}
		else if ($this->input->get('ajax'))
		{	
			echo $this->load->view("ddibrowser/ajax_view",array('html'=>$html, 'section_url'=>$section_url, 'title'=>$this->page_title),TRUE);
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
			$this->template->add_css('javascript/tree/jquery.treeview.css');
			$this->template->add_css('themes/ddibrowser/ddi.css');
			$this->template->add_js('javascript/tree/jquery.treeview.pack.js');
			$this->template->add_js('javascript/ddibrowser.js');

			//array('sidebar'=>$sidebar, 'body'=>$html,'survey_title'=>$survey_title);			
			$data['sidebar']='';
			if($show_data_menu==TRUE || $show_study_menu==TRUE)
			{
				$data['sidebar']=$this->get_sidebar_html($id,$show_study_menu,$show_data_menu);//$this->load->view('ddibrowser/sidebar_flat',$options,TRUE);
			}

			$data['body']=$html;

			//which tabs to show
			$data['page_tabs']=array(
				'study_description'=>1,
				'data_dictionary'=>$survey['varcount'],
				'get_microdata'=> ($data_access_type!='data_na' ? 1 : 0),
				'related_materials'=>$this->Catalog_model->has_external_resources($id),
				'related_citations'=>$this->Catalog_model->has_citations($id),
				'review_study'=>$review_study_enabled
			);
			
			$data['survey_title']=$survey['nation']. ' - '. $survey['titl'];
			$data['survey_info']=$this->get_survey_info($this->uri->segment(2));
			$data['data_access_type']=$data_access_type;
			$output=$this->load->view('ddibrowser/layout',$data,TRUE);
			
			$this->template->write('survey_title', $data['survey_title'],true);
			$this->template->write('section_url', $section_url,true);
			$this->template->write('content', $output,true);
			$this->template->render();	
		}		
	}
	
	
	/**
	*
	* Export DDI to PDF or HTML Format
	*
	**/
	function export()
	{
		$surveyid=$this->uri->segment(2);

		if (!is_numeric($surveyid))
		{
			show_404();
		}

		$report_link='';
			
		//get ddi file path from db
		$ddi_file=$this->ddi_file;
		
		if ($ddi_file===FALSE || !file_exists($ddi_file))
		{
			show_error(t('file_not_found'));
		}
	
		//output report file name
		$report_file=unix_path($this->survey_folder.'/ddi-documentation-'.$this->config->item("language").'-'.$this->survey['id'].'.pdf');
		
		if (!file_exists($report_file))
		{
			show_error("PDF_NOT_AVAILABLE");
		}
			
		$this->load->helper('download');
		@log_message('info','Downloading file <em>'.$report_file.'</em>');
		force_download2($report_file);exit;
	}
	
	

	/**
	* 
	* Export DDI to HTML format and start download
	*
	**/
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
		//disable ddibrowser urls and redirect to /catalog
		if ($this->uri->segment(1)=='ddibrowser')
		{
			$segments=$this->uri->segment_array();
			$url=site_url().'/'.implode('/',$segments);
			$url=str_replace('ddibrowser','catalog',$url);
			redirect($url);
			return;
		}
		
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
	*
	* Checks if user can download a file
	*
	* redirects to form if no access
	* 	
	**/
	private function can_download_data_file($resource_id,$survey_id)
	{
		$this->load->model('Resource_model');
		$this->load->model('Licensed_model');
		$this->load->model('Public_model');

		if (!$this->ion_auth->logged_in()) 
		{
			$this->session->set_flashdata('reason', t('reason_login_data_access'));
			$destination=$this->uri->uri_string();
			$this->session->set_userdata("destination",$destination);
			redirect("auth/login/?destination=$destination", 'refresh');
		}		

		//get current user
		$user=$this->ion_auth->current_user();

		//check user has admin/reviewer rights on the study
		$access=$this->acl->user_has_study_access($survey_id,$user_id=$user->id,FALSE);
		
		//user has admin/reviewer rights for the study
		if ($access)
		{
			return TRUE;
		}
		
		//get resource record
		$resource=$this->Resource_model->select_single($resource_id);
		
		//get survey model
		$da_model=$this->Resource_model->get_resource_da_type($resource_id);
				
		if ($da_model=='public')
		{
			//check if user has access to the study or the collection the survey belongs to
			$public_access=$this->Public_model->check_user_has_data_access($user->id,$survey_id);
			
			if (!$public_access)
			{
				//redirect to PUF download page
				redirect('/access_public/download/'.$survey_id.'/'.$resource_id);
			}
		}
		else if ($da_model=='licensed')
		{
			$lic_requests=$this->Licensed_model->get_requests_by_file($resource_id,$user->id);
			
			if (!$lic_requests)
			{
				return FALSE;
			}
			
			foreach($lic_requests as $req)
			{
				if($req['status']=='APPROVED' && $req['expiry']> date("U") && (int)$req['downloads'] < $req['download_limit'])
				{
					//increment the download tick
					$this->Licensed_model->update_download_stats($resource_id,$req['requestid'],$user->email);
					return TRUE;
				}			
			}
			
			//links expired
			redirect('/access_licensed/expired/'.$lic_requests[0]['requestid'],"refresh");			
		}
		
		return FALSE;
	}

	
	/**
	*
	* Download DATA/MICRODATA files for public use and direct only
	**/
	function download($resource_id,$survey_id)
	{
		if (!is_numeric($resource_id) || !is_numeric($survey_id))
		{
			show_404();
		}
				
		$this->load->model('Resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('Public_model');
		
		$resource=$this->Resource_model->select_single($resource_id);
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		if ($resource===FALSE)
		{
			show_error(t('RESOURCE_NOT_FOUND'));
		}

		//resource file name
		$file_name=trim($resource['filename']);
		
		if ($file_name=='')
		{
			$this->db_logger->write_log('download-file-not-found',$survey_id,'resource='.$resource_id);
			show_error('RESOURCE_NOT_AVAILABLE!');
		}
		
		//full path to the resource
		$resource_path=unix_path($this->survey_folder.'/'.$file_name);
		
		//check if file actually exists
		if (!file_exists($resource_path))
		{
			show_error('RESOURCE_NOT_FOUND');
		}
		
		$allow_download=FALSE;			//allow download or not
		$resource_is_microdata=FALSE; //whether a resource is a microdata fiel
		
		//apply checks before download MICRODATA files
		$microdata_types=array('[dat/micro]','[dat]');
		foreach($microdata_types as $type)
		{
			if (stripos($resource['dctype'],$type)!==FALSE)
			{
				$resource_is_microdata=TRUE;
			}
		}
		
		if($data_access_type=='public' && $resource_is_microdata===TRUE)
		{
			if(!$this->user)
			{
				redirect('catalog/'.$survey_id.'/get_microdata','refresh');exit;
			}
			
			//check if user has filled the PUF form for a study
			$request_exists=$this->Public_model->check_user_has_data_access($this->user->id,$survey_id);
		
			if ($request_exists===FALSE)
			{
				redirect('catalog/'.$survey_id.'/get_microdata','refresh');
			}
			
			$allow_download=TRUE;
		}
		else if($data_access_type=='licensed')
		{
			//non-microdata requests
			if($resource_is_microdata===FALSE)
			{
				$allow_download=TRUE;
			}
			else
			{
				//Deny licensed requests
				$this->db_logger->write_log('download-denied-not-microdata',$survey_id,'resource='.$resource_id);
				show_error("RESOURCE_NOT_AVAILABLE.");
			}	
		}
		else if($data_access_type=='direct' )
		{
			$allow_download=TRUE;
		}
		else if($data_access_type=='data_na' )
		{
			$allow_download=TRUE;
		}
		else if ($data_access_type=='public' && !$resource_is_microdata)
		{
			$allow_download=TRUE;
		}
		else{
			if ($resource_is_microdata===TRUE)
			{
				//for any other data access type, disable downloads of microdata resources
				show_error("INVALID_REQUEST");
			}
			else
			{
				$allow_download=TRUE;
			}	
		}
		
		if ($allow_download)
		{
			$this->load->helper('download');		
			log_message('info','Downloading file <em>'.$resource_path.'</em>');
			$this->db_logger->write_log('download',$survey_id,'resource='.$resource_id);
			$this->db_logger->increment_study_download_count($survey_id);
			force_download2($resource_path);	
		}
		else
		{
			$this->db_logger->write_log('download-denied-2:'.$data_access_type,$survey_id,'resource='.$resource_id);
			show_error("RESOURCE_NOT_AVAILABLE");
		}

	}
	
	
	
	/**
	*
	* Get survey basic informatoin
	**/
	private function get_survey_info($id)
	{	
		$survey=$this->Catalog_model->select_single($id);

		if ($survey===FALSE || count($survey)==0)
		{
			show_error('STUDY_NOT_FOUND');
		}

		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		if (!file_exists($ddi_file))
		{
			show_error('FILE_NOT_FOUND');
		}
		
		$this->load->model("Repository_model");
		$this->load->model("Resource_model");
		$survey['repositories']=$this->Catalog_model->get_survey_repositories($id);
		$survey['owner_repo']=$this->Repository_model->get_survey_owner_repository($id);
		$survey['has_resources']=$this->Resource_model->has_external_resources($id);
		
		if (!$survey['owner_repo'])
		{
			$survey['owner_repo']=$this->Repository_model->get_central_catalog_array();
		}
		
		$content_body=$this->load->view('catalog_search/survey_info',$survey,TRUE);		
		return $content_body;	
	}
	
	
	
	/**
	*
	* Return Language info with language file (.xml) path
	**/
	private function get_language()
	{
		//language
		$language=array('lang_name'=>$this->config->item("language"));

		if(!$language)
		{
			$language=array('lang_name'=>"english");
		}

		//get the xml translation file path
		$language_file=$this->DDI_Browser->get_language_path($language['lang_name']);
		
		if ($language_file)
		{
			//change to the language file (without .xml) in cache
			$language['lang']=unix_path(FCPATH.$language_file);
		}		
		
		return $language;	
	}
	
	/**
	*
	* Returns the DDI sidebar
	**/	
	function get_sidebar_html($sid,$include_study_desc=TRUE,$include_data=TRUE)
	{
		$section='sidebar_'.($include_study_desc ? 'study' : '');
		$section.=($include_data) ? 'data' : '';
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($sid);
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		$language=$this->get_language();
		
		if ($ddi_file===FALSE)
		{
			return FALSE;
		}
	
		$cache_key=$sid.'-'.$section.'-sidebar-'.$language['lang_name'];
		$data= $this->cache->get($cache_key);
		
		if ($data===FALSE)
		{	
			$data['sidebar_items']='';
			$data['show_study_items']=$include_study_desc;
			$data['show_data_items']=$include_data;
			
			if($include_study_desc===TRUE)
			{
				$data['sidebar_items']=$this->DDI_Browser->get_sidebar_options($ddi_file);
			}
			
			if($include_data===TRUE)
			{
				$data['vargrp']=$this->DDI_Browser->get_variable_groups_array($ddi_file);
				$data['data_files']=$this->DDI_Browser->get_datafiles_array($ddi_file);
			}	
			$this->cache->save($cache_key,$data,300);
		}
		
		return $this->load->view('ddibrowser/sidebar',$data,TRUE);
	}
	
	
	
	/**
	* 
	* display survey information by survey id
	*
	* @format={json,checksum}
	*	- json - returns the survey as JSON array
	*	- checksum - returns survey ddi checksum
	*
	**/
	function survey($id=NULL)
	{				
		if (!is_numeric($id))
		{
			show_404();
		}

		$this->load->model('Citation_model');
		$this->load->model('Repository_model');
		$this->load->library('chicago_citation');
		$this->lang->load("resource_manager");
		$this->load->helper("resource_helper");
		$this->load->model('Resource_model');
		
		//get survey
		$survey=$this->Catalog_model->select_single($id);

		if ($survey===FALSE || count($survey)==0)
		{
			$this->db_logger->write_log('survey-not-found',$id,'NOT FOUND');			
			$content='<h1>NOT FOUND</h1>';
			$content.='Sorry, the page you are looking for does not exist.';
			$this->template->write('content',$content,TRUE);
			$this->template->render();
			return;
		}

		if ($this->input->get('ajax') || $this->input->get('print') )
		{
			$this->template->set_template('blank');	
		}
		
		//get survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);

		//DDI file path
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		if (!file_exists($ddi_file))
		{
			show_error('FILE_NOT_FOUND');
		}
		
		//output to JSON
		if ($this->input->get('format')=='json')
		{
			$this->_survey_json($id);
			return;
		}
		else if ($this->input->get('format')=='checksum')
		{
			echo md5_file($ddi_file);
			return;
		}
						
		//page description metatags		
		$this->template->add_meta("description",sprintf(t('study_meta_description')
													,$survey['nation'].' - '.$survey['titl'],
													$survey['producer'],
													$this->config->item("site_title")));
													
		$this->template->add_meta('<link rel="meta" type="application/rdf+xml" title="RDF" href="'.site_url('catalog/rdf/'.$id).'" />'
									,NULL,'inline');

		//get list of collections/repositories
		$survey['repositories']=$this->Catalog_model->get_survey_repositories($id);
		
		//get external resources
		$survey['resources']=$this->Resource_model->get_grouped_resources_by_survey($id);

		//get survey microdata
		$survey['resources_microdata']=$this->Resource_model->get_microdata_resources($id);
				
		//get survey related citations
		$survey['citations']=$this->Citation_model->get_citations_by_survey($id);
		
		//by default no data files are downloadable
		$survey['data_access']=FALSE;
		
		//check if user has access to data
		if (in_array($survey['model'],array('licensed','public')))
		{
			//user must be logged in
			if ($this->user)
			{
				//check user has access to the data
				if ($survey['model']=='licensed')
				{
					$this->load->model('Licensed_model');
					$survey['data_access']=$this->Licensed_model->check_user_has_data_access($id,$this->user->id);
				}	
			}
			else
			{
				$survey['data_access']=FALSE;
			}
		}
						
		$content_body=$this->load->view('catalog_search/survey_summary',$survey,TRUE);		
		$this->template->write('title', $survey['titl'].' - '.$survey['nation'],true);
		return $content_body;
	}
	
	private function review_study($id)
	{		
		$this->template->add_css('themes/base/css/font-awesome.min.css');
		$this->template->add_js('javascript/jquery/ui/minified/jquery-ui.custom.min.js');
		$this->load->library('review_study');
	
		$data=array();
		$data['study_id']=$id;
		$section=$this->uri->segment(4);
		
		switch($section)
		{
			case 'get-notes':
				$data['study_notes']=$this->review_study->get_study_notes($id,$note_type='reviewer');
				$data['study_id']=$id;
				$data['user_obj']=$this->user;
				echo $this->load->view('ddibrowser/study_notes_list',$data,TRUE);exit;
			break;
			
			case 'add-note':				
				$note_options = array(
					'id'   => NULL,
					'sid'  => $id,
					'note' => $this->security->xss_clean($this->input->post('note')),
					'type' => 'reviewer',
					'userid'  => $this->session->userdata('user_id'),
					'created' => date("U")
				);
				
				if (!$note_options['note'] || !$note_options['type'] || !$note_options['userid'])
				{
					show_error("INVALID-INPUT");
				}
				
				$this->review_study->add_study_note($note_options);exit;
			break;
			
			case 'get-add-form':
				$action_url=site_url('catalog/'.$id.'/review/add-note/');
				echo $this->review_study->get_study_add_form($action_url,$show_note_types=FALSE);exit;
			break;
			
			case 'get-edit-form':
				$note_id=$this->uri->segment(5);
				$action_url=site_url('catalog/'.$id.'/review/update-note/'.$note_id);
				echo $this->review_study->get_study_edit_form($note_id,$action_url,$show_note_types=FALSE);exit;
			break;
			
			case 'update-note':
				$note_id=$this->uri->segment(5);
				$note_options = array(
					'note' => $this->security->xss_clean($this->input->post('note')),
					'type' => 'reviewer',
					'userid'  => $this->session->userdata('user_id'),
					'changed' => date("U")
				);
				
				if (!$note_options['note'] || !$note_options['type'])
				{
					show_error("NO-DATA");
				}
				
				$this->review_study->edit_study_note($note_id,$note_options);exit;
			break;
			
			case 'delete-note':
				$note_id=(int)$this->uri->segment(5);
				$result=$this->review_study->delete_study_note($note_id);
				if (!$result)
				{
					show_error('delete-failed');
				}
				exit;
			
			break;
			
			case 'resources':
				$this->load->model('Resource_model');
				$result['resources_microdata']=$this->Resource_model->get_microdata_resources($id);
				$data['tab_content']= $this->load->view('ddibrowser/study_review_microdata', $result,TRUE);
				
				$survey['resources']=$this->Resource_model->get_grouped_resources_by_survey($id);
				$data['tab_content'].= $this->load->view('ddibrowser/study_review_resources',$survey,TRUE);
			break;
			
			case 'download':
				$resource_id=$this->uri->segment(5);
				$this->load->model('Resource_model');
				$resource_path= $this->Resource_model->get_resource_download_path($resource_id);
				
				if(!$resource_path)
				{
					show_404();
				}
				
				$this->load->helper('download');
				log_message('info','Downloading file <em>'.$resource_path.'</em>');
				force_download2($resource_path);
				exit;
			break;			
			
			case '':
			case 'reviewer-notes':
			default:
				//get reviewer study notes
				$options['study_notes']=$this->review_study->get_study_notes($id,'reviewer');
				$options['study_id']=$id;
				$options['user_obj']=$this->user;
				$data['tab_content']=$this->load->view('ddibrowser/study_notes',$options,TRUE);

				//$tab_content= $this->load->view('ddibrowser/review_study',$data,TRUE);	
			
			break;
		}
		
		$output=$this->load->view('ddibrowser/review_study',$data,TRUE);
		return $output;
		
	}
	
	private function show_study_error($error_type)
	{
		show_error('Content for the page is not available.');
	}
	
}
/* End of file ddibrowser.php */
/* Location: ./controllers/ddibrowser.php */