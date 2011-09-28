<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class sitemap extends CI_Controller {

	function __construct()
    {
        parent::__construct();
		$this->load->library('sitemaplib'); 
    }
    
    function index()
    {
        // Show the index page of each controller (default is FALSE)
        $this->sitemaplib->set_option('show_index', true);

        // Exclude all methods from the "Test" controller
        $this->sitemaplib->ignore('Test', '*');

        // Exclude all methods from the "Job" controller
        $this->sitemaplib->ignore('Job', '*');

        // Exclude a list of methods from any controller
        $this->sitemaplib->ignore('*', array('view', 'create', 'edit', 'delete'));

        // Exclude this controller
        $this->sitemaplib->ignore('Sitemap', '*'); 

        // Show the sitemap
        echo '<h1>Sitemap</h1>';
        echo $this->sitemaplib->generate();
    }
}