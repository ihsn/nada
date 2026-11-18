<?php
/**
 * Citations.
 *
 * A citation is a CSL-JSON document in citations.metadata (see application/libraries/Citation_csl.php) plus
 * columns copied out of it for filtering, sorting and full-text search (ctype = CSL type, title,
 * container_title, authors_text, search_text, pub_year, doi, url, lang) and internal columns that are
 * never part of the document (published, flag, notes, owner, url_status, attachment, audit columns).
 *
 * Rows returned to callers are "hydrated": the legacy flat fields (ctype as the NADA type, subtitle,
 * authors/editors/translators as [{fname, lname, initial}], volume, ...) are rebuilt from the document,
 * so the Chicago formatter, the legacy admin form and the public views keep working. The decoded
 * document is in $row['metadata'] and the CSL type in $row['csl_type'].
 *
 * The old columns (subtitle, authors, volume, ...) and the citation_authors table were removed by migration
 * 20261112120001_citations_drop_legacy (their data is in the document, and in the *_legacy_backup tables).
 */
class Citation_model extends CI_Model {

	//no. of rows found by search
	var $search_found_rows=0;

	/** Input of search(): a next_cursor/prev_cursor of an earlier page (nada-ai only, past its offset limit); '' for none. */
	var $search_cursor = '';

	/**
	 * Output of search(): NULL when numbered (offset) pages reach every row (database, Solr); for nada-ai,
	 * {offset, max_offset, next_cursor, prev_cursor} (see Citation_search_nada_ai).
	 */
	var $search_paging = NULL;

	/** Columns of a citation row (the legacy columns are left out on purpose). */
	const ROW_COLUMNS = 'citations.id, citations.uuid, citations.ctype, citations.title, citations.container_title,
		citations.authors_text, citations.pub_year, citations.doi, citations.url, citations.lang, citations.published,
		citations.flag, citations.notes, citations.owner, citations.url_status, citations.url_status_time,
		citations.attachment, citations.created, citations.changed, citations.created_by, citations.changed_by,
		citations.metadata';

	/** Internal columns that can be set on insert/update (besides the document). */
	private static $internal_columns=array(
		'published','flag','notes','owner','attachment','url_status','url_status_time','created_by','changed_by',
	);

	/** Max lengths of internal text columns */
	private static $column_length=array('flag'=>64,'owner'=>255,'attachment'=>300,'url_status'=>50);

	/**
	 * Fields that are editorial/workflow data and must never reach public pages or the public API.
	 */
	public static $internal_fields=array(
		'notes','flag','owner','url_status','url_status_time',
		'created_by','changed_by','created_by_user','changed_by_user',
		'attachment',
	);

	/** Legacy input keys that carry bibliographic data (an update with none of them keeps the document). */
	private static $legacy_doc_keys=array(
		'ctype','title','subtitle','alt_title','authors','editors','translators','volume','issue','idnumber','edition',
		'place_publication','place_state','publisher','publication_medium','url','page_from','page_to','data_accessed',
		'organization','pub_day','pub_month','pub_year','abstract','keywords','doi','lang','country',
	);

	/** CSL keys produced by Citation_csl::from_legacy_row(); other keys of a stored document are kept on a legacy edit. */
	private static $legacy_mapped_csl_keys=array(
		'type','title','container-title','genre','original-title','author','editor','translator','authority',
		'publisher','publisher-place','volume','issue','edition','number','page','issued','accessed','URL','DOI',
		'keyword','abstract','language','medium',
	);

	private $csl=NULL;

	public function __construct()
	{
		parent::__construct();
	}

	/** @return Citation_csl */
	public function csl()
	{
		if ($this->csl===NULL){
			require_once(APPPATH.'libraries/Citation_csl.php');
			$this->csl=new Citation_csl();
		}
		return $this->csl;
	}

	// ---------------------------------------------------------------------------------------------
	// Search
	// ---------------------------------------------------------------------------------------------

