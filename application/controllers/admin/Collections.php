<?php
/**
 * Catalog Maintenance Controller
 *
 * handles all Catalog Maintenance pages
 *
 */
class Collections extends MY_Controller {

  	public function __construct()
	{
      	parent::__construct();
		$this->load->model('Repository_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		//$this->load->helper("catalog");
		$this->template->set_template('admin');

		//load language file
		$this->lang->load('general');
		//$this->output->enable_profiler(TRUE);
	}



	/**
	 * Default page
	 *
	 */
	function index()
	{
		$this->template->set_template('admin5');

		$this->_require_admin_catalog_access();

		// Vue collections page: access is enforced via api/admin/collections and scope; no active_repo.
		// assets_base is the URL base for frontend/dist (from PHP base_url)
		$this->load->helper('vite_helper');
		$collections_view_data = [
			'api_base_url' => site_url('api/admin/collections/'),
			'site_url' => site_url(),
			'base_url' => base_url(),
			'csrf_token' => $this->security->get_csrf_hash(),
			'assets_base' => base_url('frontend/dist/'),
			'translations' => $this->lang->language //all loaded language strings
		];

		$content = $this->load->view('admin/collections/index', $collections_view_data, true);
		$this->template->write('content', $content, true);
		$this->template->render();
	}

	/**
	 * Set active repository for the user session (cookie) then redirect.
	 * Mirrors Repositories::active() so links can point to admin/collections/active/{id}.
	 */
	function active($repositoryid=NULL)
	{
		if (!is_numeric($repositoryid)){
			show_error("INVALID_ID");
		}

		$result = $this->Repository_model->set_active_repo($repositoryid);

		if ($result){
			if ($this->input->get('destination')){
				redirect($this->input->get('destination', true)); return;
			}
			redirect('admin/catalog');
		}

		show_error("CANT_SET_ACTIVE_REPO");
	}

	/**
	 * Clear active repository cookie.
	 */
	function reset_repo()
	{
		$this->Repository_model->clear_active_repo();
	}

}
/* End of file collections.php */
/* Location: ./controllers/admin/collections.php */
