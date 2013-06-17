<?php
/**
 * Study Collections
 *
 * handles all study collection related 
 *
 * @package		NADA 4
 * @author		Mehmood Asghar
 * @link		http://ihsn.org/nada/
 */
class Da_Collections extends MY_Controller {

    public function __construct()
    {
        parent::__construct();		
       	$this->load->library('Bulk_data_access');
		$this->load->helper('form');
		$this->template->set_template('admin');
		
		//load language file
		$this->lang->load('general');
		$this->lang->load('da_collection');
		//$this->output->enable_profiler(TRUE);
	}
 
	function index()
	{
		$data['rows']=$this->bulk_data_access->select_all();
		$content=$this->load->view('da_collections/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Bulk_da_collections'),true);
	  	$this->template->render();
	}

	public function add() {
		$this->edit();
	}
	
	public function edit($id=NULL)	
	{
		$this->html_form_url=site_url().'/admin/da_collections';		
		
		if (!is_numeric($id)  && $id!=NULL)
		{
			show_error('INVALID ID');
		}
		
		if (is_numeric($id))
		{
			$this->html_form_url.='/edit/'.$id;
		}
		else
		{
			$this->html_form_url.='/add';
		}
		
		$obj=NULL;
		$content=NULL;
		
		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('description', t('description'), 'xss_clean|trim|max_length[600]');
				
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array();
			$post_arr=$_POST;
						
			//read post values to pass to db
			foreach($post_arr as $key=>$value)
			{
				$options[$key]=$this->input->post($key);
			}					

															
			if ($id==NULL)
			{
				$db_result=$this->bulk_data_access->insert($options);
			}
			else
			{
				//update db
				$db_result=$this->bulk_data_access->update($id,$options);
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/da_collections","refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
		else //loading form the first time
		{
				if ( is_numeric($id) )
				{
					//get menu from db
					$obj=$this->bulk_data_access->get_collection($id);
								
					if (!$obj)
					{
						show_error("INVALID ID");
					}
				
					$obj=(object)$obj;
				
				}
		}

		//show form
		$content=$this->load->view('da_collections/edit',$obj,true);									
				
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();								
	}
	
	
	function attach_studies()
	{		
		$this->load->model('Catalog_model');
		$this->load->model('Catalog_admin_search_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');

		$this->template->add_css('themes/admin/catalog_admin.css');
		$this->template->add_js('var site_url="'.site_url().'";','embed');
		
		if (isset($this->active_repo) && $this->active_repo->repositoryid=='central')
		{
			show_error('ACTION_NOT_ALLOWED');
		}
		
		/*
		//set filter on active repo
		if (isset($this->active_repo) && $this->active_repo!=null)
		{
			//$filter=$this->Catalog_model->filter;
			$this->Catalog_model->active_repo=$this->active_repo->repositoryid;
			$this->Catalog_model->active_repo_negate=TRUE;
		}
		*/
		
		$ps=(int)$this->input->get("ps");
		if($ps==0 || $ps>500)
		{
			$ps=15;
		}

		$per_page=(int)$this->input->get("per_page");		
		$total=$this->Catalog_model->search_count();
		$db_rows['rows']=$this->Catalog_model->search($limit = $ps, $offset = $per_page);

		/*if ($curr_page>$total)
		{
			$curr_page=$total-$per_page;
			
			//search again
			$data['rows']=$this->Catalog_admin_search_model->search($search_options,$per_page,$curr_page, $filter);
		}*/
		
		//set pagination options
		$base_url = site_url('admin/catalog/copy_study');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $ps;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('sort_by','sort_order','keywords', 'field','ps'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 

		//get an array of survey ID that are already linked in the active collection
		$db_rows['linked_studies']=array();//$this->Repository_model->get_repo_linked_studies($this->active_repo->repositoryid);
		
		//load the contents of the page into a variable
		$content=$this->load->view('da_collections/attach_studies', $db_rows,true);
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}


}
/* End of file da_collections.php */
/* Location: ./controllers/admin/da_collections.php */