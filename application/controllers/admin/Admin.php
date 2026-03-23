<?php
class Admin extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->template->set_template('admin5');
        // Cache driver kept for clear_cache()
        $this->load->driver('cache', array('adapter' => 'dummy', 'backup' => 'file'));
        $this->lang->load("general");
        $this->lang->load("dashboard");
    }

    function index()
    {
        $this->load->helper('vite_helper');
        $data['title']      = t('Dashboard');
        $data['site_url']   = site_url();
        $data['base_url']   = base_url();
        $data['assets_base']= base_url('frontend/dist/');
        $data['csrf_token'] = $this->security->get_csrf_hash();
        $data['translations'] = $this->lang->language;

        $content = $this->load->view('dashboard/index', $data, TRUE);
        $this->template->write('title', $data['title'], TRUE);
        $this->template->write('content', $content, TRUE);
        $this->template->add_variable('hide_breadcrumb', true);
        $this->template->render();
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
