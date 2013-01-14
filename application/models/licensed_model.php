<?php
class Licensed_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		$this->load->config('ion_auth');
		$this->tables  = $this->config->item('tables');				
		//$this->output->enable_profiler(TRUE);
    }
	
    /**
     * Check if user has already submitted a request
     * There could be one request per user per survey
     * 
     * 
     * @param $user_id
     * @param $survey_id
     * @return request id or false
     */
	function check_user_request($user_id,$survey_id)
	{
		$this->db->select('id');
		$this->db->limit(1);
		$this->db->from('lic_requests');
		$this->db->order_by('updated','DESC');
		$this->db->where('surveyid',$survey_id);		
		$this->db->where('userid',$user_id);		
				
        $result= $this->db->get()->row_array();
		
		if ($result)
		{
			return $result['id'];
		}
		
		return FALSE;
	}

	/**
     * Returns request by user
     * 
     * 
     * @param $user_id
     * @return array
     */
	function get_user_requests($user_id)
	{
		$this->db->select('lic_requests.id,surveys.titl,lic_requests.created,lic_requests.status');		
		$this->db->from('lic_requests');	
		$this->db->join('surveys', 'surveys.id = lic_requests.surveyid','inner');	
		$this->db->where('userid',$user_id);						
		return $this->db->get()->result_array();
	}
    
	/**
	 * 
	 * @param $user_id
	 * @param $survey_id
	 * @return array of request row
	 */
	function get_request_status($user_id,$survey_id)
	{
		$this->db->select('lic_requests.*,surveys.titl');		
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
     * @param $survey_id
     * @param $user_id
     * @param $options	array
     * @return integer - insert id
     */
    function insert_request($survey_id,$user_id,$options)
	{
		$db_fields=array('org_rec','org_type', 'address', 'tel', 'fax', 'datause', 'outputs','compdate', 'datamatching', 'mergedatasets', 'team', 'dataset_access');

		$data= array(
               'surveyid' => $survey_id ,
               'userid' => $user_id ,
			   'created' => date("U"),
			   'updated' => date("U"),
				'status'=>'PENDING',
				'locked'=>1
        );

		foreach($options as $key=>$value)
		{
			if (in_array($key,$db_fields))
			{
				$data[$key]=$value;
			}
		}		
		$result=$this->db->insert('lic_requests', $data); 
		log_message('info',"Request received for [Licensed dataset]");
		
		if ($result)
		{
			return $this->db->insert_id();
		}	
		else
		{
			//failed
			log_message('info',"FAILED to save request for [Licensed dataset=$survey_id] by user $user_id ");
		}
		
		return FALSE;
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
				return FALSE;
			}					
		}
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
	* Returns the surveyid by requestid
	*
	*/
	function get_surveyid_by_request($request_id)
	{
		$this->db->select('surveyid');		
		$this->db->from('lic_requests');		
		$this->db->where('id',$request_id);
		
		$result=$this->db->get()->row_array();		
		
		if ($result)
		{
			return $result['surveyid'];
		}
		return FALSE;
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
		$this->db->select('lic_requests.*, titl,surveys.id as survey_uid, proddate,nation');		
		$this->db->from('lic_requests');		
		$this->db->join('surveys', 'surveys.id = lic_requests.surveyid','inner');
		$this->db->where('lic_requests.id',$request_id);		

		$result = $this->db->get()->row_array();
		
		if ($result)
		{
			//get user info
			$this->db->select('first_name as fname, last_name as lname, company as organization, email');
			$this->db->from($this->tables['meta']);
			$this->db->join($this->tables['users'], sprintf('%s.id = %s.user_id',$this->tables['users'],$this->tables['meta']),'inner');
			$this->db->where($this->tables['meta'].'.user_id',$result['userid']);			
			$user=$this->db->get()->row_array();
			
			if ($user)
			{
				$result=array_merge($result,$user);
			}
		}
			
		return $result;
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
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search_requests($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL)
    {
		//start caching, without this total count will be incorrect
    	$this->db->start_cache();

		//select columns for output
		$this->db->select('lic_requests.*, users.username, surveys.titl as survey_title, surveys.repositoryid, surveys.nation, surveys.data_coll_start, surveys.data_coll_end');
		
		//allowed_fields
		$db_fields=array(
					'status'=>'status',
					'username'=>'username',
					'title'=>'surveys.titl',
					'survey_title'=>'surveys.titl',
					'created'=>'lic_requests.created',
					'repositoryid'=>'surveys.repositoryid'
					);
		
		//set where
		if ($filter)
		{			
			foreach($filter as $f)
			{
				//search only in the allowed fields
				if (array_key_exists($f['field'],$db_fields))
				{
					$this->db->like( $db_fields[$f['field']], $f['keywords']); 
				}
				else if ($f['field']=='all')
				{
					foreach($db_fields as $field)
					{
						$this->db->or_like($field, $f['keywords']); 
					}
				}
			}
		}

		//set Limit clause
	  	$this->db->limit($limit, $offset);
		$this->db->from('lic_requests');		
		$this->db->join($this->tables['users'], $this->tables['users'].'.id = lic_requests.userid');
		$this->db->join('surveys', 'surveys.id = lic_requests.surveyid');
		$this->db->stop_cache();

		//set default sort order, if invalid fields are set
		if (!array_key_exists($sort_by,$db_fields))
		{
			$sort_by='title';
			$sort_order='ASC';
		}
		
		//must be set outside the start_cache...stop_cache to produce correct count_all_results query
		if ($sort_by!='' && $sort_order!='')
		{
			$this->db->order_by($db_fields[$sort_by], $sort_order); 
		}
				
        $result= $this->db->get()->result_array();
		return $result;
    }
	

	//returns the search result count  	
    function search_requests_count()
    {
        return $this->db->count_all_results('lic_requests');
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
		$this->db->select('surveyid as sid, count(surveyid) as total');
		$this->db->from('lic_requests');
		$this->db->group_by('surveyid');
		$this->db->where_in('surveyid',$sid_arr);		

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
	
}