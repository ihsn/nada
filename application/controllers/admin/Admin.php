<?php
class Admin extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        // Cache driver kept for clear_cache()
        $this->load->driver('cache', array('adapter' => 'dummy', 'backup' => 'file'));
        $this->lang->load("general");
        $this->lang->load("dashboard");
    }

    function index()
    {
        $this->load->helper('vite_helper');

        $inner = array(
            'site_url'     => site_url(),
            'base_url'     => base_url(),
            'assets_base'  => base_url('frontend/dist/'),
            'csrf_token'   => $this->security->get_csrf_hash(),
            'translations' => $this->lang->language,
        );

        $page = array(
            'title'             => t('Dashboard'),
            'content'           => $this->load->view('dashboard/index', $inner, true),
            'hide_breadcrumb'   => true,
            'theme_folder'      => 'adminvue',
        );

        $this->load->view('layouts/admin_vue', $page);
    }

    /**
     * Admin UI style guide (Vue).
     */
    function ui_kit()
    {
        $this->load->helper('vite_helper');

        $inner = array(
            'site_url'     => site_url(),
            'base_url'     => base_url(),
            'assets_base'  => base_url('frontend/dist/'),
            'csrf_token'   => $this->security->get_csrf_hash(),
            'translations' => $this->lang->language,
        );

        $page = array(
            'title'             => 'Admin UI Kit',
            'content'           => $this->load->view('admin/ui_kit/index', $inner, true),
            'hide_breadcrumb'   => true,
            'theme_folder'      => 'adminvue',
        );

        $this->load->view('layouts/admin_vue', $page);
    }

    /**
     * Clear CI file cache
     */
    function clear_cache()
    {
        $this->cache->clean();
        $this->session->set_flashdata('message', 'Cache is cleared!');
        redirect("admin", "refresh");
    }

}
/* End of file admin.php */
/* Location: ./controllers/admin/admin.php */
