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
		$result= $this->db->get("ts_databases")->result_array();
        return $result;
	}

    function get_row($id) 
	{
        $this->db->select('id,idno,title,created,changed,published,metadata');
        $this->db->where('id', $id);
		$result= $this->db->get("ts_databases")->row_array();

		if($result){
			$result=$this->decode_encoded_fields($result);
		}

        return $result;
	}

	function get_row_by_idno($idno)
	{
		$this->db->select('*');
        $this->db->where('idno', $idno);
		$result= $this->db->get("ts_databases")->row_array();

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
		
		return $this->get_row_by_idno($database_id);
	}
	

	function find_by_idno($idno)
	{
		$this->db->select('id');
		$this->db->where('idno', $idno); 
		$query=$this->db->get('ts_databases')->row_array();
		
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
		
		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($data[$field])){
				$data[$field]=$this->encode_metadata($data[$field]);
			}
		}		

		$result=$this->db->insert('ts_databases', $data); 

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

		$this->db->where('id',$id);
		$result=$this->db->update('ts_databases', $data); 

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
		$sid=$this->get_id_by_idno($idno);

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
        $deleted=$this->db->delete('ts_databases');

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