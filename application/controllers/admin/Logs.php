<?php
/**
 * Admin site logs, API logs and log cleanup — Vue 3 SPA shell.
 *
 * UI: frontend/admin/logs (one app; PHP picks the view per URL)
 * JSON: api/admin/db_logs, api/admin/api_logs (ACL: logs view / cleanup)
 */
class Logs extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        $this->lang->load('general');
        $this->lang->load('sitelogs');
    }

    function index()
    {
        $this->acl_manager->has_access_or_die('logs', 'view');
        $this->_render_logs_vue_shell('site', t('site_logs'));
    }

    function cleanup()
    {
        $this->acl_manager->has_access_or_die('logs', 'view');
        $this->_render_logs_vue_shell('cleanup', t('Database Logs Cleanup'));
    }

    /**
     * Routed from admin/api_logs.
     */
    function api_logs()
    {
        $this->acl_manager->has_access_or_die('logs', 'view');
        $this->_render_logs_vue_shell('api', 'API Logs');
    }

    /**
     * @param string $view site|api|cleanup
     * @param string $html_title
     * @return void
     */
    private function _render_logs_vue_shell($view, $html_title)
    {
        $this->load->helper('vite_helper');

        $view_data = array(
            'logs_view'        => $view,
            'db_logs_api_url'  => site_url('api/admin/db_logs/'),
            'api_logs_api_url' => site_url('api/admin/api_logs/'),
            'can_cleanup'      => $this->acl_manager->user_has_access('logs', 'cleanup'),
            'site_url'         => site_url(),
            'base_url'         => base_url(),
            'csrf_token'       => $this->security->get_csrf_hash(),
            'csrf_token_name'  => $this->security->get_csrf_token_name(),
            'assets_base'      => base_url('frontend/dist/'),
            'translations'     => $this->lang->language,
        );

        $page = array(
            'title'           => $html_title,
            'content'         => $this->load->view('admin/logs/index', $view_data, true),
            'hide_breadcrumb' => true,
            'theme_folder'    => 'adminvue',
        );
        $this->load->view('layouts/admin_vue', $page);
    }
}
