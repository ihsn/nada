<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Database Search builder
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
class Search_Builder{
	
	var $fulltext_enabled=TRUE;			//whether to search using FULLTEXT
	var $db_fields=array				//database searchable fields 
						(
						'repositoryid',
						'titl',
						'titlstmt',
						'authenty',
						'geogcover',
						'nation',
						'topic',
						'sername',
						'producer',
						'sponsor',
						'proddate',
						'refno',
						'name',
						'labl',
						'qstn',
						'catgry',
						'qstn,catgry',
						'labl,qstn'
						);	
	
	var $fulltext_fields=array			//fulltext indexes defined on the database table
						(
						'qstn', 
						'catgry',
						'labl',
						'qstn,catgry',
						'qstn,catgry,labl'
						);
	
	var $wildcard='%';					//wildcard for mysql LIKE searches
		
	var $search_fields=array('title');			//user selected fields to search on
	var $search_keywords='';			//search string
	
	var $ci=NULL;	

   /**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	function Search_Builder($params = array())
	{
		if (count($params) > 0)
		{
			$this->initialize($params);
		}
		
		$this->ci=& get_instance();
		
		log_message('debug', "Search Class Initialized");
	}

	
	function initialize($params=array())
	{
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
	
	function build_search_query($fields=NULL,$keywords=NULL)	
	{	
		if ( is_array($fields) )
		{
			$this->search_fields=$fields;
		}
		
		if ($keywords!='')
		{
			$this->search_keywords=$keywords;
		}		
		if ($this->search_keywords=='')
		{
			return 'NOTHING TO SEARCH';
		}
		
		var_dump($this->search_fields);
		
		echo 'why';
		$result='';										
		foreach($this->search_fields as $field)
		{
echo 'x';
			//if field is a fulltext field, no need to tokenize
			if (in_array($field,$this->fulltext_fields) && $this->fulltext_enabled===TRUE)
			{
				$keywords=array($this->search_keywords);
			}
			else
			{
				//tokenize words for simple search
				$keywords=$this->_tokenize($this->search_keywords);
			}

var_dump($keywords);
			foreach ($keywords as $word)
			{
				//search on STUDY fields only
				if (in_array($field,$this->db_fields))
				{
					if ($result=='')
					{
						$result=$this->_search_single_field($field,$word);
					}
					else
					{
						$result.=' OR '. $this->_search_single_field($field,$word);
					}	
				}	
			}	
		}
	var_dump($result);
		return $result;
	}
	
	//searches a single field based on it is a fulltext column or a single field
	function _search_single_field($fieldname,$keyword)
	{
		if (strlen(trim($keyword))==3 )
		{
			//$field .' REGEXP (\'[[:<:]]'.$keyword.'[[:>:]]\' ) ';		
			$result=sprintf("%s REGEXP %s",$fieldname, $this->ci->db->escape('[[:<:]]'.$keyword.'[[:>:]]'));
		}
		else if ($this->fulltext_enabled==TRUE && in_array($fieldname,$this->fulltext_fields))
		{
			$result=sprintf("MATCH(%s) AGAINST(%s)",$fieldname, $this->ci->db->escape($keyword));
		}
		else
		{
			$result=sprintf("%s LIKE %s",$fieldname, $this->ci->db->escape($this->wildcard.$keyword.$this->wildcard));
		}
		return $result;
	}
	
	//explode words or perform any cleaning here to remove keywords based on lenght 
	//remove noise words etc
	function _tokenize($str)
	{
		$str_arr=explode(" ",$str);
		
		$result=array();
		foreach($str_arr as $value)
		{
			$value=trim($value);
			if ($value!='')
			{
				$result[]=$value;
			}
		}		
		return $result;
	}
} //end SEARCH class

/* End of file Search.php */
/* Location: ./application/libraries/Search.php */