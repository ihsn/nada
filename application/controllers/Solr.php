<?php
/**
 *
 * SOLR indexing -  CLI mode
 * 
 *
 **/

class Solr extends MY_Controller {

    //solr configrations
    var $solr_config;

    public function __construct()
    {
		parent::__construct($skip_auth=false, $isadmin=true);
	
		/*if(!$this->input->is_cli_request()){
			die("ERROR_NOT-CLI");
		}*/

		$this->load->library('solr_manager');		
		error_reporting(E_ALL);
		//$this->output->enable_profiler(TRUE);
    }


    function index()
    {
			echo "SOLR";
			echo '<ul>';
			echo '<li><a href="'.site_url('solr/ping_test').'">Ping test</a></li>';
			echo '<li><a href="'.site_url('solr/get_total_documents_count').'">Documents count in SOLR index</a></li>';		
			echo '<li><a href="'.site_url('solr/clean_index').'">clear index</a></li>';
			echo '<li><a href="'.site_url('solr/full_import_surveys/0/50').'">Index all datasets</a></li>';
			echo '<li><a href="'.site_url('solr/full_import_variables').'">Index variables</a></li>';
			echo '<li><a href="'.site_url('solr/ping_test').'">Ping test</a></li>';
			echo '</ul>';
    }

    public function ping_test()
    {
      	var_dump($this->solr_manager->ping_test());
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
	public function full_import_surveys($start_row=NULL, $limit=10, $loop=FALSE)
	{		
		$this->solr_manager->full_import_surveys($start_row, $limit, $loop);
	}


	/**
	 *
	 * remove all documents from index
	 *
	 **/
	function clean_index(){
		$this->solr_manager->clean_index();
	}


	function get_total_documents_count()
	{
		echo $this->solr_manager->get_total_documents_count();
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
		$result=$this->solr_manager->full_import_variables($start_row, $limit, $loop);

		echo '<pre>';
		print_r($result);
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
		$this->solr_manager->full_import_citations($start_row, $limit, $loop);
	}


	function delete_document()
	{
		die("disabled");
		$query='id:(cit-301 OR cit-302 OR cit-303 OR cit-304 OR cit-305)';
		$this->solr_manager->delete_document($query);
	}

	function sync_solr_with_db()
	{
		die("disabled");
		$this->solr_manager->sync_solr_with_db();
	}


}//end-class