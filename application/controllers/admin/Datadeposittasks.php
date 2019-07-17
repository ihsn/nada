<?php
class Datadeposittasks extends MY_Controller {

	private $storage_location=NULL;

	public function __construct() 
	{
		parent::__construct();
		$this->load->model('DD_project_model');
        $this->load->model('DD_tasks_model');
		$this->template->set_template('admin');
		//$this->_get_active_project();
		
		//$this->output->enable_profiler(TRUE);
	}
	
	public function index()
    {
        //recently completed tasks with in 3 days
        $options['tasks_completed']=$this->DD_tasks_model->get_tasks_by_status($status=1,$days_offset=3);

        //find all pending tasks
        $options['tasks_pending']=$this->DD_tasks_model->get_tasks_by_status($status=0);

        $content=$this->load->view('datadeposit/tasks_index',$options,true);
        $this->template->write('content', $content,true);
        $this->template->write('title', t('View task Info'),true);
        $this->template->render();
    }



    //edit task assignment
    function info($task_id=null)
    {
        if (!is_numeric($task_id))
        {
            show_404();
        }

        $this->load->model('user_model');
        $this->load->model('DD_tasks_team_model');

        $data['task']= $this->DD_tasks_model->select_single($task_id);

        if (!$data['task'])
        {
            show_404();
        }

        $data['project_id']=$data['task']['project_id'];
        $data['project']=(object)$this->DD_project_model->get_by_id($data['project_id']);

        $content=$this->load->view('datadeposit/view_task',$data,true);

        $this->template->write('content', $content,true);
        $this->template->write('title', t('View task Info'),true);
        $this->template->render();
    }


    public function update($task_id,$status_code)
    {
        $this->DD_tasks_model->update_task($task_id,$status_code);

        $task= $this->DD_tasks_model->select_single($task_id);

        $task_options=array(
            'assigned_by'=>$task['user_id'],
            'assigned_to'=>$task['assigner_id'],
            'project_id'=>$task['project_id'],
            'status'=>$task['status']
        );

        //send email notification
        $this->DD_tasks_model->send_status_notification($task_options);

        redirect('admin/datadeposit');
    }

    public function delete($task_id)
    {
        $task= $this->DD_tasks_model->select_single($task_id);

        $this->DD_tasks_model->delete($task_id);

        $this->db_logger->write_log('data-deposit',$this->session->userdata('username'). ' deleted task '.$task['project_id'] ,'delete');
        redirect('admin/datadeposit');
    }


    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this, $method), $params);
        }
        show_404();
    }


    //show tasks by the current users
    function my_tasks()
    {
        $user_id=$this->session->userdata('user_id');

        //find tasks assigned to me or I assigned to others
        $options['tasks']=$this->DD_tasks_model->get_tasks_by_user($user_id);
        $options['assigner_tasks']=$this->DD_tasks_model->get_tasks_by_assigner($user_id);

        $content=$this->load->view('datadeposit/my_tasks',$options,true);

        $this->template->write('content', $content,true);
        $this->template->write('title', t('View task Info'),true);
        $this->template->render();
    }

}	