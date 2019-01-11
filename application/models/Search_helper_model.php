<?php
/**
*  Data Catalog Search Helper methods
*
*/
class Search_helper_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		//$this->load->library('cache');
    }
	
	/**
	* Returns a list of topics filtered by countries
	*
	*/	
	function get_topics_by_countries($country_array)
	{
		//add quotes
		for($i=0;$i<count($country_array);$i++)
		{
			$country_array[$i]=$this->db->escape($country_array[$i]);
		}
		
		$countries=implode(",", $country_array);
		
		$sql=sprintf('select topics.tid from topics
				inner join survey_topics on topics.tid= survey_topics.tid
				inner join surveys on surveys.id =survey_topics.sid
				where surveys.nation in (%s);',$countries);		
		
		return $this->db->query($sql)->result_array();
	}
	
	/**
	* TODO://REMOVE NO LONGER IN USE - need to check
	*
	*	Returns a list of countries filtered by topics
	*
	*/	
	function get_countries_by_topics($topic_array)
	{
		$topics=implode(",", $topic_array);
		
		$sql=sprintf('select nation from terms
				  inner join survey_topics on terms.tid= survey_topics.tid
				  inner join surveys on surveys.id =survey_topics.sid
				  where terms.tid in (%s)
				  group by nation',$topics);		
		
		return $this->db->query($sql)->result_array();
	}
	
	/**
	* Returns a list of countries filtered by topics with min and max year
	*
	*/	
	function filter_by_topics($topic_array,$min_year=0,$max_year=0)
	{
		$topics=implode(",", $topic_array);
		
		$output=NULL;
		
		if ($min_year>0 and $max_year >0)
		{
			//get list of filtered countries
			$sql=sprintf('select nation from terms
				  inner join survey_topics on terms.tid= survey_topics.tid
				  inner join surveys on surveys.id =survey_topics.sid
				  inner join survey_years on surveys.id=survey_years.sid
				  where terms.tid in (%s)
  		        	and ((data_coll_year between %d and %d) or (data_coll_year=0) )
				  group by nation',$topics,$min_year,$max_year);
		}
		else
		{
			//get list of filtered countries
			$sql=sprintf('select nation from terms
				  inner join survey_topics on terms.tid= survey_topics.tid
				  inner join surveys on surveys.id =survey_topics.sid
				  where terms.tid in (%s)
				  group by nation',$topics);		
		}
		
		//echo $sql;
		$countries=$this->db->query($sql)->result_array();
		
		if ($countries)
		{
			foreach($countries as $country)
			{
				$output['countries'][]=$country['nation'];
			}			
		}
				
			$output['min_year']=0;
			$output['max_year']=0;

		return $output;		
	}
	
	/**
	* Returns a list of topics filtered by countries and years
	*
	*/	
	function filter_by_countries($country_array,$min_year=0, $max_year=0)
	{
		$output=NULL;
		
		//add quotes
		for($i=0;$i<count($country_array);$i++)
		{
			$country_array[$i]=$this->db->escape($country_array[$i]);
		}
		
		$countries=implode(",", $country_array);
		
		if ($min_year>0 && $max_year>0)
		{
			$sql=sprintf('select terms.tid as tid from terms
				inner join survey_topics on terms.tid= survey_topics.tid
				inner join surveys on surveys.id =survey_topics.sid
		        inner join survey_years on survey_years.sid=surveys.id
        		where surveys.nation in (%s)
		        and ((data_coll_year between %d and %d) or (data_coll_year=0) )
				group by terms.tid',$countries,$min_year, $max_year);
		}
		else
		{
			$sql=sprintf('select terms.tid as tid from terms
				inner join survey_topics on terms.tid= survey_topics.tid
				inner join surveys on surveys.id =survey_topics.sid
        		where surveys.nation in (%s)
				group by terms.tid',$countries);		
		}

		$query=$this->db->query($sql)->result_array();
		
		$output['topics']=array();
		
		if ($query)
		{
			foreach($query as $topic)
			{
				$output['topics'][]=$topic['tid'];
			}
		}
		
		$output['min_year']=0;//$min_year;
		$output['max_year']=0;//$max_year;
		
		return $output;
		
	}


	/**
	* Returns a list of topics and countries filtered by collection dates
	*
	*/	
	function filter_by_years($start_year=NULL, $end_year=NULL)
	{
		
		if (!is_numeric($start_year) || !is_numeric($end_year) )
		{
			return FALSE;
		}
	
		$output=NULL;
		
		//get topics
		$sql=sprintf('select tid from survey_topics t
						inner join survey_years y on t.sid=y.sid
						where y.data_coll_year between %s and %s
						group by tid;',$this->db->escape($start_year), $this->db->escape($end_year));		
		
		$query=$this->db->query($sql)->result_array();
		
		if ($query)
		{
			foreach($query as $topic)
			{
				$output['topics'][]=$topic['tid'];
			}
		}
		
		//get countries
		$sql=sprintf('select nation from surveys s
					  inner join survey_years y on s.id=y.sid
					  where y.data_coll_year between %s and %s
					  group by nation;',$this->db->escape($start_year), $this->db->escape($end_year));

		$query=$this->db->query($sql)->result_array();
		
		if ($query)
		{
			foreach($query as $country)
			{
				$output['countries'][]=$country['nation'];
			}
		}

		return $output;		
	}


/**
	* Return min. data collection year
	*
	*/
	function get_min_year()
	{
		$this->db->select_min('year_start','min_year');
		$this->db->where('year_start > 0'); 
		$this->db->where('published',1); 
		$result=$this->db->get('surveys')->row_array();
		
		if ($result)
		{
			$result=array_change_key_case($result, CASE_LOWER);
			return $result['min_year'];
		}
		return FALSE;
	}
	
	
	/**
	* Return max. data collection year
	*
	*/
	function get_max_year()
	{
		$this->db->select_max('year_end','max_year');
		$this->db->where('year_end > 0'); 
		$this->db->where('published',1); 
		$result=$this->db->get('surveys')->row_array();
		if ($result)
		{
			$result=array_change_key_case($result, CASE_LOWER);
			return $result['max_year'];
		}
		return FALSE;
	}

		
	/**
	* Get start and End data collection years
	*
	*/
	function get_collection_years()
	{
		//get start years
		$sql='select year_start from surveys
				where year_start>0 and published=1
				group by year_start;';
		$result=$this->db->query($sql)->result_array();
		
		$years['from']=array('--'=>'--');
		
		if ($result)
		{
			foreach($result as $row)
			{
				$years['from'][$row['year_start']]=$row['year_start'];
			}
		}

		//get end years
		$sql='select year_end from surveys
				where year_end>0 and published=1
				group by year_end;';
				
		$result=$this->db->query($sql)->result_array();
		
		$years['to']=array('--'=>'--');
		if ($result)
		{
			foreach($result as $row)
			{
				$years['to'][$row['year_end']]=$row['year_end'];
			}
		}
		
		return $years;		
	}

	/**
	* Topics with survey counts 
	*
	*/	
	function get_active_topics()
	{
		$sql='select t.tid as tid,t.pid as pid,t.title as title,count(st.sid) as surveys_found
				from topics t
				inner join survey_topics st on t.tid=st.tid
					   where pid<>0 group by t.tid
				union all
				select t.tid as tid,t.pid as pid,t.title as title,count(st.sid) as surveys_found
				from topics t
					left join survey_topics st on t.tid=st.tid
					   where pid=0 and t.tid
						in(
							select t.pid from topics t
						inner join survey_topics st on t.tid=st.tid
					  )
					group by t.tid
					order by pid,tid;';		
		
		return $this->db->query($sql)->result_array();
	}
	
	
	/**
	* List of countries from the survey table [nation field]
	*
	*
	* @repositoryid	- repositoyr identifier to filter list of countries
	*/
	function get_active_countries($repositoryid=NULL)
	{
		$this->db->select('cid,countries.name as nation, count(cid) as surveys_found');
		$this->db->join('surveys', 'surveys.id=survey_countries.sid','inner');
		$this->db->join('countries', 'countries.countryid=survey_countries.cid','inner');
		$this->db->order_by('countries.name','ASC');
		$this->db->group_by('cid,countries.name','ASC');
		$this->db->where('surveys.published',1);
		$this->db->where('survey_countries.cid >',0);
		if($repositoryid!=NULL)
		{
			$this->db->join('survey_repos', 'surveys.id=survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}
		
		$query=$this->db->get('survey_countries');
		
		if(!$query)
		{
			return FALSE;
		}
		
		$rows=$query->result_array();
		
		$countries=array();
		foreach($rows as $country)
		{
			$countries[$country['cid']]=$country;
		}
		
		return $countries;
	}
	
	
	/**
	* Returns a list of countries filtered by topics with min and max year
	*
	*/	
	function filter_by_collections($topic_array,$min_year=0,$max_year=0)
	{
		$topics=implode(",", $topic_array);
		
		$output=NULL;
		
		if ($min_year>0 and $max_year >0)
		{
			//get list of filtered countries
			$sql=sprintf('select nation from terms
				  inner join survey_collections on terms.tid= survey_collections.tid
				  inner join surveys on surveys.id =survey_collections.sid
				  inner join survey_years on surveys.id=survey_years.sid
				  where terms.tid in (%s)
  		        	and ((data_coll_year between %d and %d) or (data_coll_year=0) )
				  group by nation',$topics,$min_year,$max_year);
		}
		else
		{
			//get list of filtered countries
			$sql=sprintf('select nation from terms
				  inner join survey_collections on terms.tid= survey_collections.tid
				  inner join surveys on surveys.id =survey_collections.sid
				  where terms.tid in (%s)
				  group by nation',$topics);		
		}
		
		//echo $sql;
		$countries=$this->db->query($sql)->result_array();
		
		if ($countries)
		{
			foreach($countries as $country)
			{
				$output['countries'][]=$country['nation'];
			}			
		}
		
		$output['min_year']=0;
		$output['max_year']=0;

		return $output;		
	}	
	
	/**
	* Returns an array of available DA types for current repo
	*
	*/
	function get_active_data_types($repositoryid)
	{
		if ($repositoryid=='central' || trim($repositoryid)=='')
		{
			$this->db->select('surveys.formid,forms.model');
			$this->db->join('forms','forms.formid=surveys.formid','inner');	
			$this->db->where('surveys.published',1);
			$this->db->group_by('surveys.formid, forms.model');
			$query=$this->db->get('surveys');
		}
		else
		{
			$this->db->select('surveys.formid,forms.model');
			$this->db->join('forms','forms.formid=surveys.formid','inner');	
			$this->db->join('survey_repos','survey_repos.sid=surveys.id','inner');	
			$this->db->where('surveys.published',1);
			$this->db->where('survey_repos.repositoryid',$repositoryid);
			$this->db->group_by('surveys.formid, forms.model');
			$query=$this->db->get('surveys');
		}
		
		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->result_array();

		if (!$result)
		{
			return FALSE;
		}
		
		$types=array();
		foreach($result as $row)
		{
			$types[(string)$row['formid']]=$row['model'];
		}
		
		return $types;
	}
	
	
	/**
	* Returns a list of Centers 
	*
	**/
	function get_active_centers($repositoryid)
	{
		if ($repositoryid=='central' || trim($repositoryid)=='')
		{
			$sql='select survey_centers.id,center_name from surveys
					inner join survey_centers on survey_centers.sid=surveys.id
					where surveys.published=1
				group by survey_centers.id, survey_centers.center_name;';
		}
		else
		{
			$sql='select survey_centers.id,center_name from surveys
					inner join survey_centers on survey_centers.sid=surveys.id
					inner join survey_repos on surveys.id=survey_repos.sid
					where survey_repos.repositoryid='.$this->db->escape($repositoryid).'
					and surveys.published=1
				group by survey_centers.id, survey_centers.center_name;';
		}

		$result=$this->db->query($sql)->result_array();

		if (!$result)
		{
			return FALSE;
		}
		
		$centers=array();
		foreach($result as $row)
		{
			$centers[(string)$row['id']]=$row['center_name'];
		}
		
		return $centers;
	}
	
	
	/**
	* 
	* Returns a list of collections
	*/	
	function get_collections($repositoryid)
	{
		$this->db->select('sc.tid as tid, collections.title as title, count(collections.id) as found');
		$this->db->join('survey_collections sc', 'sc.tid=collections.id','inner');
		$this->db->join('surveys s', 's.id=sc.sid','inner');
		$this->db->order_by('collections.title','ASC');
		
		//filter by repository
		if (trim($repositoryid)!=='' && $repositoryid!='central')
		{
			$this->db->join('survey_repos sr', 'sr.sid=sc.sid','inner');
			$this->db->where('sr.repositoryid',$repositoryid);	
		}
		
		$this->db->group_by('sc.tid, collections.title');
		$this->db->where('s.published',1);
		
		$result=$this->db->get('collections')->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[$row['tid']]=$row['title'] .' ('.$row['found'].')';
		}
		
		return $output;
	}
	
}
?>