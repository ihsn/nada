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
		$this->ci->db->select('da_collection_surveys.cid,da_collections.title');
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

}//end-class

