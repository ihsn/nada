<?php
/**
*
* Packager V2
*
* ///////////////////////////////////////////////////////////////////////////////////////
*	CITATION Packaging
* ///////////////////////////////////////////////////////////////////////////////////////
*
* Citations export [ /citations_export ]
*	exports all citations to json/xml format with related survey info. Requires the folder
*	/backup/packages/ to be available with READ/WRITE permissions
*
* Citations import [ /citations_import/<package-file-name> ]
*	input: export package file name e.g. citations-12-11-12.json.xml
*	run: /index.php/utils/packager/import_citations/citations-12-11-12.json.xml
*
*	It only imports citations that are linked to surveys found in the target catalog. It
*	searches for survey IDs in the survey alias table. For inserting/updating citations, it is
*	required that you add IHSN_ID field to the citation's table. It is safe to re-run the script as 
*	it would not import duplicate citations when using IHSN_ID field.
*
*	Requires a minor update to the CITATIONS_MODEL: add IHSN_ID field to update/insert functions
*
*
*	Dependency/Files:
*
*	\models\citations_model.php
*	\models\packager_model.php
*	\controllers\utils\packager.php
*
*
*
*
* ///////////////////////////////////////////////////////////////////////////////////////
*	SURVEY Packaging
* ///////////////////////////////////////////////////////////////////////////////////////
*
*	NOTE: script can take very long to finish, so only run from CLI
*
*	Create Package [create] 
*	It creates an individual package folder for each survey and create a .PKG package file 
*	and copies DDI, RDF and resources listed in the RDF except microdata files.
*
*	Output is stored in the backup/packages/[survey-folder]
*
*	TODO: create a file to list available packages.
*
*
*	RESTORE Package
*	restore_study($package_file) restores a single survey package.
*
*	TODO: Add survey aliases to the export/import options
*
**/
class Packager extends MY_Controller { 
 
 	var $package_folder='';
	var $log_file='';
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
       	$this->load->model('Packager_model');
		$this->load->model('Catalog_model');
		$this->load->model('Citation_model');
		$this->template->set_template('admin');
		
		$this->lang->load("general");
		$this->lang->load("dashboard");
		
		//package folder
		$this->package_folder='backup/packages';
		$this->log_file=$this->package_folder.'/log-'.date("M-d-y").'.txt';
		
		$this->_write_log('====','===================================================');
    }
 
	function index()
	{	
		$data['title']='Packager';
		
		//load the contents of the page into a variable
		$content="Studies packager";
		
		//set page title
		$this->template->write('title', 'data packager',true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	
	//create package
	function create()
	{
		//check requiremnets
		//$this->_check_requirements();
	
		//get all surveys
		$surveys=$this->Packager_model->get_surveys();
		$surveys=$this->Packager_model->get_surveys_by_tags('ihsn');
		
		$output=array();
		$packages_list=array();
		
		//get resources for each survey
		foreach($surveys as $survey)
		{
			//get survey external resources
			//$resources=$this->Packager_model->get_resources($survey['id']);
			
			$survey_obj=(object)$survey;			
			//$survey_obj->resources=$resources;
			
			$output[]=$survey_obj;
			
			$packages_list[]=unix_path($this->package_folder.'/'.md5($survey['surveyid']) );
		}
		
		//list of package files created
		file_put_contents($this->package_folder.'\package-list-'.date("m-d-y-His").'.txt',json_encode($packages_list));

		//package file
		$file_name='/package-'.date("m-d-y-His").'.pkg';
		$file_path=$this->package_folder.$file_name;
		
		//save package contents
		$result=@file_put_contents($file_path, json_encode($output));
		
		if (!$result)
		{
			show_error("FAILED_TO_WRITE_TO_PACAKE_FILE");
		}
		
		echo $file_name.' created successfully!<BR>';
		echo count($output). ' studies were packaged';

		//package individual study + files
		foreach($surveys as $survey)
		{
			echo "packaging ".$survey['id']."\r\n";
			$this->package_study($survey['id'],FALSE);
		}
		
	}

	
	//package a single study
	function package_study($id=NULL,$overwrite=TRUE)
	{
		if (!is_numeric($id))
		{
			show_error("INVALID_STUDY");
		}
		
		/*
			Tasks
			
			1. copy DDI
			2. create RDF
			3. copy files defined in RDF
			4. 
		*/
		
		//get study info from db
		$study=$this->Packager_model->get_survey($id);
		
		if(!$study)
		{
			show_error("STUDY_NOT_FOUND");
		}

		set_time_limit(0);
			
		//get survey folder path
		$survey_folder=$this->Catalog_model->get_survey_path_full($id);
		
		//get study resources
		$study['resources']=$this->Packager_model->get_resources($id);
				
		//start packaging
		
		//check package folder is created
		$study_package_folder=unix_path($this->package_folder.'/'.md5($study['surveyid']) );
		
		//set package folder path in
		$study['package_path']=$study_package_folder;
		
		//try create folder
		@mkdir($study_package_folder);
		
		if (!file_exists($study_package_folder))
		{
			show_error("STUDY_PACKAGE_FOLDER_NOT_FOUND");
		}

		///////////////////////////////////////////////////////////////////////////////////////		
		//	1)	create package file
		///////////////////////////////////////////////////////////////////////////////////////
		$result=@file_put_contents($study_package_folder.'/package.json',json_encode($study));
		
		if (!$result)
		{
			show_error("FAILED_TO_WRITE_PACKAGE_FILE ".$study_package_folder);
		}

		///////////////////////////////////////////////////////////////////////////////////////
		//	2)	copy DDI
		///////////////////////////////////////////////////////////////////////////////////////		
		copy($this->Catalog_model->get_survey_ddi_path($id),unix_path($study_package_folder.'/'.$study['ddifilename']));
		
		$this->_write_log('info','ddi_copied::'.$study['ddifilename']);
		
		///////////////////////////////////////////////////////////////////////////////////////
		// 	3)	copy external resources except microdata
		//////////////////////////////////////////////////////////////////////////////////////
		
		foreach($study['resources'] as $resource)
		{
			$found=false;
			
			//resource types to exclude from copying
			$ignore=array('[dat/micro]','[dat]');
			
			//check if resource is to be ignored
			foreach($ignore as $keyword)
			{
				if (stristr($resource['dctype'],$keyword)!='')
				{
					$found=true;
				}
			}
			
			//skip if from ignored list
			if ($found)
			{
				continue;
			}
			
			//full path to resource file
			$resource_path=unix_path($survey_folder.'/'.$resource['filename']);
			$destination_path=unix_path($study_package_folder.'/'.$resource['filename']);
			
			//copy resource files
			if (file_exists($resource_path) && !is_dir($resource_path))
			{
				//echo 'FOUND FILE'. $resource['filename'];
				set_time_limit(0);
				
				if (!$overwrite && file_exists($destination_path))
				{
					$this->_write_log('info','skipped_resource::'.$resource['filename']);
					continue;//skip file copy if overwrite is set to FALSE
				}
				
				copy($resource_path,$destination_path);
				$this->_write_log('error','copied_resource::'.$resource['filename']);
			}
		}
	}//end-func
	
	
	
	/**
	*
	* Restore a study from package
	**/
	function restore_study($package_file)
	{
		 $data=@file_get_contents($package_file);
		 
		 if (!$data)
		 {
			$this->_write_log('error','restore_study::PACKAGE_NOT_FOUND');
			return FALSE;
		 }
		 
		 $study=json_decode($data);
		 
		 if (!$study)
		 {
		 	$this->_write_log('error','restore_study::FAILED_TO_DECODE');
			return FALSE;
		 }
		
		$this->_write_log('codebookid',$study->surveyid);
		
		//import DDI
		$ddi_import_result=$this->_import_ddi($study,$overwrite=TRUE);
		
		if (!$ddi_import_result)
		{
		 	$this->_write_log('error','restore_study::DDI_IMPORT_FAILED');
			return FALSE;			
		}

				
		//Set Survey Options
		$options=array(
					'link_da'			=>$study->link_da,
					'link_technical'	=>$study->link_technical,
					'link_study'		=>$study->link_study,
					'link_report'		=>$study->link_report,
					'formid'			=>$study->formid,
					'published'			=>0//$study->published
					);
		
		//update survey
		$this->Packager_model->set_survey_options($study->surveyid,$options);
				
		//get internal survey id
		$id=$this->Packager_model->study_exists($study->surveyid);
		
		if (!$id)
		{
			echo 'SURVEY_ID_NOT_FOUND';
		 	$this->_write_log('error','restore_study::SURVEY_ID_NOT_FOUND');
			return FALSE;						
		}
		
		$survey_folder=$this->Catalog_model->get_survey_path_full($id);
		
		//import Resources
		
		//but first remove any existing resources for the study
		$this->Packager_model->delete_resources($study->surveyid);
		
		//import resources
		$this->Packager_model->import_resources($study->surveyid,$study->resources);		
		$this->_write_log('info','restore_study::imported resources');
		
		
		//copy Resource files
		foreach($study->resources as $resource)
		{
			$resource=(array)$resource;
			
			$resource_path=unix_path($study->package_path.'/'.$resource['filename']);
			
			if (file_exists($resource_path))
			{
				$copy=@copy($resource_path,unix_path($survey_folder.'/'.$resource['filename']));
				if ($copy)
				{
					$this->_write_log('copied',$resource['filename']);
				}
				else
				{
					$this->_write_log('copy-failed',$resource['filename']);
				}	
			}
		}
		
	}
	
	
	function restore_study_test()
	{
		 $path='backup/packages/669f1985dddea5d2818e825031d3727a/package.json';
		 
		 $this->_write_log('import', $path);		 
		 $this->restore_study($path);
	}
	
	function restore_all_studies()
	{
		$path='backup/packages/package-list-12-19-12-141955.txt';
		$content=file_get_contents($path);
		$packages=json_decode($content);
		
		$k=0;
		foreach($packages as $package)
		{
			$k++;
			
			if ($k>=5)
			{
				break;
			}
		
			$this->_write_log('import', $package);		 
		 	$this->restore_study($package.'/package.json');
		}
	}
	
	
	
	/**
	*
	* import a DDI from package
	**/
	function _import_ddi($study,$overwrite=TRUE)
	{
		/**
			1) if study already exists, update
			2) if study not found, create new
		
		**/
		
		if ($overwrite==FALSE)
		{
			$id=$this->Packager_model->study_exists($study->surveyid);
			
			if ($id)
			{
				//skip import, study already exists
				 $this->_write_log('import-skipped', 'Skipping, overwrite is set to FALSE and study exists');
				return FALSE;
			}
		}
		
		//load DDI Parser Library
		$this->load->library('DDI_Parser');
		$this->load->library('DDI_Import','','DDI_Import');
		
		$ddi_file=$study->package_path.'/'.$study->ddifilename;
		
		if (!file_exists($ddi_file))
		{
			show_error("DDI_NOT_FOUND".$ddi_file);
		}

		//set file for parsing
		$this->ddi_parser->ddi_file=$ddi_file;
		
		//only available for xml_reader
		$this->ddi_parser->use_xml_reader=TRUE;
		
		//validate DDI file
		if ($this->ddi_parser->validate()===false)
		{
			//log import error
			$error= t('invalid_ddi_file').' '.$ddi_file;
			$this->_write_log('error', $error);
			return FALSE;
		}
						
		//parse ddi to array
		$data=$this->ddi_parser->parse();
		
		//set the repository where the ddi will be uploaded to	
		$this->DDI_Import->repository_identifier=$study->repositoryid;
						
		//import to db
		$result=$this->DDI_Import->import($data,$ddi_file,$overwrite);
		
		if (!$result)
		{
			$error=is_array($this->DDI_Import->errors) ? implode("<BR>",$this->DDI_Import->errors) : $this->DDI_Import->errors;
			$this->_write_log('ddi-import-failed',$error );
		}
		
		return $result;
	}
	
	function _write_log($type,$message)
	{
		$content=date('H:i:s')."\t$type\t$message\r\n";
		file_put_contents($this->log_file,$content,FILE_APPEND);
	}
	
	
	
	/**
	*
	* Export all citations to json format
	**/
	function export_citations()
	{	
		$this->load->model('Citation_model');
	
		$output_file=$this->package_folder.'/citations-'.date("m-d-y").'.json.xml';
		
		//get all citations
		$citations=$this->Packager_model->get_citations_ID_array();
		
		//start output
		$created=@file_put_contents($output_file,'<?xml version="1.0"?>'."\r\n".'<citations>');
		
		if (!$created)
		{
			show_error("OUTPUT_FILE_WRITE_ERROR");
		}
		
		$combine_row_count=50;
		
		$count=0;
		$output='';
		$k=0;
		
		//package each citation
		foreach($citations as $row)
		{
			$count++;
			$k++;
			set_time_limit(0);
			//get citation info
			$citation=$this->Citation_model->select_single($row['id']);

			//prepare for saving			
			$output.='<citation><![CDATA['.json_encode($citation).']]></citation>'."\r\n";
			
			if ($k>=$combine_row_count)
			{			
				//save to output file
				file_put_contents($output_file,$output,FILE_APPEND);
				
				//reset
				$k=0;
				$output='';
				
				echo 'exported '. $count."\r\n";
			}	
		}
		
		if ($output!='')
		{
			file_put_contents($output_file,$output,FILE_APPEND);
		}
				
		//end
		file_put_contents($output_file,'</citations>',FILE_APPEND);		
		
		echo "$k records exported";
	}
	
	function import_citations($filename=NULL)
	{
		
		if (!$filename)
		{
			show_error("NO_FILE");
		}
		
		$input_file=APPPATH.'/../backup/packages/'.basename($filename);
		
		if (!file_exists($input_file))
		{
			show_error("NOT_FOUND");
		}
		
		//initialize the reader	
		$reader = new XMLReader();

		//read the xml file
	    if(!$reader->open($input_file))
		{ 
			show_error("FILE_READING_ERROR");
			return false;
		}
		
		//find citation elements
		while ($reader->read() ) 
		{
			if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == "citation") 
			{
				$this->_import_single_citation($reader->readString());
			}
		}				
		$reader->close();	
	}
	
	
	/**
	*
	* Import a single citation from package
	**/
	private function _import_single_citation($citation)
	{
		$citation=json_decode($citation,TRUE);
		/*
		echo '<pre>';	
		var_dump($citation->related_surveys);
		exit;
		var_dump(json_decode($citation));
		echo '<hr>';
		exit;
		*/
		
		if (!is_array($citation['related_surveys']) && count($citation['related_surveys'])<1)
		{
			//skip citations that are not attached to any surveys
			return FALSE;
		}
		
		$related_surveys=array();
		
		//find matching survey ID in databse
		foreach($citation['related_surveys'] as $related)
		{
			$survey_found=$this->Packager_model->get_survey_uid($related['surveyid']);
			
			if ($survey_found)
			{
				$related_surveys[]=$survey_found;
			}			
		}	
		
		if (count($related_surveys)==0)
		{
			//skip if the related survey is not found in the database
			return FALSE;
		}
				
		//insert/update citation and link to the surveys
		$citation_id=$this->Packager_model->update_citation($citation,$related_surveys);
		
		echo 'updated/inserted: '. $citation_id;
		echo '<BR>';
	}
	
	
	/**
	*
	* Return a list of all surveys from the catalog [published/unpublished]
	**/
	function export_survey_list()
	{
		//array of all surveys found in the catalog
		$surveys=$this->Packager_model->get_surveys();
		
		//array of all survey aliases
		$survey_aliases=$this->Packager_model->get_all_survey_aliases();
		
		echo '<textarea rows="500" style="width:100%;">';
		foreach($surveys as $survey)
		{
			echo $survey['id'].':'.$survey['surveyid'].':';
			if (array_key_exists($survey['id'],$survey_aliases))
			{
				echo implode(',',$survey_aliases[$survey['id']]);
			}
			echo "\r\n";
		}
		echo '</textarea>';		
	}
	
	
	function compare_catalogs()
	{
		$form='<form method="post">';
		$form.='<p>Paste list of surveys [export_survey_list] here:</p>';
		$form.='<textarea name="survey" style="width:100%;height:400px">'.$this->input->post('survey').'</textarea>';
		$form.='<input type="submit" name="submit"/></form>';
		
		echo $form;
		
		$survey=$this->input->post('survey');
		if (!$survey)
		{
			return;
		}
		
		$rows=explode("\r\n",$survey);
		
		$surveys=array();
		
		//build an array of survey IDs and aliases
		foreach($rows as $row)
		{
			$info=explode(":",$row);
			
			if (!isset($info[1]))
			{
				continue;//skip row
			}
			
			$surveys[$info[1]]['surveyid']=$info[1];//codebook id
			if (isset($info[2]) && trim($info[2])!='')
			{
				//aliases
				$surveys[$info[1]]['alias'][]=explode(",",$info[2]);
			}
		}
	
		//compare surveys
		$found=array();
		$notfound=array();
		
		foreach($surveys as $survey)
		{
			$exists=$this->Packager_model->get_survey_uid($survey['surveyid']);
			
			if ($exists)
			{
				$found[$exists]=$survey;
			}
			else
			{
				$notfound[]=$survey;
			}
		}
		
		
		
		echo '<pre>';
		var_dump($found);
	}
	
}
/* End of file packager.php */
/* Location: ./controllers/utils/packager.php */