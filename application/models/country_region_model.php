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

	/**
	*
	* Return a single vocabulary
	*/
	function select_single($id)
	{		
		$this->db->select('*');
		$this->db->from('regions');
		$this->db->where('id', $id);
		$query = $this->db->get()->row_array();		
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
	function get_tree_region_countries()
	{
		$region_tree=$this->get_tree();
		$count=count($region_tree);
		for($i=0;$i<=$count;$i++)
		{
			if (isset($region_tree[$i]['children']))
			{
				foreach($region_tree[$i]['children'] as $key=>$child)
				{
					$region_tree[$i]['children'][$key]['countries']=$this->get_countries_by_region($child['id']);
				}	
			}	
		}
		
		return $region_tree;
	}
	
	function get_countries_by_region($region_id)
	{
		$this->db->select('c.countryid,c.name');
		$this->db->from('region_countries rc');
		$this->db->join('countries c','c.countryid=rc.country_id','inner');
		$this->db->where('rc.region_id',$region_id);
		$this->db->order_by('c.name');
		$query = $this->db->get()->result_array();
		return $query;		
	}
}