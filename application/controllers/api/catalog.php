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
		);
		
		$limit=5;
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);

		$content=$this->catalog_search->search($limit,$offset);
		
		//var_dump($result);
		//return;
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

/*
	function user_get()
    {
        if(!$this->get('id'))
        {
        	$this->response(NULL, 400);
        }

        // $user = $this->some_model->getSomething( $this->get('id') );
    	$users = array(
			1 => array('id' => 1, 'name' => 'Some Guy', 'email' => 'example1@example.com', 'fact' => 'Loves swimming'),
			2 => array('id' => 2, 'name' => 'Person Face', 'email' => 'example2@example.com', 'fact' => 'Has a huge face'),
			3 => array('id' => 3, 'name' => 'Scotty', 'email' => 'example3@example.com', 'fact' => 'Is a Scott!'),
		);
		
    	$user = @$users[$this->get('id')];
    	
        if($user)
        {
            $this->response($user, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'User could not be found'), 404);
        }
    }
    
    function user_post()
    {
        //$this->some_model->updateUser( $this->get('id') );
        $message = array('id' => $this->get('id'), 'name' => $this->post('name'), 'email' => $this->post('email'), 'message' => 'ADDED!');
        
        $this->response($message, 200); // 200 being the HTTP response code
    }
    
    function user_delete()
    {
    	//$this->some_model->deletesomething( $this->get('id') );
        $message = array('id' => $this->get('id'), 'message' => 'DELETED!');
        
        $this->response($message, 200); // 200 being the HTTP response code
    }
    
    function users_get()
    {
        //$users = $this->some_model->getSomething( $this->get('limit') );
        $users = array(
			array('id' => 1, 'name' => 'Some Guy', 'email' => 'example1@example.com'),
			array('id' => 2, 'name' => 'Person Face', 'email' => 'example2@example.com'),
			array('id' => 3, 'name' => 'Scotty', 'email' => 'example3@example.com'),
		);
        
        if($users)
        {
            $this->response($users, 200); // 200 being the HTTP response code
        }

        else
        {
            $this->response(array('error' => 'Couldn\'t find any users!'), 404);
        }
    }
  */  
}

?>