<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Copy collection-scoped ACL from role_permissions into repositories_acl:
 * rows where resource matches {repositoryid}-study or {repositoryid}-licensed_request.
 *
 * Legacy global resources (bare "study", "licensed_request") stay on role_permissions.
 * Inserts skip users who already have any is_admin role.
 * Idempotent: safe to re-run (unique index prevents duplicates).
 *
 * repositories_acl.permission values: study_{privilege}, licensed_request_{privilege}.
 */
class Migration_Migrate_legacy_role_permissions_to_repositories_acl extends MY_Migration {

	public function up()
	{
		log_message('info', __CLASS__ . '::up()');

		if ( ! $this->db->table_exists('repositories_acl')) {
			echo "⚠ repositories_acl missing — create it first (20260508120001_create_repositories_acl_table).\n";
			log_message('error', __CLASS__ . ': repositories_acl table missing');
			return;
		}

		if ( ! $this->db->table_exists('role_permissions') || ! $this->db->table_exists('user_roles') || ! $this->db->table_exists('repositories')) {
			echo "⚠ Required tables missing; aborting legacy ACL migration.\n";
			log_message('error', __CLASS__ . ': prerequisite tables missing');
			return;
		}

		$slug_to_repo_id = $this->_repository_slug_map();
		$admin_users     = $this->_user_ids_with_admin_role();

		$role_permissions_all   = $this->db->get('role_permissions')->result_array();
		$inserted               = 0;
		$skipped_no_repo        = 0;
		$skipped_no_users_roles = 0;
		$matched_collection_rp  = 0;

		foreach ($role_permissions_all as $rp) {
			$resource = isset($rp['resource']) ? trim((string) $rp['resource']) : '';
			if ($resource === '' || ! preg_match('/^(.+)-(study|licensed_request)$/i', $resource, $matches)) {
				continue;
			}
			$matched_collection_rp++;

			$slug_lower = strtolower($matches[1]);
			if ( ! isset($slug_to_repo_id[$slug_lower])) {
				log_message(
					'debug',
					__CLASS__ . ': no repository matching slug "' . $slug_lower . '" for resource ' . $resource
				);
				$skipped_no_repo++;
				continue;
			}
			$repository_pk = (int) $slug_to_repo_id[$slug_lower];
			$suffix        = strtolower($matches[2]);

			$cvs = isset($rp['permissions']) ? (string) $rp['permissions'] : '';
			if ($cvs === '' || trim($cvs) === '') {
				continue;
			}
			$privileges = array_filter(array_map('trim', explode(',', $cvs)));
			if (empty($privileges)) {
				continue;
			}

			$role_id_val = isset($rp['role_id']) ? trim((string) $rp['role_id']) : '';
			if ($role_id_val === '' || ! ctype_digit($role_id_val)) {
				continue;
			}
			$role_id_int = (int) $role_id_val;

			$this->db->reset_query();
			$this->db->select('user_id');
			$this->db->where('role_id', $role_id_int);
			$ur_rows = $this->db->get('user_roles')->result_array();
			if (empty($ur_rows)) {
				$skipped_no_users_roles++;
				continue;
			}

			foreach ($ur_rows as $ur_row) {
				$user_id = (int) $ur_row['user_id'];
				if ($user_id < 1) {
					continue;
				}
				if (isset($admin_users[$user_id])) {
					continue;
				}

				foreach ($privileges as $priv) {
					$priv = strtolower(trim((string) $priv));
					$priv = str_replace(array(' ', '-'), '_', $priv);
					$priv = preg_replace('/[^a-z0-9_]/', '', preg_replace('/_+/', '_', $priv));
					$priv = trim($priv, '_');
					if ($priv === '') {
						continue;
					}
					$permission_key = $suffix . '_' . $priv;
					$new_rows       = $this->_insert_grant_if_missing($user_id, $repository_pk, $permission_key);
					$inserted      += $new_rows;
				}
			}
		}

		$msg = sprintf(
			"Migrated legacy collection role_permissions: collection-scoped RP rows=%d, new repositories_acl inserts=%d, unknown-repo-slug=%d, role-with-no-users=%d\n",
			$matched_collection_rp,
			$inserted,
			$skipped_no_repo,
			$skipped_no_users_roles
		);
		echo $msg;
		log_message('info', __CLASS__ . ' ' . trim($msg));
	}

	public function down()
	{
		// Rows are not tagged by source; do not truncate repositories_acl automatically.
		log_message('info', __CLASS__ . '::down() no-op — remove repositories_acl grants manually if required');
		echo "⚠ Migrate_legacy_role_permissions_to_repositories_acl::down() is a no-op (see migration comment).\n";
	}

	/**
	 * @return array<string,int> lowercased repositoryid => repositories.id
	 */
	protected function _repository_slug_map()
	{
		$map = [];
		foreach ($this->db->select('id, repositoryid')->get('repositories')->result_array() as $row) {
			$rpid = strtolower(trim(isset($row['repositoryid']) ? (string) $row['repositoryid'] : ''));
			if ($rpid === '') {
				continue;
			}
			$map[$rpid] = (int) $row['id'];
		}
		return $map;
	}

	/**
	 * @return array<int,bool>
	 */
	protected function _user_ids_with_admin_role()
	{
		$this->db->reset_query();
		$this->db->select('ur.user_id');
		$this->db->distinct();
		$this->db->from('user_roles ur');
		$this->db->join('roles r', 'r.id = ur.role_id', 'inner');
		$this->db->where('r.is_admin', 1);

		$out = [];
		foreach ($this->db->get()->result_array() as $row) {
			$uid       = isset($row['user_id']) ? (int) $row['user_id'] : 0;
			if ($uid > 0) {
				$out[$uid] = true;
			}
		}
		return $out;
	}

	/**
	 * @param int $user_id
	 * @param int $repository_pk repositories.id
	 * @param string $permission repositories_acl.permission
	 * @return int Rows inserted (0 when already present)
	 */
	protected function _insert_grant_if_missing($user_id, $repository_pk, $permission)
	{
		$db_driver = $this->db->dbdriver;
		if ($db_driver !== 'mysql' && $db_driver !== 'mysqli' && $db_driver !== 'sqlsrv') {
			throw new Exception('Unsupported database driver: ' . $db_driver);
		}

		$exists_q = $this->db->get_where(
			'repositories_acl',
			array(
				'user_id'       => (int) $user_id,
				'repository_id' => (int) $repository_pk,
				'permission'    => $permission,
			),
			1
		);
		if ($exists_q->num_rows() > 0) {
			return 0;
		}

		$row = array(
			'user_id'         => (int) $user_id,
			'repository_id'   => (int) $repository_pk,
			'permission'      => $permission,
			'created_by'      => null,
		);

		$this->db->reset_query();
		if ( ! $this->db->insert('repositories_acl', $row)) {
			return 0;
		}
		return (int) $this->db->affected_rows() > 0 ? 1 : 0;
	}
}

