<?php

class Solr extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		$this->load->library("Solr_manager");
		$this->template->set_template('admin');
	}
 
    function index()
    {
        $options['db_stats']=$this->solr_manager->get_db_counts();
        $options['solr_stats']=$this->solr_manager->get_solr_counts();
        $content=$this->load->view('solr/index',$options,true);
		$this->template->write('title', t('resource_manager'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
    }
    
}    