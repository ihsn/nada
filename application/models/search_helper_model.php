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
		
		//get min year
		/*$sql=sprintf('select min(data_coll_year) as min_year from survey_years y
					  inner join survey_topics t on t.sid=y.sid
					  where t.tid in (%s) and data_coll_year>0;',$topics);		

		$min_query=$this->db->query($sql)->row_array();
		
		if ($min_query)
		{
			$output['min_year']=$min_query['min_year'];
		}
		
		$sql=sprintf('select max(data_coll_year) as max_year from survey_years y
					  inner join survey_topics t on t.sid=y.sid
					  where t.tid in (%s);',$topics);		

		
		$max_query=$this->db->query($sql)->row_array();
		
		if ($max_query)
		{
			$output['max_year']=$max_query['max_year'];
		}

		//set min/max years to 0 if not found in db
		if (!is_numeric($output['min_year']) || !is_numeric($output['max_year']) )
		{
			$output['min_year']=0;
			$output['max_year']=0;
		}*/
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
		$this->db->select_min('data_coll_start','min_year');
		$this->db->where('data_coll_start > 0'); 
		$result=$this->db->get('surveys')->row_array();
		
		if ($result)
		{
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
		$this->db->select_max('data_coll_end','max_year');
		$this->db->where('data_coll_end > 0'); 
		$result=$this->db->get('surveys')->row_array();
		if ($result)
		{
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
		$sql='select data_coll_start from surveys
				where data_coll_start>0
				group by data_coll_start;';
		$result=$this->db->query($sql)->result_array();
		
		$years['from']=array('--'=>'--');
		
		if ($result)
		{
			foreach($result as $row)
			{
				$years['from'][$row['data_coll_start']]=$row['data_coll_start'];
			}
		}

		//get end years
		$sql='select data_coll_end from surveys
				where data_coll_end>0
				group by data_coll_end;';
				
		$result=$this->db->query($sql)->result_array();
		
		$years['to']=array('--'=>'--');
		if ($result)
		{
			foreach($result as $row)
			{
				$years['to'][$row['data_coll_end']]=$row['data_coll_end'];
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
		$sql='select nation,count(nation) as surveys_found 
				from '.$this->db->dbprefix.'surveys';
		if ($repositoryid!==NULL && trim($repositoryid)!='' && $repositoryid!='central')
		{
			$sql.='	inner join survey_repos sr on sr.sid=surveys.id 
					where sr.repositoryid='.$this->db->escape($repositoryid);
		}		
		$sql.=' group by nation';
		return $this->db->query($sql)->result_array();		
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
}
?>