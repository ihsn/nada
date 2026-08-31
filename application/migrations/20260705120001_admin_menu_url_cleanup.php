<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Legacy admin menu URL cleanup (site_menu table).
 */
class Migration_Admin_menu_url_cleanup extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260705120001_migrate_site_menu_repositories_urls.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
