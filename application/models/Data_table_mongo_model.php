<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;
use League\Csv\Reader;

require_once 'modules/mongodb/vendor/autoload.php';

class Data_table_mongo_model extends CI_Model {

    //table type object holds table definition[features, codelists, etc]
    private $table_type_obj=null;

    private $mongo_client;

    private $db_name;

    public function __construct() 
    {
        parent::__construct();
        $this->config->load('mongo');        
        $this->db_name=$this->config->item("mongodb_database");
        $this->mongo_client=$this->get_db_connection();
    }


    function get_db_connection()
    {
        $username=$this->config->item("mongodb_username");
        $password=$this->config->item("mongodb_password");
        $host=$this->config->item("mongodb_host");
        $port=$this->config->item("mongodb_port");


	    if (!empty($username) && !empty($password)){
            return new MongoDB\Client(
                "mongodb://${host}:${port}",
                    array(
                        "username" => $username, 
                        "password" => $password, 
                        "db"=> $this->get_db_name(), 
                        'authSource' => $this->get_db_name() 
            ));
        }
        
        return new MongoDB\Client(
            "mongodb://${host}:${port}",
                array(
                    "db"=> $this->get_db_name(), 
                )
        );

    }

    function get_mongo_manager()
    {
        $username=$this->config->item("mongodb_username");
        $password=$this->config->item("mongodb_password");
        $host=$this->config->item("mongodb_host");
        $port=$this->config->item("mongodb_port");

        $user_pass_str='';

        if(!empty($username) && !empty($password)){
            $user_pass_str=$username.':'.$password.'@';
        }

        $manager = new MongoDB\Driver\Manager("mongodb://${user_pass_str}${host}:${port}", 
            array("db" => $this->get_db_name()));
    
        return $manager;
    }

    /**
     * 
     * Set database for the application
     * 
     */
    function set_database($database_name)
    {
        $this->db_name=$database_name;
    }



    private function get_db_name()
    {
        if(empty($this->db_name)){
            throw new Exception("MongoDB Database not set, check application config for mongo.");
        }
        
        return $this->db_name;
    }

    
    private function get_table_name($db_id,$table_id)
    {
        return strtolower('table_'.$db_id.'_'.$table_id);
    }
    


    public function table_batch_insert($db_id,$table_id,$rows)
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        $insertManyResult = null;

        try {
            $insertManyResult = $collection->insertMany($rows,array('ordered' => false));
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw new Exception("ERROR::". utf8_encode($e->getMessage()));            
        }

        $inserted_count=$insertManyResult->getInsertedCount();
        

        if (!$inserted_count){
			throw new Exception($insertManyResult);
        }
        
