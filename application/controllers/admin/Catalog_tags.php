<?php
/**
 * Catalog Tags
 *
 * manage survey tags
 *
 * @package		NADA 4
 * @author		IHSN
 * @link		http://ihsn.org/nada/
 */
class Catalog_Tags extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_tags_model');
		$this->lang->load('general');
		$this->load->helper('security');
		//$this->output->enable_profiler(TRUE);			
	}

	
	public function add($id) 
	{		
		if (!is_numeric($id))
		{
			return FALSE;
		}
	
		$this->load->helper("string");
		
		$tag=xss_clean($this->input->post('tag'));
		
		//remove single/double quotes
		$tag=strip_quotes($tag);
		
		//remove any tags
		$tag=strip_tags($tag);
		
		//convert spaces and accents
		$tag=url_title($tag);
		
		if (!$this->Catalog_tags_model->tag_exists($id, $tag)) 
		{
			$options=array(
					'sid'=>$id,
					'tag'=>$tag
				);
			$this->Catalog_tags_model->insert($options);
		}
		
		//get all tags associated with the survey	
		$survey_tags = $this->Catalog_tags_model->survey_tags($id);		
		
		//return new tag list for the survey
		$this->load->view("catalog/survey_tags_list",array('tags'=>$survey_tags));
	}


	public function delete($id) 
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		$this->Catalog_tags_model->delete($id);
	}
}
