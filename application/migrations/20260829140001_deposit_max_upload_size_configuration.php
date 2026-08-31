<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Site setting: max size in MB for a single data-deposit file upload.
 */
class Migration_Deposit_max_upload_size_configuration extends MY_Migration {

	public function up()
	{
		$this->insert_configuration_if_missing('deposit_max_upload_size', array(
			'value' => '2048',
			'label' => 'Data deposit max file size (MB)',
			'helptext' => 'Maximum size in megabytes for one file uploaded to a data-deposit project.',
			'item_group' => null,
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
