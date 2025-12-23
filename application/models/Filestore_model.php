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
     * Search files with pagination, filtering, and sorting
     * 
     * @param int $per_page - Records per page
     * @param int $offset - Offset for pagination
     * @param array $filter - Filter options (keywords, field, filter_type, filter_images)
     * @param string $sort_by - Field to sort by (file_name, changed, file_ext)
     * @param string $sort_order - Sort order (asc, desc)
     * @return array - Array of file records with additional info
     */
    function search($per_page=15, $offset=0, $filter=NULL, $sort_by='changed', $sort_order='desc')
    {
        $this->db->select("*");
        
        // Apply filters
        if ($filter && is_array($filter)) {
            // Search by filename
            if (isset($filter['keywords']) && !empty($filter['keywords'])) {
                $field = isset($filter['field']) ? $filter['field'] : 'file_name';
                if ($field === 'file_name' || $field === 'all') {
                    $this->db->like('file_name', $filter['keywords']);
                }
            }
            
            // Filter by file type
            if (isset($filter['filter_type']) && !empty($filter['filter_type'])) {
                $this->db->where('file_ext', $filter['filter_type']);
            }
            
            // Filter images only
            if (isset($filter['filter_images']) && $filter['filter_images'] == true) {
                $this->db->where('is_image', 1);
            }
        }
        
        // Apply sorting
        $valid_sort_fields = array('file_name', 'changed', 'file_ext', 'is_image');
        if (in_array($sort_by, $valid_sort_fields)) {
            $this->db->order_by($sort_by, strtoupper($sort_order));
        } else {
            $this->db->order_by('changed', 'DESC');
        }
        
        // Apply pagination
        if ($per_page > 0) {
            $this->db->limit($per_page, $offset);
        }
        
        $files = $this->db->get("filestore")->result_array();
        
        // Add file size and other metadata
        foreach ($files as &$file) {
            $file_path = $this->get_full_path($file);
            if (file_exists($file_path)) {
                $file['file_size'] = filesize($file_path);
                $file['file_exists'] = true;
            } else {
                $file['file_size'] = 0;
                $file['file_exists'] = false;
            }
        }
        
        return $files;
    }

    /**
     * Get total count of files matching filters
     * 
     * @param array $filter - Filter options
     * @return int - Total count
     */
    function search_count($filter=NULL)
    {
        // Apply filters
        if ($filter && is_array($filter)) {
            // Search by filename
            if (isset($filter['keywords']) && !empty($filter['keywords'])) {
                $field = isset($filter['field']) ? $filter['field'] : 'file_name';
                if ($field === 'file_name' || $field === 'all') {
                    $this->db->like('file_name', $filter['keywords']);
                }
            }
            
            // Filter by file type
            if (isset($filter['filter_type']) && !empty($filter['filter_type'])) {
                $this->db->where('file_ext', $filter['filter_type']);
            }
            
            // Filter images only
            if (isset($filter['filter_images']) && $filter['filter_images'] == true) {
                $this->db->where('is_image', 1);
            }
        }
        
        $this->db->from('filestore');
        return $this->db->count_all_results();
    }

    /**
     * Get file info with size
     * 
     * @param string $filename - File name
     * @return array - File record with size and metadata
     */
    function get_file_with_size($filename)
    {
        $file = $this->find($filename);
        if ($file) {
            $file_path = $this->get_full_path($file);
            if (file_exists($file_path)) {
                $file['file_size'] = filesize($file_path);
                $file['file_exists'] = true;
            } else {
                $file['file_size'] = 0;
                $file['file_exists'] = false;
            }
        }
        return $file;
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

        $original_file_name=$_FILES[$file_field]['name'];
        
        // Sanitize filename - keep only alphanumeric, dashes, and underscores
        $file_name = $this->sanitize_filename($original_file_name);

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

            //create the folder (will also ensure base directory exists)
            $this->create_folder($upload_path);
        }
        
		$config['upload_path'] = $upload_path;
		$config['overwrite'] = $overwrite;
        $config['encrypt_name']=false;
        //$config['remove_spaces']=false;
		
		// Set sanitized filename for upload
		$config['file_name'] = $file_name;
		
		// Get allowed file types from config
		$allowed_types = $this->config->item("allowed_resource_types");
		if (empty($allowed_types)) {
			// Fallback to default if config not set
			$allowed_types = 'jpg|jpeg|bmp|gif|png|pdf|txt|csv|xls|xlsx|ppt|pptx|doc|docx|zip';
		} else {
			// Convert comma-separated list to pipe-separated (CodeIgniter Upload format)
			$allowed_types = str_replace(',', '|', $allowed_types);
		}
		$config['allowed_types'] = $allowed_types;
        
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

        // Get file extension from sanitized filename
        $file_info = new SplFileInfo($upload_data['file_name']);
        $file_ext = $file_info->getExtension();

        //add to db
        $options=array(
            'file_name'=>$upload_data['file_name'], // Already sanitized
            'file_path'=>$upload_path_rel,
            'is_image'=>$upload_data['is_image'],
            'file_ext'=>$file_ext
        );
        $this->insert($options);

        return $upload_data;		
    }
    


    /**
     * Generate folder path based on year (YYYY)
     * Simple single-level structure: /2024/, /2025/, etc.
     * 
     * @param int $id - Unix timestamp
     * @return string - Relative folder path (e.g., "/2024")
     */
    function generate_folder_path($id) 
    {
        $year = date('Y', $id);
        return '/' . $year;
    }

    function create_folder($path)
    {
        // Check if directory already exists
        if (is_dir($path)) {
            return true;
        }
        
        // Resolve to absolute path if relative
        if (!file_exists($path)) {
            // Check if path is relative (doesn't start with /)
            if (substr($path, 0, 1) !== '/' && strpos($path, ':') === false) {
                // Relative path - resolve from FCPATH
                $path = FCPATH . $path;
            }
        }
        
        // Ensure base storage directory exists first
        $base_path = $this->storage_path;
        if (substr($base_path, 0, 1) !== '/' && strpos($base_path, ':') === false) {
            // Relative path - resolve from FCPATH
            $base_path = FCPATH . $base_path;
        }
        
        if (!is_dir($base_path)) {
            if (!mkdir($base_path, 0755, true)) {
                throw new Exception("error_creating_base_folder:: " . $base_path);
            }
        }
        
        // Create the full path
        if (!mkdir($path, 0755, true)) {
            throw new Exception("error_creating_folder:: " . $path);
        }
        
        return true;
    }


    /**
     * 
     * Sanitize filename - keep only alphanumeric, dashes, and underscores
     * Preserves file extension
     * 
     * @param string $file_name - Original filename
     * @return string - Sanitized filename
     */
    function sanitize_filename($file_name)
    {
        // Get file extension
        $file_info = new SplFileInfo($file_name);
        $extension = $file_info->getExtension();
        $basename = $file_info->getBasename('.' . $extension);
        
        // Sanitize basename: keep only alphanumeric, dashes, and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        
        // If basename becomes empty after sanitization, create random name
        if (empty($sanitized)) {
            $sanitized = 'file-'.random_string('alnum', 10);
        }
        
        // Reconstruct filename with extension
        if (!empty($extension)) {
            return $sanitized . '.' . strtolower($extension);
        }
        
        return $sanitized;
    }

    /**
     * 
     * Replace spaces in filename with underscores (legacy method, kept for backward compatibility)
     * 
     */
    function file_remove_spaces($file_name)
    {
        $file_parts=explode(" ",$file_name);
        $file_parts=array_filter($file_parts);
        $file_name=implode("_",$file_parts);
        return $file_name;
    }



    function photo($filename)
    {   
        $filename=$this->file_remove_spaces($filename);
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
        $filename=$this->file_remove_spaces($filename);
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
        $filename=$this->file_remove_spaces($filename);
        $this->db->select("*");
        $this->db->where('file_name',$filename);
        return $this->db->get("filestore")->row_array();
    }



    function delete($filename)
    {
        $filename=$this->file_remove_spaces($filename);
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