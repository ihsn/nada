<?php
class Facets extends MY_Controller {
 

	private $data_types=array(		
		'survey',
		'geospatial',
		'document',
		'table',
		'image',
		'video',
		'timeseries',
		'script'
	);

    function __construct() 
    {
        parent::__construct();   
		$this->load->model('Facet_model');
		$this->load->model('Dataset_model');
		$this->template->set_template('admin5');
    	
		$this->lang->load('general');
		$this->acl_manager->has_access_or_die('facets', 'edit');
		//$this->output->enable_profiler(TRUE);	
	}
 
 
	function index()
	{
		$this->load->library("Schema_util");
		$this->load->helper('vite_helper');

		$fields = array();
		foreach ($this->data_types as $type) {
			if ($type === 'geospatial') {
				$fields[$type] = json_decode($this->load->view('facets/geospatial.json', null, true));
			} else {
				$fields[$type] = $this->schema_util->get_schema_elements($type);
			}
		}

		$view_data = array(
			'site_url'          => site_url(),
			'base_url'          => base_url(),
			'api_base_url'      => site_url('api/facets/'),
			'assets_base'       => base_url('frontend/dist/'),
			'csrf_token'        => $this->security->get_csrf_hash(),
			'translations'      => $this->lang->language,
			'data_types'        => $this->data_types,
			'reorder_data_types'=> array('all','microdata','geospatial','document','table','image','video','timeseries','script'),
			'fields'            => $fields,
			'facet'             => null,
		);

		$page = array(
			'title'           => t('Facets'),
			'content'         => $this->load->view('admin/facets/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
	

	function create()
	{
		redirect(site_url('admin/facets') . '#/new');
	}

	function edit($facet_name=null)
	{
		redirect(site_url('admin/facets') . '#/edit/' . rawurlencode($facet_name));
	}

	function reorder()
	{
		redirect(site_url('admin/facets') . '#/reorder');
	}

	function terms($facet_id=null)
	{
		redirect(site_url('admin/facets') . '#/terms/' . rawurlencode($facet_id));
	}

	function indexer()
	{
		redirect(site_url('admin/facets') . '#/indexer');
	}


	function reorder_legacy()
	{
		$options['facets']=$this->Facet_model->select_all();
		$options['data_types']=array(
			'all',
			'microdata',
			'geospatial',
			'document',
			'table',
			'image',
			'video',
			'timeseries',
			'script'
		);

		$facet_sort_options=array();

		foreach($options['data_types'] as $type){
			//get ordering info from config file by data type
			$sort_options=(array)json_decode($this->Configurations_model->get_config_item("facets_".$type));

			//remove keys that are not in facets
			foreach($sort_options as $idx=>$facet_key){
				if(!isset($options['facets'][$facet_key])){
					unset($sort_options[$idx]);
				}
			}

			//disabled facets = all facets - sort_optins
			$facets_disabled=array_diff(array_keys($options['facets']),$sort_options);

			//enabled facets options
			foreach($sort_options as $facet_key){
				$facet_sort_options[$type][$facet_key]=array(
					'enabled'=>true,
					'title'=>$options['facets'][$facet_key]['title']
				);
			}

			//disabled facets
			foreach($facets_disabled as $facet_key){
				$facet_sort_options[$type][$facet_key]=array(
					'enabled'=>false,
					'title'=>$options['facets'][$facet_key]['title']
				);
			}
			
		}

		$options['facets_selection']=$facet_sort_options;		
		$content=$this->load->view('facets/reorder', $options,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}




	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{
		$this->acl_manager->has_access_or_die('facets', 'delete');

		$facet=$this->Facet_model->select_single($id);

		if($facet){
			if ($facet['facet_type']=='core'){
				show_error('Core facets cannot be deleted!');
			}
		}

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
				redirect('admin/menu',"refresh");
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
				redirect('admin/facets');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Facet_model->delete_facet($item);
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
				redirect('admin/facets');
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

	
	
	
}    