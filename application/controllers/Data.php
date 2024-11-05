<?php


class Data extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);        
	}

    
    


    function chart()
    {
        $options=array();
        $content=$this->load->view('vue2/charts/index', $options,true);
        echo $content;
        die();
    } 

    function datagrid()
    {
        $options=array();
        $content=$this->load->view('vue2/datagrid/index', $options,true);
        echo $content;
        die();
    } 

    
}
