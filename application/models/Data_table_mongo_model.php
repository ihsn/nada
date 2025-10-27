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

   function update_table_type($db_id,$table_id,$update_data)
   {
        $table_id=strtolower($table_id);
        $db_id=strtolower($db_id);

        $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->updateOne(
            ['_id' => $this->get_table_name($db_id,$table_id)],
            ['$set' => $update_data]
        );
        
        return $result->getModifiedCount();
   }

   
   /**
    * 
    * Update import progress
    * 
    * @db_id - database id
    * @table_id - table id
    * @progress_data - progress data
    * 
    */
   function update_import_progress($db_id, $table_id, $progress_data)
   {
        $table_id = strtolower($table_id);
        $db_id = strtolower($db_id);

        $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
                
        $update_data = array();
        foreach ($progress_data as $key => $value) {
            if ($key === 'last_batch') {
                $update_data['import_progress.last_batch'] = $value;
            } else {
                $update_data['import_progress.' . $key] = $value;
            }
        }
        
        $result = $collection->updateOne(
            ['_id' => $this->get_table_name($db_id, $table_id)],
            ['$set' => $update_data]
        );
        
        return $result->getModifiedCount();
   }


   /**
    * 
    * Upsert table type
    * 
    * @db_id - database id
    * @table_id - table id
    * @csv_file_path - csv file path
    * @form_data - form data
    * 
    */
   function upsert_table_type($db_id, $table_id, $csv_file_path, $form_data = array())
   {
        $table_id = strtolower($table_id);
        $db_id = strtolower($db_id);
        
        $existing_table = $this->get_table_type($db_id, $table_id);
        
        $title = isset($form_data['title']) ? $form_data['title'] : null;
        $description = isset($form_data['description']) ? $form_data['description'] : null;
        
        if ($existing_table) {
            // Update existing table definition
            $update_data = array(
                'csv_file_path' => $csv_file_path,  // Always update file path
                'updated_at' => date('Y-m-d H:i:s'),
                'csv_uploaded_at' => date('Y-m-d H:i:s'),
                // reset import progress fields under import_progress
                'import_progress' => array(
                    'byte_offset_end' => 0,
                    'total_rows_processed' => 0,
                    'import_status' => 'ready',
                    'import_started_at' => null,
                    'import_completed_at' => null,
                    'last_import_at' => null,
                    'progress_percent' => 0
                )
            );
            
            if ($title) {
                $update_data['title'] = $title;
            }
            
            if ($description) {
                $update_data['description'] = $description;
            }
            
            if (isset($form_data['indicators']) && $form_data['indicators']) {
                $update_data['indicators'] = is_string($form_data['indicators']) ? 
                    json_decode($form_data['indicators'], true) : $form_data['indicators'];
            }
            
            for ($i = 1; $i <= 9; $i++) {
                $feature_key = 'feature_' . $i;
                if (isset($form_data[$feature_key]) && $form_data[$feature_key]) {
                    $update_data[$feature_key] = is_string($form_data[$feature_key]) ? 
                        json_decode($form_data[$feature_key], true) : $form_data[$feature_key];
                }
            }
            
            $result = $this->update_table_type($db_id, $table_id, $update_data);

            return array(
                'action' => 'updated',
                'result' => $result,
                'was_existing' => true
            );
        } else {
            // Create new table definition
            $table_metadata = array(
                'title' => $title ?: $db_id . ' - ' . $table_id,
                'description' => $description ?: 'N/A',
                'table_id' => $table_id,
                'csv_file_path' => $csv_file_path,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'csv_uploaded_at' => date('Y-m-d H:i:s'),
                // Initialize import progress fields under import_progress
                'import_progress' => array(
                    'byte_offset_end' => 0,
                    'total_rows_processed' => 0,
                    'import_status' => 'ready',
                    'import_started_at' => null,
                    'import_completed_at' => null,
                    'last_import_at' => null,
                    'progress_percent' => 0
                )
            );
            
            if (isset($form_data['indicators']) && $form_data['indicators']) {
                $table_metadata['indicators'] = is_string($form_data['indicators']) ? 
                    json_decode($form_data['indicators'], true) : $form_data['indicators'];
            }
            
            for ($i = 1; $i <= 9; $i++) {
                $feature_key = 'feature_' . $i;
                if (isset($form_data[$feature_key]) && $form_data[$feature_key]) {
                    $table_metadata[$feature_key] = is_string($form_data[$feature_key]) ? 
                        json_decode($form_data[$feature_key], true) : $form_data[$feature_key];
                }
            }
            
            $result = $this->create_table($db_id, $table_id, $table_metadata);
            return array(
                'action' => 'created',
                'result' => $result,
                'was_existing' => false
            );
        }
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


   /**
    * Fast CSV import using byte offset tracking - O(1) seek time
    * Processes maximum rows within the time limit
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID  
    * @param string $csv_path Path to CSV file
    * @param string $delimiter CSV delimiter
    * @param int $byte_offset Byte position to start from (0 for beginning)
    * @param int $max_time_seconds Maximum execution time (default: 30 seconds)
    * @return array Import result with next byte offset
    */
   function import_csv_chunked($db_id, $table_id, $csv_path, $delimiter = ',', $byte_offset = 0, $max_time_seconds = 30)
   {
       $start_time = microtime(true);
       $total_processed = 0;
       $chunk_size = 1000; // Batch insert every 1000 rows
       $chunked_rows = [];
       
       set_time_limit(0);
       
       // Open file handle
       $handle = fopen($csv_path, 'r');
       if (!$handle) {
           throw new Exception("Failed to open CSV file: {$csv_path}");
       }
       
       // Parse delimiter
       $delimiters = [
           'comma' => ',',
           'tab' => "\t",
           'semicolon' => ';',
           ',' => ',',
           ';' => ';',
       ];
       $delimiter_char = isset($delimiters[$delimiter]) ? $delimiters[$delimiter] : ',';
       
       // Read header (first line)
       $header = fgetcsv($handle, 0, $delimiter_char);
       if (!$header) {
           fclose($handle);
           throw new Exception("Failed to read CSV header");
       }
       
       // Clean header names
       $header = array_map('trim', $header);
       
       $header_byte_length = ftell($handle); // Store header length
       
       // If starting from beginning, byte_offset should be after header
       if ($byte_offset == 0) {
           $byte_offset = $header_byte_length;
       }
       
       // Seek to the byte offset - THIS IS THE KEY OPTIMIZATION
       // This makes seeking O(1) instead of O(n)
       fseek($handle, $byte_offset);
       
       $current_byte_offset = $byte_offset;
       $has_more = false;
       $file_size = filesize($csv_path);
       
       // Read rows from this position - process as many as possible within time limit
       while (($row_data = fgetcsv($handle, 0, $delimiter_char)) !== false) {
           
           // Check time limit (primary constraint)
           if ((microtime(true) - $start_time) >= $max_time_seconds) {
               $has_more = true;
               break;
           }
           
           // Skip empty rows
           if (empty($row_data) || (count($row_data) == 1 && empty($row_data[0]))) {
               continue;
           }
           
           // Combine header with row data
           if (count($row_data) != count($header)) {
               // Handle rows with different column counts
               $row_data = array_pad($row_data, count($header), '');
           }
           
           $row = array_combine($header, $row_data);
           if ($row === false) {
               continue; // Skip malformed rows
           }
           
           // Clean values
           $row = array_map(array($this, 'clean_csv_value'), $row);
           $chunked_rows[] = $row;
           $total_processed++;
           
           // Batch insert to MongoDB
           if (count($chunked_rows) >= $chunk_size) {
               $this->table_batch_insert($db_id, $table_id, $chunked_rows);
               $chunked_rows = [];
           }
       }
       
       // Insert remaining rows
       if (!empty($chunked_rows)) {
           $this->table_batch_insert($db_id, $table_id, $chunked_rows);
       }
       
       $next_byte_offset = ftell($handle);
       
       // Check if we've reached end of file
       if ($next_byte_offset >= $file_size || feof($handle)) {
           $has_more = false;
       }
       
       fclose($handle);
       
       $execution_time = microtime(true) - $start_time;
       
       return [
           'rows_processed' => $total_processed,
           'byte_offset_start' => $byte_offset,
           'byte_offset_end' => $next_byte_offset,
           'file_size' => $file_size,
           'progress_percent' => round(($next_byte_offset / $file_size) * 100, 2),
           'has_more' => $has_more,
           'execution_time_seconds' => round($execution_time, 2),
           'execution_time_formatted' => $this->format_execution_time($execution_time)
       ];
   }

/**
 * Helper: Format seconds as H:M:S
 */
function format_execution_time($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = floor($seconds % 60);
    return sprintf("%02dh:%02dm:%02ds", $hours, $minutes, $seconds);
}


   function import_csv($db_id, $table_id, $csv_path, $delimiter = '')
   {
       $result = $this->import_csv_chunked(
           $db_id, 
           $table_id, 
           $csv_path, 
           $delimiter, 
           0, // byte offset (start from beginning)
           600 // 10 minutes timeout
       );
       
        return $result;
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
	
   /**
    * 
    * Get field metadata for a table
    * 
    */
   function get_field_metadata($db_id, $table_id, $field_name = null)
   {
       $table_type = $this->get_table_type($db_id, $table_id);
       
       if (!$table_type || !isset($table_type['fields'])) {
           return null;
       }
       
       if ($field_name) {
           foreach ($table_type['fields'] as $field) {
               if ($field['name'] === $field_name) {
                   return $field;
               }
           }
           return null;
       }
       
       // Return all fields
       return $table_type['fields'];
   }

   /**
    * 
    * Create or update field metadata
    * 
    */
   function create_field_metadata($db_id, $table_id, $field_metadata)
   {
       $this->ensure_fields_array_exists($db_id, $table_id);
       
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       
       $result = $collection->updateOne(
           ['_id' => $this->get_table_name($db_id, $table_id)],
           ['$push' => ['fields' => $field_metadata]]
       );
       
       return $result->getModifiedCount();
   }

   /**
    * 
    * Update field metadata
    * 
    */
   function update_field_metadata($db_id, $table_id, $field_name, $field_metadata)
   {
       $this->ensure_fields_array_exists($db_id, $table_id);
       
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       
       $result = $collection->updateOne(
           [
               '_id' => $this->get_table_name($db_id, $table_id),
               'fields.name' => $field_name
           ],
           ['$set' => ['fields.$' => $field_metadata]]
       );
       
       return $result->getModifiedCount();
   }

   /**
    * 
    * Delete field metadata
    * 
    */
   function delete_field_metadata($db_id, $table_id, $field_name)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       
       // Remove field from the fields array
       $result = $collection->updateOne(
           ['_id' => $this->get_table_name($db_id, $table_id)],
           ['$pull' => ['fields' => ['name' => $field_name]]]
       );
       
       return $result->getModifiedCount();
   }
	
   /**
    * 
    * Ensure fields array exists in table type
    * 
    */
   function ensure_fields_array_exists($db_id, $table_id)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       
       $result = $collection->updateOne(
           [
               '_id' => $this->get_table_name($db_id, $table_id),
               'fields' => ['$exists' => false]
           ],
           ['$set' => ['fields' => []]]
       );
       
       return $result->getModifiedCount();
   }
	
   /**
    * 
    * Get field names from actual data collection
    * 
    */
   function get_data_field_names($db_id, $table_id)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
       
       // Get a sample document to extract field names
       $sample = $collection->findOne();
       
       if (!$sample) {
           return array();
       }
       
       $field_names = array();
       foreach (array_keys((array)$sample) as $field_name) {
           if ($field_name !== '_id') { // Exclude MongoDB _id field
               $field_names[] = $field_name;
           }
       }
       
       return $field_names;
   }

	/**
	 * 
	 * Update table schema in table_types collection
	 * 
	 */
	function update_table_schema($db_id, $table_id, $fields_metadata)
	{
		try {
			$collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
			
			$this->ensure_fields_array_exists($db_id, $table_id);
			
			// Replace the entire fields array with new metadata
			$result = $collection->updateOne(
				['_id' => $this->get_table_name($db_id, $table_id)],
				['$set' => ['fields' => $fields_metadata]]
			);
			
			return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
		} catch (Exception $e) {
			log_message('error', 'Failed to update table schema: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get the number of rows in a table (optimized)
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @param bool $exact_count Whether to get exact count (slower) or estimated count (faster)
	 * @return int Number of rows in the table
	 */
	function get_table_row_count($db_id, $table_id, $exact_count = false)
	{
		try {
			$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
			
			if ($exact_count) {
				// Use countDocuments() for exact count (slower but accurate)
				return $collection->countDocuments();
			} else {
				// Use estimatedDocumentCount() for fast approximate count
				return $collection->estimatedDocumentCount();
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get table row count: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Get fast row count using _id index filter (optimized for large collections)
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @return int Number of rows in the table
	 */
	function get_table_row_count_fast($db_id, $table_id)
	{
		try {
			$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
			// Use _id index filter for faster counting
			return $collection->countDocuments(['_id' => ['$ne' => null]]);
		} catch (Exception $e) {
			log_message('error', 'Failed to get fast table row count: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Clean and convert CSV values to appropriate data types
	 * 
	 * @param mixed $value The value to clean
	 * @return mixed Cleaned value with proper data type
	 */
	private function clean_csv_value($value)
	{
		if (is_numeric($value)) {
			return $value + 0;
		}
		return mb_convert_encoding($value, 'UTF-8', 'auto');
	}

	/**
	 * Process import request - main orchestrator method
	 */
	public function process_import_request($db_id, $table_id, $options)
	{
		// Validate parameters
		$validated_options = $this->validate_import_parameters($options);
		
		// Validate table and file
		$table_definition = $this->validate_table_and_file($db_id, $table_id);
		
		// Handle status-only request
		if ($validated_options['status_only']) {
			return $this->get_import_status_response($table_definition);
		}
		
		// Handle completed import
		if (isset($table_definition['import_progress']['import_status']) && 
			$table_definition['import_progress']['import_status'] === 'completed') {
			return $this->get_completed_import_response($table_definition, $validated_options);
		}
		
		// Execute import process
		$result = $this->execute_import_process($db_id, $table_id, $validated_options, $table_definition);
		
		// Build and return response
		return $this->build_import_response($table_definition, $result, $validated_options);
	}

	/**
	 * Validate import parameters
	 */
	private function validate_import_parameters($options)
	{
		$status_only = isset($options['status']) ? (bool)$options['status'] : false;
		$delimiter = isset($options['delimiter']) ? $options['delimiter'] : 'comma';
		$max_time = isset($options['max_time']) ? (int)$options['max_time'] : 30;
		
		// Validate and normalize max_time (between 10 and 120 seconds)
		if ($max_time < 10) {
			$max_time = 10;
		}
		if ($max_time > 120) {
			$max_time = 120;
		}
		
		return array(
			'status_only' => $status_only,
			'delimiter' => $delimiter,
			'max_time' => $max_time
		);
	}

	/**
	 * Validate table and file existence
	 */
	private function validate_table_and_file($db_id, $table_id)
	{
		$table_definition = $this->get_table_type($db_id, $table_id);
		
		if (!$table_definition) {
			throw new Exception("Table definition not found - please upload a file first");
		}

		if (!isset($table_definition['csv_file_path']) || empty($table_definition['csv_file_path'])) {
			throw new Exception("No CSV file path found in table definition - please upload a file first");
		}

		$validated_file_path = validate_file_path($table_definition['csv_file_path'], $db_id, $table_id);
		$full_file_path = 'datafiles/' . $validated_file_path;

		if (!file_exists($full_file_path)) {
			throw new Exception("CSV file not found: " . $validated_file_path);
		}

		return $table_definition;
	}

	/**
	 * Get import status response (status-only request)
	 */
	private function get_import_status_response($table_definition)
	{
		$import_progress = isset($table_definition['import_progress']) ? $table_definition['import_progress'] : array();
		
		return array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => isset($import_progress['file_size']) ? $import_progress['file_size'] : 0
			),
			'progress' => array(
				'total_rows_processed' => isset($import_progress['total_rows_processed']) ? $import_progress['total_rows_processed'] : 0,
				'byte_offset_end' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
				'progress_percent' => isset($import_progress['progress_percent']) ? $import_progress['progress_percent'] : 0,
				'import_status' => isset($import_progress['import_status']) ? $import_progress['import_status'] : 'ready',
				'has_more' => isset($import_progress['import_status']) ? $import_progress['import_status'] !== 'completed' : true
			)
		);
	}

	/**
	 * Get completed import response
	 */
	private function get_completed_import_response($table_definition, $options)
	{
		$import_progress = isset($table_definition['import_progress']) ? $table_definition['import_progress'] : array();

		return array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => isset($import_progress['file_size']) ? $import_progress['file_size'] : 0
			),
			'batch' => array(
				'rows_processed' => 0,
				'byte_offset_start' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
				'byte_offset_end' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
				'execution_time_seconds' => 0,
				'execution_time_formatted' => '00h:00m:00s'
			),
			'progress' => array(
				'total_rows_processed' => isset($import_progress['total_rows_processed']) ? $import_progress['total_rows_processed'] : 0,
				'progress_percent' => 100,
				'import_status' => 'completed',
				'has_more' => false
			),
			'next' => null,
			'message' => 'Import already completed'
		);
	}

	/**
	 * Execute import process
	 */
	private function execute_import_process($db_id, $table_id, $options, $table_definition)
	{
		// Get byte offset to resume from (0 for new import)
		$byte_offset = isset($table_definition['import_progress']['byte_offset_end']) ? 
			$table_definition['import_progress']['byte_offset_end'] : 0;

		// Get existing row count
		$existing_row_count = isset($table_definition['import_progress']['total_rows_processed']) ? 
			$table_definition['import_progress']['total_rows_processed'] : 0;

		// Validate import consistency
		$this->validate_import_consistency($db_id, $table_id, $byte_offset, $existing_row_count, $table_definition);

		// Update progress if starting fresh
		if ($byte_offset == 0) {
			$this->update_import_progress($db_id, $table_id, array(
				'import_status' => 'in_progress',
				'total_rows_processed' => 0,
				'byte_offset_end' => 0,
				'import_started_at' => date('Y-m-d H:i:s'),
				'import_completed_at' => null
			));
		}

		// Get file path
		$validated_file_path = validate_file_path($table_definition['csv_file_path'], $db_id, $table_id);
		$full_file_path = 'datafiles/' . $validated_file_path;

		// Execute import with byte offset (fast seeking)
		$result = $this->import_csv_chunked(
			$db_id, 
			$table_id, 
			$full_file_path, 
			$options['delimiter'], 
			$byte_offset,
			$options['max_time']
		);

		// Calculate new total
		$new_total_rows = $existing_row_count + $result['rows_processed'];

		// Update progress
		$progress_data = array(
			'total_rows_processed' => $new_total_rows,
			'byte_offset_end' => $result['byte_offset_end'],
			'file_size' => $result['file_size'],
			'progress_percent' => $result['progress_percent'],
			'last_batch' => array(
				'rows_processed' => $result['rows_processed'],
				'byte_offset_start' => $result['byte_offset_start'],
				'byte_offset_end' => $result['byte_offset_end'],
				'execution_time' => $result['execution_time_seconds']
			)
		);

		if (!$result['has_more']) {
			$progress_data['import_status'] = 'completed';
			$progress_data['import_completed_at'] = date('Y-m-d H:i:s');
		} else {
			$progress_data['import_status'] = 'in_progress';
		}

		$this->update_import_progress($db_id, $table_id, $progress_data);

		return $result;
	}

	/**
	 * Build import response
	 */
	private function build_import_response($table_definition, $result, $options)
	{
		// Get updated progress from table definition
		$updated_definition = $this->get_table_type($table_definition['db_id'], $table_definition['table_id']);
		$import_progress = isset($updated_definition['import_progress']) ? $updated_definition['import_progress'] : array();

		return array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => $result['file_size'],
				'file_size_mb' => round($result['file_size'] / (1024 * 1024), 2)
			),
			'batch' => array(
				'rows_processed' => $result['rows_processed'],
				'byte_offset_start' => $result['byte_offset_start'],
				'byte_offset_end' => $result['byte_offset_end'],
				'execution_time_seconds' => $result['execution_time_seconds'],
				'execution_time_formatted' => $result['execution_time_formatted']
			),
			'progress' => array(
				'total_rows_processed' => isset($import_progress['total_rows_processed']) ? $import_progress['total_rows_processed'] : 0,
				'progress_percent' => $result['progress_percent'],
				'import_status' => $result['has_more'] ? 'in_progress' : 'completed',
				'has_more' => $result['has_more']
			),
			'next' => $result['has_more'] ? array(
				'byte_offset' => $result['byte_offset_end'],
				'endpoint' => base_url() . 'api/tables/import/' . $table_definition['db_id'] . '/' . $table_definition['table_id'],
				'message' => 'Call this endpoint again to continue import'
			) : null
		);
	}

	/**
	 * Validate import consistency
	 */
	private function validate_import_consistency($db_id, $table_id, $byte_offset, $expected_row_count, $table_definition)
	{
		$existing_rows = $this->get_table_row_count_fast($db_id, $table_id);
		
		if ($byte_offset == 0) {
			// New import - table must be empty
			if ($existing_rows > 0) {
				throw new Exception("Table already contains {$existing_rows} rows. Use DELETE /api/tables/{$db_id}/{$table_id} to clear data first.");
			}
		} else {
			// Resume import - check consistency
			if ($existing_rows !== $expected_row_count) {
				throw new Exception("Data inconsistency: expected {$expected_row_count} rows, found {$existing_rows} rows. Use DELETE endpoint to reset.");
			}
		}
	}
	
}    
