<?php
/**
 * Dead controller — data-deposit tasks are handled by admin/datadeposit +
 * api/admin/datadeposit. Legacy task URLs always 404.
 */
class Datadeposittasks extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function _remap($method, $params = array())
	{
		unset($method, $params);
		show_404();
	}
}
