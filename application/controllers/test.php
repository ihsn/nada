<?php
class Test extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->lang->load('general');
    }
 
	function index()
	{	
		return false;
		$this->load->library('endnote');
		
		$str=file_get_contents("c:/wb/workspace/nada3/trash/endnote.enw.txt");
		
		echo '<pre>';
		var_dump($this->endnote->parse($str));
	}
	
	
	function email()
	{
		$this->load->library('email');
		$this->email->clear();
		$this->email->set_newline("\r\n");
		$this->email->from("mehmood@ihsn.org");
		$this->email->to("mah0001@gmail.com");
		$this->email->subject("testing email settings");
		$this->email->message("adsfadsfadsf");
		
		if ($this->email->send())
		{
			echo "it works";
		}
		else
		{
			echo "does not work";
		}

		echo $this->email->print_debugger();
	}
	
	function forge()
	{
		$this->load->dbforge();
		
		$fields = array(
                        'blog_id' => array(
                                                 'type' => 'INT',
                                                 'constraint' => 5, 
                                                 'unsigned' => TRUE,
                                                 'auto_increment' => TRUE
                                          ),
                        'blog_title' => array(
                                                 'type' => 'VARCHAR',
                                                 'constraint' => '100',
                                          ),
                        'blog_author' => array(
                                                 'type' =>'VARCHAR',
                                                 'constraint' => '100',
                                                 'default' => 'King of Town',
                                          ),
                        'blog_description' => array(
                                                 'type' => 'TEXT',
                                                 'null' => TRUE,
                                          ),
                );

		$this->dbforge->add_field($fields);
		$this->dbforge->create_table('blog');
		$this->dbforge->add_key('blog_id', TRUE);
	}	
	
	
	function import_rdf_ipums()
	{
		return "disabled";
		$db=$this->load->database('remote',TRUE);
		$db->select('id,surveyid,nation,data_coll_start');		
		$query=$db->get("surveys")->result_array();
	
		$files=array();
		foreach($query as $row)
		{
			$files[$row['id']]=str_replace(" ", "_",$row['nation']).'_'.$row['data_coll_start'].'.rdf';
		}
		
		//var_dump($files);
		
		$dir="C:/wb/ddi-rdf-extract/output/";

		foreach($files as $key=>$file)
		{
			//check if file exists
			if (file_exists($dir.$file))
			{
				echo 'FOUND<BR>'.$file;
				var_dump($this->_import_rdf($key,$dir.$file));
				//exit;
			}
			else
			{
				echo '<HR>NOT FOUND '.$file;
			}
		}
		
	}
	
	function _import_rdf($surveyid,$filepath)
	{
		//check file exists
		if (!file_exists($filepath))
		{
			return FALSE;
		}
		
		//read rdf file contents
		$rdf_contents=file_get_contents($filepath);
			
		//load RDF parser class
		$this->load->library('RDF_Parser');
		$this->load->model('Resource_model');
			
		//parse RDF to array
		$rdf_array=$this->rdf_parser->parse($rdf_contents);

		if ($rdf_array===FALSE || $rdf_array==NULL)
		{
			return FALSE;
		}

		//Import
		$rdf_fields=$this->rdf_parser->fields;
			
		//success
		foreach($rdf_array as $rdf_rec)
		{
			$insert_data['survey_id']=$surveyid;
			
			foreach($rdf_fields as $key=>$value)
			{
				if ( isset($rdf_rec[$rdf_fields[$key]]))
				{
					$insert_data[$key]=trim($rdf_rec[$rdf_fields[$key]]);
				}	
			}										
			
			//check if it is not a URL
			if (!is_url($insert_data['filename']))
			{
				//clean file paths
				$insert_data['filename']=unix_path($insert_data['filename']);

				//remove slash before the file path otherwise can't link the path to the file
				if (substr($insert_data['filename'],1,1)=='/')
				{
					$insert_data['filename']=substr($insert_data['filename'],2,255);
				}												
			}
			
			//check if the resource file already exists
			$resource_exists=$this->Resource_model->get_resources_by_filepath($insert_data['filename']);
			
			if (!$resource_exists)
			{										
				//insert into db
				$this->Resource_model->insert($insert_data);				
			}
		}
	}
	
	
	function gytis()
	{
		$this->load->database();
		$this->justice=$this->load->database('justice',TRUE);
		// Load the DB utility class
		$this->load->dbutil();
		
		//database utility class		
		$this->load->dbforge();
		
		//get a list of all tables in the database
		$tables = $this->justice->list_tables();		
		$ignore=array();
		$ignore[]='jus_affaire';
		//$ignore[]='jus_audience';
		
		
		foreach($tables as $table)
		{

			if (in_array($table,$ignore))
			{
				continue;
			}		
		
			$delimiter = ",";
			$newline = "\r\n";
			$destination='trash/gytis/'.$table.'.csv';
			$content=NULL;
			
			if (!file_exists($destination))
			{

				//get row count
				$row_count=$this->justice->count_all($table);
				echo "wy";
				if ($row_count>20000)
				{
					echo $table;
					/*$limit=20000;
					$offset=0;
					//$rows_processed=$limit+$offset;
					$k=0;					
					
					while($rows_processed<=$row_count)
					{	
						$rows_processed=$limit+$offset;					
						$destination='trash/gytis/'.$table.$k.'.csv';
						$sql="SELECT * FROM justice.$table LIMIT $offset,$limit";
						echo $sql.'<BR>';
						$query=$this->justice->query($sql);
						$content=$this->dbutil->csv_from_result($query, $delimiter, $newline);
						file_put_contents($destination,$content);
						$k++;
						$offset=$limit+1;
					}
					exit;
					*/
					echo '<BR>file ignored -'.$table.'<BR>';
				}
				else
				{
					echo "processing $table";
					$query=$this->justice->query("SELECT * FROM justice.$table");
					$content=$this->dbutil->csv_from_result($query, $delimiter, $newline);
					file_put_contents($destination,$content);
				}	
			}
			
			echo "--done<BR>";
			
		}

//		var_dump($tables);
	}
	
	function bank_boxes()
	{
		$this->load->view("tests/bank_boxes");
	}
}
/* End of file test.php */
/* Location: ./controllers/test.php */