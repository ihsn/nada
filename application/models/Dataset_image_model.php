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
        //IPTC or DCMI?
        $sub_schema_type=$this->get_image_schema_type($options);

        //clean metadata
        $options=$this->clean_metadata($options,$sub_schema_type);

        //validate schema
        $this->validate_schema($type,$options);

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);
		
		if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
            throw new ValidationException("VALIDATION_ERROR", "IDNO is required");
		}

        if(!isset($core_fields['title']) || empty($core_fields['title'])){
            throw new ValidationException("VALIDATION_ERROR", "Title is required");
		}

		//validate IDNO field
        $dataset_id=$this->find_by_idno($core_fields['idno']); 

		//overwrite?
		if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
        }
        
        //fields to be stored as metadata
        $study_metadata_sections=array('metadata_information','image_description','files','provenance','embeddings','lda_topics','tags','additional');

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

        //IPTC or DCMI?
        $sub_schema_type=$this->get_image_schema_type($options);

        //clean metadata
        $options=$this->clean_metadata($options,$sub_schema_type);

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
        $study_metadata_sections=array('metadata_information','image_description','files','provenance','embeddings','lda_topics','tags','additional');

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
       $core_fields=array();

       //use DCMI schema if filled in
       $core_fields=$this->get_core_fields_dcmi($options);

       if (empty($core_fields['title'])){
            $core_fields=$this->get_core_fields_iptc($options);
       }

        return $core_fields;       
    }


    //get core fields for DCMI schema
    function get_core_fields_dcmi($options)
    {
        $output=array();
        $output['title']=$this->get_array_nested_value($options,'image_description/dcmi/title');
        $output['idno']=$this->get_array_nested_value($options,'image_description/idno');
        
        //extract country names from the location element
        $nations=$this->get_country_names_dcmi((array)$this->get_array_nested_value($options,'image_description/dcmi/country'));
        $output['nations']=$nations;
        $nation_str=$this->get_country_names_string($nations);        
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);
        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;
        
        $output['abbreviation']='';
        
        $creators=(array)$this->get_array_nested_value($options,'image_description/dcmi/creator');
		$output['authoring_entity']=implode(",", $creators);

        $date=explode("-",$this->get_array_nested_value($options,'image_description/dcmi/date'));

		if(is_array($date)){
			$output['year_start']=(int)$date[0];
			$output['year_end']=(int)$date[0];			
        }
        
        return $output;
    }

    //get core fields for IPTC schema
    function get_core_fields_iptc($options)
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


    //check which schema to use
    function get_image_schema_type($options)
    {    
        $schema_type=false;

        //check if DCMI schema is used
        if($this->get_array_nested_value($options,'image_description/dcmi')){
            $schema_type='dcmi';
        }

        //check if IPTC schema is used
        if($this->get_array_nested_value($options,'image_description/iptc')){
            $schema_type='iptc';
        }
    
        return $schema_type;
    }


    /**
     * 
     * Remove metadata from the options array
     * 
     */
    function clean_metadata($options,$schema_type)
    {
        if($schema_type=='dcmi'){
            //remove IPTC schema
            if (isset($options['image_description']['iptc'])){
                unset($options['image_description']['iptc']);
            }            
        }
        else if($schema_type=='iptc'){
            //remove DCMI schema
            if (isset($options['image_description']['dcmi'])){
                unset($options['image_description']['dcmi']);
            }
        }
        else{
            throw new ValidationException("VALIDATION_ERROR", "Invalid schema. Use DCMI or IPTC schema.");
        }

        return $options;
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
     * Return an array of country names using DCMI schema
     * 
     *  names  (@name, @code)
     */
    function get_country_names_dcmi($nations)
    {
        if(!is_array($nations)){
            return false;
        }

        $nation_names=array();

        foreach($nations as $nation){
            if(isset($nation['name'])){
                $nation_names[]=$nation['name'];
            }
        }	
        return $nation_names;	
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