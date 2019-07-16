<?php
/**
* Team tasks assignment
*
**/
class dd_tasks_model extends CI_Model {
	
	//database allowed column names
	private $allowed_fields=array('project_id','user_id','date_assigned', 'date_completed','status', 'comments','assigner_id');

    private $task_types=array(
        'wip'        => 0,
        'completed'  => 1,
    );

	
    public function __construct()
    {
        parent::__construct();
        //$this->output->enable_profiler(TRUE);
    }


    function assign_task($project_id,$user_id,$assigner_id)
    {
        $options=array(
            'project_id'    => $project_id,
            'user_id'       => $user_id,
            'assigner_id'   => $assigner_id,
            'date_assigned' => date("U"),
            'status'        =>0
        );

        //delete project assignment if it was already assigned to another user
        //a project can be assigned to one person at a time
        $this->delete_task_assignment($project_id,$user_id);

        //assign to new user
        return $this->insert($options);
    }



	function insert($data)
	{		
        $options=array();
        foreach($data as $key=>$value)
        {
            if(in_array($key,$this->allowed_fields))
            {
                $options[$key]=$value;
            }
        }

        return $this->db->insert('dd_tasks', $options);
	}


    function update($id,$data)
    {
        $options=array();
        foreach($data as $key=>$value)
        {
            if (in_array($key,$this->allowed_fields) )
            {
                $options[$key]=$value;
            }
        }
        $this->db->where('id', $id);
        $result=$this->db->update('dd_tasks', $options);
        return $result;
    }


    function update_task($task_id,$status_code)
    {
        $options=array(
            'status'=>$status_code
        );

        if ($status_code==1)
        {
            $options['date_completed']=date("U");
        }

        return $this->update($task_id,$options);
    }


    function delete($id)
    {
		$this->db->delete('dd_tasks', array('id' => $id));
	}


    function delete_task_assignment($project_id)
    {
        $this->db->delete('dd_tasks', array('project_id' => $project_id));
    }
	


	function select_single($id)
    {
		$this->db->select('dd_tasks.*, users.username as task_user, u2.username as assigner');
        $this->db->where('dd_tasks.id', $id);
        $this->db->join('users', 'users.id=dd_tasks.user_id','inner');
        $this->db->join('users as u2', 'u2.id=dd_tasks.assigner_id','left');
		return $this->db->get('dd_tasks')->row_array();
	}


    function get_tasks_by_user($user_id)
    {
        $this->db->select('dd_tasks.*, users.username as task_user, u2.username as assigner, dd_projects.title as project_title');
        $this->db->where('dd_tasks.user_id', $user_id);
        $this->db->join('dd_projects', 'dd_projects.id=dd_tasks.project_id','inner');
        $this->db->join('users', 'users.id=dd_tasks.user_id','inner');
        $this->db->join('users as u2', 'u2.id=dd_tasks.assigner_id','left');
        return $this->db->get('dd_tasks')->result_array();
    }


    function get_tasks_by_assigner($user_id)
    {
        $this->db->select('dd_tasks.*, users.username as task_user, u2.username as assigner, dd_projects.title as project_title');
        $this->db->where('dd_tasks.assigner_id', $user_id);
        $this->db->join('users', 'users.id=dd_tasks.user_id','inner');
        $this->db->join('dd_projects', 'dd_projects.id=dd_tasks.project_id','inner');
        $this->db->join('users as u2', 'u2.id=dd_tasks.assigner_id','left');
        return $this->db->get('dd_tasks')->result_array();
    }


    function get_tasks_by_status($status_id,$day_offset=NULL)
    {
        $this->db->select('dd_tasks.*, users.username as task_user, u2.username as assigner, dd_projects.title as project_title');
        $this->db->where('dd_tasks.status', $status_id);
        $this->db->join('users', 'users.id=dd_tasks.user_id','inner');
        $this->db->join('dd_projects', 'dd_projects.id=dd_tasks.project_id','inner');
        $this->db->join('users as u2', 'u2.id=dd_tasks.assigner_id','left');

        if ($day_offset)
        {
            $current_date=date("U");
            $past_date=strtotime("-$day_offset day");
            $this->db->where("(dd_tasks.date_completed between {$past_date} and $current_date) ",NULL, FALSE);
        }

        $result=$this->db->get('dd_tasks')->result_array();
        return $result;
    }


    /**
	* returns task types
	*
	*
	**/
	function get_task_types()
    {
        return $this->task_types;
	}


    /*
     * @task_options=...
     *
        $email_options=array(
        'assigned_by'=>$current_user_id,
        'assigned_to'=>$user_id,
        'project_id'=>$project_id,
        'status'=>0,
        'deleted'=>1 //when a task is deleted
        );
    */
    function send_status_notification($task_options)
    {
        //notifications must be enabled for the emails
        $notifications_enabled=(int)$this->config->item('dd_tasks_notifications');

        if ($notifications_enabled!==1)
        {
            //die("notifications are disabled");
            return false;
        }


        $this->load->model("user_model");
        $this->load->model("dd_project_model");

        //get the site administrator email address
        $site_admin_email=$this->config->item('website_webmaster_email');
        $data['task']=$task_options;
        $data['task_user']=$this->ion_auth->get_user($task_options['assigned_by']);
        $data['assignee']=$this->ion_auth->get_user($task_options['assigned_to']);
        $data['project']=(object)$this->dd_project_model->get_by_id($task_options['project_id']);

        $assigner_email=$data['task_user']->email;
        $assigned_to_email=$data['assignee']->email;
        $subject='[task notification] - '. $data['project']->title;
        $message=$this->load->view('datadeposit/emails/email_task_info',$data,TRUE);

        /*if ($task_options['status']==0) {

        }
        else if ($task_options['status']==1)
        {
            $subject='[COMPLETED] - '. $project->title;
        }*/

        $this->send_notification($assigned_to_email,$assigner_email, $site_admin_email, $subject, $message);
    }



    /**
     *
     * Email project summary
     * Note: Duplicate function - move to model/library
     **/
    private function send_notification($to, $cc, $bcc, $subject,$message)
    {
        $this->load->helper('email');
        $this->load->library('email');

        //set email body
        $data['content']=$message;
        $data['message']='';

        //format html for email
        $css= file_get_contents(APPPATH.'../themes/datadeposit/email.css');
        $contents=$this->load->view('datadeposit/emails/template', $data,TRUE);

        //convert external styles to inline styles
        $this->load->library('CssToInlineStyles');
        $this->csstoinlinestyles->setCSS($css);
        $this->csstoinlinestyles->setHTML($contents);
        $contents=$this->csstoinlinestyles->convert();

        $this->email->clear();
        $config['mailtype'] = 'html';
        $this->email->debug=true;
        $this->email->initialize($config);
        $this->email->CharSet = 'UTF-8';
        $this->email->set_newline("\r\n");

        $this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
        $this->email->to($to);

        if ($cc){
            $this->email->cc($cc);
        }
        if ($bcc){
            $this->email->bcc($bcc);
        }

        $this->email->subject($subject);
        $this->email->message($contents);

        if (!@$this->email->send())
        {
            echo ("EMAIL_FAILED");
            //echo $this->email->print_debugger();
            //exit;
            return false;
        }
        else
        {
            //echo $this->email->print_debugger();
            //die ("EMAIL_SENT");
            return true;
        }
    }
}

