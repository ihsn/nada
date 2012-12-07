<?php

class Catalog_Notes extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_Notes_model');
		$this->lang->load('general');
	}
	
	public function delete($id) {
		$t=$this->Catalog_Notes_model->delete($id);
	}
	
	public function add($id) 
	{
		if (is_numeric($id))
		{
			$note = array(
				'id'   => NULL,
				'sid'  => $id,
				'note' => $this->security->xss_clean($this->input->post('note')),
				'type' => $this->security->xss_clean($this->input->post('type')),
				'userid'  => $this->session->userdata('user_id'),
				'created' => date("U")
			);
		
			//add note
			$this->Catalog_Notes_model->insert($note);
		}
				
		//get a list of notes from db
		$notes= $this->Catalog_Notes_model->notes_from_catelog_id($id, $note['type']);
		
		//return formatted list of notes
		echo $this->load->view('catalog/notes_by_type',array('notes'=>$notes));
	}
	
}
