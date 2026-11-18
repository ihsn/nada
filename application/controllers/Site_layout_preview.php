<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Preview of a Site layout draft, shown in a frame on Admin > Settings > Site layout.
 *
 *   GET site_layout_preview/header             the header draft (or the published header when there is no draft)
 *   GET site_layout_preview/header?as=guest    the same, as a signed-out visitor sees it
 *   GET site_layout_preview/footer             the footer draft
 *   GET site_layout_preview/styles             the site CSS draft
 *
 * Only for users who may edit configurations; everyone else gets "page not found".
 */
class Site_layout_preview extends MY_Controller {

	private static $pages = array(
		'header' => array('site_header_preview', 'Header preview'),
		'footer' => array('site_footer_preview', 'Footer preview'),
		'styles' => array('site_styles_preview', 'Styles preview'),
	);

	public function __construct()
	{
		parent::__construct($skip_auth = TRUE);
		$this->lang->load('general');
		$this->lang->load('site_footer');
		$this->lang->load('site_styles');
	}

	/** site_layout_preview/{part}: the URL segment is the part, not a method */
	public function _remap($part)
	{
		if (!isset(self::$pages[$part]) || !$this->acl_manager->user_has_access('configurations', 'edit')) {
			show_404();
			return;
		}

		$builder = 'site_' . $part . '_builder';
		$this->load->library($builder);
		if ($part === 'header') {
			$this->$builder->set_preview(TRUE, $this->input->get('as') === 'guest');
		} else {
			$this->$builder->set_preview(TRUE);
		}

		list($title_key, $title) = self::$pages[$part];
		$this->output->set_header('X-Robots-Tag: noindex, nofollow');
		$this->template->write('title', tt($title_key, $title), TRUE);
		$this->template->write('content', $this->load->view('site_' . $part . '/preview_page', array(), TRUE), TRUE);
		$this->template->render();
	}
}