	/**
	 * Search citations with the engine that serves them (Search_engine_resolver): nada-ai, Solr or the
	 * database. That is the site's search engine, or the database when the engine has no citation search.
	 *
	 * Filter keys: keywords, ctype[] (CSL or legacy NADA types), from, to, flag[], user[], has_notes,
	 * no_survey_attached, url_status[], include_notes (admin: keywords also match notes).
	 */
	function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
	{
		$this->load->library('search_engine_resolver');
		$this->search_paging = NULL;

		switch ($this->search_engine_resolver->provider_for(Search_engine_resolver::CITATIONS)) {
			case Search_engine_resolver::NADA_AI:
				$this->search_engine_resolver->assert_backend();
				$this->load->library('citation_search_nada_ai');
				$this->citation_search_nada_ai->cursor = $this->search_cursor;
				$result = $this->citation_search_nada_ai->search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
				$this->search_found_rows = $this->citation_search_nada_ai->search_found_rows;
				$this->search_paging = $this->citation_search_nada_ai->paging;
				return $result;

			case Search_engine_resolver::SOLR:
				$this->load->library('citation_search_solr');
				$result = $this->citation_search_solr->search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
				$this->search_found_rows = $this->citation_search_solr->search_found_rows;
				return $result;

			default:
				return $this->search_database($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
		}
	}

	/**
	 * Search with the database driver of this installation (mysql, sqlsrv or the generic sql one).
	 * Also the fallback of Citation_search_nada_ai for what nada-ai does not serve.
	 */
	function search_database($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
	{
		switch($this->db->dbdriver)
		{
			case 'mysql':
			case 'mysqli':
				$library='citation_search_mysql';
				break;
			case 'sqlsrv':
				$library='citation_search_sqlsrv';
				break;
			default:
				$library='citation_search_sql';
		}

		$this->load->library($library);
		$result=$this->$library->search($limit, $offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
		$this->search_found_rows=$this->$library->search_found_rows;
		return $result;
	}

	function search_count()
	{
		return $this->search_found_rows;
	}

	// ---------------------------------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------------------------------

	/**
	 * Hydrated rows for the given ids, in the order given; ids not found are skipped.
	 * Adds survey_count and created_by_user / changed_by_user.
	 */
	function get_rows_by_ids($ids)
	{
		$ids=array_values(array_unique(array_map('intval',(array)$ids)));
		if (empty($ids)){
			return array();
		}

		$this->db->select(self::ROW_COLUMNS.', user_changed.username as changed_by_user, user_created.username as created_by_user', FALSE);
		$this->db->from('citations');
		$this->db->join('users user_created', 'citations.created_by = user_created.id', 'left');
		$this->db->join('users user_changed', 'citations.changed_by = user_changed.id', 'left');
		$this->db->where_in('citations.id', $ids);
		$query=$this->db->get();

		if (!$query){
			return array();
		}

		$by_id=array();
		foreach($query->result_array() as $row){
			$by_id[(int)$row['id']]=$row;
		}

		$counts=$this->get_survey_counts_by_citation(array_keys($by_id));

		$rows=array();
		foreach($ids as $id){
			if (!isset($by_id[$id])){
				continue;
			}
			$row=$this->hydrate_row($by_id[$id]);
			$row['survey_count']=isset($counts[$id]) ? (int)$counts[$id] : 0;
			$rows[]=$row;
		}
		return $rows;
	}

	/**
	 * Row from the citations table -> row with the legacy flat fields rebuilt from the document.
	 */
	function hydrate_row(array $row)
	{
		$doc=isset($row['metadata']) ? $row['metadata'] : NULL;
		if (is_string($doc)){
			$doc=json_decode($doc,true);
		}
		if (!is_array($doc)){
			$doc=$this->csl()->normalize(array(
				'type'=>isset($row['ctype']) ? $row['ctype'] : 'book',
				'title'=>isset($row['title']) ? $row['title'] : '',
			));
		}

		$out=$row;
		foreach($this->csl()->to_legacy_row($doc) as $key=>$value){
			$out[$key]=$value;
		}
		$out['csl_type']=isset($doc['type']) ? $doc['type'] : (isset($row['ctype']) ? $row['ctype'] : '');
		$out['metadata']=array('id'=>isset($row['uuid']) ? (string)$row['uuid'] : '') + $doc;
		return $out;
	}

	function hydrate_rows($rows)
	{
		$out=array();
		foreach((array)$rows as $row){
			$out[]=$this->hydrate_row($row);
		}
		return $out;
	}

	/**
	 * Return IDs for all citations
	 */
	function select_all_to_array()
	{
		$this->db->select("id");
		$citations=$this->db->get("citations")->result_array();
		return array_map('intval', array_column($citations, 'id'));
	}

	function select_single($id)
	{
		if (!is_numeric($id)){
			return FALSE;
		}

		$rows=$this->get_rows_by_ids(array((int)$id));
		if (empty($rows)){
			return FALSE;
		}

		$row=$rows[0];
		$row['related_surveys']=$this->get_related_surveys($id);
		return $row;
	}

	/**
	 * Raw citations row (document decoded), not hydrated. FALSE when not found.
	 */
	function get_row($id)
	{
		$this->db->select(self::ROW_COLUMNS, FALSE);
		$this->db->where('citations.id', (int)$id);
		$row=$this->db->get('citations')->row_array();
		if (!$row){
			return FALSE;
		}
		$doc=json_decode((string)$row['metadata'],true);
		$row['metadata']=is_array($doc) ? $doc : array();
		return $row;
	}

	/**
	 * Get citations by Survey (hydrated rows; FALSE when there are none)
	 */
	function get_citations_by_survey($surveyid,$sort_by=NULL,$sort_order=NULL,$published_only=FALSE)
	{
		$valid_sort=array('title'=>'citations.title','pub_year'=>'citations.pub_year','authors'=>'citations.authors_text');
		$sort_order=strtolower((string)$sort_order);

		$this->db->select(self::ROW_COLUMNS, FALSE);
		$this->db->from('citations');
		$this->db->join('survey_citations', 'survey_citations.citationid = citations.id');
		$this->db->where('survey_citations.sid',(int)$surveyid);
		if ($published_only){
			$this->db->where('citations.published',1);
		}
		if(isset($valid_sort[$sort_by]) && in_array($sort_order,array('asc','desc'),true)){
			$this->db->order_by($valid_sort[$sort_by], $sort_order);
		}
		else{
			$this->db->order_by('citations.authors_text', 'ASC');
			$this->db->order_by('citations.title', 'ASC');
		}
		$query=$this->db->get();

		if (!$query){
			return FALSE;
		}

		$rows=$query->result_array();
		if (empty($rows)){
			return FALSE;
		}

		return $this->hydrate_rows($rows);
	}


	/**
	 * Remove internal fields from a citation row (or a list of rows).
	 */
	function strip_internal_fields($row)
	{
		if (!is_array($row)){
			return $row;
		}

		//list of rows
		if (isset($row[0]) && is_array($row[0])){
			foreach($row as $key=>$item){
				$row[$key]=$this->strip_internal_fields($item);
			}
			return $row;
		}

		foreach(self::$internal_fields as $field){
			unset($row[$field]);
		}

		return $row;
	}

	/**
	 * Returns a list of related surveys by citation id
	 */
	function get_related_surveys($citationid)
	{
		$this->db->select('surveys.id,surveys.idno,nation,title,year_start,year_end,surveys.repositoryid');
		$this->db->join('surveys', 'surveys.id= survey_citations.sid','inner');
		$this->db->where('citationid', (int)$citationid);
		$this->db->order_by('surveys.nation');
		$this->db->order_by('surveys.title');
		return $this->db->get('survey_citations')->result_array();
	}

	// ---------------------------------------------------------------------------------------------
	// Related surveys
	// ---------------------------------------------------------------------------------------------

	/**
	 *	Links the surveys to the citation
	 *
	 *	@surveys	array of survey id
	 */
	function attach_related_surveys($citationid,$surveys)
	{
		if(empty($surveys)){
			return false;
		}

		foreach((array)$surveys as $survey)
		{
			if (!is_numeric($survey)){
				continue;
			}
			if (!$this->check_survey_exists($citationid,$survey))
			{
				$result=$this->db->insert('survey_citations', array(
					'sid'=>(int)$survey,
					'citationid'=>(int)$citationid
				));

				if ($result===FALSE)
				{
					return false;
				}
			}
		}
		return TRUE;
	}

	function check_survey_exists($citationid,$surveyid)
	{
		$this->db->where('sid', (int)$surveyid);
		$this->db->where('citationid', (int)$citationid);
		return $this->db->get('survey_citations')->result_array();
	}

	/**
	 * Delete a single or all related surveys for a citation
	 */
	function delete_related_survey($citationid,$surveyid=NULL)
	{
		if (is_numeric($surveyid))
		{
			$this->db->where('sid', (int)$surveyid);
		}
		$this->db->where('citationid', (int)$citationid);
		return $this->db->delete('survey_citations');
	}

	// ---------------------------------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------------------------------

	/**
	 * Add a citation.
	 *
	 * @param array $options either {metadata: CSL document, ...internal columns} or a legacy flat citation
	 *                       (admin form, import parsers, api/citations v1 body) with internal columns
	 * @return int new id
	 * @throws ValidationException when the document is not valid
	 */
	function insert($options)
	{
		$doc=$this->document_from_input($options, NULL);

		$data=$this->record_columns($doc);
		$data=array_merge($data, $this->internal_columns($options));
		$data['uuid']=isset($options['uuid']) && trim((string)$options['uuid'])!=='' ? substr(trim((string)$options['uuid']),0,50) : $this->GUID();
		$data['created']=date("U");
		$data['changed']=date("U");
		if (!isset($data['published'])){
			$data['published']=0;
		}

		$this->db->trans_start();
		$this->db->insert('citations', $data);
		$id=(int)$this->db->insert_id();
		$this->db->trans_complete();

		if ($this->db->trans_status()===FALSE || $id<1){
			$error=$this->db->error();
			throw new MY_Exception('DB_ERROR: '.$error['message']);
		}

		return $id;
	}

	/**
	 * Update a citation.
	 *
	 * The document is rebuilt only when the input has one: {metadata: ...} replaces it; legacy bibliographic
	 * fields are merged into the current values. Input with only internal columns (published, flag, notes, ...)
	 * leaves the document untouched.
	 *
	 * @throws ValidationException
	 */
	function update($id,$options)
	{
		$current=$this->get_row($id);
		if (!$current){
			throw new MY_Exception('CITATION_NOT_FOUND');
		}

		$data=$this->internal_columns($options, isset($current['flag']) ? (string)$current['flag'] : '');

		if ($this->has_document_input($options)){
			$doc=$this->document_from_input($options, $current['metadata']);
			$data=array_merge($data, $this->record_columns($doc));
		}

		if (isset($options['uuid']) && trim((string)$options['uuid'])!==''){
			$data['uuid']=substr(trim((string)$options['uuid']),0,50);
		}
		$data['changed']=isset($options['changed']) && is_numeric($options['changed']) ? (int)$options['changed'] : date("U");

		$this->db->trans_start();
		$this->db->where('id', (int)$id);
		$this->db->update('citations', $data);
		$this->db->trans_complete();

		if ($this->db->trans_status()===FALSE){
			$error=$this->db->error();
			throw new MY_Exception('DB_ERROR: '.$error['message']);
		}

		return TRUE;
	}

	/**
	 * Set the published flag only.
	 */
	function set_published($id,$published)
	{
		$this->db->where_in('id',array_map('intval',(array)$id));
		return $this->db->update('citations', array(
			'published'=>((int)$published===1) ? 1 : 0,
			'changed'=>date("U"),
		));
	}

	/**
	 * Set the flag only ('' clears it).
	 */
	function set_flag($id,$flag)
	{
		$this->assert_valid_flag($flag);
		$this->db->where_in('id',array_map('intval',(array)$id));
		return $this->db->update('citations', array(
			'flag'=>substr(trim((string)$flag),0,self::$column_length['flag']),
			'changed'=>date("U"),
		));
	}

	function update_attachment($id,$attached)
	{
		$data=array();
		if (isset($attached['attachment'])){
			$data['attachment']=substr((string)$attached['attachment'],0,self::$column_length['attachment']);
		}
		if (empty($data)){
			return FALSE;
		}
		$this->db->where('id',(int)$id);
		return $this->db->update('citations',$data);
	}

	/**
	 * Delete citation (with its study links and attachment file)
	 */
	function delete($id)
	{
		$this->load->library('Search_index_manager');
		$this->search_index_manager->handle_event('citations', $id, 'delete', true);

		$this->delete_attachment($id);

		$this->db->trans_start();
		$this->db->where('citationid', (int)$id);
		$this->db->delete('survey_citations');

		$this->db->where('id', (int)$id);
		$this->db->delete('citations');
		$this->db->trans_complete();

		return $this->db->trans_status()!==FALSE;
	}

	/**
	 * CSL document for an insert/update input (normalized and validated).
	 *
	 * @param array      $options
	 * @param array|null $current_doc the stored document (update); legacy input is merged into it
	 * @throws ValidationException
	 */
	private function document_from_input(array $options, $current_doc)
	{
		$csl=$this->csl();

		if (isset($options['metadata']) && is_array($options['metadata'])){
			$doc=$options['metadata'];
		}
		elseif (!$csl->is_legacy_input($options)){
			$doc=$options;
			foreach(self::$internal_columns as $column){
				unset($doc[$column]);
			}
			unset($doc['uuid'],$doc['created'],$doc['changed'],$doc['related_studies'],$doc['overwrite']);
		}
		else{
			$legacy=$options;
			if (is_array($current_doc) && !empty($current_doc)){
				// fields not in the input keep their current value
				$legacy=array_merge($csl->to_legacy_row($current_doc), $this->legacy_input_fields($options));
			}
			$doc=$csl->from_legacy_row($legacy);

			// CSL-only fields of the stored document (e.g. ISBN set through the API) survive a legacy edit
			if (is_array($current_doc)){
				foreach($current_doc as $key=>$value){
					if ($key==='custom'){
						$other=$value;
						unset($other['nada']);
						if (!empty($other)){
							$doc['custom']=array_merge($other, isset($doc['custom']) ? $doc['custom'] : array());
						}
						continue;
					}
					if (!array_key_exists($key,$doc) && !in_array($key, self::$legacy_mapped_csl_keys, true)){
						$doc[$key]=$value;
					}
				}
			}
		}

		$doc=$csl->normalize($doc);
		$errors=$csl->validate($doc);
		if (!empty($errors)){
			if (!class_exists('ValidationException', FALSE)){
				$this->load->library('form_validation');
			}
			throw new ValidationException('VALIDATION_ERROR: '.implode(', ', array_map(function($k,$v){ return $k.': '.$v; }, array_keys($errors), $errors)), $errors);
		}
		return $doc;
	}

	/** Bibliographic legacy keys present in the input. */
	private function legacy_input_fields(array $options)
	{
		$out=array();
		foreach(self::$legacy_doc_keys as $key){
			if (array_key_exists($key,$options)){
				$out[$key]=$options[$key];
			}
		}
		return $out;
	}

	private function has_document_input(array $options)
	{
		if (isset($options['metadata']) && is_array($options['metadata'])){
			return TRUE;
		}
		if (!$this->csl()->is_legacy_input($options)){
			return TRUE;
		}
		foreach(self::$legacy_doc_keys as $key){
			if (array_key_exists($key,$options)){
				return TRUE;
			}
		}
		return FALSE;
	}

	/** metadata + projected columns for a document */
	private function record_columns(array $doc)
	{
		$data=$this->csl()->project_columns($doc);
		$data['metadata']=json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		return $data;
	}

	/**
	 * Flag must be '' or a code of the citation_flags codelist (Citation_flag_model).
	 * @throws ValidationException
	 */
	private function assert_valid_flag($flag)
	{
		$this->load->model('Citation_flag_model');
		if ($this->Citation_flag_model->is_valid_code($flag)){
			return;
		}
		if (!class_exists('ValidationException', FALSE)){
			$this->load->library('form_validation');
		}
		throw new ValidationException('VALIDATION_ERROR: flag: INVALID_FLAG', array('flag'=>'INVALID_FLAG'));
	}

	/** internal columns present in the input */
	private function internal_columns(array $options, $current_flag=NULL)
	{
		// an unchanged flag is accepted even if it was later removed from the codelist
		if (array_key_exists('flag',$options) && $options['flag']!==NULL
			&& ($current_flag===NULL || trim((string)$options['flag'])!==trim((string)$current_flag))){
			$this->assert_valid_flag($options['flag']);
		}

		$data=array();
		foreach(self::$internal_columns as $column){
			if (!array_key_exists($column,$options)){
				continue;
			}
			$value=$options[$column];
			switch($column){
				case 'published':
					$data[$column]=((int)$value===1) ? 1 : 0;
					break;
				case 'created_by':
				case 'changed_by':
				case 'url_status_time':
					$data[$column]=is_numeric($value) ? (int)$value : NULL;
					break;
				default:
					$value=($value===NULL) ? NULL : trim((string)$value);
					if ($value!==NULL && isset(self::$column_length[$column])){
						$value=substr($value,0,self::$column_length[$column]);
					}
					$data[$column]=$value;
			}
		}
		return $data;
	}

	/**
	 * Generate GUID
	 * @author - http://php.net/manual/en/function.com-create-guid.php#99425
	 */
	function GUID()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	// ---------------------------------------------------------------------------------------------
	// Surveys
	// ---------------------------------------------------------------------------------------------

	/**
	 * Return an array of Surveys by survey ID list
	 */
	function get_surveys($id_list)
	{
		if (!is_array($id_list) || count($id_list)<1){
			return false;
		}

		$this->db->select('id,title,idno,nation,repositoryid,year_start,year_end');
		$this->db->where_in('id', array_map('intval',$id_list));
		$result = $this->db->get('surveys');

		if ($result) {
			return $result->result_array();
		}
		return false;
	}

	/**
	 * Get citations ID array by Survey
	 */
	function get_citations_id_array_by_survey($surveyid)
	{
		$this->db->select('citationid');
		$this->db->where('sid',(int)$surveyid);
		$query=$this->db->get('survey_citations');

		if (!$query)
		{
			return FALSE;
		}

		return array_map('intval', array_column($query->result_array(), 'citationid'));
	}

	/**
	 * Citation counts keyed by survey id.
	 *
	 * @param bool $published_only  TRUE for public catalog badges; FALSE for admin (includes drafts)
	 */
	function get_citations_count_by_survey_list($sid_list, $published_only=FALSE)
	{
		$sid_list=array_filter(array_map('intval',(array)$sid_list));

		if(count($sid_list)==0){
			return false;
		}

		$this->db->select('survey_citations.sid, count(survey_citations.sid) as total');
		$this->db->from('survey_citations');
		if ($published_only){
			$this->db->join('citations', 'citations.id = survey_citations.citationid');
			$this->db->where('citations.published', 1);
		}
		$this->db->where_in('survey_citations.sid', $sid_list);
		$this->db->group_by('survey_citations.sid');
		$result=$this->db->get()->result_array();

		$output=array();
		foreach($result as $row){
			$output[$row['sid']]=$row['total'];
		}

		return $output;
	}

	/**
	 * Count citations linked to a survey.
	 *
	 * @param bool $published_only  TRUE for public study tabs (default); FALSE for admin totals
	 */
	function get_citations_count_by_survey($sid, $published_only=TRUE)
	{
		$this->db->select('count(survey_citations.sid) as total');
		$this->db->from('survey_citations');
		if ($published_only){
			$this->db->join('citations', 'citations.id = survey_citations.citationid');
			$this->db->where('citations.published', 1);
		}
		$this->db->where('survey_citations.sid', (int)$sid);
		$result=$this->db->get()->row_array();
		return isset($result['total']) ? (int)$result['total'] : 0;
	}

	/**
	 * Returns an array of survey counts per citation
	 *
	 * @cid citation ID or array of citation IDs
	 */
	function get_survey_counts_by_citation($cid)
	{
		$cid=array_filter(array_map('intval',(array)$cid));
		if (empty($cid)){
			return array();
		}

		$this->db->select('citationid,count(citationid) as survey_count');
		$this->db->where_in('citationid', $cid);
		$this->db->group_by('citationid');
		$result=$this->db->get('survey_citations')->result_array();

		$output=array();
		foreach($result as $row){
			$output[(int)$row['citationid']]=(int)$row['survey_count'];
		}

		return $output;
	}

	// ---------------------------------------------------------------------------------------------
	// Duplicates
	// ---------------------------------------------------------------------------------------------

	/**
	 * Id of a citation that is the same work as this document: same DOI, or same type, title and year.
	 * FALSE when there is none. Used by the import to skip what is already in the catalogue.
	 */
	function find_duplicate_id(array $doc)
	{
		$cols=$this->csl()->project_columns($doc);

		if ($cols['doi']!==''){
			$this->db->select('id');
			$this->db->where('doi',$cols['doi']);
			$this->db->limit(1);
			$row=$this->db->get('citations')->row_array();
			if ($row){
				return (int)$row['id'];
			}
		}

		if ($cols['title']!==''){
			$this->db->select('id');
			$this->db->where('title',$cols['title']);
			$this->db->where('ctype',$cols['ctype']);
			if ($cols['pub_year']!==NULL){
				$this->db->where('pub_year',$cols['pub_year']);
			}
			else{
				$this->db->where('pub_year IS NULL',NULL,FALSE);
			}
			$this->db->limit(1);
			$row=$this->db->get('citations')->row_array();
			if ($row){
				return (int)$row['id'];
			}
		}

		return FALSE;
	}

	/**
	 * Finds duplicate or similar citations: DOI matches first, then full-text matches on title,
	 * container title and authors. Hydrated rows with a `score`.
	 */
	function search_duplicates($keywords, $doi=NULL)
	{
		$keywords=trim((string)$keywords);
		$doi=$this->csl()->normalize_doi((string)$doi);

		$ids=array();
		$scores=array();

		if ($doi!==''){
			$this->db->select('id');
			$this->db->where('doi',$doi);
			$this->db->limit(15);
			foreach($this->db->get('citations')->result_array() as $row){
				$ids[]=(int)$row['id'];
				$scores[(int)$row['id']]=1000;
			}
		}

		if ($keywords!==''){
			if ($this->db->dbdriver=='mysql' || $this->db->dbdriver=='mysqli')
			{
				$sql=sprintf("SELECT id, MATCH(title,container_title,authors_text) AGAINST(%s) AS score FROM citations
					WHERE MATCH(title,container_title,authors_text) AGAINST(%s) ORDER BY score DESC LIMIT 15",
					$this->db->escape($keywords),$this->db->escape($keywords));
			}
			else if ($this->db->dbdriver=='sqlsrv')
			{
				$sql=sprintf("SELECT citations.id, KEY_TBL.RANK AS score FROM citations
					INNER JOIN FREETEXTTABLE(citations,(title,container_title,authors_text),%s,15) AS KEY_TBL
					ON citations.id=KEY_TBL.[KEY] WHERE KEY_TBL.RANK >= 10 ORDER BY KEY_TBL.RANK DESC",
					$this->db->escape($keywords));
			}
			else
			{
				$sql=sprintf("SELECT id, 1 AS score FROM citations WHERE title LIKE %s ESCAPE '!' ORDER BY id DESC",
					$this->db->escape('%'.$this->db->escape_like_str($keywords).'%'));
			}

			$query=$this->db->query($sql);
			if ($query){
				foreach($query->result_array() as $row){
					$id=(int)$row['id'];
					if (!isset($scores[$id])){
						$ids[]=$id;
						$scores[$id]=(float)$row['score'];
					}
				}
			}
		}

		if (empty($ids)){
			return FALSE;
		}

		$rows=$this->get_rows_by_ids(array_slice($ids,0,15));
		foreach($rows as $k=>$row){
			$rows[$k]['score']=$scores[(int)$row['id']];
		}
		return $rows;
	}

	// ---------------------------------------------------------------------------------------------
	// Lists and stats for the admin
	// ---------------------------------------------------------------------------------------------

	//returns a list of users who created/updated citations
	public function get_citations_user_list()
	{
		$this->db->select("users.id,users.username");
		$this->db->where('(id IN (SELECT changed_by FROM citations WHERE changed_by IS NOT NULL) OR id IN (SELECT created_by FROM citations WHERE created_by IS NOT NULL))',NULL,FALSE);
		$this->db->order_by('users.username');
		$result=$this->db->get('users');
		if (!$result){
			return FALSE;
		}

		return $result->result_array();
	}

	//list of flags used by citations
	public function get_citations_flag_list()
	{
		$this->db->select('flag,count(*) as total');
		$this->db->group_by('flag');
		$result=$this->db->get('citations');

		if (!$result){
			return false;
		}

		return $result->result_array();
	}

	/**
	 * Returns url status summary counts
	 */
	public function get_citations_url_stats()
	{
		$this->db->select('url_status,count(*) as total');
		$this->db->group_by('url_status');
		$result=$this->db->get('citations');

		if (!$result){
			return false;
		}

		return $result->result_array();
	}

	/**
	 * Returns counts of published and unpublished citations
	 */
	public function get_citations_publish_stats()
	{
		$this->db->select('published,count(*) as total');
		$this->db->group_by('published');
		$result=$this->db->get('citations');

		if (!$result){
			return false;
		}

		return $result->result_array();
	}

	/**
	 * Count citations by CSL type (published only), optionally for one collection
	 *
	 * @return array [CSL type => count]
	 */
	function get_types_with_count($repo_id=null)
	{
		$this->db->select('citations.ctype, count(DISTINCT citations.id) as total', FALSE);
		$this->db->from('citations');
		$this->db->where('citations.published',1);

		if($repo_id!=null && $repo_id!=='central'){
			$this->db->join('survey_citations', 'survey_citations.citationid = citations.id','inner');
			$this->db->join('survey_repos', 'survey_citations.sid = survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repo_id);
		}

		$this->db->group_by('citations.ctype');
		$result=$this->db->get()->result_array();

		$output=array();
		foreach($result as $row)
		{
			$output[$row['ctype']]=$row['total'];
		}

		return $output;
	}

	/**
	 * Counts for the admin list filters (all citations, not just the current result): type, flag, published,
	 * link status, year range, and how many have notes or no study.
	 */
	function get_facets()
	{
		$facets=array();

		foreach(array('ctype','flag','published','url_status') as $column){
			$this->db->select($column.' as value, count(*) as total',FALSE);
			$this->db->group_by($column);
			$this->db->order_by('total','DESC');
			$rows=$this->db->get('citations')->result_array();
			$facets[$column]=array();
			foreach($rows as $row){
				$facets[$column][]=array('value'=>$row['value'],'count'=>(int)$row['total']);
			}
		}

		// plain queries: SQL Server does not allow a subquery inside an aggregate
		$row=$this->db->query('SELECT MIN(pub_year) AS year_min, MAX(pub_year) AS year_max, COUNT(*) AS total FROM citations')->row_array();
		$facets['years']=array('min'=>$row['year_min']!==NULL ? (int)$row['year_min'] : NULL,'max'=>$row['year_max']!==NULL ? (int)$row['year_max'] : NULL);
		$facets['total']=(int)$row['total'];

		$row=$this->db->query("SELECT COUNT(*) AS total FROM citations WHERE notes IS NOT NULL AND notes <> ''")->row_array();
		$facets['has_notes']=(int)$row['total'];

		$row=$this->db->query('SELECT COUNT(*) AS total FROM citations WHERE NOT EXISTS (SELECT 1 FROM survey_citations WHERE survey_citations.citationid = citations.id)')->row_array();
		$facets['no_studies']=(int)$row['total'];

		return $facets;
	}

	/**
	 * Store the result of a link check (Citation_url_checker::check())
	 */
	function set_url_status($id,$status)
	{
		$this->db->where('id',(int)$id);
		return $this->db->update('citations',array(
			'url_status'=>(string)(int)$status,
			'url_status_time'=>date("U"),
		));
	}

	// ---------------------------------------------------------------------------------------------
	// Attachments
	// ---------------------------------------------------------------------------------------------

	//Remove attachment file from citation
	function delete_attachment($citation_id)
	{
		$row=$this->get_attachment($citation_id);

		if (!$row){
			return false;
		}

		//remove attachment file if exists
		$attachment=isset($row['attachment']) ? trim((string)$row['attachment']) : '';
		if ($attachment!==''){
			$storage_path=$this->config->item("citations_storage_path");
			$file_path=unix_path($storage_path.'/'.basename($attachment));

			if (file_exists($file_path) && is_file($file_path)){
				unlink($file_path);
			}
		}

		$this->db->where('id',(int)$citation_id);
		return $this->db->update('citations',array('attachment' => ''));
	}

	//Return attachment info by citation id
	function get_attachment($citation_id)
	{
		$this->db->select('attachment');
		$this->db->where('id',(int)$citation_id);
		return $this->db->get("citations")->row_array();
	}

	// ---------------------------------------------------------------------------------------------
	// Export / import
	// ---------------------------------------------------------------------------------------------

	/** Legacy flat fields exported to CSV (besides id, uuid and the internal columns). */
	private static $export_fields=array(
		'ctype','title','subtitle','alt_title','authors','editors','translators','volume','issue','idnumber','edition',
		'place_publication','place_state','publisher','url','page_from','page_to','data_accessed','organization',
		'pub_day','pub_month','pub_year','abstract','keywords','doi','lang',
	);

	/**
	 * Export all citations (CSV, JSON, BibTeX or RIS download)
	 */
	function export($format='csv')
	{
		$types=array('csv'=>'text/csv','json'=>'application/json','bibtex'=>'application/x-bibtex','ris'=>'application/x-research-info-systems');
		$extensions=array('csv'=>'csv','json'=>'json','bibtex'=>'bib','ris'=>'ris');
		if (!isset($types[$format])){
			throw new Exception("UKNOWN_FORMAT: ".$format);
		}

		header('Content-Type: '.$types[$format].'; charset=utf-8');
		header('Content-Disposition: attachment;filename=citations-'.date("m-d-y-his").'.'.$extensions[$format]);

		$this->stream_export($format, fopen('php://output','w'));
	}

	/**
	 * Write all citations (or the given ids) to a stream
	 *
	 * @return int number of citations written
	 */
	function stream_export($format, $fp, $ids=NULL)
	{
		require_once(APPPATH.'libraries/Citation_import_export.php');
		$io=new Citation_import_export();

		if ($ids!==NULL){
			$ids=array_values(array_unique(array_map('intval',(array)$ids)));
			$offset=0;
			$next=function() use (&$offset,$ids){
				$batch=array_slice($ids,$offset,500);
				$offset+=500;
				return empty($batch) ? false : $this->get_export_rows_by_ids($batch);
			};
		}
		else{
			$after_id=0;
			$next=function() use (&$after_id){
				set_time_limit(0);
				$rows=$this->get_all_citations($after_id, 500);
				if ($rows){
					$last=end($rows);
					$after_id=(int)$last['id'];
				}
				return $rows;
			};
		}

		return $io->stream($format, $fp, $next);
	}

	/**
	 * A page of citations for export, keyset-paged on id.
	 *
	 * Each row: id, uuid, csl_type, the legacy flat fields with authors/editors/translators as JSON text
	 * (the format older NADA versions import), metadata (CSL document), internal columns, and
	 * related_datasets (JSON list of study IDNOs).
	 *
	 * @param int $after_id return citations with id > after_id
	 * @param int $limit
	 * @return array|false
	 */
	function get_all_citations($after_id=0, $limit=100)
	{
		$this->db->select(self::ROW_COLUMNS, FALSE);
		$this->db->order_by('id');
		$this->db->limit($limit);
		$this->db->where('id >',(int)$after_id);
		$rows=$this->db->get("citations")->result_array();

		if(!$rows){
			return false;
		}

		return $this->export_rows($rows);
	}

	/**
	 * Export rows (see get_all_citations) for the given citation ids, in id order; FALSE when there are none.
	 */
	function get_export_rows_by_ids($ids)
	{
		$ids=array_values(array_unique(array_filter(array_map('intval',(array)$ids))));
		if (empty($ids)){
			return false;
		}

		$this->db->select(self::ROW_COLUMNS, FALSE);
		$this->db->where_in('id',$ids);
		$this->db->order_by('id');
		$rows=$this->db->get("citations")->result_array();

		return $rows ? $this->export_rows($rows) : false;
	}

	private function export_rows(array $rows)
	{
		$citations=$this->hydrate_rows($rows);
		$citation_id_list=array_column($citations,'id');

		$this->db->select("survey_citations.citationid as citation_id, surveys.idno");
		$this->db->where_in('citationid',$citation_id_list);
		$this->db->join('surveys', 'survey_citations.sid = surveys.id','inner');
		$result=$this->db->get("survey_citations")->result_array();

		$citation_surveys=array();
		foreach($result as $row){
			$citation_surveys[$row['citation_id']][]=$row['idno'];
		}

		foreach($citations as $idx=>$citation){
			foreach(array('id','published','created','changed','created_by','changed_by') as $field){
				if (isset($citation[$field]) && $citation[$field]!==''){
					$citations[$idx][$field]=(int)$citation[$field];
				}
			}
			foreach(array('authors','editors','translators') as $field){
				$citations[$idx][$field]=json_encode($citation[$field], JSON_UNESCAPED_UNICODE);
			}
			unset($citations[$idx]['metadata']['id']);
			if(array_key_exists($citation['id'],$citation_surveys)){
				$citations[$idx]['related_datasets']=json_encode($citation_surveys[$citation['id']]);
			}
		}

		return $citations;
	}

	/**
	 * Get count of total citations in db
	 */
	function get_citations_count()
	{
		$result=$this->db->query('select count(id) as total from citations')->row_array();
		return $result['total'];
	}

	/**
	 * Check if UUID already exists and returns the citation ID
	 */
	function uuid_exists($uuid)
	{
		if ($uuid===NULL || trim((string)$uuid)===''){
			return false;
		}
		$this->db->select("id");
		$this->db->where('uuid',$uuid);
		$this->db->limit(1);
		$result=$this->db->get("citations")->row_array();

		if(isset($result['id'])){
			return $result['id'];
		}

		return false;
	}

	/**
	 * Import citations from a JSON export (export_to_json_file, this or older NADA versions)
	 *
	 * @json_file - JSON file path
	 * @overwrite - overwrite if already exists
	 * @dataset_matched - import only citations with a matching dataset; false imports all
	 * @verbose - print status message for each citation imported
	 *
	 * NOTE: Requires the library - https://github.com/salsify/jsonstreamingparser
	 */
	function import_from_json($json_file, $overwrite=false, $dataset_matched=false,$verbose=false)
	{
		$this->load->model("Dataset_model");

		$stream = fopen($json_file, 'r');
		$listener = new \JsonStreamingParser\Listener\SimpleObjectQueueListener(function($obj) use ($overwrite,$dataset_matched,$verbose) {

			// newer exports carry the CSL document; older ones only the flat fields
			if (isset($obj['metadata']) && is_array($obj['metadata'])){
				$input=array('metadata'=>$obj['metadata']);
				foreach(array('published','flag','notes','owner','uuid') as $key){
					if (isset($obj[$key])){
						$input[$key]=$obj[$key];
					}
				}
			}
			else{
				foreach(array('authors','editors','translators') as $field){
					if (isset($obj[$field]) && is_string($obj[$field])){
						$obj[$field]=json_decode($obj[$field],true);
					}
				}
				unset($obj['id'],$obj['created'],$obj['changed']);
				$input=$obj;
			}

			if(!isset($input['uuid']) || trim((string)$input['uuid'])=='') {
				$input['uuid']=$this->GUID();
			}

			$citation_id=$this->uuid_exists($input['uuid']);

			$related_survey_uids=array();
			$related_surveys=array();

			if(isset($obj['related_datasets'])){
				$related_survey_uids=json_decode($obj['related_datasets'],true);

				foreach((array)$related_survey_uids as $idno){
					$id=$this->Dataset_model->find_by_idno($idno);
					if($id>0){
						$related_surveys[]=$id;
					}
				}
			}

			//skip import
			if($dataset_matched==true && empty($related_surveys)){
				echo ($verbose==true) ? $input['uuid'].' - ' . implode(",", (array)$related_survey_uids). " - skipped - no matching dataset found \r\n" : '';
				return;
			}

			if ($citation_id>0 && $overwrite==true){
				$this->update($citation_id,$input);
				$this->attach_related_surveys($citation_id,$related_surveys);
				echo ($verbose==true) ? $input['uuid']. " updated\r\n" : '';
			}
			else if(!$citation_id){
				$citation_id=$this->insert($input);
				$this->attach_related_surveys($citation_id,$related_surveys);
				echo ($verbose==true) ? $input['uuid']. " imported\r\n" : '';
			}else{
				echo ($verbose==true) ? $input['uuid']. " skipped\r\n" : '';
			}

		});

		try {
			$parser = new \JsonStreamingParser\Parser($stream, $listener);
			$parser->parse();
			fclose($stream);
		} catch (Exception $e) {
			fclose($stream);
			throw $e;
		}
	}

	/**
	 * Export all citations to JSON (see get_all_citations for the row format)
	 */
	function export_to_json_file($file_handle)
	{
		return $this->stream_export('json', $file_handle);
	}

	// ---------------------------------------------------------------------------------------------
	// Validation
	// ---------------------------------------------------------------------------------------------

	/**
	 * Validate API input against the JSON schema: application/schemas/citation-schema.json for the legacy
	 * flat format, citation-csl-schema.json for {metadata: CSL} or a bare CSL document.
	 *
	 * @throws ValidationException
	 */
	function validate_schema($data)
	{
		$this->load->library("Schema_validator");

		if ($this->csl()->is_legacy_input((array)$data)){
			return $this->schema_validator->validate_schema('application/schemas/citation-schema.json',$data);
		}

		$doc=isset($data['metadata']) ? $data['metadata'] : $data;
		return $this->schema_validator->validate_schema('application/schemas/citation-csl-schema.json',$doc);
	}

	function get_id_by_uuid($uuid)
	{
		$this->db->select('id');
		$this->db->where('uuid', $uuid);
		$result=$this->db->get('citations')->row_array();

		if ($result){
			return $result['id'];
		}

		return false;
	}
}
