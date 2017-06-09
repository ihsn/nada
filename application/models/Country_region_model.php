<?php
class Country_region_model extends CI_Model { 

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all regions
	*
	*/
	function select_all()
	{		
		$this->db->select('*');
		$this->db->from('regions');
		$this->db->order_by('pid,weight,title');
		$query = $this->db->get()->result_array();		
		return $query;
	}


	function get_countries_compact()
	{
		$this->db->select('countryid,name');
		$this->db->from('countries');
		$this->db->order_by('name');
		return $this->db->get()->result_array();		
	}
	

	/**
	*
	* Return a single row
	*/
	function select_single($id)
	{		
		$this->db->select('*');
		$this->db->from('regions');
		$this->db->where('id', $id);
		$row = $this->db->get()->row_array();
		$row['countries']=$this->get_country_array_by_region($id);
		return $row;
	}
	
	
	function get_country_array_by_region($region_id)
	{
		$this->db->select('country_id');
		$this->db->from('region_countries');
		$this->db->where('region_id', $region_id);
		$rows = $this->db->get()->result_array();
		$output=array();
		foreach($rows as $row)
		{
			$output[]=$row['country_id'];
		}
		return $output;
	}
	
	function get_parents()
	{
		$this->db->select('*');
		$this->db->from('regions');
		$this->db->where('pid', 0);
		$query = $this->db->get()->result_array();		
		return $query;
	}
	
	//source: http://stackoverflow.com/questions/4843945/
	//php-tree-structure-for-categories-and-sub-categories-without-looping-a-query
	private function build_tree($items) 
	{	
		$children = array();
	
		foreach($items as &$item) 
		{	
			$children[$item['pid']][] = &$item;
			unset($item);
		}
		
		foreach($items as &$item)
		{ 
			if (isset($children[$item['id']]))
			{
				$item['children'] = $children[$item['id']];
			}
		}	
		return $children[0];
	}
	
	function get_tree()
	{
		$items=$this->select_all();
		return $this->build_tree($items);
	}

	
	/**
	*
	* Build an tree array with regions and countries
	**/
	function get_tree_region_countries($repositoryid=NULL)
	{
		$region_tree=$this->get_tree();
		$count=count($region_tree);
		for($i=0;$i<=$count;$i++)
		{
			if (isset($region_tree[$i]['children']))
			{
				foreach($region_tree[$i]['children'] as $key=>$child)
				{
					$region_tree[$i]['children'][$key]['countries']=$this->get_countries_by_region($child['id'],$repositoryid);
				}	
			}	
		}
		
		return $region_tree;
	}
	
	function get_countries_by_region($region_id,$repositoryid=NULL)
	{
		$this->db->select('c.countryid,c.name,count(c.countryid) as total');
		$this->db->from('region_countries rc');
		$this->db->join('countries c','c.countryid=rc.country_id','inner');
		$this->db->join('survey_countries sc','sc.cid=rc.country_id','inner');
		$this->db->join('surveys s','s.id=sc.sid','inner');
		
		if($repositoryid!=NULL)
		{
			$this->db->join('survey_repos', 's.id=survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}
		
		$this->db->where('rc.region_id',$region_id);
		$this->db->where('s.published',1);
		$this->db->group_by('c.countryid,c.name');
		$this->db->order_by('c.name');
		$query = $this->db->get();
		
		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();
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
			'pid',
			'title',
			'weight'
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
		$this->db->where('id', $id);
		$result=$this->db->update('regions', $data); 

		//delete all existing region related countries
		$this->delete_region_related_countries($id);

		$countries=array();
		
		//remove duplicates
		foreach($options['country'] as $country)
		{		
			$countries[$country]=$country;
		}
		
		//update related countries
		foreach($countries as $country)
		{
			if( is_numeric($country) && $country>0)
			{
				$this->add_region_related_country($id,$country);
			}	
		}

		return $result;		
	}
	
	
	
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
			'pid',
			'title',
			'weight'
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
		
		//insert
		$result=$this->db->insert('regions', $data);
		
		if (!$result)
		{
			return FALSE;
		} 
		
		$id=$this->db->insert_id();

		$countries=array();
		
		if (!isset($options['country']))
		{
			return $result;
		}
		
		//remove duplicate countries
		foreach($options['country'] as $country)
		{		
			$countries[$country]=$country;
		}
		
		//update related countries
		foreach($countries as $country)
		{
			if( is_numeric($country) && $country>0)
			{
				$this->add_region_related_country($id,$country);
			}	
		}

		return $result;
	}
	
	
	function delete($region_id)
	{
		//get all children regions
		$child_regions=$this->get_child_regions($region_id);
		
		$regions=NULL;
		$regions[]=$region_id;
		
		if($child_regions)
		{
			foreach($child_regions as $row)
			{
				$regions[]=$row['id'];
			}
		}
		
		//delete the regions + sub-regions
		$this->db->where_in('id', $regions);
		$this->db->delete('regions');
		
		//delete children regions
		//$this->db->where('pid',$region_id);
		//$this->db->delete('region_countries');
		
		//delete region countries
		foreach($regions as $id)
		{
			$this->delete_region_related_countries($id);
		}	
	}
	
	
	//return sub/children regions
	function get_child_regions($region_id)
	{
		$this->db->select('id');
		$this->db->where('pid',$region_id);
		return $this->db->get('regions')->result_array();
	}
	
	
	//delete all related countries by region id
	function delete_region_related_countries($region_id)
	{
		$this->db->where('region_id', $region_id);
		return $this->db->delete('region_countries');
	}
	
	//add country to a region
	function add_region_related_country($region_id,$country_id)
	{	
		$options=array(
				'region_id'=>$region_id,
				'country_id'=>$country_id
		);
		
		$this->db->insert("region_countries",$options);
	}
	
	
}