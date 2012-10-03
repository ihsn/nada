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
		
		switch($segments[1])
		{
				case 'catalog':			
					$breadcrumbs['catalog']=t('Data Catalog');
					
					if (count($segments)>1)
					{
						if (is_numeric($segments[2]))
						{							
							$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
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
								$breadcrumbs['catalog/'.$segments[2].'/reports']=t('access_policy');
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
						
						if (isset($segments[3]) && ($segments[3]=='datafile' || $segments[3]=='datafiles'))
						{
							$breadcrumbs['catalog/'.$segments[2].'/datafiles']=t('Datafiles');
							
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
					$breadcrumbs['catalog']=t('Data Catalog');
					$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
					$breadcrumbs['access_direct/'.$segments[2]]=t('Direct Data Access');
			break;

			case 'access_public':
					$breadcrumbs['catalog']=t('Data Catalog');
					$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
					$breadcrumbs['access_public/'.$segments[2]]=t('Public Use Dataset');
			break;

			case 'access_licensed':
					if (is_numeric($segments[2]))
					{
						$breadcrumbs['catalog']=t('Data Catalog');
						$breadcrumbs['catalog/'.$segments[2]]=$this->get_study_info($segments[2]);
						$breadcrumbs['access_licensed/'.$segments[2]]=t('Access to a Licensed Dataset');
					}
					
					if (isset($segments[3]))
					{
						if ($segments[2]=='track' && is_numeric($segments[3]))
						{
							//get survey id
							$surveyid=$this->get_study_id_by_licensed_request_id($segments[3]);
							
							$breadcrumbs['catalog']=t('Data Catalog');
							$breadcrumbs['catalog/'.$surveyid]=$this->get_study_info($surveyid);
							$breadcrumbs['access_licensed/'.$segments[2]]=t('Request status');
						}
						
						if ($segments[2]=='confirm' && is_numeric($segments[3]))
						{
							//get survey id
							$surveyid=$this->get_study_id_by_licensed_request_id($segments[3]);
							
							$breadcrumbs['catalog']=t('Data Catalog');
							$breadcrumbs['catalog/'.$surveyid]=$this->get_study_info($surveyid);
							$breadcrumbs[]=t('Request confirmation');
						}
					}
					
			break;
			
			case 'datadeposit':
					$breadcrumbs['datadeposit/projects']=t('datadeposit');
					if (count($segments)>1)
					{
						$segments[2] = str_replace(array('create', 'update', 'delete_resource'), array('new', 'edit', 'delete'), $segments[2]);
						$id          = isset($segments[3]) && is_numeric($segments[3]) ? $segments[3] : '';
						$breadcrumbs['datadeposit/'.$segments[2].'/'. $id] = ucfirst($segments[2]);						
					}
			break;
			
			case 'citations';	
				$breadcrumbs['citations']=t('Citations');
				if (count($segments)>1)
				{
					if (is_numeric($segments[2]))
					{							
						$breadcrumbs['citations/'.$segments[2]]=$this->get_citation($segments[2]);
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
			
		}
		
		if (count($breadcrumbs)==1)
		{
			return FALSE;
		}
		return $breadcrumbs;		
	}
	
	function to_string($seperator=" / ")
	{
		$data=array('breadcrumbs'=>$this->to_array());
		$this->ci->load->view("breadcrumbs",$data);
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
}
