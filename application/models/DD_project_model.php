<?php
/*
 projects
 --------------------------
  id            int unsigned  AUTO_INCREMENT NOT NULL,
  uid           int unsigned                 NOT NULL,
  created_by    tinytext                     NOT NULL,
  title         tinytext                     NOT NULL,
  shortname     VARCHAR(50)                  NOT NULL,
  created_on    datetime                     NOT NULL,
  data_type     tinytext                     NOT NULL,
  last_modified datetime                     NOT NULL,
  status        VARCHAR(20)                  NOT NULL default 'draft',
  description   tinytext                     NOT NULL,
  PRIMARY KEY (id) ENGINE=InnoDB 
----------------------------
*/

class DD_project_model extends CI_Model {

	private $project_valid_fields=array(
        'title',
        'shortname',
        'description',
        'created_on',
        'last_modified',
        'created_by',
        'status',
        'data_type'
    );


	public function __construct()
	{
		parent::__construct();
	}


	//get project by id
	public function get_by_id($id)
	{
		$q = $this->db->select('*')
				->from('dd_projects')
				->where('id',$id);

		$project= $q->get()->row_array();
		
		//get project owner 
		$project['owner']=$this->get_owner($id);
		
		//get project collaborators
		$project['collaborators']=$this->get_collaborators($id);
		
		return $project;
	}
        
	//get project title
	public function get_title_by_id($id)
	{
		$q = $this->db->select('title')
				->from('dd_projects')
				->where('id',$id);

		$project= $q->get()->row_array();
		
		return $project['title'];
	}
		
        
        
	public function project_id ($id, $email) {

		$q = $this->db->select('dd_projects.*, dd_collaborators.access')
			->from('dd_projects, dd_collaborators')
			->where('dd_projects.id = dd_collaborators.pid')
			->where('dd_collaborators.email', $email)
			->where('dd_collaborators.pid', $id);
			
		$query=$q->get();
		
		if ($query)
		{
			return $query->result();
		}	
	}
	
	/**
	* 
	* Returns a string of project owner(s) email addresses
	**/
	public function get_project_owner_email($project_id) 
	{
		return implode (",",$this->get_owner($project_id));
	}
	
	
	
	// exporting
	public function project_id2 ($id, $email) {

		$q = $this->db->select('dd_projects.*, dd_collaborators.access')
			->from('dd_projects, dd_collaborators')
			->where('dd_projects.id = dd_collaborators.pid')
			->where('dd_collaborators.pid', $id);

		return $q->get()->result();
	}

    

	//project is locked for editing
	public function is_locked($id, $email) 
	{
		$projects = $this->project_id($id, $email);
		if (isset($projects[0]->id) && $projects[0]->status=='draft')
		{
			return false;
		}
		
		return true;
	}


	public function has_access($id, $email) 
	{
		$projects = $this->project_id($id, $email);
		return isset($projects[0]->id);
	}
	
	public function has_access_or_die($id,$email)
	{
		if(!$this->has_access($id,$email))
		{
			show_error('ACCESS_DENIED');
		}
	}

	public function all_projects() 
	{
		 return $this->db->get('dd_projects')->result();
	}
	
    //get projects by user
    public function get_user_projects($uid, $order='created_on', $order_by = 'desc', $limit = 1000, $offset = 0)
    {
        return $this->projects($uid,$order, $order_by,$limit,$offset);
    }



    /**
     *
     * TODO: TOBE removed
     *
     * Return a list of projects by user
	**/
	public function projects ($uid, $order='created_on', $order_by = 'desc', $limit = 0, $offset = 0, $keywords = '', $status = '') 
	{
		$email = $this->_user_email_by_uid($uid);
		if ($email === '') {
			return array();
		}

		$allowed = array('title', 'created_by', 'status', 'created_on', 'last_modified', 'id');
		if (!in_array($order, $allowed, true)) {
			$order = 'created_on';
		}
		$order_by = strtolower((string) $order_by) === 'asc' ? 'asc' : 'desc';

		$this->db->select('dd_projects.*, dd_collaborators.access');
		$this->_projects_for_email($email, $keywords, $status);
		$this->db->order_by('dd_projects.'.$order, $order_by);

		$limit = (int) $limit;
		$offset = max(0, (int) $offset);
		if ($limit > 0) {
			$this->db->limit($limit, $offset);
		}

		return $this->db->get()->result();
	}

