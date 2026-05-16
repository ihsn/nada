<?php

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin API — data classifications reference (site toggle + table `data_classifications`).
 *
 * GET /api/admin/data-classifications
 */
class Data_classifications extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->model('Configurations_model');
		$this->load->model('Data_classification_model');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}

		return parent::_auth_override_check();
	}

	/**
	 * GET /api/admin/data-classifications
	 *
	 * Response:
	 *   status — success | error
	 *   data_classifications_enabled — bool (site configuration)
	 *   codelist — array of { id, code, title } from `data_classifications`, ordered by id
	 */
	public function index_get()
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$enabled = $this->Configurations_model->is_data_classifications_enabled();

			$this->db->select('id, code, title');
			$this->db->from('data_classifications');
			$this->db->order_by('id', 'asc');
			$query = $this->db->get();
			$codelist = ($query && $query->num_rows() > 0) ? $query->result_array() : array();

			$this->set_response(
				array(
					'status'                         => 'success',
					'data_classifications_enabled' => $enabled,
					'codelist'                       => $codelist,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
