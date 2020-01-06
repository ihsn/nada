<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA ACL
 * 
 *
 * Note: Add this line to application/config/config.php to enable debugging 
 * $config['acl_debug']=true;
 * 
 */ 
class ACL
{
	var $debug=false;

	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "ACL Class Initialized.");
		$this->ci =& get_instance();
		$this->ci->load->model('Permissions_model');
		$this->ci->load->model('repository_model');

		if ($this->ci->config->item('acl_debug')==true){
			$this->debug=true;
		}
	}

	/**
	*
	* Returns the currently logged in user object
	**/
	function current_user()
	{
		return $this->ci->ion_auth->current_user();
	}

	function user_has_url_access($user_id=NULL,$url=NULL)
	{
		if($user_id==NULL)
		{
			//get current user
			$user=$this->current_user();
			$user_id=$user->id;
		}
		
		if($url==NULL)
		{			
			//get url path
			$url=$this->ci->uri->uri_string();
		}
		
		//check user has access
		return $this->check_url_access($user->id,$url);
	}
	
	
	/**
	*
	* Returns an array of all urls for which user has access by collection
	**/
	function get_repo_urls_by_user($user_id=NULL,$repo_id=NULL)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}
		
		$this->ci->db->select('u.url');
		$this->ci->db->from('user_repo_permissions p');
		$this->ci->db->join('repo_perms_urls u','p.repo_pg_id=u.repo_pg_id','INNER');
		$this->ci->db->where('p.user_id',$user_id);
		if($repo_id)
		{
			$this->ci->db->where('p.repo_id',$repo_id);
		}	
		$query=$this->ci->db->get()->result_array();
		
		$urls=array();
		foreach($query as $row)
		{
			$urls[]=$row['url'];
		}
		
		return $urls;
	}
	
	
	
	function check_url_access($user_id,$url)
	{
		//get user global groups
		$groups=$this->ci->ion_auth->get_groups_by_user($user_id);
		
		if (!is_array($groups) || count($groups)==0)
		{
			return FALSE;
		}

		//check if user is ADMIN and has UNLIMITED permissions
		$unlimited=$this->has_unlimited_access($groups);
		
		//user has unlimited access, skip url checks
		if ($unlimited===TRUE)
		{
			return TRUE;
		}
		
		//get URLs allowed to the global groups
		$allowed_urls=$this->url_access_by_group($groups);
		
		//permissions are tested at the page level by per collection
		$excluded_urls=array(
						'admin/catalog',
						'admin/catalog/*',
						'admin/licensed_requests',
						'admin/licensed_requests/*',
						'admin/resources/*',
						'admin/pdf_generator/*',
						'admin/catalog_notes/*',
						'datadeposit',
						'datadeposit/*'
		);
		
		$allowed_urls=array_merge($allowed_urls,$excluded_urls);
		
		//see if it matches with a url without using regex
		if (in_array($url,$allowed_urls))
		{
			return TRUE;
		}

		//try finding url using regex/other expressions
		foreach ($allowed_urls as $page_url)
		{
			// Convert wild-cards to RegEx
			$key = str_replace('*', '.+', $page_url);

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $url))
			{
				return TRUE;
			}
		}

		
		/*
		echo '<pre>';
		print_r($allowed_urls);
		echo '</pre>';
		*/
		
		return FALSE;		