	public function count_projects($uid, $keywords = '', $status = '')
	{
		$email = $this->_user_email_by_uid($uid);
		if ($email === '') {
			return 0;
		}

		$this->db->select('dd_projects.id');
		$this->_projects_for_email($email, $keywords, $status);
		return (int) $this->db->count_all_results();
	}

	private function _user_email_by_uid($uid)
	{
		$row = $this->db->select('email')
			->from('users')
			->where('id', (int) $uid)
			->get()
			->row();
		return ($row && isset($row->email)) ? (string) $row->email : '';
	}

	private function _projects_for_email($email, $keywords = '', $status = '')
	{
		$this->db->from('dd_projects');
		$this->db->join('dd_collaborators', 'dd_collaborators.pid=dd_projects.id', 'left');
		$this->db->where('dd_collaborators.email', $email);

		$allowed_status = array('draft', 'submitted', 'accepted', 'processed', 'closed');
		$status = strtolower(trim((string) $status));
		if ($status !== '' && in_array($status, $allowed_status, true)) {
			$this->db->where('dd_projects.status', $status);
		}

		$keywords = trim((string) $keywords);
		if ($keywords === '') {
			return;
		}

		$this->db->group_start();
		$this->db->like('dd_projects.title', $keywords);
		$this->db->or_like('dd_projects.description', $keywords);
		$this->db->or_like('dd_projects.created_by', $keywords);
		$this->db->group_end();
	}
	
	

	
	
	public function get_collaborators($pid) {
		$q = $this->db->select('*')
			->from('dd_collaborators')
			->where('pid', $pid)
			->where('access', 'collaborator')
			->order_by('id');
		$result = $q->get()->result();
		$collabs = array();
		foreach($result as $collab) {
			$collabs[] = $collab->email;
		}
		return $collabs;
	}
	
	public function delete_collaborators($pid) {
		$this->db->where('pid', $pid)
			->where('access', 'collaborator')
			->delete('dd_collaborators');	
	}
	
	public function add_collaborator($pid,$email,$access='collaborator')
	{
		//if collaborator is owner of the project, don't add
		if ($this->is_owner($pid,$email))
		{
			return FALSE;
		}
	
		$options=array(
			'pid'		=>	$pid,
			'email'		=>	$email,
			'access'	=>	$access
		);
		
		return $this->db->insert('dd_collaborators',$options);	
	}
	
	
	public function get_owner($pid) {
		$q = $this->db->select('*')
			->from('dd_collaborators')
			->where('pid', $pid)
			->where('access', 'owner')
			->order_by('id')
			->limit(1);
		$result = $q->get()->result();
		$collabs = array();
		foreach($result as $collab) {
			$collabs[] = $collab->email;
		}
		return $collabs;		
	}
	
	public function has_collaborator($pid, $email) {
		$q = $this->db->select('*')
			->from('dd_collaborators')
			->where('pid', $pid)
			->where('access', 'collaborator')
			->where('email', $email);
		$result = $q->get()->result();
		return sizeof($result);
	}
	
	
	/**
	*
	* Submit project
	**/	
	public function submit_project($id, $data)	
	{
		$valid_fields=array(
				'title',
				'shortname',
				'description',
				'access_policy',
				'to_catalog',
				'library_notes',
				'cc',
				'submitted_on',
				'is_embargoed',
				'embargoed',
				'disclosure_risk',
				'key_variables',
				'sensitive_variables',
				'status'
		);
		
		$options=array();
		
		foreach($data as $key=>$value)
		{
			if (in_array($key,$valid_fields))
			{
				$options[$key]=$value;
			}
		}
		
		//update project
		$this->db->where('id', $id);
		$result=$this->db->update('dd_projects',$options);
		
		if (!$result)
		{
			return false;
		}
		
		return true;		
	}

