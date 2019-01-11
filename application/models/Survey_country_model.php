<?php
class Survey_country_model extends CI_Model {
 
    public function __construct()
    {
		parent::__construct();
		$this->load->model("Country_model");
		//$this->output->enable_profiler(TRUE);
    }
	

	/**
	*
	* Remove all countries from survey
	**/
	function remove_survey_countries($sid)
	{
		//remove existing survey countries
		$this->db->where('sid',$sid);
		return $this->db->delete('survey_countries');
	}


	/**
	*
	* Add/edit survey countries
	**/
	function update_countries($sid, $countries)
	{
		$this->remove_survey_countries($sid);

		if(!is_array($countries) ){ 
			return false;
		}

		$countries=array_unique($countries);

		$data=array();
		foreach ($countries as $country)
		{
			//country id if exists			
			$countryid=$this->Country_model->find_country_by_name($country);

			//add to survey_countries
			$this->add_single_country($sid, $country, $countryid);
		}	
	}

	/**
	*
	* Add a single country to survey
	**/
	function add_single_country($sid,$country_name,$countryid)
	{
		$options=array(
					'sid'			=>$sid,
					'country_name'	=>$country_name,
					'cid'			=>$countryid
				);
		return $this->db->insert('survey_countries',$options);
	}

	
}
	
