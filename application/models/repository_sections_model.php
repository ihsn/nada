<?php
class Repository_sections_model extends CI_Model {
 
 	var $search_count=0;
	var $db_fields=array('title', 'weight');
	
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
		$db_fields=array('title',  'weight', );
		
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
		$this->db->from('repository_sections');
		
        $result= $this->db->get()->result_array();				
		
		//get count
		$this->search_count=$this->db->count_all_results('repository_sections');
	
		return $result;
    }
		
	public function search_count()
    {
        return $this->db->count_all_results('repository_sections');
    }
	

	public function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
			'title',
			'weight',
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
		$result=$this->db->update('repository_sections', $data); 

		return $result;		
	}
	
	public function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'title',
			'weight',
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
		
		$result=$this->db->insert('repository_sections', $data); 

		return $result;		
	}
	
	public function delete($id)
	{
		$this->db->where('id', $id); 
		return $this->db->delete('repository_sections');
	}
	
	public function select_single($id)
	{		
		$this->db->select("*");
		$this->db->where('id', (integer)$id); 
		return $this->db->get('repository_sections')->row_array();
	}
	
	public function select_all($sort_by='weight', $sort_order='ASC')
	{
		$this->db->select('*');	
		$this->db->order_by($sort_by, $sort_order);
		$query=$this->db->get('repository_sections');
		
		return $query->result_array();
	}
}