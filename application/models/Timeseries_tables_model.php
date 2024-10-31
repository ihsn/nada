<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;
use League\Csv\Reader;

require_once 'modules/mongodb/vendor/autoload.php';


/**
 * 
 * 
 * Timeseries info table (collection) model
 * 
 * 
 *  - stores information about timeseries:
 *    - series_id
 *    - db_id
 *    - name
 *    - description
 *    - data_structure
 *    - data_notes
 * 
 */

class Timeseries_tables_model extends CI_Model {

    private $mongo_client;
    private $table_name="timeseries_tables"; //collection name

    public function __construct() 
    {
        parent::__construct();
        $this->load->model("Timeseries_model");        
        $this->mongo_client=$this->Timeseries_model->get_db_connection();
    }


    /**
     * 
     * 
     * List timeseries available
     * 
     * 
     * @param $db_id - database id
     * @param $series_id - series id
     * 
     * @return array
     * 
     */
    public function list_timeseries($db_id=null, $series_id=null)
    {
        $filter=array();
        if (!empty($db_id)){
            $filter["db_id"]=$db_id;
        }
        if (!empty($series_id)){
            $filter["series_id"]=$series_id;
        }

        $projection=array(
            'data_structure'=>0,
            'data_notes'=>0
        );

        if (isset($db_id) && isset($series_id)){
            $projection=array();
        }

        $collection=$this->mongo_client->selectCollection($this->Timeseries_model->get_db_name(), $this->table_name);
        $cursor=$collection->find($filter, ['projection'=>$projection]);
        $result=array();
        foreach($cursor as $doc){
            $result[]=$doc;
        }
        return $result;
    }


