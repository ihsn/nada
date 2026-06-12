<?php
/**
* External resources for surveys
*
**/
class Survey_resource_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array(
		'survey_id',
		'dctype',
		'title',
		'subtitle',
		'author', 
		'dcdate',
		'country', 
		'language', 
		'contributor',
		'publisher',
		'rights',
		'toc', 
		'abstract', 
		'description',
		'subjects',
		'filename',
		'dcformat',
		'changed',
		'resource_idno',
		'resource_type',
		'is_url',
		'filesize',
		'created',
		'created_by',
		'changed_by',
		'data_file_id',
		'sort_order',
		'status',
		'metadata'
	);

	private $dctype_groups = array();
	private $dctype_group_translations = array();

	/** @var bool */
	private $_dctype_group_cache_loaded = false;

	/**
	 * Load dctype group config from Dctype_model (codelists DB or config fallback) once per request.
	 */
	private function _ensure_dctype_group_cache()
	{
		if ($this->_dctype_group_cache_loaded) {
			return;
		}
		$this->_dctype_group_cache_loaded = true;
		$this->load->model('Dctype_model');
		$this->dctype_groups             = $this->Dctype_model->get_groups();
		$this->dctype_group_translations = $this->Dctype_model->get_group_translations();
	}
	
			
    public function __construct()
    {
		parent::__construct();
		$this->load->model("Dataset_model");
		$this->load->model("Catalog_model");
		$this->load->helper('hash');
		//$this->output->enable_profiler(TRUE);
    }
	

	/**
	 * Get resolved group labels for a given language code.
	 * Falls back to English translation, then null (caller should use t($key)).
	 *
	 * @param string $lang_code ISO 639-1 code e.g. 'en', 'fr'
	 * @return array [ group_name => title ]
	 */
	public function get_group_labels($lang_code = 'en')
	{
		$this->_ensure_dctype_group_cache();
		$labels = array();
		foreach ($this->dctype_group_translations as $group_name => $translations) {
			if (!empty($translations[$lang_code])) {
				$labels[$group_name] = $translations[$lang_code];
			} elseif (!empty($translations['en'])) {
				$labels[$group_name] = $translations['en'];
			}
		}
		return $labels;
	}

	/**
	 * Prepare and validate resource data for insert or update
	 *
	 * @param array $options - raw input data
	 * @param bool $is_insert - true for insert, false for update
	 * @param int $resource_id - resource ID (for update operations only)
	 * @return array - filtered and prepared data
	 */
	private function prepare_resource_data($options, $is_insert = true, $resource_id = null)
	{
		// For updates, get survey_id from existing resource if not provided
		if (!$is_insert && $resource_id && !isset($options['survey_id'])) {
			$existing = $this->select_single($resource_id);
			if ($existing) {
				$options['survey_id'] = $existing['survey_id'];
			}
		}

		// Handle legacy field mappings
		if (isset($options['type'])){
			$options['dctype'] = $options['type'];
		}
		if (isset($options['format'])){
			$options['dcformat'] = $options['format'];
		}

		// Always recompute resource_type from dctype
		if (!empty($options['dctype'])) {
			$options['resource_type'] = $this->get_resource_type_from_dctype($options['dctype']);
		}

		// Set timestamps
		$options['changed'] = date("U");
		if ($is_insert) {
			$options['created'] = date("U");
		}

		// Process filename
		if (isset($options['filename'])) {
			// Normalize filename
			$options['filename'] = $this->normalize_filename($options['filename']);
			
			// Auto-detect if URL or file
			$options['is_url'] = $this->form_validation->valid_url($options['filename']) ? 1 : 0;

			// Get file metadata for actual files (not URLs)
			if ($options['is_url'] == 0 && isset($options['survey_id'])) {
				$file_metadata = $this->get_file_metadata($options['survey_id'], $options['filename']);
				
				if ($file_metadata) {
					// Always read and set filesize from actual file
					if (!empty($file_metadata['filesize'])) {
						$options['filesize'] = $file_metadata['filesize'];
					}
					
					// Always read and set dcformat from actual file
					if (!empty($file_metadata['mime_type'])) {
						$options['dcformat'] = $file_metadata['mime_type'];
					}
				}
			}
		}

		// Clean up resource_idno: treat empty string as null
		if (isset($options['resource_idno']) && trim($options['resource_idno']) === '') {
			$options['resource_idno'] = null;
		}

		// Validate resource_idno uniqueness if provided
		if (!empty($options['resource_idno']) && isset($options['survey_id'])) {
			// Check if resource_idno already exists (excluding current resource for updates)
			$exclude_id = $is_insert ? null : $resource_id;
			
			if ($this->resource_idno_exists($options['survey_id'], $options['resource_idno'], $exclude_id)) {
				throw new ValidationException("VALIDATION_ERROR: The resource identifier '{$options['resource_idno']}' already exists for this study. Please use a different identifier.", 
					array('resource_idno' => "The resource identifier already exists for this study."));
			}
		}

		// Filter to only allowed fields
		$data = array();
		foreach($options as $key => $value) {
			if (in_array($key, $this->allowed_fields)) {
				$data[$key] = $value;
			}
		}

		return $data;
	}


	/**
	* update external resource
	*
	*	resource_id		int
	* 	options			array
	**/
	function update($resource_id,$options)
	{
		$data = $this->prepare_resource_data($options, false, $resource_id);
		
		//update db
		$this->db->where('resource_id', $resource_id);
		$result=$this->db->update('resources', $data); 
		
		return $result;		
	}
	
	
	/**
	* 
	*	Add external resource
	*
	**/
	function insert($options)
	{
		$data = $this->prepare_resource_data($options, true);

		$this->db->insert('resources', $data); 		
		return $this->db->insert_id();
	}


	public function validate_study_exists($sid)
	{
		if (!$this->Dataset_model->get_idno($sid)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid.');
			return false;
		}
		return true;
	}


	public function validate_resource_exists($resource_id)
	{		
		if (!$this->select_single($resource_id)){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid.');
			return false;
		}
		return true;
	}


	/**
	 * 
	 * 
	 * Validate resource
	 * @options - array of resource fields
	 * @is_new - boolean - if set to true, requires resource_id field to be set
	 * 
	 **/
	function validate_resource($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validate form input
		if(!$is_new){
			//rule for resource_id
			$this->form_validation->set_rules(
				'resource_id', 
				'Resource ID',
				array(
					"required",
					"is_numeric",
					array('validate_resource_exists',array($this, 'validate_resource_exists')),				
				)				
			);
		}

		//below rules only get applied if inserting a new record or filled in when updating a record
		if($is_new || (!$is_new && isset($options['dctype']) )) {
			$this->form_validation->set_rules('dctype', 'Resource Type', 'xss_clean|trim|max_length[100]|required');
			$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
			$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');	
		}
		
		//resource_idno validation rule - if provided
		if(isset($options['resource_idno']) && !empty($options['resource_idno'])) {
			// Build validation rules
			$idno_rules = array(
				'trim',
				'max_length[100]',
				'alpha_dash',
				array('validate_resource_idno_format',array($this, 'validate_resource_idno_format')),
				array('validate_resource_idno_unique',array($this, 'validate_resource_idno_unique'))
			);
			
			$this->form_validation->set_rules(
				'resource_idno', 
				'Resource Identifier',
				$idno_rules
			);
		}
		
		//survey_id validation rule
		$this->form_validation->set_rules(
			'survey_id', 
			'Survey ID',
			array(
				"required",
				"is_numeric",
				array('validate_study_exists',array($this, 'validate_study_exists')),				
			)				
		);
		
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}



	/**
	*
	* Return all resources attached to a survey
	*
	* @fields - comma seperated list of field names
	*
	**/
	function get_survey_resources($sid,$fields=null)
	{
		if(!empty($fields)){
			$this->db->select($fields);
		}else{
			$this->db->select('*');
		}
		$this->db->where('survey_id',$sid);
		$this->db->order_by('title','ASC');
		return $this->db->get('resources')->result_array();
	}

	/**
	 * Sort a resources result_array in memory (after DB fetch).
	 *
	 * @param array  $resources By reference
	 * @param string $sort_by   title|dctype|changed|created|resource_id|filename
	 * @param string $sort_order asc|desc
	 */
	public function sort_resources_result(array &$resources, $sort_by = null, $sort_order = null)
	{
		$allowed = array('title', 'dctype', 'changed', 'created', 'resource_id', 'filename');
		$col = is_string($sort_by) && in_array($sort_by, $allowed, true) ? $sort_by : 'title';
		$desc = is_string($sort_order) && strtolower($sort_order) === 'desc';

		usort(
			$resources,
			function ($a, $b) use ($col, $desc) {
				$va = isset($a[$col]) ? $a[$col] : '';
				$vb = isset($b[$col]) ? $b[$col] : '';
				if (is_numeric($va) && is_numeric($vb)) {
					$cmp = ($va == $vb) ? 0 : (($va < $vb) ? -1 : 1);
				} else {
					$cmp = strnatcasecmp((string) $va, (string) $vb);
				}
				return $desc ? -$cmp : $cmp;
			}
		);
	}

	/**
	 * Whether the resource filename points to an existing local file or a remote URL (catalog tab semantics).
	 *
	 * @param int   $sid
	 * @param array $resources result_array rows (mutated: adds file_exists bool)
	 */
	public function enrich_resources_file_exists($sid, array &$resources)
	{
		$this->load->model('Catalog_model');
		$this->load->helper('file');

		$survey_folder = $this->Catalog_model->get_survey_path_full($sid);
		$root = ($survey_folder && is_dir($survey_folder)) ? unix_path(rtrim($survey_folder, '/')) : '';

		foreach ($resources as $k => $row) {
			$fn = isset($row['filename']) ? trim((string) $row['filename']) : '';
			if ($fn === '') {
				$resources[$k]['file_exists'] = false;
				continue;
			}
			if (function_exists('is_url') && is_url($fn)) {
				$resources[$k]['file_exists'] = true;
				continue;
			}
			if ($root !== '') {
				$full = unix_path($root . '/' . $fn);
				$resources[$k]['file_exists'] = is_file($full);
			} else {
				$resources[$k]['file_exists'] = false;
			}
		}
	}


	/**
	* returns resource filenames by survey id
	*
	*
	**/
	function get_survey_resource_files($surveyid)
	{
		$this->db->select("resource_id,filename,title");
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	* 
	*
	* returns a single row
	*
	**/
	function select_single($id)
	{
		$this->db->where('resource_id', $id); 
		return $this->db->get('resources')->row_array();
	}


	function delete($id)
	{
		$this->db->where('resource_id', $id); 
		return $this->db->delete('resources');
	}


	//delete a single resource by dataset
	function delete_single($sid,$resource_id)
	{
		if (!$this->check_survey_resource($sid,$resource_id)){
			return false;
		}

		$this->db->where('survey_id', $sid);  
		$this->db->where('resource_id', $resource_id); 
		$this->db->delete('resources');

		return true;
	}

	function check_survey_resource($sid,$resource_id)
	{
		$this->db->select("resource_id");
		$this->db->where('survey_id', $sid);  
		$this->db->where('resource_id', $resource_id);
		$result=$this->db->get("resources")->result_array();

		if(empty($result)){
			return false;
		}
		return true;
	}
	

	/**
	*
	* Delete all resources by survey id
	**/
	function delete_all_survey_resources($survey_id)
	{
		$this->db->where('survey_id', $survey_id); 
		return $this->db->delete('resources');
	}
	

	/**
	* returns DC Types
	*
	*
	**/
	function get_dc_types()
	{
		$this->load->model('Dctype_model');
		$lang = ci_lang_to_iso();
		$flat = $this->Dctype_model->get_flat_for_lang($lang);
		$list = array();
		foreach ($flat as $code => $title) {
			$list[$code] = $title . ' [' . $code . ']';
		}
		return $list;
	}
	
	/**
	* returns DC Formats
	*
	*
	**/
	function get_dc_formats()
	{
		$result= $this->db->get('dcformats')->result_array();

		$list=array();
		foreach($result as $row){
			$list[$row['title']]=$row['title'];
		}
		
		return $list;
	}
	
	/**
	* Returns the DC Format ID by Format-name
	*
	*/
	function get_dcformat_id_by_name($type_name)
	{
		$type_arr=explode(' ', $type_name);

		if (!$type_arr)
		{
			return 0;
		}
		
		$type=NULL;
		foreach($type_arr as $str)
		{
			$str=trim($str);
			if (isset($str[0]))
			{
				if ($str[0]=='[' && $str[strlen($str)-1]==']')
				{
					$type=$str;
				}
			}
		}
		
		//Type not found
		if ($type==NULL)
		{
			return 0;
		}
		
		//search db
		$this->db->select('id'); 
		$this->db->like('title', $type); 
		$result= $this->db->get('dcformats')->row_array();
		
		if ($result)
		{
			return $result['id'];
		}
		else
		{
			return 0;
		}	
	}
	
	
	
	
	/**
	*
	* Get a resource by filepath
	*
	* @filepath	relative path to the resource
	*/
	function get_resources_by_filepath($filepath)
	{
		$this->db->where('filename', $filepath); 
		return $this->db->get('resources')->result_array();
	}
	
	/**
	*
	* Get a resource by filepath
	*
	* @filepath	relative path to the resource
	*/
	function get_survey_resources_by_filepath($surveyid,$filepath)
	{
		$filepath=$this->normalize_filename($filepath);

		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath); 		
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	*
	* Check if resource is a duplicate
	*
	* @filepath	relative path to the resource
	*/
	function check_duplicate($surveyid,$filepath,$title,$dctype=null)
	{
		$filepath=$this->normalize_filename($filepath);

		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath);		
		$this->db->where('LOWER(title)', strtolower(trim($title)));		
		$resources=$this->db->get('resources')->result_array();
		$dctype=$this->get_dctype_code_from_string($dctype);

		if ($resources){
			foreach($resources as $idx=>$resource){
				$res_dctype=$this->get_dctype_code_from_string($resource['dctype']);
				if ($dctype!=$res_dctype){
					unset($resources[$idx]);
				}
			}
		}
		return $resources;
	}

	/**
	 * 
	 * 
	 * Return the dctype code from text
	 * 
	 * e.g. Document [doc/adm] will return doc/adm
	 * 
	 */
	function get_dctype_code_from_string($dctype)
	{
		preg_match_all("/\[([^\]]*)\]/", $dctype, $matches);
		$result= $matches[1];
		if ($result){
			return $result[0];
		}
		return $dctype;
	}
	
	
	/**
	*
	* Get a list of all resources by survey id
	*
	*/
	function get_resources_by_survey($surveyid)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}

	/**
	* returns a single row
	*
	*
	**/
	function get_single_resource_by_survey($sid,$resource_id)
	{
		$this->db->where('resource_id', $resource_id); 
		$this->db->where('survey_id', $sid); 
		return $this->db->get('resources')->row_array();
	}

	/**
	*
	* List of resources grouped by resource-type
	*
	*
	*/
	function get_grouped_resources_by_survey($surveyid)
	{
		$this->_ensure_dctype_group_cache();
		$output = false;
		$codes_exclude = array();

		if ($this->dctype_groups) {
			foreach ($this->dctype_groups as $group_name => $codes) {
				foreach ($codes as $code) {
					$codes_exclude[] = $code;
					$result = $this->get_resources_by_type($surveyid, $code);
					if ($result) {
						if (!isset($output[$group_name])) {
							$output[$group_name] = $result;
						} else {
							$output[$group_name] = array_merge($output[$group_name], $result);
						}
					}
				}
			}
			$output['other_materials'] = $this->get_resources_other_materials($surveyid, $codes_exclude);
			return $output;
		}

		// Fallback when no config: questionnaires, reports, technical, other
		$result = $this->get_resources_by_type($surveyid, 'doc/qst');
		if ($result) {
			$output['questionnaires'] = $result;
		}
		$result = $this->get_resources_by_type($surveyid, 'doc/rep');
		if ($result) {
			$output['reports'] = $result;
		}
		$result = $this->get_resources_by_type($surveyid, 'doc/tec');
		if ($result) {
			$output['technical'] = $result;
		}
		$result = $this->get_resources_by_type($surveyid, 'other');
		if ($result) {
			$output['other'] = $result;
		}
		return $output;
	}


	/**
	 * Flat list of resource_type codes that belong to configured groups (for "other" exclude list).
	 *
	 * @return array
	 */
	private function _get_grouped_resource_type_codes()
	{
		$this->_ensure_dctype_group_cache();
		$codes = array();
		if ($this->dctype_groups) {
			foreach ($this->dctype_groups as $dctypes) {
				foreach ($dctypes as $code) {
					$codes[] = $code;
				}
			}
		} else {
			$codes = array('doc/qst', 'doc/rep', 'doc/tec');
		}
		return array_values(array_unique($codes));
	}

	/**
	 * Return resources by survey and type. Uses resource_type for reliable filtering.
	 *
	 * @param int    $surveyid Survey ID
	 * @param string $dctype   Type code (e.g. 'doc/qst', 'final'), or 'other' for non-grouped non-microdata, or "Label [code]" (normalized to code)
	 * @return array
	 */
	function get_resources_by_type($surveyid, $dctype)
	{
		$code = null;
		if ($dctype !== 'other') {
			$code = $this->get_dctype_code_from_string($dctype);
			if ($code === null || $code === '') {
				$code = $dctype;
			}
		}

		$this->db->select('resources.*, surveys.idno as dataset_idno');
		$this->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
		$this->db->where('survey_id', $surveyid);

		if ($dctype === 'other') {
			$exclude = array_merge(
				$this->_get_grouped_resource_type_codes(),
				$this->_get_microdata_resource_type_codes()
			);
			$exclude = array_filter(array_unique($exclude));
			if (!empty($exclude)) {
				$this->db->group_start();
				$this->db->where_not_in('resource_type', $exclude);
				$this->db->or_where('resource_type IS NULL', null, false);
				$this->db->group_end();
			}
		} else {
			$this->db->where('resource_type', $code);
		}

		$result = $this->db->get('resources')->result_array();
		foreach ($result as $row_idx => $row) {
			$result[$row_idx]['is_microdata'] = $this->is_microdata_resource_type(isset($row['resource_type']) ? $row['resource_type'] : null)
				? true
				: $this->is_microdata_resource(isset($row['dctype']) ? $row['dctype'] : '');
		}
		return $result;
	}

	/**
	 * Return resources that are not in the given type codes and not microdata (for "other materials").
	 *
	 * @param int   $surveyid        Survey ID
	 * @param array $resource_type_codes_exclude List of resource_type codes to exclude (e.g. from config groups)
	 * @return array
	 */
	function get_resources_other_materials($surveyid, $resource_type_codes_exclude)
	{
		$exclude = array_merge(
			(array) $resource_type_codes_exclude,
			$this->_get_microdata_resource_type_codes()
		);
		$exclude = array_filter(array_unique($exclude));

		$this->db->select('resources.*, surveys.idno as dataset_idno');
		$this->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
		$this->db->where('survey_id', $surveyid);

		if (!empty($exclude)) {
			$this->db->group_start();
			$this->db->where_not_in('resource_type', $exclude);
			$this->db->or_where('resource_type IS NULL', null, false);
			$this->db->group_end();
		}

		$result = $this->db->get('resources')->result_array();
		foreach ($result as $row_idx => $row) {
			$result[$row_idx]['is_microdata'] = $this->is_microdata_resource_type(isset($row['resource_type']) ? $row['resource_type'] : null)
				? true
				: $this->is_microdata_resource(isset($row['dctype']) ? $row['dctype'] : '');
		}
		return $result;
	}

	/**
	 * Microdata resource_type codes. Single source of truth for SQL filtering by type.
	 *
	 * @return array
	 */
	private function _get_microdata_resource_type_codes()
	{
		return array('dat', 'dat/micro');
	}

	/**
	 * Apply WHERE conditions for microdata (resource_type IN (...)). Caller must set from/where survey_id first.
	 */
	private function _where_microdata_resource_type()
	{
		$this->db->where_in('resource_type', $this->_get_microdata_resource_type_codes());
	}

	/**
	 * Return dctype patterns for microdata (used when only dctype string is available, e.g. display).
	 *
	 * @return array
	 */
	private function _get_microdata_dctype_patterns()
	{
		return array('[dat/micro]', '[dat]', '[dat/');
	}

	/**
	 * Return true if resource_type code is microdata. Use for rows that have resource_type set.
	 */
	function is_microdata_resource_type($resource_type)
	{
		if ($resource_type === null || $resource_type === '') {
			return false;
		}
		return in_array($resource_type, $this->_get_microdata_resource_type_codes(), true);
	}

	/**
	 * Return true or false if dctype string indicates microdata. Use when only dctype is available.
	 */
	function is_microdata_resource($dctype)
	{
		$dctype = $dctype === null || $dctype === '' ? '' : (string) $dctype;
		foreach ($this->_get_microdata_dctype_patterns() as $type) {
			if (stripos($dctype, $type) !== false) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Return resources of microdata type (uses resource_type for reliable filtering).
	 */
	function get_microdata_resources($surveyid)
	{
		$this->db->select('*');
		$this->db->from('resources');
		$this->db->where('survey_id', $surveyid);
		$this->_where_microdata_resource_type();
		$this->db->order_by('dcdate', 'desc');
		$this->db->order_by('title', 'asc');
		return $this->db->get()->result_array();
	}
	
	/**
	*
	* Returns Data Access type set for the Resource
	**/
	function get_resource_da_type($resource_id)
	{
		$this->db->select('forms.model');
		$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
		$this->db->join('forms', 'forms.formid= surveys.formid','inner');
		$this->db->where('resources.resource_id',$resource_id);
		$query=$this->db->get('resources')->row_array();
		
		if ($query)
		{
			return $query['model'];
		}
		
		return FALSE;
	}
	
	/**
	*
	* Check user has access to resource file
	*
	* @resource_obj - resource record 
	* 
	* 
	**/
	function user_has_download_access($user_id,$survey_id,$resource_obj) 
	{
		$this->load->model("Licensed_model");
		$this->load->model("Public_model");

		$dctype = isset($resource_obj['dctype']) ? $resource_obj['dctype'] : '';
		if (!$this->is_microdata_resource($dctype)) {
			return true;
		}
		
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		switch($data_access_type){
			case 'direct':
			case 'open':
			case 'public':
				return true;
				break;
			case 'licensed':

				if (!$user_id){
					throw new Exception(t("reason_login_licensed_access"));
				}

				$req_entries=$this->Licensed_model->get_requests_by_file($resource_obj['resource_id'],$user_id);

				foreach($req_entries as $req){
					if(strtoupper(trim($req['status']))=='APPROVED' 
						&& $req['expiry']> date("U") 
						&& (int)$req['downloads'] < (int)$req['download_limit'])
					{
						return true;
					}
				}
			
				throw new Exception("Access expired or you have reached the download limit for the file.");
				break;

			/*case 'public':
				$puf_request=$this->Public_model->check_user_has_data_access($user_id,$survey_id);

				if ($puf_request===FALSE){
					throw new Exception("For PUF files, user must accept terms of use.");
				}
				
				return true;
				break;
			*/
			default:
				throw new Exception("Unsupported");
		}
	}

	/**
	 * 
	 * Same as user_has_download_access except it returns an array with access info
	 */
	function get_user_download_access_info($user_id,$survey_id,$resource_obj) 
	{
		$this->load->model("Licensed_model");
		$this->load->model("Public_model");

		$dctype = isset($resource_obj['dctype']) ? $resource_obj['dctype'] : '';
		if (!$this->is_microdata_resource($dctype)) {
			return array(
				'access' => true,
				'is_microdata' => false
			);
		}
		
		$data_access_type=$this->Catalog_model->get_survey_form_model($survey_id);

		switch($data_access_type){
			case 'direct':
			case 'open':
				return array(
					'access'=>true,
					'is_microdata'=>true,
					'license'=>$data_access_type
				);
				break;
			case 'licensed':

				if (!$user_id){
					throw new Exception(t("reason_login_licensed_access"));
				}

				$req_entries=$this->Licensed_model->get_requests_by_file($resource_obj['resource_id'],$user_id);

				foreach($req_entries as $req){
					if(strtoupper(trim($req['status']))=='APPROVED' 
						&& $req['expiry']> date("U") 
						&& (int)$req['downloads'] < (int)$req['download_limit'])
					{
						return array(
							'access'=>true,
							'is_microdata'=>true,
							'license'=>$data_access_type,
							'access_request'=>$req
						);
					}
				}
			
				return false;
				break;

			case 'public':

				//TODO: remove - for api access, at the time of api key creation, user must accept terms of use
				$puf_request=$this->Public_model->check_user_has_data_access($user_id,$survey_id);
				
				/*if ($puf_request===FALSE){
					return array(
						'access'=>false,
						'is_microdata'=>true,
						'license'=>$data_access_type,
						'error'=>'puf'
					);
				}*/
				
				return array(
					'access'=>true,
					'is_microdata'=>true,
					'license'=>$data_access_type,
					'access_request'=>$puf_request
				);

				break;
			default:
				return false;	
		}		
	}


	function whitelist_download($user_id,$survey_id,$resource)
	{
		$this->load->model("Data_access_whitelist_model");
		$user_whitelisted=$this->Data_access_whitelist_model->has_access($user_id,$survey_id);

		if(!$user_whitelisted){
			return false;
		}

		$resource_path=$this->get_resource_download_path($resource['resource_id']);

		if (!file_exists($resource_path)){
			throw new Exception ('RESOURCE_FILE_NOT_FOUND');
		}

		$dctype = isset($resource['dctype']) ? $resource['dctype'] : '';
		$is_microdata = $this->is_microdata_resource($dctype);

		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$resource_path.'</em>');
		$this->analytics_tracker->track_download($survey_id, basename($resource_path), array(
			'file_type' => $is_microdata ? 'microdata' : 'doc'
		));
		force_download2($resource_path);
		exit;
	}


	/**
	 * True when the resource is a local (non-URL) PDF file on disk.
	 */
	function is_local_pdf_resource($resource)
	{
		if (empty($resource) || ! is_array($resource)) {
			return false;
		}

		if (! empty($resource['is_url']) && (int) $resource['is_url'] === 1) {
			return false;
		}

		$filename = isset($resource['filename']) ? trim((string) $resource['filename']) : '';
		if ($filename === '') {
			return false;
		}

		if ($this->form_validation->valid_url($filename)) {
			return false;
		}

		$dcformat = strtolower(trim((string) ($resource['dcformat'] ?? '')));
		if ($dcformat === 'application/pdf') {
			return true;
		}

		return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
	}


	/**
	 * Stream a local PDF inline (Content-Disposition: inline). Same ACL as download().
	 */
	function stream_pdf_inline($user, $survey_id, $resource_id)
	{
		$resource = $this->select_single($resource_id);

		if (! $resource) {
			throw new Exception('RESOURCE_NOT_FOUND');
		}

		if ((int) $resource['survey_id'] !== (int) $survey_id) {
			throw new Exception('RESOURCE_NOT_FOUND');
		}

		if (! $this->is_local_pdf_resource($resource)) {
			throw new Exception('RESOURCE_NOT_PDF_STREAMABLE');
		}

		$user_id = isset($user->id) ? $user->id : false;

		$this->whitelist_stream_pdf($user_id, $survey_id, $resource);

		$download_req = $this->get_user_download_access_info($user_id, $survey_id, $resource);

		if (! $download_req) {
			throw new Exception('FILE_NOT_AVAILABLE');
		}

		if ($download_req['is_microdata'] === true && $download_req['license'] == 'public') {
			if (! $user_id) {
				throw new Exception(t('USER_NOT_LOGGED_IN'));
			}
		}

		$resource_path = $this->get_resource_download_path($resource_id);

		if (! file_exists($resource_path)) {
			throw new Exception('RESOURCE_FILE_NOT_FOUND');
		}

		$this->load->helper('download');
		log_message('info', 'Streaming PDF inline <em>'.$resource_path.'</em>');
		force_download_inline($resource_path, null, true);
		throw new Exception('RESOURCE_STREAM_FAILED');
	}


	function whitelist_stream_pdf($user_id, $survey_id, $resource)
	{
		$this->load->model('Data_access_whitelist_model');
		$user_whitelisted = $this->Data_access_whitelist_model->has_access($user_id, $survey_id);

		if (! $user_whitelisted) {
			return false;
		}

		$resource_path = $this->get_resource_download_path($resource['resource_id']);

		if (! file_exists($resource_path)) {
			throw new Exception('RESOURCE_FILE_NOT_FOUND');
		}

		$this->load->helper('download');
		log_message('info', 'Streaming PDF inline (whitelist) <em>'.$resource_path.'</em>');
		force_download_inline($resource_path, null, true);
		throw new Exception('RESOURCE_STREAM_FAILED');
	}


	function download($user,$survey_id,$resource_id)
	{
		//get resource
		$resource=$this->select_single($resource_id);

		if(!$resource){
			throw new Exception("RESOURCE_NOT_FOUND");
		}

		$user_id=isset($user->id) ? $user->id : false;
		
		$this->whitelist_download($user_id,$survey_id,$resource);
		
		$download_req=$this->get_user_download_access_info($user_id,$survey_id,$resource);

		if (!$download_req){
			throw new Exception("FILE_NOT_AVAILABLE");
		}

		//for public use
		if ($download_req['is_microdata']===true && $download_req['license']=='public'){
			if (!$user_id){
				throw new Exception(t("USER_NOT_LOGGED_IN"));
			}
		}				
		
		//licensed access increment download count
		if ($download_req['is_microdata']===true && $download_req['license']=='licensed'){
			$lic_request_info=$download_req['access_request'];

			//increment the download count for licensed file
			$this->Licensed_model->update_download_stats($resource_id,$lic_request_info['requestid'],$user->email);
		}


		//full path to the resource
		$resource_path=$this->get_resource_download_path($resource_id);

		if (!file_exists($resource_path)){
			throw new Exception ('RESOURCE_FILE_NOT_FOUND');
		}

		//download file
		$this->load->helper('download');
		log_message('info','Downloading file <em>'.$resource_path.'</em>');
		$this->analytics_tracker->track_download($survey_id, basename($resource_path), array(
			'file_type' => $download_req['is_microdata'] ? 'microdata' : 'resource'
		));
		force_download2($resource_path);
		exit;
	}
	
	
	/**
	*
	* Check if any resources are attached to the study
	*
	*/
	function has_external_resources($surveyid)
	{
		$microdata = $this->_get_microdata_resource_type_codes();
		$this->db->select('count(*) as total');
		$this->db->where('survey_id', $surveyid);
		$this->db->group_start();
		$this->db->where_not_in('resource_type', $microdata);
		$this->db->or_where('resource_type IS NULL', null, false);
		$this->db->group_end();
		$result = $this->db->get('resources')->row_array();
		return isset($result['total']) ? (int) $result['total'] : 0;
	}

	/**
	 * Returns resource counts grouped by resource_type (code). Use get_dctype_label_by_code() for display.
	 */
	function get_grouped_resources_count($surveyid)
	{
		$this->db->select('resource_type, count(*) as total');
		$this->db->where('survey_id', $surveyid);
		$this->db->group_by('resource_type');
		return $this->db->get('resources')->result_array();
	}

	
	/**
	 * 
	 * Get the resource filename
	 * 
	 */
	function get_resource_filename($resource_id)
	{
		$resource=$this->select_single($resource_id);
		
		if (!$resource)
		{
			return FALSE;
		}
		
		return $resource['filename'];
	}

	
	function get_resource_download_path($resource_id)
	{		
		//resource info
		$resource=$this->select_single($resource_id);
		
		if (!$resource){
			return FALSE;
		}
		
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($resource['survey_id']);
						
		//build complete filepath to be downloaded
		$file_path=unix_path($survey_folder.'/'.$resource['filename']);

		return $file_path;		
	}


	/**
	*
	* Check if resource already exists for a study
	*
	* @filepath	relative path to the resource
	*/
	function survey_resource_exists($sid,$title,$dctype,$filename)
	{
		$this->db->select('count(*) as found');
		$this->db->where('survey_id', $sid); 
		$this->db->where('filename', $filename);
		$this->db->where('dctype', $dctype); 
		$query=$this->db->get('resources')->row_array();
		
		if ($query['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}


	/**
	*
	* Import RDF file
	**/
	public function import_rdf($surveyid,$filepath)
	{
		//check file exists
		if (!file_exists($filepath)){
			return FALSE;
		}
		
		//read rdf file contents
		$rdf_contents=file_get_contents($filepath);
			
		//load RDF parser class
		$this->load->library('RDF_Parser');
			
		//parse RDF to array
		$rdf_array=$this->rdf_parser->parse($rdf_contents);

		if ($rdf_array===FALSE || $rdf_array==NULL){
			return FALSE;
		}

		//Import
		$rdf_fields=$this->rdf_parser->fields;

		$output=array(
			'added'=>0,
			'skipped'=>0
		);
			
		//success
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
			
			//check filenam is URL?
			$insert_data['filename']=$this->normalize_filename($insert_data['filename']);
			
			//check if the resource file already exists
			$resource_exists=$this->get_survey_resources_by_filepath($surveyid,$insert_data['filename']);
			
			if (!$resource_exists)
			{										
				//insert into db
				$this->insert($insert_data);
				$output['added']++;
			}
			else{
				$output['skipped']++;
			}
		}
	
		return $output;
	}

	function normalize_filename($filename)
	{
		//check filenam is URL?
		if (!is_url($filename))
		{
			//clean file paths
			$filename=unix_path($filename);
			
			//keep only the filename, remove path
			return basename($filename);
		}

		return $filename;
	}


	/**
	 * Get file metadata (mime type and file size)
	 * 
	 * @param int $survey_id
	 * @param string $filename
	 * @return array|false - array with mime_type and filesize, or false if file not found
	 */
	function get_file_metadata($survey_id, $filename)
	{
		if (empty($survey_id) || empty($filename)) {
			return false;
		}

		// Get survey folder path
		$survey_folder = $this->Catalog_model->get_survey_path_full($survey_id);
		
		if (!$survey_folder) {
			return false;
		}

		// Build full file path
		$file_path = unix_path($survey_folder . '/' . $filename);
		
		// Check if file exists
		if (!file_exists($file_path) || !is_file($file_path)) {
			return false;
		}

		$metadata = array();

		// Get mime type using helper function
		$mime_type = get_file_mime_type($file_path);
		if ($mime_type) {
			$metadata['mime_type'] = $mime_type;
		}

		// Get file size in bytes
		$filesize = @filesize($file_path);
		if ($filesize !== false) {
			$metadata['filesize'] = $filesize;
		}

		return !empty($metadata) ? $metadata : false;
	}


	/**
	 * 
	 * Upload an RDF file and import resources
	 * 
	 * 
	 */
	function import_uploaded_rdf($sid,$tmp_path,$file_field='rdf')
	{
		//upload RDF file
		$uploaded_rdf_path=$this->upload_rdf($tmp_path,$file_field);
		
		//import rdf entries
		$rdf_import_result=$this->import_rdf($sid,$uploaded_rdf_path);

		//delete rdf
		@unlink($uploaded_rdf_path);

		return $rdf_import_result;
	}


	/**
     *
     * Count resources by survey
     *
     */
    function get_resources_count_by_survey($sid)
    {
        $this->db->select('count(resource_id) as total');
        $this->db->where('survey_id', $sid);
        $result=$this->db->get('resources')->row_array();
        return $result['total'];
	}

	function get_microdata_resources_count_by_survey($sid)
	{
		$this->db->select('count(resource_id) as total');
		$this->db->from('resources');
		$this->db->where('survey_id', $sid);
		$this->_where_microdata_resource_type();
		$result = $this->db->get()->row_array();
		return isset($result['total']) ? (int) $result['total'] : 0;
	}


	/**
	*
	* Returns an array of all files in the survey folder
	*
	**/
	function get_files_array($sid)
	{	
		$this->load->model('Catalog_model');
		$this->load->model('Managefiles_model');

		//get survey folder path
		$folderpath=$this->Catalog_model->get_survey_path_full($sid);
					
		//get all survey files
		$data=$this->get_files_recursive($folderpath,$folderpath);
		$files=array();
		
		if (isset($data['files'])){
			foreach($data['files'] as $file){				
				$file_rel_path=$file['relative'].'/'.$file['name'];
				$files[]=array(					
					'name'=>$file['name'],
					'rel_path'=>$file_rel_path,
					'base64'=>base64_encode($file['name']),
					'date'=>$file['date'],
					'fileperms'=>$file['fileperms'],
					'size'=>$file['size']
				);
			}
		}		
		return $files;
	}



	/**
	 * 	
	 *
	 * upload external resource file
	 *
	 * @sid - survey id
	 * @file_field_name 	- name of POST file variable
	 *  
	 **/ 
	function upload_file($sid,$file_field_name='file',$remove_spaces=true)
	{
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $survey_folder;
		$config['overwrite'] = true;
		$config['encrypt_name']=false;
		$config['remove_spaces'] = $remove_spaces;//convert spaces or not
		$config['allowed_types'] = str_replace(",","|",$this->config->item("allowed_resource_types"));
		
		$this->load->library('upload', $config);
		//$this->upload->initialize($config);

		//process uploaded rdf file
		$upload_result=$this->upload->do_upload($file_field_name);

		if (!$upload_result){
			$error = $this->upload->display_errors('', ''); // remove <p> tags
			throw new Exception($error);
		}

		return $this->upload->data();		
	}
	

	/**
	 * 	
	 *
	 * upload rdf file
	 *
	 * @file_field 	- name of POST file variable
	 *  
	 **/ 
	function upload_rdf($tmp_path,$file_field='file')
	{		
		if (!$tmp_path){
			$tmp_path=get_catalog_root().'/tmp';
		
			if (!file_exists($tmp_path)){
				@mkdir($tmp_path);
			}
		}
		
		if (!file_exists($tmp_path)){
			throw new Exception('TEMP-FOLDER-NOT-SET: '.$tmp_path);
		}
						
		//upload class configurations for RDF
		$config['upload_path'] = $tmp_path;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf|xml';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload($file_field);

		if(!$rdf_upload_result){
			$error = $this->upload->display_errors();
			throw new Exception("RDF_UPLOAD::".$error);
		}
		
		$upload = $this->upload->data();

		//path to the uploaded rdf file
		return $upload['full_path'];
		
	}



	/*
	* Delete a single file
	*
	*/
	function delete_file($sid, $base64_filepath)
	{
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
				
		$filepath=urldecode(base64_decode($base64_filepath));		
		$fullpath=unix_path($survey_folder.'/'.$filepath);
		
		//log deletion
		$this->db_logger->write_log('resource-delete',$fullpath,'external-resource',$sid);
		
		if(!file_exists($fullpath)){
			throw new Exception("FILE_NOT_FOUND: ".urlencode($filepath));
		}
		elseif (is_file($fullpath) && file_exists($fullpath)){
			$isdeleted=silent_unlink($fullpath);
			
			if($isdeleted===FALSE){
				throw new Exception("file_delete_failed");
			}
		}
		return true;
	}


	/**
	 * 
	 * Download a file
	 * 
	 */
	function download_file($sid, $base64_filepath) 
	{
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);
		
		if (!file_exists($survey_folder)){
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}
		
		$filepath=urldecode(base64_decode($base64_filepath));		
		$fullpath=unix_path($survey_folder.'/'.basename($filepath));
		
		//log download
		$this->db_logger->write_log('download',$fullpath,'external-resource');
		
		if (is_file($fullpath) && file_exists($fullpath)){
			$this->load->helper('download');
			log_message('info','Downloading file <em>'.$fullpath.'</em>');
			force_download2($fullpath);
		}
		else {
			throw new Exception("FILE_NOT_FOUND: ".urlencode(basename($filepath)));
		}
	}




	/**
	*
	* Fix file paths for external resources and sync all resource metadata
	**/
	function fix_resource_links($surveyid)
	{		
		$this->load->model('Catalog_model');

		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		
		//get survey resources
		$resources=$this->get_survey_resource_files($surveyid);
		
		//hold broken resources
		$broken_links=array();
		
		//build an array of broken resources, ignore the resources with correct paths
		foreach($resources as $resource){
			//check if the resource file found on disk
			if(!is_url($resource['filename'])){
				if(!file_exists( unix_path($survey_folder.'/'.$resource['filename']))){
					$broken_links[]=$resource;
				}
			}
		}
		
		//get a list of all files in the survey folder
		$files=$this->get_files_recursive($survey_folder,$survey_folder);

		//number of links fixed
		$fixed_count=0;
		
		//find matching files in the filesystem for the broken links
		foreach($broken_links as $key=>$resource){			
			$match=FALSE;
			
			//search files array and return the relative path to the file if found 
			foreach($files['files'] as $file){
				//match found
				if(strtolower($file['name'])==strtolower(basename($resource['filename'])) ){					
					$match=$file['relative'];
					
					//update path in database
					$this->update($resource['resource_id'],array('filename'=>$file['relative'].'/'.$file['name']));
					
					//update the count
					$fixed_count++;					
					break;
				}
			}			
			//add path for the resources
			$broken_links[$key]['match']=$match;
		}
		
		// After fixing broken links, sync all resource metadata
		try {
			$sync_result = $this->sync_all_resources($surveyid);
			
			log_message('info', sprintf(
				"Resource sync after fixlinks: %d synced, %d not found, %d errors",
				$sync_result['synced'],
				$sync_result['not_found'],
				$sync_result['errors']
			));
		} catch (Exception $e) {
			log_message('error', "Resource sync failed after fixlinks: " . $e->getMessage());
		}
		
		return $fixed_count;
	}



	/**
	*
	* Return all files including subfolders
	* 
	*	@make_relative_to	make the file path relative to this path
	*/	
	function get_files_recursive($absolute_path,$make_relative_to)
	{
		$dirs = array();
		$files = array();
		//traverse folder
		if ( $handle = @opendir( $absolute_path )){
			while ( false !== ($file = readdir( $handle ))){
				if (( $file != "." && $file != ".." )){
					if ( is_dir( $absolute_path.'/'.$file )){
						$tmp=$this->get_files_recursive($absolute_path.'/'.$file,$make_relative_to);
						foreach($tmp['files'] as $arr){
							if (isset($arr["name"])){
								$files[]=$arr;
							}
						}
						foreach($tmp['dirs'] as $arr){
							if (isset($arr["name"])){
								$dirs[]=$arr;
							}
						}
						$dirs[]=$this->get_file_relative_path($make_relative_to,$absolute_path.'/'.$file);
					}
					else{
						$tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));
						$tmp['name']=$file;
						$tmp['size']=format_bytes($tmp['size']);
						$tmp['fileperms']=symbolic_permissions($tmp['fileperms']);
						$tmp['path']=$absolute_path;
						$tmp['relative']=$this->get_file_relative_path($make_relative_to,$absolute_path);
						$files[]=$tmp;
					}
				}
			}
			closedir( $handle );
			sort( $dirs );
		}
		return array('files'=>$files, 'dirs'=>$dirs);
	}

	/**
	*
	* Get file relative path excluding the survey folder path
	*
	*/
	function get_file_relative_path($survey_path,$file_path)
	{
		$survey_path=unix_path($survey_path);
		$file_path=unix_path($file_path);		
		return str_replace($survey_path,"",$file_path);
	}



	/**
	 * 
	 * Extract resource_type code from dctype
	 * 
	 * Extracts the code from dctype string (the part in brackets)
	 * e.g., "Document, Questionnaire [doc/qst]" -> "doc/qst"
	 * 
	 * @param string $dctype - full dctype string or code
	 * @return string - dctype code
	 */
	function get_resource_type_from_dctype($dctype)
	{
		if (empty($dctype)) {
			return null;
		}

		// Extract code from string if in format "Label [code]"
		$code = $this->get_dctype_code_from_string($dctype);

		// No brackets found — treat the whole value as a bare code (e.g., "doc/qst")
		return !empty($code) ? $code : $dctype;
	}


	/**
	 * 
	 * 
	 * Return the DCTYPE label by code
	 * 
	 * 
	 */
	function get_dctype_label_by_code($dctype)
	{
		$codes=array(
			'doc/adm'=>'Document, Administrative [doc/adm]',
			'doc/anl'=>'Document, Analytical [doc/anl]',
			'doc/oth'=>'Document, Other [doc/oth]',
			'doc/qst'=>'Document, Questionnaire [doc/qst]',
			'doc/ref'=>'Document, Reference [doc/ref]',
			'doc/rep'=>'Document, Report [doc/rep]',
			'doc/tec'=>'Document, Technical [doc/tec]',
			'aud'=>'Audio [aud]',
			'dat'=>'Database [dat]',
			'map'=>'Map [map]',
			'dat/micro'=>'Microdata File [dat/micro]',
			'pic'=>'Photo [pic]',
			'prg'=>'Program [prg]',
			'tbl'=>'Table [tbl]',
			'vid'=>'Video [vid]',
			'web'=>'Web Site [web]'
		);

		if(array_key_exists($dctype,$codes)){
			return $codes[$dctype];
		}
		
		return $dctype;
	}

	/**
	 * 
	 * 
	 * Return the dcformat label by code
	 * 
	 * 
	 */
	function get_dcformat_label_by_code($dcformat)
	{
		$codes=array(
			'application/x-compressed'=>'Compressed, Generic []',
			'application/zip'=>'Compressed, ZIP',
			'application/x-cspro'=>'Data, CSPro',
			'application/dbase'=>'Data, dBase',
			'application/msaccess'=>'Data, Microsoft Access',
			'application/x-sas'=>'Data, SAS',
			'application/x-spss'=>'Data, SPSS',
			'application/x-stata'=>'Data, Stata',
			'text'=>'Document, Generic',
			'text/html'=>'Document, HTML',
			'application/msexcel'=>'Document, Microsoft Excel',
			'application/mspowerpoint'=>'Document, Microsoft PowerPoint',
			'application/msword'=>'Document, Microsoft Word',
			'application/pdf'=>'Document, PDF',
			'application/postscript'=>'Document, Postscript',
			'text/plain'=>'Document, Plain',
			'text/wordperfect'=>'Document, WordPerfect',
			'image/gif'=>'Image, GIF',
			'image/jpeg'=>'Image, JPEG',
			'image/png'=>'Image, PNG',
			'image/tiff'=>'Image, TIFF'
		);

		if(array_key_exists($dcformat,$codes)){
			return $codes[$dcformat] . ' ['.$dcformat.']';
		}
		
		return $dcformat;
	}


	/**
	 * Build download URL and link type for a resource. Single source for catalog vs API link building.
	 *
	 * @param array  $resource  Resource row (survey_id, resource_id, filename; optional dataset_idno for admin path)
	 * @param string $link_type page | api | admin_api
	 * @return array ['url' => string, 'type' => 'link'|'download']
	 */
	private function _build_resource_download_link($resource, $link_type = 'page')
	{
		if (!isset($this->form_validation)) {
			$this->load->library('form_validation');
		}
		if (empty($resource['filename'])) {
			return array('url' => '', 'type' => 'download');
		}
		if ($this->form_validation->valid_url($resource['filename'])) {
			return array('url' => $resource['filename'], 'type' => 'link');
		}
		if ($link_type === 'api') {
			$url = site_url('api/resources/download/' . $resource['survey_id'] . '/' . $resource['resource_id'] . '?file_name=' . rawurlencode($resource['filename']) . '&id_format=id');
		} elseif ($link_type === 'admin_api') {
			$study_seg = '';
			$query = '';
			if (!empty($resource['dataset_idno'])) {
				$study_seg = rawurlencode((string) $resource['dataset_idno']);
			} else {
				$study_seg = (string) (int) $resource['survey_id'];
				$query = '?id_format=id';
			}
			$url = site_url(
				'api/admin/resources/' . $study_seg . '/resources/download/' . (int) $resource['resource_id'] . $query
			);
		} else {
			$url = site_url('catalog/' . $resource['survey_id'] . '/download/' . $resource['resource_id'] . '/' . rawurlencode($resource['filename']));
		}
		return array('url' => $url, 'type' => 'download');
	}

	/**
	 * 
	 * Get resources downloadable links by Study IDNO
	 * 
	 */
	function get_download_links($idno_arr)
	{
		if (!is_array($idno_arr)){
			throw new Exception("idno_arr is is not an array");
		}

		//default fields
		$fields="resource_id,survey_id,filename,surveys.idno";

		foreach (array_chunk($idno_arr, 100, true) as $chunk) {
			$this->db->select($fields);
			$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
			$this->db->where_in("surveys.idno",$chunk);
			$resources=$this->db->get('resources')->result_array();
			
			if ($resources){
				foreach ($resources as $resource) {
					$link_info = $this->_build_resource_download_link($resource, 'page');
					yield array(
						'idno' => $resource['idno'],
						'link' => $link_info['url']
					);
				}
			}
		}
	}


	/**
	 * 
	 * Get resources by studies
	 * 
	 * @additional_fields = array of additional fields to include
	 * 
	 */
	function get_resources_by_studies($idno_arr,$additional_fields=null)
	{
		if (!is_array($idno_arr)){
			throw new Exception("idno_arr is is not an array");
		}

		//default fields
		$fields="resource_id,survey_id,filename,surveys.idno";

		if(is_array($additional_fields)){
			$fields=$fields . ',' . implode(",",$additional_fields);
		}

		foreach (array_chunk($idno_arr, 100, true) as $chunk) {
			$this->db->select($fields);
			$this->db->join('surveys', 'surveys.id= resources.survey_id','inner');
			$this->db->where_in("surveys.idno",$chunk);
			$resources=$this->db->get('resources')->result_array();
			
			if ($resources){
				foreach ($resources as $resource) {
					$link_info = $this->_build_resource_download_link($resource, 'page');
					$resource['link'] = $link_info['url'];
					$resource['ext'] = strtolower(pathinfo($resource['filename'], PATHINFO_EXTENSION));
					yield $resource;
				}
			}
		}
	}


	/**
	 * 
	 * Format resources
	 * 
	 * add download link + file sizes
	 * 
	 */
	function format_resources($resources)
	{
		if (empty($resources)){
			return false;
		}

		$output = array();
		foreach ($resources as $resource) {
			$link_info = $this->_build_resource_download_link($resource, 'page');
			$resource['link'] = $link_info['url'];
			$resource['is_url'] = ($link_info['type'] === 'link');
			if (!$resource['is_url']) {
				$resource['filesize'] = $this->get_resource_filesize($resource);
			}
			$resource['ext'] = strtolower(pathinfo($resource['filename'], PATHINFO_EXTENSION));
			$output[] = $resource;
		}
		return $output;
	}

	function resource_attach_zip_preview($survey_folder,$resources)
	{	
		foreach($resources as $key=>$resource){
			$filepath=unix_path($survey_folder.'/'.$resource['filename']);
			$resources[$key]['zip_preview']=$this->get_zip_archive_info($filepath);
		}

		return $resources;
	}

	
	function get_zip_archive_info($filepath)
	{
		$zip_content=get_zip_archive_list($filepath);

		if (!$zip_content){
			return false;
		}

		$output=[];

		//convert to a nested array
		foreach($zip_content as $key=>$value){

			//remove last slash
			if (substr($key, -1) == '/'){				
				$key=substr($key, 0, -1);
			}
			set_array_nested_value($output, $parents=$key, $value, $glue = '/');
		}

		return $output;
	}


	function get_resource_filesize($resource)
	{
		if (!$resource){
			return FALSE;
		}
		
		$survey_folder=$this->Catalog_model->get_survey_path_full($resource['survey_id']);						
		$file_path=unix_path($survey_folder.'/'.$resource['filename']);

		if (file_exists($file_path)){
			return filesize($file_path);
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Add download links for resources
	 * 
	 */
	function generate_download_link($resources)
	{
		foreach ($resources as $idx => $resource) {
			$link_info = $this->_build_resource_download_link($resource, 'page');
			if ($link_info['url'] !== '') {
				$resources[$idx]['_links'] = array(
					'download' => $link_info['url'],
					'type' => $link_info['type']
				);
			}
		}
		return $resources;
	}

	/**
	 * Add download links for resources (API URL format).
	 *
	 * @param array $resources
	 * @param bool  $use_admin_api When true, use api/admin/resources/.../download/... (catalog admin); when false, api/resources/download/... (datasets API).
	 */
	function generate_api_download_link($resources, $use_admin_api = false)
	{
		$link_type = $use_admin_api ? 'admin_api' : 'api';
		foreach ($resources as $idx => $resource) {
			$link_info = $this->_build_resource_download_link($resource, $link_type);
			if ($link_info['url'] !== '') {
				$resources[$idx]['_links'] = array(
					'download' => $link_info['url'],
					'type' => $link_info['type']
				);
			}
		}
		return $resources;
	}



	/**
	* searche database
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL)
    {
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		$this->db->start_cache();		
		
		//select survey fields
		$this->db->select('*');
		
		//build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	//$this->db->or_like($field,$where['keywords']);
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND survey_id='.$this->surveyid;
		}
		else
		{
			$where_clause='survey_id='.$this->db->escape($this->surveyid);
		}
		
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause, NULL, FALSE);

		//set order by
		if ($sort_by!='' && $sort_order!=''){
			$this->db->order_by($sort_by, $sort_order); 
		}
		
	  	$this->db->limit($limit, $offset);
		$this->db->from('resources');
		$this->db->stop_cache();

        $result= $this->db->get()->result_array();		
		return $result;
    }
	
	//builds where clause using the variables from GET
	function _build_search_query()
	{		
		$fields=$this->input->get("field");
		$keywords=$this->input->get("keywords");
		
		$allowed_fields=$this->allowed_fields;
		
		if ($keywords=='')
		{
			return NULL;
		}
		
		if ($fields=='')
		{
			return NULL;
		}
		else if ($fields=='all')
		{			
			$where['field']=$allowed_fields;
			$where['keywords']=$keywords;
			
			return $where;
		}
		else if (in_array($fields, $allowed_fields) )
		{
			$where['field']=array($fields);
			$where['keywords']=$keywords;
			
			return $where;
		}
		
		return NULL;
	}

	//returns the search result count  	
    function search_count()
    {
        //build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	//$this->db->or_like($field,$where['keywords']);
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND survey_id='.$this->surveyid;
		}
		else
		{
			$where_clause='survey_id='.$this->db->escape($this->surveyid);
		}
		//print $where_clause;
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause,NULL,FALSE);
		$result=$this->db->count_all_results('resources');
		return $result;
    }



	/**
	 * 
	 * Return an associated array using filename as the key
	 * 
	 * 
	 */
	function get_survey_resources_group_by_filename($sid)
	{
		$resources=$this->get_survey_resources($sid);
		$output=array();
		
		foreach($resources as $resource)
		{
			$output[$resource['filename']]=$resource;
		}

		return $output;
	}

	/**
	 * Get resources by resource type
	 * 
	 * @param int $survey_id
	 * @param string $resource_type
	 * @return array
	 */
	function get_resources_by_resource_type($survey_id, $resource_type)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $survey_id);
		$this->db->where('resource_type', $resource_type);
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('title', 'ASC');
		return $this->db->get('resources')->result_array();
	}


	/**
	 * Check if resource_idno exists for survey
	 * 
	 * @param int $survey_id
	 * @param string $resource_idno
	 * @param int $exclude_resource_id (optional)
	 * @return bool
	 */
	function resource_idno_exists($survey_id, $resource_idno, $exclude_resource_id = null)
	{
		$this->db->select('resource_id');
		$this->db->where('survey_id', $survey_id);
		$this->db->where('resource_idno', $resource_idno);
		
		if ($exclude_resource_id) {
			$this->db->where('resource_id !=', $exclude_resource_id);
		}
		
		$result = $this->db->get('resources')->row_array();
		return !empty($result);
	}

	/**
	 * 
	 * Get resource by resource_idno
	 * 
	 * @param int $survey_id
	 * @param string $resource_idno
	 * @return array|false Resource data or false if not found
	 */
	function get_resource_by_idno($survey_id, $resource_idno)
	{
		$this->db->where('survey_id', $survey_id);
		$this->db->where('resource_idno', $resource_idno);
		
		$result = $this->db->get('resources')->row_array();
		return $result ? $result : false;
	}

	/**
	 * Generate unique resource_idno from filename
	 * 
	 * @param int $survey_id
	 * @param string $filename
	 * @return string
	 */
	function generate_resource_idno($survey_id, $filename=null)
	{
		$resource_idno = null;
		if (!empty($filename) && !$this->form_validation->valid_url($filename)){
			// Get just the filename (remove path)
			$base_name = basename($filename);
			
			// Convert to lowercase
			$base_name = strtolower($base_name);
			
			// Replace underscores, dots, and spaces with hyphens
			$base_name = str_replace(array('_', '.', ' '), '-', $base_name);
			
			// Replace any other non-alphanumeric characters (except hyphens) with hyphens
			$base_name = preg_replace('/[^a-z0-9-]/', '-', $base_name);
			
			// Clean up multiple consecutive hyphens
			$base_name = preg_replace('/-+/', '-', $base_name);
			
			// Trim hyphens from start and end
			$base_name = trim($base_name, '-');
			
			// Limit length
			$base_name = substr($base_name, 0, 100);
			
			$resource_idno = $base_name;
			$counter = 1;
			
			// Ensure uniqueness
			while ($this->resource_idno_exists($survey_id, $resource_idno)) {
				$resource_idno = $base_name . '-' . $counter;
				$counter++;
			}
		}

		if (!$this->validate_idno_format($resource_idno)){
			//generate a random UUID-style slug
			$resource_idno = nada_random_slug(6, 4); // e.g., "a3f8b2-9d4e1c-7b2a5f-3e9d1b"
		}
		
		return $resource_idno;
	}


	/**
	 * 
	 *  Validate IDNO format
	 * 
	 *  - MUST be URL friendly format
	 *	- Alphanumeric
	 *	- Underscore or hyphen
	 *	- Less than 100 characters
	 *	- No special characters allowed
	 * 
	 */
	function validate_idno_format($idno)
	{
		if (strlen($idno) > 100) {
			return false;
		}

		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $idno)) {
			return false;
		}

		return true;
	}

	/**
	 * 
	 * Validation callback for resource_idno format (for form_validation library)
	 * 
	 */
	public function validate_resource_idno_format($resource_idno)
	{
		if (!$this->validate_idno_format($resource_idno)) {
			$this->form_validation->set_message(__FUNCTION__, 'The {field} can only contain letters, numbers, hyphens, and underscores.');
			return false;
		}
		return true;
	}

	/**
	 * 
	 * Validation callback for resource_idno uniqueness (for form_validation library)
	 * 
	 */
	public function validate_resource_idno_unique($resource_idno)
	{
		// Get survey_id and resource_id from validation data
		$survey_id = null;
		$resource_id = null;
		
		if (array_key_exists('survey_id', $this->form_validation->validation_data)) {
			$survey_id = $this->form_validation->validation_data['survey_id'];
		}
		
		if (array_key_exists('resource_id', $this->form_validation->validation_data)) {
			$resource_id = $this->form_validation->validation_data['resource_id'];
		}
		
		// Check uniqueness
		$exclude_id = is_numeric($resource_id) ? $resource_id : null;
		
		if ($this->resource_idno_exists($survey_id, $resource_idno, $exclude_id)) {
			$this->form_validation->set_message(__FUNCTION__, 'The {field} already exists for this study. Please use a different identifier.');
			return false;
		}
		
		return true;
	}


	/**
	 * Build update_data and changes for a single file resource (not URL). Shared by sync_all_resources and sync_resource.
	 *
	 * @param array  $resource           Resource row
	 * @param string $survey_folder      Full path to survey folder
	 * @param bool   $calculate_checksum Whether to compute SHA256 checksum
	 * @return array ['not_found' => true] | ['error' => string] | ['update_data' => array, 'changes' => array]
	 */
	private function _build_sync_update_for_file_resource($resource, $survey_folder, $calculate_checksum)
	{
		$filename = $resource['filename'];
		$file_path = unix_path($survey_folder . '/' . $filename);

		if (!file_exists($file_path) || !is_file($file_path)) {
			return array('not_found' => true);
		}

		$file_metadata = $this->get_file_metadata($resource['survey_id'], $filename);
		if (!$file_metadata) {
			return array('error' => 'Could not read file metadata');
		}

		$update_data = array('changed' => time());
		$changes = array();

		if (isset($resource['is_url']) && $resource['is_url'] != 0) {
			$update_data['is_url'] = 0;
			$changes[] = 'is_url: 1 → 0';
		}

		if (isset($resource['filesize']) && $resource['filesize'] != $file_metadata['filesize']) {
			$changes[] = 'filesize: ' . format_bytes($resource['filesize']) . ' → ' . format_bytes($file_metadata['filesize']);
		}
		$update_data['filesize'] = $file_metadata['filesize'];

		if (isset($resource['dcformat']) && $resource['dcformat'] != $file_metadata['mime_type']) {
			$changes[] = 'dcformat: ' . ($resource['dcformat'] ?: 'NULL') . ' → ' . $file_metadata['mime_type'];
		}
		$update_data['dcformat'] = $file_metadata['mime_type'];

		if ($calculate_checksum) {
			$checksum = @hash_file('sha256', $file_path);
			if ($checksum && (empty($resource['checksum']) || $resource['checksum'] != $checksum)) {
				$update_data['checksum'] = $checksum;
				$changes[] = !empty($resource['checksum']) ? 'checksum: updated' : 'checksum: calculated';
			}
		}

		if (!empty($resource['dctype'])) {
			$new_resource_type = $this->get_resource_type_from_dctype($resource['dctype']);
			$current_type = isset($resource['resource_type']) ? $resource['resource_type'] : null;
			if ($current_type != $new_resource_type) {
				$update_data['resource_type'] = $new_resource_type;
				$changes[] = 'resource_type: ' . ($current_type ?: 'NULL') . ' → ' . $new_resource_type;
			}
		}

		return array('update_data' => $update_data, 'changes' => $changes);
	}

	/**
	 * 
	 * Sync all resources metadata for a survey with actual files on disk
	 * 
	 * This method:
	 * - Gets all resources for the survey
	 * - Always rechecks and updates is_url field (URL vs local file)
	 * - For file resources (not URLs), checks if file exists
	 * - Always updates filesize, dcformat from actual file
	 * - Always recomputes resource_type from dctype
	 * - Optionally calculates checksum (can be slow for large files)
	 * - Returns summary of sync operations
	 * 
	 * @param int $survey_id - Survey ID
	 * @param bool $calculate_checksum - Calculate SHA256 checksums (default: false, can be slow)
	 * @return array - Summary of sync operations
	 */
	public function sync_all_resources($survey_id, $calculate_checksum = false)
	{
		if (!is_numeric($survey_id)) {
			throw new Exception('Invalid survey ID');
		}

		// Get all resources for this survey
		$resources = $this->get_survey_resources($survey_id);
		
		// Get survey folder path
		$survey_folder = $this->Catalog_model->get_survey_path_full($survey_id);
		
		if (!$survey_folder || !file_exists($survey_folder)) {
			throw new Exception('Survey folder not found: ' . $survey_folder);
		}

		// Summary counters
		$summary = array(
			'total' => count($resources),
			'urls' => 0,
			'synced' => 0,
			'not_found' => 0,
			'errors' => 0,
			'skipped' => 0,
			'details' => array()
		);

		foreach ($resources as $resource) {
			$resource_id = $resource['resource_id'];
			$filename = $resource['filename'];

			try {
				// Always check if filename is URL and update is_url field
				$is_url = $this->form_validation->valid_url($filename) ? 1 : 0;
				
				// Update is_url if it changed
				if ($resource['is_url'] != $is_url) {
					$this->db->where('resource_id', $resource_id);
					$this->db->update('resources', array(
						'is_url' => $is_url,
						'changed' => time()
					));
					log_message('info', "Resource #{$resource_id}: is_url updated to {$is_url}");
				}
				
				// Skip URLs - nothing to sync with disk
				if ($is_url == 1) {
					$summary['urls']++;
					$summary['details'][] = array(
						'resource_id' => $resource_id,
						'filename' => $filename,
						'status' => 'skipped',
						'reason' => 'URL resource'
					);
					continue;
				}

				$sync_result = $this->_build_sync_update_for_file_resource($resource, $survey_folder, $calculate_checksum);

				if (!empty($sync_result['not_found'])) {
					$summary['not_found']++;
					$summary['details'][] = array(
						'resource_id' => $resource_id,
						'filename' => $filename,
						'status' => 'not_found',
						'reason' => 'File not found on disk'
					);
					log_message('warning', "Resource #{$resource_id}: File not found: {$filename}");
					continue;
				}
				if (!empty($sync_result['error'])) {
					$summary['errors']++;
					$summary['details'][] = array(
						'resource_id' => $resource_id,
						'filename' => $filename,
						'status' => 'error',
						'reason' => $sync_result['error']
					);
					continue;
				}

				$update_data = $sync_result['update_data'];
				$changes = $sync_result['changes'];

				// Only update if there are changes (besides 'changed' timestamp)
				if (count($update_data) > 1) {
					// Direct update to avoid full validation
					$this->db->where('resource_id', $resource_id);
					$result = $this->db->update('resources', $update_data);

					if ($result) {
						$summary['synced']++;
						$summary['details'][] = array(
							'resource_id' => $resource_id,
							'filename' => $filename,
							'status' => 'synced',
							'changes' => $changes
						);
						
						log_message('info', "Resource #{$resource_id} synced: " . implode(', ', $changes));
					} else {
						$summary['errors']++;
						$summary['details'][] = array(
							'resource_id' => $resource_id,
							'filename' => $filename,
							'status' => 'error',
							'reason' => 'Database update failed'
						);
					}
				} else {
					$summary['skipped']++;
					$summary['details'][] = array(
						'resource_id' => $resource_id,
						'filename' => $filename,
						'status' => 'no_changes',
						'reason' => 'Metadata already up to date'
					);
				}

			} catch (Exception $e) {
				$summary['errors']++;
				$summary['details'][] = array(
					'resource_id' => $resource_id,
					'filename' => $filename,
					'status' => 'error',
					'reason' => $e->getMessage()
				);
				
				log_message('error', "Resource #{$resource_id} sync error: " . $e->getMessage());
			}
		}

		// Log summary
		log_message('info', sprintf(
			"Survey #%d resource sync complete: %d total, %d synced, %d not found, %d errors, %d URLs, %d skipped",
			$survey_id,
			$summary['total'],
			$summary['synced'],
			$summary['not_found'],
			$summary['errors'],
			$summary['urls'],
			$summary['skipped']
		));

		return $summary;
	}


	/**
	 * 
	 * Sync metadata for a single resource
	 * 
	 * Useful for refreshing metadata after a file is uploaded or replaced
	 * Always rechecks and updates is_url, filesize, dcformat, and resource_type
	 * 
	 * @param int $resource_id - Resource ID
	 * @param bool $calculate_checksum - Calculate SHA256 checksum (default: false, can be slow)
	 * @return array - Sync result
	 */
	public function sync_resource($resource_id, $calculate_checksum = false)
	{
		if (!is_numeric($resource_id)) {
			throw new Exception('Invalid resource ID');
		}

		$resource = $this->select_single($resource_id);
		
		if (!$resource) {
			throw new Exception('Resource not found');
		}

		$result = array(
			'resource_id' => $resource_id,
			'filename' => $resource['filename'],
			'status' => 'unknown',
			'changes' => array()
		);

		try {
			// Always check if filename is URL and update is_url field
			$is_url = $this->form_validation->valid_url($resource['filename']) ? 1 : 0;
			
			// Update is_url if it changed
			if ($resource['is_url'] != $is_url) {
				$this->db->where('resource_id', $resource_id);
				$this->db->update('resources', array(
					'is_url' => $is_url,
					'changed' => time()
				));
				$result['changes'][] = 'is_url: ' . $resource['is_url'] . ' → ' . $is_url;
				log_message('info', "Resource #{$resource_id}: is_url updated to {$is_url}");
			}
			
			// Skip URLs - nothing to sync with disk
			if ($is_url == 1) {
				$result['status'] = 'skipped';
				$result['reason'] = 'URL resource - nothing to sync';
				return $result;
			}

			$survey_folder = $this->Catalog_model->get_survey_path_full($resource['survey_id']);
			if (!$survey_folder) {
				$result['status'] = 'error';
				$result['reason'] = 'Survey folder not found';
				return $result;
			}

			$sync_result = $this->_build_sync_update_for_file_resource($resource, $survey_folder, $calculate_checksum);

			if (!empty($sync_result['not_found'])) {
				$result['status'] = 'not_found';
				$result['reason'] = 'File not found on disk';
				return $result;
			}
			if (!empty($sync_result['error'])) {
				$result['status'] = 'error';
				$result['reason'] = $sync_result['error'];
				return $result;
			}

			$update_data = $sync_result['update_data'];
			$changes = $sync_result['changes'];

			if (count($update_data) > 1) {
				$this->db->where('resource_id', $resource_id);
				$this->db->update('resources', $update_data);
				$result['status'] = 'synced';
				$result['changes'] = $changes;
				log_message('info', "Resource #{$resource_id} synced: " . implode(', ', $changes));
			} else {
				$result['status'] = 'no_changes';
				$result['reason'] = 'Metadata already up to date';
			}

		} catch (Exception $e) {
			$result['status'] = 'error';
			$result['reason'] = $e->getMessage();
			log_message('error', "Resource #{$resource_id} sync error: " . $e->getMessage());
		}

		return $result;
	}

}