<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * File Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		IHSN
 * @link		
 */

// ------------------------------------------------------------------------

/**
 * unix_path
 *
 * Converts the forward slashes in file path to back slashs and remove double slashes
 *
 * @access	public
 * @param	string	path to the file
 * @return	string
 */	
if ( ! function_exists('unix_path'))
{
	function unix_path($file_path)
	{
		$is_network_share=FALSE;
		
		if (substr($file_path,0,2)=='//' || substr($file_path,0,2)=='\\')
		{
			$is_network_share=TRUE;
		}
		
		$file_path=str_replace('\\','/',$file_path);
		$file_path=str_replace('//','/',$file_path);
		
		if ($is_network_share)
		{
			$file_path='/'.$file_path;
		}
		return $file_path;
	}
}


/**
 * unix_realpath
 *
 * Converts the forward slashes in file path to back slashs and remove double slashes
 *
 * @access	public
 * @param	string	file path
 * @return	string
 */	
if ( ! function_exists('unix_realpath'))
{
	function unix_realpath($file_path)
	{
		$file_path=unix_path($file_path);
		return unix_path(realpath($file_path));
	}
}


/**
*
* Return filename from file path
*
*/
if ( ! function_exists('get_filename'))
{
	function get_filename($file_path)
	{
		$file_path=str_replace('\\','/',$file_path);
		$file_path=str_replace('//','/',$file_path);
		
		$arr=explode('/',$file_path);
		
		return end($arr);
	}
}

if ( ! function_exists('is_url'))
{
	function is_url($str)
	{
		$str=trim($str);
		if (strpos($str,'http:')!==FALSE || strpos($str,'https:')!==FALSE || strpos($str,'ftp:')!==FALSE )
		{
			return TRUE;
		}
		return FALSE;
	}
}
/**
 * silent_unlink
 *
 * Deletes a file silently without throwing any warnings if the file was not found
 *
 * @access	public
 * @param	string	the language line
 * @param	string	the id of the form element
 * @return	string
 */	
if ( ! function_exists('silent_unlink'))
{
	function silent_unlink($file_path)
	{
		$error_reporting=error_reporting();
		error_reporting(E_ERROR);
		return unlink($file_path);
		error_reporting($error_reporting);
	}
}


/**
* Convert file size into human readable format
*
* @author: nak5ive at DONT-SPAM-ME dot gmail dot com
* @link: http://php.net/manual/en/function.filesize.php
*/ 
if ( ! function_exists('format_bytes'))
{
	function format_bytes($bytes, $precision = 2) 
	{ 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
	   
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
	   
		$bytes /= pow(1024, $pow); 
	   
		return round($bytes, $precision) . ' ' . $units[$pow]; 
	}
}

if ( ! function_exists('get_file_extension'))
{
	function get_file_extension($filename) 
	{ 
		$parts = explode('.',$filename);
		if (!is_array($parts) || count($parts)==1)
		{
			return FALSE;
		}
		return $parts[count($parts)-1];	   
	}
}


/**
 * 
 *  Return file extension info array 
 * 
 *  @output
 * 	- ext - file ext
 *  - link_type - download | view
 * 
 */
if ( ! function_exists('get_file_extension_info'))
{
	function get_file_extension_info($ext) 
	{ 
		$link_type='download';

		switch($ext){
			case 'html':
			case 'aspx':			
			case 'php':
				$link_type='view';
			break;
		}

		return array(
			'ext'=>$ext,
			'link_type'=>$link_type
		);

	}
}



if ( ! function_exists('get_file_icon'))
{
	function get_file_icon($ext)
	{
		switch ($ext)
		{
			case 'pdf':
				return 'images/acrobat.png';
				break;
				
			case 'xls':
			case 'xlsx':
				return 'images/page_white_excel.png';
				break;
				
			case 'doc':
			case 'docx':
				return 'images/page_white_word.png';
				break;
						
			case 'txt':
				return 'images/page_white_text.png';
				break;
				
			case 'zip':
				return 'images/page_white_compressed.png';
				
			default:
				return 'images/page_white.png';
		}
	}
}

/**
 * Remove folder and subfolders recursively
 *
 * Author: holger1 at NOSPAMzentralplan dot de
 * Link: http://www.php.net/manual/en/function.rmdir.php#98622
 *
 * Note: not tested and used
 */	
if ( ! function_exists('remove_folder'))
{
	function remove_folder($dir) 
	{ 
		   if (is_dir($dir)) 
		   { 
				 $objects = scandir($dir); 
				 foreach ($objects as $object) 
				 { 
					   if ($object != "." && $object != "..")
					   { 
						 if (filetype($dir."/".$object) == "dir") remove_folder($dir."/".$object); else unlink($dir."/".$object); 
					   } 
				 } 
				 reset($objects); 
				 rmdir($dir); 
		   } 
	 } 
}


/**
*
* Return an array of files and folders
* 
*	@path				folder path
*	@make_relative_to	Make the folder paths relative to a folder
*/	
if ( ! function_exists('get_dir_recursive'))
{
	function get_dir_recursive($path,$make_relative_to=FALSE)
	{	
		$files=array();
		$folders=array();
		
		if ($make_relative_to!==FALSE)
		{
			$make_relative_to=unix_path($make_relative_to);
		}

		$iterator = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as  $file)
		{	
			//is a folder
			if (!$file->isFile()) 
			{
				//folder path
				$folder=unix_path($file->getPathname());
				
				if ($make_relative_to!==FALSE)
				{
					$folder=str_replace($make_relative_to,"",$folder);
				}

				$folders[]=$folder;
			}
			else
			{
				//file path
				$file_path=unix_path($file->getPathname());
				
				if ($make_relative_to!==FALSE)
				{
					$file_path=str_replace($make_relative_to,"",$file_path);
				}				
				
				$files[]=$file_path;
			}
		}		
		return array('files'=>$files, 'folders'=>$folders);
	}
}	


