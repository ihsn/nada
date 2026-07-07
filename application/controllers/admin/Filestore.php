<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Filestore extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->template->set_template('admin5');
    }

    /**
     * Main index method - serves the Vue 2 frontend
     */
    public function index()
    {
        $this->acl_manager->has_access_or_die('filestore', 'view');
        
        $data['title'] = 'public_resources';
        $data['api_base_url'] = base_url('index.php/api/filestore');
        
        // Load the Vue app content
        $content = $this->load->view('admin/filestore/index', $data, true);
        
        // Write to template
        $this->template->write('title', $data['title'], true);
        $this->template->write('content', $content, true);
        $this->template->render();
    }
}


