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
        'status'
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
	
	public function import_from_project($uid, $from_id, $to_id) 
	{
		$ci =& get_instance();
		$ci->load->model('DD_study_model');
		$uid = (int) $uid;

		$q = $this->db->select('id, email')
			->from('users')
			->where('id', $uid);
			
		$user = $q->get()->result();
		
		if (!$this->has_access($from_id, $user[0]->email) || !$this->has_access($to_id, $user[0]->email)) {
			return false;
		}

		$data   = $this->DD_study_model->get_study($from_id);
		$insert = array();
		
		foreach((array)$data[0] as $key => $field) {
			$insert[$key] = $field;
		}
		
		unset($insert['id']);
		unset($insert['ident_title']);
		$this->DD_study_model->update_study($to_id, $insert);
		return true;
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
	public function projects ($uid, $order='created_on', $order_by = 'desc', $limit = 1000, $offset = 0) 
	{
		$uid = (int) $uid;
		$q = $this->db->select('id, email')
			->from('users')
			->where('id', $uid);
			
		$user = $q->get()->result();
		
		//return projects owned and collborated by the user
		$q = $this->db->select('dd_projects.*, dd_collaborators.access')
			->from('dd_projects')
			->join('dd_collaborators','dd_collaborators.pid=dd_projects.id','left')
			->where('dd_collaborators.email', $user[0]->email)
			->order_by($order, $order_by);

	  return $q->get()->result();
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
		
		//create study description row
		$study_options=array(
				'id'          => $id,
				'ident_title' => $data['title']
		);
		
		$this->db->insert('dd_study',$study_options);
		
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
	public function get_pending_tasks($pid)
	{
		$output=array(
			'incomplete_study_fields'	=>0,
			'attached_files'			=>0,
			'attached_citations'		=>0
		);
		
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

		//check data or other resources are uploaded
		$this->db->select('count(*) as total');
		$this->db->where('project_id',$pid);
		$resources_count=$this->db->get('dd_project_resources')->row_array();
		
		if($resources_count)
		{
			$output['attached_files']=$resources_count['total'];
		}
		
		//check if citations been attached
		$this->db->select('count(*) as total');
		$this->db->where('pid',$pid);
		$citations_count=$this->db->get('dd_citations')->row_array();
		
		if($citations_count)
		{
			$output['attached_citations']=$citations_count['total'];
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
	
	public function all_projects_by_filter($status=NULL, $order='dd_projects.created_on', $order_by='desc',$search_keywords=NULL)
	{
        $order="dd_projects.created_on";
		$q = $this->db->select('dd_projects.id,dd_projects.title,dd_projects.status,dd_projects.shortname, dd_projects.last_modified, dd_projects.created_on,dd_projects.created_by, dd_tasks.id as task_id,dd_tasks.user_id as task_user_id, users.username as task_user, dd_tasks.status as task_status')
			->from('dd_projects')
            ->join('dd_tasks','dd_tasks.project_id=dd_projects.id','left')
            ->join('users','dd_tasks.user_id=users.id','left')
			->order_by($order, $order_by);

			if ($status){
				$this->db->where('dd_projects.status', $status);
			}

            if($search_keywords)
            {
                $keywords_arr=explode(" ",$search_keywords);
                foreach($keywords_arr as $keyword) {
                    $escaped_keywords = $this->db->escape('%'.$keyword.'%');
                    $where = sprintf('(title like %s OR description like %s OR created_by like %s OR shortname like %s)',
                        $escaped_keywords,
                        $escaped_keywords,
                        $escaped_keywords,
                        $escaped_keywords
                    );
                    $this->db->where($where,NULL,FALSE);
                }
            }

        $result=$q->get()->result();
		return $result;
	}

	public function all_projects_requested_reopen() {
		$q = $this->db->select('*')
			->from('dd_projects')
			->where('requested_reopen', 1);
		return $q->get()->result();
	}


	private function decode_json_data($data) {
		return ($data) ? json_decode($data) : null;
	}

	//get project summary
	public function get_project_summary($id)
	{		
		$ci =& get_instance();
		
		$ci->load->model('DD_resource_model');
		$ci->load->model('DD_study_model');
		$ci->load->model('DD_citation_model');

		//get request data
        $data['project'][0] = (object)$this->get_by_id($id);
		$data['row']     = $ci->DD_study_model->get_study($data['project'][0]->id);
		$data['files']   = $ci->DD_resource_model->get_project_resources_to_array($id);
		$data['fields']  = $ci->config->item('datadeposit');
		$data['citations']=$ci->DD_citation_model->get_citations_by_project($id);
		
		//get project owner
		$data['project'][0]->owner=$this->get_owner($id);
            
       //get project collaborators
        $data['project'][0]->collaborators=$this->get_collaborators($id);
		
        $this->_study_grid_ids         = array();
		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
			);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->decode_json_data((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);

		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) 
		{
			//$data[$grid_id] = $this->_summary_study_grid($grid_id, $grid_data, true);
			$data[$grid_id] = $this->print_single_grid($grid_data['titles'],$grid_data['data']);
		}	
		
		$content     = $this->load->view('datadeposit/project_review', $data, true);
		
		return $content;
	}
	
	
	private function print_single_grid($columns,$data)
	{
		if (!$data)
		{
			return;
		}
	
		$output= '<table border="1" class="grid-table">';
		$output.= '<tr class="grid-table-header">';
		foreach($columns as $column)
		{
			$output.= '<th>'.$column.'</th>';
		}
		$output.= '</tr>';
		
		//$data=json_decode($data);
		
		foreach($data as $row)
		{
			$output.= '<tr>';
			foreach($row as $value)
			{
				$output.= '<td>'.$value.'</td>';
			}
			$output.= '</tr>';
		}
		
		$output.= '</table>';
		
		return $output;
	}

	
}

