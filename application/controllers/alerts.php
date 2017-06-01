<?php
class Alerts extends MY_Controller {

    function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->template->set_template('default');
        $this->lang->load('general');
        //$this->output->enable_profiler(TRUE);
    }

    function index()
    {
        $content="Alerts home";
        $this->template->write('title', t('Alerts'),true);
        $this->template->write('content', $content,true);
        $this->template->render();
    }


    function edit()
    {
        echo "edit alert";
    }

    function create(){
        $content=$this->load->view('alerts/create',NULl,TRUE);
        $this->template->write('title', t('Alerts'),true);
        $this->template->write('content', $content,true);
        $this->template->render();
    }

    function verify_email(){

        echo "verify email address";
    }


    function remove(){
        echo "remove alerts by email";
    }

    function remove_confirm(){
        echo "enter the removal confirmation code";
    }

    function test_filename()
    {
        $filenames=array('Thisisa/test','another\\test','another-äëüÖÜ-test','accented-carachter-ãõñÃÕÑ');
foreach($filenames as $filename) {
    $filename = $this->security->sanitize_filename($filename, FALSE);
    var_dump($filename);
    echo "<HR>";
}


    }

}//end-class
