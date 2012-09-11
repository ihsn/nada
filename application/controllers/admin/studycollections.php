<?php
/**
 * Study Collections
 *
 * handles all study collection related 
 *
 * @package		NADA 4
 * @author		Mehmood Asghar
 * @link		http://ihsn.org/nada/
 */
class StudyCollections extends MY_Controller {

    public function __construct()
    {
        parent::__construct();		
       	$this->load->model('Catalog_model');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		$this->load->helper("catalog");
		$this->template->set_template('admin');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');
		$this->lang->load('resource_manager');
	}
 
	/**
	 * return list of attached terms by vocabularyid
	 *
	 * 	@vid	vocabulary id
	 *	@sid	survey id (optional)	if provided, terms attached to survey are pre-selected
	 */
	function terms_list($vid,$sid=NULL){
		$this->load->model('term_model');
		
		//get a list of all survey collections
		$data['terms']=$this->term_model->get_terms_by_vocabulary($vid);
		$data['selected']=array();
		
		if (is_numeric($sid))
		{
			//get collections attached to a study
			$data['selected']=$this->term_model->get_survey_collections($sid);
		}	

		$output=$this->load->view("catalog_admin/studycollections",$data,TRUE);
		echo $output;
	}
		
	
	function detach($sid,$tid)
	{
		if (!is_numeric($sid) && !is_numeric($tid))
		{
			show_404();
		}
		
		$this->load->model('survey_coll_model');		
		$this->survey_coll_model->detach($sid,$tid);		
	}
	
	function attach($sid,$tid)
	{
		if (!is_numeric($sid) && !is_numeric($tid))
		{
			show_404();
		}
		
		$this->load->model('survey_coll_model');		
		$this->survey_coll_model->attach($sid,$tid);
	}


}
/* End of file studycollections.php */
/* Location: ./controllers/admin/studycollections.php */