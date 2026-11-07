<?php
/**
 * Retired. Study PDF options run in the Vue study sidebar and overview
 * against api/admin/catalog/{id}/pdf_options, generate_pdf, and delete_pdf.
 */
class Pdf_generator extends MY_Controller {

	public function _remap($method, $params = array())
	{
		unset($method, $params);
		show_404();
	}
}
