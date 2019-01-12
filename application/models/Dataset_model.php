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

	private $dataset_core_sections=array(
		'survey'=>array(
			'study'=>array('doc_desc','study_desc','additional'),
			'data_files'=>array('data_files'),
			'variables'=>array('variables'),
			'variable_groups'=>array('variable_groups')
		),
		'timeseries'=>array(
			'study'=>array('metadata_creation','database_description','additional'),
			'data_files'=>array('data_files','database'),
			'variables'=>array('indicators'),
			'variable_groups'=>array('indicator_groups')
		),
		'geospatial'=>array(
			'study'=>array('metadata_maintenance','dataset_description','additional'),
			'data_files'=>array(),
			'variables'=>array(''),
			'variable_groups'=>array('')
		),
		'document'=>array(
			'study'=>array('metadata_information','document_description','additional'),
			'data_files'=>array(),
			'variables'=>array(),
			'variable_groups'=>array()
		),
		'table'=>array(
			'study'=>array('metadata_information','table_description','additional'),
			'data_files'=>array(),
			'variables'=>array(),
			'variable_groups'=>array()
		),
		'image'=>array(
			'study'=>array('metadata_information','image_description','additional'),
			'data_files'=>array(),
			'variables'=>array(),
			'variable_groups'=>array()
		)		
	);
 
    public function __construct()
    {
		parent::__construct();
		$this->load->library("form_validation");		
		//$this->output->enable_profiler(TRUE);
		$this->load->model("Survey_country_model");
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
		$this->db->select("id,repositoryid,type,idno,title,year_start, year_end,nation,published,created, changed, varcount, total_views, total_downloads, surveys.formid,forms.model as data_access_type,link_da as remote_data_url, link_study, link_questionnaire, link_indicator, link_technical, link_report");		
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
				Constraint::CHECK_MODE_TYPE_CAST + Constraint::CHECK_MODE_COERCE_TYPES);

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
	 * get core fields 
	 * 
	 * core fields are: idno, title, nation, year
	 * 
	 * 
	 */
	function get_core_fields($type,$options)
	{
		$output=array();
		
		switch($type)
		{
			case 'timeseries':
				$output=$options;

				//title
				$output['title']=$this->get_array_nested_value($options,'database_description/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'idno');

				//nation - get nation name or comma separated nation names if multiple
				$nations=$this->get_countries_by_type($type,$options);

				$nation_str=implode(", ",$nations);

				if(strlen($nation_str)>150){
					$nation_str=substr($nation_str,0,145).'...';
				}

				$output['nation']=$nation_str;

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'database_description/abbreviation');

				//authoring entity
				$output['authoring_entity']=$this->authoring_entity_to_string($this->get_array_nested_value($options,'database_description/authoring_entity'));

				//year_start, year_end
				$years=$this->ddi_transform_years($type,$options);
				$output['year_start']=$years['start'];
				$output['year_end']=$years['end'];
			break;

			case 'survey':

				$output=$options;

				$output['study_desc']=null;
				$output['doc_desc']=null;

				//title
				$output['title']=$this->get_array_nested_value($options,'study_desc/title_statement/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'study_desc/title_statement/idno');

				//nation - get nation name or comma separated nation names if multiple
				$nations=$this->get_countries_by_type($type,$options);
				$nation_str=implode(", ",$nations);

				if(strlen($nation_str)>150){
					$nation_str=substr($nation_str,0,145).'...';
				}

				$output['nation']=$nation_str;

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'study_desc/title_statement/alternate_title');

				//authoring entity
				$output['authoring_entity']=$this->authoring_entity_to_string($this->get_array_nested_value($options,'study_desc/authoring_entity'));

				//year_start, year_end
				$years=$this->ddi_transform_years($type,$options);
				$output['year_start']=$years['start'];
				$output['year_end']=$years['end'];			
			break;

			case 'script':
				$output=$options;

				//title
				$output['title']=$this->get_array_nested_value($options,'project_desc/title_statement/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'project_desc/title_statement/idno');

				//nation - get nation name or comma separated nation names if multiple
				$nations=(array)$this->get_countries_by_type($type,$options);

				$nation_str=implode(", ",$nations);

				if(strlen($nation_str)>150){
					$nation_str=substr($nation_str,0,145).'...';
				}

				$output['nation']=$nation_str;

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'abbreviation');

				//authoring entity
				$output['authoring_entity']=$this->authoring_entity_to_string($this->get_array_nested_value($options,'project_desc/authoring_entity'));

				//year_start, year_end
				$years=$this->ddi_transform_years($type,$options);
				$output['year_start']=$years['start'];
				$output['year_end']=$years['end'];
			break;
			case 'document':
			
				$output=$options;

				$output['metadata_maintenance']=null;
				$output['document_description']=null;

				//title
				$output['title']=$this->get_array_nested_value($options,'document_description/title_statement/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'document_description/title_statement/idno');

				//country
				$output['nation']='';

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'document_description/title_statement/alternate_title');

				//authoring entity
				$output['authoring_entity']=$this->get_array_nested_value($options,'document_description/publisher');

				//year_start, year_end
				$years=explode("/",$this->get_array_nested_value($options,'document_description/date_published'));

				if(is_array($years)){
					$output['year_start']=(int)$years[0];
					$output['year_end']=(int)$years[0];			
				}
			break;
			case 'table':
			
				$output=$options;

				$output['metadata_maintenance']=null;
				$output['document_description']=null;

				//title
				$output['title']=$this->get_array_nested_value($options,'table_description/title_statement/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'table_description/title_statement/idno');

				//country
				$output['nation']='';

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'table_description/title_statement/alternate_title');

				//authoring entity
				$authoring_entity=$this->get_array_nested_value($options,'table_description/publisher');

				if(is_array($authoring_entity)){
					$output['authoring_entity']=implode(", ", array_column($authoring_entity,'name'));
				}

				//year_start, year_end
				$years=explode("/",$this->get_array_nested_value($options,'table_description/date_published'));

				if(is_array($years)){
					$output['year_start']=(int)$years[0];
					$output['year_end']=(int)$years[0];			
				}
			break;

			case 'image':
			
				$output=$options;

				$output['metadata_information']=null;
				$output['image_description']=null;

				//title
				$output['title']=$this->get_array_nested_value($options,'image_description/identification/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'image_description/identification/digital_image_guid');

				//country
				$output['nation']='';

				//abbreviation
				$output['abbreviation']='';

				//authoring entity
				$output['authoring_entity']=$this->get_array_nested_value($options,'creation/creator_name');

				//year_start, year_end
				$date=explode("-",$this->get_array_nested_value($options,'image_description/identification/date_created'));

				if(is_array($date)){
					$output['year_start']=(int)$date[0];
					$output['year_end']=(int)$date[0];			
				}
			break;
			case 'geospatial':
			
				$output=$options;

				$output['metadata_information']=null;
				$output['dataset_description']=null;

				//title
				$output['title']=$this->get_array_nested_value($options,'dataset_description/identification_info/title');

				//idno
				$output['idno']=$this->get_array_nested_value($options,'dataset_description/file_identifier');

				//country
				$output['nation']='';

				//abbreviation
				$output['abbreviation']=$this->get_array_nested_value($options,'dataset_description/identification_info/alternate_title');

				//authoring entity
				$output['authoring_entity']='';//$this->get_array_nested_value($options,'dataset_description/identification_info/contact');

				//year_start, year_end
				$years=explode("-",$this->get_array_nested_value($options,'dataset_description/date_stamp'));

				if(is_array($years)){
					$output['year_start']=(int)$years[0];
					$output['year_end']=(int)$years[0];			
				}
			break;			
		}

		return $output;
	}


	/**
	 * 
	 * convert authoring entity array to string
	 * 
	 * authoring entity format = {name, affiliation}
	 * 
	 **/
	function authoring_entity_to_string($authoring_entity, $max_length=300)
	{
		if(!is_array($authoring_entity)){
			return '';
		}

		$names=array_column($authoring_entity, 'name');

		$names=implode(", ", $names);

		if (strlen($names) <=$max_length) {
			return $names;
		}

		//trim to max limit
		$wrapped=wordwrap($names, $max_length);
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


	function create_dataset($type,$options)
	{
		//validate core fields
		//$this->validate($type,$options);		

		//validate schema
		$this->validate_schema($type,$options);

		//get core fields for listing datasets in the catalog
		$data=$this->get_core_fields($type,$options);
		
		if(!isset($data['idno']) || empty($data['idno'])){
			throw new exception("IDNO-NOT-FOUND");
		}

		//validate IDNO field
		$id=$this->find_by_idno($data['idno']);

		//overwrite?
		if($id>0 && isset($options['overwrite']) && $options['overwrite']=='yes'){
			return $this->update_dataset($id,$type,$options);
		}

		if(is_numeric($id) ){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$id);
		}

		$data_files=null;
		$variables=null;
		$variable_groups=null;

		//study level metadata for the dataset type
		foreach($options as $key=>$value){		
			if(in_array($key,$this->dataset_core_sections[$type]['study'])){
				$data['metadata'][$key]=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['data_files'])){
				$data_files=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['variables'])){
				$variables=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['variable_groups'])){
				$variable_groups=$value;
			}
		}

		//start transaction
		$this->db->trans_start();
		
		//metadata field - all fields are stored
		//$data['metadata']=$options;
		
		//insert record
		$dataset_id=$this->insert($type,$data);

		//update years
		$this->update_years($dataset_id,$data['year_start'],$data['year_end']);

		//set topics

		//get list of countries
		$countries=$this->get_countries_by_type($type,$options);

		//update countries
		$this->Survey_country_model->update_countries($dataset_id,$countries);

		//set aliases

		//set geographic locations (bounding box)


		//data files?
		if(is_array($data_files)){
			//create each data file
			foreach($data_files as $data_file){
				$this->Data_file_model->validate_data_file($data_file);
				$file_id=$this->Data_file_model->insert($dataset_id,$data_file);
			}
		}


		//variables?
		//create variables
		if(is_array($variables)){
			foreach($variables as $variable){
				//validate file_id exists
				$fid=$this->Data_file_model->get_fid_by_fileid($dataset_id,$variable['file_id']);
		
				if(!$fid){
					throw new exception("variable creation failed. Variable 'file_id' not found: ".$variable['file_id']);
				}
							
				$variable['fid']=$variable['file_id'];
				$this->Variable_model->validate_variable($variable);
			}

			$result=array();
			foreach($variables as $variable)
			{
				$variable['fid']=$variable['file_id'];
				//all fields are stored as metadata
				$variable['metadata']=$variable;
				$variable_id=$this->Variable_model->insert($dataset_id,$variable);
			}

			//update survey varcount
			$this->update_varcount($dataset_id);
		}	
		

		//variable groups?
		//todo

		//complete transaction
		$this->db->trans_complete();

		return $dataset_id;
	}


	function update_dataset($sid,$type,$options)
	{
		//need this to validate IDNO for uniqueness
		$options['sid']=$sid;

		//validate core fields
		//$this->validate($type,$options);

		//validate schema
		$this->validate_schema($type,$options);

		//get core fields for listing datasets in the catalog
		$data=$this->get_core_fields($type,$options);

		//validate IDNO field
		$new_id=$this->find_by_idno($data['idno']);

		//if IDNO is changed, it should not be an existing IDNO
		if(is_numeric($new_id) && $sid!=$new_id ){
			throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$new_id.':'.$data['idno']);
		}
		
		
		$data_files=null;
		$variables=null;
		$variable_groups=null;

		//study level metadata for the dataset type
		foreach($options as $key=>$value){		
			if(in_array($key,$this->dataset_core_sections[$type]['study'])){
				$data['metadata'][$key]=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['data_files'])){
				$data_files=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['variables'])){
				$variables=$value;
			}

			if(in_array($key,$this->dataset_core_sections[$type]['variable_groups'])){
				$variable_groups=$value;
			}
		}

		//metadata field - all fields are stored
		//$data['metadata']=$options;		

		//calculate year_start, year_end
		//$years=$this->ddi_transform_years($type,$options);

		//$options['year_start']=$years['start'];
		//$options['year_end']=$years['end'];

		//start transaction
		$this->db->trans_start();

		//update
		$this->update($sid,$type,$data);

		//update years
		$this->update_years($sid,$data['year_start'],$data['year_end']);

		//set topics

		//set countries
		$countries=$this->get_countries_by_type($type,$options);
		$this->Survey_country_model->update_countries($sid,$countries);

		//set aliases

		//set geographic locations (bounding box)

		$dataset_id=$sid;

		//data files?
		if(is_array($data_files) && $type=='survey' && isset($data_files['file_desc'])){
			//create each data file
			foreach($data_files['file_desc'] as $data_file){					
				$this->Data_file_model->validate_data_file($data_file);
				//check if file already exists?
				$file=$this->Data_file_model->get_file_by_id($dataset_id,$data_file['file_id']);
				if($file){
					$this->Data_file_model->update($file['id'],$data_file);
				}else{
					$this->Data_file_model->insert($dataset_id,$data_file);
				}
			}
		}

		//variables?
		//create variables
		if(is_array($variables) && $type='survey' && isset($variables['variable'])){
			foreach($variables['variable'] as $variable){
				//validate file_id exists
				$fid=$this->Data_file_model->get_fid_by_fileid($dataset_id,$variable['file_id']);
		
				if(!$fid){
					throw new exception("variable creation failed. Variable 'file_id' not found: ".$variable['file_id']);
				}
							
				$variable['fid']=$variable['file_id'];
				$this->Variable_model->validate_variable($variable);
			}

			$result=array();
			foreach($variables['variable'] as $variable)
			{
				$variable['fid']=$variable['file_id'];
				//all fields are stored as metadata
				$variable['metadata']=$variable;
				$variable_id=$this->Variable_model->insert($dataset_id,$variable);
			}

			//update survey varcount
			$this->update_varcount($dataset_id);
		}
		
		//complete transaction
		$this->db->trans_complete();

		return $sid;
	}
	


	/**
	*
	* insert new dataset and return new dataset id
	*
	* @options - array()
	*/
	private function insert($type,$options)	
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
	private function update($sid,$type,$options)	
	{
		$options['type']=$type;

		/*
		note: dirpath should not be set in insert/update
		$dir_path=$this->get_dirpath($sid);

		if(!$dir_path){
			$dir_path=$this->setup_folder($repositoryid, $folder_name=md5($options['idno']));
		}

		//set dataset storage folder path
		$options['dirpath']=$dir_path;
		*/

		//transform fields to map to core and metadata columns
		//$options=$this->map_fields($type,$options);
						
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

	
	
	//apply field mappings
	function map_fields($type,$options)
	{
        $core=array();
		$metadata=array();
		$mappings=$this->get_core_mappings($type);

        foreach($options as $key=>$value){
            if(array_key_exists($key,$mappings)){
				$core[$mappings[$key]]=$value;	
			}else if(in_array($key,$this->survey_fields)){
				$core[$key]=$value;
			}
			else{
                $metadata[$key]=$value;
            }
		}
		
        $core['metadata']=$metadata;
        return $core;
	}


	
	//return core fields mappings by type
	function get_core_mappings($type)
	{
		//map fields to core db fields if field names are different
		$core_fields=array(
			'survey'=>array(
				'title'=>'title',
				'idno'=>'idno',
				'nation'=>'nation',
			),
			'timeseries'=>array(
				'title'=>'title',
				'idno'=>'idno',
				'nation'=>'nation',
			),
			'geospatial'=>array(
				'title'=>'title',
				'idno'=>'idno',
				'nation'=>'nation',
			)
		);

		return $core_fields[$type];
	}



	//get data collection years from a ddi data collection element
	function ddi_transform_years($type,$options)
	{
		$years=array();

		switch($type){
			case 'survey':
			$data_coll=$this->get_array_nested_value($options,'study_desc/study_info/coll_dates');

			if (is_array($data_coll)){
				//get years from data collection dates				
				foreach($data_coll as $row){
					$year_=substr($row['start'],0,4);
					if((int)$year_>0){
						$years[]=$year_;
					}					
					if(isset($row['end'])){
						$year_=substr($row['end'],0,4);
						if((int)$year_>0){
							$years[]=$year_;
						}
					}
				}
			}

			break;

			case 'geospatial':
			break;

			case 'timeseries':
				$data_coll=$this->get_array_nested_value($options,'study_desc/time_coverage');
			
				if (is_array($data_coll)){
					//get years from data collection dates				
					foreach($data_coll as $row){
						$year_=substr($row['start'],0,4);
						if((int)$year_>0){
							$years[]=$year_;
						}					
						if(isset($row['end'])){
							$year_=substr($row['end'],0,4);
							if((int)$year_>0){
								$years[]=$year_;
							}
						}
					}
				}
			break;
		}

		$start=0;
		$end=0;
		
		if (count($years)>0){
			$start=min($years);
			$end=max($years);
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$start=$end;
		}

		return array(
			'start'=>$start,
			'end'=>$end
		);
	}


	
	/**
	 * 
	 * Get countries from ddi nation/geog_units and geog_coverage fields
	 *  
	 * Returns an array of countries + country ID
	 * 
	 * 
	 * 
	 **/ 
	function get_ddi_countries($options)
	{
		$countries=array();

		//var_dump($options);die();

		$nations=$this->get_array_nested_value($options,'study_desc/study_info/nation');

		$nation_names=array();
		foreach($nations as $nation){
			$nation_names[]=$nation['name'];
		}

		return $nation_names;		
	}

	

	//return the countries list by dataset type
	function get_countries_by_type($type,$options)
	{
		switch($type){
			case 'survey':
				$nations=$this->get_array_nested_value($options,'study_desc/study_info/nation');				
				$nation_names=array();
				foreach($nations as $nation){
					$nation_names[]=$nation['name'];
				}	
				return $nation_names;	
			break;

			case 'timeseries':
				$nations=$this->get_array_nested_value($options,'database_description/geographic_units');				
				$nation_names=array();
				foreach($nations as $nation){
					$nation_names[]=$nation['name'];
				}	
				return $nation_names;
			break;
		}
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
    
	
    
    //@topics array(topic, vocab, uri)
    function update_survey_topics($sid,$topics)
	{
        //TODO
	}
	
	
	/**
	*
	* Set the owner repo for the dataset
	*/
	function set_dataset_owner_repo($sid,$repositoryid)
	{
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

	

}//end-class
	