        return $inserted_count;
    }



   //return storage info for all tables (collections) in db
   function get_tables_list()
   {
       $database=$this->mongo_client->{$this->get_db_name()};
       $cursor = $database->command(['listCollections' => 1, 'nameOnly'=> true ]);

       $output=array();
       foreach ($cursor as $collection) {
            $coll_stats=$this->get_collection_info($this->get_db_name(),$collection['name']);
            $output[$collection['name']]=array(
                'name'=>$collection['name'],
                'storageUnit'=>'M',
                'size'=>$coll_stats['size'],
                'count'=>$coll_stats['count'],
                'storageSize'=>$coll_stats['storageSize'],
                'nindexes'=>$coll_stats['nindexes'],
                'indexNames'=>array_keys((array)$coll_stats['indexDetails'])
            );
       }

       return $output;
   } 
   

   /**
    * 
    * Return collection info
    * 
    * 
    */
    function get_collection_info($db_name,$table_name)
    {
        $database = $this->mongo_client->{$db_name};
        $collection_info = $database->command(['collStats' => $table_name, 'scale'=> 1024*1024 ]);
        return $collection_info->toArray()[0];
    }


    /**
    * 
    * Rename collectinon
    * 
    * 
    */
    function rename_collection($old_name, $new_name)
    {
        $db_name=$this->get_db_name();
     
        //enable admin priviledges
        $admin = $this->mongo_client->admin;
     
        $result= $admin->command(array(
            'renameCollection'=> $db_name.'.'.$old_name,
            'to'=> $db_name.'.'.$new_name)
        );
        return $result->toArray()[0];
    }


    /**
    * 
    * 
    * Return features for a table
    * 
    */
   function get_table_features_list($db_id,$table_id,$features=array())
   {
       $table_id=strtolower($table_id);       
       $table=$this->get_table_type($db_id,$table_id);

       if(empty($table)){
           return array();
       }
       
       if(empty($features)){
        return $table['features'];
       }

       $output=array();

       foreach($table['features'] as $key=>$feature){
           if(in_array($feature['feature_name'],$features)){               
               $output[]=$feature;
           }
       }

       return $output;
   }

   /**
    * 
    * 
    * Return indicator codelist
    * 
    */
    function get_table_indicator_codelist($db_id,$table_id)
    {
        $table_id=strtolower($table_id);       
        $table=$this->get_table_type($db_id,$table_id);
        
        return isset($table['indicator']) ? $table['indicator']  : array();

        $output=array();
        
        foreach($table['indicator'] as $key=>$indicator){
            /*if(in_array($feature['feature_name'],$features)){               
                $output[]=$feature;
            }*/
        }
 
        return $output;
    }
   

   /**
    * 
    * Return table type information
    * 
    * 
    */
   function get_table_info($db_id,$table_id)
   {
       $table_id=strtolower($table_id);
       $result= $this->get_collection_info($this->get_db_name(),$this->get_table_name($db_id,$table_id));
       $result['table_type']=$this->get_table_type($db_id,$table_id);       
       return $result;
   }

   /**
    * 
    * Return the table definition
    *
    */
   function get_table_type($db_id,$table_id)
   {
       $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
       $result = $collection->findOne(
           [
               '_id'=>$this->get_table_name($db_id,$table_id)
           ]
       );
       
       return $result;
   }


   /**
    * 
    * Check if a table definition exists
    *
    */
   function table_type_exists($db_id,$table_id)
   {
       $type=$this->get_table_type($db_id,$table_id);

       if(empty($type) ){
           return false;
       };

       return true;
   }


   function get_table_types_list($db_id)
   {
      $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       $projection_options=[
            '_id'=>1,
            'db_id'=>1,
            'table_id'=>1,
            'title'=>1, 
            'description'=>1
       ];

       $filter_options=array();

       if (!empty($db_id)){
           $filter_options=array(
               'db_id' => $db_id
           );
       }

       $result = $collection->find(
           $filter_options,
           [    
               'projection'=>$projection_options
           ]           
       );
       
       $output=array();
       foreach($result as $item){
            $output[$item['_id']]=$item;
       }

       return $output;
   }


   function get_database_info()
   {
       $database=$this->mongo_client->{$this->get_db_name()};
       $db_info = $database->command(['dbStats' => 1, 'scale'=> 1024*1024 ]);
       return $db_info->toArray()[0];
   }


   function get_collection_indexes($db_id,$table_id)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};

        $indexes=array();
        foreach ($collection->listIndexes() as $index) {
            $indexes[$index->getName()]=$index->getKey();
        }

       // var_dump($indexes);
        return $indexes;
   }


   /**
    * 
    * Create index on a collection
    *
    * @index_options - comma seperated list of field names
    *
    */
   function create_collection_index($db_id,$table_id,$index_options)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};

        $index_options=array_filter(explode(",",$index_options));
        
        if(empty($index_options)){
            return false;
        }

        $indexes=array();
        foreach($index_options as $index){
            $indexes[$index]=1;
        }

        $result= $collection->createIndex($indexes);
        return $result;
   }


   /**
    * 
    * Create text index on a collection
    *
    * @index_options - comma seperated list of field names
    *
    */
    function create_collection_text_index($db_id,$table_id,$index_options)
    {
         $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
 
         $index_options=array_filter(explode(",",$index_options));
         
         if(empty($index_options)){
             return false;
         }
 
         $indexes=array();
         foreach($index_options as $index){
             $indexes[$index]='text';
         }
 
         $result= $collection->createIndex($indexes);
         return $result;
    }


   /**
    * 
    * Delete index on a collection
    *
    * @index_name - name of index
    *
    */
    function delete_collection_index($db_id,$table_id,$index_name)
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};          
        $result= $collection->dropIndex($index_name);
        return $result;
    }


   /**
	 * 
	 * 
	 * Get table data
	 * 
	 * Filters
	 * - table id
	 * - region_type
	 * - state_code
	 * - district_code
	 * - subdistrict_code
	 * - town_code
	 * - ward_code
	 * 
	 * - features
	 * 
	 * 
	 * 
	 * 
	 * 
	 */
   function get_table_data($db_id,$table_id,$limit=100,$offset=0,$options)
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>10000){
            $limit=100;
        }        

        $table_id=strtolower($table_id);

        //get table type info
        $this->table_type_obj= $this->get_table_type($db_id,$table_id);

        $fields=$this->get_table_field_names($db_id,$table_id);

        $features=$fields;
        $feature_filters=array();
        $filter_options=array();

        //see if any key matches with the feature name
        foreach($options as $key=>$value)
        {
            if(array_key_exists($key,$features)){
                 $filter_options[$key]=$value; //age=something
            }
        }
        
        $tmp_feature_filters=array();

        //filter by features
        foreach($filter_options as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value);
        }

        //fulltext query
        if(isset($options['ft_query']) && !empty($options['ft_query'])){
            $tmp_feature_filters['ft_query'][]['$text']=$this->text_search($options['ft_query']);
        }
        
        $feature_filters=array();

        if (!empty($tmp_feature_filters)){

            $feature_filters=array(
                '$and'=> array()
            );

            foreach($tmp_feature_filters as $feature_key=>$filter){
                $feature_filters['$and'][]['$or']=$filter;
            }
        }
        

        /*
        $feature_filters_example=array(

            '$and'=> array(
                array(
                '$or'=> array(
                    array( 
                        'sex' => array( '$in' => array(1) ), 
                        'age'=> array(
                            '$gte' => 10,
                            '$lte' => 15                    
                        )
                    ) 
                )
                )
            )
        );
        */

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};        

        if (!isset($options['fields'])){
            $options['fields']="";
        }

        //which fields to display
        $output_fields=$this->get_projection_fields($options['fields']);

        $cursor = $collection->find(
            $feature_filters,
            [
                'projection'=>$output_fields,
                'limit' => $limit,
                'skip'  => $offset
            ]
        );

        $output=array();

        if (isset($options['debug'])){
            $output['options']=$options;
            $output['features']=$features;        
            $output['feature_filters']=$feature_filters;
        }

        $output['rows_count']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;
        $output['found']=$collection->count($feature_filters);
        $output['total']=$collection->count();        
        $output['data']=array();
        
        foreach ($cursor as $document) {
            //convert to array from mongodb object
            $output['data'][]= iterator_to_array($document);
        }

        $output['rows_count']=count($output['data']);
        return $output;
   } 


   /**
    * 
    *
    *  Export data from a table
    *  
    *  - output_format - json, csv
    * 
    */
    function export_data($db_id, $table_id, $output_format = 'json', $options = array())
    {
        $table_id = strtolower($table_id);
    
        $output_file_name = $db_id . '_' . $table_id . '_' . base64_encode(json_encode($options)) . '.' . $output_format;
        $output_file_name = 'datafiles/tmp/' . $output_file_name;
        $download_file_name = $db_id . '_' . $table_id . '_' . date('Y-m-d') . '.' . $output_format;
    
        if (file_exists($output_file_name)) {
            // check if the file is older than 5 hours
            if (filemtime($output_file_name) < (time() - 5 * 3600)) {
                unlink($output_file_name);
            } else {
                $this->download_file($output_file_name, $output_format, $download_file_name);
                return;
            }
        }
    
        $this->table_type_obj = $this->get_table_type($db_id, $table_id);    
        $fields = $this->get_table_field_names($db_id, $table_id);
    
        $features = $fields;
        $feature_filters = array();
        $filter_options = array();
    
        // See if any key matches with the feature name
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $features)) {
                $filter_options[$key] = $value; // age = something
            }
        }
    
        $tmp_feature_filters = array();
    
        // Filter by features
        foreach ($filter_options as $feature_key => $value) {
            $tmp_feature_filters[$feature_key] = $this->apply_feature_filter($feature_key, $value);
        }
    
        // Full-text query
        if (isset($options['ft_query']) && !empty($options['ft_query'])) {
            $tmp_feature_filters['ft_query'][]['$text'] = $this->text_search($options['ft_query']);
        }
    
        $feature_filters = array();
    
        if (!empty($tmp_feature_filters)) {
            $feature_filters = array('$and' => array());
    
            foreach ($tmp_feature_filters as $feature_key => $filter) {
                $feature_filters['$and'][]['$or'] = $filter;
            }
        }
    
        $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
    
        if (!isset($options['fields'])) {
            $options['fields'] = "";
        }
    
        // Which fields to display
        $output_fields = $this->get_projection_fields($options['fields']);
    
        $cursor = $collection->find(
            $feature_filters,
            [
                'projection' => $output_fields
            ]
        );

        $file_handle = fopen($output_file_name, 'w');

        $rows_found= $collection->count($feature_filters);

        if ($output_format === 'json') {
            // Start JSON array for output
            fwrite($file_handle, '[');
    
            $first_row = true;
            foreach ($cursor as $document) {
                // Convert MongoDB object to array
                $data = iterator_to_array($document);
    
                // Write JSON for each document
                if (!$first_row) {
                    fwrite($file_handle, ',');
                }
                fwrite($file_handle, json_encode($data));
    
                $first_row = false;
            }
    
            // End JSON array
            fwrite($file_handle, ']');

        } elseif ($output_format === 'csv') {
            // Prepare CSV format
            $headers_written = false;
            $batch_size = 1000; // Set batch size for flushing data
            $batch_data = [];
    
            foreach ($cursor as $document) {
                // Convert MongoDB object to array
                $data = iterator_to_array($document);
    
                // Write headers if not already written
                if (!$headers_written) {
                    fputcsv($file_handle, array_keys($data));
                    $headers_written = true;
                }
    
                // Store data in batch
                $batch_data[] = $data;
    
                // Write batch to CSV after reaching batch size
                if (count($batch_data) >= $batch_size) {
                    foreach ($batch_data as $row) {
                        fputcsv($file_handle, $row);
                    }
                    // Clear the batch data
                    $batch_data = [];
                }
            }
    
            // Write any remaining data in the batch
            if (count($batch_data) > 0) {
                foreach ($batch_data as $row) {
                    fputcsv($file_handle, $row);
                }
            }
        }    
        fclose($file_handle);        
        $this->download_file($output_file_name, $output_format);
    }
    


    function download_file($file_path, $output_format = 'json', $download_file_name=null)
    {
        if (!file_exists($file_path)) {
            throw new Exception("File not found: " . $file_path);
        }

        if ($download_file_name === null) {
            $download_file_name = basename($file_path);
        }

        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/' . $output_format);
        header('Content-Disposition: attachment; filename=' . $download_file_name);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        exit;
    }
   

   function get_projection_fields($fields)
   {
        $fields=explode(",",$fields);
        $fields=array_filter($fields);

        $output=array(
            '_id'=>0
        );

        foreach($fields as $field){            
            $output[$field]=1;
        }

        return $output;
   }




    /**
     * 
     * parse value formats
     * 
     * - age=1-12
     * - age=1,2,3,4,5,6
     * - age=1-12,24-18,7,9
     * 
     */
   function parse_filter_value($value)
   {
       $output=array();

       $values=explode(",",$value);

       foreach($values as $val){

            //check for values enclosed in quotes e.g. "0-4"
            if (substr($val,0,1)=='"'){
                $output[]=array(
                    'type'=>'value',
                    'value'=>str_replace('"','',$val)
                );
                continue;
            }
			
			//gte or lte 
			if (substr($val,0,1)=='>' && is_numeric(substr($val,1))){
                $output[]=array(
                    'type'=>'gte',
                    'value'=>substr($val,1)
                );
                continue;
            }
			
			if (substr($val,0,1)=='<' && is_numeric(substr($val,1))){
                $output[]=array(
                    'type'=>'lte',
                    'value'=>substr($val,1)
                );
                continue;
            }
            if (substr($val,0,4)=='&lt;' && is_numeric(substr($val,4))){
                $output[]=array(
                    'type'=>'lte',
                    'value'=>substr($val,4)
                );
                continue;
            }
            
            $range=explode("-",$val);

            if(count($range)==2){
                $output[]=array(
                    'type'=>'range',
                    'start'=>$range[0],
                    'end'=>$range[1]
                );
            }else{
                $output[]=array(
                    'type'=>'value',
                    'value'=>$val
                );
            }
       }
       return $output;
   }


   function text_search($keywords)
   {        
        /* format for text search
        return ['$text' => ['$search' => "jammu"]];
        */
        
        $keywords=explode(" ", $keywords);
        $output=array();
        foreach($keywords as $keyword){
            $output[]='"'.str_replace('"','',trim($keyword)).'"'; 
        }

        return array('$search' => implode(" ", $output));
   }


   function regex_search($keywords)
   {
        return new \MongoDB\BSON\Regex('^'.$keywords, 'i');
   }

   function apply_feature_filter($feature_name,$value)
   {
        $parsed_val=$this->parse_filter_value($value);

        $output=array();
        $values=array();

        /*
        //output format
        array( 
            'sex' => array( '$in' => array(1) ), 
            'age'=> array(
                '$gte' => 10,
                '$lte' => 15                    
            )
        ) 
        */

        foreach($parsed_val as $val){
            if($val['type']=='range')
            {
                $start=(int)$val['start'];
                $end=(int)$val['end'];
            
                $output[][$feature_name]= array(
                        '$gte' => $start,
                        '$lte' => $end
                );
            }else if($val['type']=='value'){
                //$wheres[]=$feature_name." = ".$this->db->escape($val['value']);
                $values[]=is_numeric($val['value']) ? (int)$val['value']: $val['value'];
            }
			else if($val['type']=='gte'){                
				$output[][$feature_name]= array(
                        '$gte' => is_numeric($val['value']) ? (int)$val['value']: $val['value']                        
                );
            }
			else if($val['type']=='lte'){
				$output[][$feature_name]= array(
                        '$lte' => is_numeric($val['value']) ? (int)$val['value']: $val['value']                        
                );
            }
        }

        if (count($values)>0){
            $values_in=array();
            $values_nin=array();

            foreach($values as $value){
                if (trim($value)==''){continue;}

                if (substr($value,0,1)=='!'){
                    $nin_=substr($value,1,strlen($value));
                    if (is_numeric($nin_)){
                        $nin_=$nin_+0;
                    }
                    $values_nin[]=$nin_;

                }
                else{
                    $values_in[]=$value;
                }
            }

            if (count($values_in)>0){
                $output[][$feature_name]= 
                    array(
                        '$in'=>$values_in
                );
            }
            if (count($values_nin)>0){
                $output[][$feature_name]= 
                    array(
                        '$nin'=>$values_nin
                );
            }
        }

        return $output;

        
        if (count($output)==1){
            return $output[0];
        }
        else if (count($output)>1){
            return $output;
            return array(
                '$and' => 
                    array('$or'=>$output)
            );
        }
   }

    
   
   function create_table($db_id,$table_id,$options)
   {
        $table_id=strtolower($table_id);
        $db_id=strtolower($db_id);

        //schema file name
        $schema_name='census-table_type';

        //validate schema
        $this->validate_schema($schema_name,$options);
    
        //remove table definition if already exists
        $this->delete_table_type($db_id,$table_id);

        $options['db_id']=(string)$db_id;
        $options['table_id']=(string)$table_id;

        $options['_id']=$this->get_table_name($db_id,$table_id);
        $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->insertOne($options);
        $inserted_count=$result->getInsertedCount();
        
        if (!$inserted_count){
			throw new Exception($result);
        }
        
        return $inserted_count;
   }


   function update_many($db_id,$table_id,$update_filter,$update_options)
   {
        $table_id=strtolower($table_id);
        $db_id=strtolower($db_id);
    
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        
        $result = $collection->updateMany(
            $update_filter,
            [ '$set' => $update_options]
        );

        //$result = $collection->updateMany($update_filter,$update_options);
        $updated_count=$result->getModifiedCount();        
        return $updated_count;
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
     * 
     * Get features array - id, name 
     * 
     */
    function get_features_by_table($db_id,$table_id)
    {       
        if($this->table_type_obj==null){
            $this->table_type_obj=$this->get_table_type($db_id,$table_id);
        }

        if(!$this->table_type_obj['features']){
            return array();
        }

        //features
        $features_list=array();

        foreach($this->table_type_obj['features'] as $feature){
            $features_list[$feature['feature_name']]=$feature['feature_name'];
        }

        return $features_list;        
    }

    /**
     * 
     * 
     * Get features array - id, name 
     * 
     */
    function get_table_field_names($db_id,$table_id)
    {
        $collection = $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        $result = $collection->findOne();

        $output=array();

        if($result){

            foreach (array_keys((array)$result) as $key){
                $output[$key]=$key;
            }
        }

        return $output;
    }




    function delete_table_data($db_id,$table_id)
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        $result = $collection->drop();
        return $result;
    }


    function delete_table_type($db_id,$table_id)
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->deleteOne(['_id' => $this->get_table_name($db_id,$table_id) ]);
        return $result->getDeletedCount();
    }



    function get_db_error()
    {
        $error=$this->db->error();
        if(is_array($error)){
            return implode(", ",$error);
        }		
    }


    function geo_search($db_id,$options,$fields='')
   {
        /*return array(
            'options'=>$options,
            'fields'=>$fields
        );*/

        $limit=250;

        //geo fields + others
        $features=array(
            'level'=>'level',
            'state'=> 'state',
            'district'=> 'district',
            'subdistrict'=> 'subdistrict',
            'town_village'=>'town_village',
            'ward'=> 'ward',
        );

        $fields=explode(",",$fields);

        $projection=[
            '_id'=>0
        ];

        //set projection fields
        foreach($fields as $field){
            if (array_key_exists($field,$features)){
                $projection[$field]=1;
            }
            if ($field=='areaname'){
                $projection[$field]=1;
            }
        }


        $text_search_field='areaname';

        $feature_filters=array();

        //see if any key matches with the feature name
        foreach($options as $key=>$value)
        {
            if(array_key_exists($key,$features)){
                 $feature_filters[$key]=$value; //age=something
            }
        }
        
        $tmp_feature_filters=array();

        //filter by features - uses feature_1, feature_2,... for searching
        foreach($feature_filters as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value);
        }


        if(isset($options[$text_search_field])){            
            //$tmp_feature_filters[$text_search_field][][$text_search_field]=$this->regex_search($options[$text_search_field]);
            $tmp_feature_filters[$text_search_field][]['$text']=$this->text_search($options[$text_search_field]);
        }


        $feature_filters=array(
            //'$and'=> array()
        );

        foreach($tmp_feature_filters as $feature_key=>$filter){
            $feature_filters['$and'][]['$or']=$filter;
        }

        //return $feature_filters;

        //$feature_filters=$tmp_feature_filters;

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,"geo_codes")};
        
        $cursor = $collection->find(
            $feature_filters,
            [
                /*'projection'=>[
                    '_id'=>0
                ],*/
                'projection'=>$projection,
                'limit' => $limit
            ]
        );

        $output=array();
        $output['features']=$features;        
        $output['feature_filters']=$feature_filters;
        $output['found']=$collection->count($feature_filters);
        $output['total']=$collection->count();
        $output['data']=array();

        foreach ($cursor as $document) {
            $output['data'][]= $document;
        }
        
        return $output;
   } 


   function import_csv($db_id,$table_id,$csv_path,$delimiter='')
   {       
        $csv=Reader::createFromPath($csv_path,'r');
        $csv->setHeaderOffset(0);

        $delimiters=array(
            'tab'=>"\t",
            ','=>',',
            ';'=>';'
        );

        if (!empty($delimiter) && array_key_exists($delimiter,$delimiters)){
            $csv->setDelimiter($delimiters[$delimiter]);
        }
        

        $header=$csv->getHeader();
        $records= $csv->getRecords();

        $chunk_size =15000;
        $chunked_rows=array();
        $k=1;
        $total=0;
        
        //delete existing table data
        //$this->Data_table_model->delete_table_data($table_id);

        $intval_func= function($value){
            if (is_numeric($value)){
                return $value + 0;
            }

            //return $value;
            return utf8_encode(utf8_decode($value));
        };

        foreach($records as $row){

            $row=array_map($intval_func, $row);            
            $total++;
            $chunked_rows[]=$row;

            if($k>=$chunk_size){
                $result=$this->table_batch_insert($db_id,$table_id,$chunked_rows);
                $k=1;
                $chunked_rows=array();
                set_time_limit(0);
                //break;
            }

            $k++;				
        }

        if(count($chunked_rows)>0){
            $result=$this->table_batch_insert($db_id,$table_id,$chunked_rows);
        }

        return $total;
   }


   function get_table_aggregate($db_id,$table_id,$limit=100,$offset=0,$options)
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>10000){
            $limit=100;
        }        

        $table_id=strtolower($table_id);

        if (!isset($options['fields'])){
            throw new Exception("fields parameter is required");
        }

        $group_fields=explode(",",$options['fields']);

        //get table type info
        $this->table_type_obj= $this->get_table_type($db_id,$table_id);

        //get table fields list
        $fields=$this->get_table_field_names($db_id,$table_id);

        if (!empty($fields) && isset($fields['_id'])){
            unset($fields['_id']);
        }

        $group_fields=array_intersect($group_fields,$fields);

        if (empty($group_fields)){
            throw new Exception("No valid field values provided. Valid values are: ". implode(", ",$fields));
        }

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};        

        /*
        $cond = array(
            //array('$match' => array('page_id' =>123456)),
            array(
                '$group' => array(
                    '_id' => array("ctry_code" => '$ctry_code', 'country'=>'$ctry_name'),
                'total' => array('$sum' => '$ctry_code'),
                //'count' => "total_count"
                'count' => array('$sum' => 1),
                )
            ),
        );
        */

        $groups_arr=array();
        foreach($group_fields as $f){
            $groups_arr[$f]='$'.$f;
        }

        $cond=array(
            //array('$match'=>null),
            array('$group'=>array('_id'=>$groups_arr, 'count'=>array('$sum'=>1))),
            array('$sort'=>array('_id'=>1)),
            array('$skip'=>$offset),
            array('$limit'=>$limit)
        );

        $filters=$this->get_filters($db_id,$table_id,$options);

        if ($filters){
            //must be added at the beginning of the conditions to work
            array_unshift($cond,array('$match'=>$filters));
        }

        $cursor = $collection->aggregate($cond);

        $output['rows']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;        
        $output['data']=array();

        if (isset($options['debug'])){
            $output['debug']=$cond;
        }

        $k=0;
        foreach ($cursor as $document) {
            $k++;
            $row=iterator_to_array($document);
            $result=array();
            if(isset($row['_id'])){
                $result=$row['_id'];
            }
            $result['count']=$row['count'];
            $output['data'][]= $result;
        }

        $output['rows']=$k;
        return $output;
   } 


   function get_filters($db_id, $table_id,$options)
   {
        $fields=$this->get_table_field_names($db_id,$table_id);

        $features=$fields;
        $feature_filters=array();
        $filter_options=array();

        //see if any key matches with the feature name
        foreach($options as $key=>$value)
        {
            if(array_key_exists($key,$features)){
                $filter_options[$key]=$value; //age=something
            }
        }
        
        $tmp_feature_filters=array();

        //filter by features
        foreach($filter_options as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value);
        }

        //fulltext query
        if(isset($options['ft_query']) && !empty($options['ft_query'])){
            $tmp_feature_filters['ft_query'][]['$text']=$this->text_search($options['ft_query']);
        }
        
        $feature_filters=array();

        if (!empty($tmp_feature_filters)){

            $feature_filters=array(
                '$and'=> array()
            );

            foreach($tmp_feature_filters as $feature_key=>$filter){
                $feature_filters['$and'][]['$or']=$filter;
            }
        }

        return $feature_filters;
   }
	
}    
