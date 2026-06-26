<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Timeseries database
 * 
 */
class Timeseries_db_model extends CI_Model {
 
	private $db_fields=array(
		'id',
		'idno',
		'title',
		'abbreviation',
		'published',
		'created',
		'changed',
		'created_by',
		'changed_by',
		'metadata',
		'thumbnail'
		);

	private $encoded_fields=array(
		"metadata"
	);

 
    public function __construct()
    {
		parent::__construct();
		$this->load->library("form_validation");		
	}
	
	
	function get_all()
	{
		$this->db->select('id,idno,title,created,changed,published');
		$this->db->where('type','timeseriesdb');
		$result= $this->db->get("surveys")->result_array();
        return $result;
	}

    function get_row($id) 
	{
        $this->db->select('id,idno,title,created,changed,published,metadata,type');
        $this->db->where('id', $id);
		$result= $this->db->get("surveys")->row_array();

		if($result){
			if (!in_array($result['type'], array('timeseriesdb','timeseries-db'))){
				return false;
			}
			$result=$this->decode_encoded_fields($result);
		}

        return $result;
	}

	function get_row_by_idno($idno)
	{
		$this->db->select('*');
        $this->db->where('idno', $idno);
		$this->db->where_in('type', array('timeseriesdb','timeseries-db'));
		$result= $this->db->get("surveys")->row_array();

		if($result){
			$result=$this->decode_encoded_fields($result);
		}

        return $result;
	}

	function get_database_by_series_id($sid)
	{
		$this->load->model('Dataset_timeseries_model');
		$database_id=$this->Dataset_timeseries_model->get_timeseries_db_id($sid);

		if (empty($database_id)){
			return false;
		}

		if (is_numeric($database_id)){
			return $this->get_row((int)$database_id);
		}

		return $this->get_row_by_idno($database_id);
	}
	

	function find_by_idno($idno)
	{
		$this->db->select('id');
		$this->db->where('idno', $idno); 
		$this->db->where_in('type', array('timeseriesdb','timeseries-db'));
		$query=$this->db->get('surveys')->row_array();
		
		if ($query){
			return $query['id'];
		}

        return false;
	}


	
	/**
	 * 
	 * get core fields 
	 * 
	 * core fields are: idno, title, nation, year, authoring_entity
	 * 
	 * 
	 */
	function get_core_fields($options)
	{        
        $output=array();
        $output['title']=$this->get_array_nested_value($options,'database_description/title_statement/title');
        $output['idno']=$this->get_array_nested_value($options,'database_description/title_statement/idno');
        return $output;
	}
	



	function create_database($options)
	{
		//validate schema
		$this->validate_schema($options);	
		
        //get core fields for listing datasets in the catalog
		$core_fields=$this->get_core_fields($options);
		
        $options=array_merge($options,$core_fields);
		
		if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
			throw new exception("IDNO-NOT-SET");
		}

		//validate IDNO field
        $id=$this->find_by_idno($core_fields['idno']); 

		//overwrite?
		/*if($id>0 && isset($options['overwrite']) && $options['overwrite']=='yes'){
			return $this->update_dataset($id,$type,$options);
		}*/

		if($id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$id);
        }
        
        $study_metadata_sections=array('database_description','additional');

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }
								
		//start transaction
		$this->db->trans_start();

		$database_id=null;		
        
        if($id>0){
            //update
            $database_id=$this->update($id,$options);
        }
        else{
		    //insert 
            $database_id=$this->insert($options);
        }

		$this->db->trans_complete();

		return $database_id;
	}


	/**
	 * Upsert timeseries_db_links rows from database_description.indicators[].
	 * Called when a timeseriesdb entry is created or updated.
	 * Only adds rows — never removes (series saves manage their own rows).
	 */
	function sync_series_links($db_idno, $metadata)
	{
		$db_idno = trim((string)$db_idno);
		if ($db_idno === '' || !is_array($metadata)) {
			return;
		}

		$indicators = isset($metadata['database_description']['indicators'])
			? $metadata['database_description']['indicators']
			: array();

		if (!is_array($indicators) || count($indicators) === 0) {
			return;
		}

		foreach ($indicators as $indicator) {
			$idno = isset($indicator['id']) ? trim((string)$indicator['id']) : '';
			if ($idno === '') {
				continue;
			}

			$row = $this->db->select('id')->where('idno', $idno)->where('type', 'timeseries')->get('surveys')->row_array();
			if (!$row) {
				continue;
			}

			$this->db->query(
				'INSERT IGNORE INTO timeseries_db_links (series_id, db_idno, is_primary) VALUES (?, ?, 0)',
				array((int)$row['id'], $db_idno)
			);
		}
	}


	/**
	 * Get timeseries series linked to a database, with pagination.
	 */
	function get_series_by_db_idno($db_idno, $limit = 20, $offset = 0)
	{
		$this->db->select('s.id, s.idno, s.title, s.published, s.nation, s.year_start, s.year_end, s.authoring_entity, s.abstract, s.thumbnail, s.changed, s.ts_dimensions');
		$this->db->from('surveys s');
		$this->db->join('timeseries_db_links l', 'l.series_id = s.id');
		$this->db->where('l.db_idno', $db_idno);
		$this->db->where('s.type', 'timeseries');
		$this->db->order_by('s.title', 'ASC');
		$this->db->limit((int)$limit, (int)$offset);
		return $this->db->get()->result_array();
	}


	/**
	 * Count timeseries series linked to a database.
	 */
	function count_series_by_db_idno($db_idno)
	{
		$this->db->from('surveys s');
		$this->db->join('timeseries_db_links l', 'l.series_id = s.id');
		$this->db->where('l.db_idno', $db_idno);
		$this->db->where('s.type', 'timeseries');
		return $this->db->count_all_results();
	}




    /**
	*
	* create new timeseries database 
	*
	* @options - array()
	*/
	function insert($options)
	{
		$data=array();

		//default values, if no values are passed in $options
		$data['created']=date("U");
		$data['changed']=date("U");

		foreach($options as $key=>$value){
			if (in_array($key,$this->db_fields) ){
				$data[$key]=$value;
			}
		}
		
		$data['type'] = 'timeseriesdb';

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}

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
	* create new timeseries database 
	*
	* @options - array()
	*/
	function update($id,$options)
	{
		$data=array();
		
		foreach($options as $key=>$value){
			if (in_array($key,$this->db_fields) ){
				$data[$key]=$value;
			}
		}

		$data['changed']=date("U");
		
		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}		

		$this->db->where('id', $id);
		$this->db->where_in('type', array('timeseriesdb', 'timeseries-db'));
		$result=$this->db->update('surveys', $data);

		if ($result===false){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));
		}

		return $id;
	}
    

    /**
	 * 
	 * Delete by IDNO
	 * 
	 */
	function delete_by_idno($idno)
	{
		//get internal ID by IDNO
		$sid=$this->find_by_idno($idno);

		if($sid){
			return $this->delete($sid);
		}

		return false;
	}


	/**
	* 
	* Delete
	*
	*/
	function delete($id)
	{
		$this->db->where('id', $id); 
        $this->db->where_in('type', array('timeseriesdb','timeseries-db'));
        $deleted=$this->db->delete('surveys');

        return $deleted;
    }



    function validate_schema($data)
	{
        $type='timeseries-db';
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

}