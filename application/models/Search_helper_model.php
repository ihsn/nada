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
	* Return min/max years 
	*
	*/
	function get_min_max_years($published=1, $study_type=null)
	{
		$this->db->select_min('year_start','min_year');
		$this->db->select_max('year_end','max_year');
		$this->db->where('year_start > 0'); 

		if($published==1 || $published==0){
			$this->db->where('published',$published); 
		}

		if($study_type){
			$this->db->where('surveys.type',$study_type);
		}

		$result=$this->db->get('surveys')->row_array();

		if ($result){
			return $result;
		}
		
		return array(
			'min_year'=>0,
			'max_year'=>0
		);
	}

		
	/**
	* Get start and End data collection years
	*
	*/
	/*function get_collection_years()
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
	}*/

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
	function get_active_countries($repositoryid=NULL, $data_type=NULL,$filter_values=array() )
	{
		$this->db->select('cid as id,countries.name as title, count(cid) as found');
		$this->db->join('surveys', 'surveys.id=survey_countries.sid','inner');
		$this->db->join('countries', 'countries.countryid=survey_countries.cid','inner');
		$this->db->order_by('countries.name','ASC');
		$this->db->group_by('cid,countries.name','ASC');
		$this->db->where('surveys.published',1);
		$this->db->where('survey_countries.cid >',0);

		if($data_type!=NULL){
			$this->db->where('surveys.type',$data_type);
			if($filter_values!=NULL){
				$this->db->or_where_in('cid',$filter_values);
			}			
		}

		if($repositoryid!=NULL){
			//$this->db->join('survey_repos', 'surveys.id=survey_repos.sid','inner');
			//$this->db->where('survey_repos.repositoryid',$repositoryid);

			$subquery= sprintf('(surveys.repositoryid= %s OR sr.repositoryid = %s)',
			$this->db->escape($repositoryid),
			$this->db->escape($repositoryid));

			$this->db->join('survey_repos sr', 'sr.sid=surveys.id','left');
			$this->db->where($subquery,null,false);
		}
		
		$query=$this->db->get('survey_countries');
		
		if(!$query){
			return FALSE;
		}
		
		$rows=$query->result_array();
		
		$countries=array();
		foreach($rows as $country)
		{
			$countries[$country['id']]=$country;
		}
		
		return $countries;
	}


	public function get_active_repositories($study_type=NULL,$filter_values=array())
	{
		$this->db->select('r.repositoryid as id,r.pid,r.title,r.repositoryid,count(sr.sid) as found, rsections.title as group_name');
		$this->db->join('survey_repos sr', 'r.repositoryid= sr.repositoryid','INNER');
		$this->db->join('repository_sections rsections', 'r.section= rsections.id','left');
		$this->db->join('surveys', 'sr.sid= surveys.id','INNER');
		$this->db->where('r.ispublished',1);
		$this->db->where('surveys.published',1);
		$this->db->group_by('r.id,r.pid,r.title,r.repositoryid,r.weight,rsections.title');
		$this->db->order_by('r.weight');
		
		if($study_type!=NULL){
			$this->db->where('surveys.type',$study_type);
			if($filter_values!=NULL){
				$this->db->or_where_in('r.repositoryid',$filter_values);
			}			
		}
		
		$query=$this->db->get('repositories r');
		
		if (!$query){
			return FALSE;
		}
		
		$rows=$query->result_array();
		
		$repositories=array();
		foreach($rows as $repository)
		{
			$repositories[$repository['id']]=$repository;
		}
		
		return $repositories;
	}

	
	/**
	 * 
	 * 
	 * Get tags
	 * 
	 */
	function get_active_tags($repositoryid=NULL,$data_type=NULL,$filter_values=array())
	{
			$this->db->select('tag as id, tag as title, count(tag) as found');
			$this->db->join('surveys', 'surveys.id=survey_tags.sid','inner');
			$this->db->order_by('survey_tags.tag','DESC');
			$this->db->group_by('survey_tags.tag');
			$this->db->where('surveys.published',1);
			
			if($repositoryid!=NULL){
				//$this->db->join('survey_repos', 'surveys.id=survey_repos.sid','inner');
				//$this->db->where('survey_repos.repositoryid',$repositoryid);

				$subquery= sprintf('(surveys.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

				$this->db->join('survey_repos sr', 'sr.sid=surveys.id','left');
				$this->db->where($subquery,null,false);
			}

			if($data_type!=NULL){
				$this->db->where('surveys.type',$data_type);
				if($filter_values!=NULL){
					$this->db->or_where_in('survey_tags.tag',$filter_values);
				}
			}
			
			$query=$this->db->get('survey_tags');
			
			if(!$query){
				return FALSE;
			}
			
			$rows=$query->result_array();
			
			$tags=array();
			foreach($rows as $tag)
			{
				$tags[$tag['id']]=$tag;
			}
			
			return $tags;
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
	 * 
	 * 
	 * Returns an array of available DA types for current repo
	 *
	 */
	function get_active_data_types($repositoryid='', $data_type='',$filter_values=array())
	{
		$this->db->select('surveys.formid as id,forms.model as code, forms.fname as title');
		$this->db->join('forms','forms.formid=surveys.formid','inner');			
		$this->db->where('surveys.published',1);

		if (trim($repositoryid)!=='' && $repositoryid!='central'){
			$subquery= sprintf('(surveys.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

			$this->db->join('survey_repos sr', 'sr.sid=surveys.id','left');
			$this->db->where($subquery,null,false);
			//$this->db->join('survey_repos','survey_repos.sid=surveys.id','inner');	
			//$this->db->where('survey_repos.repositoryid',$repositoryid);
		}

		if($data_type!=''){
			$this->db->where('surveys.type',$data_type);
			if($filter_values!=NULL){
				$this->db->or_where_in('surveys.formid',$filter_values);
			}
		}

		$this->db->group_by('surveys.formid, forms.model, forms.fname');
		$query=$this->db->get('surveys');
	
		
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
			$row['title']=t('legend_data_'.$row['code']);
			$types[(string)$row['id']]=$row;
		}
		
		return $types;
	}



	/**
	 * 
	 * 
	 * List of data classifications
	 *
	 */
	function get_active_data_classifications($repositoryid=null)
	{
		$this->config->load('data_access');
		$data_classifications_enabled=(bool)$this->config->item("data_classifications_enabled");

		if($data_classifications_enabled==false){
			return false;
		}
		
		$this->db->select('surveys.data_class_id as id, data_classifications.code, data_classifications.title, count(surveys.data_class_id) as found');
		$this->db->join('data_classifications','data_classifications.id=surveys.data_class_id','inner');	
		$this->db->where('surveys.published',1);
		$this->db->group_by('surveys.data_class_id, data_classifications.code, data_classifications.title');

		if (trim($repositoryid)!=='' && $repositoryid!='central'){
			$subquery= sprintf('(surveys.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

			$this->db->join('survey_repos sr', 'sr.sid=surveys.id','left');
			$this->db->where($subquery,null,false);
			
			//$this->db->join('survey_repos','survey_repos.sid=surveys.id','inner');	
			//$this->db->where('survey_repos.repositoryid',$repositoryid);
		}

		$result=$this->db->get('surveys')->result_array();

		if (!$result){
			return FALSE;
		}
		
		$types=array();
		foreach($result as $row){
			$row['title']=t('data_class_'.$row['code']);
			$types[(string)$row['id']]=$row;
		}
		
		return $types;
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
			$subquery= sprintf('(s.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

			$this->db->join('survey_repos sr', 'sr.sid=s.id','inner');
			$this->db->where($subquery,null,false);
			//$this->db->join('survey_repos sr', 'sr.sid=sc.sid','inner');
			//$this->db->where('sr.repositoryid',$repositoryid);	
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



	/**
	* 
	* Returns a list of dataset types
	*/	
	function get_dataset_types($repositoryid=null)
	{
			$this->db->select('survey_types.code,survey_types.title, survey_types.weight, count(*) as found');
			$this->db->join('surveys s', 's.type=survey_types.code','inner');
			$this->db->where('s.published',1);
			$this->db->order_by('survey_types.weight','desc');
			$this->db->group_by('survey_types.code, survey_types.title, survey_types.weight');

			//filter by repository
			if (trim($repositoryid)!=='' && $repositoryid!='central'){				
				$subquery= sprintf('(s.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

				$this->db->join('survey_repos sr', 'sr.sid=s.id','left');
				$this->db->where($subquery,null,false);

				//$this->db->join('survey_repos sr', 'sr.sid=s.id','inner');
				//$this->db->where('sr.repositoryid',$repositoryid);
			}

			$result=$this->db->get('survey_types')->result_array();
			
			$output=array();
			foreach($result as $row){
				$output[$row['code']]=$row;
			}
			
			return $output;
	}


	/**
	* 
	* Returns a list of collections
	*/	
	function get_repositories_list($published=1)
	{
		$this->db->select('repositoryid,title');
		
		if($published){
			$this->db->where('ispublished',1);
		}
		
		$result=$this->db->get('repositories')->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['repositoryid']]=$row['title'];
		}
		
		return $output;
	}


	/**
	* 
	* Returns a list of collections
	*
	* @countries - array of country integer codes
	*
	*/	
	function get_countries_list($countries)
	{
		$countries_list=array();

		if(is_array($countries) && count($countries)>0){
			
			foreach($countries as $country_id){
				if(is_numeric($country_id)){
					$countries_list[]=$country_id;
				}
			}
			if(count($countries_list)==0){
				return false;
			}
		}
		
		$this->db->select('countryid,name');
		$this->db->where_in('countryid',$countries);
		$result=$this->db->get('countries')->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['countryid']]=$row['name'];
		}
		
		return $output;
	}

	/**
	* 
	* Returns a list of regions
	*
	* @countries - array of region integer codes
	*
	*/	
	function get_regions_list($regions=null)
	{
		$this->db->select('id,title');
		
		if(is_array($regions) && count($regions)>0){
			$this->db->where_in('id',$regions);
		}
		
		$result=$this->db->get('regions')->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['id']]=$row['title'];
		}
		
		return $output;
	}


	/**
	*
	* Return an array of collection by study
	*
	**/
	function related_collections($sid_arr)
	{
		if (empty($sid_arr)){
			return false;
		}

		$this->db->select('repositories.repositoryid,sid,title');
		$this->db->join('repositories', 'repositories.repositoryid=survey_repos.repositoryid','inner');
		$this->db->where('isadmin',0);
		$this->db->where_in('sid',$sid_arr);
		$query=$this->db->get('survey_repos');
		
		if (!$query){
			return array();
		}

		$result=$query->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['sid']][]=$row;
		}
		
		return $output;
	}


	public function get_active_regions($repositoryid=NULL, $study_type=NULL,$filter_values=array())
	{
		//parent regions
		$this->db->select('parent_regions.title as group_name,regions.id,regions.title,regions.weight,count(distinct surveys.id) as found');
		$this->db->join('regions', 'parent_regions.id= regions.pid','INNER');

		$this->db->join('region_countries', 'region_countries.region_id=regions.id','INNER');
		$this->db->join('countries', 'countries.countryid=region_countries.country_id','INNER');
		$this->db->join('survey_countries', 'survey_countries.cid=countries.countryid','INNER');
		$this->db->join('surveys', 'surveys.id=survey_countries.sid','INNER');		

		$this->db->order_by('parent_regions.id,regions.weight');
		$this->db->group_by('parent_regions.title, regions.id, regions.title, regions.weight');
		$this->db->where('surveys.published',1);

		//filter by repository
		if (trim($repositoryid)!=='' && $repositoryid!='central')
		{
			$subquery= sprintf('(surveys.repositoryid= %s OR sr.repositoryid = %s)',
				$this->db->escape($repositoryid),
				$this->db->escape($repositoryid));

			$this->db->join('survey_repos sr', 'sr.sid=surveys.id','inner');
			$this->db->where($subquery,null,false);
		}

		if($study_type!=NULL){
			$this->db->where('surveys.type',$study_type);
			if($filter_values!=NULL){
				$this->db->or_where_in('regions.id',$filter_values);
			}			
		}

		$query=$this->db->get('regions as parent_regions');
		
		if (!$query){
			return FALSE;
		}
		
		$rows=$query->result_array();
		
		$regions=array();
		foreach($rows as $row)
		{
			$regions[$row['id']]=$row;
		}
		
		return $regions;
	}
	
	
}//end class
