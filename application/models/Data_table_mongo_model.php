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

    public function __construct()
    {
        parent::__construct();
        //$this->load->model("Data_tables_places_model");
        //$this->geo_fields=$this->Data_tables_places_model->get_geo_mappings();
		//$this->output->enable_profiler(TRUE);
    }


    

    //map geo fields
    function transform_geo_fields($row)
    {
        if(!isset($row['geo_level'])){
            throw new Exception("geo_level not set");
        }
        
        //set geo_level
        if(!is_numeric($row['geo_level'])){
            $geo_level=$this->Data_tables_places_model->get_geo_levels($row['geo_level']);
            $row['geo_level']=$geo_level['code'];
        }

        foreach($this->geo_fields as $geo_name=>$geo_field){
            if(isset($row[$geo_name])){
                if($geo_field['code']!=false){
                    $row[$geo_field['code']]=(int)$row[$geo_name];
                }
            }
        }

        return $row;
    }


    public function table_batch_insert($db_id,$table_id,$rows)
    {
        $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->{$this->get_table_name($table_id)};

        $insertManyResult = $collection->insertMany($rows);        
        $inserted_count=$insertManyResult->getInsertedCount();
        
        if (!$inserted_count){
			throw new Exception($insertManyResult);
        }
        
        return $inserted_count;
    }




   //delete all rows by table ID
   function truncate_table($table_id=null)
   {
       $this->db->where("table_id",$table_id);
       return $this->db->delete("data_table");
   }


   
   function get_tables_list($db_id,$options=array()) 
   {
       $database = (new MongoDB\Client)->{$this->get_db_name($db_id)};
       $cursor = $database->command(['listCollections' => 1, 'nameOnly'=> true ]);

       $output=array();
       foreach ($cursor as $collection) {
           $output[]=$collection;
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
       $database = (new MongoDB\Client)->{$this->get_db_name($db_id)};
       $collection_info = $database->command(['collStats' => $this->get_table_name($table_id), 'scale'=> 1024*1024 ]);
       $result= $collection_info->toArray()[0];
       $result['table_type']=$this->get_table_type($db_id,$table_id);
       return $result;
   }

   function get_table_type($db_id,$table_id)
   {
       $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->{'table_types'};
       $result = $collection->findOne(
           [
               'table_id'=>$table_id
           ]
       );
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
   function get_table_data($db_id,$table_id,$limit=100,$options)
   {
        if (!$limit){
            $limit=100;
        }

        $limit=intval($limit);

        $table_id=strtolower($table_id);

        //get table type info
        $this->table_type_obj= $this->get_table_type($db_id,$table_id);

        $features=$this->get_features_by_table($db_id,$table_id);

        $features=array_flip($features); //turn feature names (e.g. age) into keys

        //geo fields + others
        $geo_features=array(
            'scst'=>'scst',
            'state'=> 'state',
            'district'=> 'district',
            'geo_level'=>'geo_level',
            'indicator'=>'indicator'
        );

        $features=array_merge($features,$geo_features);


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

        $feature_filters=$tmp_feature_filters;


        $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->{$this->get_table_name($table_id)};
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

        $filter=array( 
           //'age'=>2,
            'age'=>array(
                '$in'=>array(2)
            ),

        );
        
        $cursor = $collection->find(
            $feature_filters,
            [
                'projection'=>[
                    '_id'=>0
                ],
                'limit' => $limit
            ]
        );

        $geo_features=array(
            'state',
            'district'
        );

        $output=array();
        $geo_codes=array();
        $output['features']=$features;        
        $output['feature_filters']=$feature_filters;
        $output['found']=$collection->count($feature_filters);
        $output['total']=$collection->count();
        $output['geo_codes']=array();
        $output['data']=array();
        

        foreach ($cursor as $document) {
            $output['data'][]= $document;
            foreach($geo_features as $geo_feature_name){
                if (isset($document[$geo_feature_name])){
                    $geo_codes[$geo_feature_name][]=$document[$geo_feature_name];
                }
            }
        }
        
        foreach($geo_codes as $geo_feature_name_=>$geo_values){
            $geo_codes[$geo_feature_name_]=array_values(array_unique($geo_values));
        }

        $output['geo_codes']=$geo_codes;
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



   function apply_feature_filter($feature_name,$value)
   {
        $parsed_val=$this->parse_filter_value($value);

        $output=array();
        $values=array();

        foreach($parsed_val as $val){
            if($val['type']=='range'){
                $start=(int)$val['start'];
                $end=(int)$val['end'];
               
               return array(
                        '$gte' => $start,
                        '$lte' => $end
                );


            }else if($val['type']=='value'){
                //$wheres[]=$feature_name." = ".$this->db->escape($val['value']);
                $values[]=is_numeric($val['value']) ? (int)$val['value']: $val['value'];
            }        
        }

        if (count($values)>0){
            return 
                array(
                    '$in'=>$values
            );
        }
   }

    
   
   function create_table($db_id,$table_id,$options)
   {
        $table_id=strtolower($table_id);

        //schema file name
        $schema_name='census-table_type';

        //validate schema
        $this->validate_schema($schema_name,$options);
    
        //remove table definition if already exists
        $this->delete_table_type($db_id,$table_id);

        $options['_id']=$table_id;
        $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->table_types;
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


   function table_type_exists($table_id)
   {
        $this->db->select("id");
        $this->db->where("table_id",$table_id);    
        return $this->db->get("data_tables_types")->row_array();
   }


   //check if feature name + code combination already exists
   function codelist_exists($table_id,$feature_name, $code)
   {
       $this->db->select("*");
       $this->db->where("table_id",$table_id);
       $this->db->where("feature_name",$feature_name);
       $this->db->where("code",$code);
       return $this->db->get("data_tables_codelist")->result_array();
   }


    //check if indicator code already exists
    function indicator_exists($table_id,$code)
    {
        $this->db->select("*");
        $this->db->where("table_id",$table_id);
        $this->db->where("code",$code);
        return $this->db->get("data_tables_indicators")->result_array();
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




    function get_feature_code_list($table_id,$feature_name)
    {
        $this->db->select("code,label");
        $this->db->where("table_id",$table_id);
        $this->db->where("feature_name",$feature_name);        
        return $this->db->get("data_tables_codelist")->result_array();
    }


    function get_indicator_codelist($table_id)
    {
        $this->db->select("code,label,measurement_unit");
        $this->db->where("table_id",$table_id);        
        return $this->db->get("data_tables_indicators")->result_array();
    }


    function delete_table_data($db_id,$table_id)
    {
        $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->{$this->get_table_name($table_id)};
        $result = $collection->drop();
        return $result;
    }


    function delete_table_type($db_id,$table_id)
    {
        $collection = (new MongoDB\Client)->{$this->get_db_name($db_id)}->table_types;
        $result = $collection->deleteOne(['_id' => $table_id]);
        return $result->getDeletedCount();
    }



    function get_db_error()
    {
        $error=$this->db->error();
        if(is_array($error)){
            return implode(", ",$error);
        }		
    }


    function geo_search($options)
   {
        $limit=100;

        //geo fields + others
        $features=array(
            'level'=>'level',
            'state'=> 'state',
            'district'=> 'district',
            'subdistrict'=> 'subdistrict',
            'town_village'=>'town_village',
            'ward'=> 'ward',
            'areaname'=> 'areaname',
        );

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

        $feature_filters=$tmp_feature_filters;


        $collection = (new MongoDB\Client)->census->{"geo_codes"};
        
        $cursor = $collection->find(
            $feature_filters,
            [
                'projection'=>[
                    '_id'=>0
                ],
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


   function import_csv($db_id,$table_id,$csv_path,$delimeter='')
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
                return intval($value);
            }

            return $value;
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

   
   private function get_db_name($db_id){
    return 'db_'.$db_id;
    }

    private function get_table_name($table_id){
        return strtolower('table_'.$table_id);
    }

	
}    