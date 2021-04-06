<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * Embed
 * 
 */
class Embed_model extends CI_Model {


    private $storage_path='files/embed';
    private $temp_path='datafiles/tmp';

    private $fields=array(
        'uuid',
        'title',
        'description',
        'storage_path',
        'published',
        'created',
        'changed',
        'created_by',
        'changed_by'

    );


    public function __construct()
    {        
        parent::__construct();        
        $this->load->library('upload');

        $path=$this->config->item("embed_path");

        if(!empty($path)){
            $this->storage_path=$path;
        }

        $this->temp_path=$this->config->item("catalog_root").'/tmp';
    }



    function select_all()
    {
        $this->db->select("id,uuid,title,description,storage_path,published, changed,created");
        return $this->db->get("embed")->result_array();
    }

    function get_total_counts()
    {
        $result= $this->db->query("select count(*) as total from embed")->row_array();
        return $result['total'];
    }


    function create_project($uuid,$options)
    {
        $options['uuid']=$uuid;
        $this->validate($options);

        $zip_file=$this->upload_file($tmp_path=$this->temp_path,$file_field='file');        

        //create folder for project
        $project_folder=$this->storage_path.'/'.md5($uuid);
        $this->create_folder($project_folder);

        //unzip files to project folder
        $this->unzip_project_files($project_folder,$zip_file);

        $options['storage_path']=md5($uuid);

        
        if ($this->find($uuid)){
            $this->update($uuid,$options);
        }else{
            $this->insert($uuid,$options);
        }
        
        return array(
            'folder'=>$options['storage_path'],
            'uuid'=>$uuid,
            'link'=>site_url('embed/'.$uuid)
        );
    }

    function unzip_project_files($project_folder,$zip_file)
    {
        $allowed_file_types=explode(",",'html,js,css,ttf,json,png,jpg,gif');

        $excluded=array();
        $included=array();

        $zip_files_list= $this->get_zip_list($zip_file);

        //validate file types
        foreach($zip_files_list as $file){
            $file_ext=pathinfo($file,PATHINFO_EXTENSION);
            if(!in_array($file_ext, $allowed_file_types)){
                $excluded[]=$file;
            }else{
                $included[]=$file;
            }
        }

        $zip = new ZipArchive;
        $res = $zip->open($zip_file);
        if ($res === TRUE) {
          $zip->extractTo($project_folder,$included);
          $zip->close();
        } else {
          throw new Exception("Unzip failed: ".$zip_file);
        }
    }

    function get_zip_list($zip_file)
    {

        $za = new ZipArchive(); 
        $za->open($zip_file);

        $output=array();

        for( $i = 0; $i < $za->numFiles; $i++ ){ 
            $stat = $za->statIndex( $i ); 
            $output[]=$stat['name'];
        }

        return $output;
    }


    function upload_file($tmp_path,$file_field='file')
	{
		//upload class configurations
		$config['upload_path'] = $tmp_path;
		$config['overwrite'] = TRUE;
		$config['encrypt_name']=FALSE;
		$config['allowed_types'] = 'zip';

		$this->upload->initialize($config);

		//process uploaded file
		$upload_result=$this->upload->do_upload($file_field);

		if(!$upload_result){
			$error = $this->upload->display_errors();
			throw new Exception("FILE_UPLOAD::".$error);
		}
		
		$upload = $this->upload->data();
		return $upload['full_path'];
	}


    function create_folder($path)
    {       
        if (file_exists($path)){
            return true;
        }

        if (!mkdir($path,0777,true)){
            throw new Exception("error_creating_folder:: ".$path);
        }
    }



    function get_full_path($file_obj)
    {
        $path=unix_path($this->storage_path.$file_obj['file_path'].'/'.$file_obj['file_name']);
        return $path;
    }
    

    private function insert($uuid,$options)
    {
        $valid_fields=$this->fields;
        $options['changed']=date("U");
        $options['created']=date("U");
        
        $data=array();

        foreach($options as $key=>$value){
            if (in_array($key,$valid_fields)){
                $data[$key]=$value;
            }
        }

        $data['uuid']=$uuid;
        $data['options']=json_encode($data);
        
        $result=$this->db->insert('embed', $data);

        if ($result===false){
            throw new MY_Exception($this->db->error());
        }
            
        return $this->db->insert_id();
    }


