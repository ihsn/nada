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
	
	private function _reload_notes($notes) {
		$x=1; foreach($notes as $note) {
			$user = $this->ion_auth->get_user($note['uid']);
			$note['date'] = current(explode(' ', $note['date']));
			$note['note'] = str_split($note['note'], 70);
			$note['note'] = implode(PHP_EOL, $note['note']);
			echo "		<li id='{$note['id']}'>".nl2br($note['note']), "<br /><small style='margin-left:7px'>{$note['date']}&nbsp;by: {$user->username}</small>&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
			$x++;
		}	 
    }
	
	public function add($id) {
		$note = array(
			'id'   => NULL,
			'sid'  => $id,
			'note' => $this->input->post('note'),
			'type' => $this->input->post('type'),
			'uid'  => $this->session->userdata('user_id'),
			'date' => date("Y:m:d H:i:s")
		);			
		$this->Catalog_Notes_model->insert($note);
			
		$notes['notes'] = $this->Catalog_Notes_model->notes_from_catelog_id($id, $note['type']);
		
		// a separate ajax call could probably be done instead of doing this, but whatever.
		$this->_reload_notes($notes['notes']);	
	}
}
