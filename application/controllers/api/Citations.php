<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Public citations API (read-only).
 *
 * Create / update / delete / publish live under api/admin/citations.
 * Responses are always published-only and never include internal fields
 * (notes, flag, owner, url_status, audit usernames, …).
 */
class Citations extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Citation_model");
	}

	/**
	 * List published citations, or a single citation when $uuid is set.
	 */
	function index_get($uuid=null)
	{
		try{
			if($uuid){
				return $this->single_get($uuid);
			}

			$per_page = $this->get_page_size($this->input->get("ps"));

			$offset=(int)$this->input->get('offset');
			if ($offset<0){
				$offset=0;
			}

			$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
			$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'rank';

			$filter=array(
				'keywords'=>$this->input->get("keywords"),
				'from'=>$this->input->get("from"),
				'to'=>$this->input->get("to"),
				'ctype'=>array_filter(explode(",",$this->input->get("ctype")))
			);

			// past the offsets the search engine reaches (nada-ai), pages continue with next_cursor / prev_cursor
			$this->Citation_model->search_cursor = trim((string) xss_clean($this->input->get('cursor')));

			$rows=$this->Citation_model->search(
				$per_page,
				$offset,
				$filter,
				$sort_by,
				$sort_order,
				1 // published only
			);

			$total = $this->Citation_model->search_count();
			$paging = $this->Citation_model->search_paging;
			$rows = $this->Citation_model->strip_internal_fields($rows);

			$response=array(
				'published' =>1,
				'status'	=> 'success',
				'total'		=> $total,
				'found'		=> count($rows),
				'offset'	=> $paging !== NULL ? $paging['offset'] : $offset,
				'per_page'	=> $per_page,
				'citations'	=> $rows,

			);
			// nada-ai: offset reaches max_offset rows; next_cursor / prev_cursor (in place of offset) page past them
			if ($paging !== NULL) {
				$response['max_offset'] = $paging['max_offset'];
				$response['next_cursor'] = $paging['next_cursor'];
				$response['prev_cursor'] = $paging['prev_cursor'];
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			// an offset past max_offset (page_out_of_range) or a cursor of another search (cursor_invalid)
			$this->load->helper('catalog');
			$paging_error = catalog_search_paging_error($e);
			if ($paging_error !== null) {
				$this->set_response(array('status' => 'failed', 'errors' => $paging_error['message']) + $paging_error, REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Get page size
	 */
	private function get_page_size($page_size)
	{
		$page_size_min=1;
		$page_size_max=300;

		if($page_size>=$page_size_min && $page_size<=$page_size_max){
			return $page_size;
		}

		return 50;//default page size
	}


	/**
	 * Get a single published citation
	 */
	function single_get($uuid=null)
	{
		try{

			$id=$this->get_id_from_uuid($uuid);

			if(!is_numeric($id)){
				throw new Exception("MISSING_PARAM: citationId");
			}

			$citation=$this->Citation_model->select_single($id);

			if(!$citation || (int)$citation['published']!==1){
				$this->set_response(array(
					'status'=>'failed',
					'errors'=>'CITATION_NOT_FOUND'
				), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$this->set_response($this->Citation_model->strip_internal_fields($citation), REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Get published citations by Study
	 */
	function by_dataset_get($sid=null)
	{
		try{
			if(!is_numeric($sid)){
				$sid=$this->Dataset_model->find_by_idno($sid);
			}

			if (!$sid){
				throw new Exception("MISSING_PARAM: DatasetId");
			}

			$citations=$this->Citation_model->get_citations_by_survey($sid, NULL, NULL, $published_only=TRUE);
			$citations=$citations ? $this->Citation_model->strip_internal_fields($citations) : array();

			$output=array(
				'status'=>'success',
				'found'=>count($citations),
				'records'=>$citations
			);

			$this->set_response($output, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Find similar published citations
	 */
	function find_similar_get()
	{
		try{
			$keywords=array();

			//key variables to search on
			$search_keys=explode(",","title,author_fname,author_lname");

			foreach($search_keys as $key){
				if (isset($_GET[$key])){
					$value=$this->get($key,true);

					if (is_array($value)){
						$keywords[]=implode(" ",$value);
					}
					else{
						$keywords[]=$value;
					}
				}
			}

			$keywords= implode(" ",$keywords);

			$doi=$this->get('doi',true);
			$citations=$this->Citation_model->search_duplicates($keywords, is_string($doi) ? $doi : '');

			if (is_array($citations)){
				$citations=array_values(array_filter($citations, function($row){
					return isset($row['published']) && (int)$row['published']===1;
				}));
				$citations=$this->Citation_model->strip_internal_fields($citations);
			}

			$output=array(
				'citations'=>$citations,
				'status'=>'success'
			);

			$this->set_response($output, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * Writes moved to api/admin/citations. Explicit handlers so clients get 405
	 * (not an API-key 403 from the REST auth layer).
	 */
	function index_post()
	{
		$this->_method_not_allowed('Use api/admin/citations to create or update citations');
	}

	function index_delete($uuid=null)
	{
		$this->_method_not_allowed('Use api/admin/citations to delete citations');
	}

	function delete_delete($uuid=null)
	{
		$this->_method_not_allowed('Use api/admin/citations to delete citations');
	}

	function delete_post($uuid=null)
	{
		$this->_method_not_allowed('Use api/admin/citations to delete citations');
	}

	private function _method_not_allowed($message)
	{
		$this->set_response(array(
			'status'=>'failed',
			'errors'=>'METHOD_NOT_ALLOWED',
			'message'=>$message
		), REST_Controller::HTTP_METHOD_NOT_ALLOWED);
	}

	// Read-only public controller: no API key required (writes answer 405 above).
	function _auth_override_check()
	{
		return true;
	}


	/**
	 * Return ID by UUID
	 *
	 * @uuid 		- Citation UUID
	 * @id_format 	- ID | IDNO
	 *
	 * Note: to use ID instead of UUID, must pass id_format in querystring
	 */
	public function get_id_from_uuid($uuid=null)
	{
		if(!$uuid){
			throw new Exception("UUID-NOT-PROVIDED");
		}

		$id_format=$this->input->get("id_format");

		if ($id_format=='id'){
			return $uuid;
		}

		$id=$this->Citation_model->get_id_by_uuid($uuid);

		if(!$id){
			throw new Exception("IDNO-NOT-FOUND");
		}

		return $id;
	}

}
