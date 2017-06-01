<?php
class Vocabularies extends MY_Controller {

    function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->template->set_template('default');
        $this->lang->load('general');
        //$this->output->enable_profiler(TRUE);
    }


    //print a table of countries
    function countries()
    {

    }

}//end-class
