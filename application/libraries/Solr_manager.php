<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Solr Manager Class
 * 
 * Manages Solr indexing operations for surveys, variables, and citations
 * Handles batch imports, individual document operations, and synchronization
 *
 * @author NADA Development Team
 * @version 1.0
 */

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Solr_manager {

    var $ci;
    var $solr_config;


    /**
     * Constructor - Initialize Solr manager
     * @param array $params Optional parameters
     */
    function __construct($params = array())
    {
        $this->ci =& get_instance();
        $this->ci->config->load('solr');
        
        // Load required models
        $this->ci->load->model('solr_delta_updates_model');
        $this->ci->load->model("Dataset_model");
        $this->ci->load->model("catalog_model");
        $this->ci->load->helper('array');
        $this->ci->load->model("Facet_model");

        $this->initialize_solr();

        log_message('debug', "Solr Class Initialized");

        ini_set('memory_limit','256M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
    }

    /**
     * Initialize Solr configuration
     */
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
    }

    /**
     * Get Solarium client instance
     * @return Solarium\Client
     */
    function get_solarium_client()
    {
        $adapter = new Curl();
        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
        return new Solarium\Client($adapter, $eventDispatcher, $this->solr_config);
    }

    /**
     * Get Solarium client for core admin operations (no specific core)
     * @return Solarium\Client
     */
    function get_solarium_admin_client()
    {
        $adapter = new Curl();
        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
        
        $admin_config = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $this->ci->config->item('solr_host'),
                    'port' => $this->ci->config->item('solr_port'),
                    'path' => '/',
                )
            )
        );
        
        return new Solarium\Client($adapter, $eventDispatcher, $admin_config);
    }
    
    /**
     * Get Solr system information including version
     * @return array System information
     */
    public function get_solr_system_info()
    {
        try {
            $solr_host = $this->ci->config->item('solr_host');
            $solr_port = $this->ci->config->item('solr_port');
            $solr_collection = $this->ci->config->item('solr_collection');
            
            //try admin endpoint without collection first (works for Solr 8+)
            $urls = array();
            
            //first try: admin endpoint without collection
            $urls[] = "http://{$solr_host}:{$solr_port}/solr/admin/info/system";
            
            //second try: with collection if configured
            if (!empty($solr_collection)) {
                $urls[] = "http://{$solr_host}:{$solr_port}/solr/{$solr_collection}/admin/info/system";
            }
            
            $last_error = null;
            foreach ($urls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200) {
                    $data = json_decode($response, true);
                    if (isset($data['lucene'])) {
                        return array(
                            'solr_version' => isset($data['lucene']['solr-spec-version']) ? $data['lucene']['solr-spec-version'] : (isset($data['lucene']['solr-impl-version']) ? $data['lucene']['solr-impl-version'] : 'N/A'),
                            'lucene_version' => isset($data['lucene']['lucene-spec-version']) ? $data['lucene']['lucene-spec-version'] : 'N/A',
                            'jvm_version' => isset($data['jvm']['version']) ? $data['jvm']['version'] : 'N/A',
                            'jvm_name' => isset($data['jvm']['name']) ? $data['jvm']['name'] : 'N/A',
                            'system' => isset($data['system']) ? $data['system'] : array()
                        );
                    }
                    return $data;
                } else {
                    $last_error = "HTTP $http_code";
                }
            }
            
            return array(
                'error' => $last_error . ": Failed to get system info from any endpoint"
            );
        } catch (Exception $e) {
            log_message('error', 'Failed to get Solr system info: ' . $e->getMessage());
            return array(
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Test Solr connectivity
     * @return array Ping response data
     */
    public function ping_test()
    {
        $configured_core = $this->ci->config->item('solr_collection');
        
        //first try pinging the configured core
        try {
            $client = $this->get_solarium_client();
            $ping = $client->createPing();
            $result = $client->ping($ping);
            $data = $result->getData();
            $data['configured_core_exists'] = true;
            return $data;
        } catch (Exception $e) {
            //if ping fails with 404, the core might not exist
            $error_msg = $e->getMessage();
            if (strpos($error_msg, '404') !== false || strpos($error_msg, 'not found') !== false) {
                //core doesn't exist, but verify Solr server is accessible
                try {
                    //try to list cores to verify Solr server is running
                    $cores = $this->list_cores();
                    
                    if (!empty($cores)) {
                        $core_name = $cores[0]['name'];
                        $ping_config = array(
                            'endpoint' => array(
                                'localhost' => array(
                                    'host' => $this->ci->config->item('solr_host'),
                                    'port' => $this->ci->config->item('solr_port'),
                                    'path' => '/',
                                    'core' => $core_name,
                                )
                            )
                        );
                        $adapter = new Curl();
                        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
                        $temp_client = new Solarium\Client($adapter, $eventDispatcher, $ping_config);
                        $ping = $temp_client->createPing();
                        $result = $temp_client->ping($ping);
                        $data = $result->getData();
                        $data['note'] = "Configured core '$configured_core' not found. Pinged available core: '$core_name'";
                        $data['configured_core_exists'] = false;
                        $data['configured_core'] = $configured_core;
                        return $data;
                    } else {
                        //no cores exist, but Solr server is accessible
                        return array(
                            'status' => 'OK',
                            'note' => "Solr server is accessible but configured core '$configured_core' does not exist. No cores are currently available.",
                            'configured_core_exists' => false,
                            'configured_core' => $configured_core,
                            'server_accessible' => true,
                            'cores_available' => false
                        );
                    }
                } catch (Exception $e2) {
                    throw new Exception("Solr server may not be accessible or configured core '$configured_core' does not exist. " . 
                        "Original error: " . $error_msg);
                }
            }
            // Re-throw if it's not a 404 error (connection issues, etc.)
            throw $e;
        }
    }


    /**
     * Transform variable fields to use var_ prefix
     * @param array $rows Array of variable rows from database
     * @return array Transformed rows with var_ prefixed fields
     */
    private function map_variable_fields($rows) {
        $transformed_rows = array();
        
        foreach($rows as $row) {
            $transformed_row = array(
                'doctype' => $row['doctype'],
                'id' => $row['id'],
                'vid' => $row['vid'],
                'fid' => $row['fid'],
                'var_name' => $row['name'],
                'var_label' => $row['labl'],
                'var_question' => $row['qstn'],
                'var_survey_id' => $row['sid'],
                'var_uid' => $row['var_uid']
            );
            
            // Add optional fields if they exist
            // if (isset($row['catgry'])) {
            // 	$transformed_row['var_categories'] = $row['catgry'];
            // }
            
            $transformed_rows[] = $transformed_row;
        }
        
        return $transformed_rows;
    }
    

    /**
     * Import surveys in batches
     * @param int $start_row Starting row number
     * @param int $limit Number of records per batch
     * @param bool $loop Whether to continue with next batch
     * @return array|false Import results or false on failure
     */
    public function import_surveys_batch($start_row=NULL, $limit=10, $loop=TRUE)
    {
        if (!is_numeric($start_row)){
            throw new Exception("Start row must be a numeric value");
        }

        if (!is_numeric($limit)){
            throw new Exception("Limit must be a numeric value");
        }

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
                surveys.doi,
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
                surveys.metadata,
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
        //echo $this->ci->db->last_query();die();
        //echo "\r\n".count($rows). "rows found\r\n";

        //echo "finished reading db rows= ".date("H:i:s")."\r\n";
        //die();

        if (!$rows){
            return false;
        }

        $last_row_id=NULL;

        // Batch load survey metadata to avoid N+1 queries (excluding variables for memory safety)
        $survey_ids = array_column($rows, 'survey_uid');
        $batch_countries = $this->batch_load_survey_countries($survey_ids);
        $batch_repositories = $this->batch_load_survey_repositories($survey_ids);
        $batch_years = $this->batch_load_survey_years($survey_ids);
        $batch_user_facets = $this->batch_load_user_facets($survey_ids);

        foreach($rows as $key=>$row)
        {
            //survey topics
            //survey countries - use batch loaded data
            $rows[$key]['countries'] = isset($batch_countries[$row['survey_uid']]) ? $batch_countries[$row['survey_uid']] : array();
            
            //survey repositories - use batch loaded data
            $rows[$key]['repositories'] = isset($batch_repositories[$row['survey_uid']]) ? $batch_repositories[$row['survey_uid']] : array();
            //survey years - use batch loaded data
            $rows[$key]['years'] = isset($batch_years[$row['survey_uid']]) ? $batch_years[$row['survey_uid']] : array();

            $rows[$key]['regions']=$this->derive_regions_from_countries($rows[$key]['countries']);

            //custom user defined facets - use batch loaded data
            $user_facets_by_study = isset($batch_user_facets[$row['survey_uid']]) ? $batch_user_facets[$row['survey_uid']] : array();

            foreach($user_facets_by_study as $facet_name=>$facet_terms){
                $rows[$key]['fq_'.$facet_name]=$facet_terms;
            }

            $metadata=$this->ci->Dataset_model->decode_metadata($row['metadata']);
            
            //metadata
            /*if (!empty($metadata)){
                $rows[$key]['metadata']=array_to_plain_text($metadata);
            }*/
            
            //extract methodology from metadata
            $rows[$key]['methodology']=$this->parse_methodology($metadata);
            
            //array of variable keywords
            $rows[$key]['var_keywords']=$this->load_survey_variable_keywords($row['survey_uid']);
            
            // Add title_sort for sorting (string field with docValues)
            if (isset($rows[$key]['title'])) {
                $rows[$key]['title_sort'] = $rows[$key]['title'];
            }
            
            // Add nation_sort for sorting (string field with docValues)
            if (isset($rows[$key]['nation'])) {
                $rows[$key]['nation_sort'] = $rows[$key]['nation'];
            }

            //row survey id
            $last_row_id=$row['survey_uid'];
        }

        $this->index_documents($rows,$id_prefix='survey-');

        //echo "finish time= ".date("H:i:s")."\r\n";

        if ($loop==true){
            //recursively call to fetch next batch of rows
            $this->import_surveys_batch($last_row_id,$limit,$loop);
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


    // === SYNC & DELTA OPERATIONS ===

    /**
     * Synchronize Solr index with database
     * @param bool $dry_run Whether to perform a dry run
     */
    function synchronize_index($dry_run=false)
    {
            $doctypes=array(1,3);//doc types that will be synced

            foreach($doctypes as $doctype)
            {
                $result=$this->synchronize_by_document_type($doctype,$dry_run);

                echo "<pre>";
                print_r($result);
            }

    }


    /**
     * Synchronize Solr index with database by document type
     * @param int $doctype Document type
     * @param bool $dry_run Whether to perform a dry run
     * @return array Sync results
     */
    function synchronize_by_document_type($doctype,$dry_run=true)
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
        $solr_total=$this->count_documents_by_type($doctype);

        //get a list of all documents in SOLR
        $solr_documents=$this->get_document_ids_by_type($doctype,$solr_total);

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
                    $this->import_single_survey($doc_id);
                }
            }
            else if($doctype==3){
                $this->import_citations($new_documents);
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
     * 
     * Get document IDs by type from Solr
     * 
     * @param int $doctype Document type
     * @param int $limit Maximum number of documents
     * @return array Array of document IDs
     */
    function get_document_ids_by_type($doctype=1,$limit=1000)
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
     * 
     * Count documents by type in Solr
     * @param int $doctype Document type
     * @param int $published Published status filter
     * @return int Document count
     */
    function count_documents_by_type($doctype=1,$published=null)
    {
        try {
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
        } catch (\Exception $e) {
            // Handle all exceptions - check for schema field errors
            $error_msg = $e->getMessage();
            
            $error_body = '';
            if (method_exists($e, 'getBody')) {
                $error_body = $e->getBody();
            }
            
            $is_schema_error = false;
            if (strpos($error_msg, 'undefined field doctype') !== false || 
                strpos($error_msg, 'undefined field') !== false ||
                strpos($error_msg, '"msg":"undefined field') !== false) {
                $is_schema_error = true;
            }
            
            if ($error_body && (strpos($error_body, 'undefined field doctype') !== false || 
                                strpos($error_body, 'undefined field') !== false ||
                                strpos($error_body, '"msg":"undefined field') !== false)) {
                $is_schema_error = true;
            }
            
            if ($is_schema_error) {
                log_message('debug', 'Schema field not found in count_documents_by_type, returning 0. Error: ' . $error_msg);
                return 0;
            }
            
            // Re-throw other errors
            throw $e;
        }
    }


    /**
     * Index documents to Solr
     * @param array $rows Array of document data
     * @param string $id_prefix Prefix for document IDs
     * @param bool $apply_commit Whether to commit changes immediately
     */
    function index_documents($rows,$id_prefix='',$apply_commit=false)
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
    

    /**
     * Commit pending changes to Solr index
     * @return int Status code
     */
    function commit_index_changes()
    {
        $client=$this->get_solarium_client();
        $update = $client->createUpdate();
        $update->addCommit();
        $result = $client->update($update);
        unset($client);
        return $result->getStatus();		
    }


    /**
     * Handle database update events
     * @param array $options Update options
     */
    function handle_database_update($options)
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
        $this->process_delta_update($table, $delta_op, $obj_id);
    }



        /**
     * Process delta update for database changes
     * @param string $table Table name
     * @param string $delta_op Operation type
     * @param int $obj_id Object ID
     */
    function process_delta_update($table, $delta_op, $obj_id)
    {
        switch($table){
            case 'surveys':
                if(in_array($delta_op, array('refresh','import','replace','update','create','facet') )){
                    return $this->import_single_survey($obj_id);                        
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
        return $this->process_delta_update($table='surveys', 'delete', $id);
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
        surveys.doi,
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
        surveys.metadata,
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
        $survey['countries']=$this->load_survey_countries($survey['survey_uid']);
        //survey repositories
        $survey['repositories']=$this->load_survey_repositories($survey['survey_uid']);
        //survey years
        $survey['years']=$this->load_survey_years($survey['survey_uid']);
        $survey['regions']=$this->derive_regions_from_countries($survey['countries']);
        
        //extract methodology from metadata
        $metadata=$this->ci->Dataset_model->decode_metadata($survey['metadata']);
        $survey['methodology']=$this->parse_methodology($metadata);

        if ($inc_keywords){
            //variable keywords
            $survey['var_keywords']=$this->load_survey_variable_keywords($survey['survey_uid']);
        
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
        
        // Add title_sort for sorting (string field with docValues)
        if (isset($survey['title'])) {
            $survey['title_sort'] = $survey['title'];
        }
        
        // Add nation_sort for sorting (string field with docValues)
        if (isset($survey['nation'])) {
            $survey['nation_sort'] = $survey['nation'];
        }
        
        return $survey;
    }



    /**
     * Import a single survey with its variables
     * @param int $id Survey ID
     */
    public function import_single_survey($id)
    {
        $documents=array();
        $documents[]=$this->get_survey_by_id($id);		
        $this->index_documents($documents,$id_prefix='',$commit=false);

        //delete variables if exist
        $this->delete_document('sid:'.$id);
        //import survey variables
        $this->import_survey_variables($id);
    }



    /**
     * Import variables for a specific survey
     * @param int $survey_id Survey ID
     * @param int $start_row Starting row number
     * @param int $limit Number of records per batch
     * @param bool $loop Whether to continue with next batch
     */
    public function import_survey_variables($survey_id,$start_row=NULL, $limit=200, $loop=TRUE)
    {
        set_time_limit(0);

        $this->ci->db->select("2 as doctype,
            uid as id,
            fid,
            vid,
            name,
            labl,
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

        // Transform variable fields to use var_ prefix
        $transformed_rows = $this->map_variable_fields($rows);

        // Add survey metadata if enabled
        $include_metadata = $this->ci->config->item('solr_variable_include_survey_metadata');
        if ($include_metadata) {
            $survey_metadata = $this->load_survey_metadata($survey_id);
            if (!empty($survey_metadata)) {
                foreach ($transformed_rows as &$row) {
                    $row = array_merge($row, $survey_metadata);
                }
                log_message('debug', 'Added survey metadata to ' . count($transformed_rows) . ' variables for survey ' . $survey_id);
            }
        }

        $last_row_id=NULL;

        //row variable id
        $last_row_id=$transformed_rows[ count($transformed_rows)-1]['var_uid'];
        $this->index_documents($transformed_rows,'v-');
        unset($transformed_rows);

        if ($loop){
            //recursively call to fetch next batch of rows
            $this->import_survey_variables($survey_id,$last_row_id,$limit,$loop);
        }

    }


    /**
     * Import all variables for a single survey with survey metadata loaded once
     * @param int $survey_id Survey ID
     * @param int $start_row Starting variable row number (default: 0)
     * @param int $limit Number of variables per batch (default: 200)
     * @param bool $loop Whether to continue with next batch (default: true)
     * @return array|false Import results or false on failure
     */
    public function import_variables_by_survey_batch($survey_id, $start_row=0, $limit=200, $loop=TRUE)
    {
        if (!is_numeric($survey_id)) {
            throw new Exception("Survey ID must be a numeric value");
        }
        
        if (!is_numeric($start_row)) {
            throw new Exception("Start row must be a numeric value");
        }
        
        if (!is_numeric($limit)) {
            throw new Exception("Limit must be a numeric value");
        }

        set_time_limit(0);
        
        // Get total variable count for this survey (for progress tracking)
        $this->ci->db->select('COUNT(*) as total', FALSE);
        $this->ci->db->where('sid', $survey_id);
        $total_variables = $this->ci->db->get("variables")->row()->total;
        
        if ($total_variables == 0) {
            return array(
                'survey_id' => $survey_id,
                'rows_processed' => 0,
                'total_variables' => 0,
                'last_row_id' => NULL,
                'survey_metadata_included' => !empty($survey_metadata),
                'message' => 'No variables found for this survey'
            );
        }
        
        // Load survey metadata once for this survey (if enabled)
        $survey_metadata = array();
        $include_metadata = $this->ci->config->item('solr_variable_include_survey_metadata');
        if ($include_metadata) {
            $survey_metadata = $this->load_survey_metadata($survey_id);
            if (!empty($survey_metadata)) {
                log_message('debug', 'Loaded survey metadata for survey ' . $survey_id . ' - will be applied to all variables');
            }
        }

        // Load variables for this batch
        $this->ci->db->select("2 as doctype,
            uid as id,
            fid,
            vid,
            name,
            labl,
            qstn,
            sid,
            uid as var_uid", FALSE);
        $this->ci->db->where('sid', $survey_id);
        $this->ci->db->limit($limit);
        $this->ci->db->order_by('uid ASC');

        if ($start_row > 0) {
            $this->ci->db->where("uid >", $start_row, false);
        }

        $rows = $this->ci->db->get("variables")->result_array();

        if (!$rows) {
            return array(
                'survey_id' => $survey_id,
                'rows_processed' => 0,
                'total_variables' => $total_variables,
                'last_row_id' => $start_row,
                'survey_metadata_included' => !empty($survey_metadata),
                'message' => 'No more variables to process'
            );
        }

        // Transform variable fields to use var_ prefix
        $transformed_rows = $this->map_variable_fields($rows);

        // Apply survey metadata to all variables in this batch
        if (!empty($survey_metadata)) {
            foreach ($transformed_rows as &$row) {
                $row = array_merge($row, $survey_metadata);
            }
            log_message('debug', 'Applied survey metadata to ' . count($transformed_rows) . ' variables for survey ' . $survey_id);
        }

        $last_row_id = NULL;
        $row_count = count($transformed_rows);

        // Get the last variable ID for next batch
        if ($row_count > 0) {
            $last_row_id = $transformed_rows[$row_count - 1]['var_uid'];
        }

        // Index the variables
        $this->index_documents($transformed_rows, 'v-');
        unset($transformed_rows);

        // Calculate progress
        $processed_so_far = $start_row + $row_count;
        $progress_percentage = round(($processed_so_far / $total_variables) * 100, 2);

        // Continue with next batch if loop is enabled and there are more variables
        if ($loop && $last_row_id) {
            // Check if there are more variables for this survey
            $this->ci->db->select('COUNT(*) as count', FALSE);
            $this->ci->db->where('sid', $survey_id);
            $this->ci->db->where('uid >', $last_row_id, false);
            $remaining_count = $this->ci->db->get("variables")->row()->count;
            
            if ($remaining_count > 0) {
                log_message('debug', 'Survey ' . $survey_id . ': Processed ' . $processed_so_far . '/' . $total_variables . ' variables (' . $progress_percentage . '%) - continuing with next batch');
                
                // Recursively call to fetch next batch of variables for this survey
                $next_result = $this->import_variables_by_survey_batch($survey_id, $last_row_id, $limit, $loop);
                
                // Merge results
                if ($next_result) {
                    $row_count += $next_result['rows_processed'];
                    $last_row_id = $next_result['last_row_id'];
                }
            } else {
                log_message('debug', 'Survey ' . $survey_id . ': Completed processing all ' . $total_variables . ' variables');
            }
        }

        return array(
            'survey_id' => $survey_id,
            'rows_processed' => $row_count,
            'total_variables' => $total_variables,
            'last_row_id' => $last_row_id,
            'survey_metadata_included' => !empty($survey_metadata),
            'progress_percentage' => $progress_percentage,
            'chunk_size' => $limit
        );
    }


    /**
     * Import citations by ID array
     * @param array $id_array Array of citation IDs
     * @return bool Success status
     */
    public function import_citations($id_array)
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

        $this->index_documents($rows,$_prefix='cit-');
    }



    /**
     * Clear entire Solr index
     * @return array Status information
     */
    function clear_index()
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
    /**
     * Import variables in batches
     * @param int $start_row Starting row number
     * @param int $limit Number of records per batch
     * @param bool $loop Whether to continue with next batch
     * @return array|false Import results or false on failure
     */
    public function import_variables_batch($start_row=NULL, $limit=100, $loop=FALSE)
    {
        if (!is_numeric($start_row)){
            throw new Exception("Start row must be a numeric value");
        }

        if (!is_numeric($limit)){
            throw new Exception("Limit must be a numeric value");
        }

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

		        // Transform variable fields to use var_ prefix
        $transformed_rows = $this->map_variable_fields($rows);

		// Add survey metadata if enabled
		$include_metadata = $this->ci->config->item('solr_variable_include_survey_metadata');
		if ($include_metadata) {
			foreach ($transformed_rows as &$row) {
				$survey_id = $row['var_survey_id'];
				                $survey_metadata = $this->load_survey_metadata($survey_id);
				if (!empty($survey_metadata)) {
					$row = array_merge($row, $survey_metadata);
				}
			}
			log_message('debug', 'Added survey metadata to ' . count($transformed_rows) . ' variables');
		}

		//echo "DB results loaded= ".date("H:i:s")."\r\n";
		//echo $this->ci->db->last_query();

		//echo "\r\n".count($transformed_rows). "rows found\r\n";
		//echo "memory usage after=".$this->convert(memory_get_usage())."\r\n";


		if (!$transformed_rows){
			return false;
		}

		$last_row_id=NULL;

		//row variable id
		$last_row_id=$transformed_rows[ count($transformed_rows)-1]['var_uid'];
		//echo "add docs " .date("H:i:s")."\r\n";

		        $this->index_documents($transformed_rows,$id_prefix='v-');
		$row_count=count($transformed_rows);
		unset($transformed_rows);


		        if ($loop){
            //recursively call to fetch next batch of rows
            $this->import_variables_batch($last_row_id,$limit,$loop);
        }

		return array(
			'rows_processed'=>$row_count,
			'last_row_id'=>$last_row_id
		);

	}


    /**
     * Format memory size in human readable format
     * @param int $size Size in bytes
     * @return string Formatted size string
     */
    function format_memory_size($size)
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
    /**
     * Import citations in batches
     * @param int $start_row Starting row number
     * @param int $limit Number of records per batch
     * @param bool $loop Whether to continue with next batch
     * @return array|false Import results or false on failure
     */
    public function import_citations_batch($start_row=NULL, $limit=100, $loop=TRUE)
    {
        if (!is_numeric($start_row)){
            throw new Exception("Start row must be a numeric value");
        }

        if (!is_numeric($limit)){
            throw new Exception("Limit must be a numeric value");
        }

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

		        $this->index_documents($rows,$id_prefix='cit-');

		        if($loop ==true){
            $this->import_citations_batch($last_row_id,$limit,$loop);
        }

		return array(
			'rows_processed'=>count($rows),
			'last_row_id'=>$last_row_id
		);
	}



    //////////////////////////////////////////////////////////////////////////////////






    // === DATA LOADING OPERATIONS ===

    /**
     * Load survey countries for a survey
     * @param int $sid Survey ID
     * @return array Array of country IDs
     */
    function load_survey_countries($sid)
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

    /**
     * Derive regions from country IDs
     * @param array $countries_ids Array of country IDs
     * @return array Array of region IDs
     */
    function derive_regions_from_countries($countries_ids)
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


    /**
     * Load survey years for a survey
     * @param int $sid Survey ID
     * @return array Array of years
     */
    function load_survey_years($sid)
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


    /**
     * Load survey repositories for a survey
     * @param int $sid Survey ID
     * @return array Array of repository IDs
     */
    function load_survey_repositories($sid)
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
    
    /**
     * Batch load survey countries for multiple surveys
     * @param array $survey_ids Array of survey IDs
     * @return array Countries indexed by survey ID
     */
    private function batch_load_survey_countries($survey_ids)
    {
        if (empty($survey_ids)) {
            return array();
        }
        
        $this->ci->db->select('sid, cid');
        $this->ci->db->where_in('sid', $survey_ids);
        $this->ci->db->where('cid >', 0, false);
        $result = $this->ci->db->get('survey_countries')->result_array();
        
        $countries_by_survey = array();
        foreach ($result as $row) {
            if (!isset($countries_by_survey[$row['sid']])) {
                $countries_by_survey[$row['sid']] = array();
            }
            $countries_by_survey[$row['sid']][] = $row['cid'];
        }
        
        return $countries_by_survey;
    }
    
    /**
     * Batch load survey repositories for multiple surveys
     * @param array $survey_ids Array of survey IDs
     * @return array Repositories indexed by survey ID
     */
    private function batch_load_survey_repositories($survey_ids)
    {
        if (empty($survey_ids)) {
            return array();
        }
        
        $this->ci->db->select('sid, repositoryid');
        $this->ci->db->where_in('sid', $survey_ids);
        $result = $this->ci->db->get('survey_repos')->result_array();
        
        $repos_by_survey = array();
        foreach ($result as $row) {
            if (!isset($repos_by_survey[$row['sid']])) {
                $repos_by_survey[$row['sid']] = array();
            }
            $repos_by_survey[$row['sid']][] = $row['repositoryid'];
        }
        
        return $repos_by_survey;
    }
    
    /**
     * Batch load survey years for multiple surveys
     * @param array $survey_ids Array of survey IDs
     * @return array Years indexed by survey ID
     */
    private function batch_load_survey_years($survey_ids)
    {
        if (empty($survey_ids)) {
            return array();
        }
        
        $this->ci->db->select('sid, data_coll_year');
        $this->ci->db->where_in('sid', $survey_ids);
        $this->ci->db->where('data_coll_year >', 0);
        $result = $this->ci->db->get('survey_years')->result_array();
        
        $years_by_survey = array();
        foreach ($result as $row) {
            if (!isset($years_by_survey[$row['sid']])) {
                $years_by_survey[$row['sid']] = array();
            }
            $years_by_survey[$row['sid']][] = $row['data_coll_year'];
        }
        
        return $years_by_survey;
    }
    
    /**
     * Batch load user facets for multiple surveys
     * @param array $survey_ids Array of survey IDs
     * @return array Facets indexed by survey ID
     */
    private function batch_load_user_facets($survey_ids)
    {
        if (empty($survey_ids)) {
            return array();
        }
        
        $facets_by_survey = array();
        
        // Check if Facet_model has a batch method
        if (method_exists($this->ci->Facet_model, 'facet_terms_by_studies')) {
            // Use batch method if available
            $facets_by_survey = $this->ci->Facet_model->facet_terms_by_studies($survey_ids);
        } else {
            // Fallback to individual calls but still batch the database layer
            // This is a compromise - we'll still make individual calls but optimize the database layer
            foreach ($survey_ids as $survey_id) {
                $user_facets = $this->ci->Facet_model->facet_terms_by_study($survey_id);
                $facets_by_survey[$survey_id] = $user_facets;
            }
        }
        
        return $facets_by_survey;
    }
    
    /**
     * Batch load survey variables for multiple surveys
     * @param array $survey_ids Array of survey IDs
     * @return array Variable keywords indexed by survey ID
     */
    private function batch_load_survey_variables($survey_ids)
    {
        if (empty($survey_ids)) {
            return array();
        }
        
        $variables_by_survey = array();
        
        // Batch load variables for all surveys in one query
        $this->ci->db->select('sid, uid, name, labl, qstn');
        $this->ci->db->where_in('sid', $survey_ids);
        $this->ci->db->limit(15000); // Limit to prevent memory issues
        $result = $this->ci->db->get('variables')->result_array();
        
        // Group variables by survey ID
        foreach ($result as $row) {
            if (!isset($variables_by_survey[$row['sid']])) {
                $variables_by_survey[$row['sid']] = '';
            }
            $variables_by_survey[$row['sid']] .= ' ' . implode(' ', array_values($row));
        }
        
        // Trim whitespace
        foreach ($variables_by_survey as $survey_id => $keywords) {
            $variables_by_survey[$survey_id] = trim($keywords);
        }
        
        return $variables_by_survey;
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

    
    /**
     * Load survey variable keywords
     * @param int $sid Survey ID
     * @return string Concatenated variable keywords
     */
    function load_survey_variable_keywords($sid)
    {
        //max variables to be indexed
        $max_rows=15000;
        
        $limit=500;
        $chunks=ceil($max_rows/$limit);

        $output=array();
        $last_row_id=0;

        for($i=1;$i<=$chunks;$i++){
            $chunked_variables=$this->load_survey_variables_chunked($sid,$start_row=$last_row_id,$limit=500);

            if(!count($chunked_variables) > 0){
                break;
            }			
            
            foreach($chunked_variables as $row){
                $output[]=implode(" ", array_values($row));
                $last_row_id=$row['var_uid'];
            }
            unset($chunked_variables);

            if($last_row_id==0){
                break;
            }
        }

        return implode(" ",$output);
    }


    /**
     * Load survey variables in chunks
     * @param int $sid Survey ID
     * @param int $start_row Starting row number
     * @param int $limit Number of records per chunk
     * @return array Array of variable data
     */
    function load_survey_variables_chunked($sid,$start_row=0,$limit=500)
    {
        $this->ci->db->select("uid,name,labl,qstn");
        $this->ci->db->where('sid',$sid);
        $this->ci->db->where('uid>',$start_row);
        $this->ci->db->limit($limit);		
        $result= $this->ci->db->get("variables")->result_array();
        
        // Transform variable fields to use var_ prefix
        $transformed_result = array();
        foreach($result as $row){
            $transformed_row = array(
                'var_name' => $row['name'],
                'var_label' => $row['labl'],
                'var_question' => $row['qstn'],
                'var_uid' => $row['uid']
            );
            
            // Add optional fields if they exist
            // if (isset($row['catgry'])) {
            // 	$transformed_row['var_categories'] = $row['catgry'];
            // }
            
            $transformed_result[] = $transformed_row;
        }
        
        return $transformed_result;
    }


    /**
     * Count all documents from database
     * @return array Counts by document type
     */
    function count_database_records()
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
     * Count all documents from Solr
     * @return array Counts by document type
     */
    function count_solr_records()
    {
        try {
            $datasets=$this->count_documents_by_type(1);
            $variables=$this->count_documents_by_type(2);
            $citations=$this->count_documents_by_type(3);

            return array(
                'datasets'=>$datasets,
                'variables'=>$variables,
                'citations'=>$citations,
                'last_dataset'=>null,
                'last_variable'=>null
            );
        } catch (\Exception $e) {
            // Handle all exceptions - check for schema field errors
            $error_msg = $e->getMessage();
            
            // Try to get body if it's an HttpException
            $error_body = '';
            if (method_exists($e, 'getBody')) {
                $error_body = $e->getBody();
            }
            
            // Combine message and body for checking
            $full_error = $error_msg . ($error_body ? "\n" . $error_body : '');
            
            // Check if error is due to missing schema field (check various formats, case-insensitive)
            $is_schema_error = false;
            if (stripos($full_error, 'undefined field') !== false ||
                stripos($full_error, '"msg":"undefined field') !== false ||
                stripos($full_error, 'msg":"undefined field') !== false) {
                $is_schema_error = true;
            }
            
            if ($is_schema_error) {
                log_message('debug', 'Schema not set up, returning zero counts. Error: ' . $error_msg);
                return array(
                    'datasets'=>0,
                    'variables'=>0,
                    'citations'=>0,
                    'last_dataset'=>null,
                    'last_variable'=>null,
                    'note'=>'Schema not set up. Please set up schema before indexing.'
                );
            }
            
            // Re-throw other errors
            throw $e;
        }
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
    /**
     * Export surveys to JSON files
     * @param int $start_row Starting row number
     * @param int $limit Number of records per batch
     * @param bool $loop Whether to continue with next batch
     * @return array|false Export results or false on failure
     */
    public function export_surveys_to_json($start_row=NULL, $limit=10, $loop=TRUE)
    {

        if (!is_numeric($start_row)){
            throw new Exception("Start row must be a numeric value");
        }

        if (!is_numeric($limit)){
            throw new Exception("Limit must be a numeric value");
        }

        $start_time=date("h:i:s");
        set_time_limit(0);
        $this->ci->load->model("Dataset_model");
        $this->ci->load->helper('array');

        //concat('survey-', surveys.id)  as id,
        $this->ci->db->select("
                1 as doctype,
                surveys.id,
                surveys.doi,
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

        if ($start_row && is_numeric($start_row)){
            $this->ci->db->where("surveys.id >",$start_row,false);
        }

        //echo "start time= ".date("H:i:s")."\r\n";		

        $rows=$this->ci->db->get("surveys")->result_array();
        //echo $this->ci->db->last_query();die();
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
            $row['countries']=$this->load_survey_countries($row['survey_uid']);
            //survey repositories
            $row['repositories']=$this->load_survey_repositories($row['survey_uid']);
            //survey years
            $row['years']=$this->load_survey_years($row['survey_uid']);
            $row['regions']=$this->derive_regions_from_countries($row['countries']);
            
            //metadata
            $row['metadata']=$this->ci->Dataset_model->decode_metadata($row['metadata']);
            
            //array of variable keywords
            $row['var_keywords']=$this->load_survey_variable_keywords($row['survey_uid']);
            
            // Add title_sort for sorting (string field with docValues)
            if (isset($row['title'])) {
                $row['title_sort'] = $row['title'];
            }
            
            // Add nation_sort for sorting (string field with docValues)
            if (isset($row['nation'])) {
                $row['nation_sort'] = $row['nation'];
            }

            //row survey id
            $last_row_id=$row['survey_uid'];

            //print_r($row);die();

            //save each document as json file
            file_put_contents($output_file='imports/'.$row['survey_uid'].'.json',json_encode($row,JSON_PRETTY_PRINT));
        }

        //echo "finish time= ".date("H:i:s")."\r\n";

        if ($loop==true){
            //recursively call to fetch next batch of rows
            $this->export_surveys_to_json($last_row_id,$limit,$loop);
        }

        return array(
            'rows_processed'=>count($rows),
            'last_row_id'=>$last_row_id,
            'start_time'=>$start_time,
            'end_time'=>date("h:i:s")
        );
    }

    

        /**
     * Get the last document ID by type from Solr
     * @param int $doctype Document type
     * @return int|false Last document ID or false if not found
     */
    // === CORE MANAGEMENT ===


    /**
     * Create a new Solr core
     * @param string $core_name Name of the core to create
     * @param string $instance_dir Instance directory (defaults to core_name)
     * @param string $config_set Config set to use (default: _default)
     * @return array Result of the operation
     */
    public function create_core($core_name, $instance_dir = null, $config_set = '_default')
    {
        if (empty($core_name)) {
            throw new Exception("Core name is required");
        }

        if ($instance_dir === null) {
            $instance_dir = $core_name;
        }

        $client = $this->get_solarium_admin_client();
        $coreAdmin = $client->createCoreAdmin();
        $createAction = $coreAdmin->createCreate();
        $createAction->setCore($core_name);
        $createAction->setInstanceDir($instance_dir);
        
        // Config set is required for core creation - Solr needs config files
        // If not provided, try common defaults
        if (empty($config_set)) {
            // Try common config set names in order of preference
            $config_sets_to_try = ['_default', 'basic_configs', 'data_driven_schema_configs', 'sample_techproducts_configs'];
            $last_error = null;
            
            foreach ($config_sets_to_try as $try_config_set) {
                try {
                    $createAction->setConfigSet($try_config_set);
                    $coreAdmin->setAction($createAction);
                    $response = $client->coreAdmin($coreAdmin);
                    
                    return array(
                        'status' => $response->getStatus(),
                        'core_name' => $core_name,
                        'config_set_used' => $try_config_set,
                        'response' => $response->getData()
                    );
                } catch (Exception $e) {
                    $last_error = $e;
                    // Continue to next config set
                    continue;
                }
            }
            
            // If all config sets failed, provide generic instructions
            throw new Exception("Failed to create core: No valid config set found. " .
                "Tried: " . implode(', ', $config_sets_to_try) . ". " .
                "\n\nYour Solr installation is missing config sets. " .
                "To fix this, you can:\n\n" .
                "1. Create the core manually via Solr Admin UI:\n" .
                "   http://localhost:8983/solr/#/ → Core Admin → Add Core\n\n" .
                "2. Use Solr CLI (find your Solr installation directory first):\n" .
                "   bin/solr create_core -c " . $core_name . "\n\n" .
                "3. Set up config sets by copying from your Solr installation:\n" .
                "   Find your Solr installation directory (usually contains 'server/solr/configsets/')\n" .
                "   Copy config sets to your Solr data directory's configsets folder\n\n" .
                "4. Check Solr documentation for your installation method (Homebrew, manual, Docker, etc.)\n\n" .
                "Original error: " . $last_error->getMessage());
        } else {
            // Use specified config set
            $createAction->setConfigSet($config_set);
        }
        
        $coreAdmin->setAction($createAction);
        
        try {
            $response = $client->coreAdmin($coreAdmin);
            
            return array(
                'status' => $response->getStatus(),
                'core_name' => $core_name,
                'config_set_used' => $config_set,
                'response' => $response->getData()
            );
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            
            // Provide helpful error message if config set is missing
            if (strpos($error_msg, 'Could not load configuration') !== false || 
                strpos($error_msg, 'configsets') !== false ||
                strpos($error_msg, "Can't find resource 'solrconfig.xml'") !== false) {
                throw new Exception("Failed to create core: Config set '$config_set' not found or invalid. " . 
                    "Your Solr installation may not have this config set. " .
                    "Common config sets: _default, basic_configs, data_driven_schema_configs. " .
                    "Check your Solr configsets directory or create the core manually. " .
                    "Original error: " . $error_msg);
            }
            
            throw $e;
        }
    }

    /**
     * List all Solr cores
     * @return array List of cores with their status
     */
    public function list_cores()
    {
        try {
            $client = $this->get_solarium_admin_client();
            $coreAdmin = $client->createCoreAdmin();
            $statusAction = $coreAdmin->createStatus();
            $coreAdmin->setAction($statusAction);
            
            $response = $client->coreAdmin($coreAdmin);
            $data = $response->getData();
            
            $cores = array();
            if (isset($data['status']) && is_array($data['status'])) {
                foreach ($data['status'] as $coreName => $coreInfo) {
                    $cores[] = array(
                        'name' => $coreName,
                        'instanceDir' => isset($coreInfo['instanceDir']) ? $coreInfo['instanceDir'] : '',
                        'dataDir' => isset($coreInfo['dataDir']) ? $coreInfo['dataDir'] : '',
                        'config' => isset($coreInfo['config']) ? $coreInfo['config'] : '',
                        'schema' => isset($coreInfo['schema']) ? $coreInfo['schema'] : '',
                        'startTime' => isset($coreInfo['startTime']) ? $coreInfo['startTime'] : '',
                        'uptime' => isset($coreInfo['uptime']) ? $coreInfo['uptime'] : ''
                    );
                }
            }
            
            return $cores;
        } catch (Exception $e) {
            log_message('error', 'Error listing cores: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get status of a specific core
     * @param string $core_name Name of the core
     * @return array Core status information
     */
    public function get_core_status($core_name)
    {
        if (empty($core_name)) {
            throw new Exception("Core name is required");
        }

        try {
            $client = $this->get_solarium_admin_client();
            $coreAdmin = $client->createCoreAdmin();
            $statusAction = $coreAdmin->createStatus();
            $statusAction->setCore($core_name);
            $coreAdmin->setAction($statusAction);
            
            $response = $client->coreAdmin($coreAdmin);
            $data = $response->getData();
            
            // Check if the core exists in the status response
            if (isset($data['status']) && is_array($data['status']) && isset($data['status'][$core_name])) {
                $core_status = $data['status'][$core_name];
                // Ensure we have actual status data, not just an empty array
                if (is_array($core_status) && !empty($core_status)) {
                    return $core_status;
                }
            }
            
            // Core doesn't exist
            return null;
        } catch (Exception $e) {
            log_message('error', 'Error getting core status: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete/unload a Solr core
     * @param string $core_name Name of the core to delete
     * @param bool $delete_index Whether to delete the index
     * @param bool $delete_data_dir Whether to delete the data directory
     * @param bool $delete_instance_dir Whether to delete the instance directory
     * @return array Result of the operation
     */
    public function delete_core($core_name, $delete_index = true, $delete_data_dir = true, $delete_instance_dir = true)
    {
        if (empty($core_name)) {
            throw new Exception("Core name is required");
        }

        $client = $this->get_solarium_admin_client();
        $coreAdmin = $client->createCoreAdmin();
        $unloadAction = $coreAdmin->createUnload();
        $unloadAction->setCore($core_name);
        $unloadAction->setDeleteIndex($delete_index);
        $unloadAction->setDeleteDataDir($delete_data_dir);
        $unloadAction->setDeleteInstanceDir($delete_instance_dir);
        $coreAdmin->setAction($unloadAction);
        
        $response = $client->coreAdmin($coreAdmin);
        
        return array(
            'status' => $response->getStatus(),
            'core_name' => $core_name,
            'response' => $response->getData()
        );
    }

    /**
     * Get the configured core name from config
     * @return string Configured core name
     */
    public function get_configured_core_name()
    {
        $core_name = $this->ci->config->item('solr_collection');
        // Ensure we return a string, even if empty
        return $core_name !== false && $core_name !== null ? trim($core_name) : '';
    }

    /**
     * Reload a Solr core
     * @param string $core_name Name of the core to reload
     * @return array Result of the operation
     */
    public function reload_core($core_name)
    {
        if (empty($core_name)) {
            throw new Exception("Core name is required");
        }

        $client = $this->get_solarium_admin_client();
        $coreAdmin = $client->createCoreAdmin();
        $reloadAction = $coreAdmin->createReload();
        $reloadAction->setCore($core_name);
        $coreAdmin->setAction($reloadAction);
        
        $response = $client->coreAdmin($coreAdmin);
        
        return array(
            'status' => $response->getStatus(),
            'core_name' => $core_name,
            'response' => $response->getData()
        );
    }

    function get_last_document_id($doctype=1)
    {
        try {
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
                    return false;
                }
                return $document[$id_field];
            }
            
            return false;
        } catch (\Exception $e) {
            // Handle schema field errors - return null if schema not set up
            $error_msg = $e->getMessage();
            
            // Try to get body if it's an HttpException
            $error_body = '';
            if (method_exists($e, 'getBody')) {
                $error_body = $e->getBody();
            }
            
            // Combine message and body for checking
            $full_error = $error_msg . ($error_body ? "\n" . $error_body : '');
            
            // Check if error is due to missing schema field (check various formats)
            $is_schema_error = false;
            if (stripos($full_error, 'undefined field doctype') !== false ||
                stripos($full_error, 'undefined field') !== false ||
                stripos($full_error, '"msg":"undefined field') !== false ||
                stripos($full_error, 'msg":"undefined field') !== false) {
                $is_schema_error = true;
            }
            
            if ($is_schema_error) {
                log_message('debug', 'Schema field not found in get_last_document_id, returning null. Error: ' . substr($error_msg, 0, 200));
                return null;
            }
            
            // Re-throw other errors
            throw $e;
        }
    }

    /**
     * Parse methodology from metadata
     * @param array $metadata Survey metadata
     * @return string Methodology notes
     */
    function parse_methodology($metadata)
    {
        $methodology = $this->ci->Dataset_model->get_array_nested_value((array)$metadata, 'study_desc.method.method_notes', '.');
        return $methodology;
    }

    /**
     * Load survey metadata for variable indexing
     * @param int $survey_id Survey ID
     * @return array Survey metadata
     */
    private function load_survey_metadata($survey_id) {
        $survey_data = array();
        
        // Check if survey metadata should be included
        $include_metadata = $this->ci->config->item('solr_variable_include_survey_metadata');
        if (!$include_metadata) {
            return $survey_data;
        }
        
        // Get survey basic info (similar to full_import_surveys query)
        $this->ci->db->select("
            surveys.id,
            surveys.thumbnail as thumbnail,
            surveys.type as dataset_type,
            surveys.id as survey_uid,
            surveys.idno as idno,
            surveys.doi,
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
            surveys.metadata,
            surveys.total_downloads", FALSE);
        $this->ci->db->join("forms", "surveys.formid=forms.formid", "left");
        $this->ci->db->join('repositories', 'surveys.repositoryid= repositories.repositoryid', 'left');
        $this->ci->db->where('surveys.id', $survey_id);
        $survey = $this->ci->db->get("surveys")->row_array();
        
        if (!$survey) {
            log_message('warning', 'Survey not found for metadata: ' . $survey_id);
            return $survey_data;
        }
        
        // Get allowed metadata fields
        $metadata_fields = $this->ci->config->item('solr_survey_metadata_fields');
        if (empty($metadata_fields)) {
            log_message('warning', 'No survey metadata fields configured');
            return $survey_data;
        }
        
        // Add basic survey fields (direct table fields)
        foreach ($metadata_fields as $field) {
            if (isset($survey[$field])) {
                $survey_data[$field] = $survey[$field];
            }
        }
        
        // Batch load related data for this survey (similar to full_import_surveys)
        $batch_countries = $this->batch_load_survey_countries(array($survey_id));
        $batch_repositories = $this->batch_load_survey_repositories(array($survey_id));
        $batch_years = $this->batch_load_survey_years(array($survey_id));
        $batch_user_facets = $this->batch_load_user_facets(array($survey_id));
        
        // Add countries
        if (in_array('countries', $metadata_fields) && isset($batch_countries[$survey_id])) {
            $survey_data['countries'] = $batch_countries[$survey_id];
        }
        
        // Add repositories
        if (in_array('repositories', $metadata_fields) && isset($batch_repositories[$survey_id])) {
            $survey_data['repositories'] = $batch_repositories[$survey_id];
        }
        
        // Add years
        if (in_array('years', $metadata_fields) && isset($batch_years[$survey_id])) {
            $survey_data['years'] = $batch_years[$survey_id];
        }
        
        // Add regions (derived from countries)
        if (in_array('regions', $metadata_fields) && isset($batch_countries[$survey_id])) {
            $survey_data['regions'] = $this->derive_regions_from_countries($batch_countries[$survey_id]);
        }
        
        // Add custom user-defined facets (fq_ prefixed fields with term IDs)
        if (isset($batch_user_facets[$survey_id])) {
            foreach ($batch_user_facets[$survey_id] as $facet_name => $facet_terms) {
                $survey_data['fq_' . $facet_name] = $facet_terms; // term IDs array
            }
        }
        
        // Add title_sort for sorting (string field with docValues)
        if (isset($survey_data['title'])) {
            $survey_data['title_sort'] = $survey_data['title'];
        }
        
        // Add nation_sort for sorting (string field with docValues)
        if (isset($survey_data['nation'])) {
            $survey_data['nation_sort'] = $survey_data['nation'];
        }
        
        return $survey_data;
    }

    /**
     * Get list of variable fields with var_ prefix
     * @return array List of variable field names
     */
    public function get_variable_fields()
    {
        return array(
            'var_name',
            'var_label', 
            'var_question',
            'var_categories',
            'var_survey_id',
            'var_uid'
        );
    }

    /**
     * Get variable field mapping configuration
     * @return array Field mapping configuration
     */
    public function get_variable_field_mapping()
    {
        return array(
            'var_name' => 'name',
            'var_label' => 'labl',
            'var_question' => 'qstn',
            'var_categories' => 'catgry',
            'var_survey_id' => 'sid',
            'var_uid' => 'uid'
        );
    }

    /**
     * Get list of survey fields (for future denormalized support)
     * @return array List of survey field names
     */
    public function get_survey_fields()
    {
        return array(
            'survey_title',
            'survey_year',
            'survey_country',
            'survey_repository',
            'survey_keywords',
            'survey_methodology'
        );
    }




}// END  class

/* End of file Solr.php */
/* Location: ./application/libraries/Solr.php */