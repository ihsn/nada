<?php
/**
*
* DDP Utilities 
*
**/
class DDP_Util extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		$this->load->database();
		//header('Content-type: text/html; charset=utf-8');
		$this->load->model("Catalog_model");
		$this->load->model("Resource_model");
		$this->load->library("RDF_Parser");
		
    }
 
	function index()
	{	
		echo "ddp util";
	}
	
	
	
	/**
	*
	* Check all survey folders to report unused/deleted folders
	*
	**/
	function deleted_surveys()
	{
		$folder=unix_path('//decfile\datalib\DDP_DATA\prod\nada_ddp_datafiles/');
		$repositories=$this->get_folder_names($folder);
		//var_dump($repositories);
		
		$folders_arr=array();
		foreach($repositories as $repository)
		{
			$folders_arr[$repository]=$this->get_folder_names($repository);
		}
		
		var_dump($folders_arr);
	}
	
	//returns a list of folder 
	function get_folder_names($folder_path)
	{
		$dir=array();
		if ($handle = opendir($folder_path)) {
			echo "Directory handle: $handle\n";
			echo "Entries:\n";

			/* This is the correct way to loop over the directory. */
			while (false !== ($entry = readdir($handle))) 
			{
				if($entry!="." && $entry!="..")
				{ 
					$path=unix_path($folder_path.'/'.$entry);
					if (is_dir($path))
					{
						$dir[] = $path; 
					}	
				} 
			}
			closedir($handle);
		}
		return $dir;
	}
	
	
	
	/**
	*
	*
	**/
	function diff()
	{
		$this->load->helper("file");
		
		$folder=unix_path('//decfile\datalib\DDP_DATA\prod\nada_ddp_datafiles');
		//$folder='\\\decfile\datalib\DataLib\Bhutan\BTN_2009_EMP';
		
		if ($handle = opendir($folder)) {
			echo "Directory handle: $handle\n";
			echo "Entries:\n";

			/* This is the correct way to loop over the directory. */
			while (false !== ($entry = readdir($handle))) {
				echo "$entry\n";
			}

   		 closedir($handle);
		}
		
		var_dump($files);
	}
	
	
	//copy survey files
	function copy_files()
	{
		$this->db->select("map.*, surveys.nation");
		$this->db->limit(1);
		$this->db->join('tmp_survey_folder_mapping map', 'map.sid= surveys.id','inner');
		$this->db->where("status","found");
		$surveys=$this->db->get("surveys")->result_array();
		echo '<pre>';
		
		foreach($surveys as $survey)
		{
			$survey=(object)$survey;
			//get a list of files from remote share
			$remote_files=get_dir_recursive($survey->remotepath,$make_relative_to=FALSE);
			echo $survey->remotepath;
			var_dump($remote_files);
		}
	}
	
	
	//map survey to the q drive folder
	function map_survey_folders()
	{
		//$this->db->query("truncate table tmp_survey_folder_mapping");
		$surveys=$this->_get_surveys();
		foreach($surveys as $survey)
		{
			$obj=(object)$survey;
			$this->_map_single_survey_folder($obj);
		}	
	}
	function _map_single_survey_folder($surveyOBJ)
	{
		$countries_map=array(
				'Gambia,_The'=>'Gambia_The',
				'Yemen,_Rep.'=>'Yemen_Rep',
				'Bosnia_and_Herzegovina'=>'Bosnia_Herzegovina',
				'Côte_d\'Ivoire'=>'Côte_d_Ivoire',
				'Congo,_Dem._Rep.'=>'Congo_Democratic_Republic',
				'Egypt,_Arab_Rep.'=>'Egypt_Arab_Republic',
				'Guinea-Bissau'=>'Guinea_Bissau',
				'Congo,_Rep.'=>'Congo_Republic',
				'St._Lucia'=>'St_Lucia',
				'Micronesia,_Fed._Sts.'=>'Micronesia_Federated_States',
				'Serbia_and_Montenegro'=>'Serbia and Montenegro',
				'Timor-Leste'=>'Timor_Leste',
				'Europe_and_Central_Asia'=>'eca_region',
				'São_Tomé_and_Principe'=>'Sao_Tome_and_Principe',
				'Venezuela,_RB'=>'Venezuela_RB',
				'Bahamas,_The'=>'Bahamas_The',
		);
		
		
		//find a survey folder on the remote network share
		$country=utf8_decode(str_replace(" ","_",$surveyOBJ->nation));
		
		if (array_key_exists($country,$countries_map))
		{
			$country=$countries_map[$country];
		}		
		
		$ddifilename=$surveyOBJ->ddifilename;
		
		$file_parts=explode("_",$ddifilename);
		
		if (count($file_parts)<3)
		{
			echo '<span style="color:blue;">';
			echo 'skipped: ' . $ddifilename.'<BR>';
			echo '</span>';
			return;
		}
		
		$folder=$file_parts[0].'_'.$file_parts[1].'_'.$file_parts[2];
		
		$share_path='//DECFILE/DataLib/dataLib/'.$country.'/'.$folder.'/'.str_replace(".xml","",$surveyOBJ->ddifilename);
		
		if (file_exists($share_path))
		{
			//echo 'Folder found: ' . $share_path.'<BR>';
			
			//add info to db
			$data=array(
					'sid'=>$surveyOBJ->id,
					'remotepath'=>utf8_encode($share_path),		
					'status'=>'found'			
			);
			$this->db->where("sid",$surveyOBJ->id);
			$this->db->insert("tmp_survey_folder_mapping",$data);
			
		}
		else
		{
			echo '<span style="color:red;">';
			echo 'not-found: ' . $share_path.' - '.$surveyOBJ->surveyid.'<BR>';
			echo '</span>';
		}
		
	}
	
	


	function _get_surveys()
	{
		$this->db->select("id,surveyid,nation,dirpath,ddifilename");
		//$this->db->limit(300);
		$query=$this->db->get("surveys")->result_array();
		
		return $query;
	}

	function _get_survey_single($codebook_id)
	{
		$this->db->select("id,ddifilename,dirpath,surveyid,nation");
		$this->db->where("surveyid",$codebook_id);
		return $this->db->get("surveys")->row_array();
	}
	
	
	/*
	function resources_to_json()
	{
		$this->db->select("resources.*,surveys.surveyid");
		$this->db->join('surveys', 'resources.survey_id = surveys.id','inner');
		$this->db->limit(10);
		$query=$this->db->get("resources")->result_array();
		
		$json=json_encode($query);
		file_put_contents("datafiles/resources.json",$json);
	}
	*/
	
	/*
	function json_to_db()
	{
		$json=file_get_contents("trash/resources.js");
		$decoded=json_decode($json);
		foreach($decoded as $row)
		{
			$row=(array)$row;
			
			//get internal surveyid
			$id=$this->_get_surveyid($row['surveyid']);
			
			
			if ($id)
			{
				echo "found:".$id.'<BR>';
	
				//add resource to survey
				$data=$row;
				//remove fields not needed for insert
				unset($data['resource_id']);
				unset($data['surveyid']);
				$data['survey_id']=$id;
				
				$this->db->insert("resources",$data);
			}			
		}		
	}
	*/
	/*
	function _get_surveyid($codebook_id)
	{
		$this->db->select("id");
		$this->db->where("surveyid",$codebook_id);
		$result=$this->db->get("surveys")->result_array();
		
		if (count($result)>0)
		{
			return $result[0]["id"];
		}
		
		return FALSE;
	}
	*/
	
	
	function sync_all()
	{
		//map_survey_folders(); //call this method to build the mappings
		$this->db->select("*");
		//$this->db->limit(3);
		$this->db->where("sid > ",309);
		$surveys=$this->db->get("tmp_survey_folder_mapping")->result_array();
		$this->log_file_name='sync-log-'.date("U").'.txt';
		
		foreach($surveys as $survey)
		{
			$this->sync_study($survey['sid'],$survey['remotepath']);
			set_time_limit(0);
		}
		
	}
	
	/**
	*
	* Update study files and resources from the Q drive
	*
	* @surveyid				internal survey id
	* @survey_source_path	Survey source path outside nada (e.g. datalib share //decfile\Datalib\DataLib\Timor_Leste\TLS_2007_LSMS\TLS_2007_LSMS_v01_M) 
	**/
	function sync_study($surveyid, $survey_source_path)
	{
		//get survey info
		$survey=$this->Catalog_model->select_single($surveyid);
		
		if (!isset($this->log_file_name))
		{
			$this->log_file_name='sync-log-'.date("U").'.txt';
		}
				
		$this->_write_log($this->log_file_name,$message_type="INFO",$message="################### Processing Survey ".$surveyid." ###########################");
		
		if (!$survey)
		{
			$this->_write_log($this->log_file_name,$message_type="ERROR",$message="SURVEY ID NOT FOUND: ".$surveyid);
			return false;
		}
				
		//ddi file name
		$ddifilename=$survey["ddifilename"];
		
		//rdf file name
		$rdf_file_name=substr($ddifilename,0,strlen($ddifilename)-3).'rdf';
		
		//path to survey folder
		$survey_folder=$this->Catalog_model->get_survey_path_full($surveyid);
		
		//check survey source folder exists
		if (!file_exists($survey_source_path))
		{
			$this->_write_log($this->log_file_name,$message_type="ERROR",$message="SOURCE_FOLDER_NOT_FOUND".$survey_source_path);
			return FALSE;
		}
		
		//RDF file path
		$rdf_path=unix_path($survey_source_path.'/'.$rdf_file_name);
				
		//RDF Found
		if (!file_exists($rdf_path))
		{
			$this->_write_log($this->log_file_name,$message_type="ERROR",$message='RDF_NOT_FOUND'.$rdf_path);
			return FALSE;
		}
		
		
		//Import RDF and overwrite existing RDF entries in DB
		$rdf_content=file_get_contents($rdf_path);
		
		//Parse RDF to an Array
		$resources_arr=$this->rdf_parser->parse($rdf_content);
		
		//field mappings
		$rdf_fields=$this->rdf_parser->fields;
		
		$resources=array();
		
		//iterate resources array and convert to more accessible array format
		foreach($resources_arr as $rdf_rec)
		{
			$insert_data=array();
			foreach($rdf_fields as $key=>$value)
			{
				if ( isset($rdf_rec[$rdf_fields[$key]]))
				{
					$insert_data[$key]=trim($rdf_rec[$rdf_fields[$key]]);
				}	
			}
			$resources[]=$insert_data;
		}
		
		//var_dump($resources);
		
		//check RDF
		if (count($resources)==0)
		{
			$this->_write_log($this->log_file_name,$message_type="ERROR",$message='RDF_EMPTY: '.$survey_source_path);
			return FALSE;
		}
		
		//remove existing resources from DB
		$this->_remove_resources($surveyid);
		$this->_write_log($this->log_file_name,$message_type="INFO",$message='EMPTY_RDF_FROM_DB:'.$surveyid);
		
		//process each resource
		foreach($resources as $resource)
		{
			echo $resource['filename'].'<BR>'."\r\n";

			//check if it is not a URL
			if (!is_url($resource['filename']))
			{
				//fix html entities e.g &amp; &quot;
				$resource['filename']=htmlspecialchars_decode($resource['filename'],ENT_QUOTES);
				
				//clean file paths
				$resource['filename']=unix_path($resource['filename']);

				//remove slash before the file path otherwise can't link the path to the file
				if (substr($insert_data['filename'],0,1)=='/')
				{
					$resource['filename']=substr($resource['filename'],1,255);
				}
				
				//file path on the Q drive
				$resource_file_path_source=unix_path($survey_source_path.'/'.$resource['filename']);
				
				//file path from survey datafiles folder
				$resource_file_path_target=unix_path($survey_folder.'/'.basename($resource['filename']));
			
				//copy file if set to TRUE
				$make_copy=FALSE;
				
				echo 'SOURCE:'.$resource_file_path_source.'<BR>'."\r\n";
				echo 'TARGET:'.$resource_file_path_target.'<BR>'."\r\n";
				
				//copy resource file (if newer/changed)
				if (file_exists($resource_file_path_source))
				{
					//target file not found
					if (!file_exists($resource_file_path_target) )
					{
						$make_copy=TRUE;
					}
					//source and target times are different
					else if (filemtime($resource_file_path_source) != filemtime($resource_file_path_target))
					{
						$make_copy=TRUE;
					}
				}
				else
				{
					echo "#################### SOURCE FILE NOT FOUND #####################<BR>"."\r\n";
					$this->_write_log($this->log_file_name,$message_type="ERROR",$message='RDF Source file not found: '.$resource_file_path_source);
				}
				
				//copy file
				if ($make_copy==TRUE)
				{
					//echo 'copying  '.$resource_file_path_source.'<BR>';
					$file_copied=@copy($resource_file_path_source,$resource_file_path_target);				
					$this->_write_log($this->log_file_name,$message_type="INFO",$message='Copying RDF from: '.$resource_file_path_source. ' to: '.$resource_file_path_target);
										
					//copy files to overwrite existing files
					if ($file_copied)
					{
						$this->_write_log($this->log_file_name,$message_type="INFO",$message='Resource copied: '.$resource_file_path_source);
					}
					else
					{
						$this->_write_log($this->log_file_name,$message_type="ERROR",$message='Resouce copy failed: '.$resource_file_path_source. ' to: '.$resource_file_path_target);
					}
				}
				else
				{
					echo 'SKIPPED file:'.$resource_file_path_source.'<BR>'."\r\n";
				}
			
				//get rid of folder paths
				$resource['filename']=basename($resource['filename']);
				
				$resource['title']=substr($resource['title'],0,255);
			
			}//end-is_url
			
			//add survey id to resource array
			$resource['survey_id']=$surveyid;
			
			
			
			//import to db
			$this->Resource_model->insert($resource);
						
			//log
			$this->_write_log($this->log_file_name,$message_type="INFO",$message='RDF Import to DB: '.$resource['filename']);

			echo '<HR>';
		}
		
		//Copy distribute folder if exists
		$distribute_folder=unix_path($survey_source_path.'/data/distribute');
		
		if (file_exists($distribute_folder))
		{
			$this->load->helper("file_helper");
			@mkdir($survey_folder.'/distribute');
			
			$data_files=get_dir_recursive($path=$distribute_folder,$make_relative_to=$distribute_folder);

			if (count($data_files['files'])==0)
			{
				$this->_write_log($this->log_file_name,$message_type="INFO",$message='NO DATA FILES: '.$surveyid);
			}
			
			foreach($data_files['files'] as $file)
			{
				$data_target_path=$survey_folder.'/distribute/'.basename($file);
				$copied=@copy ($distribute_folder.$file,$data_target_path);
				
				if ($copied)
				{
					$this->_write_log($this->log_file_name,$message_type="INFO",$message='Datafile copied: '.$data_target_path);
				}
				else
				{
					$this->_write_log($this->log_file_name,$message_type="ERROR",$message='Datafile copy failed: '.basename($file));
				}	
			}
		}
		//Delete the files that are not found in RDF
		
	}
	
	
	
	function _write_log($file_name,$message_type,$message)
	{		
		if (basename($file_name)=="")
		{
			return FALSE;
		}
		
		file_put_contents("logs/".basename($file_name),date("m-d-y",date("U"))."\t".$message_type."\t".$message."\n",FILE_APPEND);
	}
	
	
	function test_log()
	{
		$this->_write_log($file_name="logs/testlog.txt",$message_type="test",$message="this is a test message");
	}
	
	/**
	*
	* Remove resources from db
	**/
	function _remove_resources($surveyid)
	{
		$this->db->where('survey_id',$surveyid);
		$this->db->where("dctype not like '%dat/micro]' and dctype not like '%dat]'");
		$this->db->delete('resources');
		echo $this->db->last_query();
	}
	
	
}
/* End of file ddp_util.php */
/* Location: ./controllers/ddp_util.php */