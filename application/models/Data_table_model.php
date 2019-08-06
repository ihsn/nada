<?php
class Data_table_model extends CI_Model {

    private $db_fields=array(
		'id',
		'sid',
		'file_id',
		'file_name',
		'description', 
		'case_count',
		'var_count',
		'producer',
		'data_checks',
		'missing_data',
		'version',
		'notes'
	);

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }


    /////////////////////// states /////////////////////////////////////////


    function create_state($uid,$name)
    {
        if($this->state_exists($name)){
            return false;
        }

        $options=array(
            'uid'=>$uid,
            'name'=>$name
        );

        $result=$this->db->insert('region_states', $options);

        echo $this->db->last_qury();
        return $result;
    }

    function state_exists($name)
    {
        $this->db->select("*");
        $this->db->where("name",$name);
        return $this->db->get("region_states")->row_array();
    }


    /////////////////////// districts /////////////////////////////////////////

    function create_district($uid,$state_name,$name)
    {
        //district?
        $district=$this->district_exists($state_name,$name);

        if($district){
            return true;
        }

        $state=$this->state_exists($state_name);
                
        $options=array(
            'uid'=>$uid,
            'state_uid'=>$state['uid'],
            'name'=>$name
        );

        return $this->db->insert('region_districts', $options);
    }


    function district_exists($state_name,$name)
    {
        //get state id
        $state=$this->state_exists($state_name);

        if(!$state){
            throw new Exception("STATE_NOT_FOUND: ". $state_name );
        }
        
        $this->db->select("*");
        $this->db->where("name",$name);
        $this->db->where("state_uid",$state['uid']);
        return $this->db->get("region_districts")->row_array();
    }

/////////////////////// sub-districts /////////////////////////////////////////    

    function create_subdistrict($uid,$state_name,$district_name,$subdistrict_name)
    {        
        /*
        $district=$this->district_exists($state_name,$district_name);

        if(!$district){
            throw new Exception("DISTRICT_NOT_FOUND: ". $district_name );
        }
        */

        $subdistrict=$this->subdistrict_exists($state_name,$district_name, $subdistrict_name);

        if($subdistrict){
            return false;
        }

        $district=$this->district_exists($state_name,$district_name);

        if(!$district){
            throw new Exception("DISTRICT_NOT_FOUND: ". $district_name );
        }
                
        $options=array(
            'uid'=>$uid,
            'district_uid'=>$district['uid'],
            'name'=>$subdistrict_name
        );

        return $this->db->insert('region_sub_districts', $options);
    }


    function subdistrict_exists($state_name,$district_name, $subdistrict_name)
    {
        //get district code
        $district=$this->district_exists($state_name,$district_name);

        if(!$district){
            throw new Exception("DISTRICT_NOT_FOUND: ". $district_name );
        }

        $this->db->select("*");
        $this->db->where("name",$subdistrict_name);
        $this->db->where("district_uid",$district['uid']);
        return $this->db->get("region_sub_districts")->row_array();
    }


/////////////////////// towns /////////////////////////////////////////    

    function create_town($uid,$state_name,$district_name,$town_name)
    {        
        $town=$this->town_exists($state_name,$district_name,$town_name);

        if($town || $this->town_uid_exists($uid)){
            return false;
        }

        $district=$this->district_exists($state_name,$district_name);

        if(!$district){
            throw new Exception("DISTRICT_NOT_FOUND: ". $district_name);
        }

                
        $options=array(
            'uid'=>$uid,
            'district_uid'=>$district['uid'],
            'name'=>$town_name
        );

        return $this->db->insert('region_towns', $options);
    }


    function town_exists($state_name,$district_name,$town_name)
    {
        //get state id
        $district=$this->district_exists($state_name,$district_name);

        if(!$district){
            throw new Exception("DISTRICT_NOT_FOUND: ". $district_name );
        }

        $this->db->select("*");
        $this->db->where("name",$town_name);
        $this->db->where("district_uid",$district['uid']);
        return $this->db->get("region_towns")->row_array();
    }
    
    function town_uid_exists($town_uid)
    {
        $this->db->select("*");
        $this->db->where("uid",$town_uid);        
        return $this->db->get("region_towns")->row_array();
    }

