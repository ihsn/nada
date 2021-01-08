<?php
class Reports_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

	/*
	* Returns top keywords
	*
	*/
	function get_top_keywords($from,$to)
	{
		$sql='SELECT keyword, count(*) as visits 
			 FROM sitelogs n
			 where logtype=\'search\' and keyword !=\'\'	';
		
		//add from/to
		if (is_numeric($from) && is_numeric($to) ) 
		{
			$sql.='	and (logtime between '.$this->db->escape($from).' and '.$this->db->escape($to).')';
		}
		
		$sql.= ' group by keyword';
		$sql.='	order by visits desc';

	   $query=$this->db->query($sql)->result_array();		   
		return $query;		
	}
	
	function get_survey_summary($start, $end)
	{
		$sql='SELECT 
					s.id as id,
					s.idno as idno, 
					s.title as title,
					count(*) as visits					 
			FROM sitelogs n
				  inner join surveys s on n. surveyid=s.id
			where logtype=\'survey\'';
			
		if (is_numeric($start) && is_numeric($end) ) 
		{
			$sql.='	and (logtime between '.$start.' and '.$end.')';
		}

		$sql.='	group by s.idno, s.title, s.id';
		$sql.= ' order by visits desc';			

		$query=$this->db->query($sql)->result_array();
		return $query;		
	}

	//get all surveys page view hits per section
	function get_survey_detailed_all($start,$end)
	{
			$sql='SELECT 
						s.id as id,
						s.idno, 
						s.title as title, 
						n.section as section,						
						s.nation as country,
						s.year_start as year,
						count(*) as visits 
				FROM sitelogs n
					  inner join surveys s on n. surveyid=s.id
				where logtype=\'survey\'';
				
			if (is_numeric($start) && is_numeric($end) ) 
			{
				$sql.='	and (logtime between '.$start.' and '.$end.')';
			}						
			$sql.='	group by s.idno, s.id, s.title, n.section';
						
			return $this->db->query($sql)->result_array();		
	}	

	
	function get_survey_detailed($start,$end)
	{
		//get a list of popular surveys
		$popular_surveys=$this->get_survey_summary($start, $end);
			
		$result=array();			
		foreach($popular_surveys as $survey)
		{
			$sql='SELECT 
						s.id as id,
						s.idno as idno,
						s.title as title,
						n.section as section,
						s.nation as country,
						s.year_start as year,
						count(*) as visits 
				FROM sitelogs n
					  inner join surveys s on n. surveyid=s.id
				where logtype=\'survey\'';
				
			$sql.=' and s.id='.$survey['id'];	
				
			if (is_numeric($start) && is_numeric($end) ) {
				$sql.='	and (logtime between '.$start.' and '.$end.')';
				
			}
			
			$sql.='	group by s.idno, s.id, s.title, n.section';
			
			$rows=$this->db->query($sql)->result_array();		
			$result[]=$rows;	
		}//end-foreach
		
		return $result;
	}	

	/**
	*
	* Returns invidual file download details with Survey, user, file details
	*
	**/
	function downloads_detailed($start=NULL,$end=NULL)
	{
		
		$sql='select
					sitelogs.id,
					logtime,
					ip,
					sitelogs.surveyid,
					users.username,
					users.email,
					meta.company,
					meta.country,
					surveys.title as survey_title,
					keyword as download_filename,
					forms.model as form_type
			
				from sitelogs
				inner join surveys on surveys.id =sitelogs.surveyid
				left join users on users.email = sitelogs.username
				left join meta on users.id=meta.user_id
				left join forms on forms.formid=surveys.formid

			where sitelogs.logtype like \'download\'';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	and (logtime between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by surveys.id ASC";

		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}
	
	/**
	*
	* Licensed survey requests
	*
	**/
	function licensed_requests($start=NULL,$end=NULL)
	{
		$sql='select
					l.request_title,
					l.id,
					l.userid,
					l.status,
					l.comments,
					l.created,
					l.updated,
					l.updatedby,
					meta.company,
					meta.country,
					u.username
			from lic_requests l
			inner join users u on u.id=l.userid
			inner join meta on u.id=meta.user_id';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	where (created between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by l.updated DESC";

		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}
	
	
	
	/**
	*
	* public survey requests
	*
	**/
	function public_requests($start=NULL,$end=NULL)
	{
		$sql='select
					p.*,
					s.title as survey_title,
					u.username,
					u.email,
					meta.company,
					meta.country			
			
				from public_requests p
				inner join surveys s on p.surveyid=s.id
				inner join users u on u.id=p.userid
				inner join meta on u.id=meta.user_id';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	where (posted between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by p.surveyid, p.posted DESC";

		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}


	/**
	*
	* Survey direct downloads
	*
	* NOTE: not in use
	* TODO:// remove
	**/
	function direct_downloads($start=NULL,$end=NULL)
	{
		$sql='select
					l.*,
					u.username,
					u.email,
					meta.company,
					meta.country,
					resources.title as download_title,
					resources.filename as download_filename			
			
				from sitelogs l
				inner join surveys s on l.surveyid=s.id
				inner join users u on u.email=l.username
				inner join meta on u.id=meta.user_id
				inner join resources on resources.resource_id=l.keyword
		
				where l.section like \'%direct-download%\'';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	where (logtime between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by l.surveyid, l.logtime DESC";

		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}


	function survey_summary_statistics()
	{
		$sql='select
				s.id,
				title,
				varcount,
				dirpath,
				s.formid,
				year_start,
				year_end,
				f.model as form_type,
				s.changed
		
			from surveys s
			left join forms f on s.formid=f.formid
			order by s.changed DESC	';
		
		$query=$this->db->query($sql);
		
		if (!$query)
		{
			return FALSE;
		}
	
		$result['rows']=$query->result_array();
		
		//survey array with citations
		$sql='select sid, count(sid) as total from survey_citations group by sid;';
		$query=$this->db->query($sql);
		
		if ($query)
		{			
			$result['citations']=$this->_rows_to_array($query->result_array());
		}
	
		//data files
		$sql='select survey_id as sid, count(survey_id) as total 
				from resources 
				where dctype like \'%dat/micro]%\' or dctype like \'%dat]%\'
				group by sid;';
				
		$query=$this->db->query($sql);
		
		if ($query)
		{			
			$result['data']=$this->_rows_to_array($query->result_array());
		}

		//questionnaires
		$sql='select survey_id as sid, count(survey_id) as total 
				from resources 
				where dctype like \'%[doc/qst]%\' 
				group by sid;';
				
		$query=$this->db->query($sql);
		
		if ($query)
		{			
			$result['questionnaires']=$this->_rows_to_array($query->result_array());
		}
		
		//reports
		$sql='select survey_id as sid, count(survey_id) as total 
				from resources 
				where dctype like \'%[doc/rep]%\' 
				group by sid;';
				
		$query=$this->db->query($sql);
		
		if ($query)
		{			
			$result['reports']=$this->_rows_to_array($query->result_array());
		}

		return $result;		
	}
	
	function _rows_to_array($rows)
	{	
		if (!is_array($rows))
		{
			return FALSE;
		}	
	
		$output=array();
		
		foreach($rows as $row)
		{
			$output[$row['sid']]=$row['total'];
		}
		
		return $output;
	}

	
	/**
	*
	* User statistics
	*
	**/
	function user_stats($start=NULL,$end=NULL)
	{
		$sql='select
				  *
				  from users u
				  inner join meta m on u.id=m.user_id';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	where (created_on between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by first_name asc, created_on DESC";

		$query=$this->db->query($sql);
		
		if ($query)
		{
			return $query->result_array();
		}
		
		return FALSE;
	}
	
	
	
	/**
	*
	* Find all the Public/Licensed studies that have no microdata attached
	*
	**/
	function study_data_access()
	{
		//get a list of all surveys having the DA type of Public use or Licensed
		$this->db->select("id");
		$this->db->where_in("formid",array(2,3));
		$surveys=$this->db->get("surveys")->result_array();
		//echo $this->db->last_query();
		
		//surveys with no data
		$output=array();
		
		//test each survey if they have got atleast one microdata file attached
		foreach($surveys as $survey)
		{
			$result=NULL;
			/*$this->db->select("count(survey_id) as rows_found");
			$this->db->where("survey_id",$survey["id"]);
			$this->db->like("dctype","dat]");
			$this->db->like("dctype","dat/micro]");*/
			//$result=$this->db->get("resources")->row_array();
			
			//checks if a survey has microdata attached
			$sql=sprintf('select count(survey_id) as rows_found from resources 
							where survey_id=%s
							AND (dctype like %s OR dctype like %s)',
							$this->db->escape($survey['id']),
							"'%dat]%'",
							"'%dat/micro]%'");
			$result=$this->db->query($sql)->row_array();
						
			//no data attached
			if ($result['rows_found'] ==0)
			{
				$output[$survey['id']]=$result['rows_found'];
			}
			
		}

		//return survey details with no data attached
		if (count($output)>0)
		{
			$this->db->select("id,titl,repositoryid,nation");
			$this->db->where_in("id",array_keys($output));
			return $this->db->get("surveys")->result_array();
		}
		
		return FALSE;
	}
	
	
	/**
	*
	* Find external resources with broken links e.g. files missing
	*
	* $dctypes	array
	* $da_types array	data access types e.g. array(2,3)
	**/
	function broken_resources($dctypes,$da_types=array(2,3))
	{
		$this->load->model("Catalog_model");
		$this->load->helper("file_helper");
		
		$sql='select surveys.dirpath,resources.* from resources 	
			inner join surveys on surveys.id=resources.survey_id';
		
		$types=array();
		
		foreach($dctypes as $dctype)
		{	
			$types[]='dctype like '.$this->db->escape($dctype);
		}	

		$custom_where='('.implode(" OR ",$types).')';
		//$custom_where.=' AND surveys.form_id in '.implode(',',$da_types);
		
		
		$this->db->select("surveys.id,resources.resource_id,filename");
		$this->db->join('resources', 'surveys.id= resources.survey_id','inner');		
		$this->db->where($custom_where);
		$this->db->where_in("surveys.formid",$da_types);
		$resources=$this->db->get("surveys")->result_array();
		//echo $this->db->last_query();
		
		
		//build an array of broken resources
		$broken_links=array();
		$broken_links_found=0;
		foreach($resources as $resource)
		{
			//skip row if URL
			if (is_url($resource['filename']))
			{
				continue;
			}
			
			$path=NULL;
			$path=$this->Catalog_model->get_survey_path_full($resource['id']);
			
			if (!$path)
			{
				$broken_links[]=$resource['resource_id'];
				$broken_links_found++;
			}
			else if (!file_exists($path.'/'.$resource['filename']))
			{
				$broken_links[]=$resource['resource_id'];
				$broken_links_found++;
			}
		
			//limit number of broken resources
			if ($broken_links_found>100)
			{
				break;
			}
		}
		
		
		if (count($broken_links)==0)
		{
			return FALSE;
		}		
		
		//return broken resources details
		$this->db->select("survey_id,resource_id,filename,title");
		$this->db->where_in("resource_id",$broken_links);
		$broken_rows=$this->db->get("resources")->result_array();
		//echo $this->db->last_query();
		
		return $broken_rows;
	}
}
?>