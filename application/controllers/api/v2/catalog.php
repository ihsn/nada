<?php

require(APPPATH.'/libraries/REST_Controller.php');

class Catalog extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Catalog_model'); 	
		$this->load->library('DDI_Browser','','DDI_Browser');
	}
	
	function search_get()
	{
		$params=array(
				'study_keywords'	=>	$this->security->xss_clean($this->input->get("sk")),
				'variable_keywords'	=>	$this->security->xss_clean($this->input->get("vk")),
				'variable_fields'	=>	array('name','labl'),
				'countries'			=>	$this->security->xss_clean($this->input->get("country")),
				'from'				=>	$this->security->xss_clean($this->input->get("from")),
				'to'				=>	$this->security->xss_clean($this->input->get("to")),
				'collections'		=>	$this->security->xss_clean($this->input->get("collection")),
				'dtype'				=>	$this->security->xss_clean($this->input->get("dtype")),
				'repo'				=>	$this->security->xss_clean($this->input->get("repo")),
		);
		
		$this->db_logger->write_log($log_type='api-search',$log_message=http_build_query($params),$log_section='api-search-v1',$log_survey=0);
		
		//covert countries names to country ID
		$params['countries']=$this->country_name_to_id($params['countries']);
		
		//search countries by iso
		$countries_by_iso=$this->security->xss_clean($this->input->get("iso"));
		
		if ($countries_by_iso)
		{
			$params['countries']=$this->country_iso_to_id($countries_by_iso);
		}
		
		
		$limit=5;
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);
		$content=$this->catalog_search->search($limit,$offset);		
		$this->response($content, 200); 
	}

	
	
	/**
	*
	* Returns the most recent studies
	*
	* @country	string	filter by single country name
	* @order	bit		order by date created 0=desc;1=asc
	**/
	function latest_get()
	{
		$country=$this->get("country");
		$limit=(int)$this->get("limit");
		
		if (!$limit>0 && !$limit<=20 )
		{
			$limit=5;
		}
		
		if ($country)
		{
			$this->db->where("nation",$country);
		}
		
		$this->db->select("id,refno,titl,nation,authenty,created");
		$this->db->where("published",1);
		$this->db->limit($limit);
		$this->db->order_by("created","desc");
		
		$query=$this->db->get("surveys");
		$content=NULL;
		
		if ($query)
		{
			$content=$query->result_array();
		}
				
		if (!$content)
		{
    		$content=array('error'=>'NO_RECORDS_FOUND');    	
		}
		else
		{
			foreach($content as $key=>$value)
			{
				$content[$key]['url']=site_url().'/catalog/'.$value['id'];
				$content[$key]['created']=date("M-d-Y",$value["created"]);
			}		
		}
		$this->response($content, 200); 
	}

	
	/**
	*
	* Returns all country names from db
	*
	**/
	function countries_get()
	{
		$this->db->select("countries.countryid,name,iso,count(*) as found");
		$this->db->join('survey_countries','survey_countries.cid=countries.countryid');
		$this->db->group_by("countries.countryid,countries.iso,countries.name");
		$query=$this->db->get("countries");
		$content=NULL;
		
		if ($query)
		{
			$content=$query->result_array();
		}
				
		if (!$content)
		{
    		$content=array('error'=>'NO_RECORDS_FOUND');    	
		}
		$this->response($content, 200); 
	}
	
	
	
	
	/**
	*
	*Returns a list of published collections
	**/
	function collections_get()
	{
		
	}
	
	
	function _remap($method)
	{
		$method=strtolower($method);
        
		if (in_array(($method.'_get'), array_map('strtolower', get_class_methods($this))) ) 
		{
            $uri = $this->uri->segment_array();
            unset($uri[1]);
            unset($uri[2]);
            call_user_func_array(array($this, $method.'_get'), $uri);return;
        }
				
		$id=$this->get('id');
		
		if(is_numeric($id))
        {
        	$this->_get_study_part($method);
        }
		
		
	}
	
	//e.g. catalog/sampling?id=[std
	private function _get_study_part($method)
	{
		$id=$this->get('id');
		
		if(!is_numeric($id))
        {
        	$this->response(NULL, 400);
        }
		
		$study=$this->Catalog_model->select_single($id);
		
		if(!$study)
		{
			$this->response(array('error'=>'NOT-FOUND'),404);exit;
		}
		
		//unpublished studies
		if(isset($study['published']) && intval($study['published'])===0)
		{
			$this->response(array('error'=>'CONTENT-NOT-AVAILABLE'),404);exit;
		}
		

		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);
		
		if ($ddi_file===FALSE)
		{
			$this->response(array('error'=>'NOT-FOUND'),404);exit;
		}
	
		$html='';

		switch($method)
		{
			case 'questionnaires':
				$html=$this->DDI_Browser->get_questionnaires_html($ddi_file);
			break;
			
			case 'sampling':
				$html=$this->DDI_Browser->get_sampling_html($ddi_file);
			break;
			
			case 'overview':
				$html=$this->DDI_Browser->get_overview_html($ddi_file);
			break;
			
			case 'accesspolicy':
				$html=$this->DDI_Browser->get_accesspolicy_html($ddi_file);
			break;
			
			case 'dataprocessing':
				$html=$this->DDI_Browser->get_dataprocessing_html($ddi_file);
			break;
			
			case 'datacollection':
				$html=$this->DDI_Browser->get_datacollection_html($ddi_file);
			break;
			
			case 'dataappraisal':
				$html=$this->DDI_Browser->get_dataappraisal_html($ddi_file);
			break;
			
			case 'technicaldocuments':
				$html=$this->DDI_Browser->get_technicaldocuments_html($ddi_file);
			break;
			
			/*
			case 'datafiles':
				$html=$this->DDI_Browser->get_datafiles_html($ddi_file);
			break;
			
			case 'datafile':
				$html=$this->DDI_Browser->get_datafile_html($ddi_file);
			break;
			
			case 'variables_by_group':
				$html=$this->DDI_Browser->get_variables_by_group_html($ddi_file);
			break;
			
			case 'variable':
				$html=$this->DDI_Browser->get_variable_html($ddi_file);
			break;		
			*/
			
			case 'ddi':
				$this->DDI_Browser->download_ddi($ddi_file);exit;
			break;
			
			case 'rdf':
				$this->Catalog_model->increment_study_download_count($id);
		
				header("Content-Type: application/xml");
				header('Content-Encoding: UTF-8');
				header( "Content-Disposition: attachment;filename=study-$id.rdf");

				echo $this->Catalog_model->get_survey_rdf($id);exit;

			break;
		
			case 'index':
			case 'info':
				$study_fields=array('id','repositoryid','surveyid','titl','authenty','geogcover','nation','producer','sponsor','link_indicator','link_study','data_coll_start','data_coll_end','link_da','published','created','changed','varcount','total_views','total_downloads','model','repo','country');
				$study_output=array();
				foreach($study as $key=>$value)
				{
					if(in_array($key,$study_fields))
					{
						$study_output[$key]=$value;
					}
				}
				$html=$study_output;
			break;
		
			default:
				$this->response(array('error'=>'NOT-FOUND'),404);exit;
			break;			
		}
		
		$this->response($html, 200); 
	}
	

	//convert country names to country IDs
	private function country_name_to_id($country_names)
	{
		$this->db->select('countryid');
		$this->db->where_in('name',$country_names);
		$result=$this->db->get('countries')->result_array();
		
		$output=array();
		
		if ($result)
		{
			foreach($result as $row)
			{
				$output[]=$row['countryid'];
			}
		}
		
		return $output;
	}

	//convert country ISO code to country IDs
	private function country_iso_to_id($country_iso)
	{
		$this->db->select('countryid');
		$this->db->where_in('iso',$country_iso);
		$result=$this->db->get('countries')->result_array();
		
		$output=array();
		
		if ($result)
		{
			foreach($result as $row)
			{
				$output[]=$row['countryid'];
			}
		}
		
		return $output;
	}
	
}