    private function update($uuid,$options)
    {
        $valid_fields=$this->fields;
        $options['changed']=date("U");        
        
        $data=array();

        foreach($options as $key=>$value){
            if (in_array($key,$valid_fields)){
                $data[$key]=$value;
            }
        }

        $data['uuid']=$uuid;
        $data['options']=json_encode($data);
        
        $this->db->where("uuid",$uuid);
        $result=$this->db->update('embed', $data);

        if ($result===false){
            throw new MY_Exception($this->db->error());
        }
            
        return $result;
    }


    /**
     * 
     * Find by UUID
     * 
     */
    function find($uuid)
    {
        $this->db->select("*");
        $this->db->where('uuid',$uuid);
        $result=$this->db->get("embed")->row_array();

        if($result){
            $result['full_path']=$this->storage_path.'/'.$result['storage_path'];
            $result['related_studies']=$this->get_attached_studies($uuid);
        }

        return $result;
    }



    function delete($uuid)
    {
        $row=$this->find($uuid);

        if(!$row){
            throw new Exception("RECORD_NOT_FOUND");
        }

        //delete from db
        $this->db->where("uuid",$uuid);
        $this->db->delete('embed');

        $this->db->where("embed_uuid",$uuid);
        $this->db->delete('survey_embeds');

        $project_path=$this->storage_path.'/'.$row['storage_path'];
        
        //$this->delete_file($file_path);
        $this->delete_project_folder($project_path);
        
        return true;
    }


    private function delete_project_folder($project_folder_name)
    {
		if($this->storage_path=='' || $project_folder_name==''){
			return false;
		}

		if (!strpos($project_folder_name, $this->storage_path) === 0 ) {
			return false;
		}
		
		remove_folder($project_folder_name);

		return true;
    }



    function validate($options)
	{				
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);

        $this->form_validation->set_rules('title', 'Title', 'required|xss_clean|trim|max_length[255]');	
        $this->form_validation->set_rules('uuid', 'UUID', 'required|xss_clean|alpha_dash|trim|max_length[100]');	
        
        //idno validation rule
        /*$this->form_validation->set_rules(
            'uuid', 
            'UUID',
            array(
                "required",
                "alpha_dash",
                "max_length[100]",
                "xss_clean",
                array('validate_uuid',array($this, 'valid_uuid')),				
            )		
        );*/
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}

    //validate uuid
	public function validate_uuid($uuid)
	{	
		$id_=null;
		if(array_key_exists('id',$this->form_validation->validation_data)){
			$id_=$this->form_validation->validation_data['id'];
		}

		//check if uuid already exists
		$id=$this->find($uuid);	

		if (is_numeric($id) && $id!=$id_ ) {
			$this->form_validation->set_message(__FUNCTION__, 'The UUID should be unique.' );
			return false;
		}
		return true;
	}



    function attach_to_study($study_id,$embed_uuid)
    {
        $options=array(
            'sid'=>$study_id,
            'embed_uuid'=>$embed_uuid
        );

        $this->remove_from_study($study_id,$embed_uuid);
        return $this->db->insert("survey_embeds",$options);
    }

    function remove_from_study($study_id,$embed_uuid)
    {
        $this->db->where("sid",$study_id);
        $this->db->where("embed_uuid",$embed_uuid);
        return $this->db->delete("survey_embeds");
    }

    function embeds_by_study($sid)
    {
        $this->db->where("sid",$sid);
        $this->db->join('survey_embeds', 'embed.uuid= survey_embeds.embed_uuid','inner');
        $query=$this->db->get("embed");

        if ($query){
            return $query->result_array();
        }
    }

    function get_attached_studies($uuid)
    {
        $this->db->select("surveys.id as sid,surveys.idno");
        $this->db->join('surveys', 'survey_embeds.sid= surveys.id','inner');
        $this->db->where("survey_embeds.embed_uuid",$uuid);
        $query=$this->db->get("survey_embeds");

        if ($query){
            return $query->result_array();
        }
    }
    

}