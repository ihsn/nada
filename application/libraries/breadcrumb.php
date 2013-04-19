<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Breadcrumb Class
 *
 * Create breadcrumbs based on the URL
 *
 * @category	Libraries
 * @author		Mehmood Asghar
 * @link		
 * @license		GPL
 */

class Breadcrumb
{
	private $ci;

	/**
	 * Constructor - Initializes and references CI
	 */
	function __construct()
	{
		log_message('debug', "Breadcrumb Class Initialized.");
		$this->ci =& get_instance();
	}
	
	/**
	*
	* Generate breadcrumb from the url path
	*/
	function generate()
	{
		$this->ci->lang->load('breadcrumbs');
		$breadcrumbs=array();
		$breadcrumbs['']=t('Home');
		
		$segments=$this->ci->uri->segment_array();
	
		if (!is_array($segments) && count($segments)===0)
		{
			return FALSE;
		}
		
		//set active repository
		/*
		$active_repo=$this->ci->session->userdata('active_repository');
		if ($active_repo=='' || $active_repo=='central')
		{
			$active_repo='central';
			$repository_title=t('central_data_catalog');
		}
		else
		{
			$repository_title=$this->get_repository_title($active_repo);
		}*/
		
		//by default set CENTRAL as active repository
		$active_repo='CENTRAL';
		$repository_title=t('central_data_catalog');
		
		
		if($segments[1]=='catalog' && isset($segments[2]))
		{
		
			if(!is_numeric($segments[2]) )
			{
				//check if a valid repo
				$repo_title=$this->get_repository_title($segments[2]);
				
				if($repo_title!==false)
				{
					$active_repo=$segments[2];
					$repository_title=$repo_title;
				}
			}
		}
		
		switch($segments[1])
		{
				case 'about':
				case 'resources':
				case 'terms':
				case 'contributing-catalogs':
				case 'deposit':
				case 'help':
				case 'faqs':
					$breadcrumbs[$segments[1]]=t($segments[1]);
				break;
				
				
				case 'catalog':
					
					$breadcrumbs['catalog']=t('central_data_catalog');
					
					//set sub collection breadcrumb
					if(strtolower($active_repo)!='central' && $repository_title!==false)
					{
						$breadcrumbs['catalog/'.$active_repo]=$repository_title;
					}
						
					$repo_owner=false;
					
					//study page e.g. catalog/123
					if (isset($segments[2]) && is_numeric($segments[2]))
					{	
						//get owner of the study
						$repo_owner=$this->get_survey_owner_repo($segments[2]);
					}

					if ($repo_owner && !in_array(strtolower($repo_owner['repositoryid']),array('central','default')))
					{
						$breadcrumbs['catalog/'.$repo_owner['repositoryid']]=strtoupper($repo_owner['repositoryid']);
					}
					
					if (count($segments)>1)
					{
						if (is_numeric($segments[2]))
						{								
							//get study reference id
							$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
						}
						
						if ($segments[2]=='history')
						{
							$breadcrumbs['catalog/history/']=t('catalog_history');
						}
						
						if ($segments[2]=='access_policy')
						{
							$breadcrumbs['catalog/'.$segments[3]]=$this->get_study_info($segments[3]);
							$breadcrumbs['catalog/access_policy/'.$segments[3]]=t('Access Policy');
						}
						
						if (isset($segments[3]))
						{						
							if ($segments[3]=='reports')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/reports']=t('Reports');
							}
							
							if ($segments[3]=='accesspolicy')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/accesspolicy']=t('access_policy');
							}
							
							if ($segments[3]=='technicaldocuments')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/technicaldocuments']=t('technical_documents');
							}

							if ($segments[3]=='sampling')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/sampling']=t('sampling');
							}
							
