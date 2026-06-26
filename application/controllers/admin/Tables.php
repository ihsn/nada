<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin MongoDB Tables manager — Vue 3 + Vite app.
 */
class Tables extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('ion_auth');

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('auth/login', 'refresh');
        }

        $this->template->set_template('admin5');
    }

    public function index()
    {
        $this->load->helper('vite_helper');
        $view_data = array(
            'title'                => 'MongoDB Tables Manager',
            'api_base_url'         => site_url('api/tables/'),
            'catalog_api_base_url' => site_url('api/catalog/'),
            'study_edit_base_url'  => site_url('catalog/'),
            'site_url'             => site_url(),
            'base_url'             => base_url(),
            'csrf_token'           => $this->security->get_csrf_hash(),
            'assets_base'          => base_url('frontend/dist/'),
        );

        $page = array(
            'title'           => $view_data['title'],
            'content'         => $this->load->view('admin/tables/index', $view_data, true),
            'hide_breadcrumb' => true,
            'theme_folder'    => 'adminvue',
        );
        $this->load->view('layouts/admin_vue', $page);
    }
}
