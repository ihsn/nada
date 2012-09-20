<?php
/**
* Catalog
*
**/
class Catalog_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array('titl', 'nation','proddate', 'authenty');
	
	//fields for the study description
	var $study_fields=array(
					'surveys.id',
					'repositoryid',
					'surveyid',
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
					'isshared',
					'dirpath',
					'ddifilename',
					'link_technical', 
					'link_study',
					'link_report',
					'link_indicator',
					'ddi_sh',
					'link_questionnaire',
					'data_coll_start',
					'data_coll_end',
					'link_da',
					'ie_program', 
					'ie_project_id',
					'ie_project_name',
					'ie_project_uri',
					'ie_team_leaders',
					'project_id',
					'project_name',
					'project_uri',
					'published',
					'created',
					'changed'
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
		$this->db->select('surveys.id,surveys.repositoryid,surveyid,titl, authenty,nation,refno,proddate,
							varcount,link_technical, link_study, link_report, 
							link_indicator, link_questionnaire,	isshared,changed,created,published,data_coll_start');
		
		
		//select form fields
		$this->db->select('forms.model as form_model, forms.path as form_path');		
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
		if ($this->active_repo!=NULL) 
		{
			$this->db->select("sr.repositoryid as repo_link, sr.isadmin as repo_isadmin");
			$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		}	
		$this->db->join('survey_notes notes', 'notes.sid= surveys.id','left');

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
			$this->db->order_by('changed', 'desc'); 
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
		
		$allowed_fields=array('titl', 'surveyid', 'producer', 'sponsor', 'proddate','nation');
		
		if ($this->active_repo!=NULL)
		{
			$allowed_fields['repositoryid']='sr.repositoryid';
		}	
		
		$where=NULL;
		
		if ($fields=='all')
		{			
			$where['field']=$allowed_fields;
			$where['keywords']=$keywords;			
		}
		else if (in_array($fields, $allowed_fields) )
		{
			$where['field']=array($fields);
			$where['keywords']=$keywords;			
		}

		$where_clause='';

		if ($where!=NULL)
		{
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
			
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
		$additional_filters=array('nation','repositoryid');
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
				$where_clause.=' and surveys.repositoryid!='.$this->db->escape($this->active_repo).' and surveys.id not in (select sid from survey_repos where repositoryid='.$this->db->escape($this->active_repo).')';
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
		
		$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		return $this->db->count_all_results('surveys');
    }

	/**
	* returns a single survey row by ID, or surveyid
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
		$fields[]='notes.admin_notes as admin_notes';
		$fields[]='notes.reviewer_notes as reviewer_notes';
		
		//implode
		$fields=implode(",",$fields);	
		
		$this->db->select($fields);
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
		$this->db->join('survey_notes notes', 'notes.sid= surveys.id','left');
		
		if (is_numeric($id))
		{
			$this->db->where('surveys.id', $id); 
		}
		else 
		{	
			//get survey by surveyid
			$this->db->where('surveys.surveyid', $id); 
		}	
		
		//execute query
		$survey=$this->db->get('surveys')->row_array();
		
		if ($repositoryid!==FALSE)
		{
			//get study ownership/link info
			$this->db->select("*");
			$this->db->where('sid', $id); 
			$this->db->where('repositoryid', $repositoryid); 
			$additional=$this->db->get('survey_repos')->result_array();
			if ($additional)
			{
				$survey['repo']=$additional;
			}
		}
		return $survey;
	}
	
	/**
	* returns a single survey by ID with minimum info
	*
	*
	**/
	function get_survey($id)
	{
		$this->db->select('id,titl,surveyid,proddate,nation,repositoryid');
		$this->db->where('id', $id); 
		return $this->db->get('surveys')->row_array();
	}

	/**
	* get variable by varid
	**/
	function get_variable_by_vid($survey_id, $variable_id)
	{
		$this->db->select('uid,name,labl');
		$this->db->where('varID', $variable_id); 
		$this->db->where('surveyid_FK', $survey_id); 
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
	function get_resources_by_survey($surveyid)
	{
		$this->db->select('*');
		$this->db->where('survey_id', $surveyid); 
		return $this->db->get('resources')->result_array();
	}

	/**
	*
	* List of resources grouped by resource-type
	*
	*
	*/
	function get_grouped_resources_by_survey($surveyid)
	{
		$output=FALSE;
		
		//questionnaires
		$result=$this->get_resources_by_type($surveyid,'doc/qst]');
		if ($result)
		{
			$output['questionnaires']=$result;
		}	

		//reports
		$result=$this->get_resources_by_type($surveyid,'doc/rep]');
		if ($result)
		{
			$output['reports']=$result;
		}			
			
		//technical documents
		$result=$this->get_resources_by_type($surveyid,'doc/tec]');
		if ($result)
		{
			$output['technical']=$result;
		}					
		
		//other materials
		$result=$this->get_resources_by_type($surveyid,'other');
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
	function get_citations_by_survey($surveyid)
	{
		$this->db->select('citations.*');
		$this->db->join('survey_citations', 'citations.id= survey_citations.citationid');
		$this->db->where('survey_citations.sid', $surveyid); 
		return $this->db->get('citations')->result_array();
	}

	/**
	*
	* Return resource by survey and resource type
	*
	*/
	function get_resources_by_type($surveyid,$dctype)
	{
		$this->db->select('*');
		$this->db->where('survey_id',$surveyid);
		
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
		$ddi_file=$catalog_root.'/'.$data->dirpath.'/'.$data->ddifilename;

		$ddi_file=unix_path($ddi_file);
		
		if (!file_exists($ddi_file))
		{
			return FALSE;
		}
		
		return $ddi_file;
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
		$this->db->where('id', $id); 
		$deleted=$this->db->delete('surveys');
		
		if ($deleted)
		{
			//remove variables
			$this->db->where('surveyid_fk', $id); 
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
			
			//remove collections
			$this->db->where('sid', $id); 
			$this->db->delete('survey_collections');					

		}		
	}

	/**
	*
	* Update collection years for all the surveys in the database
	* 	 
	*/
	function batch_update_collection_dates()
	{
		//get all surveys
		$this->db->select("id,data_coll_start,data_coll_end");
		$surveys=$this->db->get("surveys")->result_array();
		
		//update/add dates for each survey
		foreach($surveys as $survey)
		{
			$this->update_data_collection_dates($survey["id"], $survey["data_coll_start"], $survey["data_coll_end"]);
		}		
	}

	/**
	*
	* Insert/delete survey data collection date range
	*/	
	function update_data_collection_dates($surveyid,$start,$end)
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
		$this->db->delete('survey_years',array('sid' => $surveyid));

		//insert dates into database
		foreach($years as $year)
		{
			$options=array(
						'sid' => $surveyid,
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
	function get_collections($id)
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
	
	
	/**
	* returns a compact list of all surveys
	*
	**/
	function select_all_compact()
	{
		$this->db->select('id, surveyid,titl,nation');
		$this->db->order_by('nation, titl'); 
		return $this->db->get('surveys')->result_array();		
	}


	/*
====================================================================================================
TO BE REMOVED
*/

	/**
	* Replace a study with another study
	*
	* Replace target survey with source survey
	*
	* @source	Source survey ID
	* @target	Target survey ID
	*/
/*
	function replace($source, $target)
	{
		$debug=array();
		$debug['source'][]=$source;
		$debug['target'][]=$target;		
		
		//get source and target survey info from db	
		$source_survey=(object)$this->select_single($source);
		$target_survey=(object)$this->select_single($target);
		
		//get datasets folder path
		$catalog_root=$this->config->item("catalog_root");

		//get ddi file paths
		$source_ddi_file=$catalog_root.'/'.$source_survey->dirpath.'/'.$source_survey->ddifilename;
		$target_ddi_file=$catalog_root.'/'.$target_survey->dirpath.'/'.$source_survey->ddifilename;
		
		if (!file_exists($source_ddi_file))
		{
			$debug['source-ddi-not-found'][]=$source_ddi_file;
			return $debug;
		}
		
		//get source and target folder paths
		$source_folder=$catalog_root.'/'.$source_survey->dirpath;
		$target_folder=$catalog_root.'/'.$target_survey->dirpath;

		//get a list of all files and folders from the source study
		$files=get_dir_recursive($source_folder,$source_folder);
		
		//modify repositoryid to DUPLICATE FOR source survey, so we can rename the target with the same surveyid
		$this->db->query(sprintf("update surveys set repositoryid='%s' where id=%s",'DUPLICATE',$source));
		//echo '<BR>'.$this->db->last_query();
		$debug['update-surveys']=$this->db->last_query();
		
		//replace target DDI
		$ddi_copied=copy($source_ddi_file,$target_ddi_file);
		$debug['ddi-copied']=$ddi_copied;

		//Copy all folders/files to the target folder overwriting existing files
		
		//copy files/directories to target survey folder
		foreach ($files['folders'] as $folder)
		{
			$folder_path=$target_folder.'/'.$folder;
			if (!file_exists($folder_path))
			{
				$dir_created=@mkdir($folder_path);
				$debug['dir-created'][]=($dir_created===FALSE ? 'FAILED ' : 'COPIED'). ' - '. $folder_path;
			}
		}
		
		//copy files - it will overwrite any existing files
		foreach($files['files'] as $file)
		{
			$file_copied=@copy($source_folder.'/'.$file, $target_folder.'/'.$file);
			$debug['file-copied'][]=($file_copied===FALSE ? 'FAILED ' : 'COPIED'). ' - '. $file;
		}

		//list of study fields
		$db_fields=$this->study_fields;
		
		//update target db info
		$update_options=array();
		
		//fill with source info
		foreach($db_fields as $field)
		{
			$update_options[$field]=$source_survey->{$field};
		}
		unset($update_options['id']);
		
		//update db
		$this->db->where('id',$target);
		$this->db->update('surveys',$update_options);
		//echo $this->db->last_query();	
	
		//delete variables from target
		$this->db->query(sprintf('delete from variables where surveyid_FK=%d',$target));
		$debug['query'][]=$this->db->last_query();
		
		//replace variables
		$this->db->query(sprintf('update variables set surveyid_FK=%d where surveyid_FK=%d',$source,$target));
		$debug['query'][]=$this->db->last_query();
		
		//replace external resources reference
		$this->db->query(sprintf('update resources set survey_id=%d where survey_id=%d',$source,$target));
		$debug['query'][]=$this->db->last_query();
		
		//update topics
		$this->db->query(sprintf('update survey_topics set sid=%d where sid=%d',$source,$target));
		$debug['query'][]=$this->db->last_query();
		
		//update citations
		$this->db->query(sprintf('update survey_citations set sid=%d where sid=%d',$source,$target));
		$debug['query'][]=$this->db->last_query();

		//update collection dates
		$this->db->query(sprintf('update survey_years set sid=%d where sid=%d',$source,$target));
		$debug['query'][]=$this->db->last_query();
		
		return $debug;		
	}
*/
/*
====================================================================================================
END TO BE REMOVED
*/

	
	/**
	*
	* Check if a study has citations
	*
	* returns number of citations per study
	**/
	function has_citations($surveyid)
	{
		$query=$this->db->query('select count(*) as total from survey_citations where sid='.(integer)$surveyid);
		if ($query)
		{
			$row=$query->row_array();
			
			return $row['total'];
		}
		return FALSE;
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
	function copy_study($repositoryid,$sid)
	{
		$options=array(
				'repositoryid'=>$repositoryid,
				'sid'=>$sid,
				'isadmin'=>0
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
	*	@sid	surveyid
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
		
		//remove old ownership info from survey_repos
		$this->unlink_study($survey['repositoryid'],$sid,1);
		
		//remove study link from the target repository
		$this->unlink_study($target_repositoryid,$sid);
				
		//add new ownership info to survey_repos
		$options=array(
				'repositoryid'=>$target_repositoryid,
				'sid'=>$sid,
				'isadmin'=>1 
		);

		$this->db->where('repositoryid',$target_repositoryid);
		$this->db->where('sid',$sid);
		$this->db->insert("survey_repos",$options);
		
		//log
		$this->db_logger->write_log('transfer-ownership','transfered study ['.$survey['titl'].'] from '.$survey['repositoryid'].' '.$target_repositoryid,'transfer-ownership',$sid);
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
}
?>