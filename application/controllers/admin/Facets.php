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
			'api_base_url'      => site_url('api/admin/facets/'),
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


	/**
	 * Retired — use Vue facets admin reorder.
	 */
	function reorder_legacy()
	{
		show_404();
	}




	/**
	 * Retired — use api/admin/facets (Vue facets admin).
	 */
	function delete($id)
	{
		show_404();
	}

	
	
	
}    