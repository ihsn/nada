<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Model for surveys table
 * 
 */
class Dataset_model extends CI_Model {
 
	//fields for the study description
	private $survey_fields=array(
		'id',
		'type',
		'repositoryid',
		'idno',
		'title',
		'subtitle',
		'abbreviation',
		'authoring_entity',
		'nation',
		'dirpath',
		'metafile',
		'year_start',
		'year_end',
		'link_da',
		'published',
		'varcount',
		'total_views',
		'total_downloads',
		'created',
		'changed',
		'created_by',
		'changed_by',
		'data_class_id',
		'data_structure_id',
		'ts_db_id',
		'ts_dimensions',
		'ts_frequency',
		'ts_sync_required',
		'formid',
		'metadata',
		'link_study',
		'link_indicator',
		'thumbnail',
		'doi',
		'abstract'
		);
		
	
	private $listing_fields=array(
		'id',
		'type',
		'repositoryid',
		'idno',
		'title',
		'abbreviation',
		'authoring_entity',
		'nation',
		'year_start',
		'year_end',
		'link_da',
		'published',
		'created',
		'changed',
		'varcount',
		'total_views',
		'total_downloads',
		'created_by',
		'changed_by',
		'ts_db_id',
		'formid',
		'doi',
		'abstract'
		);
	

	private $encoded_fields=array(
		"metadata"
	);

 
    public function __construct()
    {
		parent::__construct();
		$this->load->library("form_validation");		
		$this->load->model("Survey_country_model");
		$this->load->model("Vocabulary_model");
		$this->load->model("Term_model");
		$this->load->model("Survey_resource_model");
		$this->load->model("Catalog_tags_model");
	}
	
	
	/**
	 * 
	 * Return all datasets
	 * 
	 * @offset - offset
	 * @limit - number of rows to return
	 * @fields - (optional) list of fields
	 * 
	 */
	function get_all($limit=0,$offset=0, $fields=array())
	{
		if (empty($fields)){
			$fields=$this->listing_fields;
		}
		
		$this->db->select(implode(",",$fields));
		$this->db->order_by('id');

		if ($limit>0){
			$this->db->limit($limit, $offset);
		}
		
		$result= $this->db->get("surveys");
		
		if ($result){
			$result=$result->result_array();
		}else{
			$error=$this->db->error();
			throw  new Exception(implode(", ", $error));
		}

		if($result){
			return $this->decode_encoded_fields_rows($result);
		}

		return false;
	}

	//returns the total 
	function get_total_count()
	{
		return $this->db->count_all('surveys');
	}


	/**
	 * 
	 * returns a list of datasets by type
	 * 
	 * 
	 */
	function get_list_by_type($dataset_type=null, $limit=100, $start=0)
	{
		$this->db->select('id,idno');
		
		if($dataset_type){
			$this->db->where('type',$dataset_type);
		}

		if(is_numeric($start)){
			$this->db->where('id>',$start);
		}

		if(!empty($limit)){
			$this->db->limit($limit);
		}

		return $this->db->get("surveys")->result_array();
	}


	/**
	 * 
	 * returns a list of datasets by type
	 * 
	 * 
	 */
	function get_list_all($dataset_type=null,$published=1)
	{
		$this->db->select('id,idno,type');
		
		if(!empty($dataset_type)){
			$this->db->where('type',$dataset_type);
		}

		if(!empty($published)){
			$this->db->where('published',$published);
		}
		
		return $this->db->get("surveys")->result_array();
	}

	


	//return IDNO
	function get_idno($sid)
	{
		$this->db->select("idno");
		$this->db->where("id",$sid);
		$result=$this->db->get("surveys")->row_array();
		
		if($result){
			return $result['idno'];
		}

        return false;
	}


	function get_id_by_idno($idno)
	{
		$this->db->select('id');
		$this->db->where('idno', $idno); 
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return $query['id'];
		}
		
		//check IDNO in survey aliases
		$this->db->select('sid');
		$this->db->where(array('alternate_id' => $idno) );
		$query=$this->db->get('survey_aliases')->result_array();

		if (!$query){
			return FALSE;
		}
		
