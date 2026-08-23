<?php
class Study extends MY_Controller {

	private $user=FALSE;

	private $display_template_types=array(
		'script',
		'survey',
		'timeseries',
		'timeseriesdb',
		'timeseries-db',
		'geospatial',
		'document',
		'table',
		'image',
		'video',
	);

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->model("Dataset_model");
		$this->lang->load('general');
		$this->lang->load("catalog_search");

		if ($this->ion_auth->logged_in()){
			$this->user=$this->ion_auth->current_user();
		}
	}

	//study metadata
	function metadata($sid=NULL)
	{
		$this->load->library("Dataset_manager");
		$this->load->model("Widget_model");
		$this->load->model("Survey_resource_model");

		$survey=$this->Dataset_model->get_row($sid);

		if (!$survey){
			show_404();
		}

		$survey=$this->attach_survey_catalog_context($survey);
		$survey['metadata']=(array)$this->dataset_manager->get_metadata($sid,$survey['type']);
		$survey['metadata']['iframe_embeds']=$this->Widget_model->widgets_by_study($sid);
		$survey['schema_org_description']=$this->Dataset_model->get_schema_org_description(
			$survey['metadata'],
			$survey['type']
		);
		$survey['schema_org_json_ld']=$this->Dataset_model->build_schema_org_json_ld($survey);

		$uses_display_template = $this->uses_display_template($survey['type']);
		if (!$uses_display_template) {
			$this->template->add_js('javascript/linkify.min.js');
			$this->template->add_js('javascript/linkify-jquery.min.js');
		}
		if (!empty($survey['metadata']['iframe_embeds'])) {
			$this->template->add_js('javascript/pym.v1.min.js');
		}

		if (!empty($survey['schema_org_json_ld'])) {
			$json_ld=$this->load->view('survey_info/dataset_json_ld',$survey,true);
			$this->template->add_js($json_ld,'inline');
		}

		$resources=$this->Survey_resource_model->get_survey_resources($sid);
		if ($uses_display_template) {
			$survey['resources']=$resources;
		}
		else{
			$survey['resources']=$this->resources_grouped_by_filename($resources);
		}

		if ($survey['type'] === 'timeseries') {
			$ts_metadata=$survey['metadata'];
			$survey['resolved_databases']=$this->resolve_timeseries_database_links($ts_metadata);
			$survey['metadata']=$ts_metadata;
		}

		if ($uses_display_template){
			$output=$this->render_metadata_html($survey);
		}
		elseif (in_array($survey['type'], $this->display_template_types, true)){
			$output=$this->render_legacy_metadata_html($survey);
		}
		else{
			show_error('DATASET-TYPE-NOT-SUPPORTED: '.$survey['type']);
		}

		$this->render_page($sid, $output, $active_tab='description', $survey);
	}

	/**
	 * Render HTML page with project metadata
	 *
	 * @param array $project
	 * @return string
	 */
	private function render_metadata_html($project)
	{
		$this->load->library("Display_template");
		try{
			$template=$this->display_template->get_template_project_type($project['type']);

			if (isset($template['template'])){
				$template=$template['template'];
			}

			if (!isset($project['resources']) || !is_array($project['resources'])) {
				$project['resources']=$this->Survey_resource_model->get_survey_resources($project['id']);
			}
			$this->display_template->initialize($project,$template);

			$page_options=array(
                'html'=>$this->display_template->render_html(),
                'sidebar'=>$this->display_template->get_sidebar_items(),
                'display_template_info'=>$this->display_template->get_template_resolution(),
            );

            return $this->load->view('display_templates/index',$page_options,true);
		}
		catch(Exception $e){
			log_message('error', 'Display template render failed: '.$e->getMessage());
			show_error($e->getMessage());
		}
	}

	/**
	 * Catalog study-description uses JSON display templates unless this type
	 * is listed in site setting legacy_study_templates.
	 *
	 * @param string $type
	 * @return bool
	 */
	private function uses_display_template($type)
	{
		if (!in_array($type, $this->display_template_types, true)) {
			return false;
		}
		$this->load->helper('display_template');
		return !display_template_uses_legacy_study_template($type);
	}

	/**
	 * Older PHP views under application/views/metadata_templates/.
	 *
	 * @param array<string, mixed> $survey
	 * @return string
	 */
	private function render_legacy_metadata_html($survey)
	{
		$this->load->library('Metadata_template');
		$this->metadata_template->initialize($survey['type'], $survey);
		$output = $this->metadata_template->render_html();
		return $this->load->view('survey_info/metadata', array('content' => $output), true);
	}


	public function data_dictionary($sid)
	{
		$survey=$this->get_survey_info($sid);
		$this->load->model("Variable_group_model");
		$this->load->model("Data_file_model");
		$views=$this->data_dictionary_views($sid);
		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$options['variable_groups_html']=$this->Variable_group_model->get_vgroup_tree_html($sid);
        $options['sid']=$sid;
		$this->apply_geospatial_catalogue_context($sid, $options, $views['type']);
		$options['content']=$this->load->view($views['index'],$options,TRUE);
		$content=$this->load->view($views['layout'],$options,TRUE);
		$this->render_page($sid, $content,'data_dictionary', $survey);
	}


	public function variable_groups($sid,$vgid=null)
	{
		$survey=$this->get_survey_info($sid);
		$this->load->model("Variable_group_model");
		$this->load->model("Data_file_model");
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
		$this->render_page($sid, $content,'data_dictionary', $survey);
	}



    //show info for a single data file
	public function data_file($sid, $file_id,$var_id=null)
    {
		if ($var_id){
			return $this->variable($sid,$file_id,$var_id);
		}

		$survey=$this->get_survey_info($sid);
		$offset=(int)$this->input->get("offset");
		$limit=300;

		$this->lang->load('ddi_fields');
		$this->load->model("Variable_group_model");
		$this->load->model("Data_file_model");
		$this->load->model("Variable_model");
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

		$views=$this->data_dictionary_views($sid);
		if ($views['type']==='geospatial'){
			$options['files']=$options['file_list'];
			$this->apply_geospatial_catalogue_context($sid, $options, $views['type']);
			$options['content']=$this->load->view($views['file'],$options,TRUE);
			$content=$this->load->view($views['layout'],$options,TRUE);
		}
		else{
			$content=$this->load->view($views['file'],$options,TRUE);
		}
        $this->render_page($sid, $content,'data_dictionary', $survey);
    }



	public function variable($sid,$file_id=null, $var_id=null)
    {
		if (!$file_id && !$var_id){
			show_error("NO_FILE_OR_VARIABLE_SET");
		}

		$survey=$this->get_survey_info($sid);

		$this->lang->load('ddi_fields');
		$this->lang->load('fields_timeseries');
		$this->load->helper("metadata_view");
		$this->load->model("Data_file_model");
		$this->load->model("Variable_model");

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

		$views=$this->data_dictionary_views($sid);

        $options['sid']=$sid;
        $options['file_id']=$file_id;
        $options['var_id']=$var_id;
		$options['file']=$file_info;		
		$options['variable']=$this->Variable_model->get_by_var_id($sid, $file_id, $var_id);

		if (!$options['variable']){
			show_404();
		}

		if($this->input->is_ajax_request()){
			$content=$this->load->view($views['variable'],$options,TRUE);
			return $this->render_page($sid, $content,'data_dictionary', $survey);
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$this->apply_geospatial_catalogue_context($sid, $options, $views['type']);
		$options['content']=$this->load->view($views['variable'],$options,TRUE);
		$content=$this->load->view($views['layout'],$options,TRUE);
        $this->render_page($sid, $content,'data_dictionary', $survey);
    }


	/**
	 * View set for the data-dictionary / feature-catalogue tab.
	 */
	private function data_dictionary_views($sid)
	{
		$this->load->helper('metadata_view');
		$type=$this->Dataset_model->get_type($sid);
		$variable='survey_info/'.catalog_variable_detail_view($type);
		if ($type==='geospatial'){
			return array(
				'type'=>'geospatial',
				'index'=>'survey_info/geospatial_feature_types',
				'file'=>'survey_info/geospatial_feature_type',
				'layout'=>'survey_info/geospatial_catalogue_layout',
				'variable'=>$variable,
			);
		}

		return array(
			'type'=>$type,
			'index'=>'survey_info/data_files',
			'file'=>'survey_info/variables_by_file',
			'layout'=>'survey_info/data_dictionary_layout',
			'variable'=>$variable,
		);
	}


	/**
	 * Catalogue header and attribute counts for geospatial tab views.
	 */
	private function apply_geospatial_catalogue_context($sid, &$options, $type=null)
	{
		if ($type===null){
			$type=$this->Dataset_model->get_type($sid);
		}
		if ($type!=='geospatial'){
			return;
		}

		$this->load->library("Dataset_manager");
		$this->load->model("Variable_model");
		$metadata=$this->dataset_manager->get_metadata($sid,'geospatial');
		$catalog=array();
		if (is_array($metadata) && isset($metadata['description']['feature_catalogue']) && is_array($metadata['description']['feature_catalogue'])){
			$catalog=$metadata['description']['feature_catalogue'];
			unset($catalog['featureType']);
		}
		$options['feature_catalogue']=$catalog;

		if (isset($options['files']) && is_array($options['files'])){
			foreach($options['files'] as $key=>$file){
				$fid=isset($file['file_id']) ? $file['file_id'] : '';
				if ($fid==='' || !empty($file['var_count'])){
					continue;
				}
				$options['files'][$key]['var_count']=$this->Variable_model->get_file_variables_count($sid,$fid);
			}
		}
	}


	public function downloads($sid=NULL)
	{
		return $this->related_materials($sid);
	}

	public function related_materials($sid=NULL)
	{
		$survey=$this->get_survey_info($sid);
        $this->load->helper("resource_helper");
        $this->load->model('Survey_resource_model');
		$this->load->model("Catalog_model");
		$this->load->model("Form_model");
		$this->load->model("Licensed_model");
		$this->lang->load('resource_manager');
		
		$user=$this->ion_auth->current_user();
		$options['user_id']=isset($user->id) ? $user->id : false;
        $options['resources']=$this->Survey_resource_model->get_grouped_resources_by_survey($sid);
        $options['group_labels'] = $this->Survey_resource_model->get_group_labels(ci_lang_to_iso());
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
					log_message('error', 'Licensed microdata access check failed: '.$e->getMessage());
				}				
			}
		}

		$content=$this->load->view('survey_info/related_resources',$options,TRUE);
		$this->render_page($sid, $content,'related_materials', $survey);
	}



	public function related_publications($sid)
	{
		$survey=$this->get_survey_info($sid);
		$this->load->model('Citation_model');
		$this->load->model('Repository_model');
		$this->load->library('chicago_citation');
		$this->lang->load("resource_manager");
		$this->load->helper("resource_helper");
				
		$options['citations']=$this->Citation_model->get_citations_by_survey($sid,$this->input->get('sort_by'),$this->input->get('sort_order'));
		$content=$this->load->view('catalog_search/survey_summary_citations',$options,TRUE);
        $options['sid']=$sid;
		$this->render_page($sid, $content,'related_citations', $survey);
	}


	public function related_datasets($sid)
	{
		$survey=$this->get_survey_info($sid);
		$this->load->model('Related_study_model');
		$related_studies=$this->Related_study_model->get_related_studies_list($sid);
		$related_studies_formatted=$this->load->view('survey_info/related_studies',array('related_studies'=>$related_studies),true);
		$this->render_page($sid, $related_studies_formatted,'related_datasets', $survey);
	}

	public function data_api($sid)
	{
		$survey=$this->get_survey_info($sid);
		$this->load->model("Survey_data_api_model");
		$api_dataset=$this->Survey_data_api_model->get_by_sid($sid);
		
		if (!$api_dataset){
			show_404();
		}

		$options=array(
            'db_id'=>$api_dataset[0]['db_id'],
            'table_id'=>$api_dataset[0]['table_id'],
			'idno'=>$survey['idno'],
			'sid'=>$sid
        );

        $content=$this->load->view('data_api/preview', $options,true); 
		$this->render_page($sid, $content,'data_api', $survey);
	}
	
	public function get_microdata($sid)
	{
		$survey=$this->get_survey_info($sid);
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

		$this->render_page($sid, $content,'get_microdata', $survey);
	}


	public function related_series($sid)
	{
		$survey=$this->get_survey_info($sid);
		if (!in_array($survey['type'], array('timeseriesdb', 'timeseries-db'))) {
			show_404();
		}

		$this->load->model("Timeseries_db_model");

		$page   = max(1, (int)$this->input->get('page'));
		$view   = $this->input->get('view') === 'list' ? 'list' : 'card';
		$limit  = 20;
		$offset = ($page - 1) * $limit;

		$series = $this->Timeseries_db_model->get_series_by_db_idno($survey['idno'], $limit, $offset);
		$total  = $this->Timeseries_db_model->count_series_by_db_idno($survey['idno']);

		$output = $this->load->view('survey_info/timeseries_db_series', array(
			'series'      => $series,
			'total'       => $total,
			'page'        => $page,
			'limit'       => $limit,
			'view'        => $view,
			'db_title'    => $survey['title'],
			'db_idno'     => $survey['idno'],
		), true);

		$this->render_page($sid, $output, 'related_series', $survey);
	}


	/**
	 * Legacy catalog URL catalog/{sid}/indicator-data — redirects to indicator-chart / indicator-data-api / indicator-structure.
	 */
	public function indicator_data($sid)
	{
		$this->load->model("Timeseries_dsd_model");
		$survey = $this->get_survey_info($sid);
		if ($survey['type'] !== 'timeseries') {
			show_404();
		}
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			$content = '<div class="container py-4"><p class="text-muted">' . htmlspecialchars(t('indicator_data_no_dsd')) . '</p></div>';
			$this->render_page($sid, $content, 'indicator_chart', $survey);
			return;
		}
		$tab = strtolower(trim((string) $this->input->get('tab')));
		$target = 'indicator-chart';
		if (in_array($tab, array('observations', 'data', 'obs'), true)) {
			$target = 'indicator-data-api';
		} elseif (in_array($tab, array('structure', 'dsd', 'ds'), true)) {
			$target = 'indicator-structure';
		} elseif (in_array($tab, array('table', 'pivot'), true)) {
			$target = 'indicator-table';
		}
		$params = array();
		$qs = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
		if ($qs !== '') {
			parse_str($qs, $params);
		}
		unset($params['tab']);
		$query = count($params) > 0 ? '?' . http_build_query($params) : '';
		redirect(site_url('catalog/' . (int) $sid . '/' . $target) . $query);
	}

	public function indicator_chart($sid)
	{
		$this->render_indicator_data_public($sid, 'indicator_chart', 'chart');
	}

	public function indicator_table($sid)
	{
		$this->render_indicator_data_public($sid, 'indicator_table', 'table');
	}

	/**
	 * Pivot-table HTML/XLSX export for the public indicator table tab.
	 *
	 * GET catalog/{sid}/indicator-table-export
	 *   ?format=html|xlsx
	 *   &table_rows=DIM1,DIM2   (comma-separated dim keys, URI-encoded)
	 *   &table_cols=DIM3
	 *   &table_time_order=asc|desc
	 *   &geo=CODE1,CODE2        (geography filter)
	 *   &period=CODE            (periodicity filter)
	 *   &c[COMPONENT]=CODES     (dimension filters)
	 *   &from=YEAR  &to=YEAR    (time range)
	 */
	public function indicator_table_export($sid)
	{
		$this->load->model('Timeseries_dsd_model');

		$survey = $this->Dataset_model->get_row((int) $sid);
		if (!$survey || $survey['type'] !== 'timeseries') {
			show_404();
		}

		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			show_404();
		}

		if ((int) $this->Dataset_model->get_indicator_ts_sync_required_for_sid((int) $sid) === 1) {
			show_404();
		}

		$this->load->library('Indicator_table_export');
		$this->indicator_table_export->export_and_stream(
			(int) $sid,
			$ctx,
			(array) $this->input->get(),
			isset($survey['idno']) ? $survey['idno'] : (string) $sid,
			isset($survey['title']) ? $survey['title'] : ''
		);
	}

	/**
	 * Permanent redirect from deprecated slug indicator-observations.
	 */
	public function redirect_indicator_observations($sid)
	{
		$qs = isset($_SERVER['QUERY_STRING']) && (string) $_SERVER['QUERY_STRING'] !== ''
			? '?' . (string) $_SERVER['QUERY_STRING']
			: '';
		redirect(site_url('catalog/' . (int) $sid . '/indicator-data-api') . $qs, 'location', 301);
	}

	public function indicator_observations($sid)
	{
		$this->render_indicator_data_public($sid, 'indicator_observations', 'observations');
	}

	public function indicator_structure($sid)
	{
		$this->render_indicator_data_public($sid, 'indicator_structure', 'structure');
	}

	/**
	 * Public catalog: indicator observations (Mongo) with chart + table via public timeseries API.
	 *
	 * @param string $active_tab Layout nav key (indicator_chart | indicator_table | indicator_observations | indicator_structure)
	 * @param string $main_view  Vue initial pane (chart | table | observations | structure)
	 */
	private function render_indicator_data_public($sid, $active_tab, $main_view)
	{
		$this->load->model("Timeseries_dsd_model");
		$survey = $this->get_survey_info($sid);
		if ($survey['type'] !== 'timeseries') {
			show_404();
		}
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			$content = '<div class="container py-4"><p class="text-muted">' . htmlspecialchars(t('indicator_data_no_dsd')) . '</p></div>';
			$this->render_page($sid, $content, $active_tab, $survey);
			return;
		}
		$ts_sync_pending = (int) $this->Dataset_model->get_indicator_ts_sync_required_for_sid((int) $sid) === 1;
		if ($ts_sync_pending && in_array($main_view, array('chart', 'table', 'observations'), true)) {
			redirect(site_url('catalog/' . (int) $sid . '/indicator-structure'), 'location', 302);
			return;
		}
		$this->load->helper('vite_helper');
		$title_key = 'tab_indicator_chart';
		if ($main_view === 'table') {
			$title_key = 'tab_indicator_table';
		} elseif ($main_view === 'observations') {
			$title_key = 'tab_indicator_observations';
		} elseif ($main_view === 'structure') {
			$title_key = 'tab_indicator_structure';
		}
		$study_abstract = isset($survey['abstract']) ? strip_tags((string) $survey['abstract']) : '';
		if (strlen($study_abstract) > 4000) {
			$study_abstract = substr($study_abstract, 0, 4000) . '…';
		}
		$content = $this->load->view('catalog/study_indicator_data_public', array(
			'survey_id' => (int) $sid,
			'idno'      => isset($survey['idno']) ? (string) $survey['idno'] : '',
			'indicator_main_view' => $main_view,
			'catalog_page_title' => function_exists('t') ? t($title_key) : $title_key,
			'study_title'   => isset($survey['title']) ? (string) $survey['title'] : '',
			'study_abstract'=> $study_abstract,
			'indicator_data_api_ui' => $this->indicator_data_api_ui_strings(),
		), true);
		$this->render_page($sid, $content, $active_tab, $survey);
	}

	/**
	 * Translated strings for the public indicator Data API Vue tab (aligned with data_api/preview.php).
	 *
	 * @return array<string, string>
	 */
	private function indicator_data_api_ui_strings()
	{
		$t = function ($key) {
			return function_exists('t') ? t($key) : $key;
		};
		return array(
			'datasetApiHeading'     => $t('indicator_data_api_dataset_api_heading'),
			'datasetLabel'          => $t('indicator_data_api_dataset_label'),
			'observationsLabel'     => $t('indicator_data_api_observations'),
			'apiUsageHeading'       => $t('indicator_data_api_api_usage'),
			'metadataLabel'         => $t('indicator_data_api_metadata'),
			'dataLabel'             => $t('indicator_data_api_data'),
			'bulkDownloadsHeading'  => $t('indicator_bulk_downloads_heading'),
			'queryParamsHeading'    => $t('indicator_data_api_query_parameters'),
			'parameterCol'          => $t('indicator_data_api_parameter'),
			'descriptionCol'      => $t('indicator_data_api_description'),
			'examplesHeading'       => $t('indicator_data_api_examples'),
			'exampleFirst'          => $t('indicator_data_api_example_first'),
			'exampleOffset'         => $t('indicator_data_api_example_offset'),
			'paramLimit'            => $t('indicator_data_api_param_limit'),
			'paramLimitDesc'        => $t('indicator_data_api_param_limit_desc'),
			'paramOffset'           => $t('indicator_data_api_param_offset'),
			'paramOffsetDesc'       => $t('indicator_data_api_param_offset_desc'),
			'paramSort'             => $t('indicator_data_api_param_sort'),
			'paramSortDesc'         => $t('indicator_data_api_param_sort_desc'),
			'paramFrom'             => $t('indicator_data_api_param_fromto'),
			'paramFromDesc'         => $t('indicator_data_api_param_fromto_desc'),
			'paramDc'               => $t('indicator_data_api_param_dc'),
			'paramDcDesc'           => $t('indicator_data_api_param_dc_desc'),
			'dataExplorerHeading'   => $t('indicator_data_api_data_explorer'),
			'apiOptionsLabel'       => $t('indicator_data_api_api_options'),
			'totalLabel'            => $t('indicator_data_api_total'),
			'showingTemplate'       => $t('indicator_data_api_showing'),
			'bulkFileCol'           => $t('indicator_data_api_bulk_file'),
			'bulkDateCol'           => $t('indicator_data_api_bulk_date'),
			'bulkActionsCol'        => $t('indicator_data_api_bulk_actions'),
			'download'              => $t('indicator_data_api_download'),
			'link'                  => $t('indicator_data_api_link'),
			'loading'               => $t('indicator_data_api_loading'),
			'previous'              => $t('indicator_data_api_previous'),
			'next'                  => $t('indicator_data_api_next'),
			'copyUrl'               => $t('indicator_data_api_copy_url'),
			'openUrl'               => $t('indicator_data_api_open_url'),
			'copied'                => $t('indicator_data_api_copied'),
			'copyFailed'            => $t('indicator_data_api_copy_failed'),
			'filtersHeading'        => $t('indicator_data_api_grid_filters'),
			'explorerQueryHeading'  => $t('indicator_data_api_explorer_query_heading'),
			'explorerQueryHint'     => $t('indicator_data_api_explorer_query_hint'),
			'applyFilters'          => $t('indicator_data_api_apply_filters'),
			'noFacetFilters'        => $t('indicator_data_api_no_facet_filters'),
			'filtersIncomplete'     => $t('indicator_data_api_filters_incomplete'),
			'reportingYearFromLabel' => $t('indicator_data_api_reporting_year_from_label'),
			'reportingYearToLabel'  => $t('indicator_data_api_reporting_year_to_label'),
			'reportingYearBoundsHint' => $t('indicator_data_api_reporting_year_bounds_hint'),
			'timePeriodNoFacets'    => $t('indicator_data_api_time_period_no_facets'),
		);
	}
	
	private function render_page($sid, $content, $active_tab='description', $survey_info=null)
	{
        if($this->input->get("print")){
            $this->template->set_template('blank');
        }

        if($this->input->get("ajax")){
            echo $content;return;
        }
        
        // Add study ID to page for client-side analytics tracking
		$this->template->add_js(
            '<script>var STUDY_ID = ' . json_encode($sid) . ';</script>',
            'inline'
        );

		$this->lang->load('ddibrowser');

		if ($survey_info === null){
			$survey_info=$this->get_survey_info($sid);
		}
		elseif (!isset($survey_info['owner_repo'])){
			$survey_info=$this->attach_survey_catalog_context($survey_info);
		}

		$dataset=$survey_info;
		$study_abstract = isset($dataset['abstract'])
			? trim(strip_tags((string) $dataset['abstract']))
			: '';
		$this->add_study_description_meta($study_abstract !== '' ? $study_abstract : null);
		$dataset_type=$dataset['type'];
        $data_access_type=$dataset['data_access_type'];
		$published=$dataset['published'];

		$nav=$this->study_page_tabs($sid, $dataset);
		$page_tabs=$nav['page_tabs'];
		$display_layout=$nav['display_layout'];
		$related_studies_count=$nav['related_studies_count'];

		if ($dataset_type === 'timeseries' && !isset($survey_info['resolved_databases'])) {
			$ts_metadata=isset($survey_info['metadata']) && is_array($survey_info['metadata'])
				? $survey_info['metadata']
				: array();
			$survey_info['resolved_databases']=$this->resolve_timeseries_database_links($ts_metadata);
			$survey_info['metadata']=$ts_metadata;
		}

		$options=array(
			'published'=>$published,
			'sid'=>$sid,
			'dataset_type'=>$dataset['type'],
			'survey'=>$survey_info,
			'page_tabs'=>$page_tabs,
			'active_tab'=>$active_tab,
			'data_access_type'=>$data_access_type,
			'data_classification'=> $dataset['data_class_code'],
			'body'=>$content,
			'survey_title'=>$dataset['title'],
			'related_studies_count'=>$related_studies_count
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
	 * Tab set and layout view for a catalog study page.
	 *
	 * @param int   $sid
	 * @param array $dataset Study row
	 * @return array{page_tabs: array, display_layout: string, related_studies_count: int}
	 */
	private function study_page_tabs($sid, $dataset)
	{
		$type=isset($dataset['type']) ? $dataset['type'] : '';
		$needed=$this->study_page_tab_needs($type);
		$counts=$this->study_page_tab_counts($sid, $dataset, $needed);

		$tabs=array(
			'description'=>$this->study_page_tab($sid, 'study-description', $this->study_page_description_label($type), 1),
		);

		switch($type){
			case 'geospatial':
				$tabs['data_dictionary']=$this->study_page_tab($sid, 'data-dictionary', t('feature_catalogue'), $counts['datafiles']);
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				$tabs['get_microdata']=$this->study_page_tab($sid, 'get-microdata', t('get_microdata'), $counts['microdata']);
				$tabs['related_citations']=$this->study_page_tab($sid, 'related-publications', t('related_citations'), $counts['citations']);
				$tabs['related_datasets']=$this->study_page_tab($sid, 'related-datasets', t('related_datasets'), $counts['related_studies']);
				$tabs['data_api']=$this->study_page_tab($sid, 'data-api', t('tab_data_api'), $counts['data_api']);
				break;
			case 'survey':
			case 'microdata':
				$tabs['data_dictionary']=$this->study_page_tab($sid, 'data-dictionary', t('data_dictionary'), $counts['datafiles']);
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				$tabs['get_microdata']=$this->study_page_tab($sid, 'get-microdata', t('get_microdata'), 1);
				$tabs['related_citations']=$this->study_page_tab($sid, 'related-publications', t('related_citations'), $counts['citations']);
				$tabs['related_datasets']=$this->study_page_tab($sid, 'related-datasets', t('related_datasets'), $counts['related_studies']);
				$tabs['data_api']=$this->study_page_tab($sid, 'data-api', t('tab_data_api'), $counts['data_api']);
				break;
			case 'video':
			case 'image':
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				break;
			case 'timeseries':
				$show_indicator=$counts['indicator_mongo'] && !$counts['indicator_sync_pending'];
				$tabs['indicator_chart']=$this->study_page_tab($sid, 'indicator-chart', t('tab_indicator_chart'), $show_indicator);
				$tabs['indicator_table']=$this->study_page_tab($sid, 'indicator-table', t('tab_indicator_table'), $show_indicator);
				$tabs['indicator_observations']=$this->study_page_tab($sid, 'indicator-data-api', t('tab_indicator_observations'), $show_indicator);
				$tabs['indicator_structure']=$this->study_page_tab($sid, 'indicator-structure', t('tab_indicator_structure'), $counts['indicator_mongo']);
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				break;
			case 'timeseriesdb':
			case 'timeseries-db':
				$tabs['related_series']=$this->study_page_tab($sid, 'related-series', t('related_series'), $counts['related_series']);
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				break;
			case 'table':
			case 'document':
			case 'script':
				$tabs['related_materials']=$this->study_page_tab($sid, 'related-materials', t('related_materials'), $counts['resources']);
				$tabs['get_microdata']=$this->study_page_tab($sid, 'get-microdata', t('get_microdata'), $counts['microdata']);
				break;
			default:
				show_error('DATASET-TYPE-NOT-SUPPORTED: '.$type);
		}

		$layout=in_array($type, array('table', 'document', 'script'), true)
			? 'survey_info/layout_scripts'
			: 'survey_info/layout';

		return array(
			'page_tabs'=>$tabs,
			'display_layout'=>$layout,
			'related_studies_count'=>$counts['related_studies'],
		);
	}


	/**
	 * Count keys required to decide tab visibility for a catalog type.
	 *
	 * @param string $type
	 * @return array<int, string>
	 */
	private function study_page_tab_needs($type)
	{
		switch($type){
			case 'survey':
			case 'microdata':
				return array('datafiles', 'resources', 'citations', 'related_studies', 'data_api');
			case 'geospatial':
				return array('datafiles', 'resources', 'microdata', 'citations', 'related_studies', 'data_api');
			case 'video':
			case 'image':
				return array('resources');
			case 'timeseries':
				return array('resources', 'indicator');
			case 'timeseriesdb':
			case 'timeseries-db':
				return array('resources', 'related_series');
			case 'table':
			case 'document':
			case 'script':
				return array('resources', 'microdata');
			default:
				return array();
		}
	}


	/**
	 * @param int   $sid
	 * @param array $dataset
	 * @param array $needed
	 * @return array
	 */
	private function study_page_tab_counts($sid, $dataset, $needed)
	{
		$counts=array(
			'datafiles'=>0,
			'resources'=>0,
			'microdata'=>0,
			'citations'=>0,
			'related_studies'=>0,
			'data_api'=>0,
			'indicator_mongo'=>false,
			'indicator_sync_pending'=>false,
			'related_series'=>0,
		);
		$needed=array_fill_keys($needed, true);

		if (isset($needed['datafiles'])){
			$counts['datafiles']=(int)$this->Dataset_model->has_datafiles($sid);
		}
		if (isset($needed['resources'])){
			$this->load->model('Survey_resource_model');
			$counts['resources']=(int)$this->Survey_resource_model->get_resources_count_by_survey($sid);
		}
		if (isset($needed['microdata'])){
			$this->load->model('Survey_resource_model');
			$counts['microdata']=(int)$this->Survey_resource_model->get_microdata_resources_count_by_survey($sid);
		}
		if (isset($needed['citations'])){
			$this->load->model('Citation_model');
			$counts['citations']=(int)$this->Citation_model->get_citations_count_by_survey($sid);
		}
		if (isset($needed['related_studies'])){
			$this->load->model('Related_study_model');
			$counts['related_studies']=count($this->Related_study_model->get_related_studies_id_list($sid));
		}
		if (isset($needed['data_api'])){
			$this->load->model('Survey_data_api_model');
			$counts['data_api']=$this->Survey_data_api_model->get_by_sid($sid) ? 1 : 0;
		}
		if (isset($needed['indicator'])){
			$this->load->model('Timeseries_dsd_model');
			$counts['indicator_mongo']=$this->Timeseries_dsd_model->resolve_dsd_for_sid((int)$sid) !== null;
			$counts['indicator_sync_pending']=(int)$this->Dataset_model->get_indicator_ts_sync_required_for_sid((int)$sid) === 1;
		}
		if (isset($needed['related_series'])){
			$this->load->model('Timeseries_db_model');
			$idno=isset($dataset['idno']) ? $dataset['idno'] : '';
			$counts['related_series']=(int)$this->Timeseries_db_model->count_series_by_db_idno($idno);
		}

		return $counts;
	}


	/**
	 * @param int    $sid
	 * @param string $path
	 * @param string $label
	 * @param mixed  $show
	 * @return array
	 */
	private function study_page_tab($sid, $path, $label, $show)
	{
		return array(
			'label'=>$label,
			'url'=>site_url('catalog/'.$sid.'/'.$path),
			'show_tab'=>$show ? 1 : 0,
		);
	}


	/**
	 * @param string $type
	 * @return string
	 */
	private function study_page_description_label($type)
	{
		switch($type){
			case 'geospatial':
				return t('geospatial_description');
			case 'survey':
			case 'microdata':
				return t('microdata_description');
			case 'timeseriesdb':
			case 'timeseries-db':
				return t('timeseries_db');
			default:
				return t($type.'_description');
		}
	}


	/**
	*
	* Get study metadata and other info
	**/
	private function get_survey_info($id)
	{
		$survey=$this->Dataset_model->get_row_detailed($id);

		if ($survey===FALSE || !is_array($survey) || count($survey)==0){
			show_404();
		}

		return $this->attach_survey_catalog_context($survey);
	}


	/**
	 * Owner collection, sibling collections, storage path, and resource flag for study chrome.
	 *
	 * @param array $survey Study row (listing or detailed)
	 * @return array
	 */
	private function attach_survey_catalog_context($survey)
	{
		$id=$survey['id'];

		$this->load->model("Catalog_model");
		$this->load->model("Repository_model");
		$this->load->model("Survey_resource_model");
		$survey['repositories']=$this->Catalog_model->get_survey_repositories($id);
		$survey['owner_repo']=$this->Repository_model->get_survey_owner_repository($id);
		$survey['has_resources']=$this->Survey_resource_model->has_external_resources($id);
		$survey['storage_path']=$this->Dataset_model->get_storage_fullpath($id);

		if (!$survey['owner_repo']){
			$survey['owner_repo']=$this->Repository_model->get_central_catalog_array();
		}

		return $survey;
	}


	/**
	 * Index resource rows by filename for legacy PHP metadata templates.
	 *
	 * @param array $resources
	 * @return array
	 */
	private function resources_grouped_by_filename($resources)
	{
		$output=array();
		foreach ((array)$resources as $resource){
			if (isset($resource['filename'])){
				$output[$resource['filename']]=$resource;
			}
		}
		return $output;
	}


	/**
	 * Attach catalog_url to series_description.databases[] for display templates and the info panel.
	 *
	 * @param array $metadata Study metadata (mutated)
	 * @return array
	 */
	private function resolve_timeseries_database_links(array &$metadata)
	{
		$this->load->model('Dataset_timeseries_model');
		$this->load->model('Timeseries_db_model');
		$this->Dataset_timeseries_model->normalize_databases_metadata($metadata);

		$databases = isset($metadata['series_description']['databases'])
			? $metadata['series_description']['databases']
			: array();

		foreach ($databases as $i => $db) {
			$idno = isset($db['id']) ? trim((string)$db['id']) : '';
			$resolved = $idno !== '' ? $this->Timeseries_db_model->get_row_by_idno($idno) : null;
			$databases[$i]['catalog_url'] = $resolved
				? site_url('catalog/' . $resolved['id'] . '/study-description')
				: null;
		}

		if (!isset($metadata['series_description']) || !is_array($metadata['series_description'])) {
			$metadata['series_description']=array();
		}
		$metadata['series_description']['databases'] = $databases;

		return $databases;
	}


	/**
	*
	* Variable Search
	*
	**/
	public function search($id)
	{
		$survey=$this->get_survey_info($id);
		$this->load->helper('form');
		$this->load->model("Data_file_model");
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
			//show search result (v_quick_search returns found/total/rows like catalog_search API)
			$var_result = $this->catalog_search->v_quick_search($id, $limit = 50);
			$data['variables'] = isset($var_result['rows']) ? $var_result['rows'] : $var_result;
			$data['sid']=$id;
			$html.=$this->load->view('survey_info/search_variable_list',$data,TRUE);
		}

		//print html without header/footers
		if($this->input->is_ajax_request()){			
			echo $html;
			return;
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($id);
		$options['content']=$html;
		$views=$this->data_dictionary_views($id);
		$this->apply_geospatial_catalogue_context($id, $options, $views['type']);
		$content=$this->load->view($views['layout'],$options,TRUE);

		$this->render_page($id, $content,'data_dictionary', $survey);
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
	 * Stream a local PDF resource inline (for in-page preview).
	 */
	function pdf_stream($survey_id, $resource_id)
	{
		if (! is_numeric($resource_id) || ! is_numeric($survey_id)) {
			show_404();
		}

		$this->load->model('Survey_resource_model');

		try {
			$this->Survey_resource_model->stream_pdf_inline($this->user, $survey_id, $resource_id);
		}
		catch (Exception $e) {
			show_error($e->getMessage());
		}
	}


	private function generate_survey_title($surveyObj)
	{
		$title=array();
		$title[]=$surveyObj['nation'];
		$title[]=$surveyObj['title'];
		return implode(" - ", $title);
	}


	/**
	 * Set meta description from surveys.abstract when non-empty.
	 *
	 * @param string|null $abstract
	 */
	private function add_study_description_meta($abstract)
	{
		if ($abstract === null || $abstract === '') {
			return;
		}

		$this->template->add_meta(
			'description',
			htmlspecialchars($abstract, ENT_QUOTES, 'UTF-8'),
			'pair'
		);
	}


	function export_citation($sid=null,$format='ris')
	{
		$this->load->library("Datacite_citation");
		return $this->datacite_citation->export($sid,$format);
	}


}
/* End of file study.php */
/* Location: ./controllers/study.php */
