<?php
class Dataaccess_whitelist extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

		$this->template->set_template('admin5');
		$this->load->model("Data_access_whitelist_model");
        $this->load->model("Repository_model");
		$this->load->helper(array ('querystring_helper','url', 'form') );
        $this->load->library( array('acl_manager','form_validation','pagination') );

        $this->lang->load('general');
        //$this->output->enable_profiler(TRUE);        
    }

    function index()
    {
        $this->acl_manager->has_access_or_die('citation', 'view');
        $options['rows']=$this->Data_access_whitelist_model->select_all();
        $options['collections']=$this->Repository_model->list_all();

        $content=$this->load->view('dataaccess_whitelist/index', $options,true);
		$this->template->write('title', t('Data access whitelist'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}


    function create()
    {
        $user_id=$this->Data_access_whitelist_model->get_user_id($this->input->post("email"));
        $repository_id=$this->input->post("repository_id");

        if(!$repository_id || !$user_id){
            $this->session->set_flashdata('error', t('Invalid values for `collection_name` or `email`'));
    		redirect("admin/dataaccess_whitelist","refresh");        
        }

        try{
            $result=$this->Data_access_whitelist_model->insert($repository_id,$user_id);
        }
        catch(Exception $e){
            $this->session->set_flashdata('error', $e->getMessage());
		    redirect("admin/dataaccess_whitelist","refresh");
        }
        $this->session->set_flashdata('message', t('form_update_success'));
		redirect("admin/dataaccess_whitelist","refresh");        
    }


    function delete($id)
    {
        $this->Data_access_whitelist_model->delete_by_id($id);
        $this->session->set_flashdata('message', t('form_update_success'));
		redirect("admin/dataaccess_whitelist","refresh");
    }


}
/* End of file Dataaccess_whitelist.php */
/* Location: ./controllers/admin/Dataaccess_whitelist.php */
