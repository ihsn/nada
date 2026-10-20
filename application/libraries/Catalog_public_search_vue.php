<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Renders the public catalog Vue search shell (catalog-search Vite app).
 */
class Catalog_public_search_vue {

	/**
	 * @param object $controller MY_Controller instance (Catalog)
	 * @param string|null $active_repo_id
	 * @param array|null $active_repo
	 */
	public function render($controller, $active_repo_id = null, $active_repo = null)
	{
		$controller->load->helper('vite_helper');
		$controller->load->helper('catalog');
		$controller->load->library('catalog_browse_service');
		$controller->catalog_browse_service->set_active_repo(
			$active_repo_id ? $active_repo_id : 'central'
		);
		$controller->catalog_browse_service->active_tab = $controller->catalog_browse_service->validate_tab_type(
			(string) $controller->input->get('tab_type')
		);

		$ssr = $this->search($controller);
		$page_not_found = false;
		if ($ssr === null) {
			// a page past what the search pages by number (an old link, a crawler, a scraper) or a cursor of another
			// search: HTTP 404, so a crawler or scraper knows it hit the limit, with the first page of the same search
			// and "Page not found" at the top for a person (the page then shows the page-1 URL)
			$page_not_found = true;
			$controller->output->set_status_header(404);
			unset($_GET['page'], $_GET['cursor']);
			unset($controller->catalog_search); // CI loads a library once: search again with the page-1 parameters
			$ssr = $this->search($controller) ?? $this->no_search();
		}
		extract($ssr);

		$data = array(
			'site_url' => site_url(),
			'base_url' => base_url(),
			'api_base_url' => site_url('api/catalog/'),
			'csrf_token' => $controller->security->get_csrf_hash(),
			'assets_base' => base_url('frontend/dist/'),
			'active_repo' => $active_repo_id,
			'active_repo_object' => $active_repo,
			'translations' => $controller->lang->language,
			'site_config' => $controller->catalog_browse_service->site_config_for_client(),
			'initial_search' => $initial_search,
			'initial_search_query_key' => $initial_search_query_key,
			'ssr_rows' => $ssr_rows,
			'ssr_search_type' => $ssr_search_type,
			'ssr_found' => $ssr_found,
			'ssr_page' => $ssr_page,
			'ssr_ps' => $ssr_ps,
			'ssr_max_offset' => $ssr_max_offset,
			'ssr_repo' => $active_repo_id,
			'page_not_found' => $page_not_found,
		);

		$content = $controller->load->view('catalog_vue/index', $data, true);

		// Lean public shell: Bootstrap/FA for nav only — no Google Fonts or classic catalog CSS.
		$controller->template->set_template('public_vue');
		$controller->template->write('title', t('data_catalog'), true);
		$controller->template->write('content', $content, true);
		$controller->template->render();
	}

	/**
	 * Run the request's search for the server-rendered first paint and the Vue bootstrap.
	 *
	 * @return array|null the page's search variables; null when the request pages past what the search can serve
	 */
	private function search($controller)
	{
		$out = $this->no_search();
		$out['initial_search_query_key'] = catalog_browse_query_fingerprint();

		try {
			$search_data = $controller->catalog_browse_service->run_search(true);
			$initial_search = $controller->catalog_browse_service->build_browse_client_response($search_data, true);
			$initial_search['queryKey'] = $out['initial_search_query_key'];
			$out['initial_search'] = $initial_search;

			$search_type = $initial_search['search_type'] ?? 'study';
			$result = isset($initial_search['result']) && is_array($initial_search['result'])
				? $initial_search['result']
				: array();
			$parsed = catalog_browse_parse_request_query();
			$out['ssr_ps'] = isset($parsed['ps']) ? (int) $parsed['ps'] : 15;

			if ($search_type === 'study' || $search_type === 'variable') {
				$out['ssr_search_type'] = $search_type;
				$out['ssr_rows'] = $search_type === 'study'
					? catalog_browse_merge_featured_rows($initial_search)
					: (isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : array());
				$out['ssr_found'] = isset($result['found']) ? (int) $result['found'] : 0;
				$out['ssr_page'] = isset($result['page']) ? (int) $result['page'] : 1;
				$out['ssr_max_offset'] = isset($result['max_offset']) ? (int) $result['max_offset'] : null;
			}
		} catch (RuntimeException | Search_cursor_exception $e) {
			if (catalog_search_paging_error($e) !== null) {
				return null;
			}
			$out['initial_search'] = null;
		}

		return $out;
	}

	private function no_search()
	{
		return array(
			'initial_search' => null,
			'initial_search_query_key' => '',
			'ssr_rows' => array(),
			'ssr_search_type' => '',
			'ssr_found' => 0,
			'ssr_page' => 1,
			'ssr_ps' => 15,
			'ssr_max_offset' => null,
		);
	}
}