//////////////////////////////////////////////////////////////////////////////////////////////////



    function create_region($region_type,$uid,$state_name=null, $district_name=null, $subdistrict_name=null, $town_name=null)
    {    
        switch(trim(strtolower($region_type)))
        {
            case 'state':
                return $this->create_state($uid,$state_name);
            break;

            case 'district':
                return $this->create_district($uid,$state_name,$district_name);
            break;

            case 'tehsil / sub-district':
            case 'subdistrict':
                return $this->create_subdistrict($uid,$state_name,$district_name,$subdistrict_name);
            break;

            case 'town':
                return $this->create_town($uid,$state_name,$district_name,$town_name);
            break;

            default:
                return false;
        }    
    }


    function create_region_x($region_type,$code,$name, $pid)
    {
        $options=array(
            'region_type'=>$region_type,
            'code'=>$code,
            'name'=>$name,
            'pid'=>$pid
        );

        return $this->db->insert('region_codes', $options);
    }


    function region_exists($region_type,$name)
    {
        $this->db->select("*");
        $this->db->where("region_type",$region_type);
        $this->db->where("name",$name);
        return $this->db->get("region_codes")->row_array();
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
        /*
        $options['state_name'],
        $options['district_name'],
        $options['subdistrict_name'],
        $options['town_name']
        */

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


    public function table_batch_insert($rows)
    {
        $valid_fields=array(
            'table_id',
            'census',
            'geo_level',
            'state_code',
            'district_code',
            'subdistrict_code',
            'town_code',
            'indicator',
            'value',
            'feature_1',
            'feature_2',
            'feature_3',
            'feature_4',
            'feature_5'
        );

        /*$required=array(
            'table_id',
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
        );*/

        //remove fields that are not in the valid_fields list
        foreach($rows as $key=>$row)
        {
            $rows[$key]=array_intersect_key($row,array_flip($valid_fields));
            if(!isset($row['table_id'])){
                throw new Exception("MISSING::TABLE_ID");
            }            
        }

        $result= $this->db->insert_batch('census_table', $rows);

        if ($result===false){
			$error=$this->db->error();
			throw new Exception(implode(", ",$error));			
        }
        
        return $result;
    }



   //delete all rows by table ID
   function truncate_table($table_id=null)
   {
       $this->db->where("table_id",$table_id);
       return $this->db->delete("census_table");
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
        $table_fields=array(
            'table_id',            
            'geo_level',
            'state_code',
            'district_code',
            'subdistrict_code',
            'town_code',
            'ward_code',
            'indicator',
            'value'            
        );   


       $region_fields=array(
           'geo_level',
           'state_code',
           'district_code',
           'subdistrict_code',
           'town_code',
           'ward_code'
       );
                            
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

        $this->db->select(implode(",",$table_fields));

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

       
        $output['data']=$this->db->get("census_table")->result_array();
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

        //indicators
        $indicators=array();

        if(isset($options['indicators'])){
            $indicators=$options['indicators'];
            unset($options['indicators']);
        }

    
        $code_list=array();


        //features
        for($i=1;$i<=9;$i++)
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
            if(!$this->codelist_exists($code['feature_name'], $code['code'])){
                $this->db->insert("census_table_codelist",$code);
            }
        }        
        

        //indicator
        if(isset($indicators)){
            foreach($indicators as $key=>$indicator){
                $indicator['table_id']=$options['table_id'];

                if (!$this->indicator_exists($options['table_id'],$indicator['code'])){
                    $this->db->insert('census_table_indicators',$indicator);
                }
            }            
        }

        //table type        
        if ($this->table_type_exists($options['table_id']))
        {
            $this->db->where('table_id',$options['table_id']);
            $result=$this->db->update("census_table_types",$options);
        }else{
            //create table type
            $result=$this->db->insert("census_table_types",$options);
        }

        return $result;
   }


   function table_type_exists($table_id)
   {
    $this->db->select("id");
    $this->db->where("table_id",$table_id);    
    return $this->db->get("census_table_types")->row_array();
   }


   //check if feature name + code combination already exists
   function codelist_exists($feature_name, $code)
   {
       $this->db->select("*");
       $this->db->where("feature_name",$feature_name);
       $this->db->where("code",$code);
       return $this->db->get("census_table_codelist")->result_array();
   }


    //check if indicator code already exists
    function indicator_exists($table_id,$code)
    {
        $this->db->select("*");
        $this->db->where("table_id",$table_id);
        $this->db->where("code",$code);
        return $this->db->get("census_table_indicators")->result_array();
    }


    /**
     * 
     * Get a count of tables with row counts
     * 
     */
    function get_tables_w_count()
    {
        return $this->db->query("select table_id,count(table_id) as total from census_table group by table_id")->result_array();        
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
        $table_type=$this->db->get("census_table_types")->row_array();

        if(!$table_type){
            throw new Exception("TABLE_NOT_FOUND");
        }

        //features
        $features_list=array();

        for($i=1;$i<=9;$i++)
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
            $features[$feature]=$this->get_feature_code_list($table_type[$feature]);
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
        $table_type=$this->db->get("census_table_types")->row_array();

        if(!$table_type){
            throw new Exception("TABLE_NOT_FOUND");
        }

        //features
        $features_list=array();

        for($i=1;$i<=9;$i++)
        {
            $feature='feature_'.$i;            
            if(isset($table_type[$feature])){
                $features_list[$feature]=$table_type[$feature];
            }
        }

        return $features_list;        
    }




    function get_feature_code_list($feature_name)
    {
        $this->db->select("code,label");
        $this->db->where("feature_name",$feature_name);        
        return $this->db->get("census_table_codelist")->result_array();
    }


    function get_indicator_codelist($table_id)
    {
        $this->db->select("code,label,measurement_unit");
        $this->db->where("table_id",$table_id);        
        return $this->db->get("census_table_indicators")->result_array();
    }


    function delete_table_data($table_id)
    {
        $this->db->where('table_id',$table_id);
        return $this->db->delete('census_table');
    }
	
}    