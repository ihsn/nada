<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Script
 * 
 */
class Dataset_script_model extends Dataset_model {
 
    public function __construct()
    {
        parent::__construct();
    }

    function create_dataset($type,$options)
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

		//overwrite?
		if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
        }
        
        //fields to be stored as metadata
        $study_metadata_sections=array('doc_desc','project_desc','provenance','embeddings','lda_topics','tags','additional');

        $options['metadata']=array();

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }                

		//start transaction
		$this->db->trans_start();
        
        if($dataset_id>0){
            $this->update($dataset_id,$type,$options);
        }
        else{
            $dataset_id=$this->insert($type,$options);
        }

		$this->update_filters($dataset_id,$options['metadata']);
        
		//complete transaction
		$this->db->trans_complete();

		return $dataset_id;
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
        $output['title']=$this->get_array_nested_value($options,'project_desc/title_statement/title');
        $output['subtitle']=$this->get_array_nested_value($options,'project_desc/title_statement/sub_title');
        $output['idno']=$this->get_array_nested_value($options,'project_desc/title_statement/idno');
        $output['doi']=$this->get_core_doi($options);

        $nations=(array)array_data_get($options, 'project_desc.geographic_units.*.name');        
        $output['nations']=$nations;
        $nation_str=$this->get_country_names_string($nations);
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);

        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;

        $output['abbreviation']=$this->get_array_nested_value($options,'abbreviation');
        
        $auth_entity=$this->get_array_nested_value($options,'project_desc/authoring_entity');
        $output['authoring_entity']=$this->array_column_to_string($auth_entity,$column_name='name', $max_length=300);

        $years=$this->get_years($options);
        $output['year_start']=$years['start'];
        $output['year_end']=$years['end'];
        return $output;
    }

    function get_core_doi($options)
    {
        $identifiers=(array)$this->get_array_nested_value($options,'project_desc/title_statement/identifiers');

        foreach($identifiers as $identifier){
            if (isset($identifier['type']) && strtolower($identifier['type'])=='doi'){
                return $identifier['identifier'];
            }
        }
    }

    /**
     * 
     * get years
     * 
     **/
	function get_years($options)
	{
		$years=array();
        $data_coll=$this->get_array_nested_value($options,'project_desc/production_date');

        if (!is_array($data_coll)){
            $data_coll=array($data_coll);
        }
        
        //get years
        foreach($data_coll as $date){
            $year_=substr($date,0,4);
            if((int)$year_>0){
                $years[]=$year_;
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
}