<?php

class Solr extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		$this->load->library("Solr_manager");
		$this->template->set_template('admin5');
	}
 
    function index()
    {
        $content=$this->load->view('solr_vue/index',null,true);
		$this->template->write('title', t('solr'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
    }
}    