/*		
		//test user access by collection
		//get study repository
		$active_repo_id=$this->user_active_repo();
		
		if(!$active_repo_id)
		{
			return FALSE;
		}
		
		//test user access by active repo
		$has_repo_access=$this->check_user_access_by_repo($user_id,$active_repo_id,$url); 		
		return $has_repo_access;;
*/		
	}
	
	
	function check_user_access_by_repo($user_id,$repo_id=NULL,$url)
	{		
		$allowed_urls=$this->get_repo_urls_by_user($user_id,$repo_id); 
		
		//see if it matches with a url without using regex
		if (in_array($url,$allowed_urls))
		{
			return TRUE;
		}
				
		//try finding url using regex/other expressions
		foreach ($allowed_urls as $page_url)
		{
			// Convert wild-cards to RegEx
			$key = str_replace('*', '.+', $page_url);

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $url))
			{
				return TRUE;
			}
		}

		if ($this->debug==TRUE)
		{
			echo '<pre>';
			var_dump('repositoryid:',$repo_id);
			var_dump('user_id:',$user_id);
			var_dump('allowed_urls:',$allowed_urls);
			echo '</pre>';
		}
		return FALSE;
	}
	
	
	
	
	/**
	*
	* Returns an array of URLs for which group(s) have access
	*
	* @groups 	array
	**/
	function url_access_by_group($groups)
	{
		if (!is_array($groups))
		{
			throw new Exception("url_access_by_group::invalid_groups");
		}
		
		$this->ci->db->select('pu.url');
		$this->ci->db->distinct();
		$this->ci->db->from('group_permissions gp');
		$this->ci->db->join('permission_urls pu','gp.permission_id=pu.permission_id','inner');
		$this->ci->db->where_in('gp.group_id',$groups);
		$query=$this->ci->db->get()->result_array();
		
		$urls=array();
		foreach($query as $row)
		{
			$urls[]=$row['url'];
		}
		
		return $urls;
	}

	/**
	*
	* Returns an array of allowed URLs by User
	*
	**/
	function url_access_by_user($user_id=NULL)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}
		
		//get user groups
		$groups=$this->ci->ion_auth->get_groups_by_user($user_id);
		
		if (!is_array($groups))
		{
			return FALSE;
		}

		return $this->url_access_by_group($groups);	
	}	
	
	/**
	*
	* Check if group(s) have unlimited access type set
	* @groups	array()
	**/
	function has_unlimited_access($groups)
	{
		if (!$groups)
		{
			return FALSE;
		}
	
		$this->ci->db->select('access_type');
		$this->ci->db->from('groups');
		$this->ci->db->where_in('id',$groups);
		$this->ci->db->where('access_type','UNLIMITED');
		$count=$this->ci->db->count_all_results();
		
		if ($count>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	*
	* Check if user has UNLIMITED access
	**/
	function user_has_unlimited_access($user_id=NULL)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}

	
		$groups=$this->get_user_groups($user_id);
		
		if (!$groups)
		{
			return FALSE;
		}
		
		return $this->has_unlimited_access($groups);
	}
	
	
	/**
	* Returns the active repo for the current logged in user
	**/
	function user_active_repo()
	{
		//$repoid=$this->ci->session->userdata('active_repo');
		$repoid=$this->ci->input->cookie('active_repo',TRUE);
		
		if (!is_numeric($repoid))
		{
			return FALSE;
		}

		return $repoid;		
	}
	
	
	/**
	* set active repo for the session
	**/
	function set_active_repo($repoid)
	{
		$this->ci->input->set_cookie($name='active_repo', $value=$repoid, $expire=865000, $domain='', $path='/', $prefix='', $secure=FALSE);
		//$this->ci->session->set_userdata('active_repo',  $repoid);
		return TRUE;
	}
	
	/**
	*Clear active repo from user session
	**/
	function clear_active_repo()
	{
		//$this->ci->session->set_userdata('active_repo',  false);
		$this->ci->input->set_cookie($name='active_repo', $value='', $expire=0, $domain='', $path='/', $prefix='', $secure=FALSE);
	}

	/**
	* Return Repo object with basic info - repositoryid, title
	**/
	function get_repo($id)
	{
		//get repo info
		$this->ci->db->select("repositoryid,id,title");
		$this->ci->db->where("id",$id);
		$query=$this->ci->db->get("repositories");

		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->row_array();
		
		if ($result)
		{
			return (object)$result;
		}
		return FALSE;
	}
	
	
	/**
	* Returns an array of repositories accessible to the user
	**/
	function get_user_repositories($user_id=NULL)
	{
		if ($user_id==NULL)
		{
			$user_id=$this->ci->session->userdata('user_id');
		}
		
		//check user has UNLIMITED access
		$is_unlimited=$this->user_has_unlimited_access($user_id);
		
		//get global user groups
		$groups=$this->get_user_groups($user_id);
				
		if ($is_unlimited===TRUE)
		{
			//return all repositories;
			$this->ci->db->select("id,title,id as repo_id,repositoryid,thumbnail,short_text");
			$this->ci->db->order_by("title", "ASC");
			$query=$this->ci->db->get("repositories");
		}
		else
		{	//limited admin account
			$this->ci->db->select("r.id,r.title,r.thumbnail,r.short_text,r.repositoryid");
			$this->ci->db->from("user_repo_permissions gr");
			$this->ci->db->join('repositories r', 'r.id = gr.repo_id');			
			$this->ci->db->where_in("user_id",$user_id);
    		$this->ci->db->order_by("r.title", "ASC");
			$this->ci->db->group_by('r.id,r.title,r.thumbnail,r.short_text,r.repositoryid');
			$query=$this->ci->db->get();
		}
		
		if (!$query)
		{
			return FALSE;
		}
		
		$collections= $query->result_array();
		array_unshift($collections, $this->ci->Repository_model->get_central_catalog_array()	);
		return $collections;
	}
	
	
	/**
	*
	* Return user groups
	**/
	function get_user_groups($user_id)
	{
		return $this->ci->ion_auth->get_groups_by_user($user_id);		
	}


	function user_has_lic_request_access($request_id,$user_id=NULL,$die=TRUE)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}
		
		//check user has UNLIMITED access
		$unlimited=$this->user_has_unlimited_access($user_id);
		
		if ($unlimited)
		{
			return TRUE;
		}

		//get licensed request information		
		$request=$this->ci->Licensed_model->get_request_by_id($request_id);
		$request_repo='';

		if (isset($request['surveys']))
		{
			$id_=array_keys($request['surveys']);
			
			if(!is_array($id_)){
				return false;
			}

			$id_=$id_[0];
			
			$request_repo=$this->get_survey_owner_repos($id_);
		}
		else
		{
			//$request_repo=$this->get_survey_owner_repos($request['surveyid']);
			show_error("NOT IMPLEMENTED");
		}

		$url=$this->ci->uri->uri_string();

		foreach($request_repo as $repo_id)
		{
			$has_repo_access=$this->check_user_access_by_repo($user_id,$repo_id,$url);
			
			if($has_repo_access===TRUE)
			{
				return TRUE;
			}
		}	
		
		if ($die==TRUE )
		{
			show_error(t("lic_data_request_access_denied"));
		}

		return FALSE;
	}


	function user_has_lic_request_view_access($repo_id,$user_id=NULL,$die=TRUE)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}
		
		//check user has UNLIMITED access
		$unlimited=$this->user_has_unlimited_access($user_id);
		
		if ($unlimited)
		{
			return TRUE;
		}

		$url=$this->ci->uri->uri_string();
		$has_repo_access=$this->check_user_access_by_repo($user_id,$repo_id,$url);			
		if($has_repo_access===TRUE)
		{
			return TRUE;
		}
		
		if ($die==TRUE )
		{
			show_error(t("lic_data_request_access_denied"));
		}

		return FALSE;
	}


	/**
	*
	* Check if user has access to a study
	**/
	function user_has_study_access($survey_id,$user_id=NULL,$die=TRUE,$check_url=TRUE)
	{
		if($user_id==NULL)
		{
			//get user
			$user=$this->current_user();
			
			//set user id
			$user_id=$user->id;
		}
		
		//check user has UNLIMITED access
		$unlimited=$this->user_has_unlimited_access($user_id);
		
		if ($unlimited)
		{
			return TRUE;
		}
		
		//for everybody else, they must have the permissions assigned per collection
		
		//check if user has access to the repository owning the study
		
		//get repository that owns the study [could be more than one repo owning the same study]
		$owner_repos=$this->get_survey_owner_repos($survey_id);
		
		$url=$this->ci->uri->uri_string();

		foreach($owner_repos as $repo_id)
		{
			$has_repo_access=$this->check_user_access_by_repo($user_id,$repo_id,$url);

			if($has_repo_access===TRUE)
			{
				return TRUE;
			}
		}	
		
		if ($die==TRUE )
		{
			show_error(t("study_access_denied"));
		}

		return FALSE;
		
		
		/*
		
		//get user groups
		$user_groups=$this->get_user_groups($user_id);

		//get survey owner repositories [survey can be owned by multiple repositories]
		$owner_repos=$this->get_survey_owner_repos($survey_id);
		
		//check if user(group) has access to repository
		$access= $this->group_has_repo_access($user_groups,$owner_repos);

		if (!$access && $die==TRUE )
		{
			show_error(t("study_access_denied"));
		}
		else if(!$access)
		{
			return FALSE;
		}
		
		return TRUE;
		
		*/
	}
	
	/**
	*
	* Get survey owner repositories
	*
	**/
	function get_survey_owner_repos($survey_id)
	{
		$this->ci->db->select('r.id');
		$this->ci->db->from('repositories r');
		$this->ci->db->join('survey_repos sr','sr.repositoryid=r.repositoryid','inner');
		$this->ci->db->where('sr.sid',$survey_id);
		$this->ci->db->where('sr.isadmin',1);
		$repositories=$this->ci->db->get()->result_array();
		
		$repos=array();
		foreach($repositories as $repo)
		{
			$repos[]=$repo['id'];
		}
		
		return $repos;
	}
	
	/**
	*
	* Check if a user group has access to repository 
	*	
	* groups	array(int)
	* repos		array(int)
	**/
	function group_has_repo_access($groups,$repos)
	{
		$this->ci->db->select('count(id) as total');
		$this->ci->db->where_in('group_id',$groups);
		$this->ci->db->where_in('repo_id',$repos);
		$query=$this->ci->db->get('group_repo_access')->row_array();
		
		if (isset($query['total']) && $query['total']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}


	function user_has_repository_access($repositoryid,$user_id=NULL,$die=TRUE)
	{	
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}

		$unlimited=$this->user_has_unlimited_access($user_id);
		
		if ($unlimited)
		{
			return TRUE;
		}
		
		/*
		//validate if user has access to the selected repository
		$user_repositories=$this->get_user_repositories();

		$user_repo_access=FALSE;
		foreach($user_repositories as $repo)
		{
			if ($repo["id"]==$repositoryid)
			{
				$user_repo_access=TRUE;
				return TRUE;
			}
		}*/
		
		$url=$this->ci->uri->uri_string();

		//check user access to repository per url
		$has_repo_access=$this->check_user_access_by_repo($user_id,$repositoryid,$url);

		if($has_repo_access===TRUE)
		{
			return TRUE;
		}		
		
		if ($has_repo_access===FALSE && $die===TRUE)
		{
			show_error(t("ACCESS_DENIED"));
		}
	
		return FALSE;
	}

	
	function user_has_unpublished_repo_access_or_die($user_id,$repositoryid)
	{
		if (!$repositoryid)
		{
			return FALSE;
		}
		
		$repo=$this->ci->repository_model->get_repository_by_repositoryid($repositoryid);
		
		if (!$repo)
		{
			return FALSE;
		}
		
		//no checks for published repositories
		if ($repo['ispublished']==1)
		{
			return TRUE;
		}
	
		$has_access=$this->user_has_unpublished_repo_access($user_id,$repositoryid);

		if(!$has_access)
		{
			show_error(t("CONTENT_NOT_AVAILABLE"));
		}
	}
	
	
	//check user has access to an unpublished repository from the front end
	function user_has_unpublished_repo_access($user_id,$repositoryid)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			
			if (!$user)
			{
				return FALSE;
			}
			
			$user_id=$user->id;
		}

		$unlimited=$this->user_has_unlimited_access();
		
		if ($unlimited)
		{
			return TRUE;
		}
		
		//validate if user has access to the selected repository
		$user_repositories=$this->get_user_repositories();
		
		foreach($user_repositories as $repo)
		{
			if ($repo["repositoryid"]==$repositoryid)
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}


	function user_can_review($study_id,$user_id=NULL)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			
			if(!$user)
			{
				return FALSE;
			}
			
			$user_id=$user->id;
		}

		/*
		$unlimited=$this->user_has_unlimited_access();
		
		if ($unlimited)
		{
			return TRUE;
		}*/
	
		//get study repository
		$repo=$this->ci->repository_model->get_survey_owner_repository($study_id);
		
		if(!$repo)
		{
			return FALSE;
		}		
		
		//check user is member of the collection/reviewer group
		$this->ci->db->select('count(*) as found');
		$this->ci->db->where('repo_id',$repo['id']);
		$this->ci->db->where('repo_pg_id',4);//hard coded group id for reviewer
		$this->ci->db->where('user_id',$user_id);
		$result=$this->ci->db->get('user_repo_permissions')->row_array();

		if ($result['found']>0)
		{
			return TRUE;
		}	
		
		return FALSE;
	}
}

