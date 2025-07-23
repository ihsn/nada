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


	/**
	 * 
	 * 
	 * Get all countries for surveys by IDs
	 * 
	 * @param array $sid_arr
	 * 
	 */
	function get_survey_country_names($sid_arr)
	{
		if(!is_array($sid_arr) || count($sid_arr)==0){
			return array();
		}

		$this->db->select('survey_countries.sid, survey_countries.cid, countries.iso, countries.name as country_name');
		$this->db->join('countries','countries.countryid=survey_countries.cid','left');
		$this->db->where_in('survey_countries.sid',$sid_arr);
		
		$result=$this->db->get('survey_countries')->result_array();
		
		if (!$result || count($result)==0){
			return array();
		}

		$countries= array();

		foreach ($result as $key => $value) {
			if ($value['country_name']!='' ) {
				$countries[$value['sid']][]=[
					'iso'=>$value['iso'],
					'name'=>$value['country_name']					
				];
			}
		}

		return $countries;
	}

	
}
	
