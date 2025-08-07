<?php
class Public_model extends CI_Model {

	private $db_fields = array(
		'id',
		'surveyid',
		'title',
		'userid',
		'abstract',
		'request_type',
		'collectionid',
		'posted'
	);

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		
		// Load custom fields configuration
		$this->load->config('public_request_fields');
    }
	
	/**
	 * Get custom fields configuration
	 * 
	 * @return array Array of custom field definitions
	 */
	public function get_custom_fields_config()
	{
		$config_fields = $this->config->item('public_request_fields');
		
		if (!$config_fields) {
			return array();
		}
		
		// Filter only enabled fields and sort by order
		$enabled_fields = array();
		foreach ($config_fields as $field_key => $field_config) {
			if (isset($field_config['enable']) && $field_config['enable'] === true) {
				$enabled_fields[$field_key] = $field_config;
			}
		}
		
		// Sort by order
		usort($enabled_fields, function($a, $b) {
			return $a['order'] - $b['order'];
		});
		
		return $enabled_fields;
	}
	
	/**
	 * Check if custom field column exists in database
	 * Uses simple SELECT query that works with both MySQL and SQL Server
	 * 
	 * @param string $field_name Field name
	 * @return bool True if column exists
	 */
	private function custom_field_exists($field_name)
	{
		$fields = $this->db->list_fields('public_requests');
		return in_array($field_name, $fields);
	}
	
	/**
	 * Create custom field column in database
	 * Uses CodeIgniter Database Forge for cross-database compatibility
	 * 
	 * @param string $field_name Field name
	 * @param string $data_type Database field type (e.g., string(150), text, int)
	 * @return bool Success status
	 */
	private function create_custom_field($field_name, $data_type)
	{
		// Load the database forge
		$this->load->dbforge();
		
		// Parse the data_type to get field type and length
		$field_info = $this->parse_data_type($data_type);
		
		// Add the field using database forge
		$fields = array(
			$field_name => array(
				'type' => $field_info['type'],
				'constraint' => $field_info['constraint'],
				'null' => TRUE
			)
		);
		
		return $this->dbforge->add_column('public_requests', $fields);
	}
	
	/**
	 * Parse database field type string into CodeIgniter format
	 * Supports only: string, text, int
	 * 
	 * @param string $data_type Database field type (string, text, int)
	 * @return array Array with 'type' and 'constraint' keys
	 */
	private function parse_data_type($data_type)
	{
		// Default values
		$type = 'VARCHAR';
		$constraint = 500;
		
		// Parse the data_type string
		if (strtolower($data_type) === 'string') {
			$type = 'VARCHAR';
			$constraint = 500;
		} elseif (strtolower($data_type) === 'text') {
			$type = 'TEXT';
			$constraint = NULL;
		} elseif (strtolower($data_type) === 'int') {
			$type = 'INT';
			$constraint = 11;
		}
		
		return array(
			'type' => $type,
			'constraint' => $constraint
		);
	}
	
	/**
	 * Ensure all custom fields exist in database
	 * Creates missing columns automatically
	 * 
	 * @return bool Success status
	 */
	public function ensure_custom_fields_exist()
	{
		$custom_fields = $this->get_custom_fields_config();
		
		foreach ($custom_fields as $field_config) {

			$field_key = $field_config['name'];
			
			if (!$this->custom_field_exists($field_key)) {
				// Use data_type from config
				$data_type = isset($field_config['data_type']) ? $field_config['data_type'] : 'string';
				
				$result = $this->create_custom_field($field_key, $data_type);
				
				if (!$result) {
					log_message('error', "Failed to create custom field: $field_key");
					return false;
				}
				
				log_message('info', "Created custom field: $field_key with type: $data_type");
			}
		}
		
		return true;
	}
	
	/**
	 * Insert public request with custom fields
	 * Automatically creates missing custom field columns
	 * 
	 * @param int $survey_id Survey ID
	 * @param int $user_id User ID
	 * @param string $title Title of the request
	 * @param string $abstract Abstract text
	 * @param array $custom_fields Custom field values
	 * @return bool Success status
	 */
	public function insert_public_request($survey_id, $user_id, $title, $abstract, $custom_fields = array())
	{
		// Ensure custom fields exist in database
		$this->ensure_custom_fields_exist();
		
		$data = array(
			'surveyid' => $survey_id,
			'title' => $title,
			'userid' => $user_id,
			'abstract' => $abstract,
			'posted' => time(),
			'request_type' => 'study'
		);
		
		// Add custom fields to data array
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $field_name => $field_value) {
				$data[$field_name] = $field_value;
			}
		}
		
		$result = $this->db->insert('public_requests', $data);
		log_message('info', "Request received for [Public Use Files]");
		
		return $result;
	}
	
	/**
	* Returns single request info by request ID
	*
	*/
	function select_single($request_id)
	{
		$this->db->select('p.*,s.title,s.idno,s.year_start, s.nation, u.username,u.email,m.first_name, m.last_name,m.company, m.phone,m.country',FALSE);
		$this->db->join('surveys s', 's.id = p.surveyid');
		$this->db->join('users u', 'u.id = p.userid','left');
		$this->db->join('meta m', 'u.id = m.user_id','left');
		$this->db->from('public_requests p');
		$this->db->where('p.id',$request_id);
		
		$result=$this->db->get()->row_array();		
		
		if ($result)
		{
			return $result;
		}
		
		return FALSE;
	}
	

	/**
	*
	* Return all public use surveys
	*
	**/
	function get_all_public_use_surveys()
	{
		$this->db->select('s.id,s.title,s.nation,s.year_start,s.year_end');
		$this->db->join('forms', 's.formid = forms.formid','left');
		$this->db->where('forms.model','public');
		return $this->db->get('surveys s')->result_array();
	}


	/**
	* Get a list of PUF surveys by Collection
	**/
	function get_surveys_by_collection($repositoryid)
	{
		$this->db->select('s.id,s.title,s.nation,s.year_start,s.year_end,forms.model');
		$this->db->from('surveys s');
		$this->db->join('survey_repos repos', 's.id = repos.sid','left');
		$this->db->join('forms', 'forms.formid = s.formid','inner');
		$this->db->where('repos.repositoryid',$repositoryid);
		$this->db->where('forms.model','public');
		return $this->db->get()->result_array();
	}

	
	/**
	* Check if user has already posted a request for public use for a
	* survey in the collection
	*
	**/	
	function check_user_public_request_by_collection($user_id,$repositoryid)
	{
		//get
		$this->db->select('id');		
		$this->db->from('public_requests pr');
		$this->db->where('collectionid',$repositoryid);
		$this->db->where('userid',$user_id);
		
        $result= $this->db->count_all_results();
		
		return $result;
	}
	
	/**
	* Check if user has already posted a request for public use
	*
	*
	**/	
	function check_user_public_request($user_id,$survey_id)
	{
		$this->db->select('id');		
		$this->db->from('public_requests');		
		$this->db->where('surveyid',$survey_id);		
		$this->db->where('userid',$user_id);		
		
        $result= $this->db->count_all_results();
		return $result;
	}
	
	/**
	*
	* Check if user has access to the study or collection to download files
	**/
	function check_user_has_data_access($user_id,$survey_id)
	{
		//single study public requests
		$request_exists=$this->check_user_public_request($user_id,$survey_id);
		
		//get survey collections with GROUP DA option
		$survey_collections=$this->Repository_model->survey_has_da_by_collection($survey_id);

		if ($request_exists)
		{
			return TRUE;
		}
		
		if (!is_array($survey_collections))
		{
			return FALSE;
		}
		
		foreach($survey_collections as $collection)
		{
			//check if user has access to collection's data
			$collection_access=$this->check_user_public_request_by_collection($user_id,$collection['repositoryid']);
			
			if($collection_access)
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	
	/**
	* Insert public request in DB
	*/
	function insert_collection_request($collection_id,$user_id,$data_use)
	{
		$data = array(
               'collectionid' => $collection_id,
               'userid' => $user_id ,
               'abstract' => $data_use,
			   'request_type'=>'collection',
			   'posted' => date("U")
            );
		
		$result=$this->db->insert('public_requests', $data); 
		log_message('info',"Request received for [Public Use Files]");	
		
		return $result;
	}
	
}
