<?php
class Data_table_model extends CI_Model {

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


    

    /*

CREATE TABLE `census_table` (
  `id` int(11) NOT NULL,
  `census` int(11) DEFAULT NULL,
  `scst` varchar(3) DEFAULT NULL,
  `table_id` varchar(45) DEFAULT NULL,
  `geo_level` varchar(45) DEFAULT NULL COMMENT 'national\nstate\ndistrict\nsubdistrict\ntown\nvillage',
  `state_code` varchar(45) DEFAULT NULL,
  `district_code` varchar(45) DEFAULT NULL,
  `town_code` varchar(45) DEFAULT NULL,
  `subdistrict_code` varchar(45) DEFAULT NULL,
  `village_code` varchar(45) DEFAULT NULL,
  `residence` varchar(45) DEFAULT NULL COMMENT 'national\nurban\nrural',
  `value` varchar(45) DEFAULT NULL,
  `feature_1` varchar(45) DEFAULT NULL,
  `feature_2` varchar(45) DEFAULT NULL,
  `feature_3` varchar(45) DEFAULT NULL,
  `feature_4` varchar(45) DEFAULT NULL,
  `feature_5` varchar(45) DEFAULT NULL,
  `feature_6` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    */
    /*
    function insert_table($table_id,$options)
    {
        $valid_fields=array(
            'census',
            'scst',
            'geo_level',
            'state_code',
            'district_code',
            'subdistrict_code',
            'town_code',
            'residence',
            'value',
            'feature_1',
            'feature_2',
            'feature_3',
            'feature_4',
            'feature_5'
        );

        if(isset($options['features'])){
            $k=1;
            foreach($options['features'] as $feature)
            {
                $options['feature_'.$k]=$feature;
                $k++;
            }
        }

        $data=array();

        foreach($options as $key=>$value){
			if (in_array($key,$valid_fields) ){
				$data[$key]=$value;
			}
        }
        
        $data['table_id']=$table_id;

        return $this->db->insert('census_table', $data);

    }
    */

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


