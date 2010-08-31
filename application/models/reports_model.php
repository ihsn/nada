<?php
class Reports_model extends Model {
 
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
					s.surveyid as surveyid, 
					s.titl as titl,
					count(*) as visits					 
			FROM sitelogs n
				  inner join surveys s on n. surveyid=s.id
			where logtype=\'survey\'';
			
		if (is_numeric($start) && is_numeric($end) ) 
		{
			$sql.='	and (logtime between '.$start.' and '.$end.')';
		}

		$sql.='	group by s.surveyid, s.titl, s.id';
		$sql.= ' order by visits desc';			

		$query=$this->db->query($sql)->result_array();
		return $query;		
	}

	function get_survey_detailed_x($start,$end)
	{
			$sql='SELECT 
						s.id as id,
						n.section as section,
						s.surveyid as survey, 
						s.titl as title, 
						count(*) as visits 
				FROM sitelogs n
					  inner join surveys s on n. surveyid=s.id
				where logtype=\'survey\'';
				
			if (is_numeric($start) && is_numeric($end) ) 
			{
				$sql.='	and (logtime between '.$start.' and '.$end.')';
			}						
			$sql.='	group by s.surveyid, s.id, s.titl, n.section';
						
			return $this->db->query($sql)->result_array();		
	}	

	//TODO:REMOVE	
	function get_survey_detailed($start,$end)
	{
		//get a list of popular surveys
		$popular_surveys=$this->get_survey_summary($start, $end);
			
		$result=array();			
		foreach($popular_surveys as $survey)
		{
			$sql='SELECT 
						s.id as id,
						n.section as section,
						s.surveyid as survey, 
						s.titl as title, 
						count(*) as visits 
				FROM sitelogs n
					  inner join surveys s on n. surveyid=s.id
				where logtype=\'survey\'';
				
			if (is_numeric($start) && is_numeric($end) ) {
				$sql.='	and (logtime between '.$start.' and '.$end.')';
				$sql.=' and s.id='.$survey['id'];
			}			
			$sql.='	group by s.surveyid, s.id, s.titl, n.section';
			
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
		/*
		#downloads detailed report
		select
			sitelogs.id,
			logtime,
			ip,
			sitelogs.surveyid,
			users.username,
			users.email,
			meta.company,
			meta.country,
			keyword as downloadid,
			surveys.titl as survey_title,
			resources.title as download_title,
			resources.filename as download_filename
	
		from sitelogs
		inner join surveys on surveys.id =sitelogs.surveyid
		inner join resources on resources.resource_id=sitelogs.keyword
		left join users on users.email = sitelogs.username
		left join meta on users.id=meta.id

		where sitelogs.section like '%download%';
		*/

		$sql='select
					sitelogs.id,
					logtime,
					ip,
					sitelogs.surveyid,
					users.username,
					users.email,
					meta.company,
					meta.country,
					keyword as downloadid,
					surveys.titl as survey_title,
					resources.title as download_title,
					resources.filename as download_filename,
					forms.model as form_type
			
				from sitelogs
				inner join surveys on surveys.id =sitelogs.surveyid
				inner join resources on resources.resource_id=sitelogs.keyword
				left join users on users.email = sitelogs.username
				left join meta on users.id=meta.user_id
				left join forms on forms.formid=surveys.formid

			where sitelogs.section like \'%download%\'';

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
					l.id,
					l.userid,
					l.surveyid,
					l.status,
					l.comments,
					l.created,
					l.updated,
					l.updatedby,
					meta.company,
					meta.country,
					u.username,
					s.titl as survey_title
			
			from lic_requests l
			inner join users u on u.id=l.userid
			inner join meta on u.id=meta.user_id
			inner join surveys s on s.id =l.surveyid';

		if (is_numeric($start) && is_numeric($end) ) {
			$sql.='	where (created between '.$start.' and '.$end.')';
		}
		
		$sql.=" order by l.surveyid ASC, l.updated DESC";

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
					s.titl as survey_title,
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
				titl,
				varcount,
				dirpath,
				s.formid,
				data_coll_start,
				data_coll_end,
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
				where dctype like \'%[dat/micro]%\' or dctype like \'%[dat]%\'
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
	
}
?>