if ( ! function_exists('get_catalog_root'))
{
	function get_catalog_root()
	{	
		$CI =& get_instance();
		$catalog_root=$CI->config->item("catalog_root");
		
		if(!$catalog_root || trim($catalog_root)==''){
			throw new Exception("CATALOG_ROOT-NOT_SET");
		}
		
		//if not fixed path, use a relative path
		if (!file_exists($catalog_root) ){
			$catalog_root=FCPATH.$catalog_root;
		}

		return $catalog_root;
	}
}



if ( ! function_exists('get_zip_archive_list'))
{
	/**
	 * 
	 * 
	 * Return an array of files and folders of a zip archive
	 * 
	 * @ignore - file or folder names or ignore
	 * 
	 */
	function get_zip_archive_list($zipfile_path,$ignore=array())
	{
		if (!class_exists('ZipArchive')){
			return false;
		}

		$zip = new ZipArchive();
		$zip->open($zipfile_path);

		if (count($ignore)==0){
			$ignore = array( 'MACOSX/', 'MACOSX/._','.DS_Store' );
		}

		$output=array();

		for( $i = 0; $i < $zip->numFiles; $i++ ){
			if (in_array(basename($zip->getNameIndex($i)), $ignore)) {
				continue;
			} else

			if(substr($zip->getNameIndex($i), 0, 9) === "__MACOSX/") {
				continue;
			} else {
				$stat = $zip->statIndex($i);
			}

			if ($stat['size']>0){
				$output[$stat['name']]=$stat;
			}
		}

		return $output;
	}
	
}


/**
 * validate_file_path
 *
 * Validates and sanitizes file paths to prevent directory traversal attacks
 *
 * @access	public
 * @param	string	file path to validate
 * @param	string	database id for path validation
 * @param	string	table id for path validation
 * @return	string	validated and sanitized file path
 * @throws	Exception	if path is invalid
 */	
if ( ! function_exists('validate_file_path'))
{
	function validate_file_path($file_path, $db_id, $table_id)
	{
		// Remove any directory traversal attempts
		$file_path = str_replace(['../', '..\\', '..'], '', $file_path);
		
		// Ensure path starts with expected pattern (case-insensitive)
		$expected_pattern = strtolower($db_id) . '/' . strtolower($table_id) . '/';
		$file_path_lower = strtolower($file_path);
		if (strpos($file_path_lower, $expected_pattern) !== 0) {
			throw new Exception("Invalid file path - must be within authorized directory");
		}
		
		// Ensure file has .csv extension
		if (!preg_match('/\.csv$/i', $file_path)) {
			throw new Exception("Invalid file type - only CSV files allowed");
		}
		
		// Ensure path contains only safe characters (alphanumeric, underscore, forward slash, hyphen, dot)
		if (!preg_match('/^[a-zA-Z0-9_\/\-\.]+$/', $file_path)) {
			throw new Exception("Invalid characters in file path");
		}
		
		// Additional validation: ensure no double slashes or suspicious patterns
		if (strpos($file_path, '//') !== false || 
			strpos($file_path, '\\') !== false ||
			strpos($file_path, '..') !== false) {
			throw new Exception("Invalid path format detected");
		}
		
		// Ensure the path is not too long (prevent buffer overflow attacks)
		if (strlen($file_path) > 255) {
			throw new Exception("File path too long");
		}
		
		return $file_path;
	}
}


/**
 * get_file_mime_type
 *
 * Get mime type of a file using multiple detection methods
 *
 * @access	public
 * @param	string	file path
 * @return	string|false - mime type or false
 */
if ( ! function_exists('get_file_mime_type'))
{
	function get_file_mime_type($file_path)
	{
		if (!file_exists($file_path)) {
			return false;
		}

		// Try using finfo (preferred method)
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$mime_type = finfo_file($finfo, $file_path);
				finfo_close($finfo);
				if ($mime_type) {
					return $mime_type;
				}
			}
		}

		// Fallback to mime_content_type
		if (function_exists('mime_content_type')) {
			$mime_type = @mime_content_type($file_path);
			if ($mime_type) {
				return $mime_type;
			}
		}

		// Fallback to extension-based detection
		return get_mime_type_from_extension($file_path);
	}
}


/**
 * get_mime_type_from_extension
 *
 * Get mime type from file extension using CodeIgniter's mimes configuration
 *
 * @access	public
 * @param	string	file path or filename
 * @return	string - mime type
 */
if ( ! function_exists('get_mime_type_from_extension'))
{
	function get_mime_type_from_extension($file_path)
	{
		$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		
		// Load CodeIgniter mimes configuration using the standard get_mimes() function
		$mime_types = get_mimes();
		
		// Check if extension exists in mimes config
		if (isset($mime_types[$extension])) {
			// If it's an array, return the first MIME type
			if (is_array($mime_types[$extension])) {
				return $mime_types[$extension][0];
			}
			// If it's a string, return it directly
			return $mime_types[$extension];
		}

		return 'application/octet-stream';
	}
}

// ------------------------------------------------------------------------
/* End of file MY_file_helper.php */
/* Location: ./system/helpers/MY_file_helper.php */