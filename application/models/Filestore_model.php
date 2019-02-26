<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * File storage
 * 
 */
class Filestore_model extends CI_Model {


    private $storage_path='files/public';

    private $fields=array(
        'file_name',
        'file_path',
        'file_ext',
        'is_image',
        'changed'
    );


    public function __construct()
    {        
        parent::__construct();        
        $this->load->library('upload');

        $path=$this->config->item("filestore_path");

        if(empty($path)){
            throw new Exception("FILESTORE_STORAGE_PATH_NOT_SET");
        }

        $this->storage_path=$path;
    }



    function select_all()
    {
        $this->db->select("file_name");
        $this->db->limit(1000);
        return $this->db->get("filestore")->result_array();
    }

    function get_file_counts()
    {
        $result= $this->db->query("select count(*) as total from filestore")->row_array();
        return $result['total'];
    }


    /**
	 * 
	 * 
	 * Upload an RDF and return path to the file
	 * 
	 * 
	 * @file_field - FILE field name
	 * 
	 */
	function upload($file_field='file',$overwrite=false)
	{   
        if(!isset($_FILES['file'])){
            throw new Exception("FILE NOT PROVIDED");
        }

        $file_name=$_FILES[$file_field]['name'];

        //check filename already exists
        $file=$this->find($file_name);

        if($file){
            if  ($overwrite===true){
                $upload_path_rel=$file['file_path'];
                $upload_path=unix_path($this->storage_path.$upload_path_rel);                
            }
            else{//overwrite = false
                throw new Exception("FILE_ALREADY_EXISTS");
            }
        }
        else{
            //relative path
            $upload_path_rel=$this->generate_folder_path(date("U"));

            //full path for storing the file
            $upload_path=unix_path($this->storage_path.$upload_path_rel);

            //create the folder
            $this->create_folder($upload_path);
        }

		$config['upload_path'] = $upload_path;
		$config['overwrite'] = $overwrite;
		$config['encrypt_name']=false;
		$config['allowed_types'] = 'jpg|jpeg|bmp|gif|png|pdf|txt|csv|xls|xlsx';
        
        $this->upload->initialize($config);
		
		$upload_result=$this->upload->do_upload($file_field);

		if(!$upload_result){
            $error = $this->upload->display_errors();            
			throw new Exception("UPLOAD_FAILED::".$upload_path. ' - error:: '.$error);
        }

        $upload_data = $this->upload->data();
        
        if($file){
            return $upload_data;
        }

        $file_info = new SplFileInfo($file_name);
        

        //add to db
        $options=array(
            'file_name'=>$file_name,
            'file_path'=>$upload_path_rel,
            'is_image'=>$upload_data['is_image'],
            'file_ext'=>$file_info->getExtension()
        );
        $this->insert($options);

        return $upload_data;		
    }
    


    //source: https://stackoverflow.com/questions/446358/storing-a-large-number-of-images
    function generate_folder_path($id) 
    {
		$level1 = ($id / 100000000) % 100000000;
		$level2 = (($id - $level1 * 100000000) / 100000) % 100000;
		$level3 = (($id - ($level1 * 100000000) - ($level2 * 100000)) / 100) % 1000;
		$file   = $id - (($level1 * 100000000) + ($level2 * 100000) + ($level3 * 100));
	
		$path= '/' . sprintf("%03d", $level1)
			 . '/' . sprintf("%03d", $level2)
			 . '/' . sprintf("%03d", $level3)
             . '/' . $file;
                 
        return $path;
    }

    function create_folder($path)
    {        
        if (!mkdir($path,0777,true)){
            throw new Exception("error_creating_folder:: ".$path);
        }
    }



    function photo($filename)
    {        
        $file=$this->find($filename);

        if(!$file){
            throw new Exception("FILE-INFO_NOT_FOUND");
        }

        if($file['is_image']!=1){
            throw new Exception("NOT_AN_IMAGE");
        }

        $file_path=$this->get_full_path($file);

        if(!file_exists($file_path)){
            throw new Exception("FILE_NOT_FOUND");
        }

        $this->load->helper('download');
        force_download_inline($file_path,null,true);
    }



    function download($filename,$disposition='attachment')
    {
        $file=$this->find($filename);

        if(!$file){
            throw new Exception("FILE-INFO_NOT_FOUND");
        }

        $file_path=$this->get_full_path($file);

        if(!file_exists($file_path)){
            throw new Exception("FILE_NOT_FOUND");
        }

        $this->load->helper('download');

        if($disposition=='inline'){
            return force_download_inline($file_path,null,true);
        }

        force_download($file_path,null);
    }



    function get_full_path($file_obj)
    {
        $path=unix_path($this->storage_path.$file_obj['file_path'].'/'.$file_obj['file_name']);
        return $path;
    }
    

    private function insert($options)
    {
        $valid_fields=$this->fields;
        $options['changed']=date("U");
        
        $data=array();

        foreach($options as $key=>$value){
            if (in_array($key,$valid_fields)){
                $data[$key]=$value;
            }
        }
        
        $result=$this->db->insert('filestore', $data);

        if ($result===false){
            throw new MY_Exception($this->db->error());
        }
            
        return $this->db->insert_id();
    }


    /**
     * 
     * Find photo by name
     * 
     */
    function find($filename)
    {
        $this->db->select("*");
        $this->db->where('file_name',$filename);
        return $this->db->get("filestore")->row_array();
    }



    function delete($filename)
    {
        $file=$this->find($filename);

        if(!$file){
            throw new Exception("FILE-INFO_NOT_FOUND");
        }

        //delete from db
        $this->db->where("file_name",$filename);
        $this->db->delete('filestore');

        //file path
        $file_path=$this->storage_path.$file['file_path'].'/'.$file['file_name'];
        
        //delete file
        $this->delete_file($file_path);

        return true;
    }


    private function delete_file($file_path)
    {
        if(!file_exists($file_path)){
            throw new Exception("FILE_NOT_FOUND:: ". $file_path);
        }

        unlink($file_path);
    }

    

}