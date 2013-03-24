<?php
class Country_model extends CI_Model { 

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all vocabularies
	*
	*/
	function select_all()
	{		
		$this->db->select('*');
		$this->db->from('countries');
		$this->db->order_by('name');
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single country
	*/
	function select_single($id)
	{		
		$this->db->select('*');
		$this->db->from('countries');
		$this->db->where('countryid', $id);
		$row = $this->db->get()->row_array();
		$aliases=$this->get_country_aliases($id);
		if($aliases)
		{
			$row['aliases']=$aliases[$id];
		}	
		return $row;
	}
	
	/**
	*
	* Return all country aliases
	**/
	function get_country_aliases($countryid=NULL)
	{
		$this->db->select("*");
		$this->db->order_by("countryid");
		
		if($countryid)
		{
			$this->db->where('countryid', $countryid);
		}	
		
		$rows=$this->db->get("country_aliases")->result_array();
		$output=array();
		
		foreach($rows as $row)
		{
			$output[$row['countryid']][]=$row['alias'];
		}
		
		return $output;
	}



	/**
	* update country
	*
	*	id			int
	* 	options		array
	**/
	function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
			'name',
			'iso',
			);

		
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
		$this->db->where('countryid', $id);
		$result=$this->db->update('countries', $data); 

		//delete all existing country aliases
		$this->delete_aliases($id);

		$aliases=array();
		
		//remove duplicates
		foreach($options['alias'] as $alias)
		{		
			$aliases[$alias]=$alias;
		}

		
		//update aliases
		foreach($aliases as $alias)
		{
			if( trim($alias)!="")
			{
				$this->add_alias($id,$alias);
			}	
		}

		return $result;		
	}


	/**
	* insert country
	*
	*	id			int
	* 	options		array
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'name',
			'iso',
			);

		
		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//insert db
		$result=$this->db->insert('countries', $data); 
		
		if (!$result)
		{
			return false;
		}
		
		$id=$this->db->insert_id();

		$aliases=array();
		
		//remove duplicates
		foreach($options['alias'] as $alias)
		{		
			$aliases[$alias]=$alias;
		}
		
		//update aliases
		foreach($aliases as $alias)
		{
			if( trim($alias)!="")
			{
				$this->add_alias($id,$alias);
			}	
		}

		return $result;		
	}


	//delete all country aliases
	function delete_aliases($countryid)
	{
		$this->db->where('countryid', $countryid);
		return $this->db->delete('country_aliases');
	}
	
	//add country alias
	function add_alias($countryid,$alias)
	{
		$options=array(
				'alias'=>$alias,
				'countryid'=>$countryid
		);
		
		$this->db->insert("country_aliases",$options);
	
	}

	//delete a country
	function delete($countryid)
	{
		$this->db->where('countryid', $countryid);
		$this->db->delete('countries');
		
		//delete aliases
		$this->delete_aliases($countryid);
	}

	
}