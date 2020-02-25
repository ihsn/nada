<?php
class Study extends MY_Controller {

	private $user=FALSE;

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->model("Dataset_model");
		$this->load->model("Catalog_model");
		$this->load->model("Survey_type_model");
        $this->load->model("Resource_model");
        $this->load->model("Citation_model");
		$this->load->model("Data_file_model");
		$this->load->model("Related_study_model");
        $this->load->model("Variable_model");
		$this->load->library("Metadata_template");
		$this->load->helper("resource_helper");
		$this->load->helper("metadata_view");
		$this->load->helper('array');
		$this->lang->load('general');
		$this->lang->load('ddibrowser');
		$this->lang->load("catalog_search");
		$this->load->helper('catalog');
		//$this->output->enable_profiler(TRUE);

		if ($this->ion_auth->logged_in()){
			$this->user=$this->ion_auth->current_user();
		}		
	}
	
	function index()
	{
		
	}	


	//study metadata
	function metadata($sid=NULL)
	{
		$this->load->helper('array');
		$survey=$this->Dataset_model->get_row_detailed($sid);

		if (!$survey){
			show_404();
		}

		if(!is_array($survey['metadata'])){
			$survey['metadata']=array($survey['metadata']);
		}

		$json_ld=$this->load->view('survey_info/dataset_json_ld',$survey,true);
		$this->template->add_js($json_ld,'inline');
		
		$this->metadata_template->initialize($survey['type'],$survey);
		$output=$this->metadata_template->render_html();

		//set page description meta tag
		$meta_description=$this->generate_survey_abstract($survey['metadata']);

		if(!empty($meta_description)){
			$this->template->add_meta($name="description", $meta_description,$type='pair');
		}

		$output=$this->load->view('survey_info/metadata', array('content'=>$output), TRUE);
		$this->render_page($sid, $output, $active_tab='study_description');
	}


	public function data_dictionary($sid)
	{
		$this->load->model("Variable_group_model"); 
		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$options['variable_groups_html']=$this->Variable_group_model->get_vgroup_tree_html($sid);
        $options['sid']=$sid;
		$options['content']=$this->load->view('survey_info/data_files',$options,TRUE);
		$content=$this->load->view('survey_info/data_dictionary_layout',$options,TRUE);
		$this->render_page($sid, $content,'data_dictionary');
	}


	public function variable_groups($sid,$vgid=null)
	{
		$this->load->model("Variable_group_model"); 
		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$options['variable_groups_html']=$this->Variable_group_model->get_vgroup_tree_html($sid);
		$options['sid']=$sid;

		if($vgid){
			$options['variable_group']=$this->Variable_group_model->get_single_group($sid,$vgid);
			$options['content']=$this->load->view('survey_info/variable_group',$options,TRUE);
		}
		else{
			$options['content']=$this->load->view('survey_info/variable_groups',$options,TRUE);
		}
		
		$content=$this->load->view('survey_info/data_dictionary_layout',$options,TRUE);
		$this->render_page($sid, $content,'data_dictionary');
	}



    //show info for a single data file
	public function data_file($sid, $file_id,$var_id=null)
    {
		if ($var_id){
			return $this->variable($sid,$file_id,$var_id);
		}

		$offset=(int)$this->input->get("offset");
		$limit=300;

		$this->lang->load('ddi_fields');
		$this->load->model("Variable_group_model");
        $options['sid']=$sid;
		$options['file_id']=$file_id;
		$options['variable_groups_html']=$this->Variable_group_model->get_vgroup_tree_html($sid);
		$options['file_list']=$this->Data_file_model->get_all_by_survey($sid);
        $options['file']=$this->Data_file_model->get_file_by_id($sid,$file_id);
		$options['variables']=$this->Variable_model->paginate_file_variables($sid, $file_id,$limit,$offset);
		$options['file_variables_count']=$this->Variable_model->get_file_variables_count($sid,$file_id);

		//variable pagination
		$this->load->library('pagination');

		$config['base_url'] = current_url();
		$config['total_rows'] = $options['file_variables_count'];
		$config['per_page'] = $limit;
		$config['page_query_string'] = TRUE;
		$config['reuse_query_string'] = TRUE;
		$config['query_string_segment'] = 'offset';
				
		$this->pagination->initialize($config);
		$options['variable_pagination']=$this->pagination->create_links();

        if (!$options['file']){
            show_404();
		}
		
        $content=$this->load->view('survey_info/variables_by_file',$options,TRUE);
        $this->render_page($sid, $content,'data_dictionary');
    }



	public function variable($sid,$file_id=null, $var_id=null)
    {
		if (!$file_id && !$var_id){
			show_error("NO_FILE_OR_VARIABLE_SET");
		}

		$this->lang->load('ddi_fields');
		$this->lang->load('fields_timeseries');

		//support for older URLs without data file ID in the URL
		if(!$var_id){
			
			//reset var_id and file_id variables
			$var_id=$file_id;

			//get internal fid for file
			$file_id=$this->Data_file_model->get_fid_by_varid($sid,$var_id);

			if(!$file_id){
				show_404();
			}
		}

		//get file info
		$file_info=$this->Data_file_model->get_file_by_id($sid,$file_id);

		if (!$file_info){
			show_404();
		}

		$dataset_type=$this->Dataset_model->get_type($sid);

		//default variable template
		$variable_template='timeseries';

		switch($dataset_type){
			case 'survey':
				$variable_template='variable_ddi';
			break;

			case 'timeseries':
				$variable_template='timeseries';
			break;
		}
		//$variable_template='variable_ddi';

        $options['sid']=$sid;
        $options['file_id']=$file_id;
        $options['var_id']=$var_id;
		$options['file']=$file_info;		
		$options['variable']=$this->Variable_model->get_by_var_id($sid, $file_id, $var_id);

		if($this->input->is_ajax_request()){
			$content=$this->load->view('survey_info/'.$variable_template,$options,TRUE);
			return $this->render_page($sid, $content,'data_dictionary');
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$options['content']=$this->load->view('survey_info/'.$variable_template,$options,TRUE);
		$content=$this->load->view('survey_info/data_dictionary_layout',$options,TRUE);
        $this->render_page($sid, $content,'data_dictionary');
    }



	public function related_materials($sid=NULL)
	{
        $this->load->helper("resource_helper");
        $this->load->model('Resource_model');
        $options['resources']=$this->Resource_model->get_grouped_resources_by_survey($sid);
        $options['sid']=$sid;
        $options['survey_folder']=$this->Catalog_model->get_survey_path_full($sid);

        if (!$options['resources']){
            $content="Documentation is not available";
        }
        else{
            $content=$this->load->view('survey_info/related_resources',$options,TRUE);
        }

		$this->render_page($sid, $content,'related_materials');
	}



	public function related_publications($sid)
	{
		$this->load->model('Citation_model');
		$this->load->model('Repository_model');
		$this->load->library('chicago_citation');
		$this->lang->load("resource_manager");
		$this->load->helper("resource_helper");
		$this->load->model('Resource_model');//get survey related citations
		$options['citations']=$this->Citation_model->get_citations_by_survey($sid,$this->input->get('sort_by'),$this->input->get('sort_order'));
		$content=$this->load->view('catalog_search/survey_summary_citations',$options,TRUE);
        $options['sid']=$sid;
		$this->render_page($sid, $content,'related_citations');
	}


	public function related_datasets($sid)
	{
		$this->load->model('Related_study_model');
		$related_studies=$this->Related_study_model->get_related_studies_list($sid);
		$related_studies_formatted=$this->load->view('survey_info/related_studies',array('related_studies'=>$related_studies),true);
		$this->render_page($sid, $related_studies_formatted,'related_datasets');
	}

	
	public function get_microdata($sid)
	{
		//get study data access type
		$data_access_type=$this->Catalog_model->get_survey_form_model($sid);
				
		if($data_access_type=='data_enclave'){
			$data_access_type='enclave';
		}

		$this->load->driver('data_access',array('adapter'=>$data_access_type));

		if ($this->data_access->is_supported($data_access_type)){
			$content=$this->data_access->process_form($sid,$user=$this->ion_auth->current_user());
			if($content==''){
				$content='NOT_DATA_AVAILABLE';
			}
		}
		else{
			$content="Data Access Not Available";
		}

		$options['sid']=$sid;
		$this->render_page($sid, $content,'get_microdata');
	}


	
	private function render_page($sid, $content, $active_tab='study_description')
	{
		$this->db_logger->increment_study_view_count($sid);

        if($this->input->get("print")){
            $this->template->set_template('blank');
        }

        if($this->input->get("ajax")){
            echo $content;return;
        }

        $survey=$this->Dataset_model->get_row($sid);
        $data_access_type=$survey['data_access_type'];
		$published=$survey['published'];
		
		$has_datafiles=$this->Dataset_model->has_datafiles($sid);

        //get citations count for the survey
		$related_citations_count=$this->Citation_model->get_citations_count_by_survey($sid);
		
        /*if ($related_citations_count) {
            $related_citations_count= @$related_citations_count[$sid];
        }*/

        //get a count of related resources for the survey
		$related_resources_count=$this->Resource_model->get_resources_count_by_survey($sid);
		
		//get related studies
		$related_studies=$this->Related_study_model->get_related_studies_list($sid);

		//formatted related studies
		$related_studies_formatted=$this->load->view('survey_info/related_studies',array('related_studies'=>$related_studies),true);

		$page_tabs=array(
			'study_description'=>array(
				'label'=>'Study description',
				'hover_text'=>'Related documentation: questionnaires, reports, technical documents, tables',
				'url'=>site_url("catalog/$sid/study-description"),
                'show_tab'=>1
			),
			'related_materials'=>array(
				'label'=>t('related_materials'),
				'hover_text'=>'Related documentation: questionnaires, reports, technical documents, tables',
				'url'=>site_url("catalog/$sid/related-materials"),
                'show_tab'=>(int)$related_resources_count
			),			
			'data_dictionary'=>array(
				'label'=>t('data_dictionary'),
				'hover_text'=>'Related documentation: questionnaires, reports, technical documents, tables',
				'url'=>site_url("catalog/$sid/data-dictionary"),
                'show_tab'=>$has_datafiles
			),
			'get_microdata'=>array(
				'label'=>t('get_microdata'),
				'hover_text'=>'Related documentation: questionnaires, reports, technical documents, tables',
				'url'=>site_url("catalog/$sid/get-microdata"),
                'show_tab'=>1
			),
			'related_citations'=>array(
				'label'=>t('related_citations'),
				'hover_text'=>'Related documentation: questionnaires, reports, technical documents, tables',
				'url'=>site_url("catalog/$sid/related-publications"),
                'show_tab'=>(int)$related_citations_count
			),
			'related_datasets'=>array(
				'label'=>t('related_datasets'),
				'hover_text'=>'Related datasets',
				'url'=>site_url("catalog/$sid/related-datasets"),
                'show_tab'=>count($related_studies)
			),
		);

		$options=array(
			'published'=>$published,
			'sid'=>$sid,
			'dataset_type'=>$survey['type'],
			'survey'=>$this->get_survey_info($sid),
			'page_tabs'=>$page_tabs,
			'active_tab'=>$active_tab,
			'data_access_type'=>$data_access_type,
			'body'=>$content,
			'has_related_materials'=>$related_resources_count,
            'has_citations'=>$related_citations_count,
            'has_data_dictionary'=>$has_datafiles,
			'survey_title'=>$survey['title'],
			'related_studies_count'=>count($related_studies),
			'related_studies_formatted'=>$related_studies_formatted
		);
		
		
		$this->template->write('title', $this->generate_survey_title($survey),true);
		$this->template->add_variable("body_class","container-fluid-n");
		$html= $this->load->view('survey_info/layout',$options,true); 
		$this->template->write('survey_title', "survey title",true);
		$this->template->write('content', $html,true);
		$this->template->render();
	}




	/**
	*
	* Get survey basic informatoin
	**/
	private function get_survey_info($id)
	{
		$survey=$this->Dataset_model->get_row_detailed($id);

		if ($survey===FALSE || count($survey)==0){
			show_error('STUDY_NOT_FOUND');
		}

		$this->load->model("Repository_model");
		$this->load->model("Resource_model");
		$survey['repositories']=$this->Catalog_model->get_survey_repositories($id);
		$survey['owner_repo']=$this->Repository_model->get_survey_owner_repository($id);
		$survey['has_resources']=$this->Resource_model->has_external_resources($id);
		$survey['storage_path']=$this->Dataset_model->get_storage_fullpath($id);

		if (!$survey['owner_repo']){
			$survey['owner_repo']=$this->Repository_model->get_central_catalog_array();
		}

		return $survey;
	}


	/**
	*
	* Variable Search
	*
	**/
	public function search($id)
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

		//show the search box for non-ajax request
		if(!$this->input->is_ajax_request()){			
			$html=$this->load->view('survey_info/search',array('sid'=>$id),TRUE);
		}

		if ($this->input->get('vk')!=''){
			//show search result
			$data['variables']=$this->catalog_search->v_quick_search($id,$limit=50);
			$data['sid']=$id;
			$html.=$this->load->view('survey_info/search_variable_list',$data,TRUE);
		}

		//print html without header/footers
		if($this->input->is_ajax_request()){			
			die($html);
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($id);
		$options['content']=$html;
		$content=$this->load->view('survey_info/data_dictionary_layout',$options,TRUE);

		$this->render_page($id, $content,'data_dictionary');
	}



	/**
	*
	* Export DDI to PDF or HTML Format
	*
	**/
	function pdf_documentation($sid=null)
	{
		if (!is_numeric($sid)){
			show_404();
		}

		$survey=$this->get_survey_info($sid);
		$report_file=unix_path($survey['storage_path'].'/ddi-documentation-'.$this->config->item("language").'-'.$survey['id'].'.pdf');

		if (!file_exists($report_file)){
			show_error("PDF_NOT_AVAILABLE");
		}

		$this->load->helper('download');
		@log_message('info','Downloading file <em>'.$report_file.'</em>');
		force_download2($report_file);exit;
	}


	/**
	*
	* Download DATA/MICRODATA files for public use and direct only
	**/
	function download($survey_id,$resource_id)
	{
		if (!is_numeric($resource_id) || !is_numeric($survey_id)){
			show_404();
		}

		$this->load->model('Resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('Public_model');

		$resource=$this->Resource_model->select_single($resource_id);
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		if ($resource===FALSE){
			show_error(t('RESOURCE_NOT_FOUND'));
		}

		$file_name=trim($resource['filename']);

		if ($file_name==''){
			$this->db_logger->write_log('download-file-not-found',$survey_id,'resource='.$resource_id);
			show_error('RESOURCE_NOT_AVAILABLE!');
		}

		$dataset_folder=$this->Dataset_model->get_storage_fullpath($survey_id);

		//full path to the resource
		$resource_path=unix_path($dataset_folder.'/'.$file_name);

		if (!file_exists($resource_path)){
			show_error('RESOURCE_NOT_FOUND');
		}

		$allow_download=FALSE;			//allow download or not
		$resource_is_microdata=FALSE; //whether a resource is a microdata fiel

		//apply checks before download MICRODATA files
		$microdata_types=array('[dat/micro]','[dat]');
		foreach($microdata_types as $type){
			if (stripos($resource['dctype'],$type)!==FALSE){
				$resource_is_microdata=TRUE;
			}
		}

		if($data_access_type=='public' && $resource_is_microdata===TRUE){
			if(!$this->user){
				redirect('catalog/'.$survey_id.'/get_microdata','refresh');exit;
			}

			//check if user has filled the PUF form for a study
			$request_exists=$this->Public_model->check_user_has_data_access($this->user->id,$survey_id);

			if ($request_exists===FALSE){
				redirect('catalog/'.$survey_id.'/get_microdata','refresh');
			}

			$allow_download=TRUE;
		}
		else if($data_access_type=='licensed')
		{
			//non-microdata requests
			if($resource_is_microdata===FALSE){
				$allow_download=TRUE;
			}
			else{
				//Deny licensed requests
				$this->db_logger->write_log('download-denied-not-microdata',$survey_id,'resource='.$resource_id);
				show_error("RESOURCE_NOT_AVAILABLE.");
			}
		}
		else if($data_access_type=='direct' || $data_access_type=='open'){
			$allow_download=TRUE;
		}
		else if($data_access_type=='data_na' ){
			$allow_download=TRUE;
		}
		else if ($data_access_type=='public' && !$resource_is_microdata){
			$allow_download=TRUE;
		}
		else{
			if ($resource_is_microdata===TRUE){
				//for any other data access type, disable downloads of microdata resources
				show_error("INVALID_REQUEST");
			}
			else{
				$allow_download=TRUE;
			}
		}

		if ($allow_download){
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$resource_path.'</em>');
			$this->db_logger->write_log('download',basename($resource_path),($resource_is_microdata ? 'microdata': 'resource'),$survey_id);
			$this->db_logger->increment_study_download_count($survey_id);
			force_download2($resource_path);
		}
		else{
			$this->db_logger->write_log('download-denied-2:'.$data_access_type,$survey_id,'resource='.$resource_id);
			show_error("RESOURCE_NOT_AVAILABLE");
		}

	}


	/**
	*
	* Return Language info with language file (.xml) path
	**/
	private function get_language()
	{
		$language=array('lang_name'=>$this->config->item("language"));

		if(!$language){
			$language=array('lang_name'=>"english");
		}

		//get the xml translation file path
		$language_file=$this->DDI_Browser->get_language_path($language['lang_name']);

		if ($language_file){
			//change to the language file (without .xml) in cache
			$language['lang']=unix_path(FCPATH.$language_file);
		}

		return $language;
	}

		
	//todo: need to test
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


	private function generate_survey_title($surveyObj)
	{
		$title=array();
		$title[]=$surveyObj['nation'];
		$title[]=$surveyObj['title'];
		return implode(" - ", $title);
	}


	private function generate_survey_abstract($survey_metadata=null)
	{	
		$meta_fields=array(
			'study_desc/study_info/abstract',
			'study_desc/series_statement/series_info',
			'study_desc/study_info/notes'
		);
		
		foreach($meta_fields as $meta_field){
			$abstract=get_array_nested_value($survey_metadata,$meta_field);
			if (!empty($abstract)){
				return str_replace(array('"',"\r\n","\r","\n"), " ", $abstract);
			}
		}
	}


}
/* End of file study.php */
/* Location: ./controllers/study.php */
