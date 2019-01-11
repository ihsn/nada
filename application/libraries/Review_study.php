<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Review study
 * 
 *
 *
 *
 */ 
class Review_study
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Review study Class Initialized.");
		$this->ci =& get_instance();
		$this->ci->load->model('catalog_notes_model');
	}
/*
[sid]/review					review notes
[sid]/review/microdata			list microdata files
[sid]/review/download/[fileid]	download external resources inc. microdata
[sid]/review/get_notes			get a list of all reviewer notes
[sid]/review/add_note			add new reviewer note
[sid]/review/edit_note/[id] 	edit reviewer note
[sid]/review/delete_note/[id]	delete a note
*/

	/**
	*
	* Return all study notes
	**/
	public function get_study_notes($sid,$note_type='reviewer')
	{
		return $this->ci->catalog_notes_model->get_notes_by_study($sid,$note_type);
	}

	public function get_formatted_study_notes($sid,$note_type='reviewer')
	{
		$data['study_notes']=$this->ci->catalog_notes_model->get_notes_by_study($sid,$note_type);
		$data['study_id']=$sid;
		return $this->ci->load->view('ddibrowser/study_notes_list',$data,TRUE);
	}


	public function get_study_edit_form($note_id,$action_url,$show_note_types=FALSE)
	{
		$note=$this->ci->catalog_notes_model->single($note_id);
		$note['action_url']=$action_url;
		$note['show_note_tpes']=$show_note_types;
		return $this->ci->load->view('catalog/study_notes_edit',$note,TRUE);
	}
	
	public function get_study_add_form($action_url,$show_note_types=FALSE)
	{
		$note['action_url']=$action_url;
		$note['show_note_tpes']=$show_note_types;
		return $this->ci->load->view('catalog/study_notes_edit',$note,TRUE);
	}

	/**
	*
	* Add study note
	**/
	public function add_study_note($note_options)
	{
		return $this->ci->catalog_notes_model->insert($note_options);
	}
	
	/**
	*
	*
	**/
	public function edit_study_note($note_id,$note_options)
	{
		return $this->ci->catalog_notes_model->update($note_id,$note_options);
	}
	
	/**
	*
	*
	**/
	public function delete_study_note($note_id)
	{
		//delete note		
		return $this->ci->catalog_notes_model->delete($note_id);
	}

	/**
	*
	*
	**/
	public function get_study_resources_all($sid){}

	/**
	*
	*
	**/
	public function get_study_resources_microdata($sid){}

	/**
	*
	*
	**/
	public function download($sid,$user_id,$file_id){}

	
}//end class

