<?php
class Variable_group_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model("Dataset_model");
    }

    /**
     * 
     * Remove all variable groups
     * 
     */
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


    /**
     * 
     * Get a single variable group + variables info 
     * 
     */
    function get_single_group($sid, $vgid)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        $this->db->where("vgid",$vgid);
        $variable_group=$this->db->get("variable_groups")->result_array();

        if (!$variable_group){
            return false;
        }

        $variable_group=$variable_group[0];

        $var_list=array_filter(explode(" ", $variable_group['variables']));

        if(!empty($var_list))
        {
            $variable_group['variables']=$this->get_group_variables($sid,$var_list);
        }

        return $variable_group;
    }


    /**
     * 
     * Get variables list
     * 
     * @sid - int
     * @var_list - array - variable ID
     * 
     */
    function get_group_variables($sid, $var_list)
    {
        $this->db->select("uid,vid,name,labl");
        $this->db->where("sid",$sid);
        $this->db->where_in("vid",$var_list);
        return $this->db->get("variables")->result_array();
    }


    /**
     * 
     * Delete a single variable group
     * 
     */
    public function delete($sid)
    {
        $this->db->where("sid",$sid);
        $this->db->delete("variable_groups");
    }


    /**
     * 
     * Insert variable group
     * 
     */
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



    /**
     * 
     * Create html list for variable sub-groups
     * 
     * 
     */
    private function get_html_subgroup($group,&$groups,&$output,$sid)
    {
        if(!isset($group['variable_groups'])){
            return false;
        }

        foreach($group['variable_groups'] as $vgid){                
            
            if(empty($vgid)){
                continue;
            }
            
            if(array_key_exists($vgid,$groups)){                
                
                if ($groups[$vgid]['is_shown']===true){
                    continue;
                }

                $output.='<ul class="nada-list-subgroup">';
                $output.='<li class="nada-list-vgroup-item"><a class="nav-link-x" href="'.site_url("catalog/$sid/variable-groups/$vgid").'">'.$groups[$vgid]['label'].'</a></li>';
                $groups[$vgid]['is_shown']=true;
                
                $this->get_html_subgroup($groups[$vgid],$groups,$output,$sid);
                
                /*foreach($groups[$vgid]['variables'] as $variable){
                    $output.='<ul><li>'.$variable.'</li></ul>';
                }*/
            }                
            $output.='</ul>';
        }
    }
    
    /**
     * 
     * 
     * Generate an HTML list for variable groups
     * 
     * 
     */
    function get_vgroup_tree_html($sid)
    {        
        $variable_groups=$this->select_all($sid);

        if (empty($variable_groups)){
            return false;
        }

        $groups=array();
        //$variables=array();

        foreach($variable_groups as $idx=> $vgroup){
            $groups[$vgroup['vgid']]=$vgroup;
            $groups[$vgroup['vgid']]['variable_groups']=explode(" ",$vgroup['variable_groups']);
            //$variable_list=explode(" ",$vgroup['variables']);
            //$groups[$vgroup['vgid']]['variables']=$variable_list;
            $groups[$vgroup['vgid']]['is_shown']=false;
            //$variables=array_merge($variables, $variable_list);            
        }

        //$variables=(array_filter($variables));

        $output='<ul class="nada-list-vgroup">';

        foreach($groups as $vgid=>$group){

            if ($groups[$vgid]['is_shown']===true){
                continue;
            }

            $output.='<li class="nada-list-vgroup-item ">';
            if(trim($group['variables'])==''){
                $output.='<span class="nav-link-x" xhref="'.site_url("catalog/$sid/variable-groups/$vgid").'"><i class="fa fa-angle-double-down" aria-hidden="true"></i> '.$group['label'].'</span>';
            }
            else{
                $output.='<a class="nav-link-x" href="'.site_url("catalog/$sid/variable-groups/$vgid").'"><i class="fa fa-angle-double-down" aria-hidden="true"></i> '.$group['label'].'</a>';
            }
            $groups[$vgid]['is_shown']=true;
            $this->get_html_subgroup($group,$groups,$output,$sid);                
            $output.='</li>';
        }
        $output.='</ul>';
        return $output;
    }

}    