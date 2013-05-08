<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA ACL
 * 
 *
 *
 *
 * @package		NADA 4.0-Alpha
 * @subpackage	Libraries
 * @category	Access Control Lists (ACL)
 * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class ACL
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "ACL Class Initialized.");
		$this->ci =& get_instance();
		$this->ci->load->model('Permissions_model');
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
	
	function check_url_access($user_id,$url)
	{
		//get user groups
		$groups=$this->ci->ion_auth->get_groups_by_user($user_id);
		
		if (!is_array($groups))
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
		
		//get URLs allowed to the groups
		$allowed_urls=$this->url_access_by_group($groups);
		
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
		
		//var_dump($allowed_urls);
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
		
		if (!$repoid)
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
		
		//get user groups
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
			$this->ci->db->from("group_repo_access gr");
			$this->ci->db->join('repositories r', 'r.id = gr.repo_id');			
			$this->ci->db->where_in("group_id",$groups);
    		$this->ci->db->order_by("r.title", "ASC");
			$query=$this->ci->db->get();
		}
		
		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();
	}
	
	
	/**
	*
	* Return user groups
	**/
	function get_user_groups($user_id)
	{
		return $this->ci->ion_auth->get_groups_by_user($user_id);		
	}


	/**
	*
	* Check if user has access to a study
	**/
	function user_has_study_access($survey_id,$user_id=NULL,$die=TRUE)
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


	function user_has_repository_access($repositoryid,$user_id=NULL)
	{
		if($user_id==NULL)
		{
			$user=$this->current_user();
			$user_id=$user->id;
		}

		$unlimited=$this->user_has_unlimited_access();
		
		if ($unlimited)
		{
			return TRUE;
		}
		
		//validate if user has access to the selected repository
		$user_repositories=$this->get_user_repositories();
		
		$user_repo_access=FALSE;
		foreach($user_repositories as $repo)
		{
			if ($repo["repositoryid"]==$repositoryid)
			{
				$user_repo_access=TRUE;
				return TRUE;
			}
		}
		
		if ($user_repo_access===FALSE)
		{
			show_error(t("REPO_ACCESS_DENIED"));
		}
	
	}

}

