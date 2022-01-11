<?php


class Widgets extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->load->model("Widget_model");
	}


    function view($uuid)
    {        
        $row=$this->Widget_model->find($uuid);

        if (!$row){
            echo $this->load->view('widgets/404',NULL, true);
            die();
        }

        $content=$this->load->view('widgets/info',array('widget'=>$row), true);

        /*$index_file=$row['full_path'].'/index.html';

        if (file_exists($index_file)){
            $file_content=file_get_contents($index_file);
            $head='<head><base href="'.base_url().'files/embed/'.$row['storage_path'].'/">';
            $head=$head . '<script type="text/javascript" src="https://pym.nprapps.org/pym.v1.min.js"></script><script>window.onload = function () {var pymChild = new pym.Child();}</script>';
            $file_content=str_replace('<head>', $head,$file_content);
            //echo $file_content;
            $content=$file_content;
        }*/

        //$this->template->write('title', $repo['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
    }

    function embed($uuid)
    {        
        $row=$this->Widget_model->find($uuid);

        if (!$row){
            echo $this->load->view('widgets/404',NULL, true);
            die();
        }

        $index_file=$row['full_path'].'/index.html';

        if (file_exists($index_file)){
            $file_content=file_get_contents($index_file);
            $head='<head><base href="'.base_url().'files/embed/'.$row['storage_path'].'/">';
            $head=$head . '<script type="text/javascript" src="https://pym.nprapps.org/pym.v1.min.js"></script><script>window.onload = function () {var pymChild = new pym.Child();}</script>';
            $file_content=str_replace('<head>', $head,$file_content);
            $embed_bar=$this->load->view('widgets/options',null,true);
            $file_content=str_replace('</body>', $embed_bar.'</body>',$file_content);
            $file_content=str_replace('class="container ', 'class="container-fluid',$file_content); 
            echo $file_content;
        }
    }

    function index()
    {
        $options['widget_storage_root']='files/embed/';
        $options['widgets']=$this->Widget_model->select_all();
        $content=$this->load->view('widgets/index', $options,true);
        //$content="testing";
        $this->template->set_template('default');
		//$this->template->write('title', $data['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
    }

    
}