    public function table_batch_insert($rows)
    {
        //remove fields that are not in the valid_fields list
        foreach($rows as $key=>$row)
        {
            $row=$this->transform_geo_fields($row);
            //$row=$this->transform_feature_fields($row);
            $rows[$key]=array_intersect_key($row,array_flip($this->db_fields));
        }

        $result= $this->db->insert_batch('data_tables', $rows);

        if (!$result){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));			
        }
        
        return $result;
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
   function get_table_data($table_id,$limit=1000,$options)
   {
       //alias=>name
        $table_fields=array(
            'table_id'=>'table_id',            
            //'geo_level',
            //'geo_1',
            //'geo_2',
            //'geo_3',
            //'geo_4',
            //'geo_5',
            'indicator'=>'indicator',
            'value'=>'value'
        );   


       $region_fields=array(
           'geo_level',
           'geo_1',
           'geo_2',
           'geo_3',
           'geo_4',
           'ward'
       );

       //geo fields       
       $geo_fields=$this->Data_tables_places_model->get_geo_codes_list();
       $table_fields[]='geo_level';

       foreach($geo_fields as $geo_field_num=>$geo_code){
        $table_fields[$geo_field_num] =$geo_code;
       }

       //features

       //get features list for the table
       $features=$this->get_features_by_table($table_id);

       //flip keys with values for looking up features by names
       $features_flip=array_flip($features);

       $feature_filters=array();

       //see if any key matches with the feature name
       foreach($options as $key=>$value)
       {
           if(array_key_exists($key,$features_flip)){
                $feature_filters[$features_flip[$key]]=$value; //feature_1=something
           }
       }
       
       //filter by features - uses feature_1, feature_2,... for searching
       foreach($feature_filters as $feature_key=>$value){
            $this->apply_feature_filter($feature_key,$value);
       }

       //apply region filter
       /*foreach($region_fields as $region){
            if(isset($options[$region]) && trim($options[$region])!=''){
                $this->db->where($region,$options[$region]);
            }
       }*/

       //filter by geo fields. switch fields from the format state=01 to geo_1=01
       foreach($geo_fields as $geo_field_num=>$geo_code){
           if(isset($options[$geo_code])){
            $options[$geo_field_num]=$options[$geo_code];
           }
       }



       //apply region filter
       foreach($region_fields as $region){
        if(isset($options[$region]) && trim($options[$region])!=''){
            $this->apply_feature_filter($region,trim($options[$region]));
        }        
       } 


       if(isset($options['indicator']) && is_numeric($options['indicator'])){
        $this->db->where('indicator',$options['indicator']);
       }
       
       $this->db->where("table_id",$table_id);
       
       if($limit >0){
           $this->db->limit($limit);
       }
       else{
           $this->db->limit(10);
       }

       //which fields to output
       if(isset($options['fields'])){
            $fields=explode(",",$options['fields']);
            foreach($fields as $idx=>$field){
                if(!in_array($field,$table_fields)){
                    unset($fields[$idx]);
                }
            }

            if(count($fields)>0){
                $table_fields=$fields;
            }
        }

        $table_fields_=array();
        foreach($table_fields as $alias=>$column_name)
        {
            $this->db->select($alias .' as '. $column_name);    
        }
        //$this->db->select(implode(",",$table_fields));

        //feature fields to include
        if(isset($options['fields'])){
            $fields=explode(",",$options['fields']);
            foreach($fields as $idx=>$field){
                //feature name e.g sex
                if(array_key_exists($field,$features_flip)){
                    $this->db->select($features_flip[$field] . ' as '.$field);
                }
                else {
                    if(!in_array($field,$table_fields)){
                        unset($fields[$idx]);
                    }
                }
            }

            if(count($fields)>0){
                $table_fields=$fields;
            }
        }
        else{
            //default - load all feature fields
            foreach($features as $feature_column=>$feature_name){
                $this->db->select($feature_column . ' as '.$feature_name);
            }
        }

        $result=$this->db->get("data_tables");

        if(!$result){
            throw new exception("DB ERROR");
        }

        $output['data']=$result->result_array();
        $output['query']=$this->db->last_query();

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

    
   
   function create_table($options)
   {       
        $table_id=$options['table_id'];

        //remove table definition if already exists
        $this->delete_table_type($table_id);

        //indicators
        $indicators=array();

        if(isset($options['indicator'])){
            $indicators=$options['indicator'];
            unset($options['indicator']);
        }
    
        $code_list=array();

        //features
        for($i=1;$i<=10;$i++)
        {
            $feature='feature_'.$i;
            
            if(isset($options[$feature])){
                if(isset($options[$feature]['code_list'])){
                    foreach($options[$feature]['code_list'] as $code){
                        $tmp=$code;
                        $tmp['feature_name']=$options[$feature]['feature_name'];
                        $code_list[]=$tmp;
                    }
                }
                $options[$feature]=$options[$feature]['feature_name'];
            }
        }
            
        //create feature codes        
        foreach($code_list as $code){
            $code['table_id']=$options['table_id'];
            if(!$this->codelist_exists($options['table_id'],$code['feature_name'], $code['code'])){
                $this->db->insert("data_tables_codelist",$code);
            }
        }        
        

        //indicator
        if(isset($indicators)){
            foreach($indicators as $key=>$indicator){
                $indicator['table_id']=$options['table_id'];

                if (!$this->indicator_exists($options['table_id'],$indicator['code'])){
                    $this->db->insert('data_tables_indicators',$indicator);
                }
            }            
        }


        //table type valid fields
        $type_fields=array('table_id','title','description','feature_1','feature_2','feature_3','feature_4','feature_5','feature_6','feature_7','feature_8','feature_9','feature_10');

        $type_options=array();
        foreach($options as $key=>$value){
			if (in_array($key,$type_fields) ){
				$type_options[$key]=$value;
			}
		}

        //table type        
        if ($this->table_type_exists($type_options['table_id']))
        {
            $this->db->where('table_id',$type_options['table_id']);
            $result=$this->db->update("data_tables_types",$type_options);
        }else{
            //create table type
            $result=$this->db->insert("data_tables_types",$type_options);
        }

        return $result;
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
        $counts=array();
        if(!isset($options['ignorecounts'])){
            $counts=$this->get_tables_w_count();
        }
        $output=array();

        foreach($counts as $row){
            $output[$row['table_id']]['records']=$row['total'];
        }

        $this->db->select("table_id,title");
        $result= $this->db->get("data_tables_types")->result_array();

        foreach($result as $row){
            $output[$row['table_id']]['title']=$row['title'];
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
        //table type
        $this->db->select("*");
        $this->db->where("table_id",$table_id);        
        $table_type=$this->db->get("data_tables_types")->row_array();

        if(!$table_type){
            throw new Exception("TABLE_NOT_FOUND");
        }

        $table_type['rows_count']=$this->get_table_count($table_id);

        //features
        $features_list=array();

        for($i=1;$i<=10;$i++)
        {
            $feature='feature_'.$i;            
            if(isset($table_type[$feature])){
                $features_list[]=$feature;
            }else{
                unset($table_type[$feature]);
            }
        }

        $features=array();
        foreach($features_list as $feature){
            $features[$table_type[$feature]]=$this->get_feature_code_list($table_id,$table_type[$feature]);
        }

        $table_type['features_codelist']=$features;

        //indicators
        $table_type['indicators_codelist']=$this->get_indicator_codelist($table_id);

        return $table_type;
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
        //table codelist
        $this->db->where('table_id',$table_id);
        $this->db->delete('data_tables_codelist');

        //table type
        $this->db->where('table_id',$table_id);
        $this->db->delete('data_tables_types');

        //table indicators
        $this->db->where('table_id',$table_id);
        $this->db->delete('data_tables_indicators');        
    }



    function get_db_error()
    {
        $error=$this->db->error();
        if(is_array($error)){
            return implode(", ",$error);
        }		
    }
	
}    