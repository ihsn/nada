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
		$data['rows']=$this->Facet_model->select_terms_counts_detailed();		
		$content=$this->load->view('facets/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}
	

	function create()
	{
		$this->load->library("Schema_util");

		$data_types=$this->data_types;
		$data=array();
		$data['data_types']=$data_types;

		foreach($data_types as $type){
			if ($type=='geospatial'){
				$data['fields'][$type]=json_decode($this->load->view('facets/geospatial.json',null,true));
			}else{
				$data['fields'][$type]=$this->schema_util->get_schema_elements($type);
			}
		}

		$content=$this->load->view('facets/create_vue', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}

	function edit($facet_name=null)
	{
		$this->load->library("Schema_util");

		$data_types=$this->data_types;
		$data=array();
		$data['data_types']=$data_types;

		foreach($data_types as $type){
			if ($type=='geospatial'){
				$data['fields'][$type]=json_decode($this->load->view('facets/geospatial.json',null,true));
			}else{
				$data['fields'][$type]=$this->schema_util->get_schema_elements($type);
			}
		}

		$data['facet']=$this->Facet_model->get_facet_by_name($facet_name);

		if($data['facet']){
			if ($data['facet']['facet_type']=='core'){
				show_error('Core facets cannot be edited!');
			}
		}

		$content=$this->load->view('facets/create_vue', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}



	function reorder()
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


	function terms($facet_id=null)
	{
		$facet=$this->Facet_model->select_single($facet_id);
		
		if(empty($facet)){
			show_error("FACET not found");
		}
		
		$data['facet']=$facet;
		$data['rows']=$this->Facet_model->get_facet_terms($facet_id);
		
		$content=$this->load->view('facets/terms', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Facets'),true);
	  	$this->template->render();
	}


	function indexer()
	{
		$data['rows']=$this->Facet_model->select_term_value_counts($facet_type='user');
		$data['studies_count']=$this->Dataset_model->get_total_count();
		$content=$this->load->view('facets/indexer', $data,TRUE);
		
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
		$this->acl_manager->has_access_or_die('facet', 'delete');

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