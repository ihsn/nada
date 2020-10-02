<?php
class Licensed_model extends CI_Model {

	//form fields
	var $db_fields=array(
					'request_title',
					'org_rec',
					'org_type', 
					'address', 
					'tel', 
					'fax', 
					'datause', 
					'outputs',
					'compdate', 
					'datamatching', 
					'mergedatasets', 
					'team', 
					'dataset_access',
					'additional_info'
					);
	
    public function __construct()
    {
        parent::__construct();
		$this->load->config('ion_auth');
		$this->tables  = $this->config->item('tables');
		//$this->output->enable_profiler(TRUE);
    }
	
    

	/**
     * Returns request by user
     * 
     * 
     * @param $user_id
     * @return array
     */
	function get_user_requests($user_id,$active_only=FALSE)
	{
		$this->db->select('lic_requests.id,lic_requests.request_title,lic_requests.created,lic_requests.status,lic_requests.expiry_date');
		$this->db->from('lic_requests');	
		$this->db->order_by('lic_requests.created','DESC');
		$this->db->where('userid',$user_id);
		$result=$this->db->get()->result_array();
		
		if($active_only===TRUE)
		{
			//remove expired requests
			foreach($result as $key=>$row)
			{
				if ((int)$row['expiry_date']>0 && $row['expiry_date']<=date("U"))
				{
					unset($result[$key]);
				}
			}
		}
		
		return $result;		
	}

	/**
	*
	*Find request for a user by study
	**/
	function get_requests_by_study($sid,$user_id,$active_only=FALSE)
	{
		$this->db->select('lic_requests.id,lic_requests.request_title,surveys.title,lic_requests.created,lic_requests.status,lic_requests.expiry_date');
		$this->db->from('lic_requests');	
		$this->db->join('survey_lic_requests', 'lic_requests.id = survey_lic_requests.request_id','inner');
		$this->db->join('surveys', 'surveys.id = survey_lic_requests.sid','inner');
		$this->db->where('userid',$user_id);
		$this->db->where('surveys.id',$sid);
		$result=$this->db->get()->result_array();
		
		if($active_only===TRUE)
		{
			//remove expired requests
			foreach($result as $key=>$row)
			{
				if ((int)$row['expiry_date']>0 && $row['expiry_date']<=date("U"))
				{
					unset($result[$key]);
				}
			}
		}
		
		return $result;		
	}


    
	/**
	 * 
	 * @param $user_id
	 * @param $survey_id
	 * @return array of request row
	 */
	function get_request_status($user_id,$survey_id)
	{
		$this->db->select('lic_requests.*,surveys.title');		
		$this->db->from('lic_requests');		
		$this->db->join('surveys', 'surveys.id = lic_requests.surveyid','inner');
		$this->db->where('surveys.id',$survey_id);		
		$this->db->where('userid',$user_id);		

		$result = $this->db->get()->row_array();
		return $result;
	}
	
    /**
     * Insert Licensed survey request
     * 
     * 
     * @param $user_id
     * @param $options	array
     * @return integer - insert id
     */
    function insert_request($user_id,$options)
	{
		$data= array(
			'userid' => $user_id ,
			'created' => date("U"),
			'updated' => date("U"),
			'status'=>'PENDING',
			'locked'=>1
        );

		foreach($options as $key=>$value)
		{
			if (in_array($key,$this->db_fields))
			{
				$data[$key]=$value;
			}
		}
		
		$this->db->trans_start();
		
		//add request		
		$result=$this->db->insert('lic_requests', $data); 
		
		$request_id=$this->db->insert_id();
		
		foreach($options['sid'] as $sid)
		{
			$options=array(
				'request_id'=>$request_id,
				'sid'=>$sid
			);
		
			$this->db->insert('survey_lic_requests',$options);
		}	
		
		$this->db->trans_complete();
		
		if ($this->db->trans_status() === FALSE)
		{
			log_message('info',"FAILED to save request for [Licensed dataset=$request_id] by user $user_id ");
			return FALSE;
		}

		return $request_id;
	}


	
	function update_request_status($request_id,$username,$status,$comments='',$ip_limit='')
	{
		$data= array(
               'status' => $status ,
               'comments' => $comments,
			   'ip_limit'=> $ip_limit,
			   'updated' => date("U"),
				'updatedby'=>$username
        );

		$this->db->where('id', $request_id);
		$result=$this->db->update('lic_requests', $data); 
		log_message('info',"Request [$request_id] updated by [user]");
		
		if ($result)
		{
			return TRUE;
		}	
		else
		{
			log_message('info',"FAILED to update request $request_id");
		}		
		return FALSE;
	}


