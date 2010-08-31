<?php
class Backup extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();		
		    $this->load->database();
    }
  
	function index()
	{
		//echo 'hi';
	} 

	/**
	*
	* Create database backup
	*/
	function create($install=FALSE)
	{
		// Load the DB utility class
		$this->load->dbutil();
		
		//database utility class		
		$this->load->dbforge();
		
		//get a list of all tables in the database
		$tables = $this->db->list_tables();
		
		//backup file name
		$filename='backup/db-backup-'.date("Y-m-d-his").'.sql';

		if ($install===FALSE)
		{
			//do not backup data for  the tables
			$ignore=array('sitelogs','projects');
		}
		else
		{
			//for creating install script
			$ignore=array(
					'sitelogs', 
					'surveys', 
					'variables', 
					'blocks', 
					'citations',
					'lic_file_downloads',
					'lic_files',
					'lic_files_log',
					'lic_requests',
					'meta',
					'pages',
					'planned_surveys',
					'repositories',
					'resources',
					'survey_citations',
					'survey_projects',
					'survey_topics',
					'survey_years',
					'ci_sessions',
					'tokens',
					'users',
					'citation_authors',					
					'public_requests',
					'projectsd'
				);
		}
		
		//create statements for ignored tables
		foreach($ignore as $table)
		{
			$contents="\r\n# TABLE STRUCTURE FOR: $table\r\n";
		  	$query=$this->db->query("show create table `$table`");
			if ($query)
			{
				$result=$query->row_array();				
				$contents.=$result['Create Table'].';';
				$contents.="\r\n\r\n\r\n";
				//write to file
				file_put_contents($filename,$contents,FILE_APPEND);
			}
			else
			{
				echo '<br>------------------------<br>';
				echo 'Failed: ' .$this->db->last_query().'<br>';
			}
		}
		
		//iterate and backup each table
		foreach ($tables as $table)
		{
			$table_sql=$this->_backup_single_table($table,$ignore);
			
			//write to file
			file_put_contents($filename,$table_sql,FILE_APPEND);
		}		
		
		echo $filename. ' has been created';
	}
	
	function _backup_single_table($tablename,$ignore=array())
	{
			//backup preferences
			$prefs = array(
                'tables'      => array($tablename),  // Array of tables to backup.
                'ignore'      => $ignore,//array('variables','sitelog'),         // List of tables to omit from the backup
                'format'      => 'txt',             // gzip, zip, txt
                'filename'    => 'db-backup'.date("U").'.sql',    // File name - NEEDED ONLY WITH ZIP FILES
                'add_drop'    => FALSE,              // Whether to add DROP TABLE statements to backup file
                'add_insert'  => TRUE,              // Whether to add INSERT data to backup file
                'newline'     => "\n"               // Newline character used in backup file
              );

		return $this->dbutil->backup($prefs);
	}
	
	function restore()
	{
		return;  
		echo '<pre>';
		$filename='backup/db-backup-2010-06-21-032420.sql';
		
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
							echo $templine.'<HR>';

				// Perform the query
				echo($templine) or print('Error performing query \'<strong>' . $templine . '\': <br /><br />');
				// Reset temp variable to empty
				$templine = '';
			}
		}
	}
	
	
	function toxml()
	{					
		// return all available tables 
		$tables= $this->db->list_tables();

		$output = "<?xml version=\"1.0\" ?>\n"; 
		$output .= "<schema>"; 

		// iterate over each table and return the fields for each table
		foreach ( $tables as $table ) { 
		   $output .= "<table name=\"$table\">"; 
		   
		   //get all fields
		   $fields = $this->db->query('SHOW FIELDS FROM '.$table)->result_array();
		
			//iterate fields
		   foreach($fields as $field) 
		   {
				$field=(object)$field;
				$output .= "<field name=\"$field->Field\" type=\"$field->Type\" default=\"$field->Default\" extra=\"$field->Extra\"";
				$output .= ( $field->Key == 'PRI') ? " primary_key=\"yes\" />" : " />";
		   } 

		   //get all indexes
		   $indexes = $this->db->query('SHOW INDEX FROM '.$table)->result_array();
		
			//iterate indexes
		   foreach($indexes as $index) 
		   {
				$index=(object)$index;
				//$output .= "<index name=\"$index->Key_name\" non_unique=\"$index->Non_unique\" seq_in_index=\"$index->Seq_in_index\" ";
				//$output .= ( $field->Key == 'PRI') ? " primary_key=\"yes\" />" : " />";
				$output .= '<index ';
				foreach($index as $key=>$value)
				{
					$output.= strtolower(" $key =\"$value\" ");
				}
				$output.='/>';
		   } 
		
		   $output .= "</table>"; 
		} 

		$output .= "</schema>"; 

		// tell the browser what kind of file is come in
		header("Content-type: text/xml"); 
		
		// print out XML that describes the schema
		echo $output; 
	}
	
	
	












function convert_mysql_to_postgres()
{

	$source = "/home/shaggy/shaggy.sql";
	$output = "/home/shaggy/pgtest.sql";

	$enum = 'varchar(10)'; // convert enum to this

	if ( !file_exists($source) ) {
	 die("File not found: $sourcen");
	}
	
	$fd = fopen($source, "r");
	$result = fread($fd, filesize($source));
	fclose($fd);
	
	$result = $this->mysql2postgre($result);
	
	$fd = fopen($output, "w");
	
	if (fwrite($fd, $result)) 
	{
		 echo "OKn";
	} 
	else 
	{
	 echo "Failedn";
	}
	
	fclose($fd);
}	

	function mysql2postgre($source) 
	{
		 global $enum;
		
		 $result = $source;
		 $result = preg_replace('/Type=MyISAM/i', '', $result);
		
		 // convert line comments
		 $result = preg_replace("/#(.*)/", '--$1', $result);
		 // and compress newlines
		 $result = preg_replace("/n{2,}/", "nn", $result);
		
		 // get rid of proprietary code
		 $result = preg_replace("/DROP TABLE IF EXISTSW+.+/i", '', $result);
		
		 // indices
		 $result = preg_replace("/(.*)UNIQUE KEY.+((.+))/i",
		 "$1UNIQUE ($2)", $result);
		
		 // a little hack to save primary keys
		 $result = preg_replace("/(.*)PRIMARY KEY.+((.+))/i",
		 "$1PRIMARY ($2)", $result);
		 $result = preg_replace("/,n.*KEYW.+((.+))/i",
		 "n-- was KEY ($1)", $result);
		 $result = preg_replace("/(.*)PRIMARY.+((.+))/i",
		 "$1PRIMARY KEY (\2)", $result);
		
		 $result = preg_replace("/(.*?)(w+).+auto_increment/i",
		 '$1$2 SERIAL', $result);
		
		 // Postgre doesn't support the binary modifier
		 $result = preg_replace('/binary/i', '', $result);
		
		 // type transformations
		 $result = preg_replace('/enum(.+)/i', $enum, $result);
		
		 $result = preg_replace('/tinyint(.+)/i', 'smallint', $result);
		 $result = preg_replace('/smallint(.+)/i', 'smallint', $result);
		 $result = preg_replace('/meduimint(.+)/i', 'int', $result);
		 $result = preg_replace('/int(.+)/i', 'int', $result);
		
		 // Most of my default dates are '0000-00-00'
		 $result = preg_replace("/datetime(.*) default '.*'/i",
		 'datetime$1', $result);
		 $result = preg_replace("/date(.*) default '.*'/i",
		 'date$1', $result);
			
		 return $result;
	}	
		
}//end class