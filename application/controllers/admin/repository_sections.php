<?php
class Repository_Sections extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );
       	$this->load->model('Repository_sections_model');
		
		//menu language file
		$this->lang->load('general');
		$this->lang->load('collection');
		
		//set default template
		$this->template->set_template('admin');
		
	}
	
	public function index() {
		//get array of db rows		
		$result['rows']=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('repository_sections/index', $result,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('repository_sections_management'),true);
	  	$this->template->render();	
	}
	
	public function add() {
		$this->edit();
	}
	
	public function edit($id=NULL)	
	{
		$this->html_form_url=site_url().'/admin/repository_sections';		
		
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
		
		//edit page link
		if ($this->input->post("linktype")==1)
		{
			$this->edit_link($id);return;
		}

		//validation rules
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|trim|numeric');
				
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

			//set pid
			if (!is_numeric($options['pid']))
			{
				$options['pid']=0;
			}

															
			if ($id==NULL)
			{
				$options['pid']=0;
				$db_result=$this->Repository_sections_model->insert($options);
			}
			else
			{
				//update db
				$db_result=$this->Repository_sections_model->update($id,$options);
			}
						
			if ($db_result===TRUE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//redirect back to the list
				redirect("admin/repository_sections","refresh");
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
					$obj=$this->Repository_sections_model->select_single($id);
								
					if (!$obj)
					{
						show_error("INVALID ID");
					}
				
					$obj=(object)$obj;
				
				}
		}

		//show form
		$content=$this->load->view('repository_sections/edit',$obj,true);									
				
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();								
	}
	
	
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
				redirect('admin/repository_sections',"refresh");
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
				redirect('admin/repository_sections');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Repository_sections_model->delete($item);
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
				redirect('admin/repository_sections');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}

	
	private function _search() {
		//records to show per page
		$per_page = 15;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'weight';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->Repository_sections_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->Repository_sections_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Repository_sections_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/repository_sections');
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
	
}
	
	
	