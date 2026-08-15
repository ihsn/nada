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


    function create_dataset($type,$options,$sid=null, $validate_schema=true)
	{
		//validate schema
        if ($validate_schema){
            $this->validate_schema($type,$options);
        }

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
        $study_metadata_sections=array('metadata_information','description','provenance','embeddings','lda_topics','tags','additional');

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

        // Body is stored in data_files/variables. Header stays in the metadata blob.
        $incoming_feature_types=$this->incoming_feature_types($options);
        if (isset($options['metadata']['description']['feature_catalogue']['featureType'])){
            unset($options['metadata']['description']['feature_catalogue']['featureType']);
        }
        
        if($dataset_id>0){
            $this->update($dataset_id,$type,$options);
        }
        else{
            $dataset_id=$this->insert($type,$options);
        }

        $this->update_filters($dataset_id,$options['metadata']);

        //import external resources
        $this->update_resources($dataset_id,$external_resources);

        $this->upsert_feature_catalog($dataset_id,$incoming_feature_types);
        $this->refresh_geospatial_keywords($dataset_id,$options['metadata'],$type);

        //complete transaction
		$this->db->trans_complete();

		return $dataset_id;
    }

    
    function update_dataset($sid,$type,$options, $merge_metadata=false, $validate_schema=true)
	{
        //need this to validate IDNO for uniqueness
        $options['sid']=$sid;
        
        //merge/replace metadata
        if ($merge_metadata==true){
            $metadata=$this->get_metadata($sid);
            
            if(is_array($metadata)){
                unset($metadata['idno']);
                // get_metadata() reattaches featureType from tables; do not treat that as caller input.
                if (isset($metadata['description']['feature_catalogue']['featureType'])){
                    unset($metadata['description']['feature_catalogue']['featureType']);
                }
                $options=$this->array_merge_replace_metadata($metadata,$options);
                $options=array_remove_nulls($options);
            }
        }

        return $this->create_dataset($type,$options,$sid, $validate_schema);        
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
                
        $output['title']=array_data_get($options, 'description.identificationInfo.citation.title');
        //$output['abbreviation']=$this->get_array_nested_value($options,'citation/alternateTitle');
        $output['idno']=array_data_get($options,'description.idno');

        $nations=(array)array_data_get($options, 'description.identificationInfo.extent.geographicElement.*.geographicDescription');

        $output['nations']=$nations;

        $nation_str=$this->get_country_names_string($nations);
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);

        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;

        //$auth_entity=$this->get_array_nested_value($options,'database_description/authoring_entity');
        $output['authoring_entity']='';
        
        //$years=$this->get_years($this->get_array_nested_value($options,'dataset_metadata/dateStamp'));
        //$dates=$this->get_array_nested_value($options,'dataset_metadata/identificationInfo/citation/date');

        $dates=array();
        if (isset($options['description']['identificationInfo']['citation']['date'])){
            $dates=$options['description']['identificationInfo']['citation']['date'];
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

        $feature_types=$this->get_feature_types_from_tables($sid);
        if (!empty($feature_types)){
            if (!isset($metadata['description']['feature_catalogue']) || !is_array($metadata['description']['feature_catalogue'])){
                $metadata['description']['feature_catalogue']=array();
            }
            $metadata['description']['feature_catalogue']['featureType']=$feature_types;
        }

        return $metadata;
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

        $bbox=$this->get_bbox_array($metadata);
        $this->update_locations($sid, $bbox);

        return true;
    }


    function get_bbox_array($metadata)
    {
        $bbox_arr=(array)array_data_get($metadata, 'description.identificationInfo.extent.geographicElement.*.geographicBoundingBox');

        $bbox=array();
        foreach($bbox_arr as $row){
            if (!isset($row['northBoundLatitude'])){
                continue;
            }
            $bbox[]=array(
                'north'=>$row['northBoundLatitude'],
                'south'=>$row['southBoundLatitude'],
                'east'=>$row['eastBoundLongitude'],
                'west'=>$row['westBoundLongitude']
            );
        }

        return $bbox;
    }


    /**
     * Replace feature types/attributes when the caller sent featureType.
     * null = not sent (leave tables). empty array = clear tables.
     */
    function upsert_feature_catalog($sid,$feature_types)
    {
        if ($feature_types===null){
            return false;
        }

        $this->Variable_model->remove_all_variables($sid);
        $this->Data_file_model->remove_all_files($sid);

        if (!is_array($feature_types) || empty($feature_types)){
            $this->update_varcount($sid);
            $this->Variable_model->update_survey_timestamp($sid);
            return true;
        }

        $file_counter=1;
        foreach($feature_types as $feature_type)
        {
            if (!is_array($feature_type)){
                continue;
            }

            $file_metadata=$feature_type;
            if (isset($file_metadata['carrierOfCharacteristics'])){
                unset($file_metadata['carrierOfCharacteristics']);
            }
            
            $file_id='F'.$file_counter++;
            $data_file=array(
                'sid'=>$sid,
                'file_id'=>$file_id,
                'file_name'=>isset($feature_type['typeName']) ? $feature_type['typeName'] : '',
                'description'=>isset($feature_type['definition']) ? $feature_type['definition'] : '',
                'metadata'=> json_encode($file_metadata)
            );

            $this->Data_file_model->insert($sid,$data_file);

            $car_chars=isset($feature_type['carrierOfCharacteristics']) ? $feature_type['carrierOfCharacteristics'] : null;
            $this->insert_carrierOfCharacteristics($sid,$file_id,$car_chars);
        }

        $this->update_varcount($sid);
        $this->Variable_model->update_survey_timestamp($sid);
        return true;
    }

    private function insert_carrierOfCharacteristics($sid,$fid,$car_chars)
    {
        if(!is_array($car_chars)){
            return false;
        }

        $var_counter=1;
        foreach($car_chars as $variable){
            if (!is_array($variable)){
                continue;
            }

            $vid='V'.$var_counter++;
            $listed_values=isset($variable['listedValue']) ? $variable['listedValue'] : '';
            $labl=isset($variable['definition']) ? (string)$variable['definition'] : '';
            
            if (strlen($labl)>255){
                $labl=substr($labl,0,250).'...';
            }

            $variable_metadata=array(
                'fid'=>$fid,
                'vid'=>$fid.'-'.$vid,
                'name'=>isset($variable['memberName']) ? $variable['memberName'] : '',
                'labl'=>$labl,
                'qstn'=>isset($variable['definition']) ? $variable['definition'] : '',
                'catgry'=>$this->listed_values_to_str($listed_values),
                'metadata'=>$variable
            );

            $this->Variable_model->insert($sid,$variable_metadata);
        }

        return true;
    }

    /**
     * featureType from the request, or null if the caller did not send it.
     */
    private function incoming_feature_types($options)
    {
        $catalog=null;
        if (isset($options['metadata']['description']['feature_catalogue']) && is_array($options['metadata']['description']['feature_catalogue'])){
            $catalog=$options['metadata']['description']['feature_catalogue'];
        }
        elseif (isset($options['description']['feature_catalogue']) && is_array($options['description']['feature_catalogue'])){
            $catalog=$options['description']['feature_catalogue'];
        }

        if ($catalog===null || !array_key_exists('featureType',$catalog) || !is_array($catalog['featureType'])){
            return null;
        }

        return $catalog['featureType'];
    }

    /**
     * Rebuild featureType[] from data_files + variables (response only).
     */
    private function get_feature_types_from_tables($sid)
    {
        $files=$this->Data_file_model->get_all_by_survey($sid);
        if (!is_array($files) || empty($files)){
            return array();
        }

        $variables_by_fid=array();
        $this->db->select('fid,uid,metadata');
        $this->db->where('sid',$sid);
        $this->db->order_by('uid','ASC');
        $rows=$this->db->get('variables')->result_array();
        foreach($rows as $row){
            $attribute=$this->decode_metadata($row['metadata']);
            if (!is_array($attribute)){
                continue;
            }
            $variables_by_fid[$row['fid']][]=$attribute;
        }

        $feature_types=array();
        foreach($files as $file){
            $feature_type=array();
            if (!empty($file['metadata'])){
                $decoded=json_decode($file['metadata'],true);
                if (is_array($decoded)){
                    $feature_type=$decoded;
                }
            }
            if (!empty($file['file_name'])){
                $feature_type['typeName']=$file['file_name'];
            }
            if (isset($file['description']) && $file['description']!=='' && $file['description']!==null){
                $feature_type['definition']=$file['description'];
            }
            $fid=isset($file['file_id']) ? $file['file_id'] : '';
            if ($fid!=='' && !empty($variables_by_fid[$fid])){
                $feature_type['carrierOfCharacteristics']=$variables_by_fid[$fid];
            }
            $feature_types[]=$feature_type;
        }

        return $feature_types;
    }

    /**
     * Study keywords from the header blob plus feature type names/definitions.
     */
    private function refresh_geospatial_keywords($sid,$metadata,$type)
    {
        $keywords=$this->extract_keywords($metadata,$type);
        $files=$this->Data_file_model->get_all_by_survey($sid);
        if (is_array($files)){
            $extra=array();
            foreach($files as $file){
                if (!empty($file['file_name'])){
                    $extra[]=$file['file_name'];
                }
                if (!empty($file['description'])){
                    $extra[]=$file['description'];
                }
            }
            if (!empty($extra)){
                $keywords.=' '.implode(' ',$extra);
            }
        }

        $this->db->where('id',$sid);
        $this->db->update('surveys',array('keywords'=>$keywords));
    }


    //convert listed values array to string
    function listed_values_to_str($listed_values)
    {
        if (!is_array($listed_values)){
            return '';
        }
        $output=array();
        foreach($listed_values as $lv){
            $output[]=isset($lv['label']) ? $lv['label'] : '';
            $output[]=isset($lv['definition']) ? $lv['definition'] : '';
        }

        return implode(" ",$output);
    }
}