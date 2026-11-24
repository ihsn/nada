<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Expected API error with an HTTP status, e.g. throw new Api_exception('Table not found', 404).
 *
 * Handled by MY_REST_Controller::api_error_response(): the message is returned to the client
 * as is, so it must not contain internal details.
 */
class Api_exception extends Exception
{
	/** @var int HTTP status */
	public $status;

	/** @var string|null machine readable code returned as error_code */
	public $error_code;

	public function __construct($message, $status = 400, $error_code = null, $previous = null)
	{
		parent::__construct($message, 0, $previous);
		$this->status = (int) $status;
		$this->error_code = $error_code;
	}
}
