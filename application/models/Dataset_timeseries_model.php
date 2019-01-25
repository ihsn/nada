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
        $this->load->model('Data_file_model');
        $this->load->model('Variable_model');
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
        $id=$this->find_by_idno($core_fields['idno']); 

		//overwrite?
		/*if($id>0 && isset($options['overwrite']) && $options['overwrite']=='yes'){
			return $this->update_dataset($id,$type,$options);
		}*/

		if($id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$id);
        }
        
        //split parts of the metadata
        $data_files=null;
		$variables=null;
        $variable_groups=null;

        $study_metadata_sections=array('metadata_cration','database_description','additional');

        /*
        foreach($options as $key=>$value){		
			if(in_array($key,$this->$study_metadata_sections)){                
                $options['metadata'][$key]=$value;
                unset($options[$key]);
            }
        }
        */

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }
        
        if(isset($options['data_files'])){
            $data_files=$options['data_files'];
            unset($options['data_files']);
        }

        if(isset($options['variables'])){
            $variables=$options['variables'];
            unset($options['variables']);
        }

        if(isset($options['variable_groups'])){
            $variable_groups=$options['variable_groups'];
            unset($options['variable_groups']);
        }		
                

		//start transaction
		$this->db->trans_start();
        
        if($id>0){
            //update
            $dataset_id=$id;
            $this->update($id,$type,$options);
        }
        else{
		    //insert record
            $dataset_id=$this->insert($type,$options);
        }

		//update years
		$this->update_years($dataset_id,$core_fields['year_start'],$core_fields['year_end']);

		//set topics

        //update related countries
        $countries=$this->get_country_names($this->get_array_nested_value($options,'database_description/geographic_units'));
		$this->Survey_country_model->update_countries($dataset_id,$countries);

		//set aliases

		//set geographic locations (bounding box)


        //data files
        $this->create_update_data_files($dataset_id,$data_files);
        
        //variables
        $this->create_update_variables($dataset_id,$variables);


		//variable groups?
		//todo

		//complete transaction
		$this->db->trans_complete();

		return $dataset_id;
    }


    /*
    function update_dataset($sid,$type,$options)
	{
		//need this to validate IDNO for uniqueness
		$options['sid']=$sid;

		//validate schema
		$this->validate_schema($type,$options);

		//get core fields for listing datasets in the catalog
        //$data=$this->get_core_fields($type,$options);
        
        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);
		
		//validate IDNO field
		$new_id=$this->find_by_idno($core_fields['idno']);

		//if IDNO is changed, it should not be an existing IDNO
		if(is_numeric($new_id) && $sid!=$new_id ){
			throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$new_id.':'.$core_fields['idno']);
        }
        
        $dataset=$this->get_row_detailed($sid);
        $metadata=$dataset['metadata'];
        unset($metadata['idno']);

        //replace metadata with new options
        $options=array_replace_recursive($metadata,$options);
        $options['changed']=date("U");
				
		//split parts of the metadata
        $data_files=null;
		$variables=null;
        $variable_groups=null;

        $study_metadata_sections=array('metadata_cration','database_description','additional');

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }
        
        if(isset($options['data_files'])){
            $data_files=$options['data_files'];
            unset($options['data_files']);
        }

        if(isset($options['variables'])){
            $variables=$options['variables'];
            unset($options['variables']);
        }

        if(isset($options['variable_groups'])){
            $variable_groups=$options['variable_groups'];
            unset($options['variable_groups']);
        }

		//start transaction
		$this->db->trans_start();

		//update
		$this->update($sid,$type,$options);

		//update years
		$this->update_years($sid,$options['year_start'],$options['year_end']);

		//set topics
        //set countries
        $countries=$this->get_array_nested_value($options,'database_description/geographic_units');
        
        $countries=$this->get_country_names($countries);
		$this->Survey_country_model->update_countries($sid,$countries);

		//set aliases

        //set geographic locations (bounding box)
        //todo

		 //data files
         $this->create_update_data_files($sid,$data_files);
        
         //variables
         $this->create_update_variables($sid,$variables);
 
		
		//complete transaction
		$this->db->trans_complete();

		return $sid;
    }
    */


    
    private function create_update_variables($dataset_id,$variables)
    {        
        if(is_array($variables)){
			foreach($variables as $variable){
				//validate file_id exists
				$fid=$this->Data_file_model->get_fid_by_fileid($dataset_id,$variable['file_id']);
		
				if(!$fid){
					throw new exception("variable creation failed. Variable 'file_id' not found: ".$variable['file_id']);
				}
							
				$variable['fid']=$variable['file_id'];
				$this->Variable_model->validate_variable($variable);
			}

			$result=array();
			foreach($variables as $variable){
				$variable['fid']=$variable['file_id'];
				$variable['metadata']=$variable;
				$variable_id=$this->Variable_model->insert($dataset_id,$variable);
			}

			//update survey varcount
			$this->update_varcount($dataset_id);
		}
    }


    private function create_update_data_files($dataset_id,$data_files)
    {        
		if(is_array($data_files)){
			//create each data file
			foreach($data_files as $data_file){					
                $this->Data_file_model->validate_data_file($data_file);
                
				//check if file already exists?
                $file=$this->Data_file_model->get_file_by_id($dataset_id,$data_file['file_id']);
                
				if($file){
					$this->Data_file_model->update($file['id'],$data_file);
				}else{
					$this->Data_file_model->insert($dataset_id,$data_file);
				}
			}
		}
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
        $output['title']=$this->get_array_nested_value($options,'database_description/title');
        $output['idno']=$this->get_array_nested_value($options,'idno');

        $nations=$this->get_array_nested_value($options,'database_description/geographic_units');	
        $output['nation']=$this->get_country_names_string($this->get_country_names($nations));

        $output['abbreviation']=$this->get_array_nested_value($options,'database_description/abbreviation');
        
        $auth_entity=$this->get_array_nested_value($options,'database_description/authoring_entity');
        $output['authoring_entity']=$this->array_column_to_string($auth_entity,$column_name='name', $max_length=300);

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
     * Return a comma separated list of country names
     */
    function get_country_names_string($nations)
    {
        $nation_str=implode(", ",$nations);
        if(strlen($nation_str)>150){
            $nation_str=substr($nation_str,0,145).'...';
        }
        return $nation_str;
    }


    /**
     * 
     * get years
     * 
     **/
	function get_years($options)
	{
		$years=array();
        $data_coll=$this->get_array_nested_value($options,'database_description/time_coverage');
			
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

}