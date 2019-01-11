<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Bulk Data Acces
 * 
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
		$this->ci->db->select('surveys.id,surveys.title,nation,year_start,year_end');
		$this->ci->db->join('da_collection_surveys','surveys.id=da_collection_surveys.sid','inner');
		$this->ci->db->order_by('nation'); 
		$this->ci->db->where('da_collection_surveys.cid',$da_collection_id);
		$result=$this->ci->db->get('surveys')->result_array();
		
		return $result;
	}
	
	function get_study_id_list_by_set($da_collection_id)
	{
		$this->ci->db->select('surveys.id');
		$this->ci->db->join('da_collection_surveys','surveys.id=da_collection_surveys.sid','inner');
		$this->ci->db->where('da_collection_surveys.cid',$da_collection_id);
		$this->ci->db->order_by('surveys.nation');
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
	
	
    public function search($search_options=array(),$limit = 15, $offset = 0,$sort_by=NULL,$sort_order=NULL)
    {
	
		$db_fields=array(
				'title'			=> 'surveys.title',
				'nation'		=> 'surveys.nation',
				'repositoryid'	=> 'survey_repos.repositoryid',
				'changed'		=> 'surveys.changed'
		);
		
		$where=$this->build_search_where($search_options);
		
		//set Limit clause
	  	$this->ci->db->select('surveys.id,surveys.title,surveys.nation,surveys.year_start,surveys.year_end,survey_repos.repositoryid,surveys.changed');
		$this->ci->db->join('forms', 'forms.formid= surveys.formid');
		$this->ci->db->join('survey_repos', 'survey_repos.sid= surveys.id');
		$this->ci->db->limit($limit, $offset);
		$this->ci->db->from('surveys');
		$this->ci->db->where('survey_repos.isadmin',1);
		
		if (count($where)>0)
		{
			$this->ci->db->where( implode(" AND ",$where), NULL, FALSE);
		}

		//set default sort order, if invalid fields are set
		if (!array_key_exists((string)$sort_by,$db_fields))
		{
			$sort_by='title';
			$sort_order='ASC';
		}		
		
		//must be set outside the start_cache...stop_cache to produce correct count_all_results query
		if ($sort_by!='' && $sort_order!='')
		{
			$this->ci->db->order_by($db_fields[$sort_by], $sort_order); 
		}
				
        $result= $this->ci->db->get()->result_array();
		
		//echo $this->ci->db->last_query();
		return $result;
    }
	
	private function build_search_where($search_options=array())
	{
		$where=array();
		
		//show only licensed surveys
		$where[]=" forms.model='licensed'";
		
		foreach($search_options as $key=>$value)
		{
			if ($key=='keywords' && trim($value)!="" )
			{
				$where[]=sprintf(" (surveys.title like %s OR surveys.idno=%s OR surveys.nation like %s or survey_repos.repositoryid like %s)",
						$this->ci->db->escape('%'.$value.'%'),
						$this->ci->db->escape($value),
						$this->ci->db->escape('%'.$value.'%'),
						$this->ci->db->escape('%'.$value.'%')
						);
			}
			else if ($key=='selected_only' && is_array($value))//attached studies only
			{
				$where[]=sprintf(" (surveys.id in (%s) )",implode(",",$value) );
			}
		}
		return $where;
	}
	

	//returns the search result count  	
    public function search_count($search_options)
    {
		$where=$this->build_search_where($search_options);
		
		//set Limit clause
	  	$this->ci->db->select('count(*) as total');
		$this->ci->db->join('forms', 'forms.formid= surveys.formid');
		$this->ci->db->join('survey_repos', 'survey_repos.sid= surveys.id');
		$this->ci->db->from('surveys');
		$this->ci->db->where('survey_repos.isadmin',1);

		$this->ci->db->where('forms.model','licensed');

		if (count($where)>0)
		{
			$this->ci->db->where( implode(" AND ",$where), NULL, FALSE);
		}
				
        $result= $this->ci->db->get()->row_array();
		return $result['total'];
    }
	

}//end-class

