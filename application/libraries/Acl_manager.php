<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Laminas\Permissions\Acl\Acl as Acl;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;

class Acl_manager
{
	var $debug=false;
	private $ci;

	/** @var array keyed by strtolower(repositoryid): int|false — repositories.id cache, false = unknown slug */
	private $repository_acl_repository_pk_cache = array();
	

	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Acl_manager Class Initialized.");
		$this->ci =& get_instance();
		//$this->ci->load->model('Permissions_model');
		$this->ci->load->model('repository_model');

		if ($this->ci->config->item('acl_debug')==true){
			$this->debug=true;
		}

		$this->ci->load->config('acl_permissions');
	}


	/**
	 * Site-wide permission catalog for role UI (acl_permissions config). Per-collection grants use repositories_acl.
	 *
	 * @return array
	 */
	function get_all_permissions()
	{
		return $this->ci->config->item('acl_permissions');
	}

	/**
	 * 
	 * Return a list of all roles
	 */
	function get_roles()
	{
		$this->ci->db->select("*");
		$this->ci->db->order_by("weight");
		$this->ci->db->order_by("name");
		return $this->ci->db->get("roles")->result_array();
	}

	function get_role_by_name($role_name)
	{
		$this->ci->db->select("*");
		$this->ci->db->where("name",$role_name);
		return $this->ci->db->get("roles")->row_array();
	}

	function get_role_by_id($role_id)
	{
		$this->ci->db->select("*");
		$this->ci->db->where("id",$role_id);
		return $this->ci->db->get("roles")->row_array();
	}

	function create_role($role, $description=null,$weight=0)
	{
		if ($this->get_role_by_name($role)){
			throw new Exception("Role already exists");
		}

		$options=array(
			'name'=>$role,
			'description'=>$description, 
			'weight'=>$weight
		);

		return $this->ci->db->insert("roles",$options);
	}

	function update_role($role_id, $role, $description=null,$weight=0)
	{
		$role_info=$this->get_role_by_name($role);

		if(!empty($role_info) && $role_info['id']!=$role_id){
			throw new Exception("Role already exists");
		}

		$options=array(
			'name'=>$role,
			'description'=>$description,
			'weight'=>$weight
		);

		$this->ci->db->where('id',$role_id);
		return $this->ci->db->update("roles",$options);
	}


	function delete_role($role_id)
	{
		$this->ci->db->where('id',$role_id);
		return $this->ci->db->delete("roles");
	}


	function remove_role_permissions($role_id)
	{
		$this->ci->db->where('role_id',$role_id);
		return $this->ci->db->delete("role_permissions");
	}

	function set_role_permissions($role_id,$resource, $permissions=array())
	{
		$options=array(
			'role_id'=>$role_id,
			'resource'=>$resource,
			'permissions'=>implode(",",$permissions)
		);

		return $this->ci->db->insert("role_permissions",$options);
	}

	function get_role_permissions($role_id)
	{
		$this->ci->db->where('role_id',$role_id);
		$result=$this->ci->db->get("role_permissions")->result_array();

		foreach($result as $idx=>$row){
			$result[$idx]['permissions']=explode(",",$row['permissions']);
		}

		return $result;
	}

	function get_roles_permissions($roles)
	{
		if (empty($roles)){
			return array();
		}

		$this->ci->db->where_in('role_id',$roles);
		$result=$this->ci->db->get("role_permissions")->result_array();

		foreach($result as $idx=>$row){
			$result[$idx]['permissions']=explode(",",$row['permissions']);
		}

		return $result;
	}

	/**
	 * 
	 * Return roles by user
	 * 
	 */
	function get_user_roles($user_id)
	{
		$this->ci->db->select("user_roles.user_id, user_roles.role_id, roles.name, roles.is_admin");
		$this->ci->db->where("user_id",$user_id);
		$this->ci->db->join('roles', 'roles.id = user_roles.role_id');		
		$result= $this->ci->db->get("user_roles")->result_array();

		$user_roles=array();
		foreach($result as $row){
			$user_roles[$row['role_id']]=$row;
		}

		return $user_roles;
	}


	/**
	 * 
	 * assign a role to a user
	 * 
	 */
	function set_user_role($user_id, $role_id)
	{
		$options=array(
			'role_id'=>$role_id,
			'user_id'=>$user_id
		);

		if (!$this->check_user_role_exists($user_id, $role_id)){
			return $this->ci->db->insert("user_roles",$options);
		}
	}


	function check_user_role_exists($user_id, $role_id)
	{
		$this->ci->db->select("*");
		$this->ci->db->where("user_id",$user_id);
		$this->ci->db->where("role_id",$role_id);		
		$result= $this->ci->db->get("user_roles")->result_array();

		if (count($result)>0){
			return true;
		}
		return false;
	}
	


	/**
	 * 
	 * delete all user roles
	 * 
	 */
	function remove_user_roles($user_id)
	{
		$this->ci->db->where("user_id",$user_id);		
		return $this->ci->db->delete("user_roles");
	}


	/**
	*
	* Returns the currently logged in user object
	**/
	function current_user()
	{
		return $this->ci->ion_auth->current_user();
	}

	function user_is_admin($user=null)
	{
		if(empty($user)){
			$user=$this->current_user();
		}

		if(!$user){
			throw new Exception("acl_manager::User not set");
		}

		//get user roles
		$user_roles=$this->get_user_roles($user->id);

		//user has admin access
		if($this->is_admin_role($user_roles)==true){
			return true;
		}

		return false;
	}

	private function is_admin_role($roles)
	{
		foreach($roles as $role){
			if ($role['is_admin']==1){
				return true;
			}
		}
		return false;
	}


	/**
	 * Whether the user may enter the site admin area (/admin).
	 *
	 * Coarse gate only — feature access is enforced per controller via has_access().
	 * Deny by default: plain site accounts (no roles, or only the system "user" role
	 * with no collection grants) are blocked. Does not inspect role_permissions content.
	 *
	 * Allow when:
	 * - full admin (is_admin role), or
	 * - at least one global role other than "user", or
	 * - any repositories_acl grant (collection-only admins may keep the user role)
	 *
	 * @param object|null $user
	 * @return bool
	 */
	function user_has_any_admin_capability($user = null)
	{
		if (empty($user)) {
			$user = $this->current_user();
		}

		if ( ! $user || empty($user->id)) {
			return false;
		}

		if ($this->user_is_admin($user)) {
			return true;
		}

		// Collection-only admins may have no global roles (or only "user"); check grants before role list.
		if ($this->user_has_any_collection_grant((int) $user->id)) {
			return true;
		}

		$user_roles = $this->get_user_roles((int) $user->id);
		if (empty($user_roles)) {
			return false;
		}

		if ($this->user_has_non_site_user_role($user_roles)) {
			return true;
		}

		return false;
	}

	/**
	 * True if the user has a global role other than the system site-user role (name "user").
	 *
	 * @param array $user_roles from get_user_roles()
	 * @return bool
	 */
	protected function user_has_non_site_user_role(array $user_roles)
	{
		$site_user_role = $this->site_user_role_name();

		foreach ($user_roles as $role) {
			$name = isset($role['name']) ? strtolower(trim((string) $role['name'])) : '';
			if ($name !== '' && $name !== $site_user_role) {
				return true;
			}
		}

		return false;
	}

	/**
	 * System role name for plain site users (acl_permissions acl_system_roles).
	 *
	 * @return string lowercase
	 */
	protected function site_user_role_name()
	{
		return 'user';
	}

	/**
	 * @param int $user_id
	 * @return bool
	 */
	protected function user_has_any_collection_grant($user_id)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return false;
		}

		$this->ci->db->select('id');
		$this->ci->db->where('user_id', (int) $user_id);
		$this->ci->db->limit(1);
		$row = $this->ci->db->get('repositories_acl')->row_array();

		return ! empty($row);
	}

	function has_site_admin_access($user=null)
	{
		if(empty($user)){
			$user=$this->current_user();
		}

		if(!$user){
			die("acl_manager::User not set");
		}

		return $this->user_has_any_admin_capability($user);
	}

	function has_access_or_die($resource,$privilege, $user=null, $repositoryid=null)
	{
		try{
			$this->has_access($resource, $privilege,$user,$repositoryid);
		}
		catch(Exception $e){
			if ($e instanceof AclAccessDeniedException) {
				$this->show_access_denied('feature');
			}

			if ($this->ci->input->is_ajax_request()) {
				$this->ci->output
					->set_status_header(403)
        			->set_content_type('application/json');
				die (json_encode($e->getMessage()));
			}

			show_error($e->getMessage());
		}	
	}

	/**
	 * User-facing HTML access denied (403), or JSON for AJAX/API-style requests.
	 *
	 * @param string $context 'feature' = missing permission; 'shell' = cannot enter /admin (logging only)
	 */
	public function show_access_denied($context = 'feature')
	{
		$CI =& $this->ci;

		if (!isset($CI->ion_auth)) {
			$CI->load->library('ion_auth');
		}

		if ($CI->input->is_ajax_request()) {
			$CI->output
				->set_status_header(403)
				->set_content_type('application/json', 'utf-8');
			echo json_encode(array('status' => 'failed', 'message' => 'ACCESS-DENIED'));
			exit(1);
		}

		$CI->lang->load('general');

		$title = t('access_denied_title');
		$message = t('access_denied_message');

		$use_admin_shell = (
			$context !== 'shell'
			&& $CI->uri->segment(1) === 'admin'
			&& $CI->ion_auth->logged_in()
			&& $CI->ion_auth->can_access_site_admin()
		);

		log_message(
			'info',
			'Access denied ['.$context.']: uri='.$CI->uri->uri_string().' user='.$CI->ion_auth->current_user_identity()
		);

		$data = array(
			'title'   => $title,
			'message' => $message,
		);

		if ($use_admin_shell) {
			$page = array(
				'title'           => $title,
				'content'         => $CI->load->view('errors/access_denied_panel', $data, true),
				'hide_breadcrumb' => true,
				'theme_folder'    => 'adminvue',
			);
			$html = $CI->load->view('layouts/admin_vue', $page, true);
		} else {
			$html = $CI->load->view('errors/html/error_access_denied', $data, true);
		}

		$CI->output->set_status_header(403);
		echo $html;
		exit;
	}

	/**
	 * Role-based (Zend ACL) check only — no repositories_acl. Used internally to avoid recursion with collection ACL.
	 *
	 * @param string      $resource
	 * @param string      $privilege
	 * @param object      $user
	 * @param string|null $repositoryid normalized or null
	 * @return bool
	 */
	protected function _has_access_zend_roles($resource, $privilege, $user, $repositoryid)
	{
		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return true;
		}

		$permissions = $this->get_roles_permissions(array_keys($user_roles));

		$acl = new Acl();

		foreach ($user_roles as $role_id => $role) {
			$acl->addRole(new Role($role_id));
		}

		foreach ($permissions as $perm) {

			if ($acl->hasResource($perm['resource'])) {
				continue;
			}

			$acl->addResource(new Resource($perm['resource']));
			$acl->allow($perm['role_id'], $perm['resource'], $perm['permissions']);
		}

		if ( ! empty($repositoryid)) {
			foreach ($permissions as $perm) {

				if ($acl->hasResource($repositoryid . '-' . $perm['resource'])) {
					continue;
				}

				$acl->addResource(new Resource($repositoryid . '-' . $perm['resource']));
				$acl->allow($perm['role_id'], $repositoryid . '-' . $perm['resource'], $perm['permissions']);
			}
		}

		try {
			foreach ($user_roles as $role_id => $role) {
				if ( ! empty($repositoryid)) {
					if ($acl->isAllowed($role_id, $repositoryid . '-' . $resource, $privilege)) {
						return true;
					}
				}
				else {
					if ($acl->isAllowed($role_id, $resource, $privilege)) {
						return true;
					}
				}
			}
		}
		catch (Exception $e) {
			return false;
		}

		return false;
	}

	/**
	 * repositories_acl tier grants for study_* / licensed_request_* (no admin shortcut — admin handled in {@see has_access}).
	 *
	 * @param object $user
	 * @param string $repositoryid
	 * @param string $permission_key e.g. study_edit
	 * @return bool
	 */
	protected function _user_repository_acl_grants_satisfy_key($user, $repositoryid, $permission_key)
	{
		if (empty($user) || empty($user->id)) {
			return false;
		}

		$pk = $this->repositories_acl_resolve_repository_pk($repositoryid);
		if ($pk === null) {
			return false;
		}

		$grants = $this->repositories_acl_user_grants_for_repository((int) $user->id, $pk);

		return $this->repositories_acl_grant_set_satisfies($grants, $permission_key);
	}

	/**
	 * Map resource + privilege to repositories_acl.permission key for collection grants.
	 *
	 * @param string $resource study|licensed_request
	 * @param string $privilege view|edit|...
	 * @return string|null e.g. study_edit
	 */
	protected function _collection_acl_permission_key_for_resource_privilege($resource, $privilege)
	{
		$r = strtolower(trim((string) $resource));
		$p = strtolower(trim((string) $privilege));
		if ($p === '') {
			return null;
		}

		if ($r === 'study') {
			return 'study_' . $p;
		}

		if ($r === 'licensed_request') {
			return 'licensed_request_' . $p;
		}

		if ($r === 'collection') {
			if ($p === 'manage_access') {
				return 'collection_manage_access';
			}

			return 'collection_' . $p;
		}

		return null;
	}

	/**
	 * True if collection ACL (repositories_acl) grants satisfy resource/privilege for this repository.
	 *
	 * @param object $user
	 * @param string $repositoryid
	 * @param string $resource
	 * @param string $privilege
	 * @return bool
	 */
	protected function _collection_acl_satisfies_resource_privilege($user, $repositoryid, $resource, $privilege)
	{
		$key = $this->_collection_acl_permission_key_for_resource_privilege($resource, $privilege);
		if ($key === null) {
			return false;
		}

		return $this->_user_repository_acl_grants_satisfy_key($user, $repositoryid, $key);
	}

	/**
	 * True when the user may list or open the collections admin UI (global or any per-collection view).
	 *
	 * @param object|null $user
	 * @throws AclAccessDeniedException
	 */
	public function require_collection_admin_list_access($user = null)
	{
		if ($this->get_collection_admin_repository_scope($user) === false) {
			$this->has_access_or_die('collection', 'view', $user);
		}
	}

	/**
	 * Repositories (lowercase repositoryid) the user may access in collections admin.
	 *
	 * @param object|null $user
	 * @return array|null|false null = unrestricted; array = allowed slugs; false = none
	 */
	function get_collection_admin_repository_scope($user = null)
	{
		if (empty($user)) {
			$user = $this->current_user();
		}
		if (!$user) {
			return false;
		}

		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return null;
		}

		$permissions = $this->get_roles_permissions(array_keys($user_roles));
		$repos = array();
		$has_global_collection_view = false;

		foreach ($permissions as $perm) {
			$res = isset($perm['resource']) ? $perm['resource'] : '';
			$plist = isset($perm['permissions']) ? (array) $perm['permissions'] : array();
			if ($res === 'collection' && in_array('view', $plist, true)) {
				$has_global_collection_view = true;
			}
			if (preg_match('/^(.+)-collection$/', $res, $m) && in_array('view', $plist, true)) {
				$repos[strtolower($m[1])] = true;
			}
		}

		foreach ($this->repositories_acl_repository_slugs_for_user_permission_prefix((int) $user->id, 'collection_') as $slug_lc) {
			$repos[$slug_lc] = true;
		}

		if ($has_global_collection_view) {
			return null;
		}

		if (empty($repos)) {
			return false;
		}

		return array_keys($repos);
	}

	/**
	 * Whether the user holds global collection/manage_access or collection_manage_access on any repository.
	 *
	 * @param object|null $user
	 * @return bool
	 */
	function user_has_any_collection_manage_access($user = null)
	{
		if (empty($user)) {
			$user = $this->current_user();
		}
		if (!$user) {
			return false;
		}

		if ($this->user_has_access('collection', 'manage_access', $user, null)) {
			return true;
		}

		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return false;
		}

		$this->ci->db->select('permission');
		$this->ci->db->where('user_id', (int) $user->id);
		$this->ci->db->group_start();
		$this->ci->db->where('permission', 'collection_manage_access');
		$this->ci->db->or_where('permission', 'collection_admin');
		$this->ci->db->group_end();
		$this->ci->db->limit(1);
		$row = $this->ci->db->get('repositories_acl')->row_array();

		return ! empty($row);
	}

	/**
	 * Non-throwing access probe for controllers and APIs.
	 *
	 * @param string      $resource
	 * @param string      $privilege
	 * @param object|null $user
	 * @param string|null $repositoryid
	 * @return bool
	 */
	function user_has_access($resource, $privilege, $user = null, $repositoryid = null)
	{
		try {
			$this->has_access($resource, $privilege, $user, $repositoryid);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Role permissions (Zend ACL) plus, for study / licensed_request / collection with a repository slug, grants from repositories_acl.
	 *
	 * @param string      $resource
	 * @param string      $privilege
	 * @param object|null $user
	 * @param string|null $repositoryid catalog slug when checking collection-scoped study / licensed_request
	 */
	function has_access($resource,$privilege, $user=null, $repositoryid=null)
	{
		if(empty($user)){
			$user=$this->current_user();
		}

		if(!empty($repositoryid)){
			$repositoryid=strtolower(trim($repositoryid));
		}

		if(!$user){
			throw new Exception("acl_manager::User not set");
		}

		//get user roles
		$user_roles=$this->get_user_roles($user->id);

		//user has admin access
		if($this->is_admin_role($user_roles)==true){
			return true;
		}

		if ($this->_has_access_zend_roles($resource, $privilege, $user, $repositoryid)) {
			return true;
		}

		if ( ! empty($repositoryid) && $this->_collection_acl_satisfies_resource_privilege($user, $repositoryid, $resource, $privilege)) {
			return true;
		}

		$debug_info=[];
		if ($this->debug==true){
			$debug_info[]='Access denied for resource:: '.$resource;
			$debug_info[]='<pre style="padding:20px;">';						
			$debug_info[]=print_r($user_roles,true);
			$permissions=$this->get_roles_permissions(array_keys($user_roles));
			$debug_info[]=print_r($permissions, true);
			$debug_info[]='</pre>';
			
			throw new Exception(implode("\n", $debug_info));
		}else{
			throw new AclAccessDeniedException('Access denied for resource:: '.$resource);
		}
	}

	/**
	 * Unified access check. Resolves user from ID; resolves repository from study owner if needed.
	 * Throws AclAccessDeniedException on denial.
	 *
	 * @param string      $resource      e.g. 'study', 'licensed_request'
	 * @param string      $privilege     e.g. 'view', 'edit', 'delete', 'publish'
	 * @param int|null    $user_id       defaults to current user
	 * @param int|null    $study_id      if set and repository_id null, owner repo resolved via Catalog_model::get_study_owner()
	 * @param string|null $repository_id catalog slug; takes precedence over study_id resolution
	 * @throws AclAccessDeniedException
	 */
	public function check_access($resource, $privilege, $user_id = null, $study_id = null, $repository_id = null)
	{
		if ($user_id !== null) {
			$user = $this->resolve_user_for_access_check($user_id);
		} else {
			$user = $this->current_user();
		}

		if (empty($repository_id) && !empty($study_id)) {
			$this->ci->load->model('Catalog_model');
			$repository_id = $this->ci->Catalog_model->get_study_owner((int) $study_id) ?: null;
		}

		$this->has_access($resource, $privilege, $user, $repository_id ?: null);
	}

	/**
	 * Study ACL with repository resolution aligned to legacy API checks (MY_REST_Controller::has_dataset_access).
	 * Defaults repository to central when study and repository are both omitted.
	 *
	 * @param string      $privilege
	 * @param int|null    $user_id
	 * @param int|null    $study_id
	 * @param string|null $repository_id
	 * @throws AclAccessDeniedException
	 */
	public function check_study_access($privilege, $user_id = null, $study_id = null, $repository_id = null)
	{
		if ($user_id !== null) {
			$user = $this->resolve_user_for_access_check($user_id);
		} else {
			$user = $this->current_user();
		}

		$repository_id = $this->resolve_study_repository_slug($study_id, $repository_id);

		return $this->has_access('study', $privilege, $user, $repository_id);
	}

	/**
	 * Require study view on each study id (throws on first denial).
	 *
	 * @param int[]    $study_ids
	 * @param int|null $user_id
	 * @throws AclAccessDeniedException
	 * @throws Exception when no valid ids provided
	 */
	public function assert_study_view_on_study_ids(array $study_ids, $user_id = null)
	{
		$checked = 0;
		foreach ($study_ids as $study_id) {
			if ($study_id === null || $study_id === '') {
				continue;
			}
			$checked++;
			$this->check_study_access('view', $user_id, (int) $study_id, null);
		}
		if ($checked === 0) {
			throw new Exception('study_ids contains no valid study identifiers');
		}
	}

	/**
	 * Analytics API ACL: reports/view globally, or study/view on a specific study when scoped.
	 *
	 * @param int|null    $user_id
	 * @param int|null    $study_id           numeric surveys.id; required for non-report admins when $allow_study_scope
	 * @param bool        $allow_study_scope  false for site-wide reads/writes (reports/view only)
	 * @throws AclAccessDeniedException
	 */
	public function check_analytics_access($user_id = null, $study_id = null, $allow_study_scope = true)
	{
		if ($user_id !== null) {
			$user = $this->resolve_user_for_access_check($user_id);
		} else {
			$user = $this->current_user();
		}

		if ($this->user_has_access('reports', 'view', $user)) {
			return;
		}

		if ( ! $allow_study_scope || $study_id === null || $study_id === '') {
			throw new AclAccessDeniedException('Access denied for resource:: analytics');
		}

		$this->check_study_access('view', $user_id, (int) $study_id, null);
	}

	/**
	 * Resolve catalog slug for a study check (owner repo, surveys.repositoryid fallback, else central).
	 *
	 * @param int|null    $study_id
	 * @param string|null $repository_id explicit slug when provided
	 * @return string lowercase repository slug
	 */
	protected function resolve_study_repository_slug($study_id = null, $repository_id = null)
	{
		if ($repository_id !== null && $repository_id !== '') {
			return strtolower(trim((string) $repository_id));
		}

		if ( ! empty($study_id)) {
			$this->ci->load->model('Catalog_model');
			$repo = $this->ci->Catalog_model->get_study_owner((int) $study_id);
			if ($repo) {
				return strtolower(trim($repo));
			}

			$this->ci->db->select('repositoryid');
			$this->ci->db->where('id', (int) $study_id);
			$row = $this->ci->db->get('surveys')->row_array();
			if ( ! empty($row['repositoryid'])) {
				return strtolower(trim($row['repositoryid']));
			}
		}

		return 'central';
	}

	/**
	 * @param int $user_id
	 * @return object
	 * @throws AclAccessDeniedException
	 */
	protected function resolve_user_for_access_check($user_id)
	{
		$user = $this->ci->ion_auth->get_user((int) $user_id);
		if (empty($user)) {
			throw new AclAccessDeniedException('User not found');
		}

		return $user;
	}

	/**
	 * Throw if user has no study access on any repository (global or collection-scoped).
	 * @param object|null $user
	 * @throws AclAccessDeniedException
	 */
	public function require_catalog_access($user = null)
	{
		if ($this->get_admin_catalog_repository_scope($user) === false) {
			$this->has_access_or_die('study', 'view', $user);
		}
	}

	/**
	 * Throw if user has no licensed_request access on any repository (global or collection-scoped).
	 * @param object|null $user
	 * @throws AclAccessDeniedException
	 */
	public function require_licensed_requests_access($user = null)
	{
		if ($this->get_licensed_request_repository_scope($user) === false) {
			$this->has_access_or_die('licensed_request', 'view', $user);
		}
	}

	/**
	 * Whether the user has resource/privilege on at least one catalog repository via Zend per-repo
	 * resources or repositories_acl collection grants.
	 *
	 * Does not apply global (non-repo) Zend grants for $resource; use {@see has_access} without
	 * repositoryid first when those should count.
	 *
	 * @param string      $resource e.g. licensed_request, study
	 * @param string      $privilege e.g. view, edit
	 * @param object|null $user
	 * @param string[]    $repository_ids catalog slugs (duplicates ignored)
	 * @return bool
	 */
	function has_access_on_any_repository($resource, $privilege, $user = null, array $repository_ids = array())
	{
		if (empty($user)) {
			$user = $this->current_user();
		}

		if ( ! $user || empty($user->id)) {
			return false;
		}

		$repos = array();
		foreach ($repository_ids as $rid) {
			$r = strtolower(trim((string) $rid));
			if ($r !== '') {
				$repos[$r] = true;
			}
		}
		$repos = array_keys($repos);
		if (empty($repos)) {
			return false;
		}

		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return true;
		}

		if ($this->_has_access_zend_roles_any_repository($resource, $privilege, $user, $repos)) {
			return true;
		}

		return $this->_collection_acl_satisfies_resource_privilege_any_repository($user, $repos, $resource, $privilege);
	}

	/**
	 * Zend ACL: allow if any repository prefix grants the privilege (single ACL build).
	 *
	 * @param string   $resource
	 * @param string   $privilege
	 * @param object   $user
	 * @param string[] $repository_ids_lc normalized non-empty slugs
	 * @return bool
	 */
	protected function _has_access_zend_roles_any_repository($resource, $privilege, $user, array $repository_ids_lc)
	{
		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return true;
		}

		$permissions = $this->get_roles_permissions(array_keys($user_roles));

		$acl = new Acl();

		foreach ($user_roles as $role_id => $role) {
			$acl->addRole(new Role($role_id));
		}

		foreach ($permissions as $perm) {

			if ($acl->hasResource($perm['resource'])) {
				continue;
			}

			$acl->addResource(new Resource($perm['resource']));
			$acl->allow($perm['role_id'], $perm['resource'], $perm['permissions']);
		}

		foreach ($repository_ids_lc as $repositoryid) {
			foreach ($permissions as $perm) {

				if ($acl->hasResource($repositoryid . '-' . $perm['resource'])) {
					continue;
				}

				$acl->addResource(new Resource($repositoryid . '-' . $perm['resource']));
				$acl->allow($perm['role_id'], $repositoryid . '-' . $perm['resource'], $perm['permissions']);
			}
		}

		try {
			foreach ($repository_ids_lc as $repositoryid) {
				foreach ($user_roles as $role_id => $role) {
					if ($acl->isAllowed($role_id, $repositoryid . '-' . $resource, $privilege)) {
						return true;
					}
				}
			}
		}
		catch (Exception $e) {
			return false;
		}

		return false;
	}

	/**
	 * Collection ACL: true if repositories_acl tier grants satisfy on any of the repositories.
	 *
	 * @param object     $user
	 * @param string[]   $repository_ids_lc normalized slugs
	 * @param string     $resource
	 * @param string     $privilege
	 * @return bool
	 */
	protected function _collection_acl_satisfies_resource_privilege_any_repository($user, array $repository_ids_lc, $resource, $privilege)
	{
		$key = $this->_collection_acl_permission_key_for_resource_privilege($resource, $privilege);
		if ($key === null) {
			return false;
		}

		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return false;
		}

		$pk_list = array();
		foreach ($repository_ids_lc as $slug) {
			$pk = $this->repositories_acl_resolve_repository_pk($slug);
			if ($pk !== null) {
				$pk_list[(int) $pk] = true;
			}
		}
		$pks = array_keys($pk_list);
		if (empty($pks)) {
			return false;
		}

		$this->ci->db->select('repository_id, permission');
		$this->ci->db->where('user_id', (int) $user->id);
		$this->ci->db->where_in('repository_id', $pks);
		$rows = $this->ci->db->get('repositories_acl')->result_array();

		$by_repo = array();
		foreach ($rows as $row) {
			$rid = isset($row['repository_id']) ? (int) $row['repository_id'] : 0;
			if ($rid < 1) {
				continue;
			}
			if ( ! isset($by_repo[$rid])) {
				$by_repo[$rid] = array();
			}
			if (isset($row['permission'])) {
				$by_repo[$rid][] = $row['permission'];
			}
		}

		foreach ($pks as $pk) {
			$grants = isset($by_repo[$pk]) ? $by_repo[$pk] : array();
			if ($this->repositories_acl_grant_set_satisfies($grants, $key)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Repositories (lowercase repositoryid) the user may list in admin catalog search.
	 * A study matches if surveys.repositoryid is in the set OR the study is linked in survey_repos
	 * to one of these repositories.
	 *
	 * @param object|null $user Current user (defaults to current_user())
	 * @return array|null|false null = unrestricted (admin role or global study/view); array = allowed repo ids; false = no collection-scoped or global study view
	 */
	function get_admin_catalog_repository_scope($user = null)
	{
		if (empty($user)) {
			$user = $this->current_user();
		}
		if (!$user) {
			return false;
		}

		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return null;
		}

		$permissions = $this->get_roles_permissions(array_keys($user_roles));
		$repos = array();
		$has_global_study_view = false;

		foreach ($permissions as $perm) {
			$res = isset($perm['resource']) ? $perm['resource'] : '';
			$plist = isset($perm['permissions']) ? (array) $perm['permissions'] : array();
			if ($res === 'study' && in_array('view', $plist, true)) {
				$has_global_study_view = true;
			}
			if (preg_match('/^(.+)-study$/', $res, $m) && in_array('view', $plist, true)) {
				$repos[strtolower($m[1])] = true;
			}
		}

		foreach ($this->repositories_acl_repository_slugs_for_user_permission_prefix((int) $user->id, 'study_') as $slug_lc) {
			$repos[$slug_lc] = true;
		}

		if ($has_global_study_view) {
			return null;
		}

		if (empty($repos)) {
			return false;
		}

		return array_keys($repos);
	}

	/**
	 * Repositories (lowercase repositoryid) for which the user may view licensed requests.
	 * Uses resource "licensed_request" globally or "{repo}-licensed_request" per collection.
	 * Admin role / unrestricted returns null (no SQL filter). No permission returns false.
	 *
	 * @param object|null $user
	 * @return array|null|false null = unrestricted; array = allowed repo ids; false = denied
	 */
	function get_licensed_request_repository_scope($user = null)
	{
		if (empty($user)) {
			$user = $this->current_user();
		}
		if (!$user) {
			return false;
		}

		$user_roles = $this->get_user_roles($user->id);
		if ($this->is_admin_role($user_roles)) {
			return null;
		}

		$permissions = $this->get_roles_permissions(array_keys($user_roles));
		$repos       = array();
		$has_global  = false;

		foreach ($permissions as $perm) {
			$res   = isset($perm['resource']) ? $perm['resource'] : '';
			$plist = isset($perm['permissions']) ? (array) $perm['permissions'] : array();
			$can   = in_array('view', $plist, true) || in_array('edit', $plist, true);
			if (!$can) {
				continue;
			}
			if ($res === 'licensed_request') {
				$has_global = true;
			}
			if (preg_match('/^(.+)-licensed_request$/', $res, $m)) {
				$repos[strtolower($m[1])] = true;
			}
		}

		foreach ($this->repositories_acl_repository_slugs_for_user_permission_prefix((int) $user->id, 'licensed_request_') as $slug_lc) {
			$repos[$slug_lc] = true;
		}

		if ($has_global) {
			return null;
		}

		if (empty($repos)) {
			return false;
		}

		return array_keys($repos);
	}

	/**
	 * Storage key on repositories_acl for a catalog study privilege (matches migration: study_{privilege}).
	 *
	 * @param string $privilege e.g. view, create, edit
	 * @return string
	 */
	function study_repositories_acl_key($privilege)
	{
		return 'study_' . strtolower(trim((string) $privilege));
	}

	/**
	 * @param string $privilege e.g. view, edit
	 * @return string
	 */
	function licensed_request_repositories_acl_key($privilege)
	{
		return 'licensed_request_' . strtolower(trim((string) $privilege));
	}

	/**
	 * Distinct collection slugs (lowercase) where user has any permission starting with prefix, e.g. study_ or licensed_request_.
	 *
	 * @param int $user_id
	 * @param string $prefix e.g. study_
	 * @return string[]
	 */
	function repositories_acl_repository_slugs_for_user_permission_prefix($user_id, $prefix)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}
		$p = (string) $prefix;
		if ($p === '') {
			return array();
		}

		$this->ci->db->distinct();
		$this->ci->db->select('repositories.repositoryid');
		$this->ci->db->from('repositories_acl');
		$this->ci->db->join('repositories', 'repositories.id = repositories_acl.repository_id', 'inner');
		$this->ci->db->where('repositories_acl.user_id', (int) $user_id);
		$this->ci->db->like('repositories_acl.permission', $p, 'after');
		$rows = $this->ci->db->get()->result_array();

		$slugs = array();
		foreach ($rows as $row) {
			if ( ! empty($row['repositoryid'])) {
				$slugs[] = strtolower(trim($row['repositoryid']));
			}
		}

		return array_values(array_unique($slugs));
	}

	/**
	 * Enforce study_* / licensed_request_* storage keys via {@see has_access_or_die}.
	 *
	 * @param string $repositoryid
	 * @param string $permission_key e.g. study_edit, licensed_request_view
	 * @param object|null $user
	 * @return void
	 */
	function repository_permission_or_die($repositoryid, $permission_key, $user = null)
	{
		$key = trim((string) $permission_key);
		$rp  = $key === '' ? null : $this->_resource_privilege_from_collection_permission_key($key);
		if ($rp === null) {
			if ($this->ci->input->is_ajax_request()) {
				$this->ci->output
					->set_status_header(403)
					->set_content_type('application/json');
				die (json_encode('Invalid or empty collection permission key'));
			}

			show_error('Invalid or empty collection permission key');
		}

		$this->has_access_or_die($rp[0], $rp[1], $user, $repositoryid);
	}

	/**
	 * UI + validation: simplified study tier keys for repositories_acl.
	 *
	 * @return array[] each [ 'key','label','description' ]
	 */
	function get_manageable_study_repositories_acl_rows()
	{
		$ca = $this->ci->config->item('collections_acl');
		$rows = (is_array($ca) && isset($ca['study_tiers'])) ? $ca['study_tiers'] : array();

		return is_array($rows) ? $rows : array();
	}

	/**
	 * UI + validation: simplified licensed-request tier keys for repositories_acl.
	 *
	 * @return array[] each [ 'key','label','description' ]
	 */
	function get_manageable_licensed_request_repositories_acl_rows()
	{
		$ca = $this->ci->config->item('collections_acl');
		$rows = (is_array($ca) && isset($ca['licensed_request_tiers'])) ? $ca['licensed_request_tiers'] : array();

		return is_array($rows) ? $rows : array();
	}

	/**
	 * UI + validation: collection-admin tier keys for repositories_acl.
	 *
	 * @return array[] each [ 'key','label','description' ]
	 */
	function get_manageable_collection_repositories_acl_rows()
	{
		$ca = $this->ci->config->item('collections_acl');
		$rows = (is_array($ca) && isset($ca['collection_tiers'])) ? $ca['collection_tiers'] : array();

		return is_array($rows) ? $rows : array();
	}

	/**
	 * Allowed repositories_acl.permission values for managed user-collection UI / replace.
	 *
	 * @return array<string,int> map key=>1
	 */
	function get_manageable_repositories_acl_permission_whitelist_map()
	{
		$m = array();
		foreach ($this->get_manageable_study_repositories_acl_rows() as $row) {
			$m[$row['key']] = 1;
		}
		foreach ($this->get_manageable_licensed_request_repositories_acl_rows() as $row) {
			$m[$row['key']] = 1;
		}
		foreach ($this->get_manageable_collection_repositories_acl_rows() as $row) {
			$m[$row['key']] = 1;
		}

		return $m;
	}

	/**
	 * Replace study_* / licensed_request_* rows for user with grants from admin UI (other permission keys untouched).
	 *
	 * @param int $user_id
	 * @param array $grant_rows array of ['repository_id' => int,'permission' => string]
	 */
	function repositories_acl_replace_user_managed_collection_grants($user_id, array $grant_rows)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return;
		}

		$uid = (int) $user_id;
		if ($uid < 1) {
			return;
		}

		$white = $this->get_manageable_repositories_acl_permission_whitelist_map();

		$this->ci->db->where('user_id', $uid);
		$this->ci->db->group_start();
		$this->ci->db->like('permission', 'study_', 'after');
		$this->ci->db->or_like('permission', 'licensed_request_', 'after');
		$this->ci->db->or_like('permission', 'collection_', 'after');
		$this->ci->db->group_end();
		$this->ci->db->delete('repositories_acl');

		$dedupe = array();

		foreach ($grant_rows as $row) {
			if (empty($row['repository_id']) || empty($row['permission'])) {
				continue;
			}

			$pk  = (int) $row['repository_id'];
			$key = trim((string) $row['permission']);
			if ($pk < 1 || $key === '' || empty($white[$key])) {
				continue;
			}

			$uniq = $pk . "\0" . $key;
			if (isset($dedupe[$uniq])) {
				continue;
			}
			$dedupe[$uniq] = true;

			$this->ci->db->reset_query();
			$this->ci->db->insert(
				'repositories_acl',
				array(
					'user_id'       => $uid,
					'repository_id' => $pk,
					'permission'    => $key,
					'created_by'      => null,
				)
			);
		}
	}

	/**
	 * Replace only manageable study_* / licensed_request_* rows for one user on one repository.
	 *
	 * @param int   $user_id
	 * @param int   $repository_pk repositories.id (0 = central)
	 * @param array $permission_keys list of keys from whitelist
	 */
	function repositories_acl_replace_user_repository_managed_grants($user_id, $repository_pk, array $permission_keys)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return;
		}

		$uid = (int) $user_id;
		$pk  = (int) $repository_pk;
		if ($uid < 1) {
			return;
		}

		$white        = $this->get_manageable_repositories_acl_permission_whitelist_map();
		$managed_list = array_keys($white);
		if (empty($managed_list)) {
			return;
		}

		$this->ci->db->where('user_id', $uid);
		$this->ci->db->where('repository_id', $pk);
		$this->ci->db->where_in('permission', $managed_list);
		$this->ci->db->delete('repositories_acl');

		$dedupe = array();
		foreach ($permission_keys as $raw) {
			$key = trim((string) $raw);
			if ($key === '' || empty($white[$key])) {
				continue;
			}
			if (isset($dedupe[$key])) {
				continue;
			}
			$dedupe[$key] = true;

			$this->ci->db->reset_query();
			$this->ci->db->insert(
				'repositories_acl',
				array(
					'user_id'       => $uid,
					'repository_id' => $pk,
					'permission'    => $key,
					'created_by'    => null,
				)
			);
		}
	}

	/**
	 * Resolve repositories.id for a catalog slug (consistent with lowercase {slug}-study role resources).
	 *
	 * @param string $repositoryid
	 * @return int|null
	 */
	function repositories_acl_resolve_repository_pk($repositoryid)
	{
		$key = strtolower(trim((string) $repositoryid));
		if ($key === '') {
			return null;
		}
		if (array_key_exists($key, $this->repository_acl_repository_pk_cache)) {
			$cached = $this->repository_acl_repository_pk_cache[$key];
			return $cached === false ? null : $cached;
		}
		$row = $this->ci->Repository_model->select_single($key);
		if (empty($row) || empty($row['id'])) {
			$this->repository_acl_repository_pk_cache[$key] = false;
			return null;
		}
		$id = (int) $row['id'];
		$this->repository_acl_repository_pk_cache[$key] = $id;
		return $id;
	}

	/**
	 * Map repositories_acl-style key (study_* / licensed_request_*) to Zend resource + privilege for {@see has_access}.
	 *
	 * @param string $permission_key e.g. study_edit, licensed_request_view
	 * @return array|null [ resource, privilege ]
	 */
	protected function _resource_privilege_from_collection_permission_key($permission_key)
	{
		$key = trim((string) $permission_key);
		if ($key === '') {
			return null;
		}

		$p = 'study_';
		if (strpos($key, $p) === 0) {
			$privilege = substr($key, strlen($p));
			if ($privilege === '') {
				return null;
			}

			return array('study', $privilege);
		}

		$p = 'licensed_request_';
		if (strpos($key, $p) === 0) {
			$privilege = substr($key, strlen($p));
			if ($privilege === '') {
				return null;
			}

			return array('licensed_request', $privilege);
		}

		$p = 'collection_';
		if (strpos($key, $p) === 0) {
			$privilege = substr($key, strlen($p));
			if ($privilege === '') {
				return null;
			}
			if ($privilege === 'manage_access') {
				return array('collection', 'manage_access');
			}

			return array('collection', $privilege);
		}

		return null;
	}

	/**
	 * All repositories_acl.permission values for one user on one repository row.
	 *
	 * @param int $user_id
	 * @param int $repository_pk repositories.id (may be 0 for central when stored that way)
	 * @return string[]
	 */
	function repositories_acl_user_grants_for_repository($user_id, $repository_pk)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}

		$this->ci->db->select('permission');
		$this->ci->db->where('user_id', (int) $user_id);
		$this->ci->db->where('repository_id', (int) $repository_pk);
		$rows = $this->ci->db->get('repositories_acl')->result_array();

		return array_values(array_unique(array_column($rows, 'permission')));
	}

	/**
	 * Satisfier grant keys for a required permission key (from config).
	 *
	 * @param string $required_key
	 * @return string[]
	 */
	function repositories_acl_satisfiers_for_required($required_key)
	{
		$req = trim((string) $required_key);
		if ($req === '') {
			return array();
		}

		$ca  = $this->ci->config->item('collections_acl');
		$cfg = (is_array($ca) && isset($ca['satisfiers'])) ? $ca['satisfiers'] : array();
		if (is_array($cfg) && isset($cfg[$req]) && is_array($cfg[$req])) {
			return $cfg[$req];
		}

		return array($req);
	}

	/**
	 * Whether any user grant satisfies the required permission key (tier implication).
	 *
	 * @param string[] $grants permission strings from repositories_acl
	 * @param string   $required_key e.g. study_edit
	 * @return bool
	 */
	function repositories_acl_grant_set_satisfies(array $grants, $required_key)
	{
		$req = trim((string) $required_key);
		if ($req === '') {
			return false;
		}

		$flip = array();
		foreach ($grants as $g) {
			$g = trim((string) $g);
			if ($g !== '') {
				$flip[$g] = true;
			}
		}

		foreach ($this->repositories_acl_satisfiers_for_required($req) as $candidate) {
			if (isset($flip[$candidate])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $user_id
	 * @param int $repository_pk repositories.id
	 * @param string $permission repositories_acl.permission (single key; convention e.g. study_view)
	 * @return bool
	 */
	function repositories_acl_has_grant($user_id, $repository_pk, $permission)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return false;
		}
		$perm = trim((string) $permission);
		if ($perm === '') {
			return false;
		}
		$this->ci->db->where('user_id', (int) $user_id);
		$this->ci->db->where('repository_id', (int) $repository_pk);
		$this->ci->db->where('permission', $perm);
		$this->ci->db->limit(1);
		$r = $this->ci->db->get('repositories_acl')->row_array();
		return ! empty($r);
	}

	/**
	 * Distinct repositories.id values where user holds a given permission_key.
	 *
	 * @param int $user_id
	 * @param string $permission
	 * @return int[]
	 */
	function repositories_acl_repository_pks_for_permission($user_id, $permission)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}
		$perm = trim((string) $permission);
		if ($perm === '') {
			return array();
		}
		$this->ci->db->distinct();
		$this->ci->db->select('repository_id');
		$this->ci->db->where('user_id', (int) $user_id);
		$this->ci->db->where('permission', $perm);
		$rows = $this->ci->db->get('repositories_acl')->result_array();
		return array_unique(array_map('intval', array_column($rows, 'repository_id')));
	}

	/**
	 * Collections (repositories) each user has managed per-collection ACL grants on.
	 * One batched query for listing pages; keys are user_id.
	 *
	 * @param int[] $user_ids
	 * @return array<int, array<int, array{id:int, repositoryid:string, title:string}>>
	 */
	function repositories_acl_collections_for_users(array $user_ids)
	{
		if (empty($user_ids) || ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}

		$ids = array();
		foreach ($user_ids as $uid) {
			$uid = (int) $uid;
			if ($uid > 0) {
				$ids[$uid] = true;
			}
		}
		if (empty($ids)) {
			return array();
		}

		$managed_keys = array_keys($this->get_manageable_repositories_acl_permission_whitelist_map());
		if (empty($managed_keys)) {
			return array();
		}

		$this->ci->db->select('ra.user_id, r.id, r.repositoryid, r.title');
		$this->ci->db->from('repositories_acl ra');
		$this->ci->db->join('repositories r', 'r.id = ra.repository_id', 'inner');
		$this->ci->db->where_in('ra.user_id', array_keys($ids));
		$this->ci->db->where_in('ra.permission', $managed_keys);
		$this->ci->db->order_by('r.title', 'ASC');
		$rows = $this->ci->db->get()->result_array();

		$output = array();
		$seen = array();

		foreach ($rows as $row) {
			$user_id = isset($row['user_id']) ? (int) $row['user_id'] : 0;
			$repo_id = isset($row['id']) ? (int) $row['id'] : 0;
			if ($user_id < 1 || $repo_id < 1) {
				continue;
			}

			$uniq = $user_id . ':' . $repo_id;
			if (isset($seen[$uniq])) {
				continue;
			}
			$seen[$uniq] = true;

			if ( ! isset($output[$user_id])) {
				$output[$user_id] = array();
			}

			$output[$user_id][] = array(
				'id'           => $repo_id,
				'repositoryid' => isset($row['repositoryid']) ? (string) $row['repositoryid'] : '',
				'title'        => isset($row['title']) ? (string) $row['title'] : '',
			);
		}

		return $output;
	}

	/**
	 * Distinct users with managed per-collection ACL grants, keyed by repositories.id.
	 *
	 * @return array<int,int> repository_id => user count
	 */
	function repositories_acl_managed_user_counts_by_repository()
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}

		$managed_keys = array_keys($this->get_manageable_repositories_acl_permission_whitelist_map());
		if (empty($managed_keys)) {
			return array();
		}

		$this->ci->db->select('repository_id, COUNT(DISTINCT user_id) AS user_count', false);
		$this->ci->db->from('repositories_acl');
		$this->ci->db->where_in('permission', $managed_keys);
		$this->ci->db->group_by('repository_id');
		$rows = $this->ci->db->get()->result_array();

		$out = array();
		foreach ($rows as $row) {
			$pk = isset($row['repository_id']) ? (int) $row['repository_id'] : 0;
			if ($pk < 1) {
				continue;
			}
			$out[$pk] = (int) $row['user_count'];
		}

		return $out;
	}

	/**
	 * Distinct users with managed per-collection ACL grants, keyed by repositories.id.
	 * Includes total count and a username preview (for admin list UI).
	 *
	 * @param int $preview_limit max usernames per repository
	 * @return array<int, array{count:int, users:array<int, array{user_id:int, username:string}>}>
	 */
	function repositories_acl_managed_users_preview_by_repository($preview_limit = 3)
	{
		if ( ! $this->ci->db->table_exists('repositories_acl')) {
			return array();
		}

		$preview_limit = max(1, (int) $preview_limit);
		$managed_keys = array_keys($this->get_manageable_repositories_acl_permission_whitelist_map());
		if (empty($managed_keys)) {
			return array();
		}

		$this->ci->load->config('ion_auth');
		$tables = $this->ci->config->item('tables');
		$meta_table = (is_array($tables) && ! empty($tables['meta'])) ? $tables['meta'] : 'meta';

		$this->ci->db->select('ra.repository_id, u.id AS user_id, m.first_name, m.last_name');
		$this->ci->db->from('repositories_acl ra');
		$this->ci->db->join('users u', 'u.id = ra.user_id', 'inner');
		$this->ci->db->join($meta_table . ' m', 'm.user_id = u.id', 'left');
		$this->ci->db->where_in('ra.permission', $managed_keys);
		$this->ci->db->group_by('ra.repository_id, u.id, m.first_name, m.last_name');
		$this->ci->db->order_by('ra.repository_id', 'ASC');
		$this->ci->db->order_by('m.first_name', 'ASC');
		$this->ci->db->order_by('m.last_name', 'ASC');
		$rows = $this->ci->db->get()->result_array();

		$out = array();
		foreach ($rows as $row) {
			$pk = isset($row['repository_id']) ? (int) $row['repository_id'] : 0;
			$uid = isset($row['user_id']) ? (int) $row['user_id'] : 0;
			if ($pk < 1 || $uid < 1) {
				continue;
			}

			if ( ! isset($out[$pk])) {
				$out[$pk] = array(
					'count' => 0,
					'users' => array(),
				);
			}

			$out[$pk]['count']++;
			$display_name = $this->_format_user_display_name(
				isset($row['first_name']) ? $row['first_name'] : '',
				isset($row['last_name']) ? $row['last_name'] : ''
			);
			if ($display_name !== '' && count($out[$pk]['users']) < $preview_limit) {
				$out[$pk]['users'][] = array(
					'user_id'      => $uid,
					'first_name'   => isset($row['first_name']) ? (string) $row['first_name'] : '',
					'last_name'    => isset($row['last_name']) ? (string) $row['last_name'] : '',
					'display_name' => $display_name,
				);
			}
		}

		return $out;
	}

	/**
	 * @param string $first_name
	 * @param string $last_name
	 * @return string
	 */
	private function _format_user_display_name($first_name, $last_name)
	{
		return trim(trim((string) $first_name) . ' ' . trim((string) $last_name));
	}

}
