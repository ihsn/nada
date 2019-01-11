<?php
class Variable_model extends CI_Model {

    public function remove_all_variables($sid)
    {
        $this->db->where("sid",$sid);
        $this->db->delete("variables");
    }

    //get a single variable
    function select_single($vid)
    {
        $this->db->select("*");
        $this->db->where("uid",$vid);
        $variable=$this->db->get("variables")->row_array();

        if(isset($variable['metadata']))
        {
            $this->load->model("Survey_model");
            $variable['metadata']=$this->Survey_model->decode_metadata($variable['metadata']);
        }

        return $variable;
    }

    /**
     * 
     * Select all variables
     * 
     */
    function select_all($sid)
    {
        $this->load->model("Dataset_model");
        $this->db->select("sid,metadata");
        $this->db->where("sid",$sid);
        $variables=$this->db->get("variables")->result_array();

        foreach($variables as $key=>$variable){
            $variables[$key]['metadata']=$this->Dataset_model->decode_metadata($variable['metadata']);
        }

        return $variables;
    }


    function get_var_by_vid($sid,$vid)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        $this->db->where("vid",$vid);

        $variable=$this->db->get("variables")->row_array();

        if(isset($variable['metadata'])){
            $this->load->model("Survey_model");
            $variable['metadata']=$this->Survey_model->decode_metadata($variable['metadata']);
        }

        return $variable;
    }

    function get_var_by_uid($sid,$uid)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        $this->db->where("uid",$uid);

        $variable=$this->db->get("variables")->row_array();

        if(isset($variable['metadata'])){
            $this->load->model("Survey_model");
            $variable['metadata']=$this->Survey_model->decode_metadata($variable['metadata']);
        }

        return $variable;
    }

    //get a single variable
    function get_by_var_id($sid, $file_id=null, $var_id)
    {
        $this->db->select("*");
        $this->db->where("sid",$sid);
        $this->db->where("vid",$var_id);

        if($file_id){
            $this->db->where("fid",$file_id);
        }

        $variable=$this->db->get("variables")->row_array();

        if(isset($variable['metadata'])){
            $this->load->model("Survey_model");
            $variable['metadata']=$this->Survey_model->decode_metadata($variable['metadata']);
        }

        return $variable;
    }


    function get_uid_by_vid($sid,$vid)
    {
        $this->db->select("uid");
        $this->db->where("sid",$sid);
        $this->db->where("vid",$vid);

        $variable=$this->db->get("variables")->row_array();

        if ($variable){
            return $variable['uid'];
        }

        return false;
    }


    //get all variables attached to a study
    function list_by_dataset($sid,$file_id=null)
    {
        $this->db->select("uid,sid,fid,vid,name,labl");
        $this->db->where("sid",$sid);
        if($file_id){
            $this->db->where("fid",$file_id);
        }
        return $this->db->get("variables")->result_array();
    }


    /**
     * 
     * Get all files by dataset
     * 
     * @sid - int - dataset ID
     * @file_id - string - File ID using the format - F1, F2
     * 
     */
    function get_all_by_file($sid, $file_id)
    {
        //get fid for file
        //$fid=$this->Data_file_model->get_fid_by_fileid($sid,$file_id);

        /*if(!$fid){
            return false;
        }*/

        $this->db->select("uid,vid,name,labl,qstn");
        $this->db->where("sid",$sid);
        $this->db->where("fid",$file_id);
        return $this->db->get("variables")->result_array();
    }


    /**
     * 
     * get variables count by sid
     */
    function get_variables_count($sid){
        $this->db->where('sid',$sid);
        $this->db->from("variables");
        return $this->db->count_all_results();        
    }

    public function insert($sid,$options)
    {
        $valid_fields=array(
            'name',
            'labl',
            'qstn',
            'catgry',
            'sid',
            'fid',
            'vid',
            'metadata'
        );

        //check if variable already exists
        $uid=$this->get_uid_by_vid($sid,$options['vid']);

        if($uid){
            return $this->update($sid,$uid,$options);
        }

        foreach($options as $key=>$value)
        {
            if(!in_array($key,$valid_fields))
            {
                unset($options[$key]);
            }
        }

        $options['sid']=$sid;

        //metadata
        if(isset($options['metadata'])){
            $options['metadata']=$this->Dataset_model->encode_metadata($options['metadata']);
        }

        $this->db->insert("variables",$options);
        return $this->db->insert_id();
    }

    public function update($sid,$uid,$options)
    {
        $valid_fields=array(
            'name',
            'labl',
            'qstn',
            'catgry',
            'sid',
            'fid',
            'vid',
            'metadata'
        );

        foreach($options as $key=>$value){
            if(!in_array($key,$valid_fields)){
                unset($options[$key]);
            }
        }

        $options['sid']=$sid;        

        //metadata
        if(isset($options['metadata'])){
            $options['metadata']=$this->Dataset_model->encode_metadata($options['metadata']);
        }

        $this->db->where('sid',$sid);
        $this->db->where('uid',$uid);
        $this->db->update("variables",$options);
        return $uid;
    }


    public function batch_insert($sid,$variables)
    {
        $valid_fields=array(
            'name',
            'labl',
            'qstn',
            'catgry',
            'sid',
            'fid',
            'metadata',
            'vid'
        );

        //remove fields that are not in the valid_fields list
        foreach($variables as $key=>$variable)
        {
            $variables[$key]=array_intersect_key($variable,array_flip($valid_fields));
            $variables[$key]['sid']=$sid;
            if(isset($variable['metadata'])){
                $variables[$key]['metadata']=$this->Dataset_model->encode_metadata($variable['metadata']);
            }
        }

        $this->db->insert_batch('variables', $variables);
    }




    /**
	 * 
	 * 
	 * Validate data file
	 * @options - array of fields
	 * @is_new - boolean - for new records
	 * 
	 **/
	function validate_variable($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for a new record
		if($is_new){				
			$this->form_validation->set_rules('fid', 'File ID', 'xss_clean|trim|max_length[50]|required|alpha_dash');
			$this->form_validation->set_rules('vid', 'Variable ID', 'required|xss_clean|trim|max_length[100]|alpha_dash');	
			$this->form_validation->set_rules('name', 'Variable name', 'required|xss_clean|trim|max_length[255]');	
			//$this->form_validation->set_rules('labl', 'Label', 'required|xss_clean|trim|max_length[255]');	
		}
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}
	
}
	
