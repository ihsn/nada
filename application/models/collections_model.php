<?php
class Collections_model extends CI_Model {
 
 	var $search_count=0;
	var $db_fields=array('title', 'description', 'weight', 'thumbnail');
	
    public function __construct()
    {
        parent::__construct();
    }

	//search
    public function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL)
    {	
		$this->db->start_cache();
		
		//select columns for output
		$this->db->select('*');
		
		//allowed_fields
		$db_fields=array('title', 'description', 'weight', 'thumbnail');
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{
				//search only in the allowed fields
				if (in_array($f['field'],$db_fields))
				{
					$this->db->like($f['field'], $f['keywords']); 
				}
				else if ($f['field']=='all')
				{
					foreach($db_fields as $field)
					{
						$this->db->or_like($field, $f['keywords']); 
					}
				}
			}
		}

		$this->db->stop_cache();
		
		//test if valid sort field
		if (!in_array($sort_by,$this->db_fields))
		{
			$sort_by='title';
			$sort_order='ASC';
		}
		
		//set order by
		if ($sort_by!='' && $sort_order!='')
		{
			$this->db->order_by($sort_by, $sort_order); 
		}
		
		//set Limit clause
	  	$this->db->limit($limit, $offset);
		$this->db->from('collections');
		
        $result= $this->db->get()->result_array();				
		
		//get count
		$this->search_count=$this->db->count_all_results('collections');
	
		return $result;
    }

 	public function search_count()
    {
        return $this->db->count_all_results('collections');
    }
	
	public function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
			'title',
			'description',
			'weight',
			'thumbnail'
			);

		if (!is_numeric($options['weight']))
		{
			$options['weight']=0;
		}
		
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
		$this->db->where($key_field, $id);
		$result=$this->db->update('collections', $data); 

		return $result;		
	}
	
	public function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'title',
			'description',
			'weight',
			'thumbnail'
			);

		if (!is_numeric($options['weight']))
		{
			$options['weight']=0;
		}
		
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
		
		$result=$this->db->insert('collections', $data); 

		return $result;		
	}
	
	public function delete($id)
	{
		$this->db->where('id', $id); 
		return $this->db->delete('collections');
	}
	
	public function select_single($id)
	{		
		$this->db->select("*");
		$this->db->where('id', (integer)$id); 
		return $this->db->get('collections')->row_array();
	}
	
	public function select_all($sort_by='weight', $sort_order='ASC')
	{
		$this->db->select('*');	
		$this->db->order_by($sort_by, $sort_order);
		$query=$this->db->get('collections');
		
		return $query->result_array();
	}
	
	/**
	*
	* Return collections attached to a survey
	**/
	public function get_survey_collections($sid)
	{
		$this->db->select('*');	
		$this->db->join('survey_collections','survey_collections.tid=collections.id','inner');
		$this->db->order_by('weight', 'ASC');
		$this->db->where('survey_collections.sid',$sid);
		$query=$this->db->get('collections');	
		return $query->result_array();
	}

	/**
	*
	* Return a list of collection IDs attached to a survey
	**/
	public function get_survey_collection_id_list($sid)
	{
		$this->db->select('tid');	
		$this->db->join('survey_collections','survey_collections.tid=collections.id','inner');
		$this->db->order_by('weight', 'ASC');
		$this->db->where('survey_collections.sid',$sid);
		$query=$this->db->get('collections')->result_array();	
		$list=array();
		foreach($query as $row)
		{
			$list[]=$row['tid'];
		}
		
		return $list;
	}


	/**
	*
	* detach collection from survey
	**/
	function detach($sid,$tid)
	{
		$options=array(
				'sid'=>$sid,
				'tid'=>$tid
		);
			
		return $this->db->delete("survey_collections",$options);
	}

	/**
	*
	* attach collection to a survey
	**/
	function attach($sid,$tid)
	{
		$options=array(
				'sid'=>$sid,
				'tid'=>$tid
		);
		
		return $this->db->insert("survey_collections",$options);
	}	
}