<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


class Data_table_mongo_model extends CI_Model {

    private $db_fields=array(
        'dataset',
        'table_id',
        'geo_level',
        'geo_1',
        'geo_2',
        'geo_3',
        'geo_4',
        'geo_5',
        'indicator',
        'value',
        'feature_1',
        'feature_2',
        'feature_3',
        'feature_4',
        'feature_5',
        'feature_6',
        'feature_7',
        'feature_8',
        'feature_9',
        'feature_10',
    );

    private $geo_fields=array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model("Data_tables_places_model");
        $this->geo_fields=$this->Data_tables_places_model->get_geo_mappings();
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

    function transform_feature_fields($row)
    {

    }


    public function table_batch_insert($table_id,$rows)
    {
        //remove fields that are not in the valid_fields list
        foreach($rows as $key=>$row)
        {
            //$row=$this->transform_geo_fields($row);
            //$row=$this->transform_feature_fields($row);
            //$rows[$key]=array_intersect_key($row,array_flip($this->db_fields));

            if(isset($row['age'])){
                $rows[$key]['age']=intval($row['age']);
            }
        }

        //$result= $this->db->insert_batch('data_tables', $rows);


        $collection = (new MongoDB\Client)->census->{$table_id};

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
   function get_table_data($table_id,$limit=100,$options)
   {
        if (!$limit){
            $limit=100;
        }

        $collection = (new MongoDB\Client)->census->{$table_id};
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
        
        $cursor = $collection->find(
            [
            ],
            [
                'projection'=>[
                    '_id'=>0
                ],
                'limit' => $limit
            ]
        );
        $output=array();
        $output['data']=array();
        foreach ($cursor as $document) {
            $output['data'][]= $document;
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

        $wheres=array();

        foreach($parsed_val as $val){
            if($val['type']=='range'){
                $start=(int)$val['start'];
                $end=(int)$val['end'];
                $wheres[]="($feature_name BETWEEN  $start AND $end)";
                //$this->db->where("($feature_name BETWEEN $start AND $end)");
            }else if($val['type']=='value'){
                //$this->db->where($feature_name,$val['value']);
                $wheres[]=$feature_name." = ".$this->db->escape($val['value']);
            }        
        }

        if(count($wheres)>0){
            $this->db->where("(".implode(" OR ", $wheres).")",false, false);
        }
        
   }

    
   
   function create_table($table_id,$options)
   {
        //schema file name
        $schema_name='census-table_type';

        //validate schema
        $this->validate_schema($schema_name,$options);
    
        //remove table definition if already exists
        $this->delete_table_type($table_id);

        $options['_id']=$table_id;
        $collection = (new MongoDB\Client)->census->table_types;
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



    function get_tables_list($options=array())
    {
        $database = (new MongoDB\Client)->census;
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
    function get_table_info($table_id)
    {
        $collection = (new MongoDB\Client)->census->table_types;
        $document = $collection->findOne(['_id' => $table_id]);        
        return $document;
    }


    /**
     * 
     * 
     * Get features array - id, name 
     * 
     */
    function get_features_by_table($table_id)
    {
        //table type
        $this->db->select("*");
        $this->db->where("table_id",$table_id);        
        $table_type=$this->db->get("data_tables_types")->row_array();

        if(!$table_type){
            throw new Exception("TABLE_NOT_FOUND");
        }

        //features
        $features_list=array();

        for($i=1;$i<=10;$i++)
        {
            $feature='feature_'.$i;            
            if(isset($table_type[$feature])){
                $features_list[$feature]=$table_type[$feature];
            }
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


    function delete_table_data($table_id)
    {
        $this->db->where('table_id',$table_id);
        return $this->db->delete('data_tables');
    }


    function delete_table_type($table_id)
    {
        $collection = (new MongoDB\Client)->census->table_types;
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
	
}    