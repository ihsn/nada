<?php
/**
 * Catalog Tags
 *
 * handles all Catalog Maintenance pages
 *
 * @package		NADA 4
 * @author		IHSN
 * @link		http://ihsn.org/nada/
 */
class Catalog_Tags extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_Tags_model');
		$this->lang->load('general');
		$this->load->helper('security');
		//$this->output->enable_profiler(TRUE);			
	}

	
	public function add($id) 
	{		
		$tag=xss_clean($this->input->post('tag'));

		if (!is_numeric($id))
		{
			return FALSE;
		}
	
		if (!$this->Catalog_Tags_model->tag_exists($id, $tag)) 
		{
			$this->Catalog_Tags_model->insert($id,$tag);
		}
		
		//get all tags associated with the survey	
		$survey_tags = $this->Catalog_Tags_model->survey_tags($id);		
		
		//return new tag list for the survey
		$this->_format_tags($survey_tags);	
	}

	//format tag unordered list from tags array
	private function _format_tags($tags) 
	{
		foreach($tags as $tag) 
		{
			echo "<li id='{$tag['id']}'>{$tag['tag']} <span class=\"action\">-</span></li>", PHP_EOL;
		}	 
    }



	public function delete($id) 
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		$this->Catalog_Tags_model->delete($id);
	}
}
