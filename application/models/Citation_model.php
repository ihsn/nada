<?php
class Citation_model extends CI_Model {
 
 	//no. of rows found by search
 	var $search_found_rows=0;
	
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	//search
    function search($limit = NULL, $offset = NULL,$filter=NULL,$sort_by=NULL,$sort_order=NULL,$published=NULL,$repositoryid=NULL)
    {
		$driver=$this->db->dbdriver;

		switch($driver)
		{
			case 'mysql':
			case 'mysqli':
				$this->load->library('citation_search_mysql');
				$result=$this->citation_search_mysql->search($limit, $offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
				$this->search_found_rows=$this->citation_search_mysql->search_found_rows;
				return $result;
				break;
			case 'sqlsrv';
				$this->load->library('citation_search_sqlsrv');
				$result=$this->citation_search_sqlsrv->search($limit, $offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
				$this->search_found_rows=$this->citation_search_sqlsrv->search_found_rows;
				return $result;
				break;
			default:
				$this->load->library('citation_search_sql');
				$result= $this->citation_search_sql->search($limit, $offset,$filter,$sort_by,$sort_order,$published,$repositoryid);
				$this->search_found_rows=$this->citation_search_sql->search_found_rows;
				return $result;
				break;
		}		
    }
	
  	/**
	*
	* Search on citation survey country
	*
	* return arrayy of citation IDs
	*
	* TODO: remove, no longer in use
 	**/
	function search_citation_by_country($keyword=NULL)
	{
		$this->db->select('citationid');
		$this->db->from('survey_citations');
		$this->db->join('surveys', 'survey_citations.sid = surveys.id','inner');
		$this->db->group_by('survey_citations.citationid');
		$this->db->like('surveys.nation',$keyword);
        $query= $this->db->get();

		$output=array();			

		if ($query)
		{
			$result=$query->result_array();
			
			foreach($result as $row)
			{
				$output[]=$row['citationid'];
			}			
		}		

		return $output;
	}
	
    function search_count()
    {
		return $this->search_found_rows;
    }
	
	/**
	*
	* Return IDs for all citations
	**/
	function select_all_to_array()
	{
		$this->db->select("id");
		//$this->db->limit(100);
		$citations=$this->db->get("citations")->result_array();
		
		$output=array();
		foreach($citations as $cit)
		{
			$output[]=$cit["id"];
		}
		
		return $output;
	}
	
	function select_single($id)
	{	
		$this->db->where('id', $id); 
		
		//get citation row
		$query=$this->db->get('citations');
		
		if (!$query){
			return FALSE;
		}
		
		//convert to array
		$row=$query->row_array();
		
		if (!$row){
			return FALSE;
		}
		
		//get related survey
		$row['related_surveys']=$this->get_related_surveys($id);
		
		/*$row['authors']=unserialize($row['authors']);
		$row['editors']=unserialize($row['editors']);
		$row['translators']=unserialize($row['translators']);
		*/

		$row['authors']=$this->get_citation_authors($id,'author');
		$row['editors']=$this->get_citation_authors($id,'editor');
		$row['translators']=$this->get_citation_authors($id,'translator');

        //add created_by and changed_by user names
        if ($row['created_by']){
            $user_created= $this->ion_auth->get_user($row['created_by']);
            $row['created_by_user']=@$user_created->username;
        }

        if ($row['changed_by']){
            $user_changed= $this->ion_auth->get_user($row['changed_by']);
            $row['changed_by_user']=@$user_changed->username;
        }
		return $row;
	}
		
	/**
	*
	* Get citations by Survey
	*
	**/	
	function get_citations_by_survey($surveyid,$sort_by=NULL,$sort_order=NULL)
	{
		$valid_sort=array('title','pub_year','authors');
		$valid_sort_order=array('asc','desc');		
	
		//get citations by the surveyid
		$this->db->select('citations.*');
		$this->db->from('citations');
		$this->db->join('survey_citations', 'survey_citations.citationid = citations.id');
		$this->db->where('survey_citations.sid',$surveyid);
		if(in_array($sort_by,$valid_sort) && in_array($sort_order,$valid_sort_order))
		{
			$this->db->order_by($sort_by, $sort_order);
		}
		else
		{
    		$this->db->order_by("authors, title", "ASC");
		}	
		$query=$this->db->get();

		if (!$query)
		{
			return FALSE;
		}
		
		$citations=$query->result_array();
		
		if ($citations)
		{
        $citation_id_arr=array();

        //create citation ID array
        foreach($citations as $row){
          $citation_id_arr[]=$row['id'];
        }

        //get an array of all authors for all citations
        $authors=$this->get_citation_authors_array($citation_id_arr);

        foreach($citations as $key=>$row){
	  		$cid=$row['id']; //citation id

			if (array_key_exists($cid,$authors)){
				$citations[$key]['authors']=@$authors[$cid]['author'];
				$citations[$key]['editors']=@$authors[$cid]['editor'];
				$citations[$key]['translators']=@$authors[$cid]['translator'];
			}
        }

	return $citations;
	}

	 return FALSE;
	}


	//returns citation authors, editors, translators for a single or multiple citations
	function get_citation_authors_array($citation_id_arr)
	{
		$this->db->select('*');
		$this->db->where_in('cid', $citation_id_arr);
		$query=$this->db->get("citation_authors");

		$output=array();

		if($query){
			$result=$query->result_array();
			foreach($result as $row){
		       	 $output[$row['cid']][$row['author_type']][]=$row;
      			}

			return $output;
		}

		return FALSE;
	}


	/**
	 * 
	 * Returns a list of related surveys by citation id 
	 * 
	 **/
	function get_related_surveys($citationid)
	{
		$this->db->select('surveys.id,surveys.idno,nation,title,year_start,year_end');
		$this->db->join('surveys', 'surveys.id= survey_citations.sid','inner');		
		$this->db->where('citationid', $citationid);
		$this->db->order_by('surveys.nation');
		$this->db->order_by('surveys.title');
		return $this->db->get('survey_citations')->result_array();
	}
	
	/**
	* A list of all surveys that are not already linked to a citation
	*
	*/
	function get_all_surveys($citationid)
	{
		//$remove_list=$this->get_related_surveys($citationid);
		
		$this->db->select('surveys.id,surveys.idno,surveys.title, surveys.nation,year_start,year_end');
		//$this->db->where('citationid!=', $citationid); 		
		return $this->db->get('surveys')->result_array();
	}


	/**
	*	Links the surveys to the citation
	*
	*	@surveys	array of survey id
	*
	*/
	function attach_related_surveys($citationid,$surveys)
	{
		if(empty($surveys)){
			return false;
		}

		$this->db->where('citationid', $citationid); 
		foreach($surveys as $survey)
		{
			$data=array(
					'sid'=>$survey,
					'citationid'=>$citationid
					);
			
			if (!$this->check_survey_exists($citationid,$survey))
			{
				$result=$this->db->insert('survey_citations', $data); 
				
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
		$this->db->where('sid', $surveyid); 
		$this->db->where('citationid', $citationid); 
		return $this->db->get('survey_citations')->result_array();
	}

	/**
	* Delete a single or all related surveys for a citation
	*
	**/
	function delete_related_survey($citationid,$surveyid=NULL)
	{
		if (is_numeric($surveyid))
		{
			$this->db->where('sid', $surveyid); 
		}	
		$this->db->where('citationid', $citationid); 
		return $this->db->delete('survey_citations');
	}
	
	
	function select_all($sort_by='title', $sort_order='ASC')
	{
		$this->db->select('id,title,authors,dcdate,changed');	
		$this->db->order_by($sort_by, $sort_order);
		$this->db->where('published', 1); 
		return $this->db->get('citations')->result_array();
	}
	
	
	/**
	* update 
	*
	*	id			int
	* 	options		array
	**/
	function update($id,$options)
	{
		//allowed fields
		$valid_fields=array(
				'title',
				'subtitle',
				'authors',
				'editors',
				'translators',
				'alt_title',
				'volume',
				'issue',
				'idnumber',
				'edition',
				'changed',
				'created',
				'published',
				'place_publication',
				'place_state',
				'publisher',
				'publication_medium',
				'url',
				'page_from',
				'page_to',
				'data_accessed',
				'pub_day',
				'pub_month',
				'pub_year',
				'organization',
				'ctype',
				'abstract',
				'notes',
				'keywords',
				'doi',
				'flag',
				'owner',
				'uuid',
				'attachment',
                'created_by',
                'changed_by',
				'lang'
			);
		
		$authors=array();
		$editors=array();
		$translators=array();
		
		if (isset($options['authors'])) {
			$authors=$options['authors'];
		}

		if (isset($options['editors'])) {		
			$editors=$options['editors'];
		}
				
		if (isset($options['translators'])) {
			$translators=$options['translators'];
		}
		
		
		//add date modified
		if(!isset($options['changed'])){
			$options['changed']=date("U");
		}

		$options['authors']=$this->authors_to_string($authors);
		$options['translators']=$this->authors_to_string($translators);
		$options['editors']=$this->authors_to_string($editors);

		//pk field name
		$key_field='id';
		
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		//update db
		$this->db->where($key_field, $id);
		
		$result=$this->db->update('citations', $data); 

		if (!$result)
		{
			throw new MY_Exception($this->db->_error_message());
		}
		
		//add authors
		$this->add_authors($id,$authors,"author");

		//add editors
		$this->add_authors($id,$editors,"editor");
				
		//add translators
		$this->add_authors($id,$translators,"translator");
			
		return $result;		
	}


	function update_attachment($id,$attached)
	{
        $this->db->where('id',$id);
        return $this->db->update('citations',$attached);
    }


	/**
	 * 
	 *  Convert authors array to JSON string
	 * 
	 */
	function authors_to_string($authors_array)
	{
		if (!is_array($authors_array)){
			return FALSE;
		}

		//keep only author first and last name
		$authors=array();
		foreach($authors_array as $author){
			$authors[]=array(
				'fname'=>$author['fname'],
				'lname'=>$author['lname'],
				'initial'=>$author['initial']
			);
		}

		return json_encode($authors_array);
	}



	/**
	 * 
	 * Decode JSON encoded authors
	 * 
	 */
	function authors_string_decode($authors)
	{		
		return (array)json_decode($authors,1);
	}



	/**
	*
	* Add citation related authors, editors, translators
	*
	*	@type	author, editor, translator
	**/
	function add_authors($citationid,$authors_array,$type)
	{
		//first remove the existing rows
		$this->db->where('cid', $citationid); 
		$this->db->where('author_type', $type); 
		$deleted=$this->db->delete('citation_authors');	
		
		if (!is_array($authors_array))
		{
			return FALSE;
		}
		
		foreach($authors_array as $author)
		{
			$author=(array)$author;
			$data=array(
					'fname'=>$author['fname'],
					'lname'=>$author['lname'],
					'initial'=>$author['initial'],
					'author_type'=>$type,
					'cid'=>$citationid,
					);
			$result=$this->db->insert('citation_authors', $data);
			
			if ($result===FALSE)
			{
				return FALSE;
			}
		}
		return TRUE;	
	}

	function get_citation_authors($citationid,$type)
	{
		$this->db->select('*'); 
		$this->db->where('cid', $citationid); 
		$this->db->where('author_type', $type); 
		$query=$this->db->get("citation_authors");
		
		if($query)
		{
			return $query->result_array();
		}
		return FALSE;
	}

	/**
	 * 
	 * Generate GUID
	 * @author - http://php.net/manual/en/function.com-create-guid.php#99425
	 * 
	 */
	function GUID()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
	
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	/**
	* add new citation
	*
	* 	options		array
	**/
	function insert($options)
	{
		//allowed fields
		$valid_fields=array(
				'title',
				'subtitle',
				'authors',
				'editors',
				'translators',
				'alt_title',
				'volume',
				'issue',
				'idnumber',
				'edition',
				'changed',
				'created',
				'published',
				'place_publication',
				'place_state',
				'publisher',
				'publication_medium',
				'url',
				'page_from',
				'page_to',
				'data_accessed',
				'pub_day',
				'pub_month',
				'pub_year',
				'organization',
				'ctype',
				'abstract',
				'notes',
				'keywords',
				'doi',
				'flag',
				'owner',
				'uuid',
                'created_by',
				'changed_by',
				'attachment',
				'lang'
			);

		$authors=array();
		$editors=array();
		$translators=array();
		
		if (isset($options['authors'])) {
			$authors=$options['authors'];
		}

		if (isset($options['editors'])) {		
			$editors=$options['editors'];
		}
				
		if (isset($options['translators'])) {
			$translators=$options['translators'];
		}

		//set uuid if not defined
		if(!isset($options['uuid'])){
			$options['uuid']=$this->GUID();
		}


		//add date modified and changed
		$options['changed']=date("U");
		$options['created']=date("U");
		
		$options['authors']=(string)$this->authors_to_string($authors);
		$options['translators']=(string)$this->authors_to_string($translators);
		$options['editors']=(string)$this->authors_to_string($editors);
		
							
		$data=array();
		
		//build update statement
		foreach($options as $key=>$value)
		{
			if (in_array($key,$valid_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		$field_length=array(
			'url'=>255,
			'page_from'=>25,
			'page_to'=>25,
			'title'=>255
		);
		
		//truncate fields
		foreach($field_length as $name=>$length)
		{
			if(isset($data[$name]))
			{
				$data[$name]=substr($data[$name],0,$length);
			}
		}		
		//insert record into db
		$result=$this->db->insert('citations', $data);

		if (!$result){
			throw new MY_Exception('DB_ERROR: '.implode("<BR>",$this->db->error()));
		}
		
		//id for the new citation row
		$id=$this->db->insert_id();
		
		//add authors
		$this->add_authors($id,$authors,"author");

		//add editors
		$this->add_authors($id,$editors,"editor");
				
		//add translators
		$this->add_authors($id,$translators,"translator");
				
		//return the new record
		return $id;
	}



	
	/**
	*
	* Delete citation
	*
	*/
	function delete($id)
	{
		//delete from citations table
        $this->delete_attachment($id);
		$this->db->where('id', $id);
		$deleted=$this->db->delete('citations');
		
		if (!$deleted)
		{
			return FALSE;
		}
		
		//delete survey citations table
		$this->db->where('citationid', $id); 
		$deleted=$this->db->delete('survey_citations');		

		if (!$deleted)
		{
			return FALSE;
		}

		return TRUE;
	}
	
	//TODO:REMOVE - moves authors from the citations table to citation_authors
	function move_citation_authors()
	{
		$this->db->select("*");
		$rows=$this->db->get("citations")->result_array();
		
		foreach($rows as $row)
		{
		/*	$data=array(
					'id'=>$row['id'],
					'authors'=>unserialize($row['authors']),
					);*/
			$this->add_authors($row['id'],unserialize($row['authors']),$type='author');
		}
	}
	
	function update_citation_author_array_tostring()
	{
		$this->db->select("*");
		$rows=$this->db->get("citations")->result_array();
		
		
		foreach($rows as $row)
		{
			$authors=unserialize($row['authors']);
			
			if(is_array($authors))
			{
				$author_string=$this->authors_to_string($authors);

				$data=array('authors'=>$author_string);
				$this->db->where('id',$row['id']);	
				$this->db->update('citations', $data);

			}
		}	
	}
	
	/**
	*
	* Serialize citations by survey
	*
	**/
	function serialize_citations_by_survey($surveyid)
	{
		//get citations by survey
		$citations=$this->get_citations_by_survey($surveyid);

		if (is_array($citations) && count($citations)>0)
		{
			return json_encode($citations);
		}
		
		return FALSE;
	}

	/**
	*
	* Return surveyid by codebook ID
	*
	* @codebookid_arr array of codebook IDs
	**/
	function get_surveyid_by_codebookid($codebookid_arr)
	{
		$this->db->select("id");
		$this->db->where_in("surveyid",$codebookid_arr);
		$result=$this->db->get("surveys")->result_array();
		
		$output=array();
		foreach($result as $row)
		{
			$output[]=$row["id"];
		}
		
		return $output;
	}

	
	
	/**
	* 
	* Return an array of Surveys by survey ID list
	*
	**/
	function get_surveys($id_list)
	{
		if (!is_array($id_list)) {
            return false;
		}

		if (count($id_list)<1){
			return false;
		}
		
        $this->db->select('id,title,idno,nation,repositoryid,year_start,year_end');
        $this->db->where_in('id', $id_list);
        $result = $this->db->get('surveys');

        if ($result) {
            return $result->result_array();
        }
	}
	
	
	
	/**
	*
	* Get citations ID array by Survey
	*
	**/	
	function get_citations_id_array_by_survey($surveyid)
	{
		//get citations by the surveyid
		$this->db->select('citations.id');
		$this->db->from('citations');
		$this->db->join('survey_citations', 'survey_citations.citationid = citations.id');
		$this->db->where('survey_citations.sid',$surveyid);
		$query=$this->db->get();

		if (!$query)
		{
			return FALSE;
		}
		
		$rows= $query->result_array();
		
		$output=array();
		foreach($rows as $row)
		{
			$output[]=$row['id'];
		}
		
		return $output;
	}
	
	function get_citations_count_by_survey_list($sid_list)
	{
		if (!is_array($sid_list)){
			$sid_list=(array)$sid_list;
		}

		if(count($sid_list)==0){
			return false;
		}
	
		$surveys=implode(',',$sid_list);
		$this->db->select('sid,count(sid) as total');
		$this->db->where("sid in ($surveys)");
		$this->db->group_by('sid');
		$result=$this->db->get('survey_citations')->result_array();

		$output=array();
		foreach($result as $row){
			$output[$row['sid']]=$row['total'];
		}

		return $output;
	}



    function get_citations_count_by_survey($sid)
    {
        $this->db->select('count(sid) as total');
        $this->db->where("sid",$sid);
        $result=$this->db->get('survey_citations')->row_array();
        return $result['total'];
    }

	//Finds duplicate or similar citations
	function search_duplicates($keywords)
	{
	    if (!trim($keywords))
	    {
			return FALSE;
	    }

		if ($this->db->dbdriver=='mysql' || $this->db->dbdriver=='mysqli')
		{
			$sql=sprintf("SELECT *, match(title,subtitle,alt_title,authors,editors,translators) against(%s in boolean mode) as score FROM citations ",$this->db->escape($keywords));
			$sql.=sprintf(" WHERE match(title,subtitle,alt_title,authors,editors,translators) against(%s in boolean mode) order by score DESC LIMIT 15;",$this->db->escape($keywords));
		}
		else if ($this->db->dbdriver=='sqlsrv')
		{
			$sql=sprintf("SELECT
					citations.*,
					KEY_TBL.*
					FROM citations
					INNER JOIN
						freetexttable (citations,(ft_keywords),%s,15) as KEY_TBL
					ON citations.id=KEY_TBL.[KEY]
					WHERE KEY_TBL.RANK >=10
					ORDER BY KEY_TBL.RANK DESC;",$this->db->escape($keywords));
		}
		else
		{
			die('citation_model::search_duplicates::database type not supported');
		}
	    //echo $sql;

	    $result=$this->db->query($sql);

	    if(!$result){
			return FALSE;
	    }

		$result=$result->result_array();
		
		foreach($result as $idx=>$row){
			$result[$idx]['authors']=$this->authors_string_decode($row['authors']);
		}

	    return $result;
	}

    /*
    *
    * Returns an array of survey counts per citation
    *
    * @cid citation ID or array of citation IDs
    */
    function get_survey_counts_by_citation($cid)
    {
        if ($cid || !is_array($cid)){
            $cid=(array)$cid;
        }

        $this->db->select('citationid,count(citationid) as survey_count');
        $this->db->where_in(array('citationid' => $cid));
        $this->db->group_by('citationid');
        $result=$this->db->get('survey_citations')->result_array();

		$output=array();

        foreach($result as $row){
            $output[$row['citationid']]=$row['survey_count'];
		}

        return $output;
	}


    //returns a list of users who created/updated citations
    public function get_citations_user_list()
    {
        $this->db->select("users.id,users.username");
        $this->db->where('id in (select changed_by from citations)',NULL,FALSE);
        $result=$this->db->get('users');
        if (!$result){
            return FALSE;
        }

        $users=$result->result_array();
        return $users;
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
     *
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
     *
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
     * Scan all URLs and update the status
     *
     * Status codes= [0=not found; 1=found; 2=unknown error; 3=URL not set]
     *
     * @verbose print messages
     * @limit number of records to process every time this function is called
     * @loop if true, this functions keeps running till there are no more records to process
     * @status_filter array of status codes to be processed. format: array('0','2','3')
     */
    public function batch_update_citation_links($verbose=false,$limit=10,$loop=FALSE,$status_filter=NULL)
    {
        /*
         * 1. fetch 100 citations from the database with url_status_time < 6 days ago
         * 2. update the url_status and url_status_time
         * 3. repeat 1,2 till there are no more records
         * */

        //subtract 6 days
        $time_stamp=strtotime("-1 day");//default 6 days ago

        $this->db->select("id,url");
        $this->db->where("url_status_time <",$time_stamp);
        $this->db->where("url_status",0);
        $this->db->or_where("url_status_time is NULL",NULL,FALSE);
        $this->db->limit($limit);
        $result=$this->db->get("citations")->result_array();

        if (!$result)
        {
            if ($verbose==1) {
                echo "exiting no more records";
                echo "<HR>\r\n";
            }

            return false;
        }

        $k=0;
        foreach($result as $row)
        {
            set_time_limit(0);

            $k++;

            if (!$row['url']){
                $this->update_url_status($row['id'],$status=3);
                continue;
            }

            $status=0;
            $http_status_code=$this->validate_url($row['url']);

            if ($http_status_code=="200")
            {
                $status=1;

                //download the files
                $url=$row['url'];
                $output_file='datafiles/citations/tmp/'.$row['id'].'-'.basename($row['url']);
                $this->curl_download($url,$output_file,$overwrite=FALSE);
            }

            if ($verbose==1) {
                echo $k.": ".$http_status_code.": ".$row['url'];
                echo "<HR>\r\n";
            }

            $this->update_url_status($row['id'],$status);
        }

        if ($loop==true) {
            $this->batch_update_citation_links($verbose,$limit,$loop);
        }

        return count($result);
    }




    /*
     * Update citation url status for a single citation
     * */
    public function update_url_status_single($id)
    {
        $this->db->select("id,url");
        $this->db->where("id",$id);
        $row=$this->db->get("citations")->row_array();

        if (!$row)
        {
            return false;
        }

        if (!$row['url']){
            $this->update_url_status($row['id'],$status=3);
            return;
        }

        //echo $row['url'];
        //echo "<HR>";

        $status=0;
        $http_status_code=$this->validate_url($row['url']);

        if (!$http_status_code){
            $status=2;//no http status code was returned
        }

        if (in_array($http_status_code,array("200","302","301")))
        {
            $status=1;
        }

        $this->update_url_status($row['id'],$status);

        return array(
            'status'=>  $status,
            'url'   =>  $row['url']
        );
    }


    /*
     * Update url status code for citation
     * */
    public function update_url_status($id,$status)
    {
        $options=array(
                'url_status'=>$status,
                'url_status_time'=>date("U")
        );

        $this->db->where("id",$id);
        return $this->db->update("citations",$options);
    }


	/*
     * Returns the http header code for a single link e.g. 200, 404, etc
     * */
    public function validate_url($url)
    {
        //get http headers array
        $headers=$this->get_http_headers($url);
        return $this->get_http_status_code($headers);
	}


    //return the http status code from http headers array
    // @headers http header array
    public function get_http_status_code($headers)
    {
        if (!$headers){
            return FALSE;
        }

        $header_status_index=0;

        //find the last header status code, with redirects there could be multiple values
        foreach ($headers as $key => $val) {
            if (is_int($key)) {
                $header_status_index=$key;
            }
        }

        $http_status=$headers[$header_status_index];
        $http_status_code=substr($http_status, 9, 3);

        return $http_status_code;
    }


    /*
     * Returns the http headers array for the url
     * */
    public function get_http_headers($url)
    {
        //to make a HEAD request instead of GET
        stream_context_set_default(
            array(
                'http' => array(
                    'method' => 'HEAD'
                )
            )
        );

        //replace spaces with %20
        //TODO: find a better solution, see rawurlencode
        $url=str_replace(" ","%20",$url);

        //array of http headers
        return @get_headers($url,1);
    }



    public function curl_download($url,$output_file,$overwrite=FALSE)
    {
        if($overwrite==false && file_exists($output_file))
        {
            return;
        }

        set_time_limit(0);
        $fp = fopen ($output_file, 'w+');//for writing output
        $url=str_replace(" ","%20",$url);
        $ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_FILE, $fp); // write curl response to file
        curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        //curl_setopt($ch, CURLOPT_REFERER, "http://www.example.org");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //execute, results goes in the output file $fp
        $data=curl_exec($ch);
        fwrite($fp, $data);

        //close
        curl_close($ch);
        fclose($fp);

        $result['data'] = $output_file;
        $result['content_type']=curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        return($result);
    }



	//Remove attachment file from citation
	function delete_attachment($citation_id)
	{
        $row=$this->get_attachment($citation_id);

		if (!$row){
			return false;
		}

		//remove attachment file if exists
		if (!$row['attachment'] || trim($row['attachment'])){
			$storage_path=$this->config->item("citations_storage_path");
			$file_path=unix_path($storage_path.'/'.$row['attachment']);

			if (file_exists($file_path) && is_file($file_path)){
				unlink($file_path);
			}
		}

		$this->db->where('id',$citation_id);
        return $this->db->update('citations',array('attachment' => ''));
	}

	//Return attachment info by citation id
	function get_attachment($citation_id)
	{
        $this->db->select('attachment');
		$this->db->where('id',$citation_id);
        return $this->db->get("citations")->row_array();
    }


	/**
	 * 
	 * 
	 * Export citations
	 * 
	 */
	function export($format='csv')
	{
		$this->db->select('citations.*');
		$citations=$this->db->get('citations')->result_array();

		switch($format)
		{
			case 'csv':
				$filename='citations-'.date("m-d-y-his").'.csv';
				header( 'Content-Type: text/csv' );
				header('Content-Encoding: UTF-8');		
				header( 'Content-Disposition: attachment;filename='.$filename);
				$fp = fopen('php://output', 'w');
				
				echo "\xEF\xBB\xBF"; //UTF-8 BOM
		
				//column names
				fputcsv($fp, array_keys($citations[0]));

				foreach($citations as $citation){
					$citation['changed']=date("M-d-y",$citation['changed']);
					$citation['created']=date("M-d-y",$citation['created']);
					fputcsv($fp, $citation);
				}
				
				fclose($fp);
				break;

			case 'json':
				$filename='citations-'.date("m-d-y-his").'.json';
				header( 'Content-Type: application/json');
				header('Content-Encoding: UTF-8');		
				header( 'Content-Disposition: attachment;filename='.$filename);
				echo json_encode($citations);
				break;

			default:
				throw new Exception("UKNOWN_FORMAT: ".$format);		
		}
	}


	/**
	 * 
	 * Return an array of all citations
	 * 
	 * usage as an iterator: 
	 * $iterator = function () {
	 *		for($i=0;$i<=500;$i++){
	 *			$citations=$this->Citation_model->get_all_citations($start=$i*500, $limit=500,$loop=false);
	 *			set_time_limit(0);
	 *			yield $citations;
	 *		}
	 *	};
	 * 
	 * 
	 * @start - starting citation id
	 * @limit - number of rows to return
	 * 
	 * 
	 */
	function get_all_citations($start=0, $limit=100)
	{		
		//citations
		$this->db->select("*");
		$this->db->order_by('id');
		$this->db->limit($limit);

		$this->db->where('id >',$start);
		
		$citations=$this->db->get("citations")->result_array();

		if(!$citations){
			return false;
		}

		//citation id array
		$citation_id_list=array_column($citations,'id');

		//citation surveys
		$this->db->select("survey_citations.citationid as citation_id, surveys.idno");
		$this->db->where_in('citationid',$citation_id_list);
		$this->db->join('surveys', 'survey_citations.sid = surveys.id','inner');
		$result=$this->db->get("survey_citations")->result_array();


		//create array with citation id as key
		$citation_surveys=array();
		foreach($result as $row){
			$citation_surveys[$row['citation_id']][]=$row['idno'];
		}

		//attach related survey IDNO
		foreach($citations  as $idx=>$citation){
			if(array_key_exists($citation['id'],$citation_surveys)){
				$citations[$idx]['related_datasets']=json_encode($citation_surveys[$citation['id']]);
			}

			//decode json fields
			//$citations[$idx]['authors']=json_decode($citation['authors']);
			//$citations[$idx]['editors']=json_decode($citation['editors']);
			//$citations[$idx]['translators']=json_decode($citation['translators']);
		}

		return $citations;
	}


	/**
	 * 
	 * Get count of total citations in db
	 * 
	 */
	function get_citations_count()
	{
		$result=$this->db->query('select count(id) as total from citations')->row_array();
		return $result['total'];
	}

	
	/**
	 * 
	 * Check if UUID already exists and returns the citation ID
	 * 
	 */
	function uuid_exists($uuid)
	{
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
	 * 
	 * 
	 * convert authors, editors, translators to json and add them back to the citation table
	 * 
	 */
	function refresh_author_field($citation_id)
	{
		//get authors, editors, translators
		$authors=$this->get_authors($citation_id);
		
		//update citation author fields
		$this->update_author_field($citation_id,$authors);
	}


	/**
	 * 
	 * Update authors, ediors, translations field for citations table
	 */
	function update_author_field($citation_id, $options)
	{
		$authors=array();
		$editors=array();
		$translators=array();
		
		if (isset($options['authors'])) {
			$authors=$options['authors'];
		}

		if (isset($options['editors'])) {		
			$editors=$options['editors'];
		}
				
		if (isset($options['translators'])) {
			$translators=$options['translators'];
		}

		$options['authors']=$this->authors_to_string($authors);
		$options['translators']=$this->authors_to_string($translators);
		$options['editors']=$this->authors_to_string($editors);

		$this->db->where("id",$citation_id);
		return $this->db->update("citations",$options);
	}


	/**
	 * 
	 * Get authors, translators, editors by citation ID
	 * 
	 */
	function get_authors($citation_id)
	{
		$this->db->select('*'); 
		$this->db->where('cid', $citation_id); 		
		$authors=$this->db->get("citation_authors")->result_array();
		
		$output=array();
		$types=array(
			'author'=>'authors',
			'editor'=>'editors',
			'translator'=>'translators'
		);
		foreach($authors as $author){			
			$output[$types[$author['author_type']]][]=array(
				'fname'=>$author['fname'],
				'lname'=>$author['lname'],
				'initial'=>$author['initial']
			);
		}

		return $output;
		
	}


	/**
	 * 
	 * Convert citation table's author fields to JSON strings
	 * for NADA 4.5 and later versions
	 * 
	 * 
	 */
	function refresh_db_author_fields($offset=0,$limit=0,$verbose=0)
	{
		//get all citations
		$this->db->select('id');
		$this->db->order_by('id');

		if ($offset>0){
			$this->db->where('id>=',$offset,false);
		}

		if ($limit>0){
			$this->db->limit($limit);
		}

		$query=$this->db->get("citations");
		
		
		if(!$query){
			return false;
		}	

		$citations=$query->result_array();

		$k=0;
		foreach($citations as $citation){
			$this->refresh_author_field($citation['id']);
			$k++;
			if($verbose!=0){
				echo $k.":".$citation['id']."\r\n";
			}
		}

		return $k;
	}


	/**
	 * 
	 * 
	 * Import citations from JSON
	 * 
	 * @json_file - JSON file path
	 * @overwrite - overwrite if already exists
	 * @matched - import citations if a matching dataset is found. if set to false, all citations are imported
	 * @verbose - print status message for each citation imported
	 * 
	 * 
	 * NOTE: Requires the library - https://github.com/salsify/jsonstreamingparser
	 * 
	 */
	function import_from_json($json_file, $overwrite=false, $dataset_matched=false,$verbose=false)
	{
		$this->load->model("Dataset_model");

		$stream = fopen($json_file, 'r');
		$listener = new \JsonStreamingParser\Listener\SimpleObjectQueueListener(function($obj) use ($overwrite,$dataset_matched,$verbose) {

			$obj['editors']=json_decode($obj['editors'],true);
			$obj['authors']=json_decode($obj['authors'],true);
			$obj['translators']=json_decode($obj['translators'],true);

			//var_dump($obj);
			//die();

			if(!isset($obj['uuid']) && trim($obj['uuid'])!='') {
				$obj['uuid']=$this->GUID();
			}

			$citation_id=$this->uuid_exists($obj['uuid']);

			$related_survey_uids=array();
			$related_surveys=array();

			if(isset($obj['related_datasets'])){
				$related_survey_uids=json_decode($obj['related_datasets'],true);
				
				foreach($related_survey_uids as $idno){
					$id=$this->Dataset_model->find_by_idno($idno);
					if($id>0){
						$related_surveys[]=$id;
					}
				}	
			}				
			
			//var_dump($related_surveys);
			//die();

			//skip import
			if($dataset_matched==true && empty($related_surveys)){
				echo ($verbose==true) ? $obj['uuid'].' - ' . implode(",", $related_survey_uids). " - skipped - no matching dataset found \r\n" : '';
				return;
			}

			
			if ($citation_id>0 && $overwrite==true){
				$this->update($citation_id,$obj);
				$this->attach_related_surveys($citation_id,$related_surveys);
				echo ($verbose==true) ? $obj['uuid']. " updated\r\n" : '';
			}
			else if(!$citation_id){
				$citation_id=$this->insert($obj);
				$this->attach_related_surveys($citation_id,$related_surveys);
				echo ($verbose==true) ? $obj['uuid']. " imported\r\n" : '';
			}else{
				echo ($verbose==true) ? $obj['uuid']. " skipped\r\n" : '';
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
	 * 
	 * Export all citations to JSON
	 * 
	 * 
	 * Note: requires the library - https://github.com/violet-php/streaming-json-encoder
	 * 
	 */
	function export_to_json_file($file_handle)
	{
		$iterator = function () {
			$total_citations=$this->get_citations_count();
			$max_rows=500;
			$iter_count=ceil($total_citations/$max_rows);

			for($i=0;$i<$iter_count;$i++){
				$citations=$this->Citation_model->get_all_citations($start=$i*$max_rows, $limit=$max_rows);
				set_time_limit(0);
				yield $citations;
			}
		};
		
		//$fp = fopen($output_file, 'wb');

		$encoder = new \Violet\StreamingJsonEncoder\StreamJsonEncoder(
			$iterator,
			function ($json) use ($file_handle) {
				fwrite($file_handle, $json);
			}
		);
		//$encoder->setOptions(JSON_PRETTY_PRINT);
		
		$encoder->encode();
	}


	/**
	 * 
	 * 
	 * Count citations by type
	 * 
	 */
	function get_types_with_count($repo_id=null)
	{
		$this->db->select('ctype, count(citations.id) as total');
		//$this->db->join('survey_citations', 'survey_citations.citationid = citations.id','inner');
		$this->db->where('published',1);
		$this->db->group_by('ctype');

		if($repo_id!=null && $repo_id!=='central'){
			$this->db->join('survey_citations', 'survey_citations.citationid = citations.id','inner');
			$this->db->join('survey_repos', 'survey_citations.sid = survey_repos.sid','inner');
            $this->db->where('survey_repos.repositoryid',$repo_id);
		}

		$result=$this->db->get('citations')->result_array();

		$output=array();
		foreach($result as $row)
		{
			$output[$row['ctype']]=$row['total'];
		}

		return $output;

	}

}
