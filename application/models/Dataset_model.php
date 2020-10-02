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
		'abbreviation',
		'authoring_entity',
		'nation',
		'dirpath',
		'metafile',
		'year_start',
		'year_end',
		'link_da',
		'published',
		'created',
		'changed',
		'varcount',
		'total_views',
		'total_downloads',
		'created',
		'changed',
		'created_by',
		'changed_by',
		'formid',
		'metadata',
		'link_study',
		'link_indicator',
		'thumbnail'
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
		'formid'
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

	}
	
	
	//return all datasets
	function get_all($sid=null)
	{
		$this->db->select(implode(",",$this->listing_fields));

		if($sid){
			$this->db->where('id',$sid);
		}
		$result= $this->db->get("surveys")->result_array();

		if($result){
			return $this->decode_encoded_fields_rows($result);
		}

		return false;
	}


	//return IDNO
	function get_idno($sid)
	{
		$this->db->select("idno");
		$this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();
        return $survey;
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
		$this->db->select("id,repositoryid,type,idno,title,year_start,
			year_end,nation,published,authoring_entity,
			created, changed, varcount, 
			total_views, total_downloads, surveys.formid,forms.model as data_access_type,
			link_da as remote_data_url, link_study, link_questionnaire, 
			link_indicator, link_technical, link_report");

		$this->db->join('forms','surveys.formid=forms.formid','left');
		$this->db->where("id",$sid);
		
		$survey=$this->db->get("surveys")->row_array();
		
		if($survey){
			$survey=$this->decode_encoded_fields($survey);
		}

        return $survey;
	}

	//return survey with metadata and other fields
	function get_row_detailed($sid)
	{
		$this->db->select("surveys.*, forms.model as data_access_type");		
		$this->db->join('forms','surveys.formid=forms.formid','left');
        $this->db->where("id",$sid);
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
        $this->db->select("metadata");
        $this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();

        if ($survey){
            return $this->decode_metadata($survey['metadata']);
        }
	}
	
	public function set_metadata($sid, $metadata)
	{
		return $this->update($sid, array('metadata'=>$metadata));
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
                return false;
            }
            $reference = $reference[$key];
        }
        return $reference;
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

		//keywords
		if (!isset($data['keywords'])){
			$data['keywords']=str_replace("\n","",$this->array_to_plain_text($options['metadata']));
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

		foreach($options as $key=>$value){
			if (in_array($key,$this->survey_fields) ){
				$data[$key]=$value;
			}
		}

		//keywords
		if (!isset($data['keywords']) && isset($data['metadata'])){
			$data['keywords']=str_replace("\n","",$this->array_to_plain_text($data['metadata']));
		}

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}		
		
		//update study
		$this->db->where('id',$sid);
		$result=$this->db->update('surveys', $data); 

		if ($result===false){
			$error=$this->db->error('message');
			throw new Exception("DB-ERROR: ".$error['message']);
		}

		return $sid;
	}



	function array_to_plain_text($data)
	{
		$output = array();
        $item = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
        foreach($item as $value) {
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


	function has_datafiles($sid){
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
			throw new Exception("INVALID_YEAR_RANGE");
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$start=$end;
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
		
		$this->update($sid,$options);
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
		return get_catalog_root() . '/'.$this->get_dirpath($sid);
	}

	function get_metadata_file_path($sid)
	{
		$this->db->select('dirpath,metafile');
		$this->db->where('id', $sid);
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return get_catalog_root() . '/'. $query['dirpath'].'/'.$query['metafile'];
		}
		
		return false;
	}

    
    /**
	* returns internal survey id by IDNO
	* checks for ID in both surveys and aliases table
	**/
	function find_by_idno($idno)
	{
		$this->db->select('id');
		$this->db->where('idno', $idno); 
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
		
		return false;
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
		$this->load->model("Resource_model");
		return $this->Resource_model->import_rdf($sid,$filepath);
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
		return $this->update($sid,$options);
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
		var_dump($idno);
		
		$sid=null;
		if(array_key_exists('sid',$this->form_validation->validation_data)){
			$sid=$this->form_validation->validation_data['sid'];
		}

		var_dump($sid);
		echo "=======";

		//check if the survey id already exists
		$id=$this->find_by_idno($idno);	

		var_dump($id);
		echo "=======";

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


}//end-class
	
