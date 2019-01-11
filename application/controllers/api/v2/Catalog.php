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

	private function get_api_user_id()
	{
		if(isset($this->_apiuser) && isset($this->_apiuser->user_id))
		{
			return $this->_apiuser->user_id;
		}

		return false;
	}

	//get study metadata
	function study_metadata_get($sid)
	{
		$this->load->model("Survey_model");
		$metadata=$this->Survey_model->get_metadata($sid);

		$this->set_response($metadata, REST_Controller::HTTP_OK);
	}

	function study_external_resources_get($sid){
		
		$this->load->model("Resource_model");
		$resources=$this->Resource_model->get_resources_by_survey($sid);

		$this->set_response($resources, REST_Controller::HTTP_OK);
	}


	function import_dataset_post()
	{
		$this->load->library('ion_auth');
		$this->load->library('acl');
		$this->load->model("Survey_model");		

		$overwrite=$this->input->post("overwrite")=='yes' ? TRUE : FALSE;
		$repositoryid=$this->input->post("repositoryid");
		//$survey_type='geospatial';
		$dataset_type=$this->input->post("data_type");

		if (!$repositoryid){
			$repositoryid='central';
		}


		if(!$dataset_type){
			throw new Exception("DATASET_TYPE_NOT_SET");
		}

		//user has permissions on the repo or die
		$this->acl->user_has_repository_access($repositoryid,$this->get_api_user_id());
				
		//process form

		$temp_upload_folder=get_catalog_root().'/tmp';
		
		if (!file_exists($temp_upload_folder)){
			@mkdir($temp_upload_folder);
		}
		
		if (!file_exists($temp_upload_folder)){
			show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
		}

		//upload class configurations for DDI
		$config['upload_path'] 	 = $temp_upload_folder;
		$config['overwrite'] 	 = FALSE;
		$config['encrypt_name']	 = TRUE;
		$config['allowed_types'] = 'xml';

		$this->load->library('upload', $config);

		//name of the field for file upload
		$file_field_name='ddifile';
		
		//process uploaded ddi file
		$ddi_upload_result=$this->upload->do_upload($file_field_name);

		$uploaded_ddi_path=NULL;

		//ddi upload failed
		if (!$ddi_upload_result){
			$error = $this->upload->display_errors();
			$this->db_logger->write_log('ddi-upload',$error,'catalog');
			throw new Exception($error);
		}
		else //successful upload
		{
			//get uploaded file information
			$uploaded_ddi_path = $this->upload->data();
			$uploaded_ddi_path=$uploaded_ddi_path['full_path'];
			$this->db_logger->write_log('ddi-upload','success','catalog');
		}

		try{
			$this->load->library('Metadata_import', array(
				'file_type'=>$dataset_type, 
				'file_path'=>$uploaded_ddi_path,
				'repositoryid'=>$repositoryid,
				'published'=>1,
				'user_id'=>$this->get_api_user_id(),
				'formid'=>6,
				'overwrite'=>$overwrite
			));
			$result=$this->metadata_import->import();

			var_dump($result);

			//$result['sid']=$this->metadata_import->get_sid();
			//$result['message']="import successful!";
		}
		catch(Exception $e)
		{
			var_dump( $e->getMessage());
			die("FAILED");
		}

		//upload class configurations for RDF
		$config['upload_path'] = $temp_upload_folder;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload('rdf');

		$uploaded_rdf_path='';

		if ($rdf_upload_result)
		{
			$uploaded_rdf_path = $this->upload->data();
			$uploaded_rdf_path=$uploaded_rdf_path['full_path'];
		}

		if (isset($result['sid']) && $uploaded_rdf_path!="")
		{
			//import rdf
			$this->Survey_model->import_rdf($result['sid'],$uploaded_rdf_path);

			//delete rdf
			@unlink($uploaded_rdf_path);
		}

		var_dump($result);
	}

	public function import_rdf_post($sid=NULL)
	{
		$this->load->model("Survey_model");	

		if(!$sid){
			throw Exception("SID_NOT_SET");
		}
		
		$temp_upload_folder=get_catalog_root().'/tmp';
		
		if (!file_exists($temp_upload_folder)){
			@mkdir($temp_upload_folder);
		}
		
		if (!file_exists($temp_upload_folder)){
			show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
		}
		
		//upload class configurations for RDF
		$config['upload_path'] = $temp_upload_folder;
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->load->library('upload', $config);
		//$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload('rdf');

		$uploaded_rdf_path='';

		if ($rdf_upload_result){
			$uploaded_rdf_path = $this->upload->data();
			$uploaded_rdf_path=$uploaded_rdf_path['full_path'];
		}

		if ($uploaded_rdf_path!=""){
			//import rdf
			$result=$this->Survey_model->import_rdf($sid,$uploaded_rdf_path);

			//delete rdf
			@unlink($uploaded_rdf_path);
		}

		var_dump($result);
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
	
	
	/**
	*
	*	Returns a list of Country Regions
	**/
	function regions_get()
	{
		$this->db->select("id,title");
		$this->db->where("pid >",0);
		$query=$this->db->get("regions");
		
		$rows=NULL;
		
		if ($query)
		{
			$rows=$query->result_array();
			foreach($rows as $key=>$row)
			{
				$rows[$key]['countries']=$this->get_countries_by_region($row['id']);
			}
		}
				
		if (!$rows)
		{
    		$content=array('error'=>'NO_RECORDS_FOUND');    	
		}
		$this->response($rows, 200); 
	}
	
	
	//returns a comma separated list of countries by region 
	private function get_countries_by_region($region_id)
	{
		$this->db->select("country_id");
		$this->db->where("region_id",$region_id);
		$query=$this->db->get("region_countries");

		if (!$query)
		{
			return false;
		}
			
		$rows=$query->result_array();
		
		$output=array();
		
		foreach($rows as $country)
		{
			$output[]=$country['country_id'];
		}
		
		return implode(",",$output);
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
