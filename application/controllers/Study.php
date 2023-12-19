<?php
class Study extends MY_Controller {

	private $user=FALSE;

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE); 
		$this->load->model("Dataset_model");
		$this->load->model("Catalog_model");
		$this->load->model("Survey_type_model");
        $this->load->model("Survey_resource_model");
        $this->load->model("Citation_model");
		$this->load->model("Data_file_model");
		$this->load->model("Related_study_model");
		$this->load->model("Variable_model");
		$this->load->model("Timeseries_db_model");
		$this->load->model("Survey_data_api_model");
		$this->load->model("Widget_model");
		
		$this->load->library("Metadata_template");
		$this->load->library("Dataset_manager");
		$this->load->helper("resource_helper");
		$this->load->helper("metadata_view");
		$this->load->helper('array');
		$this->lang->load('general');
		$this->lang->load("catalog_search");
		$this->lang->load('ddibrowser');
		$this->lang->load('resource_manager');
		$this->load->helper("markdown");
		//$this->load->helper('catalog');
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
		$survey=$this->Dataset_model->get_row($sid);

		if (!$survey){
			show_404();
		}

		$survey['metadata']=(array)$this->dataset_manager->get_metadata($sid,$survey['type']);
		$survey['metadata']['iframe_embeds']=$this->Widget_model->widgets_by_study($sid);

		$this->template->add_js('javascript/linkify.min.js');
		$this->template->add_js('javascript/linkify-jquery.min.js');		
		$this->template->add_js('javascript/pym.v1.min.js');

		$json_ld=$this->load->view('survey_info/dataset_json_ld',$survey,true);
		$this->template->add_js($json_ld,'inline');

		$survey['resources']=$this->Survey_resource_model->get_survey_resources_group_by_filename($sid);

		if (in_array($survey['type'], array('script','survey'))){
			$output=$this->render_metadata_html($survey);
		}
		else{		
			$this->metadata_template->initialize($survey['type'],$survey);
			$output=$this->metadata_template->render_html();

			//set page description meta tag
			$meta_description=$this->generate_survey_abstract($survey['metadata']);

			if(!empty($meta_description)){
				$this->template->add_meta($name="description", $meta_description,$type='pair');
			}
		
			$output=$this->load->view('survey_info/metadata', array('content'=>$output), TRUE);
		}

		$this->render_page($sid, $output, $active_tab='description');
	}

	/**
	 * 
	 * Render HTML page with project metadata
	 * 
	 * @project - array
	 * 
	 */
	function render_metadata_html($project)
	{
		$this->load->library("Display_template");
		//try{

			$template=$this->display_template->get_template_project_type($project['type']);

			if (isset($template['template'])){
				$template=$template['template'];
			}

			//get external resources
			$project['resources']=$this->Survey_resource_model->get_survey_resources($project['id']);
			$this->display_template->initialize($project,$template);

			$page_options=array(
                'html'=>$this->display_template->render_html(),
                'sidebar'=>$this->display_template->get_sidebar_items()
            );

            return $this->load->view('display_templates/index',$page_options,true);
			
		/*}
		catch(Exception $e){
			show_error($e->getMessage());
		}*/
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

			case 'geospatial':
				$variable_template='geospatial_features';
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


	public function downloads($sid=NULL)
	{
		return $this->related_materials($sid);
	}

	public function related_materials($sid=NULL)
	{
        $this->load->helper("resource_helper");
        $this->load->model('Survey_resource_model');
		$this->load->model("Form_model");
		$this->load->model("Licensed_model");		
		
		$user=$this->ion_auth->current_user();
		$options['user_id']=isset($user->id) ? $user->id : false;
        $options['resources']=$this->Survey_resource_model->get_grouped_resources_by_survey($sid);
        $options['sid']=$sid;
        $options['survey_folder']=$this->Catalog_model->get_survey_path_full($sid);
		$microdata_resources=$this->Survey_resource_model->get_microdata_resources($sid);
		$microdata_resources= $this->Survey_resource_model->format_resources($microdata_resources);
		$options['microdata_resources']=NULL;
		$options['lic_requests']=NULL;

		$data_access=$this->Form_model->get_form_by_survey($sid);
		
		if(empty($data_access)){
			$options['data_access_type']='data_na';
		}else{
			$options['data_access_type']=$data_access['model'];
		}

		if(isset($data_access['model']) && $data_access['model']=='remote'){
			$options['link_da']=$this->Catalog_model->get_survey_link_da($sid);
		}

		//licensed data
		if ($options['data_access_type']=='licensed' && !empty($user)){

			//licensed requests by user
			$options['lic_requests']=$this->Licensed_model->get_requests_by_study($sid,$user->id,$active_only=FALSE);

			//check if user has access to a resource download
			foreach($microdata_resources as $resource){
				try{
					$has_access=$this->Survey_resource_model->user_has_download_access($user->id,$sid,$resource);
					
					if ($has_access){
						$options['microdata_resources'][]=$resource;
					}
				}
				catch(Exception $e){

				}				
			}
		}

		$content=$this->load->view('survey_info/related_resources',$options,TRUE);
		$this->render_page($sid, $content,'related_materials');
	}



	public function related_publications($sid)
	{
		$this->load->model('Citation_model');
		$this->load->model('Repository_model');
		$this->load->library('chicago_citation');
		$this->lang->load("resource_manager");
		$this->load->helper("resource_helper");
				
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

	public function data_api($sid)
	{
		$api_dataset=$this->Survey_data_api_model->get_by_sid($sid);
		
		if (!$api_dataset){
			show_404();
		}

		$options=array(
            'db_id'=>$api_dataset[0]['db_id'],
            'table_id'=>$api_dataset[0]['table_id'],
        );

        $content=$this->load->view('data_api/preview', $options,true);
		$this->render_page($sid, $content,'data_api');
	}
	
	public function get_microdata($sid)
	{
		$this->load->model("Form_model");	
		$this->load->model("Data_access_whitelist_model");

		$form_obj=$this->Form_model->get_form_by_survey($sid);
		
		if(empty($form_obj)){
			$data_access_type='data_na';
		}else{
			$data_access_type=$form_obj['model'];
		}

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

		$user=$this->ion_auth->current_user();
		
		if($user){
			$user_whitelisted=$this->Data_access_whitelist_model->has_access($user->id,$sid);

			if($user_whitelisted){
				$content=$this->Data_access_whitelist_model->get_data_files($sid);
			}
		}

		$this->render_page($sid, $content,'get_microdata');
	}


	public function timeseries_db($sid)
	{
		$database=$this->Timeseries_db_model->get_database_by_series_id($sid);

		if (empty($database)){
			show_error("Timeseries database does not exist");
		}

		$this->metadata_template->initialize('timeseriesdb',$database);
		$output=$this->metadata_template->render_html();
		$this->render_page($sid, $output,'timeseries_db');
	}
	
	private function render_page($sid, $content, $active_tab='description')
	{
		$this->db_logger->increment_study_view_count($sid);

        if($this->input->get("print")){
            $this->template->set_template('blank');
        }

        if($this->input->get("ajax")){
            echo $content;return;
        }

		$dataset=$this->Dataset_model->get_row($sid);
		$dataset_type=$dataset['type'];
        $data_access_type=$dataset['data_access_type'];
		$published=$dataset['published'];
		
		$dataset_type=$dataset['type']; 
		$published=$dataset['published'];		
		$has_datafiles=$this->Dataset_model->has_datafiles($sid);

        //get citations count for the survey
		$related_citations_count=$this->Citation_model->get_citations_count_by_survey($sid);
		
        /*if ($related_citations_count) {
            $related_citations_count= @$related_citations_count[$sid];
        }*/

        //get a count of related resources for the survey
		$related_resources_count=$this->Survey_resource_model->get_resources_count_by_survey($sid);

		//count microdata resources
		$microdata_resources_count=$this->Survey_resource_model->get_microdata_resources_count_by_survey($sid);
		
		//get related studies
		$related_studies=$this->Related_study_model->get_related_studies_list($sid);

		//formatted related studies
		$related_studies_formatted=$this->load->view('survey_info/related_studies',array('related_studies'=>$related_studies),true);

		//timeseries database
		$timeseries_db=null;

		if($dataset_type=='timeseries'){
			$timeseries_db=$this->Timeseries_db_model->get_database_by_series_id($sid);
		}

		//data api
		$has_data_api=$this->Survey_data_api_model->get_by_sid($sid);

		//default layout template view
		$display_layout='survey_info/layout';

		switch($dataset_type){
			case 'survey':
			case 'microdata':
			case 'geospatial':
				$page_tabs=array(
					'description'=>array(
						'label'=>t('microdata_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),							
					'data_dictionary'=>array(
						'label'=>t('data_dictionary'),
						'url'=>site_url("catalog/$sid/data-dictionary"),
						'show_tab'=>$has_datafiles
					),					
					'related_materials'=>array(
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
						'show_tab'=>(int)$related_resources_count
					),	
					'get_microdata'=>array(
						'label'=>t('get_microdata'),
						'url'=>site_url("catalog/$sid/get-microdata"),
						'show_tab'=>1
					),
					'related_citations'=>array(
						'label'=>t('related_citations'),
						'url'=>site_url("catalog/$sid/related-publications"),
						'show_tab'=>(int)$related_citations_count
					),
					'related_datasets'=>array(
						'label'=>t('related_datasets'),
						'url'=>site_url("catalog/$sid/related-datasets"),
						'show_tab'=>count($related_studies)
					),
					'data_api'=>array(
						'label'=>t('Data Api'),
						'url'=>site_url("catalog/$sid/data-api"),
						'show_tab'=>$has_data_api
					)
				);
				break;
			case 'video':
			case 'image':
				$page_tabs=array(
					'description'=>array(
						'label'=>t($dataset_type.'_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					'related_materials'=>array(
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
						'show_tab'=>(int)$related_resources_count
					)
				);
				break;
			case 'timeseries':
				$page_tabs=array(
					'description'=>array(
						'label'=>t($dataset_type.'_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					'timeseries_db'=>array(
						'label'=>t('timeseries_db'),
						'url'=>site_url("catalog/$sid/timeseries-db"),
						'show_tab'=>!empty($timeseries_db)
					),
					//hide related materials
					'related_materials'=>array(
						'show_tab'=> 0
					)
				);
				break;
			case 'table':
			case 'document':
			case 'script':
				$display_layout='survey_info/layout_scripts';
				$page_tabs=array(
					'description'=>array(
						'label'=>t($dataset_type.'_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					//hide related materials
					'related_materials'=>array(
						'show_tab'=> (int)$related_resources_count,
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
					),
					'get_microdata'=>array(
						'label'=>t('get_microdata'),
						'url'=>site_url("catalog/$sid/get-microdata"),
						'show_tab'=> ($microdata_resources_count >0) ? 1 : 0
					),
				);
				break;
			//case 'geospatial':
			case 'visualization':
				$page_tabs=array(
					'description'=>array(
						'label'=>t($dataset_type.'_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					/*'related_materials'=>array(
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
						'show_tab'=>(int)$related_resources_count
					),*/
					//hide related materials
					'related_materials'=>array(
						'show_tab'=> 0
					),
					'related_citations'=>array(
						'label'=>t('related_citations'),
						'url'=>site_url("catalog/$sid/related-publications"),
						'show_tab'=>(int)$related_citations_count
					),
					'related_datasets'=>array(
						'label'=>t('related_datasets'),
						'url'=>site_url("catalog/$sid/related-datasets"),
						'show_tab'=>count($related_studies)
					)
				);
			break;

			default:
				show_error('DATASET-TYPE-NOT-SUPPORTED: '. $dataset_type);
		}

		$options=array(
			'published'=>$published,
			'sid'=>$sid,
			'dataset_type'=>$dataset['type'],
			'survey'=>$this->get_survey_info($sid),
			'page_tabs'=>$page_tabs,
			'active_tab'=>$active_tab,
			'data_access_type'=>$data_access_type,
			'data_classification'=> $dataset['data_class_code'],
			'body'=>$content,
			'has_related_materials'=>$related_resources_count,
            'has_citations'=>$related_citations_count,
            'has_data_dictionary'=>$has_datafiles,
			'survey_title'=>$dataset['title'],
			'related_studies_count'=>count($related_studies),
			'related_studies_formatted'=>$related_studies_formatted
		);

		//reproduciblity package?
		if ($dataset['type']=='script'){
			$this->load->library("Script_helper");
			$options['reproducibility_package']=$this->script_helper->get_reproducibility_package_resource($sid);
		}

		$this->template->write('title', $this->generate_survey_title($dataset),true);
		$this->template->add_variable("body_class","container-fluid");
		$html= $this->load->view($display_layout,$options,true); 
		$this->template->write('survey_title', "survey title",true);
		$this->template->write('content', $html,true);
		$this->template->render();
	}




	/**
	*
	* Get study metadata and other info
	**/
	private function get_survey_info($id)
	{
		$survey=$this->Dataset_model->get_row_detailed($id);

		if ($survey===FALSE || count($survey)==0){
			show_error('STUDY_NOT_FOUND');
		}

		$this->load->model("Repository_model");
		$this->load->model("Survey_resource_model");
		$survey['repositories']=$this->Catalog_model->get_survey_repositories($id);
		$survey['owner_repo']=$this->Repository_model->get_survey_owner_repository($id);
		$survey['has_resources']=$this->Survey_resource_model->has_external_resources($id);
		$survey['storage_path']=$this->Dataset_model->get_storage_fullpath($id);

		if (!$survey['owner_repo']){
			$survey['owner_repo']=$this->Repository_model->get_central_catalog_array();
		}

		if($survey['type']=='timeseries'){
			$survey['timeseries_db']=$this->Timeseries_db_model->get_database_by_series_id($id);

			if (!empty($survey['timeseries_db'])){
				$survey['timeseries_db_title']=null;
				if (isset($survey['timeseries_db']['metadata']['database_description']['title_statement']['title'])){
					$survey['timeseries_db_title']=$survey['timeseries_db']['metadata']['database_description']['title_statement']['title'];
				}
			}

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
			'study_keywords'=>$this->input->get_post('vk'),
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
	* Download microdata and other documentation
	**/
	function download($survey_id,$resource_id)
	{
		if (!is_numeric($resource_id) || !is_numeric($survey_id)){
			show_404();
		}

		$this->load->model('Survey_resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('Public_model');
		$this->load->model('Form_model');

		try{
			$this->Survey_resource_model->download($this->user,$survey_id,$resource_id);
		}
		catch(Exception $e){
			show_error($e->getMessage());
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
				$result['resources_microdata']=$this->Survey_resource_model->get_microdata_resources($id);
				$data['tab_content']= $this->load->view('ddibrowser/study_review_microdata', $result,TRUE);

				$survey['resources']=$this->Survey_resource_model->get_grouped_resources_by_survey($id);
				$data['tab_content'].= $this->load->view('ddibrowser/study_review_resources',$survey,TRUE);
			break;

			case 'download':
				$resource_id=$this->uri->segment(5);
				$resource_path= $this->Survey_resource_model->get_resource_download_path($resource_id);

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


	/**
	 * 
	 * 
	 * Licensed data request
	 * 
	 */
	public function request_access($sid)
	{		
		$this->load->model("Form_model");
		
		$user=$this->ion_auth->current_user();

		if(!$user){
			show_error('LOGIN_TO_CONTINUE');
		}

		$form_obj=$this->Form_model->get_form_by_survey($sid);
		
		if(empty($form_obj)){
			$data_access_type='data_na';
		}else{
			$data_access_type=$form_obj['model'];
		}

		if($data_access_type=='data_enclave'){
			$data_access_type='enclave';
		}

		if ($data_access_type!=='licensed'){
			show_404();
		}

		//need this to show a new form
		$_GET['request']='new';

		$this->load->driver('data_access',array('adapter'=>$data_access_type));

		if ($this->data_access->is_supported($data_access_type)){
			$content=$this->data_access->process_form($sid,$user);

			if($content==''){
				$content='NOT_DATA_AVAILABLE';
			}
		}
		else{
			$content="Data Access Not Available";
		}

		//echo $content;
		$this->template->add_variable("body_class","container");
		$this->template->write('title', '',true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
		return;
		$this->render_page($sid, $content,'get_microdata');
	}

	function export_citation($sid=null,$format='ris')
	{
		$this->load->library("Datacite_citation");
		return $this->datacite_citation->export($sid,$format);
	}


}
/* End of file study.php */
/* Location: ./controllers/study.php */
