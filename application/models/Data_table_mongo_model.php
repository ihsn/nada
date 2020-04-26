<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;
use League\Csv\Reader;

class Data_table_mongo_model extends CI_Model {

    private $geo_fields=array();

    //table type object holds table definition[features, codelists, etc]
    private $table_type_obj=null;

    private $mongo_client;

    private $db_name;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('mongo');

        //$this->load->model("Data_tables_places_model");
        //$this->geo_fields=$this->Data_tables_places_model->get_geo_mappings();
        //$this->output->enable_profiler(TRUE);
        
        //tood: use a config value to set current database
        $this->db_name=$this->config->item("mongodb_database");

        $this->mongo_client=$this->get_db_connection();
    }


    function get_db_connection()
    {
        $username=$this->config->item("mongodb_username");
        $password=$this->config->item("mongodb_password");
        $host=$this->config->item("mongodb_host");
        $port=$this->config->item("mongodb_port");


	return new MongoDB\Client(
           "mongodb://${host}:${port}",
	    array("username" => $username, "password" => $password, "db"=> $this->get_db_name(), 'authSource' => $this->get_db_name() ));

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



   //todo: new database name scheming 
   function get_tables_list($db_id,$options=array())
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
        /*
        $manager = $this->get_mongo_manager();
        $command = new MongoDB\Driver\Command(array(
            'renameCollection' => 'nada_db.table_2020_abc_x',
            'to' => 'nada_db.table_2020_abc_xy'
        ));
        
        try {
            $cursor = $manager->executeCommand('admin', $command);
        } catch(MongoDB\Driver\Exception $e) {
            echo $e->getMessage(), "xxxxxxx\n";
            exit;
        }
        
        return "done";
        $response = $cursor->toArray()[0];
        
        return ($response);
        */

        $db_name=$this->get_db_name();
     
        //enable admin priviledges
        $admin = $this->mongo_client->admin;
     
        /*$result= ($admin->command(array(
            'renameCollection' => 'nada_db.table_2020_abc_new_YYYYY',
            'to' => 'nada_db.table_2020_abc_new_CC'
        )));

        return ($result);
        die();
        */
     
        $result= $admin->command(array(
            'renameCollection'=> $db_name.'.'.$old_name,
            'to'=> $db_name.'.'.$new_name)
        );
        //return $result;
        return $result->toArray()[0];
    }


    /**
    * 
    * 
    * 
    * 
    */
   function get_table_features_list($db_id,$table_id,$features=array())
   {
       $table_id=strtolower($table_id);       
       $table=$this->get_table_type($db_id,$table_id);
       
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
   function get_table_data($db_id,$table_id,$limit=100,$offset=0,$options,$labels=array())
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>10000){
            $limit=100;
        }        

        $table_id=strtolower($table_id);

        $labels=array_filter($labels);

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

        $filter_query=array();

        //filter by features - uses feature_1, feature_2,... for searching
        foreach($filter_options as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value);
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


        //return $feature_filters;


        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        /*
        $cursor = $collection->find(
            [
                'state'=>0,
                'age'=> array(
                    '$in'=>array(2)
                ),
                'age'=> array(
                    '$gte' => 10,
                    '$lte' => 15                    
                )
            ],
            [
                'projection'=>[
                    '_id'=>0
                ],
                'limit' => $limit
            ]
        );
        */

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

        $geo_features=array(
            'state',
            'district'
        );

        $output=array();
        $geo_codes=array();

        if (isset($options['debug'])){
            $output['options']=$options;
            $output['features']=$features;        
            $output['feature_filters']=$feature_filters;        
            $output['labels']=$labels;
        }

        $output['rows_count']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;
        $output['found']=$collection->count($feature_filters);
        $output['total']=$collection->count();
        $output['codelist']=array();
        $output['data']=array();
        

        foreach ($cursor as $document) {
            //convert to array from mongodb object
            $output['data'][]= iterator_to_array($document);            
            
            foreach($geo_features as $geo_feature_name){
                if (isset($document[$geo_feature_name])){
                    $geo_codes[$geo_feature_name][]=$document[$geo_feature_name];
                }
            }
        }

        if (is_array($labels) && !empty($labels)){
            
            //get codelists for features
            $output['codelist']=$this->get_table_features_list($db_id,$table_id,$labels);

            //codelists for geocodes
            foreach($geo_codes as $geo_feature_name_=>$geo_values)
            {
                /*if (!in_array(trim($geo_feature_name_),$labels)){
                    continue;
                }*/

                $geo_codes[$geo_feature_name_]=array_values(array_unique($geo_values));

                $params_=array();
                $params_['level']=$geo_feature_name_;
                $params_[$geo_feature_name_]=implode(",",array_values(array_unique($geo_values)));
                $labels=$this->geo_search($db_id,$params_,$fields=implode(",",array('areaname',$geo_feature_name_)));
                $output['codelist'][$geo_feature_name_]=array(
                    'feature_name'=>$geo_feature_name_,
                    'code_list'=>$labels['data']
                );
            }
        }else{
            unset($output['codelist']);
        }

        //$output['codelist']=$geo_codes;
        $output['rows_count']=count($output['data']);
        return $output;
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
        /*return array(
            '$text'=> array('$search'=> $keywords)
        );*/

        return array('$search' => $keywords);
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
            if($val['type']=='range'){
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
        }

        if (count($values)>0){

            $values_in=array();
            $values_nin=array();
            foreach($values as $value){
                if (empty($value)){$continue;}

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
     * Get a count of tables with row counts
     * 
     */
    function get_tables_w_count()
    {
        return $this->db->query("select table_id,count(table_id) as total from data_tables group by table_id")->result_array();        
    } 


    function get_table_count($table_id)
    {
        $this->db->select('count(table_id) as total');
        $this->db->where('table_id',$table_id);        
        $result= $this->db->get('data_tables')->row_array();

        if($result){
            return $result['total'];
        }

        return false;
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

        $limit=100;

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
            $tmp_feature_filters[$text_search_field][][$text_search_field]=$this->regex_search($options[$text_search_field]);
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

            return $value;
            //return utf8_encode($value);
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

   
   

    /**
     * 
     * Get population by age ranges
     * 
     * {
     *   "age_group": "0-4",
     *   "literate_male": 0,
     *   "illiterate_male": 58632074,
     *   "literate_female": 0,
     *   "illiterate_female": 54174704,
     *   "total_male": 58632074,
     *   "total_female": 54174704,
     *   "sex_ratio": 1.0822776992007193
     *   },
     * 
     * 
     * 
     * 
     */
    function population_by_age($db_id,$table_id,$options=null)
    {
        $options=null;
        //default
        if(!$options){
            $options=array(
                'geo_level'=>0,
                'age'=>'0-100',
                'sex'=>'1,2',
                'scst'=>'0',
                'urbrur'=>'0',
                'fields'=>'age,sex,literacy,value'
            );
        }

        //var_dump($options);
        //die();
        
        $data=$this->get_table_data($db_id,$table_id,$limit=1000,$offset=0,$options);
//return $data;
        $data=$data['data'];

        $age_groups=array();			
        for ($i=0; $i<=100; $i+=5) {
            $age_range=$i.'-'.($i+4);
            for($x=$i;$x<=$i+4;$x++){
                $age_groups[$x]=$age_range;
            }
        }

        //initialize output
        $output=array();
        foreach(array_values($age_groups) as $age_group){
            $output[$age_group]=array(
                'age_group'=>$age_group, 
                'literate_male'=>0, 
                'illiterate_male'=>0, 
                'literate_female'=>0, 
                'illiterate_female'=>0, 
                'total_male'=>0,
                'total_female'=>0, 
                'male'=>0,
                'female'=>0,
                'sex_ratio'=>0            
            );
        }

        //age_group, literate_male, illiterate_male, literate_female, illiterate_female, total_male,total_female, sex_ratio
        //0-4      , literacy=1,sex=1, 

        //$output=array();        
        foreach($data as $row)
        {
            if(!isset($row['age'])){
                throw new Exception("AGE NOT FOUND");
            }

            //skip invalid age groups
            if (!isset($age_groups[$row['age']])){
                continue;
            }

            //get age group e.g. 0-4
            $age_group=$age_groups[$row['age']];

            if (isset($row['literacy'])){
                //male
                if($row['sex']==1){
                    //literate_male
                    if($row['literacy']==1){
                        $output[$age_group]['literate_male']+=$row['value'];
                    }else{
                        //illiterate male
                        $output[$age_group]['illiterate_male']+=$row['value'];
                    }
                }else{
                    //literate_female
                    if($row['literacy']==1){
                        $output[$age_group]['literate_female']+=$row['value'];
                    }else{
                    //illiterate female
                        $output[$age_group]['illiterate_female']+=$row['value'];
                    }
                }
            }
            else{
                
                if($row['sex']==1){
                    //male
                    $output[$age_group]['male']+=$row['value'];
                }else{
                   // var_dump($row);die();
                    //female
                    $output[$age_group]['female']+=$row['value'];
                }            
            }

        }

        //return $output;

        if (isset($row['literacy'])){
            foreach($output as $key=> $row)
            {
                //total male
                $total_male=$row['literate_male'] + $row['illiterate_male'];
                $output[$key]['total_male']=$total_male;

                //total female
                $total_female=$row['literate_female'] + $row['illiterate_female'];
                $output[$key]['total_female']=$total_female;

                //sex ratio
                $output[$key]['sex_ratio']=$total_male/$total_female;
            }
        }
        /*else{
            foreach($output as $key=> $row)
            {
                //total male
                $output[$key]['total_male']=$row['male'];

                //total female
                $output[$key]['total_female']=$row['female'];

                //sex ratio
                //$output[$key]['sex_ratio']=(int)$row['male']/ (int)$row['female'];
            }
        }*/

       
        return $output;

        $output_idx=array();
        
        foreach($output as $row)
        {
            $output_idx[]=$row;
        }

        return $output_idex;
    }

	
}    
