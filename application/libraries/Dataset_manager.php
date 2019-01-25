<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dataset_manager{

    private $ci;
    private $types=array(
        'survey'=>'microdata',
        'geospatial'=>'geospatial',
        'timeseries'=>'timeseries',
        'document'=>'document',
        'image'=>'image',
        'table'=>'table',
        'script'=>'script'
    );

    function __construct($params=array())
    {
        $this->ci =& get_instance();
        $this->ci->load->model("Dataset_model");
        $this->ci->load->model("Dataset_microdata_model");
        $this->ci->load->model("Dataset_timeseries_model");
        $this->ci->load->model("Dataset_geospatial_model");
        $this->ci->load->model("Dataset_document_model");
        $this->ci->load->model("Dataset_image_model");
        $this->ci->load->model("Dataset_script_model");
        $this->ci->load->model("Dataset_table_model");
    }

    function create_dataset($type,$options)
    {
        $this->validate_type($type);
        //return $this->ci->Dataset_microdata_model->create_dataset($type,$options);
        return $this->ci->{'Dataset_'.$this->types[$type].'_model'}->create_dataset($type,$options);
    }

    private function validate_type($type)
    {
        if(!$this->is_valid_type($type)){
            throw new Exception("INVALID-TYPE::supported types are: ". implode(", ", array_keys($this->types)));
        }
    }
    
    function is_valid_type($type)
    {
        if (!array_key_exists($type,$this->types)){
            return false;
        }

        return true;
    }

    function get_idno($sid)
    {
        return $this->ci->Dataset_model->get_idno($sid);    
    }

    /**
     * 
     * Return a single row with minimum fields
     * 
     */
    function get_row($sid)
    {
        return $this->ci->Dataset_model->get_row($sid);
    }

    /**
     * 
     * Setup project folder
     * 
     */
    function setup_folder($repositoryid, $folder_name)
    {
        return $this->ci->Dataset_model->setup_folder($repositoryid, $folder_name);
    }


    /**
     * 
     * Update dataset options
     * 
     */
    function update_options($dataset_id,$update_options)
    {
        return $this->ci->Dataset_model->update_options($dataset_id,$update_options);
    }


    

}

/* End of file Datasets.php */
/* Location: ./application/libraries/Datasets.php */
