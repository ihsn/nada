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
			throw new exception("IDNO-NOT-SET");
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

		//set topics

        //update related countries

		//set aliases

		//set geographic locations (bounding box)

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
        $output['title']=$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/headline');
        $output['idno']=$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/digitalImageGuid');
        $output['nation']=$this->get_array_nested_value($options,'image_description/iptc/photoVideoMetadataIPTC/countryName');
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
    

}