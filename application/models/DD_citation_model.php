<?php
/**
* Data deposit project citations
*
**/
class DD_citation_model extends CI_Model {
	
	//database column names
	var $fields=array(
		'pid',
		'citation',
		'created',
		'changed',
		'created_by',
		'changed_by'
	);

			
    public function __construct()
    {
		parent::__construct();
		$this->load->model('DD_project_model');
		//$this->output->enable_profiler(TRUE);
    }
	

	/**
	 * 
	 * Encode citation json for DB storage
	 * 
	 */
	function encode($citation_json)
	{
		return base64_encode(json_encode($citation_json));
	}


	/**
	 * 
	 * 
	 * Decode citation
	 * 
	 */
	function decode($encoded_citation)
	{
		return json_decode(base64_decode($encoded_citation),true);
	}

	/**
	* 
	*
	* returns a single row
	*
	**/
	function select_single($id)
	{
		$this->db->where('id', $id); 
		$result=$this->db->get('dd_citations')->row_array();

		if(isset($result['citation'])){
			$result['citation']=$this->decode($result['citation']);
		}
		
		return $result;
	}

	/**
	 * 
	 * 
	 * Get a single citation
	 * 
	 */
	function get_project_single_citation($project_id,$citation_id)
	{
		$this->db->select('*');
		$this->db->where('pid',$project_id);
		$this->db->where('id',$citation_id);		
		$result=$this->db->get("dd_citations")->row_array();
		
		if(isset($result['citation'])){
			$result['citation']=$this->decode($result['citation']);
		}
		
		return $result;
	}


	//get citations by project
	function get_project_citations($project_id)
	{
		$this->db->select('*');
		$this->db->where('pid',$project_id);
		$result=$this->db->get("dd_citations")->result_array();

		foreach($result as $index=>$row)
		{
			//decode citation
			$result[$index]['citation']=$this->decode($row['citation']);
		}

		return $result;
	}



	/**
	 * 
	 * 
	 * Delete citation
	 * 
	 */
	function delete($citation_id)
	{
		//remove from db
		$this->db->where('id', $citation_id); 
		return $this->db->delete('dd_citations');
	}




	/**
	* 
	*	Create new citation
	*
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=$this->fields;

		$options['changed']=date("U");
		$options['created']=date("U");

		if(isset($options['citation'])){
			$options['citation']=$this->encode($options['citation']);
		}
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}
		
		$result=$this->db->insert('dd_citations', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
			
		$citation_id=$this->db->insert_id();
		return $citation_id;
	}


	/**
	* update Citation
	*
	*	citation_id		int
	* 	options			array
	**/
	function update($citation_id,$options)
	{
		$valid_fields=$this->fields;		
		$options['changed']=date("U");

		if(isset($options['citation'])){
			$options['citation']=$this->encode($options['citation']);
		}
		
		$data=array();

		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields)){
				$data[$key]=$value;
			}
		}
		
		$this->db->where('id', $citation_id);
		$result=$this->db->update('dd_citations', $data);

		if ($result===false){
			throw new MY_Exception($this->db->error());
		}
		
		return $result;
	}
	
	


	/**
	 * 
	 * 
	 * Check if a citation exists
	 * 
	 * 
	 */
	function citation_exists($project_id,$citation_id)
	{	
		$this->db->select("id");
		$this->db->where('pid',$project_id);
		$this->db->where('id',$citation_id);
		$result=$this->db->get("dd_citations")->row_array();

		if(isset($result['id'])){
			return $result['id'];
		}

		return false;
	}



}