<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Admin Catalog API Controller
 *
 * Provides REST endpoints for the admin catalog management page.
 * All endpoints require authentication.
 *
 * Base URL: /api/admin/catalog
 */
class Catalog extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper('date');
		$this->load->model('Catalog_model');
		$this->load->model('Form_model');
		$this->load->model('Repository_model');
		$this->load->model('Citation_model');
		$this->load->model('Search_helper_model');
		$this->load->model('Catalog_admin_search');
		$this->load->model('Licensed_model');
		$this->load->library('Dataset_manager');
	}


	/**
	 * Support both session auth and API key auth
	 */
	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}
		return parent::_auth_override_check();
	}


	/**
	 * Search the admin catalog
	 *
	 * GET /api/admin/catalog/search
	 *
	 * Query params:
	 *   keywords     string   text search
	 *   title        string   filter by title
	 *   idno         string   filter by study identifier
	 *   nation[]     string   filter by country/nation (repeatable)
	 *   tag[]        string   filter by tag (repeatable)
	 *   data_access[] string  filter by data access code e.g. direct, licensed (repeatable)
	 *   type[]       string   filter by dataset type e.g. survey, geospatial (repeatable)
	 *   published    0|1      filter by publish status
	 *   no_question  1        only studies without a questionnaire resource
	 *   no_datafile  1        only studies without a datafile resource
	 *   sort_by      string   column to sort by (default: changed)
	 *   sort_order   asc|desc sort direction (default: desc)
	 *   sort         string   e.g. title_asc, country_desc, modified_desc (overrides sort_by/sort_order)
	 *   ps           int      page size, 1–300 (default: 15)
	 *   page         int      page number (default: 1)
	 *   owner_repo   string   filter by owner: surveys.repositoryid
	 *   collections[] string  filter by collections (surveys linked to these repos via survey_repos)
	 */
	function search_get()
	{
		try {
			$this->has_access('study', 'view');

			$page_size = $this->_get_page_size();
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			// Collect all search parameters from GET
			$search_options = array();
			foreach ($_GET as $key => $value) {
				$search_options[$key] = $this->input->get($key, TRUE);
			}

			$owner_repo = $this->_get_owner_repo();
			$this->Catalog_admin_search->set_active_repo($owner_repo);

			// Execute search
			$rows = $this->Catalog_admin_search->search($search_options, $page_size, $offset);
			$total = $this->Catalog_admin_search->get_search_count();

			if (!is_array($rows)) {
				$rows = array();
			}

			// Enrich results with related data
			if (count($rows) > 0) {
				$sid_list = array_column($rows, 'id');

				// Bulk-fetch related data for all rows in the result set
				$survey_repos = (array) $this->Repository_model->get_survey_repositories($sid_list);
				$pending_lic  = (array) $this->Licensed_model->get_pending_requests_count($sid_list);
				$survey_tags  = (array) $this->Catalog_model->get_tags_by_survey($sid_list);
				$citations    = (array) $this->Citation_model->get_citations_count_by_survey_list($sid_list);

				foreach ($rows as $key => $row) {
					$sid = $row['id'];
					$rows[$key]['repositories']         = isset($survey_repos[$sid]) ? $survey_repos[$sid] : array();
					$rows[$key]['pending_lic_requests'] = isset($pending_lic[$sid])  ? (int) $pending_lic[$sid]  : 0;
					$rows[$key]['tags']                 = isset($survey_tags[$sid])  ? $survey_tags[$sid]  : array();
					$rows[$key]['citations']            = isset($citations[$sid])    ? (int) $citations[$sid]    : 0;
				}
			}

			$response = array(
				'status' => 'success',
				'owner_repo' => $owner_repo,
				'result' => array(
					'rows'      => $rows,
					'total'     => $total,
					'page'      => $page,
					'pages'     => $page_size > 0 ? (int) ceil($total / $page_size) : 1,
					'page_size' => $page_size,
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Return standardized filter option values for the admin catalog sidebar
	 *
	 * GET /api/admin/catalog/filter_options
	 *
	 * Optional query param:
	 *   owner_repo   string   repositoryid to scope filter option counts (optional)
	 *
	 * Returns consistent structure for all filters:
	 *   {id, name, count} for display/selection
	 *   {id, code, name, count} for dataset types
	 */
	function filter_options_get()
	{
		try {
			$this->has_access('study', 'view');

			$owner_repo = $this->_get_owner_repo();
			$repo_id    = $owner_repo;

			// Get standardized filter options from model
			$filters = $this->Catalog_admin_search->get_filter_options($repo_id);

			$response = array(
				'status'        => 'success',
				'countries'     => $filters['countries']     ?: array(),
				'tags'          => $filters['tags']          ?: array(),
				'collections'   => $filters['collections']   ?: array(),
				'data_access'   => $filters['data_access']   ?: array(),
				'dataset_types' => $filters['dataset_types'] ?: array(),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Update study options
	 * 
	 * @idno - study IDNO
	 * 
	 * note: replaces datasets/index_put method
	 * 
	 * 
	 * 
	 */
	function options_post($idno=null)
	{
		$this->load->helper("array");		

		try{
			$input=$this->raw_json_input();
			$sid=$this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit',$sid);

			$options=array(				
				'repositoryid'			=> array_get_value($input,'owner_collection'),
				'formid'				=> array_get_value($input,'access_policy'),
				'link_da'				=> array_get_value($input,'data_remote_url'),
				'published'				=> array_get_value($input,'published'),
				'link_study'			=> array_get_value($input,'link_study'),
				'link_indicator'		=> array_get_value($input,'link_indicator'),
				'thumbnail'				=> array_get_value($input,'thumbnail'),
				'tags'					=> array_get_value($input,'tags'),
				'aliases'				=> array_get_value($input,'aliases')				
			);

			if(!empty($options['formid'])){
				$options['formid']=$this->dataset_manager->get_data_access_type_id($options['formid']);
			}

			if (isset($input['data_classification'])){
				$options['data_class_id']=$this->dataset_manager->get_data_classification_id($input['data_classification']);
			}

			//remove options not set
			foreach($options as $key=>$value){
				if($value===false){
					unset($options[$key]);
				}
			}

			//linked collections
			$linked_collections=array_get_value($input,'linked_collections');

			if(is_array($linked_collections)){
				$collection_options=array(
					'study_idno'=>$idno,
					'link_collections'=>$linked_collections
				);

				$this->Repository_model->update_collection_studies($collection_options);
			}


			if (empty($options)){
				throw new Exception("NO_PARAMS_PROVIDED");
			}

		//validate
		$this->dataset_manager->validate_options($options);
		
		//update
		$this->dataset_manager->update_options($sid,$options);

		$this->events->emit('db.after.update', 'surveys', $sid,'atomic');

		$response=array(
			'status'=>'success'				
		);


		$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>"VALIDATION_ERRORS",
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		/*catch(Error $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}*/
	}


	/**
	 * 
	 *  Delete by IDNO
	 * 
	 */
	public function delete_post($idno=null)
	{
		try{
			$this->has_dataset_access('delete');
			$sid=$this->get_sid_from_idno($idno);
			$this->dataset_manager->delete($sid);
			$this->events->emit('db.after.delete', 'surveys', $sid);
		
			$response=array(
				'status'=>'success',
				'message'=>'DELETED'
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
	
	function delete_delete($idno=null)
	{
		return $this->delete_post($idno);
	}


	// ---------------------------------------------------------------
	// Private helpers
	// ---------------------------------------------------------------

	/**
	 * Resolve owner_repo for filtering by surveys.repositoryid (owner).
	 * When not set, empty, or "central", returns null (no filter = central includes everything).
	 *
	 * @return string|null owner_repo from GET, or null for no owner filter
	 */
	private function _get_owner_repo()
	{
		$owner_repo = $this->input->get('owner_repo');
		if ($owner_repo === null || $owner_repo === '' || $owner_repo === 'central') {
			return null;
		}
		return xss_clean($owner_repo);
	}


	/**
	 * Validated page size from the 'ps' query param.
	 */
	private function _get_page_size($default = 15, $min = 1, $max = 300)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= $min && $ps <= $max) {
			return $ps;
		}
		return $default;
	}
}
