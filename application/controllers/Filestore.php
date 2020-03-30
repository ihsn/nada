<?php


class Filestore extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->load->model("Filestore_model");
	}


    function photo($filename)
    {
        $filename=urldecode($filename);
        return $this->Filestore_model->photo($filename);
    }

    function file($filename)
    {
        $filename=urldecode($filename);
        return $this->Filestore_model->download($filename);
    }

}
