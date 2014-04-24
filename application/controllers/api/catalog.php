<?php

require(APPPATH.'/libraries/REST_Controller.php');

class Catalog extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		//$this->load->database();	
		//$this->load->model('menu_model');
	}
	
	function search_get()
	{
		$params=array(
				'study_keywords'	=>	$this->security->xss_clean($this->input->get("sk")),
				'variable_keywords'	=>	$this->security->xss_clean($this->input->get("vk")),
				'variable_fields'	=>	array('name','labl'),
				'countries'			=>	$this->security->xss_clean($this->input->get("country")),
				//'topics'			=>	array('1','2'),
				'from'				=>	$this->security->xss_clean($this->input->get("from")),
				'to'				=>	$this->security->xss_clean($this->input->get("to")),
				'collection'		=>	$this->security->xss_clean($this->input->get("collection")),
				'dtype'				=>	$this->security->xss_clean($this->input->get("dtype")),
		);
		
		$this->db_logger->write_log($log_type='api-search',$log_message=http_build_query($params),$log_section='api-search-v1',$log_survey=0);
		
		//covert countries names to country ID
		$params['countries']=$this->country_name_to_id($params['countries']);
		
		$limit=5;
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);
		$content=$this->catalog_search->search($limit,$offset);
		$this->response($content, 200); 
	}

	function accesspolicy_get()
	{
        $id=$this->get('id');
		
		if(!$id)
        {
        	$this->response(NULL, 400);
        }

		$this->load->model('Catalog_model'); 	
		$this->load->library('DDI_Browser','','DDI_Browser');
		
		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);
		
		//survey folder path
		$this->survey_folder=$this->Catalog_model->get_survey_path_full($id);
		
		if ($ddi_file===FALSE)
		{
			show_error(t('file_not_found'));
			return;
		}
		
		$html=$this->DDI_Browser->get_access_policy_html($ddi_file);
		$this->response($html, 200); 
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
		$this->db->select("nation,count(*) as found");
		$this->db->group_by("nation");
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
		$this->response($content, 200); 
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
}
