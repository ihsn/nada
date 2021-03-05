<?php
class Facet_model extends CI_Model {
 

	private $facets=[];

    public function __construct()
    {
        parent::__construct();
		$this->config->load('facets');
		$this->facets=$this->config->item("facets");
		$this->output->enable_profiler(FALSE);
	}


	function get_facet_options()
	{
		return $this->facets;
	}
	
	
	/**
	* 
	* Get a single value
	* 
	**/
	function select_single($id)
	{		
		$this->db->select("*");
		$this->db->where('id', (integer)$id); 
		return $this->db->get('facets')->row_array();
	}


	function get_facet_id($facet_name)
	{		
		$this->db->select("*");
		$this->db->where('name', $facet_name);
		$result=$this->db->get('facets')->row_array();

		if($result){
			return $result['id'];
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Return a list of all facets
	 * 
	 */
	function select_all()
	{		
		$this->db->select("*");
		return $this->db->get('facets')->result_array();
	}
	

	/**
	 * 
	 * 
	 * Get facets value by facet
	 * 
	 */
	function get_facet_values($facet_id,$published=null,$sort_by='value', $sort_order='ASC')
	{
		/*
		select facet_terms.*, survey_facets.* from facet_terms
			inner join survey_facets on facet_terms.id=survey_facets.term_id
    		inner join  surveys on survey_facets.sid=surveys.id
				where facet_terms.facet_id=1 
				and surveys.published=1;
		*/

		$this->db->select('facet_terms.id,facet_terms.value as title, count(survey_facets.id) as found');
		$this->db->order_by($sort_by, $sort_order);		
		$this->db->join('survey_facets', 'facet_terms.id = survey_facets.term_id');
		$this->db->join('surveys', 'survey_facets.sid = surveys.id');
		$this->db->where('facet_terms.facet_id',$facet_id);
		$this->db->where('survey_facets.facet_id',$facet_id);

		if (!empty($published)){
			$this->db->where('surveys.published', $published);
		}

		$this->db->group_by('facet_terms.id,facet_terms.value');

		//max limit for facet values
		$this->db->limit(300);
		
		$query=$this->db->get('facet_terms');

		//echo $this->db->last_query();

		if (!$query){
			return FALSE;
		}
		
		$result= $query->result_array();

		$output=array();
		foreach($result as $row)
		{
			$output[$row['id']]=$row;
		}
		return $output;
	}



	
	

	/**
	 * 
	 * 
	 * Create facets
	 * 
	 * 
	 */
	function create_facet($options)
	{
		//allowed fields
		$valid_fields=array(
			'name',
			'title',			
			);

		$data=array();
				
		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields) ){
				$data[$key]=$value;
			}
		}		
		
		return $this->db->insert('facets', $data);		
	}
	
	
	
	function delete_facet($id)
	{
		$this->db->where('id', $id); 
		return $this->db->delete('facets');
	}

	function clear_facet_values($sid)
	{
		$this->db->where('sid', $sid);
		return $this->db->delete('survey_facets');
	}


	/**
	 * 
	 * Create or update facet term
	 * 
	 */
	function upsert_facet_term($facet_id,$value)
	{
		$options=array(
			'facet_id'=>$facet_id,
			'value'=>$value	
			);

		$term=$this->facet_term_exists($facet_id, $value);
		
		if (!$term){
			return $this->db->insert('facet_terms', $options);
		}

		return $term['id'];
	}

	/**
	 * 
	 * insert values for facets
	 * 
	 */
	function insert_facet_value($sid,$facet_id,$term_id)
	{
		$options=array(
			'facet_id'=>$facet_id,
			'sid'=>$sid,
			'term_id'=>$term_id			
			);

		return $this->db->insert('survey_facets', $options);
	}


	function get_valid_fields($fields,$options)
	{
		$data=array();
		foreach($options as $key=>$value){
			if (in_array($key,$fields) ){
				$data[$key]=$value;
			}
		}	

		return $data;
	}


	/**
	 * 
	 * Check if a term value exists
	 * 
	 */
	function facet_term_exists($facet_id,$term_value)
	{
		var_dump("<HR>");
		var_dump($facet_id);
		var_dump($term_value);
		
		$this->db->select("*");
		$this->db->where('facet_id', $facet_id);
		$this->db->where('value', $term_value);
		$result=$this->db->get('facet_terms')->result_array();

		if($result && isset($result[0])){
			return $result[0];
		}

		return false;
	}
	
		


	function extract_facet_values($type,$metadata)
	{
		$metadata = new \Adbar\Dot($metadata);

		$output=array();

		//iterate over all facets
		foreach($this->facets as $facet_key=>$facet){
			if(isset($facet['enabled']) && $facet['enabled']==true){

				if (!isset($facet["mappings"])){
					continue;
				}

				foreach ($facet['mappings'] as $facet_data_type=>$facet_mapping){
					if ($facet_data_type==$type){
						$facet_data=$metadata->get($facet_mapping['path']);

						if (!empty($facet_data) && isset($facet_mapping['column']) 
							&& $facet_mapping['column']!='' 
							//&& isset($facet_data[$facet_mapping['column']])
						){
							/*if(!isset($facet_data[$facet_mapping['column']])){
								//var_dump($facet_mapping);
								var_dump($facet_data);
								echo '<hr>xxxx';

								foreach($facet_data as $row){
									var_dump($row[$facet_mapping['column']]);
									echo '<HR>';
								}
								//die("ERORR");

							}*/
							$column_data=array_column($facet_data, $facet_mapping['column']);
							$output[$facet_key]=(array)$column_data;
						}else{
							$output[$facet_key]=(array)$facet_data;
						}

					}
				}
			}
		}
		
		return $output;
	}

	

}
