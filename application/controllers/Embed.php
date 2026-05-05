<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal pages intended for iframe embedding on external sites (see /embed/catalog/{sid}/chart).
 */
class Embed extends MY_Controller {

	public function __construct()
	{
		parent::__construct(TRUE);
		$this->load->model('Dataset_model');
		$this->load->model('Timeseries_dsd_model');
		if (function_exists('t')) {
			$this->lang->load('catalog_search');
		}
	}

	/**
	 * Public indicator chart shell for iframes — same Vue app and query params as catalog/…/indicator-chart.
	 *
	 * Route: embed/catalog/{sid}/chart
	 */
	public function catalog_chart($sid = null)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			show_404();
		}

		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || $survey['type'] !== 'timeseries') {
			show_404();
		}

		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid($sid);
		if ($ctx === null) {
			show_404();
		}

		$this->load->helper('vite_helper');

		$study_abstract = isset($survey['abstract']) ? strip_tags((string) $survey['abstract']) : '';
		if (strlen($study_abstract) > 4000) {
			$study_abstract = substr($study_abstract, 0, 4000) . '…';
		}

		$content = $this->load->view('catalog/study_indicator_data_public', array(
			'survey_id' => $sid,
			'idno' => isset($survey['idno']) ? (string) $survey['idno'] : '',
			'indicator_main_view' => 'chart',
			'catalog_page_title' => function_exists('t') ? t('tab_indicator_chart') : 'Chart & data',
			'study_title' => isset($survey['title']) ? (string) $survey['title'] : '',
			'study_abstract' => $study_abstract,
			'indicator_data_api_ui' => array(),
			'embed_mode' => true,
		), true);

		$page_title = isset($survey['title']) ? (string) $survey['title'] : ('Study ' . $sid);

		$this->template->set_template('embed_chart');
		$this->template->write('title', $page_title, true);
		$this->template->write('content', $content, true);
		$this->template->render();
	}
}
