<?php
class Managefiles_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

	/**
	*
	* Get a resource by filepath and surveyid
	*
	* @filepath	relative path to the resource
	*/
	function get_resources_by_filepath($surveyid,$filepath)
	{
		$this->db->where('survey_id', $surveyid); 
		$this->db->where('filename', $filepath); 
		return $this->db->get('resources')->result_array();
	}
	
	
	/**
	*
	* Return the complete survey folder path
	*
	*/
	function get_survey_path($surveyid)
	{
		$this->db->select("dirpath");
		$this->db->where('id', $surveyid); 
		$survey=$this->db->get('surveys')->row_array();
		
		if (($survey))
		{
			$path=$this->config->item("catalog_root");
			
			//throw an exception if catalog_root setting is not set
			if($path===FALSE)
			{
				throw new exception("CATALOG_ROOT not defined.");
			}
			
			if ($survey["dirpath"]=='')
			{
				throw new exception("Survey DIRPATH not defined.");
			}
			
			$path.='/'.$survey['dirpath'];
			$path=unix_path($path);

			return $path;
		}

		return FALSE;
	}
	
	
	/**
	*
	* Return resource by id
	*/
	function get_resource_by_id($resourceid)
	{
		$this->db->select('*');
		$this->db->from('resources');
		$this->db->where('resource_id', $resourceid);
		$query = $this->db->get()->row_array();		
		return $query;
	
	}
	
	function get_resource_paths_array($surveyid)
	{
		//get resources by survey id
		$this->db->select("filename,resource_id,dctype");
		$this->db->where('survey_id',$surveyid);
		$resources=$this->db->get("resources")->result_array();
		
		//get survey folder path
		$survey_folder=$this->get_survey_path($surveyid);
		
		if($resources)
		{
			$result=array();
			foreach($resources as $resource)
			{
				//check if a microdata file
				$ismicro=FALSE;
				
				if(strpos($resource['dctype'],'dat/micro]')!==FALSE || strpos($resource['dctype'],'dat]')!==FALSE)
				{
					$ismicro=TRUE;
				}
			
				//build absolute resource path
				$resource_path=unix_path($survey_folder.'/'.$resource['filename']);				
				$result[$resource["resource_id"]]['filename']=$resource_path;
				$result[$resource["resource_id"]]['ismicro']=$ismicro;
			}
			return $result;
		}
		
		return FALSE;
	}


	/**
	*
	* Return all files including subfolders
	* 
	*	@make_relative_to	make the file path relative to this path
	*/	
	function get_files_recursive($absolute_path,$make_relative_to)
	{
            $dirs = array();
            $files = array();
            // let's traverse the directory
            if ( $handle = @opendir( $absolute_path ))
            {
                while ( false !== ($file = readdir( $handle )))
                {
                    if (( $file != "." AND $file != ".." ))
                    {
                        if ( is_dir( $absolute_path.'/'.$file ))
                        {
                            $tmp=$this->get_files_recursive($absolute_path.'/'.$file,$make_relative_to);
								foreach($tmp['files'] as $arr)
								{
										if (isset($arr["name"]))
										{
											$files[]=$arr;
										}
								}
								foreach($tmp['dirs'] as $arr)
								{
										if (isset($arr["name"]))
										{
											$dirs[]=$arr;
										}
								}

							/*
							$tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));	
							$tmp['name']=$file;
							$tmp['path']=$absolute_path.'/'.$file;
							$dirs[]=$tmp;*/
							$dirs[]=$this->get_file_relative_path($make_relative_to,$absolute_path.'/'.$file);
                        }
                        else
                        {
                            $tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));
							$tmp['name']=$file;
							$tmp['size']=format_bytes($tmp['size']);
							$tmp['fileperms']=symbolic_permissions($tmp['fileperms']);
							$tmp['path']=$absolute_path;
							$tmp['relative']=$this->get_file_relative_path($make_relative_to,$absolute_path);
							$files[]=$tmp;
                        }
                    }
                }
                closedir( $handle );
                sort( $dirs );
                //sort( $files );
            }
		return array('files'=>$files, 'dirs'=>$dirs);
	}

	/**
	*
	* Return files from a single folder (no subfolders)
	* 
	*	@make_relative_to	make the file path relative to this path
	*/	
	function get_files_non_recursive($absolute_path,$make_relative_to)
	{
            $dirs = array();
            $files = array();
            // let's traverse the directory
            if ( $handle = @opendir( $absolute_path ))
            {
                while ( false !== ($file = readdir( $handle )))
                {
                    if (( $file != "." AND $file != ".." ))
                    {
                        if ( is_dir( $absolute_path.'/'.$file ))
                        {
							$dirs[]=$file;
                        }
                        else
                        {
                            $tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));
							$tmp['name']=$file;
							$tmp['size']=format_bytes($tmp['size']);
							$tmp['fileperms']=symbolic_permissions($tmp['fileperms']);
							$tmp['path']=$absolute_path;
							$tmp['relative']=$this->get_file_relative_path($make_relative_to,$absolute_path);
							$files[]=$tmp;
                        }
                    }
                }
                closedir( $handle );
                sort( $dirs );
                //sort( $files );
            }
		return array('files'=>$files, 'dirs'=>$dirs);
	}

	
	
	/**
	*
	* Get file relative path excluding the survey folder path
	*
	*/
	function get_file_relative_path($survey_path,$file_path)
	{
		$survey_path=unix_path($survey_path);
		$file_path=unix_path($file_path);
		
		return str_replace($survey_path,"",$file_path);
	}
	
	
	
	/*
	*
	* Update the Request form for the survey
	*/
	function update_form($surveyid,$formid)
	{
		$this->db->where('id', $surveyid); 
		return $this->db->update('surveys',array('formid'=>$formid) );		
	}

	/**
	* Return the access request form info applied to the survey
	*
	*/
	function get_survey_access_form($surveyid)
	{
		$this->db->select('formid');
		$this->db->where('id', $surveyid); 
		$query=$this->db->get('surveys')->row_array();
		
		if(!$query)
		{
			throw new MY_Exception($this->db->last_query());
		}

		$output['formid']=$query['formid'];

		if (!is_numeric($output['formid']) || $output['formid']<1)
		{
			return FALSE;
		}		
		
		//get form model info
		$this->db->select('model');
		$this->db->where('formid', $output['formid']); 
		$query=$this->db->get('forms')->row_array();

		if(!$query)
		{
			throw new MY_Exception($this->db->_error_message());
		}

		$output['model']=$query['model'];
		return $output;				
	}
	
	/**
	* get all data files by survey id
	*
	*/
	function get_data_files($surveyid)
	{		
		$surveyid=$this->db->escape($surveyid);		
		$where=" survey_id=$surveyid AND (dctype like '%dat/micro]%' OR dctype like '%dat]%') ";
		
		$this->db->select('title,filename,resource_id,changed');
		$this->db->where($where,NULL,FALSE);
		$this->db->from('resources');
		$query = $this->db->get();
		
		if ($query)
		{
			return $query->result_array();
		}
		else
		{
			throw new MY_Exception($this->db->_error_message());
		}
	}
		
}
?>