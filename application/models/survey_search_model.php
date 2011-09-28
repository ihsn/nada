<?php
/**
*
* Performs simple, fulltext searches on MYSQL, MSSQL, SQLITE and POSTGRES
*
* MYSQL FULLTEXT features supported: 
* - quotations: if the keywords are enclosed in the quotes
* negate(-): use - or ~ e.g. -apples
* include(+): use +apples - result must include the keywod
*
*
*
**/
class Survey_search_model extends CI_Model {
	
	var $fulltext_enabled=TRUE;			//whether to search using FULLTEXT
	var $search_params=array();			//holds the post values array
	var $study_fields=array				//fields for the study description
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
						'refno'
						);
						
	var $var_fields=array				//searchable fields for the variable table
						(
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
		
	var $search_fields=array();			//user selected fields to search on
	var $search_keywords='';			//search string
		

    public function __construct()
    {
        // model constructor
        parent::__construct();
    }
	
	
	function initialize($search_array=NULL)
	{
		//$this->search_params=$search_array;
		
		//values received from the search form
		$this->search_fields=array
						(
							'titl',
							'name',
							'qstn',
							'qstn,catgry'
						);
						
		$this->search_keywords='health sur\'vey 20002';				
	}
	
	function build_search_query()	
	{			
		if (!is_array($this->search_params))
		{
			return 'nothing to search';
		}

		$result='';										
		foreach($this->search_fields as $field)
		{

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


			foreach ($keywords as $word)
			{
				//search on STUDY fields only
				if (in_array($field,$this->study_fields))
				{
					if ($result=='')
					{
						$result="\r\n". $this->_search_single_field($field,$word);//$field . ' LIKE ' . $this->db->escape($word);
					}
					else
					{
						$result.="\r\n OR ". $this->_search_single_field($field,$word);
					}	
				}	
				
				//search on VARIABLE fields only
				if (in_array($field,$this->var_fields))
				{
					if ($result=='')
					{
						$result="\r\n". $this->_search_single_field($field,$word);
					}
					else
					{
						$result.="\r\n OR ". $this->_search_single_field($field,$word);
					}	
				}	
			}	
		}
		return $result;
	}
	
	//searches a single field based on it is a fulltext column or a single field
	function _search_single_field($fieldname,$keyword)
	{
		if ($this->fulltext_enabled==TRUE && in_array($fieldname,$this->fulltext_fields))
		{
			$result=sprintf("MATCH(%s) AGAINST(%s)",$fieldname, $this->db->escape($keyword));
		}
		else
		{
			$result=sprintf("%s LIKE %s",$fieldname, $this->db->escape($this->wildcard.$keyword.$this->wildcard));
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
}
?>