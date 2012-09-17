<?php

class Catalog_Ids extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_Ids_model');
		$this->lang->load('general');
	}
	
	public function delete($id) {
		$t=$this->Catalog_Ids_model->delete($id);
	}
	
	private function _reload_ids($ids) {
		 foreach($ids as $survey_id) {
		echo "		<li id='{$survey_id['id']}'>{$survey_id['survey_id']}&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
		}	 
    }
	
	public function add($id) {
		$survey_id = array(
			'sid'  => $id,
			'survey_id' => $this->input->post('survey_id'),
		);
		if (!$this->Catalog_Ids_model->id_exists($id, $this->input->post('survey_id'))) {
			$this->Catalog_Ids_model->insert($survey_id);
		}
		
		$ids['ids'] = $this->Catalog_Ids_model->ids_from_catelog_id($id);
		
		// a separate ajax call could probably be done instead of doing this, but whatever.
		$this->_reload_ids($ids['ids']);	
	}
}
