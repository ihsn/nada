<?php

class Catalog_Notes extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_notes_model');
		$this->lang->load('general');
		$this->lang->load('catalog_admin');
	}
	
	public function delete($id) 
	{
		return $this->Catalog_notes_model->delete($id);
	}
	
	public function add($id) 
	{
		if (!is_numeric($id))
		{
			show_error('INVALID');
		}
		
		//show the add/edit form
		if (!$this->input->post('note'))
		{
			$data=array(
				'note'			=>'',
				'type'			=>'',
				'action_url'	=>site_url('admin/catalog_notes/add/'.$id),
				'show_note_types'=>true
			);
			$this->load->view('catalog/study_notes_edit',$data);
			return;
		}
		
		$note = array(
			'id'   => NULL,
			'sid'  => $id,
			'note' => $this->security->xss_clean($this->input->post('note')),
			'type' => $this->security->xss_clean($this->input->post('type')),
			'userid'  => $this->session->userdata('user_id'),
			'created' => date("U")
		);
	
		if (!$note['note'] || !$note['type'])
		{
			return FALSE;
		}
	
		//add note
		$this->Catalog_notes_model->insert($note);
	}
	
	public function get_notes($sid)
	{
		//get a list of notes from db
		$notes= $this->Catalog_notes_model->get_notes_by_study($sid);

		//return formatted list of notes
		echo $this->load->view('catalog/study_notes_list',array('study_notes'=>$notes),true);

	}
	
	public function edit($id)
	{
		if (!is_numeric($id))
		{
			show_error('INVALID');
		}
		
		//show the edit form
		if (!$this->input->post('note'))
		{
			$note=$this->Catalog_notes_model->single($id);
			$note['action_url']=site_url('admin/catalog_notes/edit/'.$id);
			$note['show_note_types']=TRUE;
			$this->load->view('catalog/study_notes_edit',$note);
			return;
		}
		
		//process
		$note = array(
			'note' => $this->security->xss_clean($this->input->post('note')),
			'type' => $this->security->xss_clean($this->input->post('type')),
			'userid'  => $this->session->userdata('user_id'),
			'changed' => date("U")
		);
		
		if (!$note['note'] || !$note['type'])
		{
			return FALSE;
		}
	
		//add note
		$this->Catalog_notes_model->update($id,$note);
	
	}
	
}
