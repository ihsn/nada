<?php


class Embed extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->load->model("Embed_model");
	}


    function index($uuid)
    {        
        $row=$this->Embed_model->find($uuid);

        if (!$row){
            show_error("page failed");
        }

        $index_file=$row['full_path'].'/index.html';

        if (file_exists($index_file)){
            $file_content=file_get_contents($index_file);
            $base_url='<head><base href="'.base_url().'files/embed/'.$row['storage_path'].'/">';
            $file_content=str_replace('<head>', $base_url,$file_content);

            echo $file_content;
        }
    }

    
}
