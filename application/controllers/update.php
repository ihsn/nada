<?php
/**
*
* Backup selective database tables
*
**/
class Update extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);		
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
			//do not backup data for the tables
			$ignore=array("sitelogs");
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
					'harvester_queue'
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
		   $output .= "<table name=\"$table\">\n"; 
		   
		   //get all fields
		   $fields = $this->db->query('SHOW FIELDS FROM '.$table)->result_array();
		
			//iterate fields
		   foreach($fields as $field) 
		   {
				$field=(object)$field;
				$default=($field->Default===NULL) ? 'NULL' : $field->Default;
				$output .= "<field name=\"$field->Field\" type=\"$field->Type\" null=\"$field->Null\" default=\"$default\" extra=\"$field->Extra\"\n";
				$output .= ( $field->Key == 'PRI') ? " primary_key=\"yes\" />" : " />\n";
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
				$output.='/>'.PHP_EOL;
		   } 
		
		   $output .= "</table>".PHP_EOL; 
		} 

		$output .= "</schema>".PHP_EOL; 

		// tell the browser what kind of file is come in
		header("Content-type: text/xml"); 
		
		// print out XML that describes the schema
		echo $output; 
	}

	function to_array()
	{					
		// return all available tables 
		$tables= $this->db->list_tables();

		$output = array(); 

		// iterate over each table and return the fields for each table
		foreach ($tables as $table) 
		{ 
			//get all fields
			$fields = $this->db->query('SHOW FIELDS FROM '.$table)->result_array();			
			$output[$table]['schema']=$fields;
		   //get all indexes
			$indexes = $this->db->query('SHOW INDEX FROM '.$table)->result_array();		
			$output[$table]['indexes']=$indexes;
			break;
		} 
		echo '<pre>';
		var_dump($output); 

		return $output;
		// print out XML that describes the schema
		echo '<pre>';
		var_dump($output); 
	}
	
	
	function to_mssql()
	{
		echo '<pre>';
		$tables=$this->to_array();
		
		foreach($tables as $tname=>$table)
		{
			echo $tname;
			echo '<HR>';
			//var_dump($table['indexes']);
		
			foreach($table as $key=>$field)
			{
				var_dump($field);exit;
				$field=(object)$field;
				
				//$field->Type=str_replace('(',' (',$field->Type);
				$fields[$key]['info']=$this->get_field_info($field->Type);
				//var_dump($info);
				/*return;
				echo $field->Field;
				echo ' ';
				echo $field->Type;
				echo '<HR>';*/
				echo sprintf('[%s] [%s] %s %s,',
							$field->Field, 
							$fields[$key]['info']['type'],
							($fields[$key]['info']['length'] >0) ? '('.$fields[$key]['info']['length'].')' : '',
							($field->Null=='YES') ? 'NULL' : ''	);
				echo '<BR>';
			}

		}
		print_r($tables);
		
		return;
		
		
		$table='citations';
		$fields = $this->db->query('SHOW FIELDS FROM '.$table)->result_array();
		$output[$table]['schema']=$fields;

		//get all indexes
		$indexes = $this->db->query('SHOW INDEX FROM '.$table)->result_array();		
		$output[$table]['indexes']=$indexes;
		
		foreach($output as $tablename=>$table)
		{
			var_dump($tablename);
			
			foreach($table as $key=>$field)
			{
				var_dump($field);exit;
				$field=(object)$field;
				
				//$field->Type=str_replace('(',' (',$field->Type);
				$fields[$key]['info']=$this->get_field_info($field->Type);
				//var_dump($info);
				/*return;
				echo $field->Field;
				echo ' ';
				echo $field->Type;
				echo '<HR>';*/
				echo sprintf('[%s] [%s] %s %s,',
							$field->Field, 
							$fields[$key]['info']['type'],
							($fields[$key]['info']['length'] >0) ? '('.$fields[$key]['info']['length'].')' : '',
							($field->Null=='YES') ? 'NULL' : ''	);
				echo '<BR>';
			}
		}		
		print_r($fields); 
	}

	function convert_field_to_mssql($field)
	{
		return $field;
	}
	
	function get_field_info($type)
	{
			$type=str_replace('(',' (',$type);			
			$type_arr=explode(' ', $type);
			
			//find parenthesis if any
			$pos_open=strpos($type,'(');
			$pos_close=strpos($type,')');
			
			$name=$type_arr[0];//substr($type,0,$pos_open-1);
			$length=FALSE;
			if (isset($type_arr[1]))
			{
				$length=str_replace('(','',$type_arr[1]);//substr($type,$pos_open,$pos_close);
				$length=str_replace(')','',$length);
			}	

			$info['type']=$name;
			$info['length']=$length;			
			return $info;
	}



	function _format_array($array)
	{
		$output="Array (\r\n";
		foreach($array as $key=>$value)
		{
			if (is_array($value))
			{
				$output.=$this->_format_array($value);
			}
			else
			{
				$output.="\t[$key]=>\"$value\"\r\n";
			}	
		}
		$output.="\r\n)";
		return $output;
	}

	function parsexml()
	{
		echo '<pre>';
		$file=file_get_contents("c:/wb/workspace/nada3/install/schema.xml");
		$xml = new SimpleXMLElement($file);
		//print_r($xml);
		
		//iterate tables
		foreach ($xml->table as $table) 
		{
			echo $table['name'], PHP_EOL;
			
			foreach($table->field as $field)
			{
				echo "\t",$field['name'], PHP_EOL;
				if ($field['primary_key']=='yes')					
				{
					echo 'PK=TRUE',PHP_EOL;
				}
   			}
		}
	}

	function diff($old=NULL,$new=NULL)
	{
		//get db schema from xml file
		$file=file_get_contents("c:/wb/workspace/nada3/install/schema.xml");
		$xml = new SimpleXMLElement($file);
		
		//get current db schema
		$current_schema=$this->to_array();

		//array of sql statements for diff
		$updates=array();
		
		//var_dump($current_schema);exit;
		
		//list of all tables
		$tables= $this->db->list_tables();
		
		echo '<pre>';
		//print_r($tables);exit;
		
		//iterate tablesv
		foreach ($xml->table as $table) 
		{
			echo $table['name'],(in_array($table['name'],$tables) ? '<b> --FOUND</b>' :' <b>--MISSING</b>'),PHP_EOL;
			
			//table does not exist
			if (!in_array($table['name'],$tables))
			{
				echo $this->_build_table_create_sql($table);
				
			}
			
			/*foreach($table->field as $field)
			{
				echo "\t",$field['name'], PHP_EOL;
				if ($field['primary_key']=='yes')					
				{
					echo 'PK=TRUE',PHP_EOL;
				}
   			}*/
		}
		
	}
	
	function _build_index_sql($table_obj)
	{
		//ALTER TABLE `nada_7876`.`blocks` ADD INDEX `Index_2d`(`title`, `region`);
		
		$indexes=array();
		foreach($table_obj->index as $index)
		{			
			//if ($index['key_name']!=='primary')
			$indexes[(string)$index['key_name']][(int)$index['seq_in_index']]=$index['column_name'];
		}

		$output=array();
		foreach ($indexes as $key=>$value)
		{
			if ($key!=='primary')
			{
				foreach($table_obj->index as $index)
				{			
					if ($index['key_name']==$key)
					{
						$index_str='';
						$index_str=($index['non_unique']==0) ? 'UNIQUE ' : ' ';//unique or not
						if ($index['index_type']=='fulltext')
						{
							$index_str.=' fulltext ';
						}
						$index_str.=' KEY '.$key;
						$index_str.='('.implode(',',$value).')';
						$output[]=$index_str;
						break;
					}
				}	
			}
		}
		
		/*
		  UNIQUE KEY idxSurvey (varID,surveyid_FK),
  KEY idxsurveyidfk (surveyid_FK),
  FULLTEXT KEY idx_qstn (qstn),
		*/	
		echo '<pre>';
		print_r($output);
		print_r($indexes);
	}
	
	function _build_table_create_sql($table_obj)
	{
		$table_create="create table {$table_obj['name']}( \r\n";
		
		$fields=array();
		$pk_sql='';
		foreach($table_obj->field as $field)
		{
			$null= 'NOT NULL';
			if ($field['null']=='YES')
			{
				$null="DEFAULT {$field['default']}";
			}
			
			//primary key
			if ($field['primary_key']=='yes')					
			{	
				$pk_sql="primary key({$field['name']}) \r\n";
			}
			
			$fields[]="{$field['name']} {$field['type']} $null {$field['extra']}" ;
   		}
		
		$table_create.=implode(", \r\n",$fields);
		
		if (trim($pk_sql)!='')
		{		
			$table_create.=",\r\n".$pk_sql;
		}
			
		$table_create.=');';
		
		echo '<HR><div style="color:red;">';
			$this->_build_index_sql($table_obj);
		exit;
		echo '</div>';
		return $table_create;
	}
	

}//end class