<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 *
 * Class to manage SOLR index
 *
 *
 */

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;


class Solr_manager{

	var $ci;
	var $solr_config;


	function __construct($params = array())
	{
		$this->ci=& get_instance();
		$this->ci->config->load('solr');
		$this->ci->load->model('solr_delta_updates_model');
		$this->ci->load->model("Dataset_model");
        $this->ci->load->helper('array');
		$this->ci->load->model("Facet_model");

		$this->initialize_solr();

		log_message('debug', "Solr Class Initialized");
		//$this->ci->output->enable_profiler(TRUE);

		ini_set('memory_limit','256M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
		ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
	}



	private function initialize_solr()
	{
		require('vendor/autoload.php');
		$this->solr_config = array(
			'endpoint' => array(
				'localhost' => array(
					'host' => $this->ci->config->item('solr_host'),
					'port' => $this->ci->config->item('solr_port'),
					'path' => '/',
					'core' => $this->ci->config->item('solr_collection'),
				)
			)
		);
		//$this->solr_client = new Solarium\Client($this->solr_config);
	}

	function get_solarium_client()
	{
		$adapter = new Curl();
		$eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
		return new Solarium\Client($adapter,$eventDispatcher, $this->solr_config);
	}


	public function ping_test()
	{
		$client=$this->get_solarium_client();
		$ping = $client->createPing();
		$result = $client->ping($ping);
		return $result->getData();
	}


	/**
	 *
	 * recursive function to import all surveys
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_surveys($start_row=NULL, $limit=10, $loop=TRUE)
	{
		$start_time=date("h:i:s");
        set_time_limit(0);

		//concat('survey-', surveys.id)  as id,
		$this->ci->db->select("
			1 as doctype,
            surveys.id,
            surveys.thumbnail as thumbnail,
            surveys.type as dataset_type,
            surveys.id as survey_uid,
            surveys.idno as idno,
            surveys.formid,        
            forms.model as form_model,    
            surveys.title as title,
            nation,
            surveys.year_start,
            surveys.year_end,
            surveys.repositoryid as repositoryid,
            repositories.title as repo_title,
            surveys.created,
            surveys.changed,
            surveys.varcount,
            surveys.published,
            surveys.total_views,
            surveys.keywords,
            surveys.authoring_entity,
            surveys.total_downloads",FALSE);
        $this->ci->db->join("forms","surveys.formid=forms.formid","left");
        $this->ci->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid','left');
        $this->ci->db->limit($limit);
        $this->ci->db->order_by('surveys.id ASC');

		if ($start_row){
			$this->ci->db->where("surveys.id >",$start_row,false);
		}

		//echo "start time= ".date("H:i:s")."\r\n";		

        $rows=$this->ci->db->get("surveys")->result_array();
		////echo $this->ci->db->last_query();die();
		//echo "\r\n".count($rows). "rows found\r\n";

		//echo "finished reading db rows= ".date("H:i:s")."\r\n";
		//die();

		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		foreach($rows as $key=>$row)
		{
			//survey topics
			//survey countries
			$rows[$key]['countries']=$this->get_survey_countries($row['survey_uid']);
			//survey repositories
			$rows[$key]['repositories']=$this->get_survey_repositories($row['survey_uid']);
			//survey years
            $rows[$key]['years']=$this->get_survey_years($row['survey_uid']);

			$rows[$key]['regions']=$this->get_regions_by_countries($rows[$key]['countries']);

			//custom user defined facets
			$user_facets_by_study=$this->ci->Facet_model->facet_terms_by_study($row['survey_uid']);

			foreach($user_facets_by_study as $facet_name=>$facet_terms){
				$rows[$key]['fq_'.$facet_name]=$facet_terms;
			}
            
            //metadata
			//$rows[$key]['metadata']=array_to_plain_text($this->ci->Dataset_model->decode_metadata($row['metadata']));
			
			//array of variable keywords
			$rows[$key]['var_keywords']=$this->get_survey_variables($row['survey_uid']);

			//row survey id
			$last_row_id=$row['survey_uid'];

			//print_r($row);die();
		}

		$this->add_documents($rows,$id_prefix='survey-');

		//echo "finish time= ".date("H:i:s")."\r\n";

		if ($loop==true){
			//recursively call to fetch next batch of rows
			$this->full_import_surveys($last_row_id,$limit,$loop);
		}

		return array(
			'rows_processed'=>count($rows),
			'last_row_id'=>$last_row_id,
			'start_time'=>$start_time,
			'end_time'=>date("h:i:s")
		);
	}


	function delete_document($query)
	{
		$client=$this->get_solarium_client();
		$update = $client->createUpdate();
		$update->addDeleteQuery($query);
		$update->addCommit();
		$result = $client->update($update);
	}


	//Sync SOLR index with DB
	function sync_solr_with_db($dry_run=false)
	{
			$doctypes=array(1,3);//doc types that will be synced

			foreach($doctypes as $doctype)
			{
				$result=$this->sync_solr_with_db_by_doctype($doctype,$dry_run);

				echo "<pre>";
				print_r($result);
			}

	}


	//Sync SOLR index with DB
	function sync_solr_with_db_by_doctype($doctype,$dry_run=true)
	{
		switch($doctype)
		{
				case '1':
					$prefix='survey';
				break;

				case '2':
					$prefix='v';
				break;

				case '3':
					$prefix='cit';
				break;
		}

		//get document count from SOLR
		$solr_total=$this->get_total_documents_count($doctype);

		//get a list of all documents in SOLR
		$solr_documents=$this->get_all_solr_documents($doctype,$solr_total);

		//get all database records list
		$db_documents=$this->get_all_db_documents($doctype);

		//find deleted records in SOLR index
		$solr_deleted=array_diff($solr_documents,$db_documents);

		//find new documents not in SOLR yet
		$new_documents=array_diff($db_documents,$solr_documents);

		if (count($solr_deleted)>0 && $dry_run!=true){
			//remove deleted items from SOLR
			$delete_query=array();

			foreach($solr_deleted as $del_item){
				$delete_query[]=$prefix.'-'.$del_item;
			}

			$delete_query='id:('.implode(" OR ",$delete_query).')';

			//run delete query
			$this->delete_document($delete_query);
		}

		//add new documents to SOLR
		if (count($new_documents) && $dry_run!=true){
			if ($doctype==1){
				foreach($new_documents as $doc_id)
				{
					$this->add_survey($doc_id);
				}
			}
			else if($doctype==3){
				$this->add_citation($new_documents);
			}
		}

		return array(
				'solr_deleted'=>$solr_deleted,
				'new_docs'=>$new_documents,
				//'solr_documents'=>$solr_documents
		);		
	}



	function get_all_db_documents($doctype=1)
	{
		if ($doctype==1){
			//survey
			$id_column="id";
			$this->ci->db->select("id");
			$rows=$this->ci->db->get("surveys")->result_array();
			return array_column($rows, $id_column);
		}
		else if ($doctype==3){
			//citations
			$id_column="id";
			$this->ci->db->select("id");
			$rows=$this->ci->db->get("citations")->result_array();
			return array_column($rows, $id_column);
		}
	}


	/**
	* get an array of all documents stored in SOLR index
	* returns only the ID field
	**/
	function get_all_solr_documents($doctype=1,$limit=1000)
	{
			$field='id';

			switch($doctype){
				case '1':
					$field='survey_uid';
				break;

				case '2':
					$field='var_uid';
				break;

				case '3':
					$field='citation_id';
				break;
			}

			//var_dump($field);

			$select = array(
			'query'         => '*:*',
			'start'         => 0,
			'rows'          => $limit,
			'fields'        => array($field),
			'filterquery' => array(
					'doctype' => array(
					'query' => 'doctype:'.$doctype
					)
			)
			);

			// create a client instance
			$client=$this->get_solarium_client();

			// get a select query instance based on the config
			$query = $client->createSelect($select);

			// this executes the query and returns the result
			$resultset = $client->select($query);

			// display the total number of documents found by solr
			$documents_found=$resultset->getNumFound();

			$doc_list=array();

			// show documents using the resultset iterator
			foreach ($resultset as $document) {
					$doc_list[]=$document->{$field};
			}

			return $doc_list;
	}



	/**
	* get an array of all documents stored in SOLR index
	* returns only the ID field
	**/
	function get_total_documents_count($doctype=1,$published=null)
	{
		$select = array(
			'query'         => '*:*',
			'start'         => 0,
			'rows'          => 0,
			'fields'        => array('id'),
			'filterquery' 	=> array(
				'doctype' 	=> array(
					'query' => 'doctype:'.$doctype
				)
			)
		);

		if ($published!==NULL){
				$select['filterquery']['published']['query']='published:'.$published;
		}

		$client=$this->get_solarium_client();
		$query = $client->createSelect($select);
		$resultset = $client->select($query);
		$documents_found=$resultset->getNumFound();
		return $documents_found;
	}


	//add documents to index
    function add_documents($rows,$id_prefix='',$apply_commit=false)
    {
        $client=$this->get_solarium_client();
        $update = $client->createUpdate();

        $docs=array();
        foreach($rows as $row)
        {
            $doc=NULL;
            // create a new document for the data
			$doc = $update->createDocument();
			if(isset($row['rownum'])){
				unset($row['rownum']);
			}

			foreach($row as $key=>$value){
				if($key=='id'){
					$value=$id_prefix.$value;
				}
				$doc->setField($key, $value);
			}

            $docs[]=$doc;
        }

        // add the documents and a commit command to the update query
        $update->addDocuments($docs);

		if ($apply_commit){
        	$update->addCommit();
		}

        // this executes the query and returns the result
        $result = $client->update($update);

		/*
		echo " updated applied " .date("H:i:s")."\r\n";
		echo '<b>Update query executed</b><br/>';
		echo 'Query status: ' . $result->getStatus(). '<br/>';
		echo 'Query time: ' . $result->getQueryTime();
		*/
		unset($client);
	}
	

	function commit()
	{
		$client=$this->get_solarium_client();
		$update = $client->createUpdate();
		$update->addCommit();
		$result = $client->update($update);
		unset($client);
		return $result->getStatus();		
	}


	function db_update_handler($options)
	{
		if (!isset($options['table']) &&
				!isset($options['action']) &&
				!isset($options['id'])
			)
		{
				throw exception("DB_UPDATE_HANDER ERROR: options are not set properly");
		}                        

		$table=$options['table'];
		$delta_op=$options['action'];
		$obj_id=$options['id'];
		$is_processed=1;//to mark this is already passed to SOLR index

		$this->ci->solr_delta_updates_model->apply_updates($table, $delta_op, $obj_id, $is_processed);
		$this->run_delta_update($table, $delta_op, $obj_id);
	}



	/**
	*
	* Main function for all delta updates
	*
	* @table - table name
	* @delta_op - operation - refresh, import, replace, update, publish, delete, atomic, facet
	* @obj_id - object id
	*
	**/
	function run_delta_update($table, $delta_op, $obj_id)
	{
		switch($table){
			case 'surveys':
				if(in_array($delta_op, array('refresh','import','replace','update','create','facet') )){
					return $this->add_survey($obj_id);                        
				}
				else if(in_array($delta_op, array('publish','atomic') )){
					return $this->survey_atomic_update($obj_id);
				}
				/*else if(in_array($delta_op, array('facet') )){
					return $this->survey_facets_update($obj_id);
				}*/
				else if($delta_op=='delete'){
					return $this->delete_document("survey_uid:$obj_id OR sid:$obj_id");
				}
			break;

			case 'citations':
				//throw  new exception("update handler not implemented for citations");
			break;
		}
	}


	function delete_document_by_id($id,$doc_type)
	{
		return $this->run_delta_update($table='surveys', 'delete', $id);
	}	
	
	function survey_atomic_update($id)
	{
		$options=$this->get_survey_by_id($id,$inc_keywords=false);
		if($options){
			$this->atomic_update('id','survey-'.$id,$options);
		}
	}

	function atomic_update($key_field,$key_value, $options)
	{
		$client=$this->get_solarium_client();

		$update = $client->createUpdate();
		$doc= $update->createDocument();

		//set key same as unique key in schema.xml
		$doc->setKey($key_field, $key_value);
		
		//set partial update for every field that is given
		foreach($options as $key=>$value) {
			if($key!=$key_field){
				$doc->setField($key, $value);
				$doc->setFieldModifier($key, 'set');
			}
		}

		//add document and commit
		$update->addDocument($doc);
		//$update->addCommit();

		// this executes the query and returns the result
		$result = $client->update($update);
		
		/*
		echo '<pre>';
		print_r($options);
		echo '<b>Update query executed</b><br/>';
		echo 'Query status: ' . $result->getStatus(). '<br/>';
		echo 'Query time: ' . $result->getQueryTime();
		*/
		return $result;
	}


	/**
	 * 
	 * Return a single study
	 * 
	 * @inc_keywords - include study + variable keywords
	 * 
	 */
	public function get_survey_by_id($id,$inc_keywords=true)
	{
		$fields="1 as doctype,
		surveys.id as survey_uid,
		surveys.idno as idno,
		surveys.formid,
		surveys.thumbnail,
		surveys.type as dataset_type,
		surveys.title,
		nation,
		authoring_entity,
		forms.model as form_model,
		surveys.year_start,
		surveys.year_end,					
		surveys.repositoryid as repositoryid,
		link_da,
		repositories.title as repo_title,					
		surveys.created,
		surveys.changed,
		surveys.varcount,
		surveys.published,
		surveys.total_views,		
		surveys.total_downloads";

		if ($inc_keywords==true){
			$fields.=',surveys.keywords';
		}

		//get survey record + study level metadata
		$this->ci->db->select($fields,FALSE);
		$this->ci->db->join("forms","surveys.formid=forms.formid","left");
		$this->ci->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid','left');
		$this->ci->db->where_in("surveys.id",$id);

		$survey=$this->ci->db->get("surveys")->row_array();

		if (!$survey){
			return false;
		}

		$survey['id']='survey-'.$survey['survey_uid']; //id column must use the format SURVEY-1234

		//survey topics
		//survey countries
		$survey['countries']=$this->get_survey_countries($survey['survey_uid']);
		//survey repositories
		$survey['repositories']=$this->get_survey_repositories($survey['survey_uid']);
		//survey years
		$survey['years']=$this->get_survey_years($survey['survey_uid']);
		$survey['regions']=$this->get_regions_by_countries($survey['countries']);
		

		if ($inc_keywords){
			//variable keywords
			$survey['var_keywords']=$this->get_survey_variables($survey['survey_uid']);
		
			//custom user defined facets
			$user_facets_by_study=$this->ci->Facet_model->facet_terms_by_study($survey['survey_uid']);

			foreach($user_facets_by_study as $facet_name=>$facet_terms){
				$survey['fq_'.$facet_name]=$facet_terms;
			}
		}

		//decode metadata and convert to text
		/*if($survey['metadata']){
			$this->ci->load->helper('array');
			$this->ci->load->model("Dataset_model");
			$survey['metadata']=array_to_plain_text($this->ci->Dataset_model->decode_metadata($survey['metadata']));
		}*/
		return $survey;
	}




	public function add_survey($id)
	{
		$documents=array();
		$documents[]=$this->get_survey_by_id($id);		
		$this->add_documents($documents,$id_prefix='',$commit=false);

		//delete variables if exist
		$this->delete_document('sid:'.$id);
		//import survey variables
		$this->add_survey_variables($id);
	}



	public function add_survey_variables($survey_id,$start_row=NULL, $limit=200, $loop=TRUE)
	{
		set_time_limit(0);

		$this->ci->db->select("2 as doctype,
			uid as id,
			fid,
			vid,
			name,
			labl,
			catgry,
			qstn,
			sid,
			uid as var_uid
				",FALSE);
		$this->ci->db->where('sid',$survey_id);
		$this->ci->db->limit($limit);
		$this->ci->db->order_by('uid ASC');

		if ($start_row){
			$this->ci->db->where("uid >",$start_row,false);
		}

		$rows=$this->ci->db->get("variables")->result_array();

		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		//row variable id
		$last_row_id=$rows[ count($rows)-1]['var_uid'];
		$this->add_documents($rows,'v-');
		unset($rows);

		if ($loop){
			//recursively call to fetch next batch of rows
			$this->add_survey_variables($survey_id,$last_row_id,$limit,$loop);
		}

	}


	public function add_citation($id_array)
	{
		$this->ci->db->select("
						3 as doctype,
						id,
						id as citation_id,
						uuid as citation_uuid,
						title,
						subtitle,
						authors,
						volume,
						issue,
						edition,
						place_publication,
						publisher,
						ctype,						
						abstract,
						keywords,
						notes,
						doi,
						published,
						pub_year as pub_date,
						",FALSE);
		$this->ci->db->where_in("id",$id_array);
		$rows=$this->ci->db->get("citations")->result_array();

		//echo "\r\n".count($rows). "rows found\r\n";

		if (!$rows){
			return false;
		}

		$this->add_documents($rows,$_prefix='cit-');
	}



	/**
	 *
	 * remove all documents from index
	 *
	 * other ways to clean index
	 * https://wiki.apache.org/solr/FAQ#How_can_I_delete_all_documents_from_my_index.3F
	 *
	 *
	 * http://localhost:8983/solr/update?stream.body=<delete><query>*:*</query></delete>
	 * http://localhost:8983/solr/update?stream.body=<commit/>
	 *
	 *
	 *
	 **/
	function clean_index()
	{
		// create a client instance
		$client=$this->get_solarium_client();

		// get an update query instance
		$update = $client->createUpdate();

		// add the delete query and a commit command to the update query
		$update->addDeleteQuery('*:*');
		$update->addCommit();

		// this executes the query and returns the result
		$result = $client->update($update);

		return array(
			'status'=>$result->getStatus()
		);
		//echo '<b>Update query executed</b><br/>';
		//echo 'Query status: ' . $result->getStatus(). '<br/>';
		//echo 'Query time: ' . $result->getQueryTime();
	}





	/**
	 *
	 * recursive function to import all variables
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_variables($start_row=NULL, $limit=100, $loop=FALSE)
	{
		$this->ci->load->helper('array');
		$this->ci->load->model("Dataset_model");

		//echo "starting at: ".$start_row."\r\n";
		//echo "before db call " .date("H:i:s")."\r\n";

		set_time_limit(0);

		$this->ci->db->select("
			2 as doctype,
			variables.uid as id,
			variables.fid,
			variables.vid,
			variables.name,
			variables.labl,
			variables.catgry,
			variables.qstn,
			variables.sid,			
			variables.uid as var_uid,
			surveys.idno
			  ",FALSE);
    	$this->ci->db->limit($limit);
		$this->ci->db->join("surveys","surveys.id=variables.sid");
		$this->ci->db->order_by('uid ASC');

		if ($start_row){
			$this->ci->db->where("uid >",$start_row,false);
		}

		//echo "start time= ".date("H:i:s")."\r\n";
		//echo "memory usage before=".$this->convert(memory_get_usage())."\r\n";

		$rows=$this->ci->db->get("variables")->result_array();

		
		/*foreach($rows as $idx=>$row){
			$rows[$idx]['metadata']='';
			if(!empty($row['metadata'])){
				$rows[$idx]['metadata']=array_to_plain_text($this->ci->Dataset_model->decode_metadata($row['metadata']));
			}
		}*/

		//echo "DB results loaded= ".date("H:i:s")."\r\n";
		//echo $this->ci->db->last_query();

		//echo "\r\n".count($rows). "rows found\r\n";
		//echo "memory usage after=".$this->convert(memory_get_usage())."\r\n";


		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		//row variable id
		$last_row_id=$rows[ count($rows)-1]['var_uid'];

		

		//echo "add docs " .date("H:i:s")."\r\n";

		$this->add_documents($rows,$id_prefix='v-');
		$row_count=count($rows);
		unset($rows);


		if ($loop){
			//recursively call to fetch next batch of rows
			$this->full_import_variables($last_row_id,$limit,$loop);
		}

		return array(
			'rows_processed'=>$row_count,
			'last_row_id'=>$last_row_id
		);

	}


	function convert($size)
	{
	    $unit=array('b','kb','mb','gb','tb','pb');
	    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}

	/**
	 *
	 * recursive function to import all citations
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function full_import_citations($start_row=NULL, $limit=100, $loop=TRUE)
	{
		//echo "starting at: ".$start_row."\r\n";

		set_time_limit(0);

		$this->ci->db->select("
						3 as doctype,
						id,
						id as citation_id,
						uuid as citation_uuid,
						title,
						subtitle,
						authors,
						volume,
						issue,
						edition,
						place_publication,
						publisher,
						ctype,						
						abstract,
						keywords,
						notes,
						doi,
						published,
						pub_year as pub_date
					  ",FALSE);
    	$this->ci->db->limit($limit);
		$this->ci->db->order_by('id ASC');

		if ($start_row){
			$this->ci->db->where("id >",$start_row,false);
		}

	  	$rows=$this->ci->db->get("citations")->result_array();

		//echo "\r\n".count($rows). "rows found\r\n";

		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		//row variable id
		$last_row_id=$rows[ count($rows)-1]['citation_id'];

		$this->add_documents($rows,$id_prefix='cit-');

		if($loop ==true){
			$this->full_import_citations($last_row_id,$limit,$loop);
		}

		return array(
			'rows_processed'=>count($rows),
			'last_row_id'=>$last_row_id
		);
	}



	//////////////////////////////////////////////////////////////////////////////////






	function get_survey_countries($sid)
	{
		$this->ci->db->select("cid");
		$this->ci->db->where('sid',$sid);
		$this->ci->db->where('cid >',0,false);
		$result=$this->ci->db->get("survey_countries")->result_array();

		$output=array();

		if($result)
		{
			foreach($result as $row){
				$output[]=$row['cid'];
			}
		}
		return $output;
	}

	function get_regions_by_countries($countries_ids)
	{
		if (!$countries_ids){
			return array();
		}

		$this->ci->db->select("region_id");
		$this->ci->db->where_in('country_id',$countries_ids);
		$this->ci->db->group_by('region_id');
		$result=$this->ci->db->get("region_countries")->result_array();

		foreach($result as $row){
			$output[]=$row['region_id'];
		}

		return $output;
	}


	function get_survey_years($sid)
	{
		$this->ci->db->select("data_coll_year");
		$this->ci->db->where('sid',$sid);
		$result=$this->ci->db->get("survey_years")->result_array();

		$output=array();

		if($result)
		{
			foreach($result as $row){
				$output[]=$row['data_coll_year'];
			}
		}

		return $output;
	}


	function get_survey_repositories($sid)
	{
		$this->ci->db->select("repositoryid");
		$this->ci->db->where('sid',$sid);
		$result=$this->ci->db->get("survey_repos")->result_array();

		$output=array();

		if($result)
		{
			foreach($result as $row){
				$output[]=$row['repositoryid'];
			}
		}

		return $output;
	}

	/*function get_survey_variables($sid)
	{
		$this->ci->db->select("uid,name,labl,qstn,catgry");
		$this->ci->db->where('sid',$sid);
		$this->ci->db->limit(10000);
		$result= $this->ci->db->get("variables")->result_array();

		$output=array();
		foreach($result as $row){
			//$output[]=$row['name']. ' '. $row['labl']. ' ' . $row['qstn'] . ' ' 
			$output[]=implode(" ", array_values($row));
		}
		return implode(" ",$output);
	}*/

	
	function get_survey_variables($sid)
	{
		//max variables to be indexed
		$max_rows=15000;
		
		$limit=500;
		$chunks=ceil($max_rows/$limit);

		$output=array();
		$last_row_id=0;

		for($i=1;$i<=$chunks;$i++){

			$chunked_variables=$this->get_survey_variables_chunked($sid,$start_row=$last_row_id,$limit=500);

			if(!count($chunked_variables) > 0){
				break;
			}			
			
			foreach($chunked_variables as $row){
				$output[]=implode(" ", array_values($row));
				$last_row_id=$row['uid'];
			}
			unset($chunked_variables);

			if($last_row_id==0){
				break;
			}
		}

		return implode(" ",$output);
	}


	function get_survey_variables_chunked($sid,$start_row=0,$limit=500)
	{
		$this->ci->db->select("uid,name,labl,qstn,catgry");
		$this->ci->db->where('sid',$sid);
		$this->ci->db->where('uid>',$start_row);
		$this->ci->db->limit($limit);		
		$result= $this->ci->db->get("variables")->result_array();		
		return $result;
	}


	/**
	 * 
	 * Count all documents from db
	 * 
	 */
	function get_db_counts()
	{	
		$surveys=$this->ci->db->query('select count(id) as total from surveys;')->row_array();
		$variables=$this->ci->db->query('select count(uid) as total from variables;')->row_array();
		$citations=$this->ci->db->query('select count(id) as total from citations;')->row_array();
		
		return array(
			'datasets'=>$surveys['total'],
			'variables'=>$variables['total'],
			'citations'=>$citations['total']
		);
	}

	/**
	 * 
	 * 
	 * Count all documents from solr
	 * 
	 */
	function get_solr_counts()
	{
		$datasets=$this->get_total_documents_count(1);
		$variables=$this->get_total_documents_count(2);
		$citations=$this->get_total_documents_count(3);

		return array(
			'datasets'=>$datasets,
			'variables'=>$variables,
			'citations'=>$citations
		);
	}




/**
	 *
	 * recursive function to import all surveys
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function dataset_to_json($start_row=NULL, $limit=10, $loop=TRUE)
	{
		$start_time=date("h:i:s");
        set_time_limit(0);
        $this->ci->load->model("Dataset_model");
        $this->ci->load->helper('array');

		//concat('survey-', surveys.id)  as id,
		$this->ci->db->select("
			1 as doctype,
            surveys.id,
            surveys.thumbnail as thumbnail,
            surveys.type as dataset_type,
            surveys.id as survey_uid,
            surveys.idno as idno,
            surveys.formid,        
            forms.model as form_model,
            surveys.title as title,
            nation,
            surveys.year_start,
            surveys.year_end,
            surveys.repositoryid as repositoryid,
            repositories.title as repo_title,
            surveys.created,
            surveys.changed,
            surveys.varcount,
            surveys.published,
            surveys.total_views,
			surveys.keywords,
			surveys.metadata,
            surveys.authoring_entity,
            surveys.total_downloads",FALSE);
        $this->ci->db->join("forms","surveys.formid=forms.formid","left");
        $this->ci->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid','left');
        $this->ci->db->limit($limit);
        $this->ci->db->order_by('surveys.id ASC');

		if ($start_row){
			$this->ci->db->where("surveys.id >",$start_row,false);
		}

		//echo "start time= ".date("H:i:s")."\r\n";		

        $rows=$this->ci->db->get("surveys")->result_array();
		////echo $this->ci->db->last_query();die();
		//echo "\r\n".count($rows). "rows found\r\n";

		//echo "finished reading db rows= ".date("H:i:s")."\r\n";
		//die();

		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		foreach($rows as $key=>$row)
		{
			//survey topics
			//survey countries
			$row['countries']=$this->get_survey_countries($row['survey_uid']);
			//survey repositories
			$row['repositories']=$this->get_survey_repositories($row['survey_uid']);
			//survey years
            $row['years']=$this->get_survey_years($row['survey_uid']);
			$row['regions']=$this->get_regions_by_countries($row['countries']);
            
            //metadata
			$row['metadata']=$this->ci->Dataset_model->decode_metadata($row['metadata']);
			
			//array of variable keywords
			$row['var_keywords']=$this->get_survey_variables($row['survey_uid']);

			//row survey id
			$last_row_id=$row['survey_uid'];

			//print_r($row);die();

			//save each document as json file
			file_put_contents($output_file='imports/'.$row['survey_uid'].'.json',json_encode($row,JSON_PRETTY_PRINT));
		}

		//echo "finish time= ".date("H:i:s")."\r\n";

		if ($loop==true){
			//recursively call to fetch next batch of rows
			$this->dataset_to_json($last_row_id,$limit,$loop);
		}

		return array(
			'rows_processed'=>count($rows),
			'last_row_id'=>$last_row_id,
			'start_time'=>$start_time,
			'end_time'=>date("h:i:s")
		);
	}

	

	/**
	* Get the ID of the last row ID
	* returns only the ID field
	**/
	function get_solr_last_row_id($doctype=1)
	{
		
		$client=$this->get_solarium_client();
		$query = $client->createSelect();


		$id_field='survey_uid';

		if($doctype==2){
			$id_field='var_uid';
		}
		else if ($doctype==3){
			$id_field='citation_uuid';
		}

		//get one row
		$query->setRows(1);

		$query->setQuery('doctype:'.$doctype);

		//get only uid field
		$query->setFields(array($id_field));

		//sort
		$query->addSort($id_field, $query::SORT_DESC);

		$resultset = $client->select($query);

		if(!$resultset){
			return false;
		}

		

		// show documents using the resultset iterator
		foreach ($resultset as $document) {

			if (!isset($document[$id_field])){
				var_dump($document);
				die();
			}
			return $document[$id_field];
		}
	}




}// END  class

/* End of file Solr.php */
/* Location: ./application/libraries/Solr.php */