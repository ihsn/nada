<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA Site Menu
 * 
 *
 * @category	Site Menu/Navigation
 *
 */ 
class Site_Menu
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Site_Menu Class Initialized.");
		$this->ci =& get_instance();
		
		$this->ci->lang->load('site_menu');
		//$this->ci->output->enable_profiler(TRUE);
		$this->ci->load->config("site_menus");		
	}

	/**
	*
	* return an array of all menu items
	**/
	function get_menu_items_array()
	{
		return $this->ci->config->item("site_menu");
	}

	/**
	* Returns a formatted menu true for site navigation
	*
	**/
	function get_formatted_menu_tree($items=NULL)
	{
		if($items==NULL)
		{
			$items=$this->get_menu_items_array();
		}

		$options['items']=$items;
		$options['collections']=$this->get_collections_menu();
		$content=$this->ci->load->view('admin/site_menu.php',$options,true);
		return $content;		
	}
	
	
	/**
	*
	* Formatted list of collections
	**/
	function get_collections_menu()
	{
		$repos=$this->ci->Repository_model->select_all();

		/*$repos=array();

		//show collections that the active user has access to
		foreach($repos_ as $repo){
			try{
				$this->ci->acl_manager->has_access('study', 'view',null,$repositoryid=$repo['repositoryid']);
				$repos[]=$repo;
			}
			catch(Exception $e){
			}			
		}*/	
		
		//add central collection
		array_unshift($repos, $this->ci->Repository_model->get_central_catalog_array());

		//html formatted list
		return $this->ci->load->view("admin/site_menu_collections",array('collections'=>$repos),true);
	}
	
}

