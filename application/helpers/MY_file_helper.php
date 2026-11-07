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
		// Must start with the scheme. Matching "http:" 
		return preg_match('#^(https?|ftp)://#i', trim((string) $str)) === 1;
	}
}

/**
 * Lexically sanitize an untrusted relative path (no resolve / existence check).
 *
 * @param string $relative
 * @return string|false Clean relative path (no leading slash), or false
 */
if ( ! function_exists('sanitize_relative_path'))
{
	function sanitize_relative_path($relative)
	{
		$relative = trim((string) $relative);
		if ($relative === '' || strpos($relative, "\0") !== false) {
			return false;
		}
		if (function_exists('is_url') && is_url($relative)) {
			return false;
		}
		if ($relative[0] === '/' || $relative[0] === '\\' || preg_match('/^[A-Za-z]:/', $relative) === 1) {
			return false;
		}

		$rel = ltrim(str_replace('\\', '/', $relative), '/');
		$parts = array();
		foreach (explode('/', $rel) as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}
			if ($segment === '..') {
				return false;
			}
			$parts[] = $segment;
		}
		if (empty($parts)) {
			return false;
		}

		return implode('/', $parts);
	}
}

/**
 * Absolute path under $root for a target that may not exist yet (uploads / mkdir).
 * Verifies the deepest existing ancestor stays inside $root.
 *
 * @param string $root
 * @param string $relative
 * @return string|false Absolute unix path (may not exist yet)
 */
if ( ! function_exists('build_path_under_directory'))
{
	function build_path_under_directory($root, $relative)
	{
		$rel = sanitize_relative_path($relative);
		if ($rel === false) {
			return false;
		}

		$root = unix_path((string) $root);
		$root_real = realpath($root);
		if ($root_real === false || ! is_dir($root_real)) {
			return false;
		}
		$root_real = rtrim(unix_path($root_real), '/');

		$candidate = unix_path($root_real . '/' . $rel);
		$probe = $candidate;
		// Stop on dangling symlinks too (file_exists is false for them; climbing past would bypass).
		while (! file_exists($probe) && ! is_link($probe) && $probe !== dirname($probe)) {
			$probe = dirname($probe);
		}
		$probe_real = realpath($probe);
		if ($probe_real === false) {
			return false;
		}
		$probe_real = unix_path($probe_real);
		if ($probe_real !== $root_real && strpos($probe_real, $root_real . '/') !== 0) {
			return false;
		}

		return $candidate;
	}
}

/**
 * Resolve a relative file path under a trusted root directory.
 * Do not trust DB/user values: rejects absolute paths, drive letters, null bytes, and ".." segments.
 * When the target exists, returns a realpath that is verified to stay under $root.
 *
 * @param string $root     Survey/catalog folder (trusted)
 * @param string $relative Path relative to $root (untrusted)
 * @return string|false    Canonical absolute path, or false
 */
if ( ! function_exists('resolve_under_directory'))
{
	function resolve_under_directory($root, $relative)
	{
		$rel = sanitize_relative_path($relative);
		if ($rel === false) {
			return false;
		}

		$root = unix_path((string) $root);
		$root_real = realpath($root);
		if ($root_real === false || ! is_dir($root_real)) {
			return false;
		}
		$root_real = rtrim(unix_path($root_real), '/');

		$candidate = unix_path($root_real . '/' . $rel);
		$full = realpath($candidate);
		if ($full === false) {
			return false;
		}
		$full = unix_path($full);

		if ($full === $root_real) {
			return false; // directory itself is not a downloadable resource file
		}

		$prefix = $root_real . '/';
		if (strpos($full, $prefix) !== 0) {
			return false;
		}

		return $full;
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

		$catalog_root = unix_path($catalog_root);
		$candidates = array();

		// Prefer FCPATH for relative roots — Apache CWD is unreliable (especially for ../ paths).
		if ($catalog_root[0] !== '/' && ! preg_match('/^[A-Za-z]:/', $catalog_root)) {
			$candidates[] = unix_path(FCPATH . $catalog_root);
		}
		$candidates[] = $catalog_root;

		foreach ($candidates as $candidate) {
			$resolved = realpath($candidate);
			if ($resolved !== false && is_dir($resolved)) {
				return unix_path($resolved);
			}
		}

		return $catalog_root;
	}
}


/**
 * Resolve a path under catalog_root to an absolute, canonical path.
 *
 * @param string $path absolute or relative to catalog_root
 * @return string|false
 */
if ( ! function_exists('resolve_catalog_path'))
{
	function resolve_catalog_path($path)
	{
		$path = unix_path((string) $path);
		if ($path === '') {
			return false;
		}

		$resolved = realpath($path);
		if ($resolved !== false) {
			return unix_path($resolved);
		}

		$root = get_catalog_root();
		$root = unix_path($root);
		if ($path[0] === '/' || preg_match('/^[A-Za-z]:/', $path)) {
			return false;
		}

		$resolved = realpath($root . '/' . ltrim($path, '/'));
		if ($resolved !== false) {
			return unix_path($resolved);
		}

		return false;
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