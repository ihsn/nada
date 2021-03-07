<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Image
 * 
 */
class Dataset_image_model extends Dataset_model {
 
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
            throw new ValidationException("VALIDATION_ERROR", "image_description.IDNO is required");
		}

		//validate IDNO field
        $dataset_id=$this->find_by_idno($core_fields['idno']); 

		//overwrite?
		if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
        }
        
        //fields to be stored as metadata
        $study_metadata_sections=array('metadata_information','image_description','files','additional');

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

		//update years
        $this->update_years($dataset_id,$core_fields['year_start'],$core_fields['year_end']);
        
        //countries
        $this->Survey_country_model->update_countries($dataset_id,$core_fields['nations']);

		//set topics

        //update tags
        $this->update_survey_tags($dataset_id, $this->get_tags($options['metadata']));

		//set aliases

		//set geographic locations (bounding box)

		//complete transaction
		$this->db->trans_complete();

		return $dataset_id;
    }


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

        //validate schema
        $this->validate_schema($type,$options);

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);
		
		//validate IDNO field
		$new_id=$this->find_by_idno($core_fields['idno']);

		//if IDNO is changed, it should not be an existing IDNO
		if(is_numeric($new_id) && $sid!=$new_id ){
			throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$new_id.':'.$core_fields['idno']);
        }                

        $options['changed']=date("U");
        
        //fields to be stored as metadata
        $study_metadata_sections=array('metadata_information','image_description','files','additional');

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }                

		//start transaction
		$this->db->trans_start();
        
        $this->update($sid,$type,$options);
        
		//update years
        $this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);
        
        //countries
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);

		//set topics

        //update tags
        $this->update_survey_tags($sid, $this->get_tags($options['metadata']));

		//set aliases

		//set geographic locations (bounding box)

		//complete transaction
		$this->db->trans_complete();

		return $sid;
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
        $output['title']=$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/headline');
        $output['idno']=$this->get_array_nested_value($options,'image_description/idno');
        
        //extract country names from the location element
        $nations=$this->get_country_names($this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/locationsShown'));    
        $output['nations']=$nations;
        $nation_str=$this->get_country_names_string($nations);        
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);
        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;
        
        $output['abbreviation']='';
        
        $creators=(array)$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/creatorNames');
		$output['authoring_entity']=implode(",", $creators);

        $date=explode("-",$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/dateCreated'));

		if(is_array($date)){
			$output['year_start']=(int)$date[0];
			$output['year_end']=(int)$date[0];			
        }
        
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
            if(isset($nation['countryName'])){
                $nation_names[]=$nation['countryName'];
            }
        }	
        return $nation_names;	
    }
    
    /**
     * 
     * Return a comma separated list of country names
     */
    function get_country_names_string($nations)
    {
        $nation_str=implode(", ",(array)$nations);
        if(strlen($nation_str)>150){
            $nation_str=substr($nation_str,0,145).'...';
        }
        return $nation_str;
    }


    /**
     * 
     * get tags
     * 
     **/
	function get_tags($options)
	{
        $tags=$this->get_array_nested_value($options,'image_description/tags');

        if(!is_array($tags)){
           return false;
        }

        $output=array();
        foreach($tags as $tag){
            $output[]=$tag['tag'];
        }

        return $output;
    }
    

}