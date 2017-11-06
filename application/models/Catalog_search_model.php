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
* TODOS:// use SUB-QUERIES to enchance the search speed by 2X. 
**/
class Catalog_search_model extends CI_Model {

	//whether to search using FULLTEXT
	var $fulltext_enabled=TRUE;
	
	//database allowed column names
	var $allowed_fields=array('titl', 'nation','proddate', 'authenty');
	
	//fields for the study description
	var $study_fields=array('repositoryid','titl','titlstmt','authenty','geogcover','nation','topic','sername','producer','sponsor','proddate','refno','isshared');
	
	//fields for the variable
	var $var_fields=array('name','labl','qstn','catgry');

	//whether searching in variables or not
 	var $variable_search=FALSE;
 		
	var $search_count=0;
	
		
    public function __construct()
    {
        // model constructor
        parent::__construct();

		//search bulider		
		$this->load->library('Search_builder','','Search_builder');
    }
	
	/**
	* searches the catalog
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL)
    {
		//$this->output->enable_profiler(TRUE);

		//get search statistics
		$search_count=$this->search_count();
		
		if ($search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		//set default sort order
		if ($sort_order=='')
		{
			$sort_order='asc';
		}
		
		//defautl sort by
		if ($sort_by=='')
		{
			$sort_by='titl';
		}
		
		
		$this->db->start_cache();
				
		//select survey fields
		$this->db->select('id,repositoryid,surveyid,titl, authenty,nation,refno,
							varcount,link_technical, link_study, link_report, 
							link_indicator, link_questionnaire,	isshared');
		
		//select form fields
		$this->db->select('forms.model as form_model, forms.path as form_path');
		
		//join FORMS with SURVEYS
		$this->db->join('forms', 'forms.formid= surveys.formid','left');		
		
		//build search statement
		$where=$this->_build_search_query();
			
		if ($where!=''){
			$this->db->where($where);
		}
				
		//join variables if variable fields are selected for searching
		if ($this->_is_variable_search()===TRUE)
		{
			//select columns for output
			$this->db->select('count(variables.sid) as totalFound');
			$this->db->join('variables', 'variables.sid = surveys.id','right');		
			$this->db->group_by( explode(',','id,titl,nation,authenty,refno') );		
		}
		else
		{
			//columsn for showing
			$this->db->select('id,titl,nation,authenty,refno');	
		}

		//set order by
		if ($sort_by!='' && $sort_order!=''){		
			//sort only if sorting on the allowed fields
			if ( in_array($sort_by,array('nation','titl','proddate')) )
			{
				$this->db->order_by($sort_by, $sort_order); 
			}	
		}
		
	  	$this->db->limit($limit, $offset);
		$this->db->from('surveys');		

        $result= $this->db->get()->result_array();		
		
		$this->db->stop_cache();
		//$this->last_query=$this->db->last_query;
		return $result;
    }


	//returns the search result count  	
    function search_count()
    {
		//searching variables?
		$variable_search=$this->_is_variable_search();
		
		//build where statement
		$where=$this->_build_search_query();

		//echo '<pre>';
		//echo $where;

		if ($where!='')
		{
			$where= ' where '. $where;
		}

		if ($variable_search===TRUE)
		{			
			$sql="select count(id) as total from surveys right join variables on surveys.id=variables.sid $where group by surveys.id";
			$sql="SELECT COUNT(*) AS total FROM ($sql) as table1";
		}
		else
		{
			$sql='select count(id) as total from surveys '. $where;
		}
		
		$count_query=$this->db->query($sql)->row();
		$this->search_count=$count_query->total;

		return $this->search_count;
    }
	
	function _build_search_query()
	{
			//search_builder configurations
			$options=array
			(
				'fulltext_enabled'=>TRUE,
				'db_fields'=>array				//database searchable fields 
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
							'labl,qstn',
							'titl,authenty,geogcover,nation,topic,scope,sername,producer,sponsor,refno',
							'name,labl,qstn'
						),
				'fulltext_fields'=>array			//fulltext indexes defined on the database table
						(
							'qstn', 
							'catgry',
							'labl',
							'titl',
							'titl,authenty,geogcover,nation,topic,scope,sername,producer,sponsor,refno',
							'name,labl,qstn'
						),
				'wildcard'=>'%',
				//'search_fields'=>array('name','title'),
				//'search_keywords'=>'where is the food'				
			);
			
			//database search fields when ALL fields are selected for search
			/*$default_fields=array				
							(
							'titl',
							'titlstmt',
							'authenty',
							'geogcover',
							'nation',
							'topic',
							'sername',
							'producer',
							'sponsor',
							'refno',
							'name',
							'labl',
							'qstn',
							'catgry',
							);			*/
			$default_fields=array('titl,authenty,geogcover,nation,topic,scope,sername,producer,sponsor,refno','name,labl,qstn');
		
