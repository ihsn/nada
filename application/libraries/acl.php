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
	}

	/**
	*
	* Returns the currently logged in user object
	**/
	function current_user()
	{
		$userid=$this->ci->session->userdata('user_id');
		return $userid;
	}

	/**
	* Returns an array repositories accessible to the user
	**/
	function user_repositories($userid=NULL)
	{
		if ($userid==NULL)
		{
			$userid=$this->ci->session->userdata('user_id');
		}
		
		//get user global role
		$user_role=$this->get_user_global_role($userid);
		
		if ($user_role=='admin')
		{
			//return all repositories;
			$this->ci->db->select("id,title,id as repositoryid");
			$query=$this->ci->db->get("repositories");
		}
		else
		{	//non-admin account
			$this->ci->db->select("rg.*,r.title");
			$this->ci->db->where("userid",$userid);
			$this->ci->db->join('repositories r', 'r.id = rg.repositoryid');			
			$query=$this->ci->db->get("user_repositories rg");
		}
		
		if (!$query)
		{
			return FALSE;
		}
		
		return $query->result_array();
	}
	
	/**
	* Returns the active repo for the current logged in user
	**/
	function user_active_repo()
	{
		$repoid=$this->ci->session->userdata('active_repo');
		
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
		$this->ci->session->set_userdata('active_repo',  $repoid);
		return TRUE;
	}
	
	/**
	*Clear active repo from user session
	**/
	function clear_active_repo()
	{
		$this->ci->session->set_userdata('active_repo',  false);
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
	* Return the owner repository id for the study
	**/
	function get_study_repository($id)
	{
		$this->ci->db->select("repositoryid");
		$this->ci->db->where("id",$id);
		$query=$this->ci->db->get("surveys");
		//echo $this->ci->db->last_query();
		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->row_array();
		
		if ($result)
		{
			return $result['repositoryid'];
		}
		return FALSE;
	}
	
	
	//get the user global role
	function get_user_global_role($userid)
	{
		$this->ci->db->select("name as role");
		$this->ci->db->join('user_groups', 'user_groups.id = users.group_id');
		$this->ci->db->where('users.id',$userid);
		$query=$this->ci->db->get("users");

		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->row_array();
		
		if ($result)
		{
			return $result['role'];
		}
		
		return FALSE;		
	}
	
	/**
	* Return user role object by repository id
	**/
	function get_user_roles_by_repository($userid,$repositoryid)
	{
		$this->ci->db->select("rg.*,r.title");
		$this->ci->db->join('repositories r', 'r.id = rg.repositoryid');
		$this->ci->db->where("userid",$userid);
		$this->ci->db->where("r.repositoryid",$repositoryid);
		
		$query=$this->ci->db->get("user_repositories rg");
		//echo $this->ci->db->last_query();
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
	*
	* Test user permissions for a given study
	**/
	function check_study_permissions()
	{
		//test urls under /admin/*
		if ($this->ci->uri->segment(1)!=='admin')
		{
			return TRUE;
		}
		
		$studyid=NULL; //study id to test permissions
		$repositoyid=NULL; //repository id
		$userid=$this->current_user();
		$section=''; //catalog,reports,license_requests
		
		if (!$userid)
		{
			show_error("ERROR_USERID_NOT_SET");
		}
		
		//get user global role
		$user_role=$this->get_user_global_role($userid);

		//if ADMIN, grant all permissions
		if ($user_role=='admin')
		{
			return TRUE;
		}		
		
		$catalog_urls[]='admin/managefiles/(:num)';
		$catalog_urls[]='admin/managefiles/(:num)/access';
		$catalog_urls[]='admin/catalog/26/edit';
		$catalog_urls[]='admin/catalog/(:num)/resources';
		
		
		//find study id from URL
		switch($this->ci->uri->segment(2))
		{
			case 'managefiles':
				$studyid=$this->ci->uri->segment(3);
				$section='catalog';
			break;
			
			case 'catalog':
				if (is_numeric($this->ci->uri->segment(3)))
				{
					if (in_array($this->ci->uri->segment(4),array('edit','resources'))) //apply restriction on edit and resources
					{
						$studyid=$this->ci->uri->segment(3);
						$section='catalog';
					}	
				}
			
			break;
			
		}

		if (!$studyid)
		{
			return;
			//show_error("ERROR_STUDY_NOT_SET");
		}
		
		//get owner repositoryid for the study
		$repositoryid=$this->get_study_repository($studyid); 
		
		if (!$repositoryid)
		{
			show_error(t("ERROR_REPOSITORYID_NOT_SET"));
		}
		
		//find the user roles for the study repository
		$obj_roles=$this->get_user_roles_by_repository($userid,$repositoryid);				
		

		//user roles found
		if ($obj_roles)
		{
			$access_allowed=FALSE;
			//check if user has access to the site section
			switch($section)
			{
				case 'catalog':
					if ((int)$obj_roles->allow_catalog==1)
					{
						$access_allowed=TRUE;
					}
				break;
				
				case 'reports':
					if ((int)$obj_roles->allow_reports==1)
					{
						$access_allowed=TRUE;
					}				
				break;
				
				case 'licensed_requests':
					if ((int)$obj_roles->allow_lic_request===1)
					{
						$access_allowed=TRUE;
					}
				break;
			}

			if ($access_allowed===TRUE)
			{
				return TRUE;
			}
		}
		show_error(t('ACCESS_DENIED_USER_HAS_NO_ACCESS'));
		//show_error("You don't have permissions to access content");
	}	
}

