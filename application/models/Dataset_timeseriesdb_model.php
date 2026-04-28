<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;

/**
 *
 * Timeseries database as first-class study type.
 *
 */
class Dataset_timeseriesdb_model extends Dataset_model {

    public function __construct()
    {
        parent::__construct();
    }

    function update_dataset($sid,$type,$options, $merge_metadata=false)
    {
        $options['sid']=$sid;

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
        // schema file is timeseries-db-schema.json
        $this->validate_schema('timeseries-db',$options);

        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);

        if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
            throw new exception("IDNO-NOT-SET");
        }

        $dataset_id=$this->find_by_idno($core_fields['idno']);

        if(!empty($sid)){
            if(is_numeric($dataset_id) && $sid!=$dataset_id ){
                throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$dataset_id.':'.$core_fields['idno']);
            }
            $dataset_id=$sid;
        }
        else{
            if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
                throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
            }
        }

        $options['changed']=date("U");

        $study_metadata_sections=array('metadata_information','database_description','provenance','embeddings','lda_topics','tags','additional');

        foreach($study_metadata_sections as $section){
            if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }

        $this->db->trans_start();

        if($dataset_id>0){
            $this->update($dataset_id,$type,$options);
        }
        else{
            $dataset_id=$this->insert($type,$options);
        }

        $this->update_filters($dataset_id,$options['metadata']);
        $this->db->trans_complete();

        return $dataset_id;
    }

    function get_core_fields($options)
    {
        $output=array();
        $output['title']=$this->get_array_nested_value($options,'database_description/title_statement/title');
        $output['idno']=$this->get_array_nested_value($options,'database_description/title_statement/idno');
        $output['subtitle']=$this->get_array_nested_value($options,'database_description/title_statement/sub_title');
        $output['abbreviation']=$this->get_array_nested_value($options,'database_description/title_statement/alternate_title');
        $output['authoring_entity']='';
        $output['nation']='';
        $output['nations']=array();
        $output['year_start']=0;
        $output['year_end']=0;

        $authors=(array)$this->get_array_nested_value($options,'database_description/authoring_entity');
        if (count($authors)>0){
            $author_names=array();
            foreach($authors as $author){
                if(isset($author['name']) && trim((string)$author['name'])!==''){
                    $author_names[]=trim((string)$author['name']);
                }
            }
            $output['authoring_entity']=implode(", ",$author_names);
        }

        return $output;
    }

    function update_filters($sid, $metadata=null)
    {
        if (!is_array($metadata)){
            return false;
        }

        $this->update_years($sid,0,0);
        $this->Survey_country_model->update_countries($sid,array());
        $this->add_tags($sid,$this->get_array_nested_value($metadata,'tags'));
        return true;
    }
}
