<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Geospatial
 * 
 */
class Dataset_geospatial_model extends Dataset_model {
 
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Data_file_model');
        $this->load->model('Variable_model');
    }


    function create_dataset($type,$options,$sid=null)
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
        
        //fields to be stored as metadata
        $study_metadata_sections=array('description','tags','provenance','additional');

        foreach($study_metadata_sections as $section){
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }

        //external resources
        $external_resources=$this->get_array_nested_value($options,'metadata/description/distributionInfo/transferOptions/onLine');
        
        //remove external resource from metadata
        if($external_resources){
            unset($options['metadata']['description']['distributionInfo']['transferOptions']['onLine']);
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
        
        //import external resources
        $this->update_resources($dataset_id,$external_resources);

		//set topics

        //update related countries
        $this->Survey_country_model->update_countries($dataset_id,$core_fields['nations']);

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

        return $this->create_dataset($type,$options,$sid);        
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
                
        $output['title']=array_data_get($options, 'description.identificationInfo.0.citation.title');
        //$output['abbreviation']=$this->get_array_nested_value($options,'citation/alternateTitle');
        $output['idno']=array_data_get($options,'description.idno');

        $nations=(array)array_data_get($options, 'description.identificationInfo.*.extent.geographicElement.*.geographicDescription');

        $output['nations']=$nations;

        $nation_str=$this->get_country_names_string($nations);
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);

        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;

        //$auth_entity=$this->get_array_nested_value($options,'database_description/authoring_entity');
        $output['authoring_entity']='';
        
        //$years=$this->get_years($this->get_array_nested_value($options,'dataset_metadata/dateStamp'));
        //$dates=$this->get_array_nested_value($options,'dataset_metadata/identificationInfo/citation/date');

        $dates=array();
        if (isset($options['description']['identificationInfo'][0]['citation']['date'])){
            $dates=$options['description']['identificationInfo'][0]['citation']['date'];
        }

        $date_creation=null;
        foreach($dates as $date){
            if (isset($date['type']) && $date['type']=='creation'){
                $date_creation=$date['date'];
            }
        }

        if ($date_creation){
            $years=$this->get_years($date_creation);
        }else{
            $years=$this->get_years(array_data_get($options,'description.dateStamp'));
        }

        $output['year_start']=$years['start'];
        $output['year_end']=$years['end'];
        
        return $output;
    }
    


    /**
     * 
     * get years
     * 
     **/
	function get_years($year)
	{
		$year_parts=explode("-",$year);

        $start=0;
        $end=0;

		if(is_array($year_parts)){
            $start=(int)$year_parts[0];
            $end=(int)$year_parts[0];			
        }

		return array(
			'start'=>$start,
			'end'=>$end
		);
    }
    

    //returns survey metadata array
    function get_metadata($sid)
    {
        $metadata= parent::get_metadata($sid);

        $res_fields="resource_id,dctype,dcformat,title,author,dcdate,country,language,contributor,publisher,rights,description, abstract,toc,filename";
        $external_resources=$this->Survey_resource_model->get_survey_resources($sid, $res_fields);
        
        $online_resources=array();

        //add download link
        foreach($external_resources as $resource_filename => $resource){
            if (!$this->form_validation->valid_url($resource['filename']) && !empty($resource['filename'])){
                //$resource['filename']=site_url("catalog/{$sid}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
               $external_resources[$resource_filename]['filename']=site_url("catalog/{$sid}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
            }
            
            //unset null fields
            foreach($resource as $key=>$value){
                if (!$value){
                    unset($external_resources[$resource_filename][$key]);
                }
            }            
        }

        //add external resources
        $metadata['description']['distributionInfo']['transferOptions']['onLine']=$external_resources;
        return $metadata;
	}    


     /**
     * 
     * Update all related tables used for facets/filters
     * 
     * 
     */
    function update_filters($sid, $metadata)
    {
        $core_fields=$this->get_core_fields($metadata);

        //update years
		$this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);

        //update related countries
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);
    }

}