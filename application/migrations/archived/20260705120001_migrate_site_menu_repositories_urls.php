<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Point legacy admin menu rows at /admin/collections (Repositories controller removed).
 *
 * Admin navigation at runtime uses application/config/site_menus.php; this updates the
 * orphaned site_menu table on databases upgraded from older NADA releases.
 */
class Migration_Migrate_site_menu_repositories_urls extends MY_Migration {

	public function up()
	{
		if ( ! $this->db->table_exists('site_menu')) {
			echo "⚠ site_menu table missing — skipping legacy menu URL migration\n";
			return;
		}

		$this->db->where('url', 'admin/repositories');
		$this->db->update('site_menu', array('url' => 'admin/collections'));
		$exact = (int) $this->db->affected_rows();

		$this->db->where('url LIKE', 'admin/repositories/%', false);
		$rows = $this->db->get('site_menu')->result_array();
		$prefix = 0;
		foreach ($rows as $row) {
			$new_url = 'admin/collections/' . substr($row['url'], strlen('admin/repositories/'));
			$this->db->where('id', (int) $row['id']);
			$this->db->update('site_menu', array('url' => $new_url));
			$prefix++;
		}

		echo sprintf(
			"✓ site_menu repositories URLs migrated (exact=%d, prefixed=%d)\n",
			$exact,
			$prefix
		);
		log_message(
			'info',
			__CLASS__ . ': migrated site_menu admin/repositories URLs (exact=' . $exact . ', prefixed=' . $prefix . ')'
		);
	}

	public function down()
	{
		log_message('info', __CLASS__ . '::down() no-op');
		echo "⚠ Migrate_site_menu_repositories_urls::down() is a no-op.\n";
	}
}
