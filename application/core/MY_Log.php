<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Suppress 404 noise in application logs (bots, missing static files, etc.).
 */
class MY_Log extends CI_Log {

	public function write_log($level, $msg)
	{
		if (strpos($msg, '404 Page Not Found') !== FALSE)
		{
			return FALSE;
		}

		return parent::write_log($level, $msg);
	}
}
