<?php

class Catalog_Tags extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_Tags_model');
		$this->lang->load('general');
	}
	
	public function delete($id) {
		$t=$this->Catalog_Tags_model->delete($id);
	}
	
	private function _reload_tags($tags) {
		 foreach($tags as $tag) {
		echo "		<li id='{$tag['id']}'>{$tag['tag']}&nbsp;&nbsp;<a href='javascript:void(0);' style='text-decoration:none'>-</a></li>", PHP_EOL;
		}	 
    }
	
	public function add($id) {
		$tag = array(
			'sid'  => $id,
			'tag' => $this->input->post('tag'),
		);
		if (!$this->Catalog_Tags_model->tag_exists($id, $this->input->post('tag'))) {
			$this->Catalog_Tags_model->insert($tag);
		}
		
		$tags['tags'] = $this->Catalog_Tags_model->tags_from_catelog_id($id);
		
		// a separate ajax call could probably be done instead of doing this, but whatever.
		$this->_reload_tags($tags['tags']);	
	}
}
