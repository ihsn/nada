<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
*
* Upgrade nada44 to nada5
*
**/
class Nada5_upgrade extends CI_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->database();

		if ($this->config->item("maintenance_mode")!==1){
			show_error("MAINTENANCE_MODE_OFF");
		}
    }
  
	function index()
	{
		
	}

	function run()
	{
		//default
		$db_driver=$this->db->dbdriver;

		//mysql, mysqli
		if (in_array($db_driver,array('mysql','mysqli'))){
			$db_driver='mysql';
		}

		//sql file to restore database
		$filename=APPPATH.'../install/nada5-upgrade-'.$db_driver.'.sql';
		
		if (!file_exists($filename)){
			show_error(t('file_not_found'). ' - '. $filename);
		}
		
		$this->process_sql($filename);
	}


	/**
	*
	* Create database tables
	*/
	private function process_sql($filename)
	{				
		// Temporary variable, used to store current query
		$templine = '';
		
		// Read in entire file
		$lines = file($filename);
		
		// Loop through each line
		foreach ($lines as $line)
		{
			// Skip it if it's a comment
			if (substr($line, 0, 1) == '#' || $line == '')
				continue;
		 
			// Add this line to the current segment
			$templine .= $line;
			
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';')
			{
				log_message('info', $templine);
				$result=$this->db->query($templine);				
				if($result){
					echo '<span style="background:green">Success</span>';
				}
				else{
					echo '<span style="background:red">Failed</span>';
				}
				echo $templine;
				if(!$result){
					$err=$this->db->error();
					echo '<div style="color:red">ERROR: '. $err['message'].'</div>';
				}
				echo '<HR>';
				//$result=true;
				
				if(!$result){
					log_message('error', $templine);
					//echo $this->db->last_query();
				}

				// Reset temp variable to empty
				$templine = '';
			}
		}
	}

}//end class