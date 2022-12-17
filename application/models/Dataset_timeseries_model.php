<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Timeseries
 * 
 */
class Dataset_timeseries_model extends Dataset_model {
 
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 
     * Update dataset
     * 
     * @merge_metadata - boolean
     *  true  - merge/update individual values
     *  false - replace all metadata with new values (no merge)
     * 
     */
    function update_dataset($sid,$type,$options, $merge_metadata=false)
	{
		//need this to validate IDNO for uniqueness
        $options['sid']=$sid;
        
        //merge/replace metadata
        if ($merge_metadata==true){
            $metadata=$this->get_metadata($sid);
            if(is_array($metadata)){
                unset($metadata['idno']);                
                $options=$this->array_merge_replace_metadata($metadata,$options);
                $options=array_remove_nulls($options);
            }
        }

        return $this->create_dataset($type,$options,$sid);
    }

    function create_dataset($type,$options, $sid=null)
	{
		//validate schema
        $this->validate_schema($type,$options);

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);
		
		if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
			throw new exception("IDNO-NOT-SET");
		}

		//validate IDNO field
        $dataset_id=$this->find_by_idno($core_fields['idno']); 


		if(!empty($sid)){//for updating a study
            //if IDNO is changed, it should not be an existing IDNO
            if(is_numeric($dataset_id) && $sid!=$dataset_id ){
                throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$dataset_id.':'.$core_fields['idno']);
            }

            $dataset_id=$sid;
        }
        else{//for creating new study or overwritting existing one
            if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
                throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
            }
        }

        $options['changed']=date("U");
        
        $study_metadata_sections=array('metadata_creation','series_description','provenance','embeddings','lda_topics','tags','additional');        

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }                        

		//start transaction
		$this->db->trans_start();
        
        if($dataset_id>0){
            //update
            $this->update($dataset_id,$type,$options);
        }
        else{
		    //insert record
            $dataset_id=$this->insert($type,$options);
        }

        $this->update_filters($dataset_id,$options['metadata']);

		//complete transaction
		$this->db->trans_complete();

		return $dataset_id;
    }


    /**
     * 
     * Update all related tables used for facets/filters
     * 
     * 
     */
    function update_filters($sid, $metadata=null)
    {
        if (!is_array($metadata)){            
            return false;
        }

        $core_fields=$this->get_core_fields($metadata);

		$this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);
        $this->add_tags($sid,$this->get_array_nested_value($metadata,'tags'));
        return true;
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
        $output['title']=$this->get_array_nested_value($options,'series_description/name');
        $output['idno']=$this->get_array_nested_value($options,'series_description/idno');

        $nations=(array)$this->get_array_nested_value($options,'series_description/geographic_units');	

        if (count($nations)>0 && isset($nations[0]['name'])){
            //$nation_names=array_column($nations,"name");
            $nation_names=array();
            foreach($nations as $nrow){
                //if(isset($nrow['type']) && strtolower($nrow['type'])=='country'){
                    $nation_names[]=$nrow['name'];
                //}
            }
            
            $output['nation']=$this->get_country_names_string($nation_names);
            $output['nations']=$nation_names;
        }
        else{
            $output['nation']='';
            $output['nations']=array();
        }    

        $output['abbreviation']=$this->get_array_nested_value($options,'series_description/abbreviation');
        
        //$auth_entity=$this->get_array_nested_value($options,'series_description/authoring_entity');
        //$output['authoring_entity']=$this->array_column_to_string($auth_entity,$column_name='name', $max_length=300);
        $output['authoring_entity']='';

        $years=$this->get_years($options);
        $output['year_start']=$years['start'];
        $output['year_end']=$years['end'];
        return $output;
    }
    


    /**
     * 
     * Return an array of country names
     * 
     */
	function get_country_names($nations)
	{
        if(!is_array($nations)){
            return false;
        }

        $nation_names=array();
        foreach($nations as $nation){
            $nation_names[]=$nation['name'];
        }	
        return $nation_names;	
    }
    

    /**
     * 
     * get years
     * 
     **/
	function get_years($options)
	{
		$years=array();
        $data_coll=$this->get_array_nested_value($options,'series_description/time_periods');
			
        if (is_array($data_coll)){
            //get years from data collection dates				
            foreach($data_coll as $row){
                $year_=substr($row['start'],0,4);
                if((int)$year_>0){
                    $years[]=$year_;
                }					
                if(isset($row['end'])){
                    $year_=substr($row['end'],0,4);
                    if((int)$year_>0){
                        $years[]=$year_;
                    }
                }
            }
        }

		$start=0;
		$end=0;
		
		if (count($years)>0){
			$start=min($years);
			$end=max($years);
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$start=$end;
		}

		return array(
			'start'=>$start,
			'end'=>$end
		);
    }
    

    function get_timeseries_db_id($sid)
    {
        $metadata=$this->get_metadata($sid);

        if(isset($metadata['series_description']['database_id'])){
            return $metadata['series_description']['database_id'];
        }

        return false;
    }

}