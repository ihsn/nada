<?php
class Variable_group_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model("Dataset_model");
    }

    public function remove_all_variable_groups($sid)
    {
        $this->db->where("sid",$sid);
        $this->db->delete("variable_groups");
    }

    /**
     * 
     * Get all variable groups by dataset
     * 
     */
    function select_all($sid)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        return $this->db->get("variable_groups")->result_array();
    }


    public function delete($sid)
    {
        $this->db->where("sid",$sid);
        $this->db->delete("variable_groups");
    }



    public function insert($sid,$options)
    {
        $valid_fields=array(
            'sid',
            'vgid',
            'variables',
            'variable_groups',
            'group_type',
            'label',
            'universe',
            'notes',
            'txt',
            'definition'
        );
        
        foreach($options as $key=>$value){
            if(!in_array($key,$valid_fields)){
                unset($options[$key]);
            }
        }

        $options['sid']=$sid;

        $this->db->insert("variable_groups",$options);
        return $this->db->insert_id();
    }

}    