		return $query[0]['sid'];
	}


	//return type 
	function get_type($sid)
	{
		$this->db->select("type");
		$this->db->where("id",$sid);
		$survey=$this->db->get("surveys")->row_array();
		
		if($survey){
			return $survey['type'];
		}
	}

	
	//get the survey by id
    function get_row($sid)
    {
		if (!is_numeric($sid) || is_float($sid)){
            return false;
        }

		$this->db->select("surveys.id, surveys.doi, surveys.repositoryid,surveys.type,surveys.idno,surveys.title,surveys.subtitle,surveys.year_start, 
			year_end,nation,surveys.authoring_entity,published,created, changed, varcount, total_views, total_downloads, 
			surveys.formid,forms.model as data_access_type,link_da as remote_data_url, 
			surveys.data_class_id, data_classifications.code as data_class_code, data_classifications.title as data_class_title,
			surveys.thumbnail, surveys.abstract, link_study, link_indicator, link_report");
		$this->db->join('forms','surveys.formid=forms.formid','left');
		$this->db->join('data_classifications','surveys.data_class_id=data_classifications.id','left');
		$this->db->where("surveys.id",$sid);
		
		$survey=$this->db->get("surveys")->row_array();
		
		if($survey){
			$survey=$this->decode_encoded_fields($survey);
		}

        return $survey;
	}

	//return survey with metadata and other fields
	function get_row_detailed($sid)
	{
		if (!is_numeric($sid) || is_float($sid)){
			return false;
		}

		$this->db->select("surveys.*, 
			forms.model as data_access_type, 
			surveys.data_class_id, 
			data_classifications.code as data_class_code, 
			data_classifications.title as data_class_title");
		$this->db->join('forms','surveys.formid=forms.formid','left');
		$this->db->join('data_classifications','surveys.data_class_id=data_classifications.id','left');
        $this->db->where("surveys.id",$sid);
        $data=$this->db->get("surveys")->row_array();
		$data=$this->decode_encoded_fields($data);
		
		return $data;		
	}

	//decode all encoded fields
	function decode_encoded_fields($data)
	{
		if(!$data){
			return $data;
		}

		foreach($data as $key=>$value){
			if(in_array($key,$this->encoded_fields)){
				$data[$key]=$this->decode_metadata($value);
			}
		}
		return $data;
	}

	//decode multiple rows
	function decode_encoded_fields_rows($data)
	{
		$result=array();
		foreach($data as $row){
			$result[]=$this->decode_encoded_fields($row);
		}
		return $result;
	}


    //returns survey metadata array
    function get_metadata($sid)
    {
        $this->db->select("type,metadata");
        $this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();

        if ($survey){
            $metadata= $this->decode_metadata($survey['metadata']);
			$metadata['schematype']=$survey['type'];

			//tags
			$tags=$this->Catalog_tags_model->survey_tags_with_key($sid);

			//other identifiers e.g. DOI
			$this->add_identifiers_to_metadata($sid, $survey['type'], $metadata);

			if($tags){
				$metadata['tags']=$tags;
			}

			return $metadata;
        }
	}


	/**
	 * 
	 * Add identifiers to metadata 
	 * 	- doi
	 *  - aliases
	 *  - other identifiers
	 */
	function add_identifiers_to_metadata($sid, $type, &$metadata)
	{
		$mappings=[
			'survey'=>'study_desc/title_statement/identifiers',
			'script'=>'project_desc/title_statement/identifiers'
		];

		$doi=$this->get_doi($sid);

		if (!$doi){
			return;
		}

		if ($type=='survey'){

			$identifiers=get_array_nested_value($metadata,$mappings[$type],"/");

			if (!$identifiers){
				return;
			}

			$doi_identifier=[
				'type'=>'doi',
				'identifier'=>$doi
			];

			if (!is_array($identifiers)){
				set_array_nested_value($metadata,$mappings[$type],array($doi_identifier),"/");
				return;
			}

			// Keep a single DOI entry (case-insensitive type; schema uses lowercase `doi`).
			// Also collapses doi/DOI duplicates already persisted in metadata.
			$normalized=array();
			$has_doi=false;

			foreach($identifiers as $identifier){
				if (isset($identifier['type'], $identifier['identifier'])
					&& strtolower($identifier['type'])=='doi'
					&& $identifier['identifier']==$doi){
					if (!$has_doi){
						$normalized[]=$doi_identifier;
						$has_doi=true;
					}
					continue;
				}

				$normalized[]=$identifier;
			}

			if (!$has_doi){
				$normalized[]=$doi_identifier;
			}

			set_array_nested_value($metadata,$mappings[$type],$normalized,"/");
		}
	}


	/**
	 * 
	 * 
	 * Return DOI from metadata datacite element if exists
	 * 
	 */
	function get_datacite_doi($options)
	{
		$doi=(string)$this->get_array_nested_value($options,'datacite/doi');

		if(!empty($doi)){
			return $doi;
		}
	}


	function get_doi($sid)
	{
		$this->db->select("doi");
		$this->db->where("id",$sid);
		$survey=$this->db->get("surveys")->row_array();

		if ($survey){
			return $survey['doi'];
		}
	}


	/**
	 * Plain-text abstract stored on surveys.abstract (no metadata decode).
	 *
	 * @param int $sid
	 * @return string|null
	 */
	function get_study_abstract($sid)
	{
		if (!is_numeric($sid) || is_float($sid)) {
			return null;
		}

		$this->db->select('abstract');
		$this->db->where('id', (int) $sid);
		$row = $this->db->get('surveys')->row_array();

		if (!$row || !isset($row['abstract'])) {
			return null;
		}

		$abstract = trim(strip_tags((string) $row['abstract']));

		return $abstract !== '' ? $abstract : null;
	}

	/**
	 * 
	 * Return survey keywords
	 * 
	 */
	function get_keywords($sid)
	{
		$this->db->select("keywords");
		$this->db->where("id",$sid);
		return $this->db->get("surveys")->row_array();
	}

	
	public function set_metadata($sid, $metadata)
	{
		return $this->update_options($sid, array('metadata'=>$metadata));
	}	
	
	
	
	function validate_schema($type,$data)
	{
		$schema_file="application/schemas/$type-schema.json";

		if(!file_exists($schema_file)){
			throw new Exception("INVALID-DATASET-TYPE-NO-SCHEMA-DEFINED");
		}

		// Validate
		$validator = new JsonSchema\Validator;
		$validator->validate($data, 
				(object)['$ref' => 'file://' . unix_path(realpath($schema_file))],
				Constraint::CHECK_MODE_TYPE_CAST 
				+ Constraint::CHECK_MODE_COERCE_TYPES 
				+ Constraint::CHECK_MODE_APPLY_DEFAULTS
			);

		if ($validator->isValid()) {
			return true;
		} else {			
			/*foreach ($validator->getErrors() as $error) {
				echo sprintf("[%s] %s\n", $error['property'], $error['message']);
			}*/
			throw new ValidationException("SCHEMA_VALIDATION_FAILED [{$type}]: ", $validator->getErrors());
		}
	}



	/**
	 * 	 
	 * Convert an array column to string
	 * 
	 **/
	function array_column_to_string($data,$column_name='name', $max_length=300)
	{
		if(!is_array($data)){
			return '';
		}

		$column_data=array_column($data, $column_name);
		$column_data=implode(", ", $column_data);

		if (strlen($column_data) <=$max_length) {
			return $column_data;
		}

		//trim to max limit
		$wrapped=wordwrap($column_data, $max_length);
		$wrapped=explode("\n",$wrapped);
		return $wrapped[0];
	}


	function get_array_nested_value($data, $path, $glue = '/')
    {
        $paths = explode($glue, (string) $path);
        $reference = $data;
        foreach ($paths as $key) {
            if (!array_key_exists($key, $reference)) {
                return null;
            }
            $reference = $reference[$key];
        }
        return $reference;
	}
	

	function unset_array_nested_value(&$data, $path, $glue = '/')
    {
        $paths = explode($glue, (string) $path);
        $reference = &$data;
        foreach ($paths as $key) {
            if (!array_key_exists($key, $reference)) {
                return false;
            }
            $reference = &$reference[$key];
        }
		unset($reference);
		return true;
    }


	/**
	*
	* insert new dataset and return new dataset id
	*
	* @options - array()
	*/
	function insert($type,$options)	
	{
		$options['type']=$type;

		//transform fields to map to core and metadata columns
		//$options=$this->map_fields($type,$options);
						
		$data=array();

		//default values, if no values are passed in $options
		$data['created']=date("U");
		$data['changed']=date("U");

		foreach($options as $key=>$value){
			if (in_array($key,$this->survey_fields) ){
				$data[$key]=$value;
			}
		}

		//abbreviation
		if (isset($data['abbreviation'])){
			$data['abbreviation']=substr($data['abbreviation'],0,40);
		}

		//keywords
		if (isset($data['metadata']) && !isset($data['keywords'])){
			//$keywords=str_replace("\n","",$this->array_to_plain_text($options['metadata']));
			$data['keywords']=$this->extract_keywords($data['metadata'],$type);
		}

		//abstract
		if (isset($data['metadata']) && !isset($data['abstract'])){
			$data['abstract'] = $this->extract_abstract($data['metadata'], $type);
		}

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}		

		//create new study
		$result=$this->db->insert('surveys', $data); 

		if ($result===false){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));			
		}
		
		//newly created dataset id
		$id= $this->db->insert_id();

		$this->update_options($id,$options_=array('repositoryid'=>$options['repositoryid']));

		return $id;
	}


	/**
	*
	* insert new dataset and return new dataset id
	*
	* @options - array()
	*/
	function update($sid,$type,$options)	
	{
		$options['type']=$type;
						
		$data=array();

		//default values, if no values are passed in $options
		$data['changed']=date("U");

		if (isset($options['created'])){
			unset($options['created']);
		}

		foreach($options as $key=>$value){
			if (in_array($key,$this->survey_fields) ){
				$data[$key]=$value;
			}
		}

		//abbreviation
		if (isset($data['abbreviation'])){
			$data['abbreviation']=substr($data['abbreviation'],0,40);
		}

		//keywords
		if (!isset($data['keywords']) && isset($data['metadata'])){
			$data['keywords']=$this->extract_keywords($data['metadata'],$type);
		}

		//abstract
		if (!isset($data['abstract']) && isset($data['metadata'])){
			$data['abstract'] = $this->extract_abstract($data['metadata'], $type);
		}

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}
		
		if (isset($data['id'])){
			unset($data['id']);
		}
		
		//update study
		$this->db->where('id',$sid);
		$result=$this->db->update('surveys', $data); 

		if ($result===false){
			$error=$this->db->error('message');
			throw new Exception("DB-ERROR: ".$error['message']);
		}

		$this->update_options($sid,$options_=array('repositoryid'=>$options['repositoryid']));

		return $sid;
	}

	function extract_keywords($metadata,$type='')
	{		
		if($type=='survey'){
			$type='microdata';
		}

		//exclude
		if($type=='document'){
			if(isset($metadata['document_description']['lda_topics'])){
				unset($metadata['document_description']['lda_topics']);
			}
		}
		
		$keywords=$type. ' '.str_replace(array("\n","\r")," ",$this->array_to_plain_text($metadata));

		$noise_words=explode(",",
			'about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,
			being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,
			got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,like,
			make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,
			our,out,over,said,same,see,should,since,some,still,such,take,than,that,
			the,their,them,then,there,these,they,this,those,through,to,too,under,up,
			very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,
			b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_'
		);
		$noise_words=array_map('trim',$noise_words);

		$keywords= preg_replace('/\b('.implode('|',$noise_words).')\b/i','',$keywords);

		if(isset($this->db->prefix_short_words) && $this->db->prefix_short_words==true){
			//words with length = 3
			$pattern='/\b\w{3}\b/';
			//add underscore as a prefix
			$keywords= preg_replace($pattern, '_${0}', $keywords);
		}
		
		return $keywords;
	}


	/**
	 * Build the var_keywords string 
	 *
	 * Fetches up to $max_vars variable labels from the variables table, removes
	 * noise words (loaded from config/noise_words.php), strips standalone numbers
	 * and very short tokens, deduplicates at the word level, and returns a
	 * space-separated string capped at $max_bytes bytes.
	 *
	 * @param  int $sid       Survey / dataset ID
	 * @param  int $max_vars  Maximum number of variable labels to process (default 3000)
	 * @param  int $max_bytes Hard ceiling on the returned string length (default 65535)
	 * @return string
	 */
	public function extract_var_keywords($sid, $max_vars = 3000, $max_bytes = 65535)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return '';
		}

		// Load noise words from config
		$this->load->config('noise_words', true);
		$noise_words = $this->config->item('noise_words', 'noise_words');
		if (!is_array($noise_words) || empty($noise_words)) {
			$noise_words = [];
		}
		// Build a case-insensitive regex alternation of exact whole words
		$noise_pattern = !empty($noise_words)
			? '/\b(' . implode('|', array_map('preg_quote', $noise_words)) . ')\b/iu'
			: null;

		// Fetch labels only — flat column, no JSON decode required
		$this->db->select('labl');
		$this->db->where('sid', $sid);
		$this->db->where('labl !=', '');
		$this->db->order_by('uid', 'ASC');
		$this->db->limit($max_vars);
		$rows = $this->db->get('variables')->result_array();

		if (empty($rows)) {
			return '';
		}

		$global_words = [];

		foreach ($rows as $row) {
			$label = trim($row['labl']);
			if ($label === '') {
				continue;
			}

			// Normalise whitespace and remove punctuation noise
			$label = str_replace(["\n", "\r", "(", ")", "?", ",", "/", "\\", "-", "_"], ' ', $label);
			$label = strtolower($label);

			// Remove noise words
			if ($noise_pattern !== null) {
				$label = preg_replace($noise_pattern, ' ', $label);
			}

			// Remove standalone numbers and tokens shorter than 3 characters
			$label = preg_replace('/\b\d+\b/', ' ', $label);
			$label = preg_replace('/\b\w{1,2}\b/u', ' ', $label);

			// Collect unique words from this label into the global set
			$words = array_filter(explode(' ', $label));
			foreach ($words as $word) {
				$word = trim($word);
				if ($word !== '') {
					$global_words[$word] = true;
				}
			}
		}

		if (empty($global_words)) {
			return '';
		}

		$output = implode(' ', array_keys($global_words));

		// Apply hard byte ceiling
		if (strlen($output) > $max_bytes) {
			$output = mb_substr($output, 0, $max_bytes);
			// Trim to last complete word
			$output = substr($output, 0, (int) strrpos($output, ' '));
		}

		return $output;
	}


	function extract_abstract($metadata, $type = '', $max_len = 500)
	{
		$abstract = $this->get_metadata_abstract_text($metadata, $type);
		if ($abstract === null) {
			return null;
		}

		$max_len = (int) $max_len;
		if ($max_len <= 0) {
			return $abstract;
		}

		return mb_strlen($abstract) > $max_len
			? mb_substr($abstract, 0, $max_len)
			: $abstract;
	}


	/**
	 * Plain-text description for schema.org Dataset JSON-LD (50–5000 characters).
	 *
	 * @param array  $metadata
	 * @param string $type
	 * @return string|null
	 */
	function get_schema_org_description($metadata, $type = '')
	{
		$max_len = 5000;
		$min_len = 50;

		if ($type === 'geospatial') {
			$text = $this->get_metadata_abstract_text($metadata, $type);
			if ($text === null) {
				return null;
			}

			$text = $this->truncate_schema_org_description($text, $max_len);
			return mb_strlen($text) >= $min_len ? $text : null;
		}

		$candidate_paths = $this->get_schema_org_description_paths($type);
		foreach ($candidate_paths as $path) {
			$value = $this->get_array_nested_value($metadata, $path, '/');
			$text = $this->normalize_schema_org_text($value);
			if ($text === '') {
				continue;
			}

			$text = $this->truncate_schema_org_description($text, $max_len);
			if (mb_strlen($text) >= $min_len) {
				return $text;
			}
		}

		return null;
	}


	/**
	 * @param string $type
	 * @return array<int, string>
	 */
	private function get_schema_org_description_paths($type = '')
	{
		$survey_paths = array(
			'study_desc/study_info/abstract',
			'study_desc/series_statement/series_info',
			'study_desc/study_info/notes',
		);

		$paths_by_type = array(
			'survey'        => $survey_paths,
			'microdata'     => $survey_paths,
			'document'      => array(
				'document_description/abstract',
				'document_description/description',
				'document_description/scope',
				'document_description/notes',
			),
			'script'        => array(
				'project_desc/abstract',
			),
			'timeseriesdb'  => array(
				'database_description/abstract',
			),
			'timeseries-db' => array(
				'database_description/abstract',
			),
			'table'         => array(
				'table_description/description',
				'table_description/notes',
			),
			'timeseries'    => array(
				'series_description/definition_short',
				'series_description/definition_long',
			),
			'video'         => array(
				'video_description/description',
			),
			'image'         => array(
				'image_description/dcmi/description',
				'image_description/dcmi/caption',
				'image_description/iptc/photoVideoMetadataIPTC/description',
				'image_description/iptc/photoVideoMetadataIPTC/headline',
			),
		);

		return isset($paths_by_type[$type]) ? $paths_by_type[$type] : array();
	}


	/**
	 * @param array|string|null $metadata
	 * @param string            $type
	 * @return string|null
	 */
	private function get_metadata_abstract_text($metadata, $type = '')
	{
		if ($type === 'geospatial') {
			$ident = $this->get_array_nested_value($metadata, 'description/identificationInfo', '/');
			if (is_array($ident)) {
				$first = reset($ident);
				$value = is_array($first) ? ($first['abstract'] ?? null) : null;
			} else {
				$value = null;
			}

			$text = $this->normalize_schema_org_text($value);
			return $text !== '' ? $text : null;
		}

		$paths = $this->get_schema_org_description_paths($type);
		if (empty($paths)) {
			return null;
		}

		foreach ($paths as $path) {
			$value = $this->get_array_nested_value($metadata, $path, '/');
			$text = $this->normalize_schema_org_text($value);
			if ($text !== '') {
				return $text;
			}
		}

		return null;
	}


	/**
	 * @param mixed $text
	 * @return string
	 */
	private function normalize_schema_org_text($text)
	{
		if (is_array($text)) {
			$text = $this->flatten_schema_org_text_array($text);
		}

		if (!is_string($text)) {
			return '';
		}

		$text = trim(strip_tags($text));
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim($text);
	}


	/**
	 * @param array $values
	 * @return string
	 */
	private function flatten_schema_org_text_array(array $values)
	{
		$parts = array();

		foreach ($values as $value) {
			if (is_array($value)) {
				foreach (array('note', 'text', 'description') as $key) {
					if (!empty($value[$key]) && is_string($value[$key])) {
						$parts[] = trim($value[$key]);
						break;
					}
				}
				continue;
			}

			if (is_string($value) && trim($value) !== '') {
				$parts[] = trim($value);
			}
		}

		if (!empty($parts)) {
			return implode("\n\n", $parts);
		}

		$scalar = array_filter($values, function ($value) {
			return is_scalar($value) && $value !== null && $value !== '';
		});

		return implode(', ', $scalar);
	}


	/**
	 * @param string $text
	 * @param int    $max_len
	 * @return string
	 */
	private function truncate_schema_org_description($text, $max_len = 5000)
	{
		$max_len = (int) $max_len;
		if ($max_len <= 0 || mb_strlen($text) <= $max_len) {
			return $text;
		}

		$cut = mb_substr($text, 0, $max_len - 1);
		$last_period = mb_strrpos($cut, '.');
		if ($last_period !== false && $last_period > (int) ($max_len * 0.7)) {
			return mb_substr($cut, 0, $last_period + 1);
		}

		$last_space = mb_strrpos($cut, ' ');
		if ($last_space !== false) {
			$cut = mb_substr($cut, 0, $last_space);
		}

		return rtrim($cut, '.,;') . '…';
	}


	/**
	 * Study types that represent tabular/statistical datasets for schema.org Dataset JSON-LD.
	 *
	 * @return array<int, string>
	 */
	function schema_org_dataset_types()
	{
		return array(
			'survey',
			'microdata',
			'timeseries',
			'timeseriesdb',
			'timeseries-db',
			'table',
			'geospatial',
			'script',
		);
	}


	/**
	 * All catalog study types that emit schema.org JSON-LD.
	 *
	 * @return array<int, string>
	 */
	function schema_org_supported_types()
	{
		return array(
			'survey',
			'microdata',
			'timeseries',
			'timeseriesdb',
			'timeseries-db',
			'table',
			'geospatial',
			'script',
			'document',
			'image',
			'video',
		);
	}


	/**
	 * @param string $type
	 * @return bool
	 */
	function is_schema_org_dataset_type($type = '')
	{
		return in_array($type, $this->schema_org_dataset_types(), true);
	}


	/**
	 * Build schema.org JSON-LD for public catalog study pages.
	 *
	 * @param array $survey Row from get_row() plus metadata and optional schema_org_description
	 * @return array|null
	 */
	function build_schema_org_json_ld(array $survey)
	{
		$type = isset($survey['type']) ? (string) $survey['type'] : '';
		if (!in_array($type, $this->schema_org_supported_types(), true)) {
			return null;
		}

		$metadata = isset($survey['metadata']) && is_array($survey['metadata']) ? $survey['metadata'] : array();

		$json_ld = array(
			'@context' => 'https://schema.org/',
			'@type'    => $this->get_schema_org_json_ld_type($type, $metadata),
			'url'      => site_url('catalog/' . (isset($survey['id']) ? $survey['id'] : '')),
		);

		$this->apply_schema_org_common_fields($json_ld, $survey, $metadata, $type);

		if ($this->is_schema_org_dataset_type($type)) {
			$this->apply_schema_org_dataset_fields($json_ld, $survey, $metadata, $type);
		} else {
			$this->apply_schema_org_creative_work_fields($json_ld, $survey, $metadata, $type);
		}

		return $json_ld;
	}


	/**
	 * @param string $type
	 * @param array  $metadata
	 * @return string
	 */
	function get_schema_org_json_ld_type($type = '', $metadata = array())
	{
		if ($this->is_schema_org_dataset_type($type)) {
			return 'Dataset';
		}

		switch ($type) {
			case 'video':
				return 'VideoObject';
			case 'image':
				return 'ImageObject';
			case 'document':
				return $this->get_schema_org_document_type($metadata);
			default:
				return 'CreativeWork';
		}
	}


	/**
	 * @param array  $json_ld
	 * @param array  $survey
	 * @param array  $metadata
	 * @param string $type
	 * @return void
	 */
	private function apply_schema_org_common_fields(array &$json_ld, array $survey, array $metadata, $type)
	{
		$title = trim((string) ($survey['title'] ?? ''));
		$nation = trim((string) ($survey['nation'] ?? ''));
		$name = implode(' - ', array_filter(array($title, $nation !== '' ? $nation : null)));

		if ($name !== '') {
			$json_ld['name'] = $name;
		}

		$identifiers = array();
		$idno = trim((string) ($survey['idno'] ?? ''));
		if ($idno !== '') {
			$identifiers[] = $idno;
		}

		$doi_url = $this->format_schema_org_doi_url(isset($survey['doi']) ? $survey['doi'] : '');
		if ($doi_url !== null) {
			$identifiers[] = $doi_url;
			$json_ld['sameAs'] = $doi_url;
		}

		if (count($identifiers) === 1) {
			$json_ld['identifier'] = $identifiers[0];
		} elseif (count($identifiers) > 1) {
			$json_ld['identifier'] = $identifiers;
		}

		$this->apply_schema_org_catalog_membership(
			$json_ld,
			$this->is_schema_org_dataset_type($type)
		);

		if (!empty($survey['created'])) {
			$json_ld['dateCreated'] = date('c', $survey['created']);
		}

		if (!empty($survey['changed'])) {
			$json_ld['dateModified'] = date('c', $survey['changed']);
		}

		$description = isset($survey['schema_org_description'])
			? $survey['schema_org_description']
			: $this->get_schema_org_description($metadata, $type);
		if (!empty($description)) {
			$json_ld['description'] = $description;
		}

		$keywords = $this->get_schema_org_keywords($metadata, $type);
		if (!empty($keywords)) {
			$json_ld['keywords'] = $keywords;
		}
	}


	/**
	 * @param array $json_ld
	 * @param bool  $use_included_in_data_catalog
	 * @return void
	 */
	private function apply_schema_org_catalog_membership(array &$json_ld, $use_included_in_data_catalog)
	{
		$catalog_name = trim((string) $this->config->item('website_title'));
		if ($catalog_name === '') {
			$catalog_name = trim((string) $this->config->item('site_title'));
		}

		$catalog_url = base_url();
		if ($catalog_name === '' && $catalog_url === '') {
			return;
		}

		$catalog = array('@type' => 'DataCatalog');
		if ($catalog_name !== '') {
			$catalog['name'] = $catalog_name;
		}
		if ($catalog_url !== '') {
			$catalog['url'] = $catalog_url;
		}

		if ($use_included_in_data_catalog) {
			$json_ld['includedInDataCatalog'] = $catalog;
		} else {
			$json_ld['isPartOf'] = $catalog;
		}
	}


	/**
	 * @param array  $json_ld
	 * @param array  $survey
	 * @param array  $metadata
	 * @param string $type
	 * @return void
	 */
	private function apply_schema_org_dataset_fields(array &$json_ld, array $survey, array $metadata, $type)
	{
		$years = implode('/', array_filter(array_unique(array(
			$survey['year_start'] ?? null,
			$survey['year_end'] ?? null,
		))));
		if ($years !== '') {
			$json_ld['temporalCoverage'] = $years;
		}

		$nation = trim((string) ($survey['nation'] ?? ''));
		if ($nation !== '') {
			$json_ld['spatialCoverage'] = array(
				'@type' => 'Place',
				'name'  => $nation,
			);
		}

		$creators = $this->get_schema_org_creators($metadata, $type);
		if (!empty($creators)) {
			$json_ld['creator'] = $creators;
		}

		$producers = $this->get_schema_org_producers($metadata, $type);
		if (!empty($producers)) {
			$json_ld['producer'] = $producers;
		}
	}


	/**
	 * @param array  $json_ld
	 * @param array  $survey
	 * @param array  $metadata
	 * @param string $type
	 * @return void
	 */
	private function apply_schema_org_creative_work_fields(array &$json_ld, array $survey, array $metadata, $type)
	{
		$creators = $this->get_schema_org_creators($metadata, $type);
		if (empty($creators)) {
			$creators = $this->get_schema_org_producers($metadata, $type);
		}
		if (!empty($creators)) {
			$json_ld['creator'] = $creators;
		}

		$years = implode('/', array_filter(array_unique(array(
			$survey['year_start'] ?? null,
			$survey['year_end'] ?? null,
		))));
		if ($years !== '') {
			$json_ld['temporalCoverage'] = $years;
		}

		$nation = trim((string) ($survey['nation'] ?? ''));
		if ($nation !== '') {
			$json_ld['contentLocation'] = array(
				'@type' => 'Place',
				'name'  => $nation,
			);
		}

		switch ($type) {
			case 'video':
				$this->apply_schema_org_video_fields($json_ld, $survey, $metadata);
				break;
			case 'image':
				$this->apply_schema_org_image_fields($json_ld, $survey, $metadata);
				break;
			case 'document':
				$this->apply_schema_org_document_fields($json_ld, $metadata);
				break;
		}
	}


	/**
	 * @param array $metadata
	 * @return string
	 */
	private function get_schema_org_document_type($metadata)
	{
		$doc_type = strtolower(trim((string) $this->get_array_nested_value(
			$metadata,
			'document_description/type',
			'/'
		)));

		$map = array(
			'article'       => 'ScholarlyArticle',
			'inproceeding'  => 'ScholarlyArticle',
			'incollection'  => 'ScholarlyArticle',
			'techreport'    => 'Report',
			'working-paper' => 'Report',
			'phdthesis'     => 'Thesis',
			'masterthesis'  => 'Thesis',
			'book'          => 'Book',
			'booklet'       => 'Book',
			'manual'        => 'Book',
			'proceedings'   => 'PublicationVolume',
			'website'       => 'WebPage',
		);

		return isset($map[$doc_type]) ? $map[$doc_type] : 'DigitalDocument';
	}


	/**
	 * @param array $json_ld
	 * @param array $survey
	 * @param array $metadata
	 * @return void
	 */
	private function apply_schema_org_video_fields(array &$json_ld, array $survey, array $metadata)
	{
		$video_url = trim((string) $this->get_array_nested_value($metadata, 'video_description/video_url', '/'));
		if ($video_url !== '') {
			$json_ld['contentUrl'] = $video_url;
		}

		$embed_url = trim((string) $this->get_array_nested_value($metadata, 'video_description/embed_url', '/'));
		if ($embed_url !== '') {
			$json_ld['embedUrl'] = $embed_url;
		}

		$encoding_format = trim((string) $this->get_array_nested_value($metadata, 'video_description/encoding_format', '/'));
		if ($encoding_format !== '') {
			$json_ld['encodingFormat'] = $encoding_format;
		}

		$duration = trim((string) $this->get_array_nested_value($metadata, 'video_description/duration', '/'));
		if ($duration !== '') {
			$json_ld['duration'] = $duration;
		}

		$thumbnail_url = $this->resolve_schema_org_thumbnail_url($survey);
		if ($thumbnail_url !== null) {
			$json_ld['thumbnailUrl'] = $thumbnail_url;
		}

		$credit_text = trim((string) $this->get_array_nested_value($metadata, 'video_description/credit_text', '/'));
		if ($credit_text !== '') {
			$json_ld['creditText'] = $credit_text;
		}
	}


	/**
	 * @param array $json_ld
	 * @param array $survey
	 * @param array $metadata
	 * @return void
	 */
	private function apply_schema_org_image_fields(array &$json_ld, array $survey, array $metadata)
	{
		$thumbnail_url = $this->resolve_schema_org_thumbnail_url($survey);
		if ($thumbnail_url !== null) {
			$json_ld['contentUrl'] = $thumbnail_url;
			$json_ld['thumbnailUrl'] = $thumbnail_url;
		}

		$encoding_format = trim((string) $this->get_array_nested_value($metadata, 'image_description/dcmi/format', '/'));
		if ($encoding_format !== '') {
			$json_ld['encodingFormat'] = $encoding_format;
		}

		$caption = trim((string) $this->get_array_nested_value($metadata, 'image_description/dcmi/caption', '/'));
		if ($caption === '') {
			$caption = trim((string) $this->get_array_nested_value(
				$metadata,
				'image_description/iptc/photoVideoMetadataIPTC/headline',
				'/'
			));
		}
		if ($caption !== '') {
			$json_ld['caption'] = $caption;
		}
	}


	/**
	 * @param array $json_ld
	 * @param array $metadata
	 * @return void
	 */
	private function apply_schema_org_document_fields(array &$json_ld, array $metadata)
	{
		$doc_type = trim((string) $this->get_array_nested_value($metadata, 'document_description/type', '/'));
		if ($doc_type !== '') {
			$json_ld['genre'] = $doc_type;
		}
	}


	/**
	 * @param array $survey
	 * @return string|null
	 */
	private function resolve_schema_org_thumbnail_url(array $survey)
	{
		$thumbnail = trim((string) ($survey['thumbnail'] ?? ''));
		if ($thumbnail === '') {
			return null;
		}

		if (stripos($thumbnail, 'http://') === 0 || stripos($thumbnail, 'https://') === 0) {
			return $thumbnail;
		}

		return base_url(ltrim($thumbnail, '/'));
	}


	/**
	 * @param string|null $doi
	 * @return string|null
	 */
	function format_schema_org_doi_url($doi)
	{
		$doi = trim((string) $doi);
		if ($doi === '') {
			return null;
		}

		if (stripos($doi, 'http://') === 0 || stripos($doi, 'https://') === 0) {
			return $doi;
		}

		return 'https://doi.org/' . ltrim($doi, '/');
	}


	/**
	 * @param array  $metadata
	 * @param string $type
	 * @return array<int, string>
	 */
	function get_schema_org_keywords($metadata, $type = '')
	{
		if ($type === 'geospatial') {
			return $this->extract_schema_org_geospatial_keywords($metadata);
		}

		if ($type === 'image') {
			$keywords = $this->extract_schema_org_keyword_names(
				$metadata,
				'image_description/dcmi/keywords',
				'name'
			);
			if (!empty($keywords)) {
				return $keywords;
			}

			return $this->extract_schema_org_string_list(
				$this->get_array_nested_value($metadata, 'image_description/iptc/photoVideoMetadataIPTC/keywords', '/')
			);
		}

		$paths = array(
			'survey'        => array('study_desc/study_info/keywords', 'keyword'),
			'microdata'     => array('study_desc/study_info/keywords', 'keyword'),
			'document'      => array('document_description/keywords', 'name'),
			'script'        => array('project_desc/keywords', 'name'),
			'timeseries'    => array('series_description/keywords', 'name'),
			'timeseriesdb'  => array('database_description/keywords', 'name'),
			'timeseries-db' => array('database_description/keywords', 'name'),
			'table'         => array('table_description/keywords', 'name'),
			'video'         => array('video_description/keywords', 'name'),
			'image'         => array('image_description/dcmi/keywords', 'name'),
		);

		if (!isset($paths[$type])) {
			return array();
		}

		return $this->extract_schema_org_keyword_names(
			$metadata,
			$paths[$type][0],
			$paths[$type][1]
		);
	}


	/**
	 * @param array  $metadata
	 * @param string $type
	 * @return array<int, array<string, string>>
	 */
	function get_schema_org_creators($metadata, $type = '')
	{
		if ($type === 'document') {
			return $this->schema_org_organizations_from_names(
				$this->extract_schema_org_document_author_names($metadata)
			);
		}

		if ($type === 'geospatial') {
			return $this->schema_org_organizations_from_names(
				$this->extract_schema_org_geospatial_creator_names($metadata)
			);
		}

		if ($type === 'image') {
			return $this->schema_org_organizations_from_names(
				$this->extract_schema_org_image_creator_names($metadata)
			);
		}

		if ($type === 'video') {
			return $this->schema_org_organizations_from_names(
				$this->extract_schema_org_video_creator_names($metadata)
			);
		}

		$paths = array(
			'survey'        => 'study_desc/authoring_entity',
			'microdata'     => 'study_desc/authoring_entity',
			'script'        => 'project_desc/authoring_entity',
			'timeseries'    => 'series_description/authoring_entity',
			'timeseriesdb'  => 'database_description/authoring_entity',
			'timeseries-db' => 'database_description/authoring_entity',
			'table'         => 'table_description/authoring_entity',
		);

		if (!isset($paths[$type])) {
			return array();
		}

		return $this->schema_org_organizations_from_names(
			$this->extract_schema_org_entity_names($metadata, $paths[$type])
		);
	}


	/**
	 * @param array  $metadata
	 * @param string $type
	 * @return array<int, array<string, string>>
	 */
	function get_schema_org_producers($metadata, $type = '')
	{
		$paths = array(
			'survey'        => 'study_desc/production_statement/producers',
			'microdata'     => 'study_desc/production_statement/producers',
			'document'      => 'metadata_information/producers',
			'script'        => 'metadata_information/producers',
			'timeseries'    => 'metadata_information/producers',
			'timeseriesdb'  => 'metadata_information/producers',
			'timeseries-db' => 'metadata_information/producers',
			'table'         => 'metadata_information/producers',
			'geospatial'    => 'metadata_information/producers',
			'video'         => 'metadata_information/producers',
			'image'         => 'metadata_information/producers',
		);

		if (!isset($paths[$type])) {
			return array();
		}

		return $this->schema_org_organizations_from_names(
			$this->extract_schema_org_entity_names($metadata, $paths[$type])
		);
	}


	/**
	 * @param array  $metadata
	 * @param string $path
	 * @param string $name_key
	 * @return array<int, string>
	 */
	private function extract_schema_org_keyword_names($metadata, $path, $name_key = 'name')
	{
		$items = $this->get_array_nested_value($metadata, $path, '/');
		if (empty($items) || !is_array($items)) {
			return array();
		}

		if ($this->is_assoc_array($items) && isset($items[$name_key])) {
			$items = array($items);
		}

		$keywords = array();
		foreach ($items as $item) {
			if (!is_array($item) || empty($item[$name_key])) {
				continue;
			}
			$keyword = trim((string) $item[$name_key]);
			if ($keyword !== '') {
				$keywords[] = $keyword;
			}
		}

		return array_values(array_unique($keywords));
	}


	/**
	 * @param array  $metadata
	 * @param string $path
	 * @return array<int, string>
	 */
	private function extract_schema_org_entity_names($metadata, $path)
	{
		$items = $this->get_array_nested_value($metadata, $path, '/');
		if (empty($items) || !is_array($items)) {
			return array();
		}

		if ($this->is_assoc_array($items) && isset($items['name'])) {
			$items = array($items);
		}

		$names = array();
		foreach ($items as $item) {
			if (!is_array($item) || !isset($item['name'])) {
				continue;
			}
			$name = trim((string) $item['name']);
			if ($name !== '') {
				$names[] = $name;
			}
		}

		return array_values(array_unique($names));
	}


	/**
	 * @param array $metadata
	 * @return array<int, string>
	 */
	private function extract_schema_org_document_author_names($metadata)
	{
		$authors = $this->get_array_nested_value($metadata, 'document_description/authors', '/');
		if (empty($authors) || !is_array($authors)) {
			return array();
		}

		if ($this->is_assoc_array($authors) && (isset($authors['first_name']) || isset($authors['full_name']))) {
			$authors = array($authors);
		}

		$names = array();
		foreach ($authors as $author) {
			if (!is_array($author)) {
				continue;
			}

			if (!empty($author['full_name'])) {
				$name = trim((string) $author['full_name']);
			} else {
				$name = trim(trim((string) ($author['first_name'] ?? '')) . ' ' . trim((string) ($author['last_name'] ?? '')));
			}

			if ($name !== '') {
				$names[] = $name;
			}
		}

		return array_values(array_unique($names));
	}


	/**
	 * @param array $metadata
	 * @return array<int, string>
	 */
	private function extract_schema_org_geospatial_creator_names($metadata)
	{
		$ident = $this->get_array_nested_value($metadata, 'description/identificationInfo', '/');
		if (empty($ident) || !is_array($ident)) {
			return array();
		}

		$names = array();
		foreach ($ident as $info) {
			if (!is_array($info) || empty($info['pointOfContact'])) {
				continue;
			}

			$contacts = $info['pointOfContact'];
			if ($this->is_assoc_array($contacts)) {
				$contacts = array($contacts);
			}

			foreach ($contacts as $party) {
				if (!is_array($party)) {
					continue;
				}

				if (!empty($party['organisationName'])) {
					$names[] = trim((string) $party['organisationName']);
				} elseif (!empty($party['individualName'])) {
					$names[] = trim((string) $party['individualName']);
				}
			}
		}

		return array_values(array_unique(array_filter($names)));
	}


	/**
	 * @param array $metadata
	 * @return array<int, string>
	 */
	private function extract_schema_org_geospatial_keywords($metadata)
	{
		$ident = $this->get_array_nested_value($metadata, 'description/identificationInfo', '/');
		if (empty($ident) || !is_array($ident)) {
			return array();
		}

		$keywords = array();
		foreach ($ident as $info) {
			if (!is_array($info) || empty($info['descriptiveKeywords'])) {
				continue;
			}

			$groups = $info['descriptiveKeywords'];
			if ($this->is_assoc_array($groups) && isset($groups['keyword'])) {
				$groups = array($groups);
			}

			foreach ($groups as $group) {
				if (!is_array($group) || !isset($group['keyword'])) {
					continue;
				}

				$values = $group['keyword'];
				if (!is_array($values)) {
					$values = array($values);
				}

				foreach ($values as $value) {
					$keyword = trim((string) $value);
					if ($keyword !== '') {
						$keywords[] = $keyword;
					}
				}
			}
		}

		return array_values(array_unique($keywords));
	}


	/**
	 * @param array $metadata
	 * @return array<int, string>
	 */
	private function extract_schema_org_image_creator_names($metadata)
	{
		$names = array();

		$dcmi_creator = $this->get_array_nested_value($metadata, 'image_description/dcmi/creator', '/');
		$names = array_merge($names, $this->extract_schema_org_string_list($dcmi_creator));

		$iptc_creators = $this->get_array_nested_value(
			$metadata,
			'image_description/iptc/photoVideoMetadataIPTC/creatorNames',
			'/'
		);
		$names = array_merge($names, $this->extract_schema_org_string_list($iptc_creators));

		return array_values(array_unique(array_filter($names)));
	}


	/**
	 * @param array $metadata
	 * @return array<int, string>
	 */
	private function extract_schema_org_video_creator_names($metadata)
	{
		$names = array();

		$creator = $this->get_array_nested_value($metadata, 'video_description/creator', '/');
		$names = array_merge($names, $this->extract_schema_org_string_list($creator));

		$credit_text = trim((string) $this->get_array_nested_value($metadata, 'video_description/credit_text', '/'));
		if ($credit_text !== '') {
			$names[] = $credit_text;
		}

		return array_values(array_unique(array_filter($names)));
	}


	/**
	 * @param mixed $value
	 * @return array<int, string>
	 */
	private function extract_schema_org_string_list($value)
	{
		if (is_string($value)) {
			$value = trim($value);
			return $value !== '' ? array($value) : array();
		}

		if (!is_array($value)) {
			return array();
		}

		$names = array();
		foreach ($value as $item) {
			if (is_string($item)) {
				$item = trim($item);
				if ($item !== '') {
					$names[] = $item;
				}
			}
		}

		return $names;
	}


	/**
	 * @param array<int, string> $names
	 * @return array<int, array<string, string>>
	 */
	private function schema_org_organizations_from_names(array $names)
	{
		$organizations = array();
		foreach ($names as $name) {
			$name = trim((string) $name);
			if ($name === '') {
				continue;
			}
			$organizations[] = array(
				'@type' => 'Organization',
				'name'  => $name,
			);
		}

		return $organizations;
	}


	/**
	 * @param array $array
	 * @return bool
	 */
	private function is_assoc_array(array $array)
	{
		if ($array === array()) {
			return false;
		}

		return array_keys($array) !== range(0, count($array) - 1);
	}




	function array_to_plain_text($data)
	{
		$output = array();
		$item = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
		//,\RecursiveIteratorIterator::SELF_FIRST);
		
        foreach($item as $key=>$value) {
			$output[] = $value;
        }  
        return implode(' ', $output);        
	}



	/**
	*
	* update survey table fields
	*
	* @options - array()
	*/
	function update_options($sid,$options)	
	{
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$this->survey_fields) ){
				$data[$key]=$value;
			}
		}

		//repositoryid
		if(isset($options['repositoryid'])){
			$this->set_dataset_owner_repo($sid,$options['repositoryid']);
			unset($options['repositoryid']);
		}

		//tags
		if(isset($options['tags'])){
			$this->add_survey_tags($sid, $options['tags']);
			unset($options['tags']);
		}

		//aliases
		if (isset($options['aliases'])){
			$this->add_survey_aliases($sid,$options['aliases']);
			unset($options['aliases']);
		}

		if (empty($data)) {
			return false;
		}

		//abstract
		if (!isset($data['abstract']) && isset($data['metadata'])) {
			$type = isset($data['type']) ? $data['type'] : $this->get_type($sid);
			$metadata = is_array($data['metadata'])
				? $data['metadata']
				: $this->decode_metadata($data['metadata']);
			$data['abstract'] = $this->extract_abstract($metadata, $type);
		}

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}
		
		//update
		$this->db->where('id',$sid);
		$result=$this->db->update('surveys', $data);

		if ($result===false){
			throw new Exception($this->db->error('message'));
		}

		return $sid;
	}



	/**
	 * 
	 * 
	 * Update survey variables count
	 */
	function  update_varcount($sid,$count=null)
	{
		if($count==null){
			//get a count of variables
			$count=$this->Variable_model->get_variables_count($sid);
		}

		$options=array(
			'varcount'=>$count
		);

		$this->db->where('id',$sid);
		$this->db->update('surveys',$options);	
	}


	function has_datafiles($sid)
	{
		$this->db->select('id');
		$this->db->from('data_files');
		$this->db->where('sid',$sid);
		return $this->db->count_all_results();
	}



	/**
	*
	* Build a range of data collection years range
	*
	* It uses the start and end as range and add each year as a new row
	* in the database.
	*
	* e.g. for range 2005-2010, there will be 6 rows in the survey_rows
	*/
	function update_years($sid, $start_year, $end_year)
	{		
		//remove existing dates if any
		$this->db->delete('survey_years',array('sid' => $sid));

		$start=(integer)$start_year;
		$end=(integer)$end_year;

		if ( ($start_year > 0 && $start_year < 1600) || $start_year > 3000 || ($end_year >0 && $end_year < 1600) || $end_year > 3000){
			throw new Exception("INVALID_YEAR_RANGE:" .implode("-",array($start_year,$end_year)));
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$end=$start;
		}

		//build an array of years range
		$years=range($start,$end);

		//insert dates into database
		foreach($years as $year){
			$options=array(
						'sid' => $sid,
						'data_coll_year' => $year);
			//insert
			$result=$this->db->insert('survey_years',$options);

			if ($result===false){
				throw new Exception($this->db->error('message'));
			}
		}
	}
	
	
	/**
	 * 
	 * Delete dataset by IDNO
	 * 
	 */
	function delete_by_idno($idno)
	{		
		//get internal ID by IDNO
		$sid=$this->get_id_by_idno($idno);

		if($sid){
			return $this->delete($sid);
		}

		return false;
	}


	/**
	* Delete dataset and related data
	*
	*
	*/
	function delete($id)
	{
		$this->load->library('Search_index_manager');
		$this->search_index_manager->handle_event('surveys', $id, 'delete', true);

		try {
			$this->load->model('Timeseries_mongo_model');
			$this->Timeseries_mongo_model->delete_observations_for_sid_all_indicator_collections((int) $id);
		} catch (Throwable $e) {
			log_message('error', 'Dataset_model::delete indicator Mongo cleanup failed for sid=' . $id . ': ' . $e->getMessage());
		}

		$this->delete_storage_folder($id);

		$this->db->where('id', $id); 
		$deleted=$this->db->delete('surveys');
		
		if ($deleted)
		{
			//remove variables
			$this->db->where('sid', $id); 
			$this->db->delete('variables');		

			//remove data files
			$this->db->where('sid', $id); 
			$this->db->delete('data_files');		
			
			//remove external resources
			$this->db->where('survey_id', $id); 
			$this->db->delete('resources');					

			//remove topics
			$this->db->where('sid', $id); 
			$this->db->delete('survey_topics');					

			//remove citations
			$this->db->where('sid', $id); 
			$this->db->delete('survey_citations');					

			//remove collection dates
			$this->db->where('sid', $id); 
			$this->db->delete('survey_years');
			
			//remove repos
			$this->db->where('sid', $id); 
			$this->db->delete('survey_repos');

			//remove alias
			$this->db->where('sid', $id); 
			$this->db->delete('survey_aliases');
			
			//remove countries
			$this->db->where('sid', $id); 
			$this->db->delete('survey_countries');

			//remove tags
			$this->db->where('sid', $id); 
			$this->db->delete('survey_tags');
			
			//remove notes
			$this->db->where('sid', $id); 
			$this->db->delete('survey_notes');
		}		
	}


	function delete_storage_folder($sid)
	{
		$dataset_folder=$this->get_storage_fullpath($sid);
		$catalog_root=get_catalog_root();

		if($catalog_root=='' || $dataset_folder==''){
			return false;
		}

		if($catalog_root==$dataset_folder){
			return false;
		}

		if (!strpos($dataset_folder, $catalog_root) === 0 ) {
			return false;
		}
		
		remove_folder($dataset_folder);

		return true;
	}	
	
	
	//is survey published
	public function is_published($sid)
	{
		$this->db->select("published");
		$this->db->where("id",$sid);
		
		$q=$this->db->get("surveys");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['published'];
		}
	}
	
	
	//return an array of all survey types array
	public function get_survey_types_array()
	{
		return $this->Survey_type_model->get_list();
	}
	
	
	//set study publish status
	public function set_publish_status($sid,$status)
	{
		if (!in_array($status,array(0,1)) ){
			throw new Exception("INVALID_STATUS_VALUE");
		}

		$options=array(
			'published'=>$status
		);
		
		$this->update_options($sid,$options);
	}
	
	

	/**
	* Get dataset dirpath
	* 
	**/
	function get_dirpath($sid)
	{
		$this->db->select('dirpath');
		$this->db->where('id', $sid);
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return $query['dirpath'];
		}
		
		return false;
	}

	//return storage folder fullpath for the dataset
	function get_storage_fullpath($sid)
	{
		$folder=$this->get_dirpath($sid);
		
		if (!$folder){
			return false;
		}

		return get_catalog_root() . '/'.$folder;
	}

	function get_metadata_file_path($sid)
	{
		$this->db->select('idno,dirpath,metafile');
		$this->db->where('id', $sid);
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			if(empty($query['metafile'])){
				$query['metafile']=$query['idno'].'.xml';
			}
			return get_catalog_root() . '/'. $query['dirpath'].'/'.$query['metafile'];
		}
		
		return false;
	}

    
	/**
	* returns internal survey id by IDNO
	* checks for ID in both surveys and aliases table
	* @param string $idno - Main IDNO to search for
	* @param array $alternate_idnos - Optional array of alternative IDNOs to also check
	**/
	function find_by_idno($idno, $alternate_idnos = null)
	{
		// First check the main IDNO
		$this->db->select('id');
		$this->db->where('idno', (string)$idno); 
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return $query['id'];
		}
		
		//check idno in survey aliases
		$this->db->select('sid');
		$this->db->where(array('alternate_id' => $idno) );
		$query=$this->db->get('survey_aliases')->result_array();

		if ($query){
			return $query[0]['sid'];
		}
		
		// If alternate IDNOs provided, check them as well
		if (!empty($alternate_idnos) && is_array($alternate_idnos)) {
			foreach($alternate_idnos as $alt_idno) {
				if(empty($alt_idno)) {
					continue;
				}
				
				// Check main surveys table
				$this->db->select('id');
				$this->db->where('idno', (string)$alt_idno); 
				$query=$this->db->get('surveys')->row_array();
				
				if ($query){
					return $query['id'];
				}
				
				// Check survey aliases table
				$this->db->select('sid');
				$this->db->where(array('alternate_id' => $alt_idno) );
				$query=$this->db->get('survey_aliases')->result_array();

				if ($query){
					return $query[0]['sid'];
				}
			}
		}
		
		return false;
	}

	/**
	 * 
	 * return study id by DOI
	 * 
	 * 
	**/
	function find_by_doi($doi)
	{
		if(!$doi){
			return false;
		}

		$this->db->select('id');
		$this->db->where('doi', (string)$doi);

		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return $query['id'];
		}
	}

    
	function delete_topics($sid)
	{
		$this->db->where("sid",$sid);
		$this->db->delete("survey_topics");
	}
    
    //@topics array(topic, vocab, uri)
    function update_topics($sid,$topics)
	{
		if(!$topics){
			return false;
		}

		$options=array();

		foreach($topics as $topic){
			
			if (!isset($topic['vocab'])){
				continue;//skip if no vocab is set
			}

			$vocab=$this->Vocabulary_model->get_vocabulary_by_title($topic['vocab']);

			$topic_title=explode("[",$topic['topic']);
			$topic_title=trim($topic_title[0]);

			//if($vocab){
				$terms=$this->Term_model->find_term($topic_title);

				if($terms){
					foreach($terms as $term){
						$options[]=array(
							'sid'=>$sid,
							'tid'=>$term['tid']
						);
					}
				}
			//}
		}

		if(count($options)>0){
			$this->db->insert_batch('survey_topics',$options);
		}

	}
	
	
	/**
	*
	* Set the owner repo for the dataset
	*/
	function set_dataset_owner_repo($sid,$repositoryid)
	{
		$this->unset_dataset_owner_repo(($sid));
		
		$data=array(
				'sid'=>$sid,
				'repositoryid'=>$repositoryid,
				'isadmin'=>1 //give admin rights to the repo that uploaded the survey
			);

		//delete any existing entry for the study
		$this->db->where('sid',$sid);
		$this->db->where('repositoryid',$repositoryid);
		$this->db->delete('survey_repos');

		//add new info
		$this->db->insert('survey_repos',$data);

		//update surveys table
		$this->db->where('id',$sid);
		$this->db->update('surveys',array('repositoryid'=>$repositoryid));

		return TRUE;
	}

	

	/**
	 * 
	 * Unset the dataset owner repo
	 */
	function unset_dataset_owner_repo($sid)
	{
		$this->db->where('isadmin',1);
		$this->db->where('sid',$sid);
		$this->db->delete('survey_repos');
	}

	
    /**
	*
	* @countries array name, abbreviation
	**/
	function update_survey_countries($sid, $countries)
	{
		$this->load->model("Country_model");
        
        //delete existing survey countries
        $this->Country_model->delete_by_sid($sid);
        
        if (!$countries){
            return;
        }
        
		$data=array();
		foreach ($countries as $row)
		{
            $country=$row['name'];
            
			//get country ISO code
			$countryid=$this->Country_model->find_country_by_name($country);
			
			//add to survey_countries
            $options=array(
					'sid'			=>$sid,
					'country_name'	=>$country,
					'cid'			=>$countryid
				);
            $this->db->insert('survey_countries',$options);
		}		
	}




	/**
	*
	* Add/Update tags
	*
	* @sid - dataset id
	* @tags - array - list of tags
	*
	**/
	function update_survey_tags($sid, $tags=array())
	{
		$this->load->model("Catalog_tags_model");
        $this->Catalog_tags_model->delete_survey_tags($sid);
				
        if (!is_array($tags)){
            return;
        }
        
		// Remove duplicates and nulls
		$tags = array_values(array_unique(array_filter($tags)));
        
		foreach ($tags as $tag){
			if (empty($tag)) {
				continue;
			}
			
			$options=array(
				'sid'	=>$sid,
				'tag'	=>$tag
			);
			$this->Catalog_tags_model->insert($options);
		}
	}


	function add_survey_tags($sid, $tags=array())
	{
		$this->load->model("Catalog_tags_model");
				
        if (!is_array($tags)){
            return;
        }

		//remove duplicates and nulls
		$tags = array_values(array_unique(array_filter($tags)));
        
		foreach ($tags as $tag){
			if (empty($tag)) {
				continue;
			}
			
			$this->Catalog_tags_model->upsert($sid, $tag);
		}
	}

	function add_tags($sid, $tags)
    {
        if(empty($tags)){
            return false;
        }
        
        $tags=array_column($tags,'tag');
        return $this->add_survey_tags($sid,$tags);        
    }



	function add_survey_aliases($sid, $aliases=array())
	{
		$this->load->model("Survey_alias_model");
				
        if (!is_array($aliases)){
            return;
        }

		// Get the main IDNO for this dataset to avoid creating duplicate aliases
		$main_idno = $this->get_idno($sid);

		foreach($aliases as $alias){
			// Skip if alias is empty or same as main IDNO
			if(empty($alias) || $alias === $main_idno) {
				continue;
			}

			if (!$this->Survey_alias_model->id_exists($alias)){
				$options = array(
					'sid'  => $sid,
					'alternate_id' => $alias,
				);
				$this->Survey_alias_model->insert($options);
			}
		}
	}

    
    //encode metadata for db storage
    public function encode_metadata($metadata_array)
    {
        return base64_encode(serialize($metadata_array));
    }


    //decode metadata to array
    public function decode_metadata($metadata_encoded)
    {
        return unserialize(base64_decode($metadata_encoded));
	}
	

	//import RDF
	public function import_rdf($sid,$filepath)
	{
		$this->load->model("Survey_resource_model");
		return $this->Survey_resource_model->import_rdf($sid,$filepath);
	}

	/**
	*
	* Import external resources
	*
	* 
	* - delete all existing resources?
	* 
	*
	* 
	*/
	function update_resources($sid, $external_resources)
	{		
		if (empty($external_resources)){
			return;
		}

		//remove all existing resources
		$this->Survey_resource_model->delete_all_survey_resources($sid);

		//import new
		foreach($external_resources as $resource){
			$resource['survey_id']=$sid;

			$this->Survey_resource_model->validate_resource($resource);
			$this->Survey_resource_model->insert($resource);
		}		
	}


	//list studies by current user
	public function get_studies_by_user($userid,$limit=20)
	{
		$this->db->select("id,idno,title,nation,year_start,year_end,created,created_by,changed,changed_by,published");
		$this->db->where('created_by',$userid);
		$this->db->order_by('created_by','DESC');
		$this->db->limit($limit);
		$result=$this->db->get('surveys')->result_array();
		return $result;
	}

	//list recent studies
	public function get_recent_studies($limit=20)
	{
		$this->db->select("id,idno,title,nation,year_start,year_end,created,created_by,changed,changed_by,published");
		$this->db->order_by('created_by','DESC');
		$this->db->limit($limit);
		$result=$this->db->get('surveys')->result_array();
		return $result;
	}


	public function set_data_access_type($sid,$da_type,$da_link)
	{
		$options=array(
			'formid'=>$da_type,
			'link_da'=>$da_link
		);

		return $this->update_options($sid,$options);
	}


	//remove all variables from a data file
	public function remove_datafile_variables($sid,$file_id)
	{
		$this->db->where("sid",$sid);
		$this->db->where("fid",$file_id);
		return $this->db->delete("variables");
	}


	//validate survey IDNO
	public function validate_survey_idno($idno)
	{	
		$sid=null;
		if(array_key_exists('sid',$this->form_validation->validation_data)){
			$sid=$this->form_validation->validation_data['sid'];
		}

		//check if the survey id already exists
		$id=$this->find_by_idno($idno);	

		if (is_numeric($id) && $id!=$sid ) {
			$this->form_validation->set_message(__FUNCTION__, 'The ID should be unique.' );
			return false;
		}
		return true;
	}



	//validate survey IDNO
	public function validate_study_type($datatype)
	{	
		//check if the survey id already exists
		$id=$this->Survey_type_model->get_stype_id_by_name($datatype);	

		if (!$id){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid. Supported types are: '. implode(", ", $this->Survey_type_model->get_names_array()));
			return false;
		}
		return true;
	}


	
	//validate repository IDNO
	public function validate_repository_idno_exists($repo_id)
	{	
		if (empty($repo_id)){
			return true;
		}

		$this->load->model('Repository_model');

		if (!$this->Repository_model->is_valid_repo($repo_id)) {
			$this->form_validation->set_message(__FUNCTION__, 'Collection does not exist: '.$repo_id );
			return false;
		}
		return true;
	}

	/**
	 * 
	 * 
	 * Validate survey
	 * @options - array of survey fields
	 * @is_new - boolean - new survey or updating and existing survey
	 * 
	 **/
	function validate($type,$options,$is_new=true)
	{				
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for a new record
		if($is_new){				
			#$this->form_validation->set_rules('title', 'Title', 'required|xss_clean|trim|max_length[255]');	
			$this->form_validation->set_rules('repositoryid', 'Collection ID', 'required|xss_clean|trim|max_length[25]');	
			#$this->form_validation->set_rules('nation', 'Country name', 'required|xss_clean|trim|max_length[255]');	
			#$this->form_validation->set_rules('year', 'year', 'required|is_numeric|xss_clean|trim|max_length[4]');	

			
			//survey idno validation rule
			$this->form_validation->set_rules(
				'idno', 
				'IDNO',
				array(
					"required",
					"alpha_dash",
					"max_length[200]",
					"xss_clean",
					array('validate_survey_idno',array($this, 'validate_survey_idno')),				
				)		
			);
		}
		
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
	 * 
	 * Validate survey options
	 * @options - array of survey fields
	 * @is_new - boolean - new survey or updating and existing survey
	 * 
	 **/
	function validate_options($options)
	{				
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for a new record
		$this->form_validation->set_rules('link_da', 'Remote Data URL', 'xss_clean|trim|max_length[500]');	
		$this->form_validation->set_rules('published', 'Published', 'integer|xss_clean|trim|max_length[1]');
		$this->form_validation->set_rules('link_questionnaire', 'link_questionnaire', 'valid_url|xss_clean|trim|max_length[300]');
		$this->form_validation->set_rules('link_technical', 'link_technical', 'valid_url|xss_clean|trim|max_length[300]');
		$this->form_validation->set_rules('link_study', 'link_study', 'valid_url|xss_clean|trim|max_length[300]');
		$this->form_validation->set_rules('link_indicator', 'link_indicator', 'valid_url|xss_clean|trim|max_length[300]');
		#$this->form_validation->set_rules('repositoryid', 'Collection ID', 'alpha_numeric|xss_clean|trim|max_length[25]');	
		
		//repository ID
		$this->form_validation->set_rules(
			'repositoryid', 
			'Collection ID',
			array(
				"alpha_dash",
				"max_length[50]",
				"xss_clean",
				array('validate_repository_idno_exists',array($this, 'validate_repository_idno_exists')),				
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



	//create folder for the study
    public function setup_folder($repositoryid, $folder_name)
    {
		$catalog_root=get_catalog_root();

        $repository_folder=$catalog_root.'/'.$repositoryid;
        $survey_folder=$repository_folder.'/'.$folder_name;

        //create the repo folder and survey folder
        @mkdir($survey_folder, 0777, $recursive=true);

        if(!file_exists($repository_folder)){
            throw new Exception("REPO_FOLDER_NOT_CREATED:".$repository_folder);
        }

        if(!file_exists($survey_folder)){
            throw new Exception("SURVEY_FOLDER_NOT_CREATED:".$survey_folder);
        }

		//relative path to catalog_root
        return $repositoryid.'/'.$folder_name;
	}
	

	public function get_data_access_type_id($name)
	{
		$this->load->model("Form_model");
		return $this->Form_model->get_formid_by_name($name);
	}

	/**
	 * 
	 * Replace internal db sid field with new value
	 * 
	 */
	function update_sid($old_sid,$new_id)
	{
		$options=array(
			'id'=>$new_id
		);

		$this->db->where('id',$old_sid);
		$result=$this->db->update("surveys",$options);

		if(!$result){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));
		}

		return $result;
	}


	
	/**
	 * 
	 * Return a list of datasets with tags
	 * 
	 * @idno - Survey IDNO
	 * @format - flat, distinct
	 * 	flat - survey info is repeated for each tag
	 *  distinct - tags are returned in an array format
	 */
	public function get_dataset_with_tags($idno=NULL,$format='flat')
	{
		$this->db->select("surveys.idno,surveys.id,survey_tags.tag");
		$this->db->join('surveys','surveys.id=survey_tags.sid','inner');

		if(!empty($idno)){
			$this->db->where('surveys.idno',$idno);
		}
		
		$result=$this->db->get("survey_tags")->result_array();

		if ($format=='flat'){
			return $result;
		}
		
		$output=array();
		foreach($result as $row){
			$output[$row['idno']][]=$row['tag'];
		}

		return $output;
	}


	/**
	 * 
	 * Return a list of datasets with aliases
	 * 
	 * @idno - Survey IDNO
	 */
	public function get_dataset_aliases($idno=NULL)
	{
		$this->db->select("surveys.idno,surveys.id,survey_aliases.alternate_id as alias");
		$this->db->join('surveys','surveys.id=survey_aliases.sid','inner');

		if(!empty($idno)){
			$this->db->or_where('surveys.idno',$idno);
			$this->db->or_where('survey_aliases.alternate_id',$idno);
		}
		
		$result=$this->db->get("survey_aliases")->result_array();

		return $result;
	}




	/**
	 * 
	 * Return a list of country codes for a single or multiple datasets
	 * 
	 * @sid - single or array of sid
	 */
	public function get_dataset_country_codes($sid)
	{
		if(empty($sid)){
			return false;
		}

		$this->db->select("survey_countries.sid,countries.iso");
		$this->db->join('survey_countries','survey_countries.cid=countries.countryid','inner');
		$this->db->where_in('survey_countries.sid', $sid);
		$result= $this->db->get("countries")->result_array();

		$output=array();
		foreach($result as $row){
			$output[$row['sid']][]=$row['iso'];
		}
		
		return $output;
	}

	/**
	 * 
	 * Merge and replace nested metadata elements
	 * 
	 * @metadata - original metadata array
	 * @replace - partial metadata array
	 * 
	 */
	function array_merge_replace_metadata($metadata, $replace)
    {
        $metadata=array_replace_recursive($metadata,$replace);
        $metadata_indexed_fields=array_indexed_elements($metadata);

        foreach($metadata_indexed_fields as $path){
            if ($replace_value=get_array_nested_value($replace,$path,"/")){
                set_array_nested_value($metadata,$path,$replace_value,"/");
            }
        }

        return $metadata;
	}
	

	function refresh_year_facets($start_row=NULL, $limit=1000)
	{		
		$this->db->select("id,year_start,year_end");
		$this->db->limit($limit);
        $this->db->order_by('id ASC');

		if ($start_row){
			$this->db->where("id >",$start_row,false);
		}

		$rows=$this->db->get("surveys")->result_array();
		
		$last_row_id=null;
		foreach($rows as $row){
			$this->update_years($row['id'], $row['year_start'], $row['year_end']);
			$last_row_id=$row['id'];
		}

		return array(
			'last_row_id'=>$last_row_id,
			'processed'=>count($rows),
			'start'=>$start_row,
			'limit'=>$limit
		);		
	}	


	/**
	 * 
	 * 
	 * Create a new empty project
	 * 
	 * 
	 */
	function create_new($idno, $type, $repositoryid, $title, $created_by)
	{
		$folder_path=$this->setup_folder($repositoryid, $idno);
		
		$options=array(
			'idno'=>$idno,
			'type'=>$type,
			'repositoryid'=>$repositoryid,
			'title'=>$title,
			'created_by'=>$created_by,
			'published'=>0,
			'dirpath'=>$folder_path
		);

		return $this->insert($type,$options);
	}

	

	function GUID()
	{
		if (function_exists('com_create_guid') === true){
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}


	//create a DDI file
    function write_ddi($sid,$overwrite=false)
    {
        $this->load->library("DDI_Writer");
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');
		$this->load->model('Variable_group_model');
        $dataset=$this->get_row($sid);

        if($dataset['type']!='survey'){
            throw new Exception("DDI is only available for Survey/MICRODATA types");
        }

        $ddi_path=$this->get_metadata_file_path($sid);		

		//create project folder if not exists
		if(!file_exists(dirname($ddi_path))){
			mkdir(dirname($ddi_path));
		}

		//data has changed, overwrite file
		if (file_exists($ddi_path) && filemtime($ddi_path) < $dataset['changed']){
			$overwrite=true;
		}

        if(file_exists($ddi_path) && $overwrite==false){
            throw new Exception("DDI_FILE_EXISTS");
        }

        $this->ddi_writer->generate_ddi($sid,$ddi_path);
        
        $ddi_filename = basename($ddi_path);
        if (empty($ddi_filename)) {
            $ddi_filename = $dataset['idno'] . '.xml';
        }
        
        if (empty($dataset['metafile']) || $dataset['metafile'] !== $ddi_filename) {
            $this->db->where('id', $sid);
            $this->db->update('surveys', array('metafile' => $ddi_filename));
        }
        
        return $ddi_path;
    }

	function download_metadata($sid,$format='json',$dsd_export='reference')
	{
		if ($format=='json' || $format=='jsonl'){
			$this->load->library('JSON_Writer');
			$this->json_writer->download($sid, $format, false, false, $dsd_export);
		}
		else if ($format=='ddi'){
			return $this->download_metadata_ddi($sid);
		}
	}

	function download_metadata_ddi($sid)
	{
		$dataset=$this->get_row($sid); 
		$ddi_path=$this->get_metadata_file_path($sid);

		if(!file_exists($ddi_path)){
			try{
				$result=$this->write_ddi($sid,$overwrite=true);
			}
			catch(Exception $e){                    
				show_error($e->getMessage());
			}	
		}

		if(file_exists($ddi_path)){
			$this->load->helper("download");
			force_download2($ddi_path);
		}
	}


	/**
	*
	* assign DOI
	*/
	function assign_doi($sid,$doi)
	{
		//check if a study with the same DOI already exists
		$doi_sid=$this->find_by_doi($doi);

		if ($doi_sid && $doi_sid!==$sid){
			throw new Exception("DOI is already in use. #".$doi_sid);
		}
		
		$data=array(
			'doi'=>$doi
		);

		//add doi
		$this->db->where('id',$sid);
		$this->db->update('surveys',$data);
		return TRUE;
	}


	/**
     * 
     * Return a comma separated list of country names
     */
    function get_country_names_string($nations) 
    {
		if (!is_array($nations)){
			return '';
		}

        $max_show=3;

        $nation_str='';
        if (count($nations)>$max_show){
            $nation_str=implode(", ", array_slice($nations, 0, $max_show));
            $nation_str.='...and '. (count($nations) - $max_show). ' more';
        }else{
            $nation_str=implode(", ", $nations);
        }

        return $nation_str;
    }


	function update_locations($sid, $bounds=array())
    {
		return false;//disabled
        //delete any existing locations
        $this->db->delete('survey_locations',array('sid' => $sid));

        if(!is_array($bounds)){
            return false;
        }
        
        foreach($bounds as $bbox)
        {
            $north=$bbox['north'];
            $south=$bbox['south'];
            $east=$bbox['east'];
            $west=$bbox['west'];

            $this->load->helper("gis_helper");
            $bbox_wkt=$this->db->escape(bbox_to_wkt($north, $south, $east, $west));

            $this->db->set('sid',$sid);
            $this->db->set('location',$bbox_wkt);
            $this->db->insert('survey_locations');
        }
    }


	/**
	 * 
	 * 
	 * Get thumbnail
	 * 
	 */
    function get_thumbnail($sid)
    {
		if (!is_numeric($sid) || is_float($sid)){
            return false;
        }

		$this->db->select("surveys.thumbnail");
		$this->db->where("surveys.id",$sid);		
		$survey=$this->db->get("surveys");

		if ($survey){
			$survey=$survey->row_array();
		}

        if (isset($survey['thumbnail'])){
			return $survey['thumbnail'];
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// Indicator timeseries: surveys.ts_dimensions + surveys.ts_sync_required
	// -------------------------------------------------------------------------

	/**
	 * Sorted comma-separated DSD component names with column_type `dimension`.
	 *
	 * @param array $components Data_structure_component_model rows
	 * @return string|null
	 */
	public function build_ts_dimensions_csv_from_components(array $components)
	{
		$names = [];
		foreach ($components as $c) {
			if (!is_array($c) || empty($c['name']) || empty($c['column_type'])) {
				continue;
			}
			if ($c['column_type'] === 'dimension') {
				$names[] = (string) $c['name'];
			}
		}
		$names = array_values(array_unique($names));
		sort($names, SORT_STRING);
		return $names !== [] ? implode(',', $names) : null;
	}

	/**
	 * @param int $data_structure_id data_structures.id
	 * @return string|null
	 */
	public function build_ts_dimensions_csv_for_structure_id($data_structure_id)
	{
		$data_structure_id = (int) $data_structure_id;
		if ($data_structure_id <= 0) {
			return null;
		}
		$this->load->model('Data_structure_component_model');
		$components = $this->Data_structure_component_model->get_components_by_structure_id($data_structure_id);
		return $this->build_ts_dimensions_csv_from_components($components);
	}

	/**
	 * Comma-separated titles of codelist items from the DSD's periodicity component.
	 * Returns null when no periodicity component or its codelist has no items.
	 *
	 * @param array $components Data_structure_component_model rows
	 * @return string|null e.g. "Annual" or "Annual, Quarterly"
	 */
	public function build_ts_frequency_from_components(array $components)
	{
		$periodicity = null;
		foreach ($components as $c) {
			if (is_array($c) && !empty($c['column_type']) && $c['column_type'] === 'periodicity') {
				$periodicity = $c;
				break;
			}
		}
		if (!$periodicity || empty($periodicity['codelist_id'])) {
			return null;
		}
		$this->load->model('Codelist_item_model');
		$items = $this->Codelist_item_model->get_items_by_codelist((int) $periodicity['codelist_id'], false);
		$titles = [];
		foreach ($items as $item) {
			if (!empty($item['title'])) {
				$titles[] = (string) $item['title'];
			}
		}
		return $titles !== [] ? implode(', ', $titles) : null;
	}

	/**
	 * @param int $data_structure_id data_structures.id
	 * @return string|null
	 */
	public function build_ts_frequency_for_structure_id($data_structure_id)
	{
		$data_structure_id = (int) $data_structure_id;
		if ($data_structure_id <= 0) {
			return null;
		}
		$this->load->model('Data_structure_component_model');
		$components = $this->Data_structure_component_model->get_components_by_structure_id($data_structure_id);
		return $this->build_ts_frequency_from_components($components);
	}

	/**
	 * Canonical on-disk CSV name for indicator timeseries import (one file per study folder).
	 *
	 * @param int $sid surveys.id
	 * @return string e.g. "123_indicator_data.csv"
	 */
	public function get_indicator_timeseries_import_csv_filename($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return '';
		}
		return $sid . '_indicator_data.csv';
	}

	/**
	 * Public catalogue gating: when 1, chart/data API should be hidden until import or rehash clears the flag.
	 *
	 * @param int $sid surveys.id
	 * @return int 0 or 1
	 */
	public function get_indicator_ts_sync_required_for_sid($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return 0;
		}
		$r = $this->db->select('ts_sync_required')->get_where('surveys', ['id' => $sid])->row_array();
		return ($r && !empty($r['ts_sync_required'])) ? 1 : 0;
	}

	/**
	 * Linked surveys: refresh ts_dimensions from live DSD, set ts_sync_required = 1.
	 *
	 * @param int $data_structure_id
	 */
	public function mark_surveys_indicator_ts_sync_required_for_dsd($data_structure_id)
	{
		$dsd_id = (int) $data_structure_id;
		if ($dsd_id <= 0) {
			return;
		}
		$q = $this->db->select('id')->from('surveys')->where('data_structure_id', $dsd_id)->get();
		$sids = $q ? array_map('intval', array_column($q->result_array(), 'id')) : [];
		$csv  = $this->build_ts_dimensions_csv_for_structure_id($dsd_id);
		$freq = $this->build_ts_frequency_for_structure_id($dsd_id);
		$this->db->where('data_structure_id', $dsd_id);
		$this->db->update('surveys', [
			'ts_sync_required' => 1,
			'ts_dimensions'    => $csv,
			'ts_frequency'     => $freq,
			'changed'          => date('U'),
		]);
		$this->_emit_survey_refresh_events_for_sids($sids);
	}

	/**
	 * After successful import/rehash for one study.
	 *
	 * @param int $sid surveys.id
	 * @param int $data_structure_id
	 */
	public function clear_indicator_ts_sync_for_survey($sid, $data_structure_id)
	{
		$sid = (int) $sid;
		$dsd_id = (int) $data_structure_id;
		if ($sid <= 0 || $dsd_id <= 0) {
			return;
		}
		$csv  = $this->build_ts_dimensions_csv_for_structure_id($dsd_id);
		$freq = $this->build_ts_frequency_for_structure_id($dsd_id);
		$this->db->where('id', $sid);
		$this->db->where('data_structure_id', $dsd_id);
		$this->db->update('surveys', [
			'ts_sync_required' => 0,
			'ts_dimensions'    => $csv,
			'ts_frequency'     => $freq,
			'changed'          => date('U'),
		]);
		$this->_emit_survey_refresh_events_for_sids([$sid]);
	}

	/**
	 * Full rehash for a DSD (no partial limit): clear flag for every linked survey.
	 *
	 * @param int $data_structure_id
	 */
	public function clear_indicator_ts_sync_for_all_surveys_on_dsd($data_structure_id)
	{
		$dsd_id = (int) $data_structure_id;
		if ($dsd_id <= 0) {
			return;
		}
		$q = $this->db->select('id')->from('surveys')->where('data_structure_id', $dsd_id)->get();
		$sids = $q ? array_map('intval', array_column($q->result_array(), 'id')) : [];
		$csv  = $this->build_ts_dimensions_csv_for_structure_id($dsd_id);
		$freq = $this->build_ts_frequency_for_structure_id($dsd_id);
		$this->db->where('data_structure_id', $dsd_id);
		$this->db->update('surveys', [
			'ts_sync_required' => 0,
			'ts_dimensions'    => $csv,
			'ts_frequency'     => $freq,
			'changed'          => date('U'),
		]);
		$this->_emit_survey_refresh_events_for_sids($sids);
	}

	/**
	 * @param int[] $sids
	 */
	protected function _emit_survey_refresh_events_for_sids(array $sids)
	{
		$sids = array_values(array_unique(array_map('intval', $sids)));
		$sids = array_filter($sids, function ($x) {
			return $x > 0;
		});
		if ($sids === []) {
			return;
		}
		$CI = &get_instance();
		if (!isset($CI->events) || !is_object($CI->events)) {
			return;
		}
		foreach ($sids as $sid) {
			$CI->events->emit('db.after.update', 'surveys', $sid, 'refresh');
		}
	}

}//end-class
	
