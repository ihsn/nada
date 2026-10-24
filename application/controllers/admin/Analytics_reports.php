<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Analytics Reports — Vue 3 + Vite app.
 */
class Analytics_reports extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Analytics_model');
        $this->lang->load('analytics');
        $this->template->set_template('admin5');
    }

    public function index()
    {
        $this->acl_manager->has_access_or_die('reports', 'view');

        $this->load->helper('vite_helper');
        $view_data = array(
            'title'           => t('Analytics'),
            'site_url'        => site_url(),
            'base_url'        => base_url(),
            'csrf_token'      => $this->security->get_csrf_hash(),
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'assets_base'     => base_url('frontend/dist/'),
            'server_timezone' => date_default_timezone_get(),
            'server_date'     => date('Y-m-d'),
            'nada_base_path'  => rtrim(FCPATH, '/'),
        );

        $page = array(
            'title'           => $view_data['title'],
            'content'         => $this->load->view('admin/analytics/index', $view_data, true),
            'hide_breadcrumb' => true,
            'theme_folder'    => 'adminvue',
        );
        $this->load->view('layouts/admin_vue', $page);
    }
}