	/**
	*
	* Get the max expiry date from licensed files 
	**/
	function get_files_max_expiry($request_id)
	{
		$this->db->select('max(expiry) as max_expiry',FALSE);
		$this->db->where('requestid',$request_id);
		$result=$this->db->get('lic_file_downloads')->row_array();
		
		return (int)$result['max_expiry'];
	}

	/**
	*
	* Update request expiry
	**/
	function update_request_expiry($request_id)
	{
		$max_expiry=$this->get_files_max_expiry($request_id);
		
		//update request
		$options=array(
			'expiry_date'=>$max_expiry
		);
		
		$this->db->where('id',$request_id);
		$this->db->update('lic_requests',$options);
	}

	function update_request_files($request_id,$file_options)
	{		
		foreach($file_options as $key=>$option)
		{
			$data= array(
				'fileid'=>$key,
				'requestid'=>$request_id,
				'download_limit' => $option['download_limit'],
				'expiry' => $option['expiry']
				);
			
			//check if the file already exists
			$exists=$this->exists_request_file($request_id,$data['fileid']);
			
			if ($exists)
			{
				//update
				$this->db->where('requestid',$request_id);		
				$this->db->where('fileid',$data['fileid']);	
				$result=$this->db->update('lic_file_downloads', $data); 
			}
			else
			{
				//insert
				$result=$this->db->insert('lic_file_downloads', $data); 
			}
			
			if (!$result)
			{
				log_message('info',"FAILED to attach file ". $data['fileid']);
			}					
		}
		
		//update request expiry date
		$this->update_request_expiry($request_id);
		return TRUE;
	}
	
	
	
	function delete_request_files($requestid,$excluded=NULL)
	{		
		if ($excluded!=NULL)
		{
			$this->db->where_not_in('fileid',$excluded);
		}
			
		$this->db->where('requestid',$requestid);	
		return $this->db->delete('lic_file_downloads'); 
	}


	

	/**
	* check request for attached file
	*
	*/
	function exists_request_file($request_id,$file_id)
	{
		$this->db->select('id');		
		$this->db->from('lic_file_downloads');		
		$this->db->where('requestid',$request_id);		
		$this->db->where('fileid',$file_id);		
		if ($this->db->count_all_results() > 0)
		{
			return TRUE;
		}
		return FALSE;
	}
  	
	/**
	 * Returns a single request by request_id
	 *
	 * @param $request_id	int
	 * @return array of request row
	 */
	function get_request_by_id($request_id)
	{	
		//get request info
		$this->db->select('*');
		$this->db->from('lic_requests');
		$this->db->where('lic_requests.id',$request_id);
		
		$result = $this->db->get()->row_array();
		
		if ($result)
		{
			//get request surveys
			$result['surveys']=$this->get_request_survey_list($request_id);
			
			//get user info
			$this->db->select('users.id,first_name as fname, last_name as lname, company as organization, email');
			$this->db->from($this->tables['meta']);
			$this->db->join($this->tables['users'], sprintf('%s.id = %s.user_id',$this->tables['users'],$this->tables['meta']),'inner');
			$this->db->where($this->tables['meta'].'.user_id',$result['userid']);			
			$result['user']=$this->db->get()->row_array();
		}
		
		return $result;
	}
	
	//get surveys with lic files approved by request
	function get_request_approved_surveys($request_id)
	{
		$this->db->select('resources.survey_id');
		$this->db->join('resources', 'lic_file_downloads.fileid= resources.resource_id');
		$this->db->where('lic_file_downloads.requestid',$request_id);
		$result=$this->db->get('lic_file_downloads')->result_array();
		
		if (!$result)
		{
			return FALSE;
		}
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row['survey_id'];			
		}
		