	//update project info
	public function update ($id, $data) 
	{
		$valid_fields=array('title','shortname','description','requested_reopen','requested_when','admin_comments','status','last_modified');
		
		$options=array();
		
		if (isset($options['status']) && $options['status']=='draft')
		{
			//reset reopen request for the project if project status is changed to draft
			$data['requested_reopen']=0;
		}
		
		foreach($data as $key=>$value)
		{
			if (in_array($key,$valid_fields))
			{
				$options[$key]=$value;
			}
		}
		
		if (count($options)==0)
		{
			return false;
		}
		
		//update project
		$this->db->where('id', $id);
		$result=$this->db->update('dd_projects',$options);
		
		if (!$result)
		{
			return false;
		}
		
		//don't change collaborators if variable not set
		if (!isset($data['collaborators']))
		{
			return true;
		}
		
		//remove all collaborators except owners
		$this->delete_collaborators($id);
		
		//remove duplicate email addresses
		$data['collaborators']=array_unique($data['collaborators']);
				
		//add collaborators
		foreach($data['collaborators'] as $email)
		{
			if ($email!='')
			{			
				$this->add_collaborator($id,$email,$access='collaborator');
			}
		}
		
		return true;		
	}
	
	
	public function set_study_id($id,$study_id)
	{
		if ($this->get_schema_version($id) >= 2) {
			return false;
		}

		$options=array(
			'ident_ddp_id'=>$study_id
		);
		
		$this->db->where('id',$id);
		return $this->db->update('dd_study',$options);		
	}

	public function get_study_id($id)
	{
		$this->db->select('ident_ddp_id');
		$this->db->where('id',$id);
		$result=$this->db->get('dd_study')->row_array();
		
		if ($result)
		{
			return $result['ident_ddp_id'];
		}
	}

	
	//set project data files folder path
	public function set_project_folder($id,$folder_name)
	{
		$options=array(
			//'id'				=> $id,
			'data_folder_path'	=> $folder_name
		);
		
		$this->db->where('id',$id);
		$this->db->update('dd_projects',$options);
	}



    public function create_project($options)
    {
        //get owner email
        if (!isset($options['owner_email']))
        {
            throw new Exception("OWNER_EMAIL is not set");
        }

        //return the new project id
        return $this->insert($options,$options['owner_email']);
    }



