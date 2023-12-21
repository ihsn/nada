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
        $this->load->model('Data_classification_model');
    }


    /**
     * 
     * 
     * @type - survey | table | document etc
     * @options - metadata
     * @sid - study ID for updating an existing study
     * @is_update - for partially updating a study
     * 
     */
    function create_dataset($type,$options, $sid=null,$is_update=false)
	{
        $data_files=null;
		$variables=null;
        $variable_groups=null;

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

		//validate schema
        $this->validate_schema($type,$options);

        if (!isset($options['overwrite'])){
            $options['overwrite']='no';
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
        else {//create new
        
            //overwrite?
		    if(is_numeric($dataset_id) && isset($options['overwrite']) && $options['overwrite']!=='yes'){
			    throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
            }
        }

        $options['changed']=date("U");
		
        $study_metadata_sections=array('doc_desc','study_desc','provenance','embeddings','lda_topics','tags','additional');

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
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

        //facets/filters
        $this->update_filters($dataset_id,$options['metadata']);

        //for creating new studies, always remove the existing data
        $remove_existing=true;

        //for partial updates
        if ($is_update==true){
            $remove_existing=false;
        }

        //data files
        $this->create_update_data_files($dataset_id,$data_files, $remove_existing);
        
        //variables
        $this->create_update_variables($dataset_id,$variables, $remove_existing);

		//variable groups?
		$this->create_update_variable_groups($dataset_id,$variable_groups, $remove_existing);

		//complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE){
            throw new Exception("DB_TRANSACTION_ERROR: Failed to update database". implode(",",$this->db->error()));
        }
        
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

            //merge variables
            if(isset($options['variables'])){
                foreach($options['variables'] as $idx=>$variable){ 
                    //validate file_id exists
                    $fid=$this->Data_file_model->get_fid_by_fileid($sid,$variable['file_id']);
            
                    if(!$fid){
                        throw new exception("variable update failed. Variable 'file_id' not found: ".$variable['file_id']);
                    }
        
                    //partial update variables
                    $variable_db_value=$this->Variable_model->get_by_var_id($sid, $variable['file_id'], $variable['vid']);

                    if ($variable_db_value){
                        //merge/replace variable metadata
                        $variable=array_replace_recursive($variable_db_value['metadata'],$variable);
                    }

                    $variable['fid']=$variable['file_id'];
                    $this->Variable_model->validate_variable($variable);
                    $options['variables'][$idx]=$variable;
                }
                $options=array_remove_nulls($options);
            }
        }

        return $this->create_dataset($type,$options,$sid,$is_update=true);        
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

        $limit=1000;
        $max_loop=ceil($total_vars/$limit);

        $exclude_columns=array('file_id','vid','fid','var_qstn_qstnlit','var_sumstat','var_format','var_val_range');

        if($include_categories==false){
            $exclude_columns[]="catgry";
        }

        $counter=0;
        $output=[];
        $m=0;
        $start_uid=0;

        for($vars_processed=0;$vars_processed<$total_vars;){
            $counter++;
            if ($counter>$max_loop){break;}

            $variables=$this->variable_chunk_reader($sid, $start_uid, $limit,$include_categories);
            foreach($variables as $variable){
                $m++;
                $start_uid=$variable['uid'];

                $tmp=array();
                foreach($variable['metadata'] as $key=>$value){
                    if(!in_array($key,$exclude_columns)){
                        $tmp[]=$value;
                    }
                }
                $output[]=$this->extract_var_keywords($tmp);
            }
            $vars_processed=$vars_processed+$limit;
        }

        $output=implode(" ",$output);
        $output=array_unique(array_filter(explode(" ",$output)));
        $output=implode(" ",($output));

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
        $this->db->select("uid,metadata");
        
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
        
        if(!is_array($variables)){
            return false;
        }
        

        $valid_data_files=(array)$this->Data_file_model->list_fileid($dataset_id);
        foreach($variables as $idx=>$variable){ 
            if(!in_array($variable['file_id'],$valid_data_files)){
                throw new exception("variable creation failed. Variable 'file_id' not found: ".$variable['file_id']);
            }

            $variable['fid']=$variable['file_id'];
            $this->validate_schema($type='variable',$variable);
            $this->Variable_model->validate_variable($variable);
        }

        $result=array();
        foreach($variables as $variable){
            $variable['fid']=$variable['file_id'];
            $variable['qstn']=$this->variable_question_to_str($variable);

            $variable_categories=isset($variable['var_catgry']) ? $variable['var_catgry'] : null;
            $variable['catgry']=$this->variable_categories_to_str($variable_categories);
            //$variable['keywords']=$this->variable_keywords_to_str($variable);

            //all fields are stored as metadata
            $variable['metadata']=$variable;
            $variable_id=$this->Variable_model->insert($dataset_id,$variable, $upsert=!$remove_existing);
        }

        //update survey varcount
        $this->update_varcount($dataset_id);
        $this->Variable_model->update_survey_timestamp($dataset_id);
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
            'var_qstn_ivuinstr'
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
        if(!is_array($categories) || empty($categories)){
            return null;
        }

        $categories_labl=array_column($categories,"labl");
        $categories=array_merge($categories_labl,array_column($categories,"label"));
        $categories=array_unique(explode(" ",implode(" ",$categories)));
        return implode(" ",$categories);
    }


    private function create_update_variable_groups($dataset_id,$variable_groups, $remove_existing=false)
    {
        if($remove_existing){
            $this->Variable_group_model->delete($dataset_id);
        }
        
        if(is_array($variable_groups)){
            foreach($variable_groups as $vgroup){
                $this->validate_schema($type='variable-group',$vgroup);
            }

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
	function get_core_fields($options)
	{
		$output=array();

        $output['title']=$this->get_array_nested_value($options,'study_desc/title_statement/title');
        $output['subtitle']=$this->get_array_nested_value($options,'study_desc/title_statement/sub_title');
        $output['idno']=$this->get_array_nested_value($options,'study_desc/title_statement/idno');
        $output['doi']=$this->get_core_doi($options);

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

        if(isset($options['data_classification'])){
            $data_class_id=$this->Data_classification_model->get_classification_id($options['data_classification']);
            if($data_class_id){
                $output['data_class_id']=$data_class_id;
            }
        }

		return $output;
    }

    function get_core_doi($options)
    {
        $identifiers=(array)$this->get_array_nested_value($options,'study_desc/title_statement/identifiers');

        foreach($identifiers as $identifier){
            if (isset($identifier['type']) && strtolower($identifier['type'])=='doi'){
                return $identifier['identifier'];
            }
        }
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
                $year_=substr(trim($row['start']),0,4);
                if((int)$year_>0){
                    $years[]=$year_;
                }					
                if(isset($row['end'])){
                    $year_=substr(trim($row['end']),0,4);
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
    function update_filters($sid, $metadata=null)
    {        
        if (!is_array($metadata)){            
            return false;
        }

        $core_fields=$this->get_core_fields($metadata);
		$this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);        
        $this->add_tags($sid,$this->get_array_nested_value($metadata,'tags'));
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);
        return true;
    }

}