<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Raise the default minimum password length from 5 to 8.
 * A site that already chose another length is left unchanged.
 */
class Migration_Password_min_length extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'min_password_length');
		$this->db->where('value', '5');
		$this->db->update('configurations', array('value' => '8'));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
