<?php
/**
 * Retired — old `groups` UI superseded by admin/permissions (roles).
 * URLs under admin/user_groups always 404.
 */
class User_groups extends MY_Controller {

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
