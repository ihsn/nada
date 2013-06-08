<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2006 - 2012 EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 2.0
 * @filesource	
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter DB Caching Class 
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Core
 * @author		IHSN
 * @link		
 */

class CI_Cache_db extends CI_Driver {

	protected $cache_table;
	protected $CI;
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		
		//$CI->load->helper('file');
		
		$cache_table = $this->CI->config->item('cache_table');
	
		$this->cache_table = ($cache_table == '') ? 'cache' : $cache_table;
	}

	// ------------------------------------------------------------------------

	
	/**
	 * Fetch from cache
	 *
	 * @param 	mixed		unique key id
	 * @return 	mixed		data on success/false on failure
	 */
	public function get($id)
	{
		$this->CI->db->select('*');
		$this->CI->db->where('uid',$id);
		$row=$this->CI->db->get($this->cache_table)->row_array();
		
		if (!$row)
		{
			return FALSE;
		}
		
		$data = $row['data'];
		$data = unserialize($data);
		
		if (time() >  $row['expiry'])
		{
			$this->delete($id);
			return FALSE;
		}
		
		return $data;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save into cache
	 *
	 * @param 	string		unique key
	 * @param 	mixed		data to store
	 * @param 	int			length of time (in seconds) the cache is valid 
	 *						- Default is 60 seconds - 3600=1 hour
	 * @return 	boolean		true on success/false on failure
	 */
	public function save($id, $data, $ttl= 3600)
	{		
		$options = array(
				'uid'		=> $id,
				'created'	=> time(),
				'expiry'	=> time() + $ttl,			
				'data'		=> serialize($data)
			);

		//remove any existing
		$this->delete($id);

		//insert new
		$this->CI->db->insert($this->cache_table,$options);
		
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of item in cache
	 * @return 	boolean		true on success/false on failure
	 */
	public function delete($id)
	{
		$this->CI->db->where('uid',$id);
		$this->CI->db->delete($this->cache_table);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the Cache
	 *
	 * @return 	boolean		false on failure/true on success
	 */	
	public function clean()
	{
		$this->CI->db->query(sprintf('delete from %s',$this->cache_table));
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * Not supported by file-based caching
	 *
	 * @param 	string	user/filehits
	 * @return 	mixed 	FALSE
	 */
	public function cache_info($type = NULL)
	{
		return $this->CI->db->count_all_results($this->cache_table);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	mixed		FALSE on failure, array on success.
	 */
	public function get_metadata($id)
	{
		$this->CI->db->select('*');
		$this->CI->db->where('uid',$id);
		$row=$this->CI->db->get($this->cache_table)->row_array();
		
		if (!$row)
		{
			return FALSE;
		}
		
		return $row;
	}

	// ------------------------------------------------------------------------

	/**
	 * Is supported
	 *
	 * In the file driver, check to see that the cache directory is indeed writable
	 * 
	 * @return boolean
	 */
	public function is_supported()
	{
		//assuming the table CACHE exists
		return TRUE;
		
		/*$this->CI->select('id');
		$this->CI->limit(1);
		$this->CI->get('cache');*/
	}

	// ------------------------------------------------------------------------
}
// End Class

/* End of file Cache_db.php */
/* Location: ./system/libraries/Cache/drivers/Cache_db.php */