		//initialize search builder
		$this->Search_builder->initialize($options);		
		
		//get search GET values
		
		//row 1
		$search_fields1=$this->input->get_post("field1");// explode(",",$this->input->get_post("field1"));
		$search_keywords1=$this->input->get_post("keyword1");

		//row 2
		$search_fields2=$this->input->get_post("field2");//explode(",",$this->input->get_post("field2"));
		$search_keywords2=$this->input->get_post("keyword2");

		if ($search_fields1[0]=='all')
		{
			$search_fields1=$default_fields;
		}
		if ($search_fields2[0]=='all')
		{
			$search_fields2=$default_fields;
		}
		
		//clean up keywords
		//TODO: add code to remove noise words, short words, etc				
		$search_keywords1=trim($search_keywords1);
		$search_keywords2=trim($search_keywords2);
		
		$line1='';
		$line2='';
						
		if ( $search_keywords1!='')
		{
			//For shorter keyword <3 on fulltext combined indexes, break them in individual fields
			$search_fields1=$this->_fix_short_word_fields($search_keywords1, $search_fields1);
			
			//build where stmt for line 1 of search
			$line1=$this->Search_builder->build_search_query($search_fields1, $search_keywords1);
		}

		if ( $search_keywords2!='')
		{
			$search_fields2=$this->_fix_short_word_fields($search_keywords2, $search_fields2);
			
			$line2=$this->Search_builder->build_search_query($search_fields2, $search_keywords2);			
		}

		//get seqrch query for line 1		
		$op=$this->input->get_post('op');
		
		//set AND/OR operator to join the line1 and line2
		if (strtolower($op)!='and' && strtolower($op)!='or')
		{
			$op='AND';
		}
		
		$result='';
		
		if ($line1!='')
		{
			$result=$line1;
		}
		
		if ($line2!='')
		{
			if ($result=='')
			{
				$result=$line2;
			}	
			else
			{
				$result.=" $op ($line2)";
			}
		}
		return $result;				
	}

	/**
	* for keywords shorter <4 on fulltext with multi column indexes fails
	* the function explodes the single field (titl,labl,catgy) into invidiual fields
	*
	*/
	function _fix_short_word_fields($keywords, $fields)
	{
				//For shorter keyword <3 on fulltext combined indexes, break them in individual fields
			if (strlen($keywords)==3)
			{
				$result_fields=array();
				foreach($fields as $key=>$value)
				{
					if (strpos($value,",")!==FALSE)
					{
						$arr=explode(",",$value);
						$result_fields=array_merge($result_fields,$arr);
					}
					else
					{
						$result_fields[]=$value;
					}
				}	
				return $result_fields;
			}			
		return $fields;		
	}	

	/**
	* Check if searching variables or not
	*
	* returns Boolean
	*/
	function _is_variable_search()
	{
		//row 1
		$search_fields1=$this->input->get_post("field1");
		$search_keywords1=trim($this->input->get_post("keyword1"));

		//row 2
		$search_fields2=$this->input->get_post("field2");
		$search_keywords2=trim($this->input->get_post("keyword2"));
		
		$variable_fields=array('name','catgry','qstn','labl','name,labl,qstn','all');
		
		if ($search_keywords1!='')
		{
			foreach($search_fields1 as $search_field)
			{
				if (in_array($search_field,$variable_fields) )
				{
					return TRUE;
				}
			}
		}
		
		if ($search_keywords2!='')
		{
			foreach($search_fields2 as $search_field)
			{
				if (in_array($search_field,$variable_fields) )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	

}
?>