    /**
     * 
     * Get all timeseries info
     * 
     */
    function search($db_id=null,$series_id=null,$limit=100,$offset=0,$options=array())
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>1000){
            $limit=100;
        }        

        //$collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_series_name($db_id,$series_id)};   
        $collection=$this->mongo_client->selectCollection($this->Timeseries_model->get_db_name(), $this->table_name);     

        if (!isset($options['fields'])){
            $options['fields']="";
        }

        //which fields to display
        $projection=array(
            'data_structure'=>0,
            'data_notes'=>0
        );

        //filters
        $filters=[];
        
        if (!empty($db_id)){
            $filters['db_id']=$db_id;
        }
        if (!empty($series_id)){
            $filters['series_id']=$series_id;
        }
        
        /*$series_filter=[
            '_db_id' => $db_id, 
            '_series_id' => $series_id
        ];*/

        //text search?
        if (isset($options['q']) && !empty($options['q'])){
            //$filters['$text'] = ['$search' => $options['q']];
            $filters['$text'] =$this->Timeseries_model->text_search($options['q']);            
        }

        

        //if query string is provided - check options[c]
        if (isset($options['c'])){
            foreach($options['c'] as $key=>$value){
                $filters[$key]=is_numeric($value) ? $value + 0 : $value; //this->get_query_value($value);
            }
        }

        $cursor = $collection->find(
            $filters,
            [
                'projection'=>$projection,
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
                'projection'=>$projection
            );
        }

        $output['rows_count']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;
        $output['found']=$collection->count($filters);
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
    * Get distinct list of values from series table
    * 
    */
   function get_distinct($field, $filter=array())
   {
        $collection=$this->mongo_client->selectCollection($this->Timeseries_model->get_db_name(), $this->table_name);
        $cursor = $collection->distinct($field,$filter);
        return $cursor;
   }

   function get_distinct_pipeline($field, $filter=array())
   {
        $collection=$this->mongo_client->selectCollection($this->Timeseries_model->get_db_name(), $this->table_name);
        $pipeline=[];

        //filter
        if (!empty($filter)){
            $pipeline[]=[
                '$match' => $filter
            ];
        }

        //group
        $pipeline[]=[
            '$group' => [
                '_id' => '$'.$field,
                'count' => [
                    '$sum' => 1
                ]
            ]
        ];

        /*$pipeline = [
                [
                 '$match' => $filter
                ],
                [
                 '$group' => [
                      '_id' => '$'.$field,
                        'count' => [
                             '$sum' => 1
                        ]
                 ]
                ]
          ];*/
          


          //use aggregation to get distinct values
        /* $pipeline = [
            [
             '$match' => [
                  '_db_id' => $db_id, 
                  '_series_id' => $series_id
             ]
            ],
            [
             '$group' => [
                  '_id' => '$areaName',
                    'count' => [
                         '$sum' => 1
                    ]
             ]
            ]
      ];*/

        $cursor = $collection->aggregate($pipeline)->toArray();        
        return $cursor;
   }
   


    /**
     * 
     * 
     * Insert timeseries info
     * 
     * 
     * @param $db_id - database id
     * @param $series_id - series id
     * @param $options - options
     * @param $overwrite - overwrite existing
     * 
     * @return array
     * 
     */
    public function upsert($db_id, $series_id, $options, $overwrite=false)
   {
        //schema file name
        //$schema_name='census-table_type';

        //validate schema
        //$this->validate_schema($schema_name,$options);
    
        //remove table definition if already exists
        //$this->delete_table_type($db_id,$table_id);

        $options['db_id']=(string)$db_id;
        $options['series_id']=(string)$series_id;
        $options['updated_on']=date("c");
        $options['_id']=$db_id.'-'.$series_id;

        $collection=$this->mongo_client->{$this->Timeseries_model->get_db_name()}->{$this->table_name};

        $series_filter=array(
            'db_id'=>$db_id,
            'series_id'=>$series_id
        );

        $result = $collection->updateOne($series_filter, ['$set'=>$options], ['upsert' =>$overwrite]);
        return true;        
   }
   

   public function delete($db_id, $series_id)
   {
        $collection=$this->mongo_client->{$this->Timeseries_model->get_db_name()}->{$this->table_name};
        $series_filter=array(
            'db_id'=>$db_id,
            'series_id'=>$series_id
        );
        $result = $collection->deleteOne($series_filter);
        
        if ($result->getDeletedCount()==0){
            throw new Exception("Timeseries not found");
        }

        return $result->getDeletedCount();
   }



   /**
    * 
    * Get data structure
    * 
    */
   public function get_data_structure($db_id, $series_id)
   {
        $collection=$this->mongo_client->{$this->Timeseries_model->get_db_name()}->{$this->table_name};
        $series_filter=array(
            'db_id'=>$db_id,
            'series_id'=>$series_id
        );
        $projection=array(
            'data_structure'=>1
        );
        $result = $collection->findOne($series_filter, ['projection'=>$projection]);
        if (empty($result)){
            throw new Exception("Timeseries not found");
        }
        
        if (!isset($result['data_structure'])){
            throw new Exception("Data structure not found");
        }

        return $result['data_structure'];
   }


   /**
    * 
    * Get data structure columns list
    *
    */
   public function get_data_structure_columns($db_id, $series_id)
   {
        $collection=$this->mongo_client->{$this->Timeseries_model->get_db_name()}->{$this->table_name};
        $series_filter=array(
            'db_id'=>$db_id,
            'series_id'=>$series_id
        );
        $projection=array(            
            "data_structure.name"=>1, 
            "data_structure.data_type"=>1, 
            "data_structure.column_type"=>1        
        );

        $result = $collection->findOne($series_filter, ['projection'=>$projection]);
        if (empty($result)){
            throw new Exception("Timeseries not found");
        }
        
        if (!isset($result['data_structure'])){
            throw new Exception("Data structure not found");
        }

        return json_decode(json_encode($result['data_structure']),true);
   }


   /**
    * 
    * Return data structure column by column type
    * - geography, time_period, observation_value, ...
    *
    */
   public function get_data_structure_column_by_type($db_id,$series_id,$column_type)
   {
        $dsd=$this->get_data_structure_columns($db_id,$series_id);

        if (!$dsd){
            return false;
        }

        foreach($dsd as $row)
        {
            if ($row['column_type']==$column_type){
                return $row;
            }
        }

        return false;
   }



   /**
    * 
    * Get single timeseries info
    *   
    */
   public function get_single($db_id, $series_id)
   {
        $collection=$this->mongo_client->{$this->Timeseries_model->get_db_name()}->{$this->table_name};
        $series_filter=array(
            'db_id'=>$db_id,
            'series_id'=>$series_id
        );

        $projection=array(
            //'data_structure'=>1
        );

        $result = $collection->findOne($series_filter, ['projection'=>$projection]);

        //convert to array from mongodb object
        if ($result !== null) {
            $result = json_decode(json_encode($result), true);
        } else {
            throw new Exception("Timeseries not found");
        }
        
        return $result;
   }


   public function validate_csv_data($db_id, $series_id, $csv_file_path)
   {
        //data structure columns
        $data_structure=$this->get_data_structure_columns($db_id, $series_id);
        $csv = Reader::createFromPath($csv_file_path);

        //get csv header
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        $header=array_map('trim', $header);
        $columns=array_column($data_structure, 'name');
        $columns=array_map('trim', $columns);

        $column_diff=[];

        //check if all columns are present from data structure
        foreach($columns as $column){
            if (!in_array($column, $header)){
                $column_diff[]=$column;
            }
        }

        if (!empty($column_diff)){
            throw new Exception("CSV data does not match with the Data structure definition: ".implode(",",$column_diff));
        }


        $column_errors=[];
        //check if csv column name only use alphanumeric characters and underscore
        foreach($header as $column){
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)){
                $column_errors[]=$column;
            }
        }

        if (!empty($column_errors)){
            throw new Exception("CSV column names should only contain alphanumeric characters and underscore: ".implode(",",$column_errors));
        }
        

        return true;
    }
   	
}    
