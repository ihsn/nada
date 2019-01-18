<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Datasets{

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
    }

    function create_dataset($type,$options)
    {
        $this->validate_type($type);
        //return $this->ci->Dataset_microdata_model->create_dataset($type,$options);
        return $this->ci->{'Dataset_'.$this->types[$type].'_model'}->create_dataset($type,$options);
    }

    function get_idno($sid)
    {
        return $this->ci->Dataset_model->get_idno($sid);    
    }


    private function validate_type($type)
    {
        if(!$this->is_valid_type($type)){
            throw new Exception("INVALID-TYPE: ". $type);
        }
    }
    
    private function is_valid_type($type)
    {
        if (!array_key_exists($type,$this->types)){
            return false;
        }

        return true;
    }

}

/* End of file Datasets.php */
/* Location: ./application/libraries/Datasets.php */
