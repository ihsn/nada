<?php
/**
 * Catalog Tags
 *
 * handles all Catalog Maintenance pages
 *
 * @package		NADA 4
 * @author		Mehmood Asghar
 * @link		http://ihsn.org/nada/
 */
class Catalog_Tags extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
       	$this->load->model('Catalog_model');
		$this->lang->load('general');
		//$this->output->enable_profiler(TRUE);			
	}

	//add tag to survey
	function add($sid,$tag) 
	{
		if (!is_numeric($sid))
		{
			return FALSE;
		}
	
		$tag=trim($tag);
			
		if ($tag=="")
		{
			return FALSE;
		}
		
		echo json_encode(array('error'=>t("file_not_found")) );
	}

}
/* End of file catalog_tags.php */
/* Location: ./controllers/admin/catalog_tags.php */