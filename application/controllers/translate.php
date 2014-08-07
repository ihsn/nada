<?php

class Translate extends MY_Controller 
{
	function __construct()
	{
		parent::__construct();
		redirect('admin/translate','refresh');
	}
	
}

/* End of file translate.php */
/* Location: ./system/application/controllers/translate.php */