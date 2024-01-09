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
		'formid',
		'metadata',
		'link_study',
		'link_indicator',
		'thumbnail',
		'doi'
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
		'formid',
		'doi'
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

		$this->db->select("surveys.id, surveys.doi, surveys.repositoryid,surveys.type,surveys.idno,surveys.title,surveys.year_start, 
			year_end,nation,surveys.authoring_entity,published,created, changed, varcount, total_views, total_downloads, 
			surveys.formid,forms.model as data_access_type,link_da as remote_data_url, 
			surveys.data_class_id, data_classifications.code as data_class_code, data_classifications.title as data_class_title,
			surveys.thumbnail, link_study, link_indicator, link_report");
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
				'type'=>'DOI',				
				'identifier'=>$doi
			];

			if (!is_array($identifiers)){
				set_array_nested_value($metadata,$mappings[$type],$doi_identifier,"/");
			}

			//check if DOI already exists
			foreach($identifiers as $identifier){
				if ($identifier['type']=='DOI' && $identifier['identifier']==$doi){
					return;
				}
			}

			$identifiers[]=$doi_identifier;
			set_array_nested_value($metadata,$mappings[$type],$identifiers,"/");
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
	 * 
	 * Return survey keywords
	 * 
	 */
	function get_keywords($sid)
	{
		$this->db->select("keywords,var_keywords");
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

		//keywords
		if (isset($data['metadata']) && !isset($data['keywords'])){
			//$keywords=str_replace("\n","",$this->array_to_plain_text($options['metadata']));
			$data['keywords']=$this->extract_keywords($data['metadata'],$type);			
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

		//keywords
		if (!isset($data['keywords']) && isset($data['metadata'])){
			$data['keywords']=$this->extract_keywords($data['metadata'],$type);
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


	function extract_var_keywords($keywords)
	{				
		$keywords=str_replace(array("\n","\r", ")", "(","?",",","/","\\")," ",$this->array_to_plain_text($keywords));

		$noise_words=explode(",",
			'about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,
			being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,
			got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,like,
			make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,
			our,out,over,said,same,see,should,since,some,still,such,take,than,that,
			the,their,them,then,there,these,they,this,those,through,to,too,under,up,
			very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,not			
			b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_'
		);
		$noise_words=array_map('trim',$noise_words);

		$keywords= strtolower(preg_replace('/\b('.implode('|',$noise_words).')\b/i','',$keywords));
		
		$patterns=array(
		 	'/\b\d+\b/u', //remove numbers not part of the any words
			 '/\b[a-z]{1,2}\b/'//words length <3
		);

		foreach($patterns as $regex){
			$keywords= preg_replace($regex, '', $keywords);
		}

		return $keywords;
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

		if(empty($options)){
			return false;
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
	**/
	function find_by_idno($idno)
	{
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
        
		foreach ($tags as $tag){
            $options=array(
					'sid'	=>$sid,
					'tag'	=>$tag
				);
            $this->db->insert('survey_tags',$options);
		}
	}


	function add_survey_tags($sid, $tags=array())
	{
		$this->load->model("Catalog_tags_model");
				
        if (!is_array($tags)){
            return;
        }

		$existing_tags=(array)$this->Catalog_tags_model->survey_tags_list($sid);

		//remove duplicates or null
		$tags=array_unique(array_filter($tags));
		$tags=array_diff($tags, $existing_tags);
        
		foreach ($tags as $tag){
            $options=array(
					'sid'	=>$sid,
					'tag'	=>$tag
				);
            $this->db->insert('survey_tags',$options);
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

		foreach($aliases as $alias){
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
        return $ddi_path;
    }

	function write_json($sid,$overwrite=false)
	{
		$this->load->library("DDI_Writer");
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');
		$this->load->model('Variable_group_model');
        $dataset=$this->get_row($sid);

        $study_path=$this->get_storage_fullpath($sid);

		if(!$study_path){
			throw new Exception("STUDY_FOLDER_NOT_SET");
		}

		$json_path=$study_path.'/'.$dataset['idno'].'.json';

		//create project folder if not exists
		if(!file_exists($study_path)){
			mkdir($study_path);
		}

		//data has changed, overwrite file
		if (file_exists($json_path) && filemtime($json_path) < $dataset['changed']){
			$overwrite=true;
		}

        if(file_exists($json_path) && $overwrite==false){
            throw new Exception("JSON_FILE_EXISTS");
        }

		//$fp = fopen('php://output', 'w');
		$fp = fopen($json_path, 'w');

		$metadata=$this->get_metadata($sid);
		$basic_info=array(
			'type'=>$dataset['type']
		);
		
		$output=array_merge($basic_info, $metadata );

		if($dataset['type']=='survey'){
			$this->load->model("Data_file_model");
			$output['data_files'] = function () use ($sid) {
				$files=$this->Data_file_model->get_all_by_survey($sid);
				if ($files){
					foreach($files as $file){
						unset($file['id']);
						unset($file['sid']);
						yield $file;
					}
				}
			};

			$output['variables'] = function () use ($sid) {
				foreach($this->Variable_model->chunk_reader_generator($sid) as $variable){
					yield $variable['metadata'];
				}
			};

			$output['variable_groups'] = function () use ($sid) {
				$var_groups=$this->Variable_group_model->select_all($sid);
				foreach($var_groups as $var_group){
					yield $var_group;
				}			
			};
		}
		
		$encoder = new \Violet\StreamingJsonEncoder\StreamJsonEncoder(
			$output,
			function ($json) use ($fp) {
				fwrite($fp, $json);
			}
		);
		//$encoder->setOptions(JSON_PRETTY_PRINT);
		$encoder->encode();
		fclose($fp);

		return $json_path;
	}


	function download_metadata($sid,$format='json')
	{
		if ($format=='json'){
			return $this->download_metadata_json($sid);
		}
		else if ($format=='ddi'){
			return $this->download_metadata_ddi($sid);
		}
	}

	function download_metadata_ddi($sid)
	{
		$dataset=$this->Dataset_model->get_row($sid); 
		$ddi_path=$this->get_metadata_file_path($sid);

		/*$generate_file=false;
		if (file_exists($ddi_path) && filemtime($ddi_path) < $dataset['changed']){
			$generate_file=true;
		}
		
		if(!file_exists($ddi_path)){
			$generate_file=true;
		}*/

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

	function download_metadata_json($sid)
	{
		$dataset=$this->Dataset_model->get_row($sid);

		if (!$dataset){
			throw new Exception("STUDY_NOT_FOUND");
		}

		$study_path=$this->get_storage_fullpath($sid);
		$json_path=$study_path.'/'.$dataset['idno'].'.json';

		$generate_file=false;
		if (file_exists($json_path) && filemtime($json_path) < $dataset['changed']){
			$generate_file=true;
		}
		
		if(!file_exists($json_path)){
			$generate_file=true;
		}

		if($generate_file){
			try{
				$result=$this->write_json($sid,$overwrite=true);
			}
			catch(Exception $e){                    
				show_error($e->getMessage());
			}	
		}

		if(file_exists($json_path)){
			header("Content-type: application/json; charset=utf-8");
			$stdout = fopen('php://output', 'w');			
			$fh = fopen($json_path, 'r');
			stream_copy_to_stream($fh, $stdout);
			fclose($fh);
			fclose($stdout);
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

}//end-class
	
