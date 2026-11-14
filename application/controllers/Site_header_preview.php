<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Preview of the site header draft, shown in a frame on Admin > Settings > Site header.
 *
 *   GET site_header_preview            the draft (or the published header when there is no draft)
 *   GET site_header_preview?as=guest   the same, as a signed-out visitor sees it
 *
 * Only for users who may edit the header (configurations/edit); everyone else gets "page not found".
 */
class Site_header_preview extends MY_Controller {

	public function __construct()
	{
		parent::__construct($skip_auth = TRUE);
		$this->lang->load('general');
	}

	public function index()
	{
		if (!$this->acl_manager->user_has_access('configurations', 'edit')) {
			show_404();
			return;
		}

		$this->load->library('site_header_builder');
		$this->site_header_builder->set_preview(TRUE, $this->input->get('as') === 'guest');

		$this->output->set_header('X-Robots-Tag: noindex, nofollow');
		$this->template->write('title', tt('site_header_preview', 'Header preview'), TRUE);
		$this->template->write('content', $this->load->view('site_header/preview_page', array(), TRUE), TRUE);
		$this->template->render();
	}
}
