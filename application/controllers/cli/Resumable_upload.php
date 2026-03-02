<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Controller for Resumable Upload Maintenance
 * 
 * Usage:
 *   php index.php cli/resumable_upload/cleanup
 */
class Resumable_upload extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		// Only allow CLI access
		if (!$this->input->is_cli_request()) {
			show_404();
		}
		
		$this->load->library('Resumable_upload', null, 'uploader');
	}
	
	/**
	 * Cleanup expired uploads
	 * 
	 * php index.php cli/resumable_upload/cleanup
	 */
	function cleanup()
	{
		echo "Starting cleanup of expired resumable uploads...\n";
		
		try {
			$stats = $this->uploader->cleanup_expired_uploads();
			
			echo "Cleanup completed:\n";
			echo "  - Checked: " . $stats['checked'] . " uploads\n";
			echo "  - Deleted: " . $stats['deleted'] . " expired uploads\n";
			echo "  - Errors: " . $stats['errors'] . "\n";
			
			if ($stats['errors'] > 0) {
				exit(1);
			}
		}
		catch (Exception $e) {
			echo "Error during cleanup: " . $e->getMessage() . "\n";
			exit(1);
		}
	}
}

