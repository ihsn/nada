<?php

/**
 * Global PDF viewer shell (Vue + PDF.js).
 *
 * URL: /viewer/pdf?source=resource&sid=827&resource_id=42&page=3&pages=3,12,24
 *
 * Renders a minimal HTML page (no site header/theme assets).
 */
class Pdfviewer extends MY_Controller {

	public function __construct()
	{
		parent::__construct(true);
		$this->lang->load('general');
		$this->lang->load('catalog_search');
	}

	public function index()
	{
		$this->load->helper('vite_helper');

		$this->load->view('pdf_viewer/shell', array(
			'page_title'   => t('pdf_preview'),
			'site_url'     => site_url(),
			'base_url'     => base_url(),
			'csrf_token'   => $this->security->get_csrf_hash(),
			'assets_base'  => base_url('frontend/dist/'),
			'translations' => $this->lang->language,
		));
	}
}