    //create a new project
	public function insert ($data,$owner_email) 
	{
		$options=array();
		foreach($data as $key=>$value)
		{
			if (in_array($key,$this->project_valid_fields))
			{
				$options[$key]=$value;
			}
		}
		
		if (empty($options['data_type'])) {
			$options['data_type'] = 'survey';
		}

		$options['data_folder_path']=md5(date("U"));
		
		$this->db->trans_start();
		
		//create new project record
		if (!$this->db->insert('dd_projects', $options))
		{			
			$this->db->trans_off();
			return FALSE;
		}
		
		//get newly created project id		
		$id= $this->db->insert_id();
		
		if (!is_numeric($id))
		{
			show_error('FAILED_PROJECT_INITIALIZE');
			return FALSE;
		}
		
		//set project data files folder
		$this->set_project_folder($id,'P-'.$id.'-'.date("U"));		
		
		//add project owner and collaborators/////////////////////

		//add project owner
		$this->set_owner($id,$owner_email);

        if(!isset($data['collaborators']))
        {
            $data['collaborators']=array();
        }

		//remove duplicate email addresses
		$data['collaborators']=array_unique($data['collaborators']);
		
		//add collaborators
		foreach($data['collaborators'] as $email)
		{
			if ($email!='')
			{			
				$this->add_collaborator($id,$email,$access='collaborator');
			}
		}
		
		if ($this->db->field_exists('schema_version', 'dd_projects')) {
			$title = isset($data['title']) ? $data['title'] : '';
			$data_type = isset($options['data_type']) ? $options['data_type'] : 'survey';
			$metadata = array();
			if ($data_type === 'survey') {
				$metadata = array(
					'study_desc' => array(
						'title_statement' => array(
							'title' => $title,
						),
					),
				);
			}
			$v2 = array(
				'schema_version' => 2,
			);
			if ($this->db->field_exists('metadata', 'dd_projects')) {
				$v2['metadata'] = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			if ($this->db->field_exists('submission', 'dd_projects')) {
				$v2['submission'] = '{}';
			}
			$this->db->where('id', $id);
			$this->db->update('dd_projects', $v2);
		}
		
		$this->db->trans_complete();
		
		if ($this->db->trans_status() === FALSE)
		{
			show_error("DB_TRANSACTION_FAILED");
		}
		
		return $id;
	}
	
	
	//assign owner for a project
	public function set_owner($pid,$email)
	{
		//delete existing project owner if any
		$this->db->where('pid', $pid);
		$this->db->where('access', 'owner');
		$this->db->delete('dd_collaborators');
		
		//assign owner
		$options=array(
			'pid'		=> $pid,
			'email'		=> $email,
			'access'	=> 'owner'		
		);
		 
		return $this->db->insert('dd_collaborators',$options);
	}

	
	//test if email is owner of the project
	public function is_owner($pid,$email)
	{
		$this->db->select('count(*) as found');
		$this->db->where('pid', $pid);
		$this->db->where('access', 'owner');
		$this->db->where('email', $email);
		$result=$this->db->get('dd_collaborators')->row_array();
		
		if ($result['found']>0)
		{
			return TRUE;
		}
		
		return FALSE;
	}


	public function log_history($data) {
		$this->db->insert('dd_datadeposit_history', $data);
		return $this->db->insert_id();
	}
	
	public function write_history($project_id, $status,$comment,$user_identity=NULL) 
	{
		if (!$user_identity)
		{
			$user_identity=$this->session->userdata('email');
		}
		
		$data = array(
			'project_id'     => (int) $project_id,
			'user_identity'  => $user_identity,
			'created_on'     => date('U'),
			'project_status' => $status,
			'comments'       => $comment,
		);
		$this->log_history($data);
	}
	
	public function history_id($id) {
		$q = $this->db->select('*')
			->from('dd_datadeposit_history')
			->where('project_id', $id)
			->order_by('created_on', 'desc');
		return $q->get()->result();
	}

	public function delete ($id) {		
		$this->db->delete('dd_projects', array('id' => $id));
		// Children tables
		$this->db->delete('dd_collaborators', array('pid' => $id));
   	    $this->db->delete('dd_study', array('id' => $id));
 		$this->db->delete('dd_datadeposit_history', array('project_id' => $id));
	    $this->db->delete('dd_project_resources', array('project_id' => $id));
	}
	
	
	/**
	*
	* Get pending tasks by project
	*
	**/
	public function get_schema_version($id)
	{
		if (!$this->db->field_exists('schema_version', 'dd_projects')) {
			return 1;
		}

		$row = $this->db->select('schema_version')
			->from('dd_projects')
			->where('id', (int) $id)
			->get()
			->row_array();

		return $row && isset($row['schema_version']) ? (int) $row['schema_version'] : 1;
	}

	public function get_metadata_json($id)
	{
		return $this->get_json_column($id, 'metadata');
	}

	public function get_submission_json($id)
	{
		return $this->get_json_column($id, 'submission');
	}

	public function get_json_column($id, $column)
	{
		$allowed = array('metadata', 'submission');
		if (!in_array($column, $allowed, true) || !$this->db->field_exists($column, 'dd_projects')) {
			return array();
		}

		$row = $this->db->select($column)
			->from('dd_projects')
			->where('id', (int) $id)
			->get()
			->row_array();

		if (!$row || !isset($row[$column]) || $row[$column] === null || $row[$column] === '') {
			return array();
		}

		$decoded = json_decode($row[$column], true);
		return is_array($decoded) ? $decoded : array();
	}

	public function save_json_column($id, $column, array $data)
	{
		$allowed = array('metadata', 'submission');
		if (!in_array($column, $allowed, true) || !$this->db->field_exists($column, 'dd_projects')) {
			return false;
		}

		$encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($encoded === false) {
			return false;
		}

		$options = array($column => $encoded);
		if ($this->db->field_exists('last_modified', 'dd_projects')) {
			$options['last_modified'] = date('U');
		}

		$this->db->where('id', (int) $id);
		return $this->db->update('dd_projects', $options);
	}

	public function get_pending_tasks($pid)
	{
		$output=array(
			'incomplete_study_fields'	=>0,
			'attached_files'			=>0,
			'attached_citations'		=>0
		);

		if ($this->get_schema_version($pid) >= 2) {
			$metadata = $this->get_metadata_json($pid);
			$title = '';
			if (isset($metadata['study_desc']['title_statement']['title'])) {
				$title = trim((string) $metadata['study_desc']['title_statement']['title']);
			}
			$nations = isset($metadata['study_desc']['study_info']['nation'])
				? $metadata['study_desc']['study_info']['nation']
				: array();
			if ($title === '') {
				$output['incomplete_study_fields']++;
			}
			if (!is_array($nations) || count($nations) === 0) {
				$output['incomplete_study_fields']++;
			}
			if (isset($metadata['citations']) && is_array($metadata['citations'])) {
				$output['attached_citations'] = count($metadata['citations']);
			}
		} else {
			//check all required fields are filled
			$this->db->select('coverage_country,coll_dates');
			$this->db->where('id',$pid);
			$study_row=$this->db->get('dd_study')->row_array();
			
			if ($study_row)
			{
				foreach($study_row as $key=>$value)
				{
					$value=json_decode($value);
					if(!$value)
					{
						$output['incomplete_study_fields']++;
					}
				}
			}
		}

		//check data or other resources are uploaded
		$this->db->select('count(*) as total');
		$this->db->where('project_id',$pid);
		$resources_count=$this->db->get('dd_project_resources')->row_array();
		
		if($resources_count)
		{
			$output['attached_files']=$resources_count['total'];
		}
		
		if ($this->get_schema_version($pid) < 2) {
			//check if citations been attached
			$this->db->select('count(*) as total');
			$this->db->where('pid',$pid);
			$citations_count=$this->db->get('dd_citations')->row_array();
			
			if($citations_count)
			{
				$output['attached_citations']=$citations_count['total'];
			}
		}
		
		return $output;
	}
	
	
	public function get_project_fullpath($project_id)
	{
		//get root folder path
		$root_folder=$this->get_datadeposit_root_folder();

		if (!$root_folder)
		{
			return FALSE;
		}
	
		//get project folder name
		$folder_name=$this->get_project_folder_name($project_id);
		
		if (!$folder_name)
		{
			return FALSE;
		}
		
		return unix_path($root_folder.'/'.$folder_name);
	}
	
	public function get_project_folder_name($project_id)
	{
		$this->db->select('data_folder_path');
		$this->db->where('id',$project_id);
		$row=$this->db->get('dd_projects')->row_array();
		
		if($row)
		{
			return $row['data_folder_path'];
		}
		
		return FALSE;
	}
	
	//get path to data deposit root folder where all projects are stored
	public function get_datadeposit_root_folder()
	{
		$ci =& get_instance();
		
		//load datadeposit config file
		$ci->config->load('datadeposit');
		
		//load data deposit settings array
		$datadeposit_configurations = $ci->config->item('datadeposit');
		
		//get path to the root folder
		$root_folder = $datadeposit_configurations['resources'];
		
		if (empty($root_folder) || !is_dir($root_folder) || trim($root_folder)=="")
		{
			return FALSE;
		}
		
		return $root_folder;
	}

	public function stats() {
		$stats = array();
		$stats['submitted'] = $this->db->where('status', 'submitted')->count_all_results('dd_projects');
		$stats['requested'] = $this->db->where('requested_reopen', '1')->count_all_results('dd_projects');
		$stats['processed'] = $this->db->where('status', 'processed')->count_all_results('dd_projects');
		$stats['draft']     = $this->db->where('status', 'draft')->count_all_results('dd_projects');
		return $stats;
	}
	
	public function admin_project_counts()
	{
		$counts = array(
			'all' => 0,
			'draft' => 0,
			'submitted' => 0,
			'processed' => 0,
			'accepted' => 0,
			'closed' => 0,
			'requested' => 0,
		);

		$rows = $this->db
			->select('status, COUNT(*) AS n')
			->from('dd_projects')
			->group_by('status')
			->get()
			->result();

		foreach ($rows as $row) {
			$n = (int) $row->n;
			$counts['all'] += $n;
			$key = strtolower((string) $row->status);
			if (isset($counts[$key])) {
				$counts[$key] = $n;
			}
		}

		$counts['requested'] = (int) $this->db
			->where('requested_reopen', 1)
			->count_all_results('dd_projects');

		return $counts;
	}

	public function all_projects_by_filter($status=NULL, $order='created_on', $order_by='desc',$search_keywords=NULL, $requested_reopen=false)
	{
		$sort_map = array(
			'status' => 'dd_projects.status',
			'title' => 'dd_projects.title',
			'last_modified' => 'dd_projects.last_modified',
			'created_on' => 'dd_projects.created_on',
			'created_by' => 'dd_projects.created_by',
		);
		$order_key = is_string($order) ? $order : 'created_on';
		$order_col = isset($sort_map[$order_key])
			? $sort_map[$order_key]
			: (in_array($order_key, $sort_map, true) ? $order_key : 'dd_projects.created_on');
		$dir = (strtolower((string) $order_by) === 'asc') ? 'asc' : 'desc';

		$q = $this->db->select('dd_projects.id,dd_projects.title,dd_projects.status,dd_projects.shortname, dd_projects.last_modified, dd_projects.created_on,dd_projects.created_by, dd_projects.requested_reopen, dd_tasks.id as task_id,dd_tasks.user_id as task_user_id, users.username as task_user, dd_tasks.status as task_status')
			->from('dd_projects')
			->join('dd_tasks','dd_tasks.project_id=dd_projects.id','left')
			->join('users','dd_tasks.user_id=users.id','left')
			->order_by($order_col, $dir);

		if ($requested_reopen) {
			$this->db->where('dd_projects.requested_reopen', 1);
		} elseif ($status) {
			$this->db->where('dd_projects.status', $status);
		}

		if ($search_keywords) {
			$keywords_arr = explode(' ', $search_keywords);
			foreach ($keywords_arr as $keyword) {
				$keyword = trim($keyword);
				if ($keyword === '') {
					continue;
				}
				$escaped_keywords = $this->db->escape('%'.$keyword.'%');
				$where = sprintf(
					'(dd_projects.title like %s OR dd_projects.description like %s OR dd_projects.created_by like %s OR dd_projects.shortname like %s)',
					$escaped_keywords,
					$escaped_keywords,
					$escaped_keywords,
					$escaped_keywords
				);
				$this->db->where($where, NULL, FALSE);
			}
		}

		return $q->get()->result();
	}

	public function all_projects_requested_reopen($order='created_on', $order_by='desc', $search_keywords=NULL) {
		return $this->all_projects_by_filter(NULL, $order, $order_by, $search_keywords, true);
	}

}
