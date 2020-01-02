<?php
class Country_model extends CI_Model { 

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all countries
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
	* Returns a list of all countries
	*
	*/
	function select_all_compact()
	{		
		$this->db->select('countryid,name');
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
		
		if ($this->alias_exists($alias))
		{
			return;
		}
	
		$options=array(
				'alias'=>$alias,
				'countryid'=>$countryid
		);
		
		$this->db->insert("country_aliases",$options);
	
	}

	/**
	*
	* Check if alias exists
	**/
	function alias_exists($alias)
	{		
		$this->db->select('count(*) as total');
		$this->db->from('country_aliases');
		$this->db->where('alias', $alias);
		$row = $this->db->get()->row_array();
		if ($row['total']>0)
		{
			return TRUE;
		}
		
		return FALSE;	
	}



	//delete a country
	function delete($countryid)
	{
		$this->db->where('countryid', $countryid);
		$this->db->delete('countries');
		
		//delete aliases
		$this->delete_aliases($countryid);
	}
	

	//remove countries by survey SID
	function delete_by_sid($sid)
	{
		$this->db->where('sid',$sid);
		$this->db->delete('survey_countries');
	}

	
	//return a list of study related countries that are not using an ISO code
	function get_broken_study_countries()
	{
		$this->db->select('country_name, count(country_name) as total');
		$this->db->from('survey_countries');
		$this->db->group_by('country_name');
		$this->db->where('cid',0);
		$this->db->order_by('country_name');
		return $this->db->get()->result_array();		
	}
	
	/**
	*
	* Update study related countries to use the country code
	**/
	function update_survey_country_code($name,$cid)
	{
		$options=array(
			'cid'=>$cid			
		);
		$this->db->where('country_name',$name);
		$this->db->where('cid',0);
		$this->db->update('survey_countries',$options);
	}
	
	
	/**
	*
	* Return country id by country name
	**/
	function find_country_by_name($name)
	{
		$this->db->select('countryid');
		$this->db->where('name',$name);
		$country=$this->db->get('countries')->row_array();
		
		if ($country)
		{
			return $country['countryid'];
		}
		
		//search country aliases for the country name
		$this->db->select('countryid');
		$this->db->where('alias',$name);
		$country=$this->db->get('country_aliases')->row_array();
		
		if ($country)
		{
			return $country['countryid'];
		}
		
		return false;
	}


	/**
	*
	* Return country system name
	*
	**/
	function get_country_system_name($name)
	{
		$this->db->select('name, alias');
		$this->db->join('country_aliases', 'countries.countryid= country_aliases.countryid','left');
		$this->db->where('name',$name);
		$this->db->or_where('alias',$name);
		$country=$this->db->get('countries')->result_array();
		
		if (!$country){
			return false;
		}

		return $country[0]['name'];		
	}
	
	
	
}