<?php

class Survey_alias extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Survey_alias_model');
		$this->lang->load('general');
	}
	
	public function delete($id) 
	{
		if (!is_numeric($id))
		{
			return false;
		}		
		
		$this->Survey_alias_model->delete($id);
	}
	
	
	public function add($id) 
	{
		if (!is_numeric($id))
		{
			return false;
		}
		
		$alternate_id=$this->security->xss_clean($this->input->post('alternate_id'));
		
		$options = array(
			'sid'  => $id,
			'alternate_id' => $alternate_id,
		);
		
		if (!$this->Survey_alias_model->id_exists($alternate_id)) 
		{
			$this->Survey_alias_model->insert($options);
		}
		
		//get all survey aliases
		$survey_aliases= $this->Survey_alias_model->get_aliases($id);
		
		//return formatted list from db
		$this->load->view('catalog/survey_aliases_list',array('rows'=>$survey_aliases));
	}
}
