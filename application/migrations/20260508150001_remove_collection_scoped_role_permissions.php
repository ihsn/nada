<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Drop role_permissions rows for per-collection resources ({slug}-study, {slug}-licensed_request).
 * Collection-scoped access is repositories_acl only; roles retain global study / licensed_request.
 */
class Migration_Remove_collection_scoped_role_permissions extends MY_Migration {

	public function up()
	{
		log_message('info', __CLASS__ . '::up()');

		if ( ! $this->db->table_exists('role_permissions')) {
			echo "⚠ role_permissions missing — skipping.\n";

			return;
		}

		$db_driver = $this->db->dbdriver;

		if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
			$this->db->query(
				"DELETE FROM role_permissions WHERE resource LIKE '%-study' OR resource LIKE '%-licensed_request'"
			);
		}
		elseif ($db_driver === 'sqlsrv') {
			$this->db->query(
				"DELETE FROM role_permissions WHERE resource LIKE '%-study' OR resource LIKE '%-licensed_request'"
			);
		}
		else {
			throw new Exception('Unsupported database driver: ' . $db_driver);
		}

		$n = (int) $this->db->affected_rows();
		$msg = sprintf("Removed collection-scoped role_permissions rows (driver reported affected_rows=%d).\n", $n);
		echo $msg;
		log_message('info', __CLASS__ . ' ' . trim($msg));
	}

	public function down()
	{
		log_message('info', __CLASS__ . '::down() no-op — restore from backup if needed');
		echo "⚠ Remove_collection_scoped_role_permissions::down() is a no-op.\n";
	}
}
