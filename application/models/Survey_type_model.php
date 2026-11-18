<?php
class Survey_type_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
		


	//get type
	public function get_stype_id_by_name($type_name)
	{
		$this->db->select("id");
		$this->db->where("name",$type_name);
		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['id'];
		}
	}
	
    //get type
	public function get_type_by_id($stype_id)
	{
		$this->db->select("*");
		$this->db->where("id",$stype_id);
		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row;
		}
	}
	
	public function get_type_name($stype_id)
	{
		$survey_type=$this->get_type_by_id($stype_id);
		if ($survey_type){
			return $survey_type['name'];
		}
	}
	

	
	//get survey type
	public function get_survey_stypeid($sid)
	{
		$this->db->select("stype_id");
		$this->db->where("id",$sid);
		
		$q=$this->db->get("surveys");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['stype_id'];
		}
	}
	
	
	
	//return an array of all survey types array
	public function get_list()
	{
		$this->db->select("id, name");		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			return $q->result_array();
		}
	}
	

	//return an array of all survey types names only
	public function get_names_array()
	{
		$list=$this->get_list();

		$output=array();
		foreach($list as $row){
			$output[]=$row['name'];
		}

		return $output;
	}

	/**
	 * All dataset types for catalog tab order admin (weight DESC).
	 *
	 * @return array[] list of {id, code, title, weight}
	 */
	public function get_types_for_ordering()
	{
		if (!$this->db->table_exists('survey_types')) {
			return array();
		}

		$this->db->select('id, code, title, weight');
		$this->db->order_by('weight', 'DESC');
		$this->db->order_by('id', 'ASC');
		$rows = $this->db->get('survey_types')->result_array();
		if (!is_array($rows)) {
			return array();
		}

		$output = array();
		foreach ($rows as $row) {
			$output[] = array(
				'id'     => (int) $row['id'],
				'code'   => (string) $row['code'],
				'title'  => (string) (isset($row['title']) ? $row['title'] : $row['code']),
				'weight' => (int) (isset($row['weight']) ? $row['weight'] : 0),
			);
		}

		return $output;
	}

	/**
	 * Rewrite survey_types.weight from an ordered list of codes (first = highest weight).
	 *
	 * @param string[] $codes ordered type codes
	 * @return array[] updated list from get_types_for_ordering()
	 * @throws Exception when the payload is incomplete or contains unknown codes
	 */
	public function reorder_by_codes(array $codes)
	{
		$codes = array_values(array_filter(array_map(function ($code) {
			return is_string($code) ? trim($code) : '';
		}, $codes), 'strlen'));

		if (!$codes) {
			throw new Exception('ORDER_REQUIRED');
		}

		$existing = $this->get_types_for_ordering();
		$by_code = array();
		foreach ($existing as $row) {
			$by_code[$row['code']] = $row;
		}

		if (count($codes) !== count($by_code)) {
			throw new Exception('ORDER_INCOMPLETE');
		}

		$seen = array();
		foreach ($codes as $code) {
			if (!isset($by_code[$code])) {
				throw new Exception('UNKNOWN_TYPE:' . $code);
			}
			if (isset($seen[$code])) {
				throw new Exception('DUPLICATE_TYPE:' . $code);
			}
			$seen[$code] = true;
		}

		$count = count($codes);
		foreach ($codes as $index => $code) {
			$weight = ($count - $index) * 10;
			$this->db->where('code', $code);
			$this->db->update('survey_types', array('weight' => $weight));
		}

		return $this->get_types_for_ordering();
	}
}
	
