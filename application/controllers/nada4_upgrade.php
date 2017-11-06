<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
*
* Upgrade nada3 to nada4
*
**/
class Nada4_upgrade extends CI_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->database();

		//check if maintenance mode is enabled
		if ($this->config->item("maintenance_mode")!==1)
		{
			show_error("MAINTENANCE_MODE_OFF");
		}
				
		if (!$this->_test_connection())
		{
			show_error("Failed to connect to your database, please check your database settings.");
		}
		
    }
  
	function index()
	{
		$data['body']=$this->load->view("nada4_upgrade/index",NULL,TRUE);
		$this->load->view('nada4_upgrade/template',$data);
	} 
	
	
	function manual()
	{
		$filename=APPPATH.'../install/nada4-upgrade-'.$this->db->dbdriver.'.sql';
		
		if (!file_exists($filename))
		{
			show_error(t('file_not_found'). ' - schema.sql');
		}
		
		$data['sql']=file_get_contents($filename);
		$data['updates']=implode("\r\n",$this->get_update_study_hits_sql());
		
		$this->load->view("nada4_upgrade/manual",$data);
	}
	
	
	function run_upgrade()
	{
		$this->update_db_schema();
		set_time_limit(0);
		$this->update_study_hits();
		echo "<b>Upgrade completed, if you don't see any error messages printed then your database has been upgraded!</b>";
	}
	
	
	//returns an array of sql statements to update surveys table with views/downloads hit counts
	private function get_update_study_hits_sql()
	{
		$sql=array();
		
		#study views
		$sql['total_views']='select surveyid,count(*) as total from sitelogs 
		where logtype=\'survey\'
		group by surveyid;';
		
		
		#study downloads
		$sql['total_downloads']='select surveyid,count(*) as total from sitelogs 
		where logtype=\'survey\' and (section=\'download\' or section=\'public-download\')
		group by surveyid;';
	
		$output=array();
	
		foreach($sql as $key=>$s)
		{
			set_time_limit(0);
			$query=$this->db->query($s);
			
			if (!$query)
			{
				echo ($this->db->last_query());
				return;
			}
			
			$rows=$query->result_array();
			
			foreach($rows as $row)
			{				
				$output[]=sprintf('update surveys set %s=%d where id=%d',$key,$row['total'],$row['surveyid']);
			}
		}
		
		return $output;	
	}
	
	//updates study views and download hit counts
	private function update_study_hits()
	{
		//get sql statments to execute
		$sql_array=$this->get_update_study_hits_sql();
		
		foreach($sql_array as $sql)
		{
			$result=$this->db->query($sql);
			
			if(!$result)
			{
				echo '<BR>FAILED: '.$this->db->_error_message().'<BR>';
				echo $this->db->last_query();
			}
		}	
	}
	
	
	
	/**
	*
	* add/alter database tables
	*/
	private function update_db_schema()
	{
		//sql file to restore database
		$filename=APPPATH.'../install/nada4-upgrade-'.$this->db->dbdriver.'.sql';
		
		if (!file_exists($filename))
		{
			show_error(t('file_not_found'). ' - schema.sql');
		}
		
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
				//log_message('info', $templine);
				$result=$this->db->query($templine);
				
				if(!$result)
				{
					log_message('error', $templine);
					echo '<BR>UPDATE FAILED: <span style="color:red">'.$this->db->_error_message().'</span><BR>';
					echo $this->db->last_query().'<HR>';
				}

				// Reset temp variable to empty
				$templine = '';
			}
		}
	}

	/**
	*
	* Test database connectivity
	*
	* return	bool
	*/
	function _test_connection()
	{
		$this->db->select('count(*) as total');
		$result=$this->db->get('configurations');
		if (!$result)
		{
			return FALSE;
		}
		return TRUE;
	}

}//end class