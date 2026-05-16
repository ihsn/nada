<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Admin site configurations API.
 *
 * Base URL: /api/admin/configurations
 *
 * Standard verbs:
 *   GET    — list all settings or one key (optional last URI segment)
 *   PUT    — create/update one or many keys (JSON body)
 *   PATCH  — same as PUT (partial updates: only keys sent are written)
 *   DELETE — remove one key (requires key in URI or JSON body for POST alias)
 *
 * POST aliases (when PUT/DELETE are blocked):
 *   POST .../save           — same as PUT index (batch)
 *   POST .../save/{key}     — same as PUT with single key
 *   POST .../patch          — same as PATCH index
 *   POST .../patch/{key}    — same as PATCH with single key
 *   POST .../remove         — body: {"key":"name"} when key not in URI
 *   POST .../remove/{key}   — same as DELETE for one key
 */
class Configurations extends MY_REST_Controller
{
	/** Keys stored as JSON in DB (Site_configurations). */
	protected $json_value_keys = array('admin_allowed_ip', 'admin_allowed_hosts', 'supported_languages');

	/** Never expose raw values for these keys on GET. */
	protected $secret_keys = array(
		'smtp_pass',
		'sendgrid_api_key',
		'microsoft_graph_client_secret',
	);

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Configurations_model');
		$this->load->library('acl_manager');
		$this->is_authenticated_or_die();
	}

	function _auth_override_check()
	{
		if ($this->session->userdata('user_id'))
		{
			return TRUE;
		}

		return parent::_auth_override_check();
	}

	protected function require_configurations_edit()
	{
		$this->has_access('configurations', 'edit');
	}

	/**
	 * GET /api/admin/configurations
	 * GET /api/admin/configurations/{key}
	 *
	 * Query: keys=key1,key2 (ignored when {key} is present)
	 */
	public function index_get($key = null)
	{
		try
		{
			$this->require_configurations_edit();

			$config = $this->Configurations_model->get_config_array();

			if ($key !== null && $key !== '')
			{
				if (!isset($config[$key]))
				{
					$this->set_response(
						array('status' => 'error', 'message' => 'NOT_FOUND'),
						REST_Controller::HTTP_NOT_FOUND
					);
					return;
				}

				$one = array($key => $config[$key]);
				$one = $this->mask_secrets($one);
				$one = $this->decode_json_values_for_output($one);

				$this->set_response(
					array(
						'status' => 'success',
						'key'    => $key,
						'value'  => isset($one[$key]) ? $one[$key] : null,
					),
					REST_Controller::HTTP_OK
				);
				return;
			}

			$keys_filter = $this->input->get('keys');
			if (is_string($keys_filter) && $keys_filter !== '')
			{
				$want = array_filter(array_map('trim', explode(',', $keys_filter)));
				$config = array_intersect_key($config, array_flip($want));
			}

			$config = $this->mask_secrets($config);
			$config = $this->decode_json_values_for_output($config);

			$this->set_response(
				array(
					'status'   => 'success',
					'settings' => $config,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/configurations/meta
	 *
	 * UI-only fields (language folders, ISO list, path checks) not stored as single keys.
	 */
	public function meta_get()
	{
		try
		{
			$this->require_configurations_edit();

			$this->load->library('translator');

			$c               = $this->Configurations_model->get_config_array();
			$catalog_root    = isset($c['catalog_root']) ? $c['catalog_root'] : '';
			$ddi_import      = isset($c['ddi_import_folder']) ? $c['ddi_import_folder'] : '';
			$lang_codes      = $this->config->item('language_codes');

			if (!is_array($lang_codes))
			{
				$lang_codes = array();
			}

			$this->config->load('iso_languages');
			$iso_languages = $this->config->item('iso_languages');
			if (!is_array($iso_languages))
			{
				$iso_languages = array();
			}

			$meta = array(
				'available_folders'         => $this->translator->get_languages_array(),
				'language_codes'            => $lang_codes,
				'iso_languages'             => $iso_languages,
				'email_config_file_exists'  => file_exists(APPPATH.'config/email.php'),
				'paths_ok'                  => array(
					'catalog_root'       => ($catalog_root !== '' && is_dir($catalog_root)),
					'ddi_import_folder'  => ($ddi_import !== '' && is_dir($ddi_import)),
				),
			);

			$this->set_response(
				array(
					'status' => 'success',
					'meta'   => $meta,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/configurations
	 * PUT /api/admin/configurations/{key}
	 */
	public function index_put($key = null)
	{
			$this->_save_from_request($key);
	}

	/**
	 * PATCH /api/admin/configurations
	 * PATCH /api/admin/configurations/{key}
	 */
	public function index_patch($key = null)
	{
			$this->_save_from_request($key);
	}

	/**
	 * DELETE /api/admin/configurations/{key}
	 */
	public function index_delete($key = null)
	{
		try
		{
			$this->require_configurations_edit();

			if ($key === null || $key === '')
			{
				$this->set_response(
					array('status' => 'error', 'message' => 'KEY_REQUIRED'),
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->Configurations_model->delete_by_name($key);

			$this->set_response(
				array('status' => 'success', 'key' => $key),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/** POST alias for PUT (batch or single when path includes save/{key}). */
	public function save_post($key = null)
	{
		return $this->index_put($key);
	}

	/** POST alias for PATCH. */
	public function patch_post($key = null)
	{
		return $this->index_patch($key);
	}

	/**
	 * POST alias for DELETE.
	 * POST body may contain {"key":"..."} when key is not in the path.
	 */
	public function remove_post($key = null)
	{
		if ($key === null || $key === '')
		{
			try
			{
				$body = $this->read_json_body_optional();
				if (isset($body['key']))
				{
					$key = $body['key'];
				}
				elseif (isset($body['name']))
				{
					$key = $body['name'];
				}
			}
			catch (Exception $e)
			{
				//
			}
		}

		return $this->index_delete($key);
	}

	protected function _save_from_request($uri_key = null)
	{
		try
		{
			$this->require_configurations_edit();

			$payload = $this->read_json_body_mixed();
			if ($uri_key !== null && $uri_key !== '' && $payload === null)
			{
				$payload = '';
			}

			if (($uri_key === null || $uri_key === '')
				&& ($payload === null || ($payload === array() && $this->body_stream_empty())))
			{
				$this->set_response(
					array('status' => 'error', 'message' => 'EMPTY_PAYLOAD'),
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			if (($uri_key === null || $uri_key === '') && $payload !== null && !is_array($payload))
			{
				throw new Exception('INVALID_PAYLOAD_BATCH');
			}

			$options = $this->normalize_save_payload($uri_key, $payload);

			if (count($options) === 0)
			{
				$this->set_response(
					array('status' => 'error', 'message' => 'EMPTY_PAYLOAD'),
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->apply_save_validation($options);

			if (!$this->Configurations_model->update($options))
			{
				$this->set_response(
					array('status' => 'error', 'message' => 'SAVE_FAILED'),
					REST_Controller::HTTP_INTERNAL_SERVER_ERROR
				);
				return;
			}

			$saved = $this->Configurations_model->get_config_array();
			$saved = array_intersect_key($saved, $options);
			$saved = $this->mask_secrets($saved);
			$saved = $this->decode_json_values_for_output($saved);

			$this->set_response(
				array(
					'status'        => 'success',
					'saved_keys'    => array_keys($options),
					'saved_settings'=> $saved,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Build flat key => value for model from URI + decoded JSON body.
	 *
	 * @param string|null $uri_key
	 * @param mixed       $payload  Scalar or array from json_decode(..., true)
	 */
	protected function normalize_save_payload($uri_key, $payload)
	{
		$flat = array();

		if ($uri_key !== null && $uri_key !== '')
		{
			$val = $payload;

			if (is_array($payload))
			{
				if (array_key_exists('value', $payload))
				{
					$val = $payload['value'];
				}
				elseif ($this->is_assoc($payload) && count($payload) === 1)
				{
					$only_key = key($payload);
					if (!in_array($only_key, array('settings', 'values'), TRUE))
					{
						$val = reset($payload);
					}
				}
			}

			$flat[$uri_key] = $val;
		}
		else
		{
			if (!is_array($payload))
			{
				return array();
			}

			if (isset($payload['settings']) && is_array($payload['settings']))
			{
				$flat = $payload['settings'];
			}
			elseif (isset($payload['values']) && is_array($payload['values']))
			{
				$flat = $payload['values'];
			}
			else
			{
				$flat = $payload;
				unset($flat['settings'], $flat['values']);
			}
		}

		return $this->encode_values_for_storage($flat);
	}

	protected function encode_values_for_storage(array $flat)
	{
		$out = array();

		foreach ($flat as $name => $value)
		{
			if (!is_string($name) || $name === '')
			{
				continue;
			}

			if ($this->is_secret_key($name) && ($value === '' || $value === null))
			{
				continue;
			}

			if (in_array($name, $this->json_value_keys, TRUE))
			{
				if (is_array($value))
				{
					$out[$name] = json_encode($value);
				}
				elseif (is_string($value))
				{
					json_decode($value, TRUE);
					if (json_last_error() === JSON_ERROR_NONE)
					{
						$out[$name] = $value;
					}
					else
					{
						$out[$name] = json_encode($value);
					}
				}
				else
				{
					$out[$name] = json_encode($value);
				}
				continue;
			}

			if (is_array($value) || is_object($value))
			{
				$out[$name] = json_encode($value);
			}
			else
			{
				$out[$name] = (string) $value;
			}
		}

		return $out;
	}

	protected function apply_save_validation(array &$options)
	{
		foreach ($options as $key => &$value)
		{
			if ($key === 'language')
			{
				if (!$this->language_folder_exists((string) $value))
				{
					unset($options[$key]);
				}
				continue;
			}

			if (!$this->is_secret_key($key) && is_string($value))
			{
				$value = $this->security->xss_clean($value);
			}

			if ($key === 'admin_header_background')
			{
				$value = strtoupper(trim((string) $value));
				if ($value === '')
				{
					continue;
				}
				if (preg_match('/^#[0-9A-F]{8}$/', $value))
				{
					// Normalize #RRGGBBAA to #RRGGBB for consistent storage/rendering.
					$value = substr($value, 0, 7);
				}
				if (!preg_match('/^#[0-9A-F]{6}$/', $value))
				{
					throw new Exception('INVALID_COLOR:admin_header_background');
				}
			}

			if (in_array($key, array('catalog_root', 'ddi_import_folder'), TRUE))
			{
				if ($value !== '' && !is_dir((string) $value))
				{
					throw new Exception('INVALID_FOLDER:'.$key);
				}
			}

			if ($key === 'data_classifications_enabled')
			{
				$v = strtolower(trim((string) $value));
				if (! in_array($v, array('yes', 'no'), true))
				{
					throw new Exception('INVALID_VALUE:data_classifications_enabled');
				}
				$value = $v;
			}
		}
		unset($value);
	}

	protected function language_folder_exists($folder)
	{
		if ($folder === '' || !is_string($folder))
		{
			return FALSE;
		}

		if (file_exists(APPPATH.'language/'.$folder))
		{
			return TRUE;
		}

		$userdata = $this->config->item('userdata_path');
		if (!empty($userdata) && is_dir($userdata.'/language/'.$folder))
		{
			return TRUE;
		}

		return FALSE;
	}

	protected function mask_secrets(array $config)
	{
		foreach ($this->secret_keys as $sk)
		{
			if (array_key_exists($sk, $config))
			{
				$config[$sk] = '';
			}
		}

		return $config;
	}

	protected function decode_json_values_for_output(array $config)
	{
		foreach ($this->json_value_keys as $jk)
		{
			if (!isset($config[$jk]) || !is_string($config[$jk]))
			{
				continue;
			}

			$decoded = json_decode($config[$jk], TRUE);
			if (json_last_error() === JSON_ERROR_NONE)
			{
				$config[$jk] = $decoded;
			}
		}

		return $config;
	}

	protected function is_secret_key($name)
	{
		return in_array($name, $this->secret_keys, TRUE);
	}

	protected function is_assoc(array $arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/**
	 * JSON body as associative array (for POST delete body).
	 */
	protected function read_json_body_optional()
	{
		$raw = $this->input->raw_input_stream;
		if ($raw === null || trim($raw) === '')
		{
			return array();
		}

		$data = json_decode($raw, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			throw new Exception('INVALID_JSON_INPUT');
		}

		return is_array($data) ? $data : array();
	}

	/**
	 * Decoded JSON body for writes; null if stream empty.
	 *
	 * @return mixed|null
	 */
	protected function read_json_body_mixed()
	{
		if ($this->body_stream_empty())
		{
			return NULL;
		}

		$raw = $this->input->raw_input_stream;
		$data = json_decode($raw, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			throw new Exception('INVALID_JSON_INPUT');
		}

		return $data;
	}

	protected function body_stream_empty()
	{
		$raw = $this->input->raw_input_stream;

		return $raw === null || trim($raw) === '';
	}

	/**
	 * GET /api/admin/configurations/test_email
	 *
	 * Form defaults from application/config/email.php (password never returned).
	 */
	public function test_email_get()
	{
		try
		{
			$this->require_configurations_edit();
			$this->config->load('email');

			$auth = $this->config->item('smtp_auth');
			$crypto = $this->config->item('smtp_crypto');
			if (!is_string($crypto))
			{
				$crypto = '';
			}

			$this->set_response(
				array(
					'status' => 'success',
					'form'   => array(
						'smtp_host'    => (string) $this->config->item('smtp_host'),
						'smtp_port'    => (string) $this->config->item('smtp_port'),
						'smtp_user'    => (string) $this->config->item('smtp_user'),
						'smtp_auth'    => ($auth === TRUE || $auth === 'true' || $auth === '1' || $auth === 1),
						'smtp_crypto'  => $crypto,
						'useragent'    => (string) ($this->config->item('useragent') ? $this->config->item('useragent') : 'CodeIgniter'),
						'mail_from'    => (string) $this->config->item('smtp_user'),
						'smtp_pass'    => '',
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/configurations/test_email_send
	 *
	 * JSON body mirrors legacy send_test_email POST fields.
	 */
	public function test_email_send_post()
	{
		try
		{
			$this->require_configurations_edit();
			$this->load->helper('email');
			$this->load->library('email');

			$body = $this->read_json_body_optional();

			$mail_to = isset($body['mail_to']) ? trim((string) $body['mail_to']) : '';
			if ($mail_to === '' || ! valid_email($mail_to))
			{
				$this->set_response(
					array('status' => 'error', 'message' => 'INVALID_MAIL_TO'),
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->config->load('email');

			$smtp_pass = isset($body['smtp_pass']) ? (string) $body['smtp_pass'] : '';
			if ($smtp_pass === '')
			{
				$smtp_pass = (string) $this->config->item('smtp_pass');
			}

			$smtp_auth_raw = isset($body['smtp_auth']) ? $body['smtp_auth'] : FALSE;
			$smtp_auth     = ($smtp_auth_raw === TRUE || $smtp_auth_raw === 'true' || $smtp_auth_raw === '1' || $smtp_auth_raw === 1);

			$smtp_crypto = isset($body['smtp_crypto']) ? (string) $body['smtp_crypto'] : '';
			if ($smtp_crypto !== '' && $smtp_crypto !== 'tls' && $smtp_crypto !== 'ssl')
			{
				$smtp_crypto = '';
			}

			$config = array(
				'protocol'    => 'smtp',
				'useragent'   => isset($body['useragent']) ? (string) $body['useragent'] : 'CodeIgniter',
				'smtp_host'   => isset($body['smtp_host']) ? (string) $body['smtp_host'] : '',
				'smtp_port'   => isset($body['smtp_port']) ? (string) $body['smtp_port'] : '25',
				'smtp_user'   => isset($body['smtp_user']) ? (string) $body['smtp_user'] : '',
				'smtp_pass'   => $smtp_pass,
				'mailtype'    => 'html',
				'smtp_debug'  => 2,
				'smtp_auth'   => $smtp_auth,
				'smtp_crypto' => $smtp_crypto,
			);

			$this->email->initialize($config);

			$mail_from = isset($body['mail_from']) ? trim((string) $body['mail_from']) : '';
			if ($mail_from !== '')
			{
				$this->email->from($mail_from);
			}

			$this->email->to($mail_to);
			$this->email->subject('NADA test email');
			$this->email->message('NADA test email message body');

			$ok       = $this->email->send();
			$debugger = $this->email->print_debugger(array('headers', 'subject', 'body'));
			if (! is_string($debugger))
			{
				$debugger = '';
			}

			$this->set_response(
				array(
					'status'   => 'success',
					'sent'     => (bool) $ok,
					'debugger' => $debugger,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e)
		{
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e)
		{
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
