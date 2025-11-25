<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Solr extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library("Solr_manager");
		$this->is_admin_or_die();
	}

	function index()
	{
		echo '<h2>Solr Management</h2>';
		echo '<ul>';
		echo '<li><a href="'.site_url('solr/import_surveys_batch/0/50').'">Index all datasets</a></li>';
		echo '<li><a href="'.site_url('solr/import_variables_batch').'">Index variables</a></li>';
		echo '<li><a href="'.site_url('solr/clear_index').'">clear index</a></li>';
		echo '<li><a href="'.site_url('solr/commit').'">commit</a></li>';
		echo '<li><a href="'.site_url('solr/get_total_documents_count').'">get total documents count</a></li>';
		echo '<li><a href="'.site_url('solr/sync_solr_with_db').'">sync solr with db</a></li>';
		echo '</ul>';
	}

	/**
	 *
	 * Import surveys in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function import_surveys_batch($start_row=NULL, $limit=10, $loop=FALSE)
	{		
        $result=$this->solr_manager->import_surveys_batch($start_row, $limit, false);
        
        var_dump($result);

        if($loop ==true && isset($result['last_row_id']) && $result['last_row_id'] > 0){
            $this->import_surveys_batch($result['last_row_id'], $limit, $loop);
        }
	}

	function export_surveys_to_json($start_row=NULL, $limit=10, $loop=FALSE)
	{
		$result=$this->solr_manager->export_surveys_to_json($start_row, $limit, $loop_=FALSE);

		var_dump($result);
		
		if($loop ==true && $result['last_row_id'] > 0){
            $this->export_surveys_to_json($result['last_row_id'], $limit, $loop);
        }
	}


	/**
	 *
	 * remove all documents from index
	 *
	 **/
	function clear_index(){
		$this->solr_manager->clear_index();
	}


	function get_total_documents_count()
	{
		echo $this->solr_manager->count_documents_by_type();
	}




	/**
	 *
	 * Import all variables for a single survey with survey metadata loaded once
	 *
	 * @survey_id Survey ID to import variables for
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function import_variables_by_survey_batch($survey_id, $start_row=0, $limit=200, $loop=TRUE)
	{
		$result = $this->solr_manager->import_variables_by_survey_batch($survey_id, $start_row, $limit, $loop);

        var_dump($result);

        if($loop == true && isset($result['last_row_id']) && $result['last_row_id'] > 0){
            $this->import_variables_by_survey_batch($survey_id, $result['last_row_id'], $limit, $loop);
        }
	}

	/**
	 *
	 * Import variables in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function import_variables_batch($start_row=NULL, $limit=100, $loop=FALSE)
	{
		$result=$this->solr_manager->import_variables_batch($start_row, $limit, false);

        var_dump($result);

        if($loop ==true && $result['last_row_id'] > 0){
            $this->import_variables_batch($result['last_row_id'], $limit, $loop);
        }
	}



	/**
	 *
	 * Import citations in batches
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function import_citations_batch($start_row=NULL, $limit=100, $loop=TRUE)
	{
		$this->solr_manager->import_citations_batch($start_row, $limit, $loop);
	}


	function delete_document()
	{
		die("disabled");
		$query='id:(cit-301 OR cit-302 OR cit-303 OR cit-304 OR cit-305)';
		$this->solr_manager->delete_document($query);
	}

	function synchronize_index()
	{
		$this->solr_manager->synchronize_index($dryrun=false);
	}


	function commit()
	{
		$this->solr_manager->commit_index_changes();
	}

}