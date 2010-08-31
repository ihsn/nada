<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Database simple search
 * 
 * 
 *
 *
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @category	Search
 * @author		Mehmood
 * @link		-
 *
 */
class Simple_Search{    	
	
	var $select=array();	//for db->select
	var $join=array();		//for db->join
	var $search_fields='';		//search will be performed on these fields
	var $keywords='';			//search keywords
	var $filters=array();	//additional search filters 
	
	var $ci;
	
	var $errors=array();
		
    /**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	function Simple_Search($params = array())
	{
		if (count($params) > 0)
		{
			$this->initialize($params);
		}

		log_message('debug', "Simple_Search Class Initialized");
	}

	
	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */
	function initialize($params = array())
	{
		echo 'loading';
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}
		}
	}

	function select($str)
	{
		$this->select[]=$str;
	}
	
	function find()
	{

		$sql='SELECT '. $this->select . ' FROM surveys';
		$sql.='WHERE '. $this->search_fields . ' LIKE '. $this->keywords;
		
		return $sql;
	}
	
	//check if the survey already exists?
	function survey_exists($surveyid,$repositoryid)
	{
		//$query = $this->db->get_where('surveys', array('surveyid' => $id,'repositoryid' => $repositoryid));
		
		$this->ci->db->select('id');
		$this->ci->db->from('surveys');
		$this->ci->db->where(array('surveyid' => $surveyid,'repositoryid' => $repositoryid) );
		$query=$this->ci->db->get();
		//print $this->ci->db->last_query();
		if ($query->num_rows() > 0)
		{
		   foreach ($query->result() as $row)
		   {
				return $row->id;
		   }
		}
		return false;		
	}
	
}// END Search class

/* End of file Simple_Search.php */
/* Location: ./application/libraries/Simple_Search.php */