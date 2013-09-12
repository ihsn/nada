<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Bulk Data Acces
 * 
 *
 *
 *
 * @subpackage	Libraries
 * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class Bulk_data_access
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Bulk_data_access Class Initialized.");
		$this->ci =& get_instance();
	}

	/*
	*
	* Checks whether study has bulk data access
	*/
	function study_has_bulk_access($study_id)
	{		
		$this->ci->db->select('count(*) as found');
		$this->ci->db->where('sid',$study_id);
		$result=$this->ci->db->get('da_collection_surveys')->row_array();
		
		if ($result['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	//returns an array of data access sets/collections this study is part of 
	function get_study_bulk_access_sets($study_id)
	{
		$this->ci->db->select('da_collection_surveys.cid,da_collections.title,da_collections.description');
		$this->ci->db->join('da_collections','da_collections.id=da_collection_surveys.cid','inner');
		$this->ci->db->where('sid',$study_id);
		$result=$this->ci->db->get('da_collection_surveys')->result_array();
		
		if (!$result)
		{
			return FALSE;
		}
		
		$output=array();
		foreach($result as $row)
		{
			$output[$row['cid']]=$row;
		}
		
		return $output;
	}
	
	function get_study_counts_by_collection($da_collection_id)
	{
		$this->ci->db->select('count(*) as total');
		$this->ci->db->join('da_collections','da_collections.id=da_collection_surveys.cid','inner');
		$this->ci->db->group_by('da_collections.id,da_collections.title');
		$result=$this->ci->db->get('da_collection_surveys')->row_array();
		return $result['total'];
	}
	
	function get_study_list_by_set($da_collection_id)
	{
		$this->ci->db->select('surveys.id,surveys.titl,nation,data_coll_start,data_coll_end');
		$this->ci->db->join('da_collection_surveys','surveys.id=da_collection_surveys.sid','inner');
		$this->ci->db->where('da_collection_surveys.cid',$da_collection_id);
		$result=$this->ci->db->get('surveys')->result_array();
		
		return $result;
	}
	
	function get_study_id_list_by_set($da_collection_id)
	{
		$this->ci->db->select('surveys.id');
		$this->ci->db->join('da_collection_surveys','surveys.id=da_collection_surveys.sid','inner');
		$this->ci->db->where('da_collection_surveys.cid',$da_collection_id);
		$result=$this->ci->db->get('surveys')->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row['id'];
		}
		
		return $output;
	}
	
	
	function get_collection($da_collection_id)
	{
		$this->ci->db->select('*');
		$this->ci->db->where('id',$da_collection_id);
		$result=$this->ci->db->get('da_collections')->row_array();		
		return $result;		
	}
	
	function select_all()
	{
		$this->ci->db->select('*');
		$result=$this->ci->db->get('da_collections')->result_array();		
		return $result;
	}
	
	
	function update($cid,$options)
	{	
		//allowed fields
		$valid_fields=array(
			'title',
			'description'
		);

		//pk field name
		$key_field='id';
		
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//update db
		$this->ci->db->where($key_field, $cid);
		$result=$this->ci->db->update('da_collections', $data); 

		return $result;		
	}
	
	
	
	public function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'title',
			'description'
		);

		//pk field name
		$key_field='id';
		
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		$result=$this->ci->db->insert('da_collections', $data); 

		return $result;		
	}
	
	
	
	public function delete($id)
	{		
		$this->ci->db->where('cid', $id); 
		$this->ci->db->delete('da_collection_surveys');
		
		$this->ci->db->where('id', $id); 
		$this->ci->db->delete('da_collections');
	}


	public function attach_study($collection_id,$sid)
	{
		$options=array(
			'cid'=>$collection_id,
			'sid'=>$sid
		);
		
		return $this->ci->db->insert('da_collection_surveys', $options); 
	}


	public function detach_study($collection_id,$sid)
	{
		$options=array(
			'cid'=>$collection_id,
			'sid'=>$sid
		);
		
		$this->ci->db->where('cid', $collection_id); 
		$this->ci->db->where('sid', $sid); 
		return $this->ci->db->delete('da_collection_surveys'); 
	}
	

}//end-class

