<?php
class Datadeposittasks extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->acl_manager->has_access_or_die('datadeposit', 'view');
	}

	public function _remap($method, $params = array())
	{
		show_404();
	}
}