							if ($segments[3]=='questionnaires')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/questionnaires']=t('questionnaires');
							}
							
							if ($segments[3]=='datacollection')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/datacollection']=t('data_collection');
							}

							if ($segments[3]=='othermaterials')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/othermaterials']=t('other_materials');
							}

							if ($segments[3]=='stat_tables')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/stat_tables']=t('statistical_tables');
							}
							
							if ($segments[3]=='citations')
							{
								$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
								$breadcrumbs['catalog/'.$segments[2].'/citations']=t('citations');
							}
						}						
						
						if (isset($segments[3]) && ( in_array($segments[3],array('datafiles','data_dictionary','datafile')) ))
						{
							$breadcrumbs['catalog/'.$segments[2].'/data_dictionary']=t('Data Dictionary');
							
							if (isset($segments[4]))
							{
								$breadcrumbs['catalog/'.$segments[2].'/datafile/'.$segments[4]]=$segments[4];
							}
							
							//variable e.g. catalog/7/datafile/F1/V1
							if (isset($segments[5]))
							{
								$breadcrumbs['catalog/'.$segments[2].'/datafile/'.$segments[4].'/'.$segments[5]]=$segments[5];
							}
							
						}
						
						if (isset($segments[3]) && $segments[3]=='vargrp')
						{
							if (isset($segments[4]))
							{
								$breadcrumbs['catalog/'.$segments[2].'/vargrp/'.$segments[4]]=t('Variable Group').' ['.$segments[4].']';
							}
						}
						
						if (isset($segments[3]) && $segments[3]=='variable')
						{
							if (isset($segments[4]))
							{
								$breadcrumbs['catalog/'.$segments[2].'/variable/'.$segments[4]]=t('variable').' ['.$segments[4].']';
							}
						}	
					}	
			break;

			
			case 'access_direct':
					$breadcrumbs['catalog']=$repository_title;
					$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
					$breadcrumbs['access_direct/'.$segments[2]]=t('Direct Data Access');
			break;

			case 'access_public':
					$breadcrumbs['catalog']=$repository_title;
					$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
					$breadcrumbs['access_public/'.$segments[2]]=t('Public Use Dataset');
			break;

			case 'datadeposit':
					$breadcrumbs['data-deposit']=t('datadeposit');
					$breadcrumbs['datadeposit/projects']=t('my_projects');
					$dd_array=array('summary', 'update', 'submit_review','study','datafiles','citations');
					if(in_array($segments[2], $dd_array) && isset($segments[3]))
					{
							$title = $this->get_data_deposit_project_title($segments[3]);
							if (strlen($title) > 100) {
								$title = substr($title, 0, 100) . '...';
							}
							// always to project information
							$breadcrumbs['datadeposit/update/'. $segments[3]] = $title;
					}

					if (count($segments)>1)
					{
						$segments[2] = str_replace(
							array('datafiles', 'submit_review', 'edit_citation', 'add_citations', 'create', 'request_reopen', 'update', 'delete_resource'), 
							array('data files', 'Review and Submit', 'edit citation', 'new citation', 'new',    'reopen',         'edit',   'delete'), 
						$segments[2]);
						$id          = isset($segments[3]) && is_numeric($segments[3]) ? $segments[3] : '';
						if ($segments[2] != 'projects') {
							$breadcrumbs[] = ucfirst($segments[2]);					
						}
					}
					
			break;

			case 'access_licensed':
					if (is_numeric($segments[2]))
					{
						$breadcrumbs['catalog']=$repository_title;
						$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
						$breadcrumbs['access_licensed/'.$segments[2]]=t('Access to a Licensed Dataset');
					}
					else if ($segments[2]=='by_collection')
					{
						$breadcrumbs['collections']=t('collections');
						$breadcrumbs['collections/'.$segments[3]]=$segments[3];
						$breadcrumbs['access_licensed/by_collection/'.$segments[3]]=t('Access to a Licensed Dataset');
					}
					
					if (isset($segments[3]))
					{
						if ($segments[2]=='track' && is_numeric($segments[3]))
						{
							//get survey id
							$surveyid=$this->get_study_id_by_licensed_request_id($segments[3]);
							
							$breadcrumbs['catalog']=$repository_title;
							$breadcrumbs['catalog/'.$surveyid]=$this->get_study_info($surveyid);
							$breadcrumbs['access_licensed/'.$segments[2]]=t('Request status');
						}
						
						if ($segments[2]=='confirm' && is_numeric($segments[3]))
						{
							//get survey id
							$surveyid=$this->get_study_id_by_licensed_request_id($segments[3]);
							
							$breadcrumbs['catalog']=$repository_title;
							$breadcrumbs['catalog/'.$surveyid]=$this->get_study_info($surveyid);
							$breadcrumbs[]=t('Request confirmation');
						}
					}
					
			break;
			
			case 'citations':
			
				$breadcrumbs['citations']=t('Citations');
				
				//citations by collection
				$collection=$this->ci->input->get('collection',true);
				
				if ($collection)
				{
					$repo_title=$this->get_repository_title($collection);
					
					if($repo_title)
					{
						$breadcrumbs['citations/?collection='.$collection]=$repo_title;
					}				
				}
				
				if (count($segments)>1)
				{
					if (is_numeric($segments[2]))
					{							
						$breadcrumbs['citations/'.$segments[2]]=substr($this->get_citation($segments[2]),0,70).'...';
					}
				}		
			break;
			
			case 'auth';
				if (isset($segments[2]) && ($segments[2]=='profile' || $segments[2]=='edit_profile'))
				{
					$breadcrumbs['auth/profile']=t('profile');
				}
				
				if (isset($segments[2]) && $segments[2]=='change_password')
				{
					$breadcrumbs['auth/change_password']=t('change_password');
				}
				
				if (isset($segments[2]) && $segments[2]=='forgot_password')
				{
					$breadcrumbs['auth/forgot_password']=t('forgot_password');
				}
			break;
			
			case 'collections';	
				$breadcrumbs['collections']=t('collections');
			break;	
			
			case 'admin':
				$this->generate_admin_breadcrumbs($breadcrumbs);
			break;
		}
		
		/*
		//don't show breadcrumbs when there is only one link
		if (count($breadcrumbs)==1)
		{
			return FALSE;
		}
		*/
		return $breadcrumbs;		
	}

	function generate_admin_breadcrumbs(&$breadcrumbs)
	{	
		$breadcrumbs=array();
		$breadcrumbs['admin']=t('Dashboard');
		
		$segments=$this->ci->uri->segment_array();
		
		if (!isset($segments[2]))
		{
			return;
		}
				
		switch ($segments[2])
		{
			case 'catalog':
				$breadcrumbs['admin/catalog']=t('Catalog');
			break;
			
			case 'repositories':
				$breadcrumbs['admin/repositories']=t('Repositories');
			break;
			
			case 'licensed_requests':
				$breadcrumbs['admin/licensed_requests']=t('Licensed Survey Requests');
			break;

		}
		
		if (!isset($segments[3]))
		{
			return;
		}
		
		if ($segments[2]=='catalog')
		{
			switch ($segments[3])
			{
				case 'edit':
					$breadcrumbs['admin/catalog/edit']=t('Edit Survey');
				break;
				
				case 'upload':
					$breadcrumbs['admin/catalog/upload']=t('Upload DDI');
				break;

				case 'batch_import':
					$breadcrumbs['admin/catalog/batch_import']=t('Batch Import');
				break;
				
				case 'transfer':
					$breadcrumbs['admin/catalog/transfer']=t('Transfer Study Ownership');
				break;
				
				case 'delete':
					$breadcrumbs['admin/catalog/delete']=t('Delete Survey');
				break;				
			}
		}
		
		
		if ($segments[2]=='licensed_requests')
		{
			switch ($segments[3])
			{
				case 'edit':
					$breadcrumbs['admin/licensed_requests/edit/'.$segments[4]]=t('Edit Licensed Request');
				break;
			}
		}
		
		if ($segments[2]=='repositories')
		{
			switch ($segments[3])
			{
				case 'edit':
					$breadcrumbs['admin/repositories/edit/'.$segments[4]]=t('Edit Repository');
				break;

				case 'add':
					$breadcrumbs['admin/repositories/add/']=t('Create Repository');
				break;

			}
		}	
	}

	
	function to_string($seperator=" / ")
	{
		$data=array('breadcrumbs'=>$this->to_array());
		return $this->ci->load->view("breadcrumbs",$data,TRUE);
		//return implode($seperator,$this->generate());
	}
	
	function to_array()
	{
		return $this->generate();
	}
	
	//Helper functions
	
	/**
	*
	*Get Study Refno by ID
	*
	**/
	function get_study_info($id)
	{		
		$this->ci->load->model('catalog_model');
		$survey=$this->ci->catalog_model->get_survey($id);
		
		if(!$survey)
		{
			return $id;
		}
		
		return strtoupper($survey['surveyid']);
	}
	
	/**
	*
	* Get Citation by id
	**/
	function get_citation($id)
	{
		$this->ci->load->model('Citation_model');
		$citation=$this->ci->Citation_model->select_single($id);
		
		if(!$citation)
		{
			return $id;
		}
		
		return $citation['title'];
	}
	
	/**
	*
	* Returns the survey info by licensed request id
	**/
	function get_study_id_by_licensed_request_id($id)
	{
		$this->ci->db->select('surveyid');
		$this->ci->db->where('id',$id);
		$query=$this->ci->db->get('lic_requests');
		
		if (!$query)
		{
			return FALSE;
		}
		
		$data=$query->result_array();
		
		if ($data)
		{
			return $data[0]['surveyid'];
		}
		
		return FALSE;
	}
	
	
	/**
	*
	* Returns the repository title by repositoryid
	**/
	function get_repository_title($repositoryid)
	{
		$this->ci->db->flush_cache();
		$this->ci->db->select('title');
		$this->ci->db->where('repositoryid',$repositoryid);
		$query=$this->ci->db->get('repositories');
	
		if (!$query)
		{
			return FALSE;
		}
		
		$repository=$query->row_array();
		
		if($repository)
		{
			return $repository['title'];
		}
		
		return FALSE;
	}
	
	
	/**
	*
	* Returns the repository title by repositoryid
	**/
	function get_data_deposit_project_title($projectid)
	{
		$this->ci->db->select('title');
		$this->ci->db->where('id',$projectid);
		$query=$this->ci->db->get('dd_projects');
		
		if (!$query)
		{
			return FALSE;
		}
		
		$row=$query->row_array();
		
		if($row)
		{
			return $row['title'];
		}
		
		return FALSE;
	}
	
	/**
	*
	* Return repository owning the survey
	**/
	public function get_survey_owner_repo($sid)
	{
		$this->ci->db->select('r.repositoryid,r.title');
		$this->ci->db->join('survey_repos', 'survey_repos.repositoryid= r.repositoryid','inner');		
		$this->ci->db->where('survey_repos.sid',$sid);
		$this->ci->db->where('survey_repos.isadmin',1);
		$query=$this->ci->db->get('repositories r');

		if (!$query)
		{
			return FALSE;
		}
		
		return $query->row_array();
	}

	/**
	*
	* Returns the survey country name
	**/
	function get_survey_country($sid)
	{
		$this->ci->db->select('nation');
		$this->ci->db->where('id',$sid);
		$query=$this->ci->db->get('surveys');
		
		if (!$query)
		{
			return FALSE;
		}
		
		$country=$query->row_array();
		
		if($country)
		{
			return $country['nation'];
		}
		
		return FALSE;
	}
	
}
