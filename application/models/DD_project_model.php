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
		
        
        
	/**
	 * Project rows (with the caller's access level) when the user is a member of the project.
	 *
	 * @param int $id project id
	 * @param string $email the user's current email (matches invites not linked to an account yet)
	 * @param int|null $user_id the user's id; looked up from $email when not given
	 */
	public function project_id ($id, $email, $user_id = null) {

		$user_id = $this->member_user_id($user_id, $email);
		$this->link_collaborations_for_user($user_id);

		$this->db->select('dd_projects.*, dd_collaborators.access')
			->from('dd_projects')
			->join('dd_collaborators', 'dd_collaborators.pid = dd_projects.id', 'inner')
			->where('dd_collaborators.pid', $id);
		$this->where_member($user_id, $email);

		$query=$this->db->get();
		
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
	public function is_locked($id, $email, $user_id = null)
	{
		$projects = $this->project_id($id, $email, $user_id);
		if (isset($projects[0]->id) && $projects[0]->status=='draft')
		{
			return false;
		}
		
		return true;
	}


	public function has_access($id, $email, $user_id = null)
	{
		$projects = $this->project_id($id, $email, $user_id);
		return isset($projects[0]->id);
	}

	public function has_access_or_die($id, $email, $user_id = null)
	{
		if(!$this->has_access($id, $email, $user_id))
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
		$this->link_collaborations_for_user($uid);

		$allowed = array('title', 'created_by', 'status', 'created_on', 'last_modified', 'id');
		if (!in_array($order, $allowed, true)) {
			$order = 'created_on';
		}
		$order_by = strtolower((string) $order_by) === 'asc' ? 'asc' : 'desc';

		$this->db->select('dd_projects.*, dd_collaborators.access');
		$this->_projects_for_user($uid, $email, $keywords, $status);
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
		$this->link_collaborations_for_user($uid);

		$this->db->select('dd_projects.id');
		$this->_projects_for_user($uid, $email, $keywords, $status);
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

	private function _projects_for_user($uid, $email, $keywords = '', $status = '')
	{
		$this->db->from('dd_projects');
		$this->db->join('dd_collaborators', 'dd_collaborators.pid=dd_projects.id', 'inner');
		$this->where_member($uid, $email);

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
		return $this->member_emails($pid, 'collaborator', 0);
	}

	/**
	 * Member emails for a project. A row linked to an account reports the account's current
	 * email (so notifications follow an email change); an unlinked invite reports the stored email.
	 *
	 * @param int $pid
	 * @param string $access owner|collaborator
	 * @param int $limit 0 = all
	 * @return string[]
	 */
	private function member_emails($pid, $access, $limit = 0)
	{
		$linked = $this->collaborators_have_user_id();
		$this->db->select($linked
				? 'dd_collaborators.email AS stored_email, users.email AS user_email'
				: 'dd_collaborators.email AS stored_email')
			->from('dd_collaborators')
			->where('dd_collaborators.pid', $pid)
			->where('dd_collaborators.access', $access)
			->order_by('dd_collaborators.id');
		if ($linked) {
			$this->db->join('users', 'users.id = dd_collaborators.user_id', 'left');
		}
		if ($limit > 0) {
			$this->db->limit($limit);
		}
		$emails = array();
		foreach ($this->db->get()->result() as $row) {
			$email = ($linked && !empty($row->user_email)) ? $row->user_email : $row->stored_email;
			if ($email !== '' && !in_array($email, $emails, true)) {
				$emails[] = $email;
			}
		}
		return $emails;
	}
	
	public function delete_collaborators($pid) {
		$this->db->where('pid', $pid)
			->where('access', 'collaborator')
			->delete('dd_collaborators');	
	}
	
	public function add_collaborator($pid,$email,$access='collaborator')
	{
		$email = trim((string) $email);
		$user_id = $this->resolve_user_id_for_email($email);

		//if collaborator is owner of the project, don't add
		if ($this->is_owner($pid, $email, $user_id))
		{
			return FALSE;
		}

		//same account listed twice (e.g. old and new email domain)
		if ($user_id && $this->member_row_count($pid, $user_id, '', null) > 0)
		{
			return FALSE;
		}

		$options=array(
			'pid'		=>	$pid,
			'email'		=>	$email,
			'access'	=>	$access
		);
		if ($this->collaborators_have_user_id()) {
			$options['user_id'] = $user_id;
		}

		return $this->db->insert('dd_collaborators',$options);
	}


	public function get_owner($pid) {
		return $this->member_emails($pid, 'owner', 1);
	}

	public function has_collaborator($pid, $email) {
		$email = trim((string) $email);
		return $this->member_row_count($pid, $this->resolve_user_id_for_email($email), $email, 'collaborator');
	}

	/**
	 * Rows on a project for an account (by user id) or an unlinked invite (by email).
	 *
	 * @param int $pid
	 * @param int|null $user_id
	 * @param string $email
	 * @param string|null $access null = any
	 * @return int
	 */
	private function member_row_count($pid, $user_id, $email, $access)
	{
		$this->db->from('dd_collaborators')->where('dd_collaborators.pid', $pid);
		if ($access !== null) {
			$this->db->where('dd_collaborators.access', $access);
		}
		$this->where_member($user_id, $email);
		return (int) $this->db->count_all_results();
	}


	/* ------------------------------------------------------------------
	 * Collaborator accounts (dd_collaborators.user_id)
	 *
	 * Membership is by user id. An email with no account yet is stored with user_id NULL
	 * (an invite); it is linked to the account the first time that user uses data deposit,
	 * or when the account exists at the time the email is added. Linked rows keep working
	 * when the account's email changes (for example a move to a new email domain).
	 *
	 * Optional: config email_domain_equivalence (auth.php / auth.local.php) treats the same
	 * mailbox name across the listed domains as one person, so an invite to user@old.org
	 * links to the account user@new.org. An ambiguous name (accounts in more than one of the
	 * domains) is not linked unless require_unique_local_part is false.
	 * ------------------------------------------------------------------ */

	/** @var bool|null */
	private $collab_user_id_column = null;

	/** @var array user ids already linked in this request */
	private $linked_user_ids = array();

	/** @var array|null */
	private $domain_equivalence = null;

	/**
	 * True once the dd_collaborators.user_id migration has run.
	 */
	public function collaborators_have_user_id()
	{
		if ($this->collab_user_id_column === null) {
			$this->collab_user_id_column = $this->db->table_exists('dd_collaborators')
				&& $this->db->field_exists('user_id', 'dd_collaborators');
		}
		return $this->collab_user_id_column;
	}

	/**
	 * Restrict the current query to dd_collaborators rows of one member: rows linked to the
	 * user id, plus unlinked invites to the email. Runs no query of its own.
	 *
	 * @param int|null $user_id
	 * @param string $email
	 */
	public function where_member($user_id, $email)
	{
		$user_id = (int) $user_id;
		$email = trim((string) $email);

		if (!$this->collaborators_have_user_id()) {
			$this->db->where('dd_collaborators.email', $email);
			return;
		}

		$parts = array();
		if ($user_id > 0) {
			$parts[] = 'dd_collaborators.user_id = ' . $user_id;
		}
		if ($email !== '') {
			$parts[] = '(dd_collaborators.user_id IS NULL AND dd_collaborators.email = ' . $this->db->escape($email) . ')';
		}
		if (empty($parts)) {
			$parts[] = '1 = 0';
		}
		$this->db->where('(' . implode(' OR ', $parts) . ')', null, false);
	}

	/**
	 * Link the user's pending invites (user_id NULL, email matching the account) to the account.
	 * Cheap to call on every request; runs at most once per user per request.
	 *
	 * @param int $user_id
	 * @return int rows linked
	 */
	public function link_collaborations_for_user($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id < 1 || isset($this->linked_user_ids[$user_id]) || !$this->collaborators_have_user_id()) {
			return 0;
		}
		$this->linked_user_ids[$user_id] = true;

		$email = $this->normalize_email($this->_user_email_by_uid($user_id));
		if ($email === '') {
			return 0;
		}

		$claim = array();
		foreach ($this->email_variants($email) as $variant) {
			if ($variant === $email || $this->resolve_user_id_for_email($variant) === $user_id) {
				$claim[] = $variant;
			}
		}

		$this->db->where('user_id IS NULL', null, false)
			->where_in('email', $claim)
			->update('dd_collaborators', array('user_id' => $user_id));
		$linked = (int) $this->db->affected_rows();

		if ($linked > 0) {
			$this->remove_duplicate_memberships($user_id);
		}
		return $linked;
	}

	/**
	 * Link every unlinked collaborator row whose email belongs to an account.
	 * Used by the migration and by `php index.php cli/datadeposit/link_collaborators`.
	 * Safe to run again (for example after enabling email_domain_equivalence).
	 *
	 * @return array counts: emails, linked_rows, unmatched_emails, duplicates_removed
	 */
	public function link_all_collaborators()
	{
		$this->collab_user_id_column = null;
		$stats = array('emails' => 0, 'linked_rows' => 0, 'unmatched_emails' => 0, 'duplicates_removed' => 0);
		if (!$this->collaborators_have_user_id()) {
			return $stats;
		}

		$rows = $this->db->distinct()->select('email')
			->from('dd_collaborators')
			->where('user_id IS NULL', null, false)
			->get()->result();

		$touched = array();
		foreach ($rows as $row) {
			$stats['emails']++;
			$user_id = $this->resolve_user_id_for_email($row->email);
			if (!$user_id) {
				$stats['unmatched_emails']++;
				continue;
			}
			$this->db->where('user_id IS NULL', null, false)
				->where('email', $row->email)
				->update('dd_collaborators', array('user_id' => $user_id));
			$stats['linked_rows'] += (int) $this->db->affected_rows();
			$touched[$user_id] = true;
		}

		foreach (array_keys($touched) as $user_id) {
			$stats['duplicates_removed'] += $this->remove_duplicate_memberships($user_id);
		}
		return $stats;
	}

	/**
	 * Account id for an email: an active account with that email, else (with domain equivalence)
	 * the single active account with the same mailbox name in an equivalent domain.
	 *
	 * @param string $email
	 * @return int|null
	 */
	public function resolve_user_id_for_email($email)
	{
		$email = $this->normalize_email($email);
		if ($email === '' || strpos($email, '@') === false) {
			return null;
		}

		$row = $this->db->select('id')->from('users')
			->where('email', $email)
			->where('active', 1)
			->order_by('id')
			->limit(1)
			->get()->row();
		if ($row) {
			return (int) $row->id;
		}

		$others = array_values(array_diff($this->email_variants($email), array($email)));
		if (empty($others)) {
			return null;
		}

		$rows = $this->db->select('id')->from('users')
			->where_in('email', $others)
			->where('active', 1)
			->order_by('id')
			->get()->result();
		if (count($rows) === 1) {
			return (int) $rows[0]->id;
		}
		if (count($rows) > 1) {
			$equiv = $this->get_domain_equivalence();
			if (empty($equiv['require_unique_local_part'])) {
				return (int) $rows[0]->id;
			}
			log_message('error', 'dd_collaborators: ' . $email . ' matches more than one account across equivalent domains; not linked');
		}
		return null;
	}

	/**
	 * The email plus the same mailbox name in each equivalent domain (lower case).
	 *
	 * @param string $email
	 * @return string[]
	 */
	private function email_variants($email)
	{
		$email = $this->normalize_email($email);
		$out = array($email);
		$pos = strrpos($email, '@');
		if ($pos === false || $pos === 0) {
			return $out;
		}
		$local = substr($email, 0, $pos);
		$domain = substr($email, $pos + 1);

		$equiv = $this->get_domain_equivalence();
		if (empty($equiv['domains']) || !in_array($domain, $equiv['domains'], true)) {
			return $out;
		}
		foreach ($equiv['domains'] as $d) {
			$out[] = $local . '@' . $d;
		}
		return array_values(array_unique($out));
	}

	/**
	 * @return array domains (empty when not enabled), require_unique_local_part
	 */
	private function get_domain_equivalence()
	{
		if ($this->domain_equivalence !== null) {
			return $this->domain_equivalence;
		}

		$cfg = $this->config->item('email_domain_equivalence');
		if (!is_array($cfg)) {
			$this->config->load('auth', FALSE, TRUE);
			$cfg = $this->config->item('email_domain_equivalence');
		}

		$out = array('domains' => array(), 'require_unique_local_part' => true);
		if (is_array($cfg) && !empty($cfg['enabled'])
			&& (!isset($cfg['local_part_cross_domain']) || $cfg['local_part_cross_domain'])) {
			$domains = isset($cfg['domains']) && is_array($cfg['domains']) ? $cfg['domains'] : array();
			foreach ($domains as $d) {
				$d = strtolower(trim((string) $d));
				if ($d !== '') {
					$out['domains'][] = $d;
				}
			}
			$out['domains'] = array_values(array_unique($out['domains']));
			$out['require_unique_local_part'] = !isset($cfg['require_unique_local_part']) || $cfg['require_unique_local_part'];
		}
		return $this->domain_equivalence = $out;
	}

	/**
	 * One row per project for a user: keep the owner row (else the oldest), delete the rest.
	 *
	 * @param int $user_id
	 * @return int rows deleted
	 */
	private function remove_duplicate_memberships($user_id)
	{
		$rows = $this->db->select('id, pid, access')
			->from('dd_collaborators')
			->where('user_id', (int) $user_id)
			->order_by('pid')
			->order_by('id')
			->get()->result();

		$keep = array();
		foreach ($rows as $row) {
			$pid = (int) $row->pid;
			if (!isset($keep[$pid]) || ($row->access === 'owner' && $keep[$pid]->access !== 'owner')) {
				$keep[$pid] = $row;
			}
		}

		$delete = array();
		foreach ($rows as $row) {
			if ((int) $keep[(int) $row->pid]->id !== (int) $row->id) {
				$delete[] = (int) $row->id;
			}
		}
		if (!empty($delete)) {
			$this->db->where_in('id', $delete)->delete('dd_collaborators');
		}
		return count($delete);
	}

	/**
	 * @param int|null $user_id
	 * @param string $email
	 * @return int|null
	 */
	private function member_user_id($user_id, $email)
	{
		if ((int) $user_id > 0) {
			return (int) $user_id;
		}
		$email = $this->normalize_email($email);
		if ($email === '') {
			return null;
		}
		$row = $this->db->select('id')->from('users')
			->where('email', $email)
			->order_by('id')
			->limit(1)
			->get()->row();
		return $row ? (int) $row->id : null;
	}

	private function normalize_email($email)
	{
		return strtolower(trim((string) $email));
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
        return $this->insert(
            $options,
            $options['owner_email'],
            isset($options['owner_user_id']) ? $options['owner_user_id'] : null
        );
    }



    //create a new project
	public function insert ($data, $owner_email, $owner_user_id = null)
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
		$this->set_owner($id, $owner_email, $owner_user_id);

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
	public function set_owner($pid, $email, $user_id = null)
	{
		$email = trim((string) $email);
		if ((int) $user_id < 1) {
			$user_id = $this->resolve_user_id_for_email($email);
		}

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
		if ($this->collaborators_have_user_id()) {
			$options['user_id'] = $user_id ? (int) $user_id : null;
		}

		return $this->db->insert('dd_collaborators',$options);
	}


	//test if email (or account) is owner of the project
	public function is_owner($pid, $email, $user_id = null)
	{
		$email = trim((string) $email);
		if ((int) $user_id < 1) {
			$user_id = $this->resolve_user_id_for_email($email);
		}
		$result = array('found' => $this->member_row_count($pid, $user_id, $email, 'owner'));

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
			'embargoed' => 0,
		);

		// One pass over the table for all tab counts.
		$rows = $this->db
			->select('status, COUNT(*) AS n,'
				.' SUM(CASE WHEN requested_reopen = 1 THEN 1 ELSE 0 END) AS n_requested,'
				.' SUM(CASE WHEN is_embargoed = 1 THEN 1 ELSE 0 END) AS n_embargoed', FALSE)
			->from('dd_projects')
			->group_by('status')
			->get()
			->result();

		foreach ($rows as $row) {
			$n = (int) $row->n;
			$counts['all'] += $n;
			$counts['requested'] += (int) $row->n_requested;
			$counts['embargoed'] += (int) $row->n_embargoed;
			$key = strtolower((string) $row->status);
			if (isset($counts[$key])) {
				$counts[$key] += $n;
			}
		}

		return $counts;
	}

	/**
	 * Admin project list with filters and pagination.
	 *
	 * @param array $params status, requested_reopen, keywords, embargo (all|yes|no),
	 *                      created_by, order, order_by, limit, offset
	 * @return array{items: array, total: int}
	 */
	public function search_admin_projects(array $params = array())
	{
		$status = isset($params['status']) ? $params['status'] : NULL;
		$requested_reopen = !empty($params['requested_reopen']);
		$search_keywords = isset($params['keywords']) ? $params['keywords'] : NULL;
		$embargo = isset($params['embargo']) ? strtolower((string) $params['embargo']) : 'all';
		$created_by = isset($params['created_by']) ? trim((string) $params['created_by']) : '';
		$order = isset($params['order']) ? $params['order'] : 'created_on';
		$order_by = isset($params['order_by']) ? $params['order_by'] : 'desc';
		$limit = isset($params['limit']) ? (int) $params['limit'] : 25;
		$offset = isset($params['offset']) ? max(0, (int) $params['offset']) : 0;

		if (!in_array($embargo, array('all', 'yes', 'no'), true)) {
			$embargo = 'all';
		}

		$total = $this->count_admin_projects($status, $requested_reopen, $search_keywords, $embargo, $created_by);

		$sort_map = array(
			'status' => 'dd_projects.status',
			'title' => 'dd_projects.title',
			'last_modified' => 'dd_projects.last_modified',
			'created_on' => 'dd_projects.created_on',
			'created_by' => 'dd_projects.created_by',
			'is_embargoed' => 'dd_projects.is_embargoed',
		);
		$order_key = is_string($order) ? $order : 'created_on';
		$order_col = isset($sort_map[$order_key])
			? $sort_map[$order_key]
			: (in_array($order_key, $sort_map, true) ? $order_key : 'dd_projects.created_on');
		$dir = (strtolower((string) $order_by) === 'asc') ? 'asc' : 'desc';

		$this->db->select(
			'dd_projects.id,dd_projects.title,dd_projects.status,dd_projects.shortname,'
			.' dd_projects.last_modified, dd_projects.created_on,dd_projects.created_by,'
			.' dd_projects.is_embargoed, dd_projects.requested_reopen,'
			.' dd_tasks.id as task_id,dd_tasks.user_id as task_user_id,'
			.' users.username as task_user, dd_tasks.status as task_status'
		);
		$this->db->from('dd_projects');
		$this->db->join('dd_tasks', 'dd_tasks.project_id=dd_projects.id', 'left');
		$this->db->join('users', 'dd_tasks.user_id=users.id', 'left');
		$this->apply_admin_projects_filters($status, $requested_reopen, $search_keywords, $embargo, $created_by);
		$this->db->order_by($order_col, $dir);

		if ($limit > 0) {
			$this->db->limit($limit, $offset);
		}

		return array(
			'items' => $this->db->get()->result(),
			'total' => $total,
		);
	}

	/**
	 * @param string|null $status
	 * @param bool $requested_reopen
	 * @param string|null $search_keywords
	 * @param string $embargo
	 * @param string $created_by
	 * @return int
	 */
	public function count_admin_projects($status = NULL, $requested_reopen = false, $search_keywords = NULL, $embargo = 'all', $created_by = '')
	{
		$this->db->from('dd_projects');
		$this->apply_admin_projects_filters($status, $requested_reopen, $search_keywords, $embargo, $created_by);
		return (int) $this->db->count_all_results();
	}

	/**
	 * @param string|null $status
	 * @param bool $requested_reopen
	 * @param string|null $search_keywords
	 * @param string $embargo
	 * @param string $created_by
	 */
	private function apply_admin_projects_filters($status, $requested_reopen, $search_keywords, $embargo, $created_by)
	{
		if ($requested_reopen) {
			$this->db->where('dd_projects.requested_reopen', 1);
		} elseif ($status) {
			$this->db->where('dd_projects.status', $status);
		}

		if ($embargo === 'yes') {
			$this->db->where('dd_projects.is_embargoed', 1);
		} elseif ($embargo === 'no') {
			$this->db->where('(dd_projects.is_embargoed IS NULL OR dd_projects.is_embargoed = 0)', NULL, FALSE);
		}

		if ($created_by !== '') {
			$this->db->like('dd_projects.created_by', $created_by, 'both');
		}

		if ($search_keywords) {
			$keywords_arr = explode(' ', $search_keywords);
			foreach ($keywords_arr as $keyword) {
				$keyword = trim($keyword);
				if ($keyword === '') {
					continue;
				}
				$this->db->where($this->_admin_projects_keyword_where($keyword), NULL, FALSE);
			}
		}
	}

	/**
	 * Keyword match for admin project search: project fields, depositor email,
	 * collaborator email, and registered user email / username / name (meta).
	 *
	 * @param string $keyword
	 * @return string
	 */
	private function _admin_projects_keyword_where($keyword)
	{
		$like = $this->db->escape('%'.$keyword.'%');

		// The sqlsrv driver escapes strings as N'...'. Against these varchar columns that makes
		// SQL Server convert every column value to nvarchar for each LIKE, which made a search
		// take seconds. ASCII matches the same either way, so send it as a plain '...' literal;
		// keep N'...' for non-ASCII keywords so those still match.
		if ($this->db->dbdriver === 'sqlsrv' && !preg_match('/[^\x20-\x7E]/', $keyword)) {
			$like = "'".$this->db->escape_str('%'.$keyword.'%')."'";
		}

		return sprintf(
			'(dd_projects.title like %1$s OR dd_projects.description like %1$s OR dd_projects.shortname like %1$s'
			.' OR dd_projects.created_by like %1$s'
			.' OR EXISTS (SELECT 1 FROM dd_collaborators dc WHERE dc.pid = dd_projects.id AND dc.email like %1$s)'
			// Depositor by email, then by username: separate EXISTS so each can seek its own
			// users index (an OR across both columns forces a users scan per project).
			.' OR EXISTS (SELECT 1 FROM users u'
			.' LEFT JOIN meta m ON m.user_id = u.id'
			.' WHERE u.email = dd_projects.created_by'
			.' AND (u.email like %1$s OR u.username like %1$s OR m.first_name like %1$s OR m.last_name like %1$s))'
			.' OR EXISTS (SELECT 1 FROM users u'
			.' LEFT JOIN meta m ON m.user_id = u.id'
			.' WHERE u.username = dd_projects.created_by'
			.' AND (u.email like %1$s OR u.username like %1$s OR m.first_name like %1$s OR m.last_name like %1$s))'
			.' OR EXISTS (SELECT 1 FROM dd_collaborators dc'
			.' INNER JOIN users u ON u.email = dc.email'
			.' LEFT JOIN meta m ON m.user_id = u.id'
			.' WHERE dc.pid = dd_projects.id'
			.' AND (u.email like %1$s OR u.username like %1$s OR m.first_name like %1$s OR m.last_name like %1$s)))',
			$like
		);
	}

	public function all_projects_by_filter($status=NULL, $order='created_on', $order_by='desc',$search_keywords=NULL, $requested_reopen=false)
	{
		$result = $this->search_admin_projects(array(
			'status' => $status,
			'requested_reopen' => $requested_reopen,
			'keywords' => $search_keywords,
			'order' => $order,
			'order_by' => $order_by,
			'limit' => 0,
			'offset' => 0,
		));
		return $result['items'];
	}

	public function all_projects_requested_reopen($order='created_on', $order_by='desc', $search_keywords=NULL) {
		$result = $this->search_admin_projects(array(
			'requested_reopen' => true,
			'keywords' => $search_keywords,
			'order' => $order,
			'order_by' => $order_by,
			'limit' => 0,
			'offset' => 0,
		));
		return $result['items'];
	}

}
