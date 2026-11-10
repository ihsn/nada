<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin file manager (filestore) — Vue 3 SPA shell.
 *
 * UI: frontend/admin/filestore
 * JSON: api/admin/filestore
 */
class Filestore extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->lang->load('general');
    }

    public function index()
    {
        $this->acl_manager->has_access_or_die('filestore', 'view');
        $this->load->helper('vite_helper');

        $view_data = array(
            'api_base_url'    => site_url('api/admin/filestore'),
            'files_base_url'  => base_url('files/public'),
            'can_upload'      => $this->acl_manager->user_has_access('filestore', 'upload'),
            'can_delete'      => $this->acl_manager->user_has_access('filestore', 'delete'),
            'site_url'        => site_url(),
            'base_url'        => base_url(),
            'csrf_token'      => $this->security->get_csrf_hash(),
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'assets_base'     => base_url('frontend/dist/'),
            'translations'    => $this->lang->language,
        );

        $page = array(
            'title'           => t('file_manager'),
            'content'         => $this->load->view('admin/filestore/index', $view_data, true),
            'hide_breadcrumb' => true,
            'theme_folder'    => 'adminvue',
        );
        $this->load->view('layouts/admin_vue', $page);
    }
}
