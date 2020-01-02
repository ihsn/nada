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
        'script'=>'script',
        'visualization'=>'visualization'
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
        $this->ci->load->model("Dataset_visualization_model");
    }

    function create_dataset($type,$options)
    {
        $this->validate_type($type);
        //return $this->ci->Dataset_microdata_model->create_dataset($type,$options);
        return $this->ci->{'Dataset_'.$this->types[$type].'_model'}->create_dataset($type,$options);
    }


    function update_dataset($sid,$type,$options)
    {
        $this->validate_type($type);
        return $this->ci->{'Dataset_'.$this->types[$type].'_model'}->update_dataset($sid,$type,$options);
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


    function find_by_idno($idno)
    {
        return $this->ci->Dataset_model->find_by_idno($idno);    
    }


    /**
     * 
     * Return all rows
     * 
     */
    function get_all()
    {
        return $this->ci->Dataset_model->get_all();
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
     * Get metadata
     * 
     */
    function get_metadata($sid)
    {
        return $this->ci->Dataset_model->get_metadata($sid);
    }

    function validate_options($options)
    {
        return $this->ci->validate_options($options);
    }

    public function get_data_access_type_id($name)
    {
        return $this->ci->Dataset_model->get_data_access_type_id($name);
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


    function update_varcount($sid)
    {
        return $this->ci->Dataset_model->update_varcount($sid);
    }

    function delete($sid)
    {
        return $this->ci->Dataset_model->delete($sid);
    }

    function set_data_access_type($sid,$da_type,$da_link)
    {
        return $this->ci->Dataset_model->set_data_access_type($sid,$da_type,$da_link);
    }

    function update_sid($old_sid,$new_id)
    {
        return $this->ci->Dataset_model->update_sid($old_sid,$new_id);
    }

    function set_publish_status($sid,$publish_status)
    {
        return $this->ci->Dataset_model->set_publish_status($sid,$publish_status);
    }

    function remove_datafile_variables($sid,$file_id)
    {
        return $this->ci->Dataset_microdata_model->remove_datafile_variables($sid,$file_id);
    }

    function get_dataset_with_tags($idno=null)
    {
        return $this->ci->Dataset_model->get_dataset_with_tags($idno);
    }


    function get_dataset_aliases($idno=null)
    {
        return $this->ci->Dataset_model->get_dataset_aliases($idno);
    }

}

/* End of file Dataset_manager.php */
/* Location: ./application/libraries/Dataset_manager.php */