		return $output;
	}
	
	
	
	function get_request_survey_list($request_id)
	{
		$this->db->select('surveys.id,idno,surveys.title,surveys.nation,year_start,year_end');
		$this->db->join('survey_lic_requests', 'survey_lic_requests.sid= surveys.id');
		$this->db->where('survey_lic_requests.request_id',$request_id);
		$this->db->order_by('surveys.nation,surveys.title,surveys.year_start');
		$result=$this->db->get('surveys')->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[$row['id']]=$row;
		}
		
		return $output;
	}			
		

	/**
	* Return the download options set for a licensed request 
	*
	*/
	function get_request_download_options($requestid)
	{
		$this->db->select('*');		
		$this->db->from('lic_file_downloads');		
		$this->db->where('requestid',$requestid);		
		return $this->db->get()->result_array();
	}


	/**
	* Get downloadable files for a request
	*/
	function get_request_downloads($requestid)
	{	
		$this->db->select('resources.*');
		$this->db->from('resources');
		$this->db->join('lic_file_downloads', 'resources.resource_id = lic_file_downloads.fileid');
		$this->db->where('requestid',$requestid);
		return $this->db->get()->result_array();
	}
	
	
	/**
	* Get downloadable files for a request by survey
	*/
	function get_request_downloads_by_study($requestid,$surveyid)
	{	
		$this->db->select('resources.*');
		$this->db->from('resources');
		$this->db->join('lic_file_downloads', 'resources.resource_id = lic_file_downloads.fileid');
		$this->db->where('resources.survey_id',$surveyid);
		$this->db->where('requestid',$requestid);
		return $this->db->get()->result_array();
	}


	/**
	* Get files attached to a request with download options set by admin
	*
	*
	*/
	function get_request_files($surveyid, $requestid)
	{	
		//get all survey licensed files
		$files=$this->get_survey_licensed_files($surveyid);		
			
		//get download options already set
		$options=$this->get_request_download_options($requestid);		
		$result=NULL;		
		if ($files)
		{
			foreach($files as $file)
			{
				if ($options)
				{
					foreach($options as $option)
					{
						if ($file['resource_id']==$option['fileid'])
						{
							$file['download']=$option;
						}
					}	
				}
			$result[]=$file;
			}
		}
			
		return $result;
	}
	
	
	//get a list of licensed surveys for the user
	function get_survey_licensed_files($surveyid)
	{		
		$where=" survey_id=$surveyid AND (dctype like '%dat/micro]%' OR dctype like '%dat]%') ";
		
		$this->db->select('title,filename,resource_id');
		$this->db->from('resources');
		$this->db->select('title,filename,resource_id,changed');
		$this->db->where($where,NULL,FALSE);
		$query = $this->db->get();

		if ($query)
		{
			return $query->result_array();
		}
		else
		{
			throw new MY_Exception($this->db->_error_message());
		}	
	}
	
	/**
	 * Get file download history
	 * 
	 * 
	 * @param $requestid
	 * @param $fileid
	 * @return array
	 */
	function get_download_stats($requestid, $fileid)
	{
		$this->db->select('*');
		$this->db->from('lic_file_downloads');
		$this->db->where('fileid', $fileid);
		$this->db->where('requestid', $requestid);
		$query = $this->db->get()->row_array();

		if (count($query)>0)
		{		
			return $query;
		}
		return false;			
	}
	
	/**
	 * log licensed file download
	 * Increment the download stats
	 * 
	 * @param $file_id
	 * @param $request_id
	 * @param $user		username or email
	 *
	 * @return boolean
	 */
	function update_download_stats($file_id,$request_id,$user)
	{
		$data=array(
				'fileid'=>$file_id,
				'requestid'=>$request_id,
				'lastdownloaded'=>date("U")				
				);
		
		//log download
		$this->update_file_log($request_id,$file_id,$user);
		
		//check if tracking info already exists
		$exists=$this->get_download_stats($request_id, $file_id);

		if ($exists===FALSE)
		{
			//set default options
			$data['downloads']=1;
			$data['download_limit']=3;
				
			//insert
			$result=$this->db->insert('lic_file_downloads', $data);
		}
		else
		{
			$data['downloads']=$exists['downloads']+1;
			
			//update
			$this->db->where('id', $exists['id']);
			$result=$this->db->update('lic_file_downloads', $data); 			
		}
		
		return $result;	
	}
	
	/**
	* Keep track of each licensed download
	*
	*/
	function update_file_log($request_id,$file_id,$user)
	{
		$data=array(
				'fileid'=>$file_id,
				'requestid'=>$request_id,
				'ip'=>$this->input->ip_address(),
				'created'=>date("U"),
				'username'=>$user
				);
		
		$this->db->insert('lic_files_log', $data);		
	}
	
	
	/**
	 * Get data files downloads summary
	 * 
	 * @return array
	 */
	function get_request_summary($requestid)
	{
		$this->db->select('lic_file_downloads.*, resources.filename as filepath');
		$this->db->join('resources', 'resources.resource_id = lic_file_downloads.fileid');		
		$this->db->where('requestid', $requestid);
		return $this->db->get('lic_file_downloads')->result_array();
	}
	
	/**
	 * Get request download history
	 * 
	 * @return array
	 */	
	function get_request_log($requestid)
	{
		$this->db->select('lic_files_log.*,resources.filename as filepath');	
		$this->db->join('resources', 'resources.resource_id = lic_files_log.fileid');
		$this->db->where('lic_files_log.requestid', $requestid);
		return $this->db->get('lic_files_log')->result_array();	
	}
	
	
	/**
	 * Get all Licensed Requests from DB
	 * 
	 * 
	 * @return array
	 */
	function get_licensed_requests()
	{
		return $this->db->get('lic_requests')->result_array();	
	}
	

	
	/**
	 * Get a single request by request-id
	 *	
	 * @param $requestid
	 * @return array of request row
	 */
	function select_single($id)
	{
		$this->db->select('*');		
		$this->db->from('lic_requests');		
		$this->db->where('id',$id);		

		$result = $this->db->get()->row_array();
		return $result;
	}



	/**
	 * 
	 * 
	 * Return all licensed requests
	 * 
	 */
	function select_all($limit=null,$offset=0)
	{
		$this->db->select('users.username, users.email,surveys.id as survey_id, surveys.title as study_title, lic_requests.*');
		$this->db->join('users', 'users.id = lic_requests.userid');
		$this->db->join('survey_lic_requests', 'survey_lic_requests.request_id = lic_requests.id');
		$this->db->join('surveys', 'surveys.id = survey_lic_requests.sid');

		if($limit>0){
			$this->db->limit($limit, $offset);
		}
		
		return $this->db->get('lic_requests')->result_array();
	}

	
	/**
	 * Get all licensed surveys
	 *	
	 * 
	 * @return array of surveys
	 */
	function get_licensed_surveys()
	{
		$this->db->select('*');		
		$this->db->from('lic_requests');		

		$result = $this->db->get()->result_array();
		return $result;
	}
	
	/**
	* search database
	* 
	**/
    function search_requests($limit = NULL, $offset = NULL,$search_options=NULL,$sort_by=NULL,$sort_order=NULL,$repositoryid='central')
    {
		//start caching, without this total count will be incorrect
    	$this->db->start_cache();
		
		//allowed_fields
		$db_fields=array(
					'username'=>'username',
					//'title'=>'surveys.titl',
					//'survey_title'=>'surveys.titl',
					'created'=>'lic_requests.created',
					'status' => 'status',
					'keywords' => 'request_title'
					//'repositoryid'=>'surveys.repositoryid'
					);
		
		$where=array();
		
		//set where
		if ($search_options)
		{			
			foreach($search_options as $key=>$value)
			{
				if (!$value || trim($value)=="")
				{
					continue;//skip
				}
				
				//search only in the allowed fields
				if (array_key_exists($key,$db_fields))
				{
					if ($key=='keywords')
					{
						$where[]=sprintf('(request_title like %s or username like %s)',$this->db->escape('%'.$value.'%'), $this->db->escape('%'.$value.'%') );
					}
					else
					{
						$where[]=sprintf('%s like %s',$db_fields[$key], $this->db->escape('%'.$value.'%') );
					}	
				}
			}
		}		

		//set Limit clause
	  	$this->db->select('lic_requests.request_title,lic_requests.id, lic_requests.userid, lic_requests.created, lic_requests.status,users.username');
		$this->db->join($this->tables['users'], $this->tables['users'].'.id = lic_requests.userid');
		$this->db->join('survey_lic_requests', 'survey_lic_requests.id = lic_requests.id');
		$this->db->join('survey_repos', 'survey_lic_requests.sid = survey_repos.sid');
		$this->db->group_by('lic_requests.request_title,lic_requests.id, lic_requests.userid, lic_requests.created, lic_requests.status,users.username');

		$this->db->limit($limit, $offset);
		$this->db->from('lic_requests');

		if (count($where)>0){
			$this->db->where(implode(" AND ",$where));
		}
		
		$this->db->stop_cache();

		//set default sort order, if invalid fields are set
		if (!array_key_exists($sort_by,$db_fields)){
			$sort_by='title';
			$sort_order='ASC';
		}
		
		//must be set outside the start_cache...stop_cache to produce correct count_all_results query
		if ($sort_by!='' && $sort_order!=''){
			$this->db->order_by($db_fields[$sort_by], $sort_order); 
		}
				
        $result= $this->db->get()->result_array();
		return $result;
    }
	

	//returns the search result count  	
    function search_requests_count()
    {
        $count=$this->db->count_all_results('lic_requests');
		$this->db->flush_cache();
		return $count;
    }
	
	/**
	*
	* Delete licensed request
	*/
	function delete($requestid)
	{		
		$this->db->where('id',$requestid);	
		return $this->db->delete('lic_requests'); 
	}

	/**
	*
	* Return requets for a survey by user id
	*
	**/
	function get_survey_requests_by_user($user_id=NULL,$survey_id=NULL)
	{
			$this->db->select('lic_requests.id,expiry,status');
			$this->db->where('userid',$user_id);
			$this->db->where('lic_requests.surveyid',$survey_id);
			$this->db->where('lic_requests.status !=','DENIED');
			$this->db->join('lic_file_downloads', 'lic_requests.id = lic_file_downloads.requestid','left');
			$query=$this->db->get("lic_requests");
			//echo mktime(0,0,0,9,9,2010);
			if (!$query)
			{
				return FALSE;
			}
			
			return $query->result_array();
	}
	
	
	
	/**
	 * Get pending requests count per survey
	 *	
	 * @param $sid_arr	array
	 * @return array of request row
	 */
	function get_pending_requests_count($sid_arr)
	{
		$sid_arr=(array)$sid_arr;
		$this->db->select('sid, count(sid) as total');
		$this->db->from('lic_requests');
		$this->db->join('survey_lic_requests', 'lic_requests.id = survey_lic_requests.request_id','inner');
		$this->db->group_by('sid');
		$this->db->where('status','PENDING');
		$this->db->where_in('sid',$sid_arr);		

		$query= $this->db->get()->result_array();
		
		if (!$query)
		{
			return FALSE;
		}
		
		$result=array();
		foreach($query as $row)
		{
			$result[$row['sid']]=$row['total'];
		}
		
		return $result;
	}

	
	 /**
     * Add request history
     * 
     * 
     * @param $request_id
     * @param $options	array
     * @return integer - insert id
     */
    function add_request_history($request_id,$options)
	{
		$db_fields=array(
			'user_id', 
			'logtype', 
			'request_status', 
			'description', 
			'created'
		);

		$data= array(
			   'created' 	=> date("U"),
			   'lic_req_id'	=> $request_id
        );

		foreach($options as $key=>$value)
		{
			if (in_array($key,$db_fields))
			{
				$data[$key]=$value;
			}
		}		
		
		$result=$this->db->insert('lic_requests_history', $data); 
		log_message('info',"Request received for [Licensed dataset]");
		
		if ($result)
		{
			return $this->db->insert_id();
		}	
		
		return FALSE;
	}
	
	/**
	*
	* Get request history by request ID
	**/
	function get_request_history($request_id,$logtype=NULL)
	{
		$this->db->select("lic_requests_history.*,meta.first_name,meta.last_name");
		$this->db->join("users","lic_requests_history.user_id=users.email","inner");
		$this->db->join("meta","meta.user_id=users.id","inner");
		$this->db->where('lic_req_id',$request_id);
		if ($logtype!=NULL)
		{
			$this->db->where('logtype',$logtype);
		}
		$this->db->order_by('created','DESC');
		return $this->db->get('lic_requests_history')->result_array();
	}
	
	/**
	*
	* Delete request history
	**/
	function remove_request_history($request_id)
	{
		$this->db->where('lic_req_id',$request_id);
		$this->db->delete('lic_requests_history');
	}
	
	
	/**
	* Get Requests by File-ID
	*
	*	@file_id		int		resource id
	*	@user_id 		int		user id
	*/
	function get_requests_by_file($file_id,$user_id)
	{	
	/*
		$this->db->select('lic_file_downloads.*,lic_requests.userid,lic_requests.status as request_status');
		$this->db->from('lic_file_downloads');
		$this->db->join('lic_requests', 'lic_requests.id = lic_file_downloads.requestid','INNER');
		if($user_id)
		{
			$this->db->where('userid',$user_id);
		}
		$this->db->where('fileid',$file_id);
		return $this->db->get()->result_array();
		*/
		$sql=sprintf('select * from lic_requests 
						inner join lic_file_downloads on lic_requests.id=lic_file_downloads.requestid
						where lic_requests.id in (select requestid from lic_file_downloads where fileid=%d)
						and userid=%d and lic_file_downloads.fileid=%d;',
					$this->db->escape((int)$file_id),
					$this->db->escape((int)$user_id),
					$this->db->escape((int)$file_id)
					);
	
		$result=$this->db->query($sql)->result_array();
		return $result;
	}
	
	
	/**
	*
	* Return user requests by study
	*
	**/
	function get_user_study_requests($survey_id, $user_id,$request_status=NULL)
	{
		$this->db->select('id,request_type,status');
		$this->db->where('userid',$user_id);
		$this->db->where('surveyid',$survey_id);
		if($request_status)
		{
			$this->db->where_in('status',$request_status);
		}
		return $this->db->get('lic_requests')->result_array();
	}
	
		

	
	/**
	*
	* return survey collections with LICENSED DA enabled
	**/
	function get_study_collections($survey_id)
	{
		$this->db->select('survey_repos.repositoryid');
		$this->db->join('repositories', 'repositories.repositoryid = survey_repos.repositoryid','INNER');
		$this->db->where('survey_repos.sid',$survey_id);
		$this->db->where('repositories.group_da_licensed',1);
		return $this->db->get('survey_repos')->result_array();	
	}
	
	
	
	
	 /**
     * update request options
     * 
     * 
     * @param $survey_id
     * @param $user_id
     * @param $options	array
     * @return integer - insert id
     */
    function update_request($request_id,$user_id,$options)
	{
		$data= array(
			'userid' 	=>  $user_id ,
			'updated' 	=>  date("U"),
			'status'	=>  'PENDING',
			'locked'	=>  1
        );

		foreach($options as $key=>$value)
		{
			if (in_array($key,$this->db_fields))
			{
				$data[$key]=$value;
			}
		}
		
		$this->db->where('id',$request_id);
		$result=$this->db->update('lic_requests', $data); 
		
		if ($result)
		{
			return $this->db->insert_id();
		}	
		else
		{
			//failed
			log_message('info',"FAILED to save request for [Licensed dataset=$request_id] by user $user_id ");
		}
		
		return FALSE;
	}
	
	
		
	/**
	*
	* Returns the DA collection ID array if study is part of bulk data access collections
	**/
	public function study_has_bulk_access($sid)
	{
		$this->db->select('*');
		$this->db->from('da_collections c');	
		$this->db->join('da_collection_surveys cs', 'c.id = cs.cid','INNER');
		$this->db->where('cs.sid',$sid);
		$result=$this->db->get()->result_array();

		return $result;
	}
	
	
	public function get_request_owner_repo($request_id)
	{
		$this->get_request_by_id($request_id);	
	}

	//return bulk access collection title by id
	public function get_collection_title($cid)
	{
		$this->db->select('title');
		$this->db->from('da_collections');
		$this->db->where('id',$cid);
		$result=$this->db->get()->row_array();
		return $result['title'];
	}



	function export_to_csv($rows=null,$filename=null)
	{
		if(!$rows){
			$rows=$this->select_all();
		}

		if(!$filename){
			$filename='lic-requests-'.date("m-d-Y").'.csv';
		}

		//set header for outputing to CSV
		header("Expires: Sat, 01 Jan 1980 00:00:00 GMT");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public"); 
		header("Content-Description: File Transfer");		
        header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		
		if (!is_array($rows))
		{
			echo $rows;exit;
		}

		$outstream = fopen("php://output", "w");

		//header row with column names
		$column_names=array_keys($rows[0]);
		
		//unix timestamp type columns that needs to be formatted as readable date types
		$date_columns=array('created','updated','expiry_date');
		
		//$date_type_idx=array();
		
		//date type columns used by current data
		$date_columns_found=array();
		
		//get indexes for date type columns
		foreach($column_names as $col){
		    if (in_array($col,$date_columns)){
				$date_columns_found[]=$col;
		    }
		}

        //UTF8 BOM
        echo "\xEF\xBB\xBF";

		fputcsv($outstream, $column_names,$delimiter=",", $enclosure='"');	
	            
		//data rows
		foreach($rows as $row)
		{		    
		    if ($date_columns_found)
		    {			
				foreach($date_columns_found as $col)
				{
					$row[$col]=date("M/d/Y H:i:s", $row[$col]);
				}			
		    }
		    
		    fputcsv($outstream, array_values($row));
		}
	
		fclose($outstream);
	}
}