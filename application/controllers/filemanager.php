<?php

/**
* Simple File manager
*
*
* 
* based on: http://codeigniter.com/wiki/simple_file_browser/
*/
class Filemanager extends MY_Controller {
	
	//allowed paths
    var $roots = array(
        'app' => 'application',
		'datasets' => '../nada2.1_data'//'../nada2.1_data/default/057e069438c3130490547d24687197d7/',
        );
    
		
    function __construct()
    {
        parent::MY_Controller();
        $this->template->set_template('blank');
		$this->load->helper('file');
		
		//set datasets folder path from db
		$this->roots['datasets']=$this->config->item('catalog_root');
		//$this->output->enable_profiler(TRUE);
    }
    
    function _remap()
    {
        $segment_array = $this->uri->segment_array();
        
        // first and second segments are our controller and the 'virtual root'
        $controller = array_shift( $segment_array );
        $virtual_root = array_shift( $segment_array );
        
        if( empty( $this->roots )) exit( 'no root defined' );
        
        // let's check if a virtual root is choosen
        // if this controller is the default controller, first segment is 'index'
        if ( $controller == 'index' OR $virtual_root == '' ) {
		 	show_404();
		}
        
        // let's check if a virtual root matches
        if ( ! array_key_exists( $virtual_root, $this->roots )) {
				echo 'not found';exit;
			show_404();
        }
		
        // build absolute path
        $path_in_url = '';
        foreach ( $segment_array as $segment ) $path_in_url.= $segment.'/';
        $absolute_path = $this->roots[ $virtual_root ].'/'.$path_in_url;
        $absolute_path = rtrim( $absolute_path ,'/' );
        
        // is it a directory or a file ?
        if ( is_dir( $absolute_path ))
        {
			//process create folder
			$this->_create_document($absolute_path);
			$this->_delete_files($absolute_path);
			$this->_upload_files($absolute_path);
			            
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
                            $tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));	
							$tmp['name']=$file;
							$dirs[]=$tmp;
                        }
                        else
                        {
                            $tmp=get_file_info($absolute_path.'/'.$file, array('name','date','size','fileperms'));
							$tmp['name']=$file;
							$tmp['size']=$this->_format_bytes($tmp['size']);
							$tmp['fileperms']=symbolic_permissions($tmp['fileperms']);
							$files[]=$tmp;
                        }
                    }
                }
                closedir( $handle );
                sort( $dirs );
                sort( $files );
            }
            // parent directory
            // here to ensure it's available and the first in the array
            if ( $path_in_url != '' )
                array_unshift ( $dirs, array( 'name' => '..' ));
            
            // send the view
            $data = array(
                'controller' => $controller,
                'virtual_root' => $virtual_root,
                'path_in_url' => $path_in_url,
                'dirs' => $dirs,
                'files' => $files,
                );
				
            $contents=$this->load->view( 'filemanager', $data,TRUE );
			$this->template->write('title', 'File manager',true);
			$this->template->write('content', $contents,true);		
		  	$this->template->render();
        }
        else
        {
            // it's not a directory, but is it a file ?
            if ( is_file( $absolute_path ))
            {
                // let's serve the file
                header ('Cache-Control: no-store, no-cache, must-revalidate');
                header ('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header ('Pragma: no-cache');
                 
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                // header('Content-Length: ' . filesize( ".$absolute_path." ));  // Modified
                header('Content-Length: ' . filesize( $absolute_path ));
                header('Content-Disposition: attachment; filename=' . basename( $absolute_path ));
                
                @readfile( $absolute_path );
            }
            else
            {
              echo 'else'.$absolute_path ; // show_404();
            }
        }
    }


	function _create_document($absolute_path)
	{
		if (!$this->input->post('create') )
		{
			return;
		}
	
		$name=$this->input->post('name');
		$type=$this->input->post('type');
			
		if ($type=='dir')
		{
			//Create folder
			$rs = @mkdir( $absolute_path.'/'.$name, 0777 ); 
			if( !$rs ) {
				$this->errors[]='Failed to create the folder';
			} 
		}
	}

	function _upload_files($absolute_path)
	{
		if (!$this->input->post('upload') )
		{
			return;
		}
		
		$files=$_FILES;
		
		if (!$files)
		{
			return;
		}
		
		foreach ($files["file"]["error"] as $key => $error) 
		{
		    if ($error == UPLOAD_ERR_OK) 
			{
		        $tmp_name = $_FILES["file"]["tmp_name"][$key];
        		$name = $_FILES["file"]["name"][$key];
				if (!file_exists("$absolute_path/$name"))
				{
		        	move_uploaded_file($tmp_name, "$absolute_path/$name");
				}
				else
				{
					$this->errors[]='File already exists: '. $name;
				}	
    		}
		}	
	}

	
	function _delete_files($absolute_path)
	{
		if (!$this->input->post('delete') )
		{
			return;
		}
		
		$filenames=$this->input->post('filename');
		
		if ($filenames)
		{
			foreach($filenames as $dir)
			{
				$dir=trim($dir);
				
				if ($dir!='..' && $dir!='.' && strpos($dir,'/')===FALSE && strpos($dir,'\\')===FALSE )
				{
					$this->delete_folder($absolute_path.'/'.$dir);
				}	
			}
		}
	}
	
	/**
	* Removes a folder, subfolder and all files
	*
	* @author: asn at asn24 dot dk
	* @link: http://php.net/manual/en/function.rmdir.php
	*/
	function delete_folder($dir) 
	{ 
		if (!file_exists($dir)) return true; 
		if (!is_dir($dir) || is_link($dir)) return unlink($dir); 
			foreach (scandir($dir) as $item) { 
				if ($item == '.' || $item == '..') continue; 
				if (!$this->delete_folder($dir . "/" . $item)) { 
					chmod($dir . "/" . $item, 0777); 
					if (!$this->delete_folder($dir . "/" . $item)) return false; 
				}; 
			} 
			return rmdir($dir); 
    } 
	/**
	* Convert file size into human readable format
	*
	* @author: nak5ive at DONT-SPAM-ME dot gmail dot com
	* @link: http://php.net/manual/en/function.filesize.php
	*/ 
	function _format_bytes($bytes, $precision = 2) 
	{ 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
	   
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
	   
		$bytes /= pow(1024, $pow); 
	   
		return round($bytes, $precision) . ' ' . $units[$pow]; 
	} 
}