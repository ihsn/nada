<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Model for surveys table
 * 
 */
class Dataset_microdata_model extends Dataset_model {
 
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Data_file_model');
        $this->load->model('Variable_model');
        $this->load->model('Variable_group_model');
        $this->load->model('Form_model');
    }

    function create_dataset($type,$options)
	{
		//validate schema
        $this->validate_schema($type,$options);

        if (!isset($options['overwrite'])){
            $options['overwrite']='no';
        }

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($type,$options);
        $options=array_merge($options,$core_fields);
        
		if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
			throw new exception("IDNO-NOT-SET");
		}

		//validate IDNO field
        $dataset_id=$this->find_by_idno($core_fields['idno']); 

		//overwrite?
		/*if($id>0 && isset($options['overwrite']) && $options['overwrite']=='yes'){
			return $this->update_dataset($id,$type,$options);
		}*/

		if(is_numeric($dataset_id) && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
        }
        
        //split parts of the metadata
        $data_files=null;
		$variables=null;
        $variable_groups=null;

        $study_metadata_sections=array('doc_desc','study_desc','additional');

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
        
        if(isset($options['additional'])){
            $options['metadata']['additional']=$options['additional'];
            unset($options['additional']);
        }
                

		//start transaction
		$this->db->trans_start();

        if (!empty($dataset_id)){
            //update/replace existing study
            $this->update($dataset_id,$type,$options);
        }
        else{
		    //insert record
            $dataset_id=$this->insert($type,$options);
        }
        
        //set owner repo
        $this->Dataset_model->set_dataset_owner_repo($dataset_id,$options['repositoryid']); 

		//update years
		$this->update_years($dataset_id,$core_fields['year_start'],$core_fields['year_end']);

        //set topics
        $this->delete_topics($dataset_id);
        $this->update_topics($dataset_id,$this->get_array_nested_value($options,'study_desc/study_info/topics'));

        //get list of countries
        //$countries=$this->get_country_names($this->get_array_nested_value($options,'study_desc/study_info/nation'));

		//update countries
		$this->Survey_country_model->update_countries($dataset_id,$core_fields['nations']);

		//set aliases

		//set geographic locations (bounding box)


        //data files
        $this->create_update_data_files($dataset_id,$data_files, $remove_existing=true);
        
        //variables
        $this->create_update_variables($dataset_id,$variables, $remove_existing=true);

		//variable groups?
		$this->create_update_variable_groups($dataset_id,$variable_groups, $remove_existing=true);

		//complete transaction
        $this->db->trans_complete();
        
        $this->index_variable_data($dataset_id);

		return $dataset_id;
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

		//validate schema
		$this->validate_schema($type,$options);

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($type,$options);
        $options=array_merge($options,$core_fields);
		
		//validate IDNO field
		$new_id=$this->find_by_idno($core_fields['idno']);

		//if IDNO is changed, it should not be an existing IDNO
		if(is_numeric($new_id) && $sid!=$new_id ){
			throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$new_id.':'.$core_fields['idno']);
        }                

        $options['changed']=date("U");
				
		//split parts of the metadata
        $data_files=null;
		$variables=null;
        $variable_groups=null;

        if(isset($options['doc_desc'])){
            $options['metadata']['doc_desc']=$options['doc_desc'];
            unset($options['doc_desc']);
        }

        if(isset($options['study_desc'])){
            $options['metadata']['study_desc']=$options['study_desc'];
            unset($options['study_desc']);
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

        if(isset($options['additional'])){
            $options['metadata']['additional']=$options['additional'];
            unset($options['additional']);
        }

		//start transaction
		$this->db->trans_start(); 

		//update
        $this->update($sid,$type,$options);
        
        //set owner repo
        $this->Dataset_model->set_dataset_owner_repo($sid,$options['repositoryid']); 

		//update years
		$this->update_years($sid,$options['year_start'],$options['year_end']);

        //set topics
        $this->delete_topics($sid);
        $this->update_topics($sid,$this->get_array_nested_value($options,'metadata/study_desc/study_info/topics'));

		//set countries
		$this->Survey_country_model->update_countries($sid,$core_fields['nations']);

		//set aliases

        //set geographic locations (bounding box)
        //todo

		 //data files
         $this->create_update_data_files($sid,$data_files);
        
         //variables
         $this->create_update_variables($sid,$variables);

         //variable groups
         $this->create_update_variable_groups($sid,$variable_groups);
		
		//complete transaction
        $this->db->trans_complete();
        
        //concat variable metadata into a single field for study+variable search
        $this->index_variable_data($sid);

		return $sid;
    }
    

    /**
     * 
     * 
     * Store all variables text into a single field
     * 
     */
    function index_variable_data($sid)
    {
        $total_vars=$this->Variable_model->get_variables_count($sid);
        $include_categories=true;

        if($total_vars>8000){
            $include_categories=false;
        }

        $variables=$this->variable_chunk_reader($sid, $start_id=0, $limit=0,$include_categories);

        $output=[];
        $exclude_columns=array('file_id','vid','fid','var_qstn_qstnlit','var_sumstat','var_format','var_val_range');

        if($include_categories==false){
            $exclude_columns[]="catgry";
        }
        
        foreach($variables as $variable){
            $tmp=array();
            foreach($variable['metadata'] as $key=>$value){
                if(!in_array($key,$exclude_columns)){
                    $tmp[]=$value;
                }
            }
            
            $output[]=$this->array_to_plain_text($tmp);
        }

        $output=implode(" ",$output);

        $options=array(
            'var_keywords'=>$output
        );

        $this->db->where('id',$sid);
        $this->db->update("surveys",$options);
    }


    /**
     * 
     * Read variables in chunks
     * 
     */
    private function variable_chunk_reader($sid, $start_id=0, $limit=0,$include_categories=true)
    {
        //$this->db->select("uid,name,labl,qstn");
        $this->db->select("metadata");
        
        /*if($include_categories){
            //$this->db->select("catgry");
        }*/

        if($limit>0){
            $this->db->limit($limit);
        }

        $this->db->where('sid',$sid);
        $this->db->order_by('uid');
        $this->db->where('uid>=',$start_id);
        $result=$this->db->get('variables')->result_array();

        foreach($result as $index=>$row)
        {
            $metadata=$this->decode_metadata($row['metadata']);

            if(!$include_categories){
                unset($metadata['catgry']);
            }

            $row['metadata']=$metadata;
            yield $row;
        }
    }


    
    private function create_update_variables($dataset_id,$variables, $remove_existing=false)
    {
        if ($remove_existing==true){
            $this->Variable_model->remove_all_variables($dataset_id);
        }
        
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
                $variable['qstn']=$this->variable_question_to_str($variable);
                $variable['catgry']=$this->variable_categories_to_str($variable);
                //$variable['keywords']=$this->variable_keywords_to_str($variable);

				//all fields are stored as metadata
				$variable['metadata']=$variable;
				$variable_id=$this->Variable_model->insert($dataset_id,$variable);
			}

			//update survey varcount
			$this->update_varcount($dataset_id);
		}
    }

    /*private function variable_keywords_to_str($variable)
    {
        $fields=array(
            'var_notes',
            'var_txt'
        );

        $output=[];
        foreach($fields as $field){
            if(isset($variable[$field])){
                $output[]=$variable[$field];
            }            
        }
        
        return implode("\r\n",$output);
    }*/


    /**
     * 
     * Get variable literal + pre/post + interviewer instructions as text
     * 
     */
    private function variable_question_to_str($variable)
    {
        $qstn_fields=array(
            'var_qstn_preqtxt',
            'var_qstn_qstnlit',
            'var_qstn_postqtxt',
            'var_qstn_ivulnstr'
        );

        $output=[];
        foreach($qstn_fields as $field){
            if(isset($variable[$field])){
                $output[]=$variable[$field];
            }            
        }
        
        return implode("\r\n",$output);
    }

    /**
     * 
     *  Get variable categories as string 
     * 
     * 
     */
    private function variable_categories_to_str($categories)
    {
        if(!is_array($categories)){
            return null;
        }

        $categories=array_column($categories,"labl");
        return implode(" ",$categories);
    }


    private function create_update_variable_groups($dataset_id,$variable_groups, $remove_existing=false)
    {
        if($remove_existing){
            $this->Variable_group_model->delete($dataset_id);
        }
        
        if(is_array($variable_groups)){
			foreach($variable_groups as $vgroup){
					$this->Variable_group_model->insert($dataset_id,$vgroup);
			}
		}
    }


    /**
     * 
     * Create data files for Surveys
     * 
     */
    private function create_update_data_files($dataset_id,$data_files, $remove_existing=false)
    {
        if ($remove_existing==true){
            $this->Data_file_model->remove_all_files($dataset_id);
        }

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
	function get_core_fields($type,$options)
	{
		$output=array();

        $title=array();
        $title[]=$this->get_array_nested_value($options,'study_desc/title_statement/title');
        $title[]=$this->get_array_nested_value($options,'study_desc/title_statement/sub_title');
        $title=array_filter($title);
        $output['title']=implode(", ",$title);
        $output['idno']=$this->get_array_nested_value($options,'study_desc/title_statement/idno');

        $nations=(array)$this->get_array_nested_value($options,'study_desc/study_info/nation');
        $nations=$this->get_country_names($nations);//get names only

        $output['nations']=$nations;
        $nation_str=$this->get_country_names_string($nations);        
        $nation_system_name=$this->Country_model->get_country_system_name($nation_str);

        $output['nation']=($nation_system_name!==false) ? $nation_system_name : $nation_str;
        $output['abbreviation']=$this->get_array_nested_value($options,'study_desc/title_statement/alternate_title');
        
        $auth_entity=$this->get_array_nested_value($options,'study_desc/authoring_entity');
        $output['authoring_entity']=$this->array_column_to_string($auth_entity,$column_name='name', $max_length=300);

        $years=$this->get_data_collection_years($options);
        $output['year_start']=$years['start'];
        $output['year_end']=$years['end'];
        
        //set access policy from DDI if not set in $options
        if($this->config->item("enable_access_policy_import"))
        {
            $access_conditions=$this->get_array_nested_value($options,'study_desc/data_access/dataset_use/conditions');
            if(!isset($options['access_policy'])){

                $access_policy=$this->get_access_policy_code($access_conditions);

                if($access_policy){
                    $options['access_policy']=$access_policy;
                }
            }
        }

        if(isset($options['access_policy'])){
            $formid=$this->Form_model->get_formid_by_name($options['access_policy']);

            if($formid){
                $output['formid']=$formid;
            }
        }

		return $output;
    }



    /**
     * 
     * Get access policy code from access conditions text
     * 
     *  e.g. Licensed data files [licensed]
     * 
     * Note: return the first match found in brackets
     * 
     */
    function get_access_policy_code($access_conditions)
    {
		preg_match("/\[([^\]]*)\]/", $access_conditions, $matches);
		if(!isset($matches[1])){
            return false;
        }
        return $matches[1];
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
     * get data collection years from a ddi data collection element
     * 
     **/
	function get_data_collection_years($options)
	{
		$years=array();
        $data_coll=$this->get_array_nested_value($options,'study_desc/study_info/coll_dates');

        if (is_array($data_coll)){
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


    /**
     * 
     * Update all related tables used for facets/filters
     * 
     * 
     */
    function update_filters($sid, $metadata)
    {
        $core_fields=$this->get_core_fields($type='survey',$metadata);

        //update years
		$this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);

		//set topics

        //update related countries
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);
    }
}