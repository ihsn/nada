<?php
/**
* Catalog
*
**/
class Catalog_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array('title', 'nation','year_start', 'authoring_entity');
	
	//fields for the study description
	var $study_fields=array(
					'surveys.id',
					'repositoryid',
					'idno',
					'title',
					'authoring_entity',
					'nation',
					'dirpath',
					'metafile',
					'link_technical', 
					'link_study',
					'link_report',
					'link_indicator',
					'link_questionnaire',
					'year_start',
					'year_end',
					'link_da',
					'published',
					'surveys.created',
					'changed',
					'varcount',
					'total_views',
					'total_downloads'
					);
	
	//additional filters on search
	var $filter=array('isdeleted='=>0);
	var $active_repo=NULL;
	var $active_repo_negate=FALSE;//show repo surveys or negate repo surveys
	
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* searches the catalog
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL,$filter=NULL)
    {
	
		if ($filter!=NULL)
		{
			foreach($filter as $key=>$value)
			{
				$this->filter["$key"]=$value;
			}	
		}
		
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		//$this->db->start_cache();		
		
		//select survey fields
		$this->db->select('surveys.id,surveys.repositoryid,idno,title, authoring_entity,nation,
							varcount,link_technical, link_study, link_report, 
							link_indicator, link_questionnaire,	isshared,surveys.changed,surveys.created,surveys.published,surveys.year_start, surveys.year_end');
		
		//select form fields
		$this->db->select('forms.model as form_model, forms.path as form_path');		
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
		$this->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid','left');
		
		if ($this->active_repo!=NULL) 
		{
			$this->db->select("sr.repositoryid as repo_link, sr.isadmin as repo_isadmin");
			$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		}	
		//$this->db->join('survey_notes notes', 'notes.sid= surveys.id','left');

		//build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		if ($where!='')
		{
			$this->db->where($where,NULL,FALSE);
		}	

		//set order by
		if ($sort_by!='' && $sort_order!='')
		{		
			if (in_array($sort_by, $this->study_fields) )
			{
				$this->db->order_by('surveys.'.$sort_by, $sort_order); 
			}
		}
		else
		{
			$this->db->order_by('surveys.repositoryid'); 
			$this->db->order_by('surveys.changed', 'desc'); 
		}
				
	  	$this->db->limit($limit, $offset);
		$this->db->from('surveys');
		//$this->db->stop_cache();

        $result= $this->db->get()->result_array();		

		return $result;
    }
	
	//builds where clause using the variables from GET
	function _build_search_query()
	{
		//clear search, if reset is set
		$reset=$this->input->get("reset");
		
		$fields=$this->input->get("field");
		$keywords=trim($this->input->get("keywords"));
		
		$allowed_fields=array(
						'title', 
						'idno', 
						'authoring_entity', 
						'nation'
						);
		
		if ($this->active_repo!=NULL)
		{
			$allowed_fields['repositoryid']='sr.repositoryid';
		}	
		
		$where_clause='';			
		$filter='';

		//build where clause for FILTERS
		foreach($this->filter as $key=>$value)
		{
			if ($filter!='')
			{
				$filter.=' AND ' . $key . $this->db->escape($value);
			}
			else
			{
				$filter=$key . $this->db->escape($value);
			}		
		}
			
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND '.$filter;
		}
		else
		{
			$where_clause=$filter;
		}

		//additional search options
		$additional_filters=array('repositoryid');
		foreach($additional_filters as $afilter)
		{
			$value=$this->input->get($afilter);
			if ($value)
			{
				if ( trim($where_clause)!='')
				{	
					$where_clause.= ' AND '.$afilter.' = '.$this->db->escape($value); 
				}
				else
				{
					$where_clause.= ' '.$afilter.'= '.$this->db->escape($value); 
				}
			}			
		}
		
		//search TAGS
		$tags=$this->input->get('tag');
		
		$tags_sql=NULL;
		
		if (is_array($tags))
		{
			foreach($tags as $key=>$value)
			{
				if (trim($value)!='')
				{
					$tags_sql[$key]=sprintf('tag=%s',$this->db->escape($value));
				}	
			}
			
			if ( is_array($tags_sql) && count($tags_sql)>0)
			{
				$tags_sub_query='select sid from survey_tags where '.implode(' AND ',$tags_sql);
			}	
		
			if ( trim($where_clause)!='')
			{	
				$where_clause.= sprintf(' AND surveys.id in (%s)',$tags_sub_query);
			}
			else
			{
				$where_clause.= sprintf('  surveys.id in (%s)',$tags_sub_query);
			}
		}		
		
		
		//active repo
		if ($this->active_repo!=NULL)
		{
			if (!$this->active_repo_negate)
			{
				//$where_clause.=' and (sr.repositoryid='.$this->db->escape($this->active_repo).' AND surveys.repositoryid='.$this->db->escape($this->active_repo).')';
				$where_clause.=' and (sr.repositoryid='.$this->db->escape($this->active_repo).')';
			}
			else
			{	//show studies not part of the active repository
				$where_clause.=' and isadmin=1 and sr.repositoryid!='.$this->db->escape($this->active_repo);//. ' AND sr.sid not in(select sid from survey_repos where repositoryid='.$this->db->escape($this->active_repo).') and isadmin=1';
			}	
		}
		
		//search on FIELDS [country, idno, title, producer]
		$search_fields=array('nation','idno','title','published');
		$search_options=NULL;
		
		foreach($search_fields as $name)
		{
			$value=$this->input->get($name);
			
			//for repeatable fields eg. nation[]=xyz&nation[]=abc
			if (is_array($value))
			{
				$tmp=NULL;
				foreach($value as $val)
				{
					if(trim($val)!=='') 
					{
						$tmp[]=sprintf("%s like %s",$name,$this->db->escape('%'.$val.'%'));
					}	
				}
				
				if (is_array($tmp)&& count($tmp)>0)
				{
					$search_options[]='('.implode(' OR ', $tmp).')';
				}
			}
			else
			{
				//single value fields
				if(trim($value)!=='') 
				{
					$search_options[]=sprintf("%s like %s",$name,$this->db->escape('%'.$value.'%'));
				}	
			}			
		}//end-foreach
		
		
		$search_options_str=NULL;
		if (is_array($search_options) && count($search_options)>0)
		{
			$search_options_str='('.implode(' AND ', $search_options).')';
			
			if ( trim($where_clause)!='')
			{	
				$where_clause.= ' AND ' . $search_options_str;
			}
			else
			{
				$where_clause=$search_options_str;
			}
		}
		return $where_clause;
	}


	//returns the search result count  	
    function search_count()
    {
        //build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		if ($where!='')
		{
			$this->db->where($where,NULL,FALSE);
		}
		
		$this->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid','left');
		
		if ($this->active_repo!=NULL) 
		{
			$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		}
		
		return $this->db->count_all_results('surveys');
    }

	/**
	* returns a single survey row by ID, or IDNO
	*
	*
	**/
	function select_single($id,$repositoryid=FALSE)
	{
		//study fields
		$fields=$this->study_fields;
		
		//form fields
		$fields[]='surveys.formid, forms.model as model';
		
		//notes
		//$fields[]='notes.admin_notes as admin_notes';
		//$fields[]='notes.reviewer_notes as reviewer_notes';
		
		//implode
		$fields=implode(",",$fields);	
		
		$this->db->select($fields);
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
		//$this->db->join('survey_notes notes', 'notes.sid= surveys.id','left');
		
		if (is_numeric($id))
		{
			$this->db->where('surveys.id', $id); 
		}
		else 
		{	
			//get survey by idno
			$this->db->where('surveys.idno', $id); 
		}	
		
		//execute query
		$survey=$this->db->get('surveys')->row_array();
		
		if(!$survey)
		{
			return FALSE;
		}
		
		//get study ownership/link info
		$this->db->select("*");
		$this->db->where('sid', $survey['id']); 
		$additional=$this->db->get('survey_repos')->result_array();
		
		$survey['repo']=array();
		
		if ($additional)
		{
			$survey['repo']=$additional;
		}
		
		//get study countries
		$survey['country']=$this->get_survey_iso_countries($survey['id']);

		return $survey;
	}
	
	
	function get_survey_iso_countries($id)
	{
		$this->db->select('survey_countries.cid,countries.iso,countries.name');
		$this->db->join('countries','countries.countryid=survey_countries.cid','INNER');
		$this->db->where('survey_countries.sid',$id);
		return $this->db->get('survey_countries')->result_array();
	}
	
	/**
	* returns a single survey by ID with minimum info
	*
	*
	**/
	function get_survey($id)
	{
		$this->db->select('id,title,idno,year_start,nation,repositoryid');
		$this->db->where('id', $id); 
		return $this->db->get('surveys')->row_array();
	}

	/**
	* get variable by vid
	**/
	function get_variable_by_vid($survey_id, $variable_id)
	{
		$this->db->select('uid,name,labl');
		$this->db->where('vid', $variable_id); 
		$this->db->where('sid', $survey_id); 
		return $this->db->get('variables')->row_array();
	}

	/**
	* Returns all survey rows by default 
	*
	*
	*/ 
	function select($limit=NULL,$offset=NULL,$sort_by='changed',$sort_order='desc')
	{    	

		$this->db->select('surveys.*',FALSE);
		$this->db->select('forms.model as model',FALSE);		
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
	    $this->db->order_by('changed', 'desc');
		
		if ($limit!=NULL)
		{
			$this->db->limit($limit,$offset);
		}	
		return $this->db->get('surveys');		
	}
	
	
	/**
	* returns survey form model [direct,public,etc.]
	*
	*
	**/
	function get_survey_form_model($id)
	{
		$this->db->select('forms.model');
		$this->db->join('forms', 'forms.formid= surveys.formid','left');		
		$this->db->where('id', $id); 
		$result=$this->db->get('surveys')->row_array();

		if ( count($result) > 0)
		{
			return trim(strtolower($result['model']));
		}
		return FALSE;
	}

	/**
	*
	* Get a list of all resources by survey id
	*
	*/
	function get_resources_by_survey($sid)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $sid); 
		return $this->db->get('resources')->result_array();
	}

	/**
	*
	* List of resources grouped by resource-type
	*
	*
	*/
	function get_grouped_resources_by_survey($sid)
	{
		$output=FALSE;
		
		//questionnaires
		$result=$this->get_resources_by_type($sid,'doc/qst]');
		if ($result)
		{
			$output['questionnaires']=$result;
		}	

		//reports
		$result=$this->get_resources_by_type($sid,'doc/rep]');
		if ($result)
		{
			$output['reports']=$result;
		}			
			
		//technical documents
		$result=$this->get_resources_by_type($sid,'doc/tec]');
		if ($result)
		{
			$output['technical']=$result;
		}					
		
		//other materials
		$result=$this->get_resources_by_type($sid,'other');
		if ($result)
		{
			$output['other']=$result;
		}			

		return $output;	
	}

	/**
	*
	* Get a list of citations for a survey by survey id
	*
	*/
	function get_citations_by_survey($sid)
	{
		$this->db->select('citations.*');
		$this->db->join('survey_citations', 'citations.id= survey_citations.citationid');
		$this->db->where('survey_citations.sid', $sid); 
		return $this->db->get('citations')->result_array();
	}

	/**
	*
	* Return resource by survey and resource type
	*
	*/
	function get_resources_by_type($sid,$dctype)
	{
		$this->db->select('*');
		$this->db->where('survey_id',$sid);
		
		if ($dctype=='other')
		{
			//other materials
			$this->db->not_like('dctype','doc/tec]');
			$this->db->not_like('dctype','doc/rep]');
			$this->db->not_like('dctype','doc/qst]');
			$this->db->not_like('dctype','dat]');
			$this->db->not_like('dctype','dat/micro]');
		}
		else
		{
			$this->db->like('dctype',$dctype);
		}	
		return $this->db->get('resources')->result_array();
	}	
	
	/**
	* Get survey external resources as RDF
	*
	*/
	function get_survey_rdf($id)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $id); 
		$rows=$this->db->get('resources')->result_array();
		
		$line_br="\r\n";
		
		$rdf='<?xml version=\'1.0\' encoding=\'UTF-8\'?>'.$line_br;
		$rdf.='<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">'.$line_br;
		
		foreach($rows as $row)
		{
			$row=(object)$row;	
			$rdf.=sprintf('<rdf:Description rdf:about="%s">',htmlentities($row->filename,ENT_QUOTES,'UTF-8'));
			$rdf.='<rdf:label><![CDATA['.$row->title.']]></rdf:label>';
			$rdf.='<dc:title><![CDATA['.$row->title.']]></dc:title>';
			
			if ($row->author)
			{
				$rdf.='<dc:creator><![CDATA['.$row->author.']]></dc:creator>';
			}	
			if ($row->publisher)
			{			
				$rdf.='<dc:publisher><![CDATA['.$row->publisher.']]></dc:publisher>';
			}
			if ($row->contributor)
			{
				$rdf.='<dc:contributor><![CDATA['.$row->contributor.']]></dc:contributor>';
			}	
			if ($row->dcdate)
			{
				$rdf.='<dcterms:created>'.$row->dcdate.'</dcterms:created>';
			}	
			if ($row->dcformat)
			{
				$rdf.='<dc:format><![CDATA['.$row->dcformat.']]></dc:format>';
			}	
			if ($row->dctype)
			{
				$rdf.='<dc:type><![CDATA['.$row->dctype.']]></dc:type>';
			}	
			if ($row->country)
			{
				$rdf.='<dcterms:spatial><![CDATA['.$row->country.']]></dcterms:spatial>';
			}	
			if ($row->description)
			{
				$rdf.='<dc:description><![CDATA['.$row->description.']]></dc:description>';							
			}	
			if ($row->toc)
			{
				$rdf.='<dcterms:tableOfContents><![CDATA['.$row->toc.']]></dcterms:tableOfContents>';
			}	
			if ($row->abstract)
			{
				$rdf.='<dcterms:abstract><![CDATA['.$row->abstract.']]></dcterms:abstract>';
			}
			$rdf.='</rdf:Description>'.$line_br;
		}
		$rdf.='</rdf:RDF>';
		
		return $rdf;
	}
	
	/**
	* returns survey folder path
	*
	*
	**/
	function get_survey_path($id)
	{
		$this->db->select('dirpath'); 
		$this->db->where('id', $id); 
		$result=$this->db->get('surveys')->row_array();

		if ( count($result) > 0)
		{
			return $result['dirpath'];
		}
		return false;
	}
	
	
	/*
	 * Returns the complete survey folder path
	 * 
	 */
	function get_survey_path_full($id)
	{
		//get survey folder path
		$survey_rel=$this->get_survey_path($id);
		
		if ($survey_rel===FALSE)
		{
			return FALSE;
		}
		
		//get datasets folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//join to create full path
		$survey_folder=unix_path($catalog_root.'/'.$survey_rel);
		
		return $survey_folder;
	}
	
	// returns survey DDI file path
	function get_survey_ddi_path($id)
	{
		//get survey folder path
		$data=$this->select_single($id);
		
		if ($data===FALSE || count($data)<1)
		{
			return FALSE;
		}

		$data=(object)$data;
		
		//get datasets folder path
		$catalog_root=$this->config->item("catalog_root");
		
		//join to create full path
		$ddi_file=$catalog_root.'/'.$data->dirpath.'/'.$data->metafile;

		$ddi_file=unix_path($ddi_file);
		
		if (file_exists($ddi_file) && is_file($ddi_file))
		{
			return $ddi_file;
		}
		
		return FALSE;
	}
	
	/**
	* update survey options
	*
	* options - array
	**/
	function update_survey_options($options)
	{
		//allowed fields
		$valid_fields=array('link_technical', 'link_study', 'link_report', 
							'link_indicator','link_questionnaire',
							'isshared','formid','changed','isdeleted','link_da','published');
		
		//pk field name
		$key_field='id';
		
		//set data modified
		$options['changed']=date("U");
		
		if (!array_key_exists($key_field,$options) )
		{
			echo 'id was not provided';
			return false;
		}
		
		$update_arr=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$update_arr[$key]=$value;
			}
		}
		
		//update
		$this->db->where($key_field, $options[$key_field]);
		$result=$this->db->update('surveys', $update_arr); 
		
		return $result;		
	}
	
	/**
	* Delete survey and related data
	*
	*
	*/
	function delete($id)
	{
		$this->delete_storage_folder($id);
		
		$this->db->where('id', $id); 
		$deleted=$this->db->delete('surveys');
		
		if ($deleted)
		{
			//remove variables
			$this->db->where('sid', $id); 
			$this->db->delete('variables');		
			
			//remove external resources
			$this->db->where('survey_id', $id); 
			$this->db->delete('resources');					

			//remove topics
			$this->db->where('sid', $id); 
			$this->db->delete('survey_topics');					

			//remove citations
			$this->db->where('sid', $id); 
			$this->db->delete('survey_citations');					

			//remove collection dates
			$this->db->where('sid', $id); 
			$this->db->delete('survey_years');
			
			//remove repos
			$this->db->where('sid', $id); 
			$this->db->delete('survey_repos');

			//remove alias
			$this->db->where('sid', $id); 
			$this->db->delete('survey_aliases');
			
			//remove countries
			$this->db->where('sid', $id); 
			$this->db->delete('survey_countries');

			//remove tags
			$this->db->where('sid', $id); 
			$this->db->delete('survey_tags');
			
			//remove notes
			$this->db->where('sid', $id); 
			$this->db->delete('survey_notes');

		}		
	}

	function delete_storage_folder($sid)
	{
		$dataset_folder=$this->get_survey_path_full($sid);
		$catalog_root=get_catalog_root();

		if($catalog_root=='' || $dataset_folder==''){
			return false;
		}

		if($catalog_root==$dataset_folder){
			return false;
		}

		if (!strpos($dataset_folder, $catalog_root) === 0 ) {
			return false;
		}
		
		remove_folder($dataset_folder);

		return true;
	}

	/**
	*
	* Update collection years for all the surveys in the database
	* 	 
	*/
	function batch_update_collection_dates()
	{
		//get all surveys
		$this->db->select("id,year_start,year_end");
		$surveys=$this->db->get("surveys")->result_array();
		
		//update/add dates for each survey
		foreach($surveys as $survey)
		{
			$this->update_data_collection_dates($survey["id"], $survey["year_start"], $survey["year_end"]);
		}		
	}

	/**
	*
	* Insert/delete survey data collection date range
	*/	
	function update_data_collection_dates($sid,$start,$end)
	{
		$start=(integer)$start;
		$end=(integer)$end;
		
		if ($start==0)
		{
			$start=$end;
		}
		
		if($end==0)
		{
			$start=$end;
		}
		
		//build an array of years range
		$years=range($start,$end);

		//remove existing dates if any
		$this->db->delete('survey_years',array('sid' => $sid));

		//insert dates into database
		foreach($years as $year)
		{
			$options=array(
						'sid' => $sid,
						'data_coll_year' => $year);
			//insert			
			$this->db->insert('survey_years',$options);
		}
	}
	
	
	/**
	*
	* Produce JSON for survey, related citations and external resources
	*
	**/
	function survey_to_json($id)
	{
	    $ci =& get_instance();
		$ci->load->model('Citation_model');

		//get survey
		$survey_array=$this->select_single($id);		
		
		if ($survey_array===FALSE || count($survey_array)==0)
		{
			return FALSE;
		}

		//DDI file path
		$ddi_file=$this->get_survey_ddi_path($id);

		if (!file_exists($ddi_file))
		{
			return FALSE;
		}
		
		//fields to export
		$survey['id']=$survey_array['id'];
		$survey['model']=$survey_array['model'];
						
		//Checksum
		$survey['ddi_checksum']=md5_file($ddi_file);
		
		//get external resources
		$survey['resources']=$this->get_resources_by_survey($id);
		
		//get survey related citations
		$survey['citations']=$ci->Citation_model->get_citations_by_survey($id);

		return json_encode($survey);
	}
	
	
	/**
	*
	* Check it is harvested survey
	*
	* returns True/False - 
	**/
	function is_harvested($id=NULL)
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		
		//get survey array
		$survey=$this->get_survey($id);
		
		//check if survey repositoryid exists in the repositories
		$this->db->select('repositoryid');
		$this->db->where('repositoryid',$survey['repositoryid']);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->row_array();
		
		if ($result)
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	*
	* Returns survey repository info as array
	*	
	**/
	function get_repository_by_survey($id=NULL)
	{
		if (!is_numeric($id))
		{
			return FALSE;
		}
		
		//get survey array
		$survey=$this->get_survey($id);
		
		//check if survey repositoryid exists in the repositories
		$this->db->select('*');
		$this->db->where('repositoryid',$survey['repositoryid']);
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->row_array();
		
		if ($result)
		{
			return $result;
		}

		return FALSE;
	}

	/**
	*
	* Returns an array of all repositories
	*	
	**/
	function get_repositories()
	{
		$this->db->select('*');
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result)
		{
			return array();
		}

		//create an array, making the repositoryid array key
		$repos=array();
		foreach($result as $row)
		{
			$repos[$row['repositoryid']]=$row;
		}
	
		return $repos;
	}
	
	/**
	*
	* Returns an array of all repository names
	*	
	**/
	function get_repository_array()
	{
		$this->db->select('repositoryid');
		$query=$this->db->get('repositories');

		if (!$query)
		{
			return array();
		}
		
		$result=$query->result_array();
		
		if (!$result)
		{
			return array();
		}

		//create an array, making the repositoryid array key
		$repos=array();
		foreach($result as $row)
		{
			$repos[]=$row['repositoryid'];
		}
	
		return $repos;
	}

	/**
	* 
	* Returns collection rows for the survey
	*/
	/*function get_collections($id)
	{
		$sql='select t.title as title
				FROM vocabularies v
				INNER JOIN terms t on t.vid=v.vid
				left JOIN survey_collections st on st.tid=t.tid
				where v.title=\'DDI Collection\' and sid='. (int)$id;
  		$query=$this->db->query($sql);
		
		if (!$query)
		{
			return FALSE;
		}
		
		$result=$query->result_array();
		
		if (!$result)
		{
			return FALSE;
		}

		//create an array, making the repositoryid array key
		$items=array();
		foreach($result as $row)
		{
			$items[]=$row['title'];
		}
	
		return $items;
	}
	*/
	
	/**
	* returns a compact list of all surveys
	*
	**/
	function select_all_compact()
	{
		$this->db->select('id, idno,title,nation');
		$this->db->order_by('nation, title'); 
		return $this->db->get('surveys')->result_array();		
	}



	/**
	*
	* Check if a study has citations
	*
	* returns number of citations per study
	**/
	function has_citations($sid)
	{
		$query=$this->db->query('select count(*) as total from survey_citations where sid='.(integer)$sid);
		if ($query)
		{
			$row=$query->row_array();
			
			return $row['total'];
		}
		return FALSE;
	}

	
	/**
	*
	* Return resource count by survey and resource type
	*
	*/
	function has_external_resources($sid)
	{
		$this->db->select('count(*) as total');
		$this->db->where('survey_id',$sid);
		$this->db->not_like('dctype','dat]');
		$this->db->not_like('dctype','dat/micro]');
		$result=$this->db->get('resources')->row_array();
		
		return $result['total'];
	}

	
	
	/**
	* returns distinct values for the study field
	*
	**/
	function select_distinct_field($fieldname)
	{
		$this->db->flush_cache();
		$this->db->select($fieldname);
		$this->db->order_by($fieldname); 
		$this->db->group_by($fieldname); 
		$query=$this->db->get('surveys');

		if ($query)
		{
			$rows=$query->result_array();
			$output=array();
			foreach($rows as $row)
			{
				$output[]=$row[$fieldname];
			}
			return $output;
		}		
		return FALSE;
	}
	
	/**
	*
	* Link to a study from another repo
	**/
	function copy_study($repositoryid,$sid,$isadmin=0)
	{
		$options=array(
				'repositoryid'=>$repositoryid,
				'sid'=>$sid,
				'isadmin'=>$isadmin
			);
		
		//first unlink incaase it is already set
		$this->unlink_study($repositoryid,$sid);
			
		return $this->db->insert("survey_repos",$options);
	}
	
	
	/**
	*
	* unlink a study
	**/
	function unlink_study($repositoryid,$sid,$isadmin=0)
	{
		$options=array(
				'repositoryid'=>$repositoryid,
				'sid'=>$sid,
				'isadmin'=>$isadmin
		);
			
		return $this->db->delete("survey_repos",$options);
	}

	/**
	*
	* Remove all owners from the study
	**/
	function remove_all_study_owners($sid)
	{
		$options=array(
				'sid'=>$sid,
				'isadmin'=>1
		);
			
		return $this->db->delete("survey_repos",$options);
	}


	/**
	*
	* publish/unpublish a study
	**/
	function publish_study($id,$publish)
	{
		if (!in_array($publish,array(0,1)))
		{
			$publish=1;
		}
	
		$options=array(
				'published'=>$publish
		);
		
		$this->db->where('id',$id);	
		return $this->db->update("surveys",$options);
	}
	
	
	
	/**
	*
	* transfer owndership of a study
	*
	* 	@target_repository_id		new owner of the study
	*	@sid	surveys.id
	**/
	function transfer_ownership($target_repositoryid,$sid)
	{
		//get study info
		$survey=$this->select_single($sid);
		
		if (!$survey)
		{
			return FALSE;
		}
		
		//update surveys table
		$options=array(
				'repositoryid'=>$target_repositoryid
		);
	
		$this->db->where('id',$sid);
		$this->db->update("surveys",$options);
		
		//a repository can have only one owner, remove all owners 
		//and assign a single owner
		$this->remove_all_study_owners($sid);
		
		//make the target_repository owner of the study
		$this->copy_study($target_repositoryid,$sid,1);
		
		//remove study link from the target repository if any exists
		$this->unlink_study($target_repositoryid,$sid);
						
		//log
		$this->db_logger->write_log('transfer-ownership','transfered study ['.$survey['title'].'] from '.$survey['repositoryid'].' '.$target_repositoryid,'transfer-ownership',$sid);
	}


	
	
	/**
	*
	* Check if a repository exists
	*
	**/
	function repository_exists($repositoryid)
	{
		$this->db->select("repositoryid");
		$this->db->where("repositoryid",$repositoryid);
		$result=$this->db->get("repositories")->result_array();
		
		if ($result)
		{
			if (count($result)>0)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	*
	* Attach an admin/reviewer note to a study
	**/
	function attach_note($sid,$note, $note_type="admin")
	{
		$options=array();
		if ($note_type=="reviewer")
		{
			$options['reviewer_notes']=$note;
		}
		else
		{
			$options['admin_notes']=$note;
		}

		if ($this->note_exists($sid))
		{
			$this->db->where("sid",$sid);
			return $this->db->update("survey_notes",$options);
		}
		else
		{
			$options['sid']=$sid;	
			return $this->db->insert("survey_notes",$options);	
		}
		
	}
	
	
	/**
	*
	* Check if survey has a note attached
	**/
	function note_exists($sid)
	{
		$this->db->select("sid");
		$this->db->where("sid",$sid);
		$result=$this->db->get("survey_notes")->result_array();
		
		if ($result)
		{
			if (count($result)>0)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
	/**
	*
	* Get Repository owners info
	*
	**/
	function get_repo_ownership($sid)
	{
		$this->db->select("repositoryid");
		$this->db->where("sid",$sid);
		$this->db->where("isadmin",1);
		$result=$this->db->get("survey_repos")->result_array();
		return $result;		
	}
	
	
	/**
	*
	* Returns a unique list of all tags
	**/
	function get_all_survey_tags($repositoryid=NULL)
	{
		$this->db->select('tag,count(tag) as total');
		$this->db->join('surveys','surveys.id=survey_tags.sid','INNER');
		
		if ($repositoryid && $repositoryid!='central')
		{
			$this->db->join('survey_repos','surveys.id=survey_repos.sid','INNER');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}
		
		$this->db->group_by('tag');
		$result=$this->db->get('survey_tags');

        if ($result) {
            return $result->result_array();
        }

	}


	/**
	* returns internal survey id by IDNO
	* checks for ID in both surveys and aliases table
	**/
	function get_survey_uid($idno)
	{
		$this->db->select('id');
		$this->db->where('idno', $idno); 
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			return $query['id'];
		}
		
		//check IDNO in survey aliases
		$this->db->select('sid');
		$this->db->where(array('alternate_id' => $idno) );
		$query=$this->db->get('survey_aliases')->result_array();

		if (!$query)
		{
			return FALSE;
		}
		
		return $query[0]['sid'];
	}
	
	
	

	/**
	*
	* Returns a unique list of countries with survey counts
	**/
	function get_all_survey_countries($repositoryid=NULL)
	{
		$this->db->select('country_name, count(country_name) as total');
		if ($repositoryid)
		{
			$this->db->where(sprintf("sid in (select sid from survey_repos where repositoryid=%s)",$this->db->escape($repositoryid)),NULL,FALSE);
		}
		$this->db->group_by('country_name');
		$result=$this->db->get('survey_countries')->result_array();
		return $result;
	}

	/**
	*
	* Get tags by survey.id
	*
	* @surveys array
	**/
	function get_tags_by_survey($surveys)
	{
		if (!count($surveys)>0)
		{
			return FALSE;
		}
		
		$this->db->select('tag,sid');
		$this->db->where_in('sid',$surveys);
		$query=$this->db->get('survey_tags')->result_array();
		
		$output=array();
		foreach($query as $row)
		{
			$output[$row['sid']][]=$row['tag'];
		}
		
		return $output;
	}


	/**
	*
	* Return survey aliases + survey.id by internal id
	**/
	function get_survey_alaises($sid)
	{		
		//from aliases table
		$this->db->select('alternate_id');
		$this->db->where(array('sid' => $sid) );
		$query=$this->db->get('survey_aliases')->result_array();

		$aliases=array();
		
		if ($query)
		{
			foreach($query as $row)
			{
				$aliases[]=$row['alternate_id'];
			}
		}
		
		//from survey table
		$this->db->select('idno');
		$this->db->where(array('id' => $sid) );
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			$aliases[]=$query['idno'];
		}
		
		return $aliases;
	}
	
	
	/**
	*
	* Return an array of survey repos
	*	
	**/
	function get_survey_repositories($sid=NULL)
	{
		if (!is_numeric($sid))
		{
			return FALSE;
		}
		
		$this->db->select('repositories.repositoryid,title,ispublished');
		$this->db->join('repositories', 'survey_repos.repositoryid= repositories.repositoryid','INNER');
		$this->db->where('sid',$sid);
		$query=$this->db->get('survey_repos')->result_array();
		
		return $query;
	}

	/**
	*
	* Return an array of survey related countries
	*	
	**/
	function get_survey_countries($sid=NULL)
	{
		if (!is_numeric($sid))
		{
			return FALSE;
		}
		
		$this->db->select('*');
		$this->db->where('sid',$sid);
		$query=$this->db->get('survey_countries')->result_array();
		
		return $query;
	}
	
	/**
	*
	* Increment view by one
	**/
	function increment_study_view_count($id)
	{
		$this->db->query('update surveys set total_views=total_views+1 where id='.$this->db->escape((int)$id) );
	}

	/**
	*
	* Increment download by one
	**/
	function increment_study_download_count($id)
	{
		$this->db->query('update surveys set total_downloads=total_downloads+1 where id='.$this->db->escape((int)$id) );
	}


	/**
	*
	* Returns repositoryid that owns the study
	*
	**/
	function get_study_owner($sid)
	{
		$this->db->select("repositoryid");
		$this->db->where("sid",$sid);
		$this->db->where("isadmin",1);
		$result=$this->db->get("survey_repos");
		
		if (!$result)
		{
			return FALSE;
		}
		
		$result=$result->row_array();
		return $result['repositoryid'];
	}

	
}
