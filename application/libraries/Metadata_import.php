<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Metadata Import Class
 *
 */
class Metadata_Import{

    private $ci;
    private $file_path;//metadata file path
    private $file_type; //survey,timeseries,etc
    private $sid;
    private $user_id;
    private $repositoryid='central';
    private $published=0;
    private $formid=6;//data not available
    private $catalog_root; //storage folder path
    private $overwrite=false;

    //@params array (type, file_path)
    public function __construct($params=NULL)
    {
        //required parameters
        $required=array(
            'file_type',
            'file_path',
            //'catalog_root',
            'user_id',
            'published',
            'formid',
            'repositoryid',
            'overwrite'
        );
        
        foreach($required as $field)
        {
            if(array_key_exists($field,$params)){
                $this->{$field}=$params[$field];
            }
            else{
                throw new Exception("METADATA_IMPORT::MISSING_PARAMS: ".$field);
            }
        }
        
        /*if (!isset($params['type']) || !isset($params['file_path']) || !isset($params['catalog_root'])){
            throw new Exception("METADATA_IMPORT::MISSING_PARAMS: Type or File_path");
        }

        $this->file=$params['file_path'];
        $this->file_type=$params['type'];
        $this->catalog_root=$params['catalog_root'];
        
        if (isset($params['user_id'])){
            $this->user_id=$params['user_id'];
        }

        if (isset($params['repositoryid'])){
            $this->repositoryid=$params['repositoryid'];
        }

        if (isset($params['published'])){
            $this->published=$params['published'];
        }

        if (isset($params['formid'])){
            $this->formid=$params['formid'];
        }
        */
        
        $this->ci =& get_instance();
        $this->ci->load->library('Metadata_parser', $params);
        $this->ci->load->model('Survey_type_model');
        $this->ci->load->model("Survey_model");
        $this->ci->load->model("Variable_model");
        $this->ci->load->model('Catalog_model');

        $this->catalog_root=get_catalog_root();
    }



	public function get_sid()
	{
		return $this->sid;
    }
    
    


    //create folder for the study
    public function setup_folder($repositoryid, $folder_name)
    {
        $repository_folder=$this->catalog_root.'/'.$repositoryid;
        $survey_folder=$repository_folder.'/'.$folder_name;

        //create the repo folder and survey folder
        @mkdir($survey_folder, 0777, $recursive=true);

        if(!file_exists($repository_folder)){
            throw new Exception("REPO_FOLDER_NOT_CREATED:".$repository_folder);
        }

        if(!file_exists($survey_folder)){
            throw new Exception("SURVEY_FOLDER_NOT_CREATED:".$survey_folder);
        }

        return $survey_folder;
    }


	public function copy_file($source,$target)
	{
		@copy($source, $target);

		if(!file_exists($target)){
			throw new Exception("FILE_NOT_COPIED: ".$target);
		}
	}



    public function import($sid=NULL)
    {
        //parser to read metadata
        $parser=$this->ci->metadata_parser->get_reader();

        echo "<pre>";
        //var_dump($parser->get_metadata_array());
        echo "</pre>";

        //echo $parser->get_id();
        //echo $parser->get_title();
        //echo $parser->get_years();

        $idno=$parser->get_id();

        //sanitize ID to remove anything except a-Z1-9 characters
        if ($idno!==$this->sanitize_filename($idno)){
            throw new Exception(t('IDNO_INVALID_FORMAT').': '.$idno);
        }       

        //check if the study already exists, find the sid
        if (!$sid){
            $sid=$this->ci->Survey_model->find_by_idno($idno);
            $this->sid=$sid;
        }

        //study found
		if ($sid){
            //overwrite?
            if(!$this->overwrite){
                throw new Exception("SURVEY_ALREADY_EXISTS: ".$sid);
            }
                        
            //check if study is owned by the active repository
            $owner_repository=$this->ci->Catalog_model->get_study_owner($sid);
            
            if (!$owner_repository){
                $owner_repository='central';
            }

			if($owner_repository!==$this->repositoryid){
				$this->repositoryid=$owner_repository;
			}
		}

		$repositoryid=$this->repositoryid;
		$ddi_filename=$idno.".xml";

        //generate survey folder name hash
        $survey_folder_hash=md5($repositoryid.':'.$idno);

		//survey folder path
        $survey_folder_path=$this->setup_folder($repositoryid,$survey_folder_hash);
        
        //partial relative path to survey folder
        $survey_folder_rel_path=$repositoryid.'/'.$survey_folder_hash;

		//target file path
		$survey_target_filepath=$survey_folder_path.'/'.$idno.'.xml';

		//copy the xml file to the survey folder
		$this->copy_file($this->file_path, $survey_target_filepath);

		//array_to_string($data,$type='text')
		$options=array(
            'repositoryid'	=> $repositoryid,
            'surveyid'		=> $idno,
            'titl'			=> $parser->get_title(),
            'abbreviation'	=> $parser->get_abbreviation(),
            'authenty'		=> $parser->array_to_string($parser->get_authenty(),'array'),
            'producer'		=> $parser->array_to_string($parser->get_producers(),'array'),

            //TODO recheck this is the correct field
            'sponsor'		=> $parser->array_to_string($parser->get_sponsors(),'array'),

            'changed'		=> date("U"),
            'changed_by'    => $this->user_id,
            'metafile'	    => $ddi_filename,
            #'dirpath'		=> $repositoryid.'/'.md5($idno),
            'nation'        => $parser->get_countries_str(),
            'year_start'=>$parser->get_start_year(),
            'year_end' => $parser->get_end_year(),
            'metadata'      => $this->encode_metadata($this->transform_metadata_keys($parser->get_metadata_array()))
		);

		//limit field length
		//q1    $options['producer']=substr($options['producer'],0,500);


		//options only for new studies
		if (!$sid){
			$options['dirpath']		= $repositoryid.'/'.$survey_folder_hash;
			$options['published']	= $this->published;
			$options['formid']		= $this->formid;
            $options['created']		= date("U");
            $options['created_by']  = $this->user_id;
		}

		//add new survey to DB
		if(!$sid){
            //create new survey and return the sid
            $sid=$this->ci->Survey_model->insert($options);
            $this->sid=$sid;
		}
		else //existing survey
		{
            $this->ci->Survey_model->update($sid,$options);
		}

        //callbacks for updating related tables

        //survey_years
        $this->update_survey_years($sid, $parser->get_years());

        //survey_topics
        $this->update_survey_topics($sid, $parser->get_topics());

        //survey_aliases
        

        //survey_countries
        $this->update_survey_countries($sid, $parser->get_countries());

        //survey bbox
        $this->update_survey_locations($sid, $parser->get_bounding_box());

        //survey_repos
        $this->update_survey_repos($sid,$this->repositoryid);


        //get list of data files
        $files=(array)$parser->get_data_files();

        $data_files=array();
        foreach($files as $file){
            $data_files[$file['id']]=$file;
        }
        unset($files);

        //import data files and update data_files with file db id
        $data_files=$this->update_survey_data_files($sid,$data_files);

        //import variables
        $variables_imported=$this->import_variables($sid,$data_files, $parser->get_variable_iterator());

        //update survey variables count
        $this->ci->Survey_model->update($sid,array('varcount'=>$variables_imported));

        return array(
            'sid'=>$sid,
            'surveyid'=>$idno,
            'varcount'=>$variables_imported,
            'published'=>$this->published,
            'repositoryid'=>$this->repositoryid,  
            'folder'=>$survey_folder_rel_path
        );
    }

    //convert array keys for metadata
    function transform_metadata_keys($metadata_arr){
        $output=array();
        foreach($metadata_arr as $el_key=>$el_value)
        {
            $el_key=strtolower(str_replace("/","_",$el_key));
            $output[$el_key]=$el_value;
        }
        return $output;
    }


    /**
	*
	* Update/add survey_repos info
	*/
	function update_survey_repos($surveyid,$repositoryid)
	{
		$data=array(
				'sid'=>$surveyid,
				'repositoryid'=>$repositoryid,
				'isadmin'=>1 //give admin rights to the repo that uploaded the survey
			);

		//delete any existing entry for the study
		$this->ci->db->where('sid',$surveyid);
		$this->ci->db->where('repositoryid',$repositoryid);
		$this->ci->db->delete('survey_repos');

		//add new info
		$this->ci->db->insert('survey_repos',$data);
		return TRUE;
	}


    private function update_survey_data_files($sid, $files)
    {
        //delete existing data files for the survey
        $this->ci->db->where('sid',$sid);
        $this->ci->db->delete('data_files');

        //import files
        foreach($files as $key=>$file)
        {
            $options=array(
                'sid'           =>$sid,
                'file_id'       =>$file['id'],
                'file_name'     =>str_replace(".NSDstat","",$file['filename']),
                'description'   =>$file['fileCont'],
                'case_count'    =>$file['caseQnty'],
                'var_count'     =>$file['varQnty']
            );

            $this->ci->db->insert('data_files',$options);
            $files[$key]['pk_id']=$this->ci->db->insert_id();
        }

        return $files;
    }


    //@years array (1999, 2000, ...)
    private function update_survey_years($sid, $years)
    {
        $this->ci->Survey_model->update_survey_years($sid, $years);
    }

    //@topics array(topic, vocab, uri)
    private function update_survey_topics($sid, $topics)
    {
        $this->ci->Survey_model->update_survey_topics($sid, $topics);
    }

    //@countries array(name, abbreviation)
    private function update_survey_countries($sid, $countries)
    {
        $this->ci->Survey_model->update_survey_countries($sid, $countries);
    }

    private function update_survey_locations($sid, $bounds=array())
    {
        //delete any existing locations
        $this->ci->db->delete('survey_locations',array('sid' => $sid));

        if(!is_array($bounds)){
            return false;
        }
        
        foreach($bounds as $bbox)
        {
            $north=$bbox['north'];
            $south=$bbox['south'];
            $east=$bbox['east'];
            $west=$bbox['west'];

            $this->ci->load->helper("gis_helper");
            $bbox_wkt=$this->ci->db->escape(bbox_to_wkt($north, $south, $east, $west));

            $this->ci->db->set('sid',$sid);
            $this->ci->db->set('location','GeomFromText('.$bbox_wkt.')',false);
            $this->ci->db->insert('survey_locations');
        }
    }

    private function encode_metadata($metadata_array)
    {
        return $this->ci->Survey_model->encode_metadata($metadata_array); //base64_encode(serialize($metadata_array));
    }


    /**
	*
	*Sanitize file name
	**/
	function sanitize_filename($name)
	{
		return preg_replace('/[^a-zA-Z0-9-_\.]/','-',$name);
	}


    private function import_variables($sid,$data_files, $variable_iterator)
    {
        if (!$variable_iterator){
            return;
        }

        //delete existing variables + variables metadata
        $this->ci->Variable_model->remove_all_variables($sid);

        $this->ci->load->library('DDI_Utils');

        // -------------------------------------------------------------------
        // Pass 1: stage all rows with their ORIGINAL (un-prefixed) vid.
        //
        // Same fan-out + prefix-on-duplicate strategy as DDI2_import (see the
        // commentary there). Mirrored here for consistency in case this older
        // import path is reused.
        // -------------------------------------------------------------------
        $staged_rows=array();

        foreach($variable_iterator as $var_obj)
        {
            if(!$var_obj){
                continue;
            }

            $raw_file_id=$var_obj->get_file_id();
            $file_id_tokens=DDI_Utils::split_file_ids($raw_file_id);

            if (empty($file_id_tokens)){
                //preserve legacy behavior: a missing @files yields a null fid
                //and a PHP notice. Skip silently here rather than insert garbage.
                continue;
            }

            $original_vid=$var_obj->get_id();
            $base_metadata=$var_obj->get_metadata_array();

            foreach($file_id_tokens as $fid_token){
                if (!isset($data_files[$fid_token])){
                    continue;
                }

                $variable_metadata=$base_metadata;
                $variable_metadata['file_id']=$fid_token;
                $variable_metadata['fid']=$fid_token;
                $variable_metadata['vid']=$original_vid; //may be rewritten in pass 2

                $staged_rows[]=array(
                    'fid'           => $data_files[$fid_token]['id'],
                    'vid'           => $original_vid, //may be rewritten in pass 2
                    'name'          => $var_obj->get_name(),
                    'labl'          => $var_obj->get_label(),
                    'qstn'          => $var_obj->get_question(),
                    'catgry'        => $var_obj->get_categories_str(),
                    'sid'           => $sid,
                    'metadata_array'=> $variable_metadata,
                    '_fid_token'    => $fid_token, //textual token used for prefix
                    '_original_vid' => $original_vid,
                );
            }
       }

        // -------------------------------------------------------------------
        // Pass 2: detect duplicate ORIGINAL vids and rewrite to FID_VID form.
        // -------------------------------------------------------------------
        $vid_counts=array();
        foreach($staged_rows as $row){
            $ov=$row['_original_vid'];
            $vid_counts[$ov]=isset($vid_counts[$ov]) ? $vid_counts[$ov]+1 : 1;
        }

        $vid_by_original_and_fid=array();
        foreach($staged_rows as $idx=>$row){
            $original_vid=$row['_original_vid'];
            if ($vid_counts[$original_vid] > 1){
                $stored_vid=DDI_Utils::prefix_vid($row['_fid_token'], $original_vid);
                $staged_rows[$idx]['vid']=$stored_vid;
                $staged_rows[$idx]['metadata_array']['vid']=$stored_vid;
                $staged_rows[$idx]['metadata_array']['vid_original']=$original_vid;
            } else {
                $stored_vid=$original_vid;
            }

            $vid_by_original_and_fid[$original_vid][$row['_fid_token']]=$stored_vid;
        }

        foreach($staged_rows as $idx=>$row){
            if (empty($row['metadata_array']['var_wgt'])) {
                continue;
            }

            $wgt_ddi=trim((string)$row['metadata_array']['var_wgt']);
            if ($wgt_ddi === '') {
                continue;
            }

            if (isset($vid_by_original_and_fid[$wgt_ddi][$row['_fid_token']])) {
                $staged_rows[$idx]['metadata_array']['var_wgt']=$vid_by_original_and_fid[$wgt_ddi][$row['_fid_token']];
            }
        }

        // -------------------------------------------------------------------
        // Pass 3: encode metadata and batch-insert.
        // -------------------------------------------------------------------
        $batch_options=array();
        $k=0;
        foreach($staged_rows as $row){
            $batch_options[$k]=array(
                'fid'      => $row['fid'],
                'vid'      => $row['vid'],
                'name'     => $row['name'],
                'labl'     => $row['labl'],
                'qstn'     => $row['qstn'],
                'catgry'   => $row['catgry'],
                'sid'      => $row['sid'],
                'metadata' => $this->encode_metadata($row['metadata_array']),
            );
            $k++;
        }

        if(count($batch_options)>0){
            $this->ci->Variable_model->batch_insert($sid,$batch_options);
        }

        return $k;//no. of variables imported (post fan-out)
    }
}
