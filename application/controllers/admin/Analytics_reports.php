<?php
class Analytics_reports extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		//set template to admin 
		$this->template->set_template('admin');
		$this->load->model('Analytics_model');
		
		$this->lang->load("analytics");	
		//$this->output->enable_profiler(TRUE);
    }
 
	function index()
	{
		$this->acl_manager->has_access_or_die('reports', 'view');

		$this->template->set_template('admin5');
		$data = array('nada_base_path' => rtrim(FCPATH, '/'));
		$content = $this->load->view('admin/analytics/index', $data, true);
        $this->template->write('title', t('Analytics'), true);
        $this->template->write('content', $content, true);
        $this->template->render();
	}


}
/* End of file reports.php */
/* Location: ./controllers/reports.php */