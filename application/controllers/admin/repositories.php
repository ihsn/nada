<?php
class Repositories extends MY_Controller {

	var $errors='';
	var $search_fields=array('username','email','status'); 
	
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );
       	$this->load->model('repository_model');
		
		//language file
		$this->lang->load('harvester');
		
		//set default template
		$this->template->set_template('admin');
		
		//$this->output->enable_profiler(TRUE);
	}
	
	
	
	//list repositories
	function index()
	{			
		//get array of db rows		
		$result['rows']=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('repositories/index', $result,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('repositories_management'),true);
	  	$this->template->render();	
	}
	
	
	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 **/
	function _search()
	{
		//records to show per page
		$per_page = 15;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'repositoryid';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->repository_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->repository_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->repository_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/menu');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	
	/**
	* Add new repo
	*
	*/
	function add()
	{
			$this->edit();
	}
		
	/**
	* Edit repo
	*
	* handles both add or edit
	*/
	function edit($id=NULL)	
	{
		if (!is_numeric($id)  && $id!=NULL)
		{
			show_error('Invalid id provided');exit;		
		}

		//set validation rules
		$this->form_validation->set_rules('repositoryid', t('repositoryid'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('url', t('url'), 'xss_clean|trim|required|callback__url_check|max_length[255]');
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('organization', t('organization'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('country', t('country'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('scan_interval', t('scan_interval'), 'xss_clean|trim|max_length[3]|is_numeric');
		
		if (is_numeric($id))
		{
			$this->page_title=t('edit_repository');
		}
		else
		{
			$this->page_title=t('create_repository');		
		}	

		//initialize with defaults
		$this->row_data=array(
						'repositoryid'=>'',
						'title'=>'',
						'url'=>'',
						'organization'=>'',
						'country'=>'',
						'scan_interval'=>7,
						'type'=>0,
						'short_text'=>'',
						'thumbnail'=>'',
						'long_text'=>'',
						'weight'=>0
						);
												
		//process form
		if ($this->form_validation->run() == TRUE)
		{		
				$options=array();
				$post_arr=$_POST;
							
				//read post values to pass to db
				foreach($post_arr as $key=>$value)
				{
					$options[$key]=$this->security->xss_clean($this->input->post($key));
				}
												
				if ($id==NULL)
				{
					$db_result=$this->repository_model->insert($options);
				}
				else
				{
					//update db
					$db_result=$this->repository_model->update($id,$options);
				}
							
				if ($db_result===TRUE)
				{
					//update successful
					$this->session->set_flashdata('message', t('form_update_success'));
					
					//redirect back to the list
					redirect("admin/repositories","refresh");
				}
				else
				{
					//update failed
					$this->form_validation->set_error(t('form_update_fail'));
				}

		}
		
		else //first time page is loaded
		{
				if ($id!=NULL)
				{
					$row=$this->repository_model->select_single($id);
					
					if(!$row)
					{
						show_error('ID was not found');
					} 	
					
					$this->row_data=$row;	
				}
		}			

		//textboxes
		$fields=array('repositoryid','title','url','organization','country','scan_interval','thumbnail','weight');
		
		foreach($fields as $field)
		{
				$this->data[$field]= array(
							'name'	=> $field,
							'id'    => $field,
							'type'  => 'text',
							'class' => 'input-flex',
							'value' => $this->form_validation->set_value($field,$this->row_data[$field]));
		}
		
		$this->data['type']=$this->form_validation->set_value('type',$this->row_data['type']);
		$this->data['short_text']=$this->form_validation->set_value('short_text',$this->row_data['short_text']);
		$this->data['long_text']=$this->form_validation->set_value('long_text',$this->row_data['long_text']);
		$this->data['ispublished']=$this->form_validation->set_value('ispublished',$this->row_data['ispublished']);
		
		//show form
		$content=$this->load->view('repositories/edit',NULL,true);									
				
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();								
	}
	
	
	/**
	* check if URL already exists in the database
	*
	*/
	function _url_check($url)
	{
		$id=$this->uri->segment(4);

		if (!is_numeric($id) )
		{
			$id=NULL;
		}
		
		//only apply to page links, ignore the static pages
		if ($this->input->post("linktype")==0)
		{
			//replaces spaces with dashes
			$url=url_title($url, 'dash', TRUE);
		}
				
		$exists=$this->Menu_model->url_exists($url,$id);
				
		if ($exists >0)
		{
			$this->form_validation->set_message('_url_check', t('callback_error_url_exists'));
			return FALSE;
		}
			return TRUE;
	}
	
	
	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{		
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/repositories',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/repositories');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->repository_model->delete($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/repositories');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('repositories/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	
	/**
	*
	* Scan a repository URL and test the catalog
	*
	*/
	function scan($id=NULL)
	{
		if (!is_numeric($id))
		{
			$this->db_logger->write_log('harvester','404','scan',$id);
			show_404();
		}
		
		//data repository info
		$repo=$this->repository_model->select_single($id);
		
		if (!$repo)
		{
			$this->db_logger->write_log('harvester','404','ID-NOT-FOUND',$id);
			show_error('Repository ID not found');
		}
		
		$repo=(object)$repo;
		$url=$repo->url.'/index.php/catalog/rss?limit=5000';
		$url=str_replace("//index.php","/index.php",$url);
		
		//log
		$this->db_logger->write_log('harvester',$url,'url-to-scan',$id);
		
		$this->load->library('simplepie');
		//set cache path from the CI config
		$this->simplepie->cache_location=$this->config->item("cache_path");
		
		//get feed
		$this->simplepie->set_feed_url($url);
		$this->simplepie->set_timeout(20);//time to wait for the feed
		$this->simplepie->enable_cache(false);//disable caching
		//$this->simplepie->cache_max_minutes(5);//max cache time
		$success=$this->simplepie->init();
		$feed= $this->simplepie;
		
		$total=$feed->get_item_quantity();

		//log
		$this->db_logger->write_log('harvester',$total,'scan-result',$id);

		//add surveys to queue for later download
		foreach($feed->get_items() as $item)
		{
			$item=(object)$item;
			
			//get country name
			$country='';
			$country_tag=$item->get_item_tags('http://ihsn.org/nada/', 'country');
			if (isset($country_tag[0]['data']))
			{
				$country=trim($country_tag[0]['data']);
			}

			//get survey data collection date
			$colldate='';
			$colldate_tag=$item->get_item_tags('http://ihsn.org/nada/', 'colldate');
			if (isset($colldate_tag[0]['data']))
			{
				$colldate=trim($colldate_tag[0]['data']);
			}
			
			//get survey accesspolicy
			$accesspolicy='';
			$accesspolicy_tag=$item->get_item_tags('http://ihsn.org/nada/', 'accesspolicy');
			if (isset($accesspolicy_tag[0]['data']))
			{
				$accesspolicy=trim($accesspolicy_tag[0]['data']);
			}
			
			//get surveyid
			$surveyid='';
			$surveyid_tag=$item->get_item_tags('http://ihsn.org/nada/', 'surveyid');
			if (isset($surveyid_tag[0]['data']))
			{
				$surveyid=trim($surveyid_tag[0]['data']);
			}
			
			$options=array(
						'title'=>$item->get_title(),
						'survey_url'=>$item->get_permalink(),
						'survey_timestamp'=>$item->get_date("U"),
						'status'=>'new',
						'country'=>$country,
						'survey_year'=>$colldate,
						'accesspolicy'=>$accesspolicy,
						'surveyid'=>$surveyid
							);
							
			//update queueu entries											
			$this->repository_model->update_queue($repo->repositoryid, $options);
		}
		
		//update repository stats
		$repo_options=array(
					'scan_lastrun'=>date("U"),
					'status'=>$total);
				
		$this->repository_model->update($id,$repo_options);
		
		//get newly fetched survey list
		$surveys_array['rows']=$this->repository_model->get_surveys_by_repository($repo->repositoryid);
		
		//formatted list of entries
		$content=$this->load->view('harvester/scan',$surveys_array,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->render();
	}

	
	/**
	*
	* Manage users for a repository
	*
	* @id= repositoryid
	**/
	function users($id=NULL)
	{
			show_error("feature no longer supported");
/*		if (!is_numeric($id))
		{
			show_error("INVALID_ID");
		}
*/
		//get all repos
		$repos=$this->repository_model->get_repositories();
		
		//get repository info from db
		$repo=$this->repository_model->select_single($id);
		
		if (!$repo)
		{
			//show_error("NOT-FOUND");
			$repo=current($repos);
		}
		
		//get a list of all catalog-admins
		$users=$this->ion_auth_model->get_admin_users('catalog-admin');
		//$users=$this->repository_model->get_catalog_admins($id);
				
		//get user for the current repository
		$repo_users=$this->repository_model->get_repository_admins($repo['id']);

		$repo_user_list=array();
		foreach($repo_users as $row)
		{
			$repo_user_list[$row['userid']]=$row['roleid'];
		}

		$data['repo']=$repo;
		$data['users']=$users;
		$data['repos']=$repos;
		$data['repo_admins']=$repo_user_list;
		
		$this->page_title=t('manage_repository_users');
	
		$content= $this->load->view('repositories/manage_users',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	
	
	function assign_role($repositoryid,$userid,$roleid)
	{
		$this->repository_model->assign_role($repositoryid,$userid,$roleid);
		echo json_encode(array('success'=>"role updated"));
	}

	/**
	* select active repository for the current session
	**/
	function select()
	{
		$this->lang->load('harvester');
		$this->page_title=t('select_active_repository');
		$data['repos']=$this->acl->user_repositories();
		$content=$this->load->view('repositories/active_repo',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}

	/**
	*
	*Set active repository for the user session
	**/
	function active($repositoryid=NULL)
	{
		if (!is_numeric($repositoryid))
		{
			show_error("INVALID_ID");
		}
		
		$result=$this->acl->set_active_repo($repositoryid);
		
		if ($result)
		{
			redirect("admin/catalog");
		}
		
		show_error("CANT_SET_ACTIVE_REPO");
	}
	
	/**
	* Clear active repo
	**/
	function reset_repo()
	{
		$this->acl->clear_active_repo();
	}
}

/* End of file repositories.php */
/* Location: ./system/application/controllers/repositories.php */