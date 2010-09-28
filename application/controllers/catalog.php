<?php
class Catalog extends MY_Controller {

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		
       	$this->template->set_template('default');	
		$this->template->write('sidebar', $this->_menu(),true);	
		$this->load->model('Search_helper_model');
		$this->load->library('pagination');
	 	//$this->output->enable_profiler(TRUE);
    		
		//language files
		$this->lang->load('general');
		$this->lang->load('catalog_search');
		
		//configuration settings
		$this->limit= $this->get_page_size();
		$this->topic_search=($this->config->item("topic_search")===FALSE) ? 'no' : $this->config->item("topic_search");
		$this->regional_search=($this->config->item("regional_search")===FALSE) ? 'no' : $this->config->item("regional_search");		
	}

	/**
	*
	* Return the page size from querystring, session, config
	*
	**/
	function get_page_size()
	{
		//default from the config
		$limit=($this->config->item("catalog_records_per_page")===FALSE) ? 15 : $this->config->item("catalog_records_per_page");
		
		//check session
		$sess_ps=$this->session->userdata('catalog_page_size');
		
		if(is_numeric($sess_ps))
		{
			$limit=$sess_ps;
		}
		
		//check from querystring
		$ps=$this->input->get_post("ps");

		if (is_numeric($ps))
		{
			$limit=$ps;
		}
		
		if (!is_numeric($limit))
		{
			return 15;
		}
		
		//save in the session
		$this->session->set_userdata('catalog_page_size',$limit);		

		return $limit;
	}
 
 	/**
	* Catalog search default page
	*
	*/
	function index()
	{	
		if ($this->input->get('ajax') )
		{
			$this->search();return;
		}

		$this->template->add_js('javascript/datacatalog.js');
		$this->template->add_css('javascript/jquery/themes/ui-lightness/ui.core.css');
		$this->template->add_css('javascript/jquery/themes/ui-lightness/ui.base.css');
		$this->template->add_css('javascript/jquery/themes/ui-lightness/ui.accordion.css');
		$this->template->add_css('javascript/jquery/themes/ui-lightness/ui.theme.css');

		$this->template->add_css('themes/'.$this->template->theme().'/datacatalog.css');	
		$this->template->add_js('javascript/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/ui.accordion.js');		
		$this->template->add_js('javascript/jquery.blockui.js');		

		//js & css for ceebox
		$this->template->add_css('javascript/ceebox/css/ceebox-min.css');
		$this->template->add_js('javascript/ceebox/js/jquery.ceebox-min.js');

		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$this->limit;
		$view=$this->input->get("view");

		if ($this->input->get('vk')!='' && $view=='v')
		{
			//variable search
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
			$surveys=$this->catalog_search->vsearch($this->limit,$offset);
			$surveys['current_page']=$page;
			$data['search_result']=	$this->load->view('catalog_search/variable_list', $surveys,true);
		}
		else
		{		
			//study search view
			$params=array(
				'study_keywords'=>$this->input->get_post('sk'),
				'variable_keywords'=>$this->input->get_post('vk'),
				'variable_fields'=>$this->input->get_post('vf'),
				'countries'=>$this->input->get_post('country'),
				'topics'=>$this->input->get_post('topic'),
				'from'=>$this->input->get_post('from'),
				'to'=>$this->input->get_post('to')
			);
				
			//intialize search class
			$this->load->library('catalog_search',$params);			
			$surveys=$this->catalog_search->search($this->limit,$offset);
			$surveys['current_page']=$page;
			$data['search_result']=$this->load->view('catalog_search/survey_list', $surveys,true);
		}
		
		//get list of active countries
		$data['countries']=$this->Search_helper_model->get_active_countries();
		
		//get active topics
		//$data['topics']=$this->Search_helper_model->get_active_topics();
		
		$this->load->model('term_model');
		
		//list of topics attached to a survey
		$survey_topics=$this->term_model->get_survey_topics_array();
		
		//get vocabulary id from config
		$vid=$this->config->item("topics_vocab");
		
		if ($vid!==FALSE && is_numeric($vid))
		{				
			//get all topics
			$topics_array=$this->term_model->get_terms_tree_array($vid,$tid=0);
			
			$data['topics_formatted']=$this->load->view('catalog_search/formatted_topics_list',
										array('topics'=>$topics_array,'filter'=>$survey_topics ),
										TRUE);
		}
		else
		{
			//hide the topics box
			$this->topic_search=FALSE;
		}
		//$this->term_model->get_formatted_terms_tree(3,0,$show_only=$topics_array);
		
		//get years
		$min_year=$this->Search_helper_model->get_min_year();
		$max_year=$this->Search_helper_model->get_max_year();

		foreach (range($min_year, $max_year) as $year) 
		{
        	$data['years'][$year]=$year;
        }
		
//		var_dump($data['years']);exit;
						
		//show search form
		$content=$this->load->view('catalog_search/search_form', $data,true);

		//render final output		
		$this->template->write('title', t('title_data_catalog'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	
	function search()
	{
		//$this->output->enable_profiler(TRUE);
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$this->limit;
		$view=$this->input->get("view");

		//log
		$this->db_logger->write_log('search',$this->input->get("sk"),'study');
		$this->db_logger->write_log('search',$this->input->get("vk"),'question');

		//which view to use for display	
		if ($this->input->get('vk')!='' && $view=='v')
		{
			//variable search
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
			$data=$this->catalog_search->vsearch($this->limit,$offset);

			$data['current_page']=$page;
			$this->load->view('catalog_search/variable_list', $data);
			return;
		}
		
		//$surveys=$this->Advanced_search_model->search($this->limit,$offset);		
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
		$surveys=$this->catalog_search->search($this->limit,$offset);
		//$surveys=$this->cache->model('advanced_search_model', 'search', array($limit, $offset), 30);

		$surveys['current_page']=$page;				
		$this->load->view('catalog_search/survey_list', $surveys);
	}
	
	/**
	* variable search
	*
	*/
	function vsearch($surveyid=NULL)
	{
		if ($surveyid==NULL || !is_numeric($surveyid))
		{
			echo t('error_invalid_parameters');
			return;
		}
		
		//$data['variables']=$this->Advanced_search_model->v_quick_search($surveyid);
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
		$data['variables']=$this->catalog_search->v_quick_search($surveyid);

		$this->load->view("catalog_search/var_quick_list", $data);
	}

	function compare($option=NULL, $format=NULL)
	{
		$this->load->library('compare_variable');
		$list=NULL;
		$compare_items=$this->session->userdata('compare');

		if ($compare_items)
		{
			foreach($compare_items as $item=>$value)
			{
				$tmp=explode(':',$item);
				if (isset($tmp[1]))
				{
					$list[]=array('surveyid'=>$tmp[0], 'varid'=>$tmp[1]);
				}	
			}
		}
		
		$data['list']=$list;	
		if ($option=='print')
		{
			if ($format!=='pdf')
			{
				$this->load->view("catalog_search/compare_print",$data);
			}
			else if ($format==='pdf')
			{
				$this->load->library('pdf_export');
				$contents=$this->load->view("catalog_search/compare_print",$data,TRUE);
				//echo $contents;
				//$contents="some html text";
				$this->pdf_export->create_pdf($contents);
				exit;
			}	
		}
		else
		{
			$this->load->view("catalog_search/compare",$data);
		}	
	}
	
	function compare_add($surveyid,$varid)
	{
		//maximum variables to compare
		$max_compare_limit=5;
		
		if (is_numeric($surveyid) && $this->input->xss_clean($varid)!='')
		{
			$compare_items=$this->session->userdata('compare');
						
			if (!is_array($compare_items))
			{
				$compare_items=array();
			}
			if ( count($compare_items)>=$max_compare_limit)
			{
				return;
			}
			
			$compare_items[$surveyid.':'.$varid] = 1;
			$this->session->set_userdata('compare',$compare_items);
			
			//var_dump($this->session->userdata('compare'));
		}			
	}
	
	
	function compare_remove($surveyid,$varid)
	{		
		if (is_numeric($surveyid) && $this->input->xss_clean($varid)!='')
		{
			$compare_items=$this->session->userdata('compare');
			if ($compare_items)
			{
				$compare_items=$this->session->userdata('compare');
			}
			
			unset($compare_items[$surveyid.':'.$varid]);
			$this->session->set_userdata('compare',$compare_items);
			
			//var_dump($this->session->userdata('compare'));
		}		
	}
	
	
	function compare_remove_all()
	{
		$this->session->unset_userdata('compare');
	}
	
	
	function filter_by_country()
	{
		$countries=$this->input->xss_clean($this->input->get('country'));
		$year_from=(integer)$this->input->xss_clean($this->input->get('from'));
		$year_to=(integer)$this->input->xss_clean($this->input->get('to'));

		if (!is_array($countries))
		{
			exit;
		}
		
		$this->load->model('Search_helper_model');
		
		$data=$this->Search_helper_model->filter_by_countries($countries,$year_from, $year_to);
		echo json_encode($data);		
	}

	/**
	* return data to filter search box by topic
	*
	*/
	function filter_by_topic()
	{
		$topics=$this->input->get('topic');
		$min_year=(integer)$this->input->get('from');
		$max_year=(integer)$this->input->get('to');

		if (!is_array($topics))
		{
			exit;
		}

		$this->load->model('Search_helper_model');
		
		//$countries=$this->Search_helper_model->get_countries_by_topics($topics);
		//get filtered list of countries and min/min years
		$data=$this->Search_helper_model->filter_by_topics($topics,$min_year,$max_year);		
		echo json_encode($data);
	}

	/**
	* return data to filter search box by topic
	*
	*/
	function filter_by_years()
	{
		$min_year=$this->input->get('from');
		$max_year=$this->input->get('to');

		if (!is_numeric($min_year) || !is_numeric($max_year))
		{
			return false;
		}
		
		//$countries=$this->Search_helper_model->get_countries_by_topics($topics);
		//get filtered list of countries and min/min years
		$data=$this->Search_helper_model->filter_by_years($min_year, $max_year);
		
		if (!isset($data["topics"]))
		{
			$data['topics']=array('NULL');
		}
		if (!isset($data["countries"]))
		{
			$data['countries']=array('NULL');
		}		
		
		echo json_encode($data);
	}
	
	/**
	* Search help page
	*
	*/
	function help()
	{
		if ($this->uri->segment(4)!==FALSE)
      {
        show_404();
      }  
      	
		$this->template->set_template('blank');	
		$this->template->write('title', t('catalog_search_help'),true);
		$contents=$this->load->view('catalog_search/search_help', NULL,true);
		
		$this->template->write('content', $contents,true);
	  	$this->template->render();		
	}
	
	/**
	* Data Catalog RSS feeds
	*
	* By default shows 50 latest surveys
	*
	* //TODO: 
	*	- get all records
	* 	- get all records as zip file
	* 	- get data by date ranges
	* 	- paginate?
	*/
	function rss()
	{	
		$this->load->model('Catalog_model');
		
		$limit=50;
		
		if (is_numeric($this->input->get('limit')))
		{
			$limit=$this->input->get('limit');
		}

		
		$data['records']=$this->Catalog_model->select($limit,$offset=0,$sort_by='changed',$sort_order='desc');

		header("Content-Type: application/xml");
        $contents=$this->load->view('catalog_search/rss', $data,TRUE);
		
		if ($this->input->get('format')=='zip')
		{
			$this->_rss_zip($contents);
		}
		else
		{
			echo $contents;
		}
	}
	
	/**
	* Creates a zip file for data catalog rss
	*
	*/
	function _rss_zip($data)
	{
		$this->load->library('zip');
		
		$name = 'rss.txt';
		$this->zip->add_data($name, $data);

		//start file download
		$this->zip->download('rss.zip');
	}

	/**
	* Returns survey external resources (RDF) 
	*
	* 
	*/
	function rdf($id=NULL)
	{
		if (!is_numeric($id) )
		{
			show_404();return;
		}		
	
		$this->load->model('Catalog_model');
		header("Content-Type: application/xml");
		echo $this->Catalog_model->get_survey_rdf($id);
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
	* display survey information by survey id
	*/
	function survey($id=NULL)
	{				
		if (!is_numeric($id))
		{
			show_404();
		}
		
		$this->load->model('Catalog_model');
		$this->load->model('Citation_model');
		$this->load->library('chicago_citation');
						
		//get survey
		$survey=$this->Catalog_model->select_single($id);		
		
		if ($survey===FALSE || count($survey)==0)
		{
			show_error('STUDY_NOT_FOUND');
		}

		if ($this->input->get('ajax') || $this->input->get('print') )
		{
			$this->template->set_template('blank');	
		}
		
		//$this->template->add_css('css/admin.css');	
					
		//get survey folder path - NEEDED BY THE VIEW
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);

		//get external resources
		$survey['resources']=$this->Catalog_model->get_grouped_resources_by_survey($id);
		
		//get survey related citations
		$survey['citations']=$this->Citation_model->get_citations_by_survey($id);
		
		$content_body=$this->load->view('catalog_search/survey_summary',$survey,TRUE);		
		$this->template->write('title', t('study_information'),true);
		$this->template->write('content', $content_body,true);
		
		//render final output
	  	$this->template->render();
	}

	/**
	* show study related citations
	*
	*/
	function citations($id=NULL)
	{				
		if (!is_numeric($id))
		{
			show_404();
		}
		
		$this->load->model('Catalog_model');
		$this->load->model('Citation_model');
		$this->load->library('chicago_citation');
						
		//get survey
		$survey=$this->Catalog_model->select_single($id);
		
		if ($survey===FALSE)
		{
			show_404();
		}
		//$this->template->set_template('blank');	
		
		if ($this->input->get('ajax') || $this->input->get('print') )
		{
			$this->template->set_template('blank');	
		}
		
		//get survey folder path - NEEDED BY THE VIEW
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);

		//get survey related citations
		$survey['citations']=$this->Citation_model->get_citations_by_survey($id);
		//get survey basic info
		$survey['survey']=$this->Catalog_model->get_survey($id);
		
		$content_body=$this->load->view('catalog_search/survey_summary_citations',$survey,TRUE);		
		$this->template->write('title', t('citations'),true);
		$this->template->write('content', $content_body,true);
	  	$this->template->render();
	}

	/**
	* Download survey related files e.g. questionnaire, reports, etc
	*
	*/
	function download($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_404();
		}
		
		$file=$this->uri->segment(4);
	
		if ($file=='')
		{
			show_404();
		}

		$file_name=base64_decode($file);
		
		//required for getting ddi file path
		$this->load->model('Catalog_model');
				
		//get ddi file path from db
		$folder_path=$this->Catalog_model->get_survey_path_full($id);
	
		//complete file path	
		$file_path=$folder_path .'/'.$file_name;

		if (file_exists($file_path))
		{
			$this->load->helper('download');
			//download the file
			force_download2($file_path);
			return;
		}
		else
		{
			$file_name=prep_url($file_name);
			echo t('msg_website_redirect').' ';
			echo anchor($file_name,$file_name);
			echo js_redirect($file_name,0);
		}
	}
	
	
	function export($format='doc')
	{
		//$this->output->enable_profiler(TRUE);
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$this->limit;
		$view=$this->input->get("view");

		//log
		$this->db_logger->write_log('search',$this->input->get("sk"),'study');
		$this->db_logger->write_log('search',$this->input->get("vk"),'question');

		//switch to variable view
		if ($this->input->get('vk')!='' && $view=='v')
		{
			//variable search
			//$surveys=$this->Advanced_search_model->vsearch(1000,$offset);
			$params=array(
				'study_keywords'=>$this->input->get_post('sk'),
				'variable_keywords'=>$this->input->get_post('vk'),
				'variable_fields'=>$this->input->get_post('vf'),
				'countries'=>$this->input->get_post('country'),
				'topics'=>$this->input->get_post('topic'),
				'from'=>$this->input->get_post('from'),
				'to'=>$this->input->get_post('to')
			);		
			$this->load->library('catalog_search',$params);
			$surveys=$this->catalog_search->vsearch($limit=5000);
			
			$surveys['current_page']=$page;
		}
		else
		{
			//survey view
			//$surveys=$this->Advanced_search_model->search($limit=200,$offset);
			$params=array(
				'study_keywords'=>$this->input->get_post('sk'),
				'variable_keywords'=>$this->input->get_post('vk'),
				'variable_fields'=>$this->input->get_post('vf'),
				'countries'=>$this->input->get_post('country'),
				'topics'=>$this->input->get_post('topic'),
				'from'=>$this->input->get_post('from'),
				'to'=>$this->input->get_post('to')
			);		
			$this->load->library('catalog_search',$params);
			$surveys=$this->catalog_search->search($limit=200);			
		}
		
		//echo '<pre>';
		//var_dump($surveys);exit;
		
		switch($format)
		{
			case 'doc':
				header("Content-type: application/vnd.ms-word");
				header('Content-Disposition: attachment; filename="search-export-'.date("U").'.doc"');
				$this->load->view('catalog_search/export_word',$surveys);
				return;
			break;
			
			default:
				//bulid a list of fields for export
				$export_array=array();
				foreach($surveys['rows'] as $row)
				{
					$row=(object)$row;
					$export_array[]=array
						(
							'surveyid'=>$row->refno,
							'title'=>$row->titl,
							'country'=>$row->nation,
							'primary-investigator'=>$row->authenty,
							'year'=>$row->proddate,
							'study-url'=>site_url().'/catalog/'.$row->id,
						);
				}
				
				
				header("Content-type: application/octet-stream");
				header('Content-Disposition: attachment; filename="search-export-'.date("U").'.csv"');
				
				echo $this->_array_to_scv($export_array);
				echo "\r\n\r\n\r\n";
				echo t('data_catalog').':, '. site_url().'/catalog/';
				echo "\r\n";
				echo 'Date:, '. date("M-d/Y",date("U"));
					
		}
	}

/**
* Generatting CSV formatted string from an array.
* By Sergey Gurevich.
*/
function _array_to_scv($array, $header_row = true, $col_sep = ",", $row_sep = "\n", $qut = '"')
{
	$output='';
	//Header row.
	if ($header_row)
	{
		foreach ($array[0] as $key => $val)
		{
			//Escaping quotes.
			$key = str_replace($qut, "$qut$qut", $key);
			$output .= "$col_sep$qut$key$qut";
		}
		$output = substr($output, 1)."\n";
	}
	
	
	//Data rows.
	foreach ($array as $key => $val)
	{
		$tmp = '';
		foreach ($val as $cell_key => $cell_val)
		{
			//Escaping quotes.
			$cell_val = str_replace($qut, "$qut$qut", utf8_decode($cell_val));
			$tmp .= "$col_sep$qut$cell_val$qut";
		}
		$output .= substr($tmp, 1).$row_sep;
	}
	
	return $output;
}
}
/* End of file catalog.php */
/* Location: ./controllers/catalog.php */