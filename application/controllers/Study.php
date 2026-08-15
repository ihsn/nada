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
		$this->load->model("Timeseries_dsd_model");
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
		$survey['schema_org_description']=$this->Dataset_model->get_schema_org_description(
			$survey['metadata'],
			$survey['type']
		);
		$survey['schema_org_json_ld']=$this->Dataset_model->build_schema_org_json_ld($survey);

		$uses_display_template = in_array($survey['type'], array('script', 'survey', 'timeseries', 'timeseriesdb', 'timeseries-db', 'geospatial'), true);
		if (!$uses_display_template) {
			$this->template->add_js('javascript/linkify.min.js');
			$this->template->add_js('javascript/linkify-jquery.min.js');
		}
		$this->template->add_js('javascript/pym.v1.min.js');

		if (!empty($survey['schema_org_json_ld'])) {
			$json_ld=$this->load->view('survey_info/dataset_json_ld',$survey,true);
			$this->template->add_js($json_ld,'inline');
		}

		$survey['resources']=$this->Survey_resource_model->get_survey_resources_group_by_filename($sid);

		// Pre-resolve databases[] catalog links for the display template renderer
		if ($survey['type'] === 'timeseries') {
			$databases = isset($survey['metadata']['series_description']['databases'])
				? $survey['metadata']['series_description']['databases']
				: array();
			foreach ($databases as $i => $db) {
				$idno = isset($db['id']) ? trim((string)$db['id']) : '';
				$resolved = $idno !== '' ? $this->Timeseries_db_model->get_row_by_idno($idno) : null;
				$databases[$i]['catalog_url'] = $resolved
					? site_url('catalog/' . $resolved['id'] . '/study-description')
					: null;
			}
			$survey['metadata']['series_description']['databases'] = $databases;
		}

		if (in_array($survey['type'], array('script','survey','timeseries','timeseriesdb','timeseries-db','geospatial'))){
			$output=$this->render_metadata_html($survey);
		}
		else{		
			$this->metadata_template->initialize($survey['type'],$survey);
			$output=$this->metadata_template->render_html();
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
			$project_type = $project['type'];
			if ($project_type === 'timeseries-db') {
				$project_type = 'timeseriesdb';
			}
			$template=$this->display_template->get_template_project_type($project_type);

			if (isset($template['template'])){
				$template=$template['template'];
			}

			//get external resources
			$project['resources']=$this->Survey_resource_model->get_survey_resources($project['id']);
			$this->display_template->initialize($project,$template);

			$page_options=array(
                'html'=>$this->display_template->render_html(),
                'sidebar'=>$this->display_template->get_sidebar_items(),
                'display_template_info'=>$this->display_template->get_template_resolution(),
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
		$views=$this->data_dictionary_views($sid);
		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$options['variable_groups_html']=$this->Variable_group_model->get_vgroup_tree_html($sid);
        $options['sid']=$sid;
		$this->apply_geospatial_catalogue_context($sid, $options, $views['type']);
		$options['content']=$this->load->view($views['index'],$options,TRUE);
		$content=$this->load->view($views['layout'],$options,TRUE);
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
			return $this->render_page($sid, $content,'data_dictionary');
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($sid);
		$this->apply_geospatial_catalogue_context($sid, $options, $views['type']);
		$options['content']=$this->load->view($views['variable'],$options,TRUE);
		$content=$this->load->view($views['layout'],$options,TRUE);
        $this->render_page($sid, $content,'data_dictionary');
    }


	/**
	 * View set for the data-dictionary / feature-catalogue tab.
	 */
	private function data_dictionary_views($sid)
	{
		$type=$this->Dataset_model->get_type($sid);
		if ($type==='geospatial'){
			return array(
				'type'=>'geospatial',
				'index'=>'survey_info/geospatial_feature_types',
				'file'=>'survey_info/geospatial_feature_type',
				'layout'=>'survey_info/geospatial_catalogue_layout',
				'variable'=>'survey_info/geospatial_features',
			);
		}

		$variable='survey_info/timeseries';
		if ($type==='survey'){
			$variable='survey_info/variable_ddi';
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
        $this->load->helper("resource_helper");
        $this->load->model('Survey_resource_model');
		$this->load->model("Form_model");
		$this->load->model("Licensed_model");		
		
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

		$study=$this->Dataset_model->get_row($sid);

		$options=array(
            'db_id'=>$api_dataset[0]['db_id'],
            'table_id'=>$api_dataset[0]['table_id'],
			'idno'=>$study['idno'],
			'sid'=>$sid
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
		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || $survey['type'] !== 'timeseries') {
			show_404();
		}

		$databases_meta = isset($survey['metadata']['series_description']['databases'])
			? $survey['metadata']['series_description']['databases']
			: array();

		$databases = array();
		foreach ((array)$databases_meta as $db) {
			$idno = isset($db['id']) ? trim((string)$db['id']) : '';
			$resolved = $idno !== '' ? $this->Timeseries_db_model->get_row_by_idno($idno) : null;
			$databases[] = array(
				'meta'     => $db,
				'resolved' => $resolved,
			);
		}

		$output = $this->load->view('survey_info/timeseries_databases', array('databases' => $databases), true);
		$this->render_page($sid, $output, 'timeseries_db');
	}


	public function related_series($sid)
	{
		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || !in_array($survey['type'], array('timeseriesdb', 'timeseries-db'))) {
			show_404();
		}

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

		$this->render_page($sid, $output, 'related_series');
	}


	/**
	 * Legacy catalog URL catalog/{sid}/indicator-data — redirects to indicator-chart / indicator-data-api / indicator-structure.
	 */
	public function indicator_data($sid)
	{
		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || $survey['type'] !== 'timeseries') {
			show_404();
		}
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			$content = '<div class="container py-4"><p class="text-muted">' . htmlspecialchars(t('indicator_data_no_dsd')) . '</p></div>';
			$this->render_page($sid, $content, 'indicator_chart');
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
		$this->load->model('Timeseries_mongo_model');
		$this->load->model('Codelist_item_model');

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

		// Translate browser URL filter keys → API filter format
		$query = (array) $this->input->get();
		if (!isset($query['d']) || !is_array($query['d'])) {
			$query['d'] = [];
		}
		if (!empty($query['geo'])) {
			$query['d']['geography'] = $query['geo'];
		}
		if (!empty($query['period'])) {
			$query['d']['periodicity'] = $query['period'];
		}

		$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
			(int) $sid,
			$ctx['components'],
			$query
		);
		$rows = $this->Timeseries_mongo_model->find_observations($ctx['dsd_id'], $filter, [
			'limit' => 10000,
			'skip'  => 0,
			'sort'  => ['_ts_year' => 1, '_ts_period_start' => 1],
		]);

		$obs = [];
		foreach ($rows as $doc) {
			$arr   = json_decode(json_encode($doc), true);
			if (!is_array($arr)) continue;
			$strip = $this->Timeseries_mongo_model->strip_public_observation_fields($arr);
			$obs[] = $this->Timeseries_mongo_model->append_public_observation_timeseries_fields($arr, $strip);
		}
		$built   = $this->Timeseries_mongo_model->build_catalog_chart_records($obs, $ctx['components']);
		$records = isset($built['records']) && is_array($built['records']) ? $built['records'] : [];

		// Parse layout params (dim names may be URI-encoded inside the comma-list)
		$decode_dims = function ($raw) {
			$raw = trim((string) $raw);
			if ($raw === '') return [];
			$parts = array_map('trim', explode(',', $raw));
			$parts = array_filter($parts, function ($p) { return $p !== ''; });
			return array_values(array_map('rawurldecode', $parts));
		};
		$row_dims   = $decode_dims($this->input->get('table_rows'));
		$col_dims   = $decode_dims($this->input->get('table_cols'));
		$time_order = $this->input->get('table_time_order') === 'desc' ? 'desc' : 'asc';

		$tp_comp = $this->Timeseries_mongo_model->get_component_name_for_column_type($ctx['components'], 'time_period');
		if ($tp_comp === null) $tp_comp = '';

		$label_map = [];
		$sort_map  = [];
		foreach ($ctx['components'] as $comp) {
			if (!is_array($comp) || empty($comp['name'])) continue;
			$comp_name   = (string) $comp['name'];
			$codelist_id = isset($comp['codelist_id']) ? (int) $comp['codelist_id'] : 0;
			if ($codelist_id <= 0) continue;
			$items = $this->Codelist_item_model->get_items_by_codelist($codelist_id, false);
			$map   = [];
			$order = [];
			foreach ($items as $idx => $item) {
				$code  = isset($item['code'])  ? (string) $item['code']  : '';
				$title = isset($item['title']) ? (string) $item['title'] : $code;
				if ($code !== '') {
					$map[$code]   = $title !== '' ? $title : $code;
					$order[$code] = $idx;
				}
			}
			$col_type = isset($comp['column_type']) ? (string) $comp['column_type'] : '';
			if ($col_type === 'time_period' || ($tp_comp !== '' && $comp_name === $tp_comp)) {
				$label_map[\Indicator_table_export::TIME_PERIOD_SENTINEL] = $map;
				$sort_map[\Indicator_table_export::TIME_PERIOD_SENTINEL]  = $order;
			} else {
				$label_map[$comp_name] = $map;
				$sort_map[$comp_name]  = $order;
			}
		}

		$dataset_title = isset($survey['title']) ? (string) $survey['title'] : '';
		$idno          = isset($survey['idno'])  ? (string) $survey['idno']  : (string) $sid;

		$this->load->library('Indicator_table_export');

		$format = strtolower(trim((string) $this->input->get('format')));
		if ($format === 'xlsx') {
			$bytes    = $this->indicator_table_export->build_xlsx(
				$records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $sort_map,
				['dataset_title' => $dataset_title]
			);
			$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $idno) . '_table.xlsx';
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Cache-Control: no-cache, no-store');
			echo $bytes;
			exit;
		}

		$html     = $this->indicator_table_export->build_html(
			$records, $row_dims, $col_dims, $tp_comp, $time_order, $label_map, $sort_map,
			['dataset_title' => $dataset_title]
		);
		$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $idno) . '_table.html';
		header('Content-Type: text/html; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: no-cache, no-store');
		echo $html;
		exit;
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
		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || $survey['type'] !== 'timeseries') {
			show_404();
		}
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			$content = '<div class="container py-4"><p class="text-muted">' . htmlspecialchars(t('indicator_data_no_dsd')) . '</p></div>';
			$this->render_page($sid, $content, $active_tab);
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
		$this->render_page($sid, $content, $active_tab);
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
	
	private function render_page($sid, $content, $active_tab='description')
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

		$dataset=$this->Dataset_model->get_row($sid);
		$study_abstract = isset($dataset['abstract'])
			? trim(strip_tags((string) $dataset['abstract']))
			: '';
		$this->add_study_description_meta($study_abstract !== '' ? $study_abstract : null);
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

		//data api
		$has_data_api=$this->Survey_data_api_model->get_by_sid($sid);

		//default layout template view
		$display_layout='survey_info/layout';

		switch($dataset_type){
			case 'geospatial':
				$page_tabs=array(
					'description'=>array(
						'label'=>t('geospatial_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					'data_dictionary'=>array(
						'label'=>t('feature_catalogue'),
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
			case 'survey':
			case 'microdata':
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
				$has_indicator_mongo = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid) !== null;
				$indicator_ts_sync_pending = (int) $this->Dataset_model->get_indicator_ts_sync_required_for_sid((int) $sid) === 1;
				$show_indicator_chart_and_api = $has_indicator_mongo && !$indicator_ts_sync_pending;
				$page_tabs=array(
					'description'=>array(
						'label'=>t($dataset_type.'_description'),
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					'indicator_chart'=>array(
						'label'=>t('tab_indicator_chart'),
						'url'=>site_url("catalog/$sid/indicator-chart"),
						'show_tab'=>$show_indicator_chart_and_api ? 1 : 0
					),
					'indicator_table'=>array(
						'label'=>t('tab_indicator_table'),
						'url'=>site_url("catalog/$sid/indicator-table"),
						'show_tab'=>$show_indicator_chart_and_api ? 1 : 0
					),
					'indicator_observations'=>array(
						'label'=>t('tab_indicator_observations'),
						'url'=>site_url("catalog/$sid/indicator-data-api"),
						'show_tab'=>$show_indicator_chart_and_api ? 1 : 0
					),
					'indicator_structure'=>array(
						'label'=>t('tab_indicator_structure'),
						'url'=>site_url("catalog/$sid/indicator-structure"),
						'show_tab'=>$has_indicator_mongo ? 1 : 0
					),
					'related_materials'=>array(
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
						'show_tab'=>(int)$related_resources_count
					)
				);
				break;
			case 'timeseriesdb':
			case 'timeseries-db':
				$ts_series_count = $this->Timeseries_db_model->count_series_by_db_idno($dataset['idno']);
				$page_tabs=array(
					'description'=>array(
						'label'=>'Dataset description',
						'url'=>site_url("catalog/$sid/study-description"),
						'show_tab'=>1
					),
					'related_series'=>array(
						'label'=>t('related_series'),
						'url'=>site_url("catalog/$sid/related-series"),
						'show_tab'=>$ts_series_count > 0 ? 1 : 0
					),
					'related_materials'=>array(
						'label'=>t('related_materials'),
						'url'=>site_url("catalog/$sid/related-materials"),
						'show_tab'=>(int)$related_resources_count
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

		$survey_info = $this->get_survey_info($sid);

		// Resolve databases[] for the info panel (timeseries only)
		if ($dataset_type === 'timeseries' && isset($survey_info['metadata'])) {
			$this->Dataset_timeseries_model->normalize_databases_metadata($survey_info['metadata']);
			$databases = isset($survey_info['metadata']['series_description']['databases'])
				? $survey_info['metadata']['series_description']['databases']
				: array();
			foreach ($databases as $i => $db) {
				$idno = isset($db['id']) ? trim((string)$db['id']) : '';
				$resolved = $idno !== '' ? $this->Timeseries_db_model->get_row_by_idno($idno) : null;
				$databases[$i]['catalog_url'] = $resolved
					? site_url('catalog/' . $resolved['id'] . '/study-description')
					: null;
			}
			$survey_info['resolved_databases'] = $databases;
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
			//show search result (v_quick_search returns found/total/rows like catalog_search API)
			$var_result = $this->catalog_search->v_quick_search($id, $limit = 50);
			$data['variables'] = isset($var_result['rows']) ? $var_result['rows'] : $var_result;
			$data['sid']=$id;
			$html.=$this->load->view('survey_info/search_variable_list',$data,TRUE);
		}

		//print html without header/footers
		if($this->input->is_ajax_request()){			
			die($html);
		}

		$options['files']=$this->Data_file_model->get_all_by_survey($id);
		$options['content']=$html;
		$views=$this->data_dictionary_views($id);
		$this->apply_geospatial_catalogue_context($id, $options, $views['type']);
		$content=$this->load->view($views['layout'],$options,TRUE);

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
