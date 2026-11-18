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
	public $ci;

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
		$this->ci->load->helper('datadeposit');
	}

	/**
	*
	* return an array of all menu items
	**/
	function get_menu_items_array()
	{
		$items = $this->ci->config->item("site_menu");
		if (!is_array($items)) {
			return array();
		}

		$hidden = array();
		if (!datadeposit_is_enabled()) {
			$hidden[] = 'admin/datadeposit';
		}
		if (!$this->nada_ai_enabled()) {
			$hidden[] = 'admin/search-index';
		}
		if (!$this->is_site_admin()) {
			$hidden[] = 'admin/permissions';
		}
		if (empty($hidden)) {
			return $items;
		}

		$filtered = array();
		foreach ($items as $item) {
			if (isset($item['url']) && in_array($item['url'], $hidden, true)) {
				continue;
			}
			if (isset($item['items']) && is_array($item['items'])) {
				$item['items'] = $this->remove_sub_items($item['items'], $hidden);
			}
			$filtered[] = $item;
		}
		return $filtered;
	}

	/**
	 * Whether NADA-AI is set as the search engine or the search index engine (the index can be built before
	 * search is switched to it).
	 */
	private function nada_ai_enabled()
	{
		$this->ci->load->library('search_engine_resolver');
		$resolver = $this->ci->search_engine_resolver;

		return $resolver->uses_nada_ai() || $resolver->index_engine() === Search_engine_resolver::NADA_AI;
	}

	/**
	 * The roles page (admin/permissions) is limited to site admins.
	 */
	private function is_site_admin()
	{
		$this->ci->load->library('acl_manager');
		try {
			return $this->ci->acl_manager->user_is_admin();
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Drop sub-items whose url is in $hidden, along with the divider that followed each one, so no
	 * doubled dividers are left behind.
	 */
	private function remove_sub_items(array $sub_items, array $hidden)
	{
		$result = array();
		$skip_divider = false;
		foreach ($sub_items as $sub) {
			$is_divider = isset($sub['type']) && $sub['type'] === 'divider';
			if ($is_divider && $skip_divider) {
				$skip_divider = false;
				continue;
			}
			$skip_divider = false;
			if (!$is_divider && isset($sub['url']) && in_array($sub['url'], $hidden, true)) {
				$skip_divider = true;
				continue;
			}
			$result[] = $sub;
		}
		return $result;
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

	/**
	 * Site admin Vue header: translated nav tree with full URLs (JSON-serializable).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function get_admin_nav_items()
	{
		$this->ci->lang->load('site_menu');
		$items = $this->get_menu_items_array();
		$nav = array();
		foreach ($items as $item) {
			if (!isset($item['items'])) {
				$nav[] = array(
					'kind' => 'link',
					'title' => t($item['title']),
					'href' => site_url($item['url']),
				);
			} else {
				$children = array();
				foreach ($item['items'] as $sub) {
					if (isset($sub['type']) && $sub['type'] === 'divider') {
						$children[] = array('kind' => 'divider');
						continue;
					}
					$children[] = array(
						'kind' => 'link',
						'title' => t($sub['title']),
						'href' => site_url($sub['url']),
					);
				}
				$nav[] = array(
					'kind' => 'menu',
					'title' => t($item['title']),
					'href' => site_url($item['url']),
					'children' => $children,
				);
			}
		}
		return $nav;
	}

	/**
	 * Full payload for the Vue 3 admin app bar (window.ADMIN_HEADER_CONFIG).
	 *
	 * @return array<string, mixed>
	 */
	function get_admin_header_config()
	{
		$this->ci->lang->load('general');
		$this->ci->lang->load('site_menu');
		return array(
			'siteUrl' => site_url(),
			'appVersion' => defined('APP_VERSION') ? APP_VERSION : '',
			'headerBackground' => (string) ($this->ci->config->item('admin_header_background') ? $this->ci->config->item('admin_header_background') : '#212121'),
			'nav' => $this->get_admin_nav_items(),
			'user' => array(
				'name' => strtoupper($this->ci->session->userdata('username') ? $this->ci->session->userdata('username') : ''),
			),
			'urls' => array(
				'changePassword' => site_url('auth/change_password'),
				'logout' => site_url('auth/logout'),
				'home' => site_url(),
				'dataCatalog' => site_url('catalog'),
				'citations' => site_url('citations'),
				'adminHome' => site_url('admin'),
			),
			'labels' => array(
				'changePassword' => t('change_password'),
				'logout' => t('logout'),
				'home' => t('home'),
				'dataCatalog' => t('data_catalog'),
				'citations' => t('citations'),
			),
		);
	}
	
}

