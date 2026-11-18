<?php
/**
 * Admin citations — Vue 3 SPA shell.
 *
 * UI:   frontend/admin/citations
 * JSON: api/admin/citations
 *
 * URLs:
 *   /admin/citations                         list
 *   /admin/citations/add                     create
 *   /admin/citations/edit/{id}               edit
 *   /admin/citations/import                  import from BibTeX, EndNote, RIS or JSON
 *   /admin/citations/export                  export page
 *   /admin/citations/export/{csv|json|bibtex|ris}  download everything
 */
class Citations extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('Citation_model');
		$this->lang->load('general');
		$this->lang->load('citations');
	}

	public function _remap($method = 'index', $params = array())
	{
		$id = isset($params[0]) ? $params[0] : null;

		switch ($method) {
			case 'index':
			case '':
				$this->acl_manager->has_access_or_die('citation', 'view');
				$this->_render_vue_shell(t('title_citations'));
				return;

			case 'add':
				$this->acl_manager->has_access_or_die('citation', 'create');
				$this->_render_vue_shell(t('add_new_citation'));
				return;

			case 'edit':
				if ( ! is_numeric($id) || (int) $id < 1 || count($params) > 1) {
					show_404();
				}
				$this->acl_manager->has_access_or_die('citation', 'view');
				$this->_render_vue_shell(t('edit_citation'));
				return;

			case 'import':
				$this->acl_manager->has_access_or_die('citation', 'create');
				$this->_render_vue_shell(t('import_citation'));
				return;

			case 'export':
				if ($id === null) {
					$this->acl_manager->has_access_or_die('citation', 'view');
					$this->_render_vue_shell(t('export_citations'));
					return;
				}
				$this->download_all($id);
				return;
		}

		show_404();
	}

	/**
	 * @param string $html_title
	 */
	private function _render_vue_shell($html_title)
	{
		$this->load->helper('vite_helper');

		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

		$pu    = parse_url(site_url('admin/citations'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/citations';

		$view_data = array(
			'api_base_url'          => site_url('api/admin/citations'),
			'catalog_api_url'       => site_url('api/admin/catalog'),
			'study_edit_base'       => site_url('admin/catalog/edit/'),
			'public_citation_base'  => site_url('citations/'),
			'codelists_admin_url'   => site_url('admin/codelists'),
			'site_url'              => site_url(),
			'base_url'              => base_url(),
			'csrf_token'            => $this->security->get_csrf_hash(),
			'csrf_token_name'       => $this->security->get_csrf_token_name(),
			'assets_base'           => base_url('frontend/dist/'),
			'translations'          => $this->lang->language,
			'router_path_base'      => $rpath,
		);

		$page = array(
			'title'           => $html_title,
			'content'         => $this->load->view('admin/citations/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}

	/**
	 * Download every citation (kept for bookmarks and scripts; the export page does this with filters).
	 *
	 * @param string $format csv | json | bibtex | ris
	 */
	private function download_all($format)
	{
		$this->acl_manager->has_access_or_die('citation', 'view');

		$format = strtolower(trim((string) $format));
		if ( ! in_array($format, array('csv', 'json', 'bibtex', 'ris'), true)) {
			show_404();
		}

		$this->Citation_model->export($format);
	}
}
