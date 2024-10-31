<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;
use League\Csv\Reader;

require_once 'modules/mongodb/vendor/autoload.php';

class Timeseries_model extends CI_Model {

    //table type object holds table definition[features, codelists, etc]
    private $table_type_obj=null;
    private $mongo_client;
    private $db_name;

    //tables/collections
    //timeseries_data - holds the timeseries data
    //timeseries_metadata - holds the timeseries metadata
    //timeseries_info - holds the timeseries series definitions


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



    public function get_db_name()
    {
        if(empty($this->db_name)){
            throw new Exception("MongoDB Database not set, check application config for mongo.");
        }
        
        return $this->db_name;
    }

    
    public function get_series_name($db_id,$series_id=null)
    {
        //return strtolower('timeseries_'.$db_id);
        return 'timeseries_data';
    }


    function get_database_info()
   {
       $database=$this->mongo_client->{$this->get_db_name()};
       $db_info = $database->command(['dbStats' => 1, 'scale'=> 1024*1024 ]);
       return $db_info->toArray()[0];
   }
    


    /**
     * 
     * Batch insert rows into a timeseries collection
     * 
     */
    public function series_batch_insert($db_id,$series_id,$rows) 
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_series_name($db_id,$series_id)};
        $insertManyResult = null;

        //add series_id and db_id to each row
        foreach($rows as $idx=>$row){
            $rows[$idx]['_series_id']=$series_id;
            $rows[$idx]['_db_id']=$db_id;            
        }

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



    public function series_delete($db_id, $series_id)
    {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_series_name($db_id,$series_id)};
        $deleteResult = null;

        try {
            $deleteResult = $collection->deleteMany(['_db_id' => $db_id, '_series_id' => $series_id]);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw new Exception("ERROR::". utf8_encode($e->getMessage()));            
        }

        $deleted_count=$deleteResult->getDeletedCount();        
        return $deleted_count;
    }


    function series_data($db_id,$series_id,$limit=100,$offset=0,$options=array())
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>2000){
            $limit=100;
        }

        //get table type info
        //$this->table_type_obj= $this->get_table_type($db_id,$table_id);


        //series data structure 
        //$dsd=$this->Timeseries_tables_model->get_series_data_structure($db_id,$series_id);

        
        //$fields=$this->get_table_field_names($db_id,$table_id);
        //$fields=null;

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_series_name($db_id,$series_id)};        

        if (!isset($options['fields'])){
            $options['fields']="";
        }

        //which fields to display
        $output_fields=$this->get_projection_fields($options['fields']);

        //filters
        $filters=[
            '_db_id' => $db_id, 
            '_series_id' => $series_id
        ];

        $series_filter=[
            '_db_id' => $db_id, 
            '_series_id' => $series_id
        ];

        //if query string is provided - check options[c]
        if (isset($options['c'])){
            foreach($options['c'] as $key=>$value){

                $values=explode("|",$value);
                foreach($values as $idx=>$value){
                    $values[$idx]=is_numeric($value) ? trim($value) + 0 : trim($value);
                }

                //use $in for multiple values
                if (count($values)>1){
                    $filters[$key]=array('$in'=>$values);
                }else{
                    $filters[$key]=is_numeric($value) ? $value + 0 : $value;
                }
                
            }
        }

        $cursor = $collection->find(
            $filters,
            [
                'projection'=>$output_fields,
                'limit' => $limit,
                'skip'  => $offset
            ]
        );

        $output=array();
        $debug=true;
        if ($debug=true){
            $output['debug']=array(
                'filters'=>$filters,
                'options'=>$options,
                'fields'=>$output_fields
            );
        }

        $output['rows_count']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;
        $output['found']=$collection->count($filters);
        $output['total']=$collection->count($series_filter);
        $output['data']=array();
        

        foreach ($cursor as $document) {
            //convert to array from mongodb object
            $output['data'][]= iterator_to_array($document);
        }

        $output['rows_count']=count($output['data']);
        return $output;
   } 


   function import_csv($db_id,$series_id,$csv_path,$delimiter='')
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
        
        $intval_func= function($value){
            if (is_numeric($value)){
                return $value + 0;
            }

            return utf8_encode(utf8_decode($value));
        };

        foreach($records as $row){
            $row=array_map($intval_func, $row);            
            $total++;
            $row['_db_id']=$db_id;
            $row['_series_id']=$series_id;
            $chunked_rows[]=$row;

            if($k>=$chunk_size){
                $result=$this->series_batch_insert($db_id,$series_id,$chunked_rows);
                $k=1;
                $chunked_rows=array();
                set_time_limit(0);
                //break;
            }

            $k++;				
        }

        if(count($chunked_rows)>0){
            $result=$this->series_batch_insert($db_id,$series_id,$chunked_rows);
        }

        return $total;
   }


   function get_series_distinct_values($db_id,$series_id,$field)
   {

        //validate field name [max length, allowed characters]
        if (strlen($field)>100){
            throw new Exception("Field name too long");
        }

        //allow ._a-zA-Z0-9
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $field)){
            throw new Exception("Invalid field name");
        }
        

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_series_name($db_id,$series_id)};

         $pipeline = [
                [
                 '$match' => [
                      '_db_id' => $db_id, 
                      '_series_id' => $series_id
                 ]
                ],
                [
                 '$group' => [
                      '_id' => '$'.$field,
                        'count' => [
                             '$sum' => 1
                        ]
                 ]
                        ],
                [
                    //$project use field name
                    '$project' => [
                        //$field => '$_id',
                        '_id' => 0,
                        'value' => '$_id',
                        'count' => 1
                    ]
                ]       
          ];

        $cursor = $collection->aggregate($pipeline);
        return $cursor->toArray();
   }



   /**
    * 
    * Get projection fields from a comma separated string
    *
    *
    */
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
    * Operator	Meaning	Note
    * eq	Equals [default]
    * ne	Not equal to	
    * lt	Less than	
    * le	Less than or equal to	
    * gt	Greater than	
    * ge	Greater than or equal to	
    * co	Contains	
    * nc	Does not contain	
    * sw	Starts with	
    * ew	Ends with
    *
    *
    * querystring url format: 
    *   c[AGE]=ge:18&c[AGE]=lt:65
    *   c[AGE]=18,12,5
    *   c[AGE]=18,12,5-10 -- range is only supported for numeric values and for equal operator
    * 
    *    
    */
    function get_query_value($qs_value)
    {
        $qs_value=trim($qs_value);
        $qs_value_parts=explode(":",$qs_value);

        if (count($qs_value_parts)==1){
            return array('eq'=>$qs_value_parts[0]);
        }

        return array($qs_value_parts[0]=>$qs_value_parts[1]);
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


   /**
    * 
    * Validate data structure against schema
    *
    */
   function validate_data_structure($data)
   {
       $schema_file="application/schemas/data-api-schema.json";

       if(!file_exists($schema_file)){
           throw new Exception("DATA-API-SCHEMA-NOT-FOUND");
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
            $this->validate_additional_data_structure($data);
            return true;
       } else {			
           /*foreach ($validator->getErrors() as $error) {
               echo sprintf("[%s] %s\n", $error['property'], $error['message']);
           }*/
           throw new ValidationException("SCHEMA_VALIDATION_FAILED [DATA_API]: ", $validator->getErrors());
       }
   }



   /**
    * 
    * Additional validation for data structure
    * 
    *  - there must be one Geography column_type
    */
   function validate_additional_data_structure($data)
   {
        $geography_count=0;

        foreach($data['data_structure'] as $field){
            if ($field['column_type']=='geography'){
                $geography_count++;
            }
        }

        if ($geography_count==0){
            throw new ValidationException("SCHEMA_VALIDATION_FAILED [DATA_API]: ", "At least one `geography` column_type is required");
        }
        else if ($geography_count>1){
            throw new ValidationException("SCHEMA_VALIDATION_FAILED [DATA_API]: ", "Only one `geography` column_type is allowed");
        }
   }


   	
}    
