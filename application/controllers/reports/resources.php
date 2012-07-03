<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Resources extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model("Catalog_model");
	}

	public function index()
	{
		$this->load->helper("url");
		$this->load->view('welcome_message');
		
	}
	
	public function run()
	{
		//get list of all surveys
		$surveys=$this->_get_all_surveys();
		
		$output['no_distribute']=array();
		
		foreach($surveys as $survey)
		{
			$result=$this->has_distribute_folder($survey['id']);
			
			if ($result==FALSE)
			{
				$output['no_distribute'][]=$survey['id'];
			}
			echo "\n processed ".$survey['id']."\n";
		}
		
		file_put_contents("logs/studies-no-distribute.txt",implode(",",$output['no_distribute']));
		echo "done";
	}
	
	//check if a study has a distribute folder
	function has_distribute_folder($sid)
	{
		$survey_folder=$this->Catalog_model->get_survey_path_full($sid);

		if ($survey_folder!==FALSE && file_exists($survey_folder) )
		{
			$distribute_folder=unix_path($survey_folder.'/distribute');

			//check DISTRIBUTE folder exists
			if (file_exists($distribute_folder))
			{
				return TRUE;
			}
		}
		
		return FALSE;
		
	}
	
	/**
	*
	* Return IDs for all surveys
	**/
	function _get_all_surveys()
	{
		$this->db->select("id");
		return $this->db->get("surveys")->result_array();
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */