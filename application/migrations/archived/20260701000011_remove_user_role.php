<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Remove the legacy built-in 'user' role (id 2).
 *
 *
 * Only acts while role 2 is still named 'user'; a renamed or reused id 2 is left alone.
 * Safe to re-run.
 */
class Migration_Remove_user_role extends MY_Migration {

	public function up()
	{
		log_message('info', __CLASS__ . '::up()');

		if ( ! $this->db->table_exists('roles')) {
			echo "⚠ roles missing — skipping.\n";

			return;
		}

		$role = $this->db->select('id')
			->where('id', 2)
			->where('name', 'user')
			->get('roles')
			->row_array();

		if (empty($role)) {
			echo "Role 'user' (id 2) not found — nothing to do.\n";

			return;
		}

		$this->db->trans_start();

		$this->db->where('role_id', 2)->delete('user_roles');
		$members = (int) $this->db->affected_rows();

		$this->db->where('role_id', '2')->delete('role_permissions');
		$permissions = (int) $this->db->affected_rows();

		$this->db->where('id', 2)->where('name', 'user')->delete('roles');

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			throw new Exception("Failed to remove the 'user' role.");
		}

		$msg = sprintf("Removed role 'user' (id 2): %d user_roles rows, %d role_permissions rows.\n", $members, $permissions);
		echo $msg;
		log_message('info', __CLASS__ . ' ' . trim($msg));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
