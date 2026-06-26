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

    function update_dataset($sid,$type,$options, $merge_metadata=false, $validate_schema=true)
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

        return $this->create_dataset($type,$options,$sid, $validate_schema);
    }

    function create_dataset($type,$options, $sid=null, $validate_schema=true)
    {
        // schema file is timeseries-db-schema.json
        if ($validate_schema){
            $this->validate_schema('timeseries-db',$options);
        }

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

        $this->load->model('Timeseries_db_model');
        $this->Timeseries_db_model->sync_series_links($core_fields['idno'], $options['metadata']);

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

        // nation/nations from database_description.ref_country[].name
        $ref_countries=(array)$this->get_array_nested_value($options,'database_description/ref_country');
        if (count($ref_countries)>0){
            $country_names=array();
            foreach($ref_countries as $country){
                if(isset($country['name']) && trim((string)$country['name'])!==''){
                    $country_names[]=trim((string)$country['name']);
                }
            }

            if (!empty($country_names)){
                $country_names=array_values(array_unique($country_names));
                $output['nations']=$country_names;
                $output['nation']=implode(", ",$country_names);
            }
        }

        // year_start/year_end from database_description.time_coverage[].start/end
        $time_coverage=(array)$this->get_array_nested_value($options,'database_description/time_coverage');
        if (count($time_coverage)>0){
            $start_years=array();
            $end_years=array();

            foreach($time_coverage as $period){
                if (isset($period['start'])){
                    $year=$this->extract_year($period['start']);
                    if ($year>0){
                        $start_years[]=$year;
                    }
                }

                if (isset($period['end'])){
                    $year=$this->extract_year($period['end']);
                    if ($year>0){
                        $end_years[]=$year;
                    }
                }
            }

            if (!empty($start_years)){
                $output['year_start']=min($start_years);
            }

            if (!empty($end_years)){
                $output['year_end']=max($end_years);
            }
        }

        return $output;
    }

    private function extract_year($value)
    {
        if (is_numeric($value) && (int)$value>999 && (int)$value<3001){
            return (int)$value;
        }

        $value=(string)$value;
        if (preg_match('/\b(1[6-9][0-9]{2}|20[0-9]{2}|2[1-9][0-9]{2}|3000)\b/', $value, $matches)){
            return (int)$matches[1];
        }

        return 0;
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
