<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — DDI microdata data files and variables for a catalog study.
 *
 * Routed as:
 *   GET|POST   /api/admin/catalog/{idno}/microdata/datafiles
 *   DELETE     /api/admin/catalog/{idno}/microdata/datafiles/{fileId}
 *   POST       /api/admin/catalog/{idno}/microdata/datafiles_delete/{fileId}  (alias for DELETE datafile)
 *   GET        /api/admin/catalog/{idno}/microdata/variables
 *   GET|POST|DELETE /api/admin/catalog/{idno}/microdata/variables/{second}
 *   POST       /api/admin/catalog/{idno}/microdata/variables_delete/{fileId}  (alias for batch variable delete)
 *   GET        /api/admin/catalog/{idno}/microdata/variable/{varId}
 *   DELETE     /api/admin/catalog/{idno}/microdata/variable/{fileId}/{varId}
 *   POST       /api/admin/catalog/{idno}/microdata/variable_delete/{fileId}/{varId}  (alias for single variable delete)
 *
 * Same behaviour and JSON contracts as legacy `api/datasets/...` datafiles/variables
 * (see `Datasets`). Optional query `id_format=id` for numeric `surveys.id`.
 */
class Microdata extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->helper('date');
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');
		$this->load->model('Dataset_model');
		$this->load->library('Dataset_manager');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}

		return parent::_auth_override_check();
	}


	/**
	 * Resolve surveys.id then enforce ACL on its owner repo (avoid central fallback).
	 *
	 * @param string|null $idno Study idno (or surveys.id when `id_format=id`)
	 * @param string      $privilege view|edit
	 *
	 * @return int surveys.id
	 */
	protected function _require_microdata_dataset_access($idno, $privilege)
	{
		$user = $this->api_user();
		if (! $user) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}

		$sid = $this->get_sid_from_idno($idno);
		$this->has_dataset_access($privilege, $sid);

		return (int) $sid;
	}


	/**
	 * @param Exception $e
	 */
	protected function _respond_microdata_error(Exception $e)
	{
		if ($e instanceof AclAccessDeniedException
			|| stripos($e->getMessage(), 'Access denied for resource') !== false) {
			$this->set_response(
				array('status' => 'failed', 'message' => 'ACCESS_DENIED'),
				REST_Controller::HTTP_FORBIDDEN
			);
			return;
		}

		$this->set_response(
			array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
			),
			REST_Controller::HTTP_BAD_REQUEST
		);
	}


	/**
	 * List study data files (microdata definitions).
	 */
	function datafiles_get($idno = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'view');
			$this->get_api_user_id();
			$survey = $this->dataset_manager->get_row($sid);

			if (! $survey) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$survey_datafiles = $this->Data_file_model->get_all_by_survey($sid);

			$response = array(
				'datafiles' => $survey_datafiles,
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * Create a new data file row.
	 */
	function datafiles_post($idno = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'edit');

			$options = $this->raw_json_input();
			$user_id = $this->get_api_user_id();
			$options['created_by'] = $user_id;
			$options['changed_by'] = $user_id;

			$options['sid'] = $sid;

			if ($this->Data_file_model->validate_data_file($options)) {

				$file_id = $this->Data_file_model->insert($sid, $options);
				$file = $this->Data_file_model->select_single($file_id);

				$response = array(
					'status' => 'success',
					'datafile' => $file,
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * Delete a data file and its variables.
	 */
	function datafiles_delete($idno = null, $file_id = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'edit');

			if (! $file_id) {
				throw new Exception('FILE_ID is required');
			}

			$survey = $this->dataset_manager->get_row($sid);

			if (! $survey) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$this->Data_file_model->delete_file($sid, $file_id);

			$response = array(
				'status' => 'success',
				'message' => 'DELETED',
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * POST alias for `datafiles_delete` (environments that block DELETE).
	 */
	function datafiles_delete_post($idno = null, $file_id = null)
	{
		return $this->datafiles_delete($idno, $file_id);
	}


	/**
	 * List variables (optional filter by data file id).
	 */
	function variables_get($idno = null, $file_id = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'view');
			$this->get_api_user_id();
			$survey = $this->dataset_manager->get_row($sid);

			if (! $survey) {
				throw new Exception('STUDY_NOT_FOUND');
			}

			$survey_variables = $this->Variable_model->list_by_dataset($sid, $file_id);

			$response = array(
				'variables' => $survey_variables,
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * Return one variable by `vid` with full metadata.
	 */
	function variable_get($idno = null, $var_id = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'view');
			if (! $var_id) {
				throw new Exception('MISSING_PARAM::VAR_ID');
			}

			$this->get_api_user_id();
			$variable = $this->Variable_model->get_var_by_vid($sid, $var_id);

			if (! $variable) {
				throw new Exception('VARIABLE-NOT-FOUND');
			}

			$response = array(
				'variable' => $variable,
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * Create or update variables (batch). Path segment `merge_metadata` is `true` or `false`
	 * when using POST …/microdata/variables/{true|false} (legacy datasets shape).
	 */
	function variables_post($idno = null, $merge_metadata = false)
	{
		try {
			$this->get_api_user_id();
			$merge_metadata = $merge_metadata === 'true';

			$sid = $this->_require_microdata_dataset_access($idno, 'edit');

			$options = (array) $this->raw_json_input();
			$key = key($options);

			if (! is_numeric($key)) {
				$tmp_options = array();
				$tmp_options[] = $options;
				$options = $tmp_options;
			}

			$valid_data_files = $this->Data_file_model->list_fileid($sid);

			$result = array();

			foreach ($options as $key => $variable) {

				if (! isset($variable['file_id'])) {
					throw new Exception('`file_id` is required');
				}

				if (! in_array($variable['file_id'], $valid_data_files)) {
					throw new Exception('Invalid `file_id`: valid values are: ' . implode(', ', $valid_data_files));
				}

				if (isset($variable['vid']) && ! empty($variable['vid'])) {
					$uid = $this->Variable_model->get_uid_by_vid($sid, $variable['vid']);
					$variable['fid'] = $variable['file_id'];

					if ($uid) {
						$var_mt = $this->Variable_model->get_var_by_vid($sid, $variable['vid']);
						$var_mt = isset($var_mt['metadata']) ? $var_mt['metadata'] : array();

						if ($merge_metadata == true) {
							$variable = array_replace_recursive($var_mt, $variable);
						}

						$this->Variable_model->validate_variable($variable);
						$variable['metadata'] = $variable;
						$this->Variable_model->update($sid, $uid, $variable);
					}
					else {
						$this->Variable_model->validate_variable($variable);
						$variable['metadata'] = $variable;
						$this->Variable_model->insert($sid, $variable);
					}

					$result[] = $variable['vid'];
				}
			}

			$this->dataset_manager->update_varcount($sid);

			$response = array(
				'status' => 'success',
				'variables' => $result,
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (ValidationException $e) {
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => $e->GetValidationErrors(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * Batch-delete variables for a data file (or all variables for the file id segment).
	 */
	function variables_delete($idno = null, $file_id = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'edit');
			$this->Dataset_model->remove_datafile_variables($sid, $file_id);

			$response = array(
				'status' => 'success',
				'message' => 'DELETED',
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * POST alias for `variables_delete`.
	 */
	function variables_delete_post($idno = null, $file_id = null)
	{
		return $this->variables_delete($idno, $file_id);
	}


	/**
	 * Delete a single variable within a data file.
	 */
	function variable_delete($idno = null, $file_id = null, $var_id = null)
	{
		try {
			$sid = $this->_require_microdata_dataset_access($idno, 'edit');

			if (! $file_id) {
				throw new Exception('FILE_ID is required');
			}

			if (! $var_id) {
				throw new Exception('VAR_ID is required');
			}

			$this->Variable_model->remove_variable($sid, $file_id, $var_id);

			$response = array(
				'status' => 'success',
				'message' => 'DELETED',
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_respond_microdata_error($e);
		}
	}


	/**
	 * POST alias for `variable_delete`.
	 */
	function variable_delete_post($idno = null, $file_id = null, $var_id = null)
	{
		return $this->variable_delete($idno, $file_id, $var_id);
	}
}
