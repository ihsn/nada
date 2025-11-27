<?php

class Logs extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        
        $this->load->helper(array('form', 'url'));
        $this->load->library('pagination');
        $this->load->model('Sitelog_model');
        $this->lang->load('general');
        
        $this->template->set_template('admin5');
    }

    /**
     * Site logs index page - displays all logs with search and pagination
     */
    function index()
    {
        $this->acl_manager->has_access_or_die('user', 'view');
        
        $per_page = 50;
        $offset = $this->input->get('offset') ? (int)$this->input->get('offset') : 0;
        
        $keywords = $this->input->get('keywords');
        $field = $this->input->get('field');
        $sort_by = $this->input->get('sort_by') ?: 'logtime';
        $sort_order = $this->input->get('sort_order') ?: 'desc';
        
        $filter = array();
        if ($keywords && $field) {
            $filter[] = array(
                'field' => $field,
                'keywords' => $keywords
            );
        }
        
        $total_rows = $this->Sitelog_model->search_count($filter);
        
        if ($offset > $total_rows) {
            $offset = max(0, $total_rows - $per_page);
        }
        
        $config['base_url'] = site_url('admin/logs');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $per_page;
        $config['query_string_segment'] = 'offset';
        $config['page_query_string'] = TRUE;
        $config['additional_querystring'] = get_querystring(array('keywords', 'field', 'sort_by', 'sort_order'));
        $config['num_links'] = 1;
        $config['full_tag_open'] = '<span class="page-nums">';
        $config['full_tag_close'] = '</span>';
        
        $this->pagination->initialize($config);
        
        $rows = $this->Sitelog_model->search($per_page, $offset, $filter, $sort_by, $sort_order);
        
        $data['rows'] = $rows;
        $data['title'] = t('site_logs');
        
        $content = $this->load->view('sitelogs/index', $data, true);
        $this->template->write('content', $content, true);
        $this->template->write('title', $data['title'], true);
        $this->template->render();
    }

    /**
     * Logs cleanup page
     */
    function cleanup()
    {
        $this->acl_manager->has_access_or_die('user', 'view');
        
        $data['title'] = t('Database Logs Cleanup');
        
        $content = $this->load->view('admin/logs/cleanup', $data, true);
        $this->template->write('content', $content, true);
        $this->template->write('title', $data['title'], true);
        $this->template->render();
    }

    /**
     * API logs page
     */
    function api_logs()
    {
        $this->acl_manager->has_access_or_die('user', 'view');
        
        $data['title'] = 'API Logs';
        
        $content = $this->load->view('admin/api_logs/index', $data, true);
        $this->template->write('content', $content, true);
        $this->template->write('title', $data['title'], true);
        $this->template->render();
    }
}
