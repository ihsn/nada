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

		$initial_search = null;
		$initial_search_query_key = catalog_browse_query_fingerprint();
		$ssr_rows = array();
		$ssr_search_type = '';
		$ssr_found = 0;
		$ssr_page = 1;
		$ssr_ps = 15;

		try {
			$search_data = $controller->catalog_browse_service->run_search(true);
			$initial_search = $controller->catalog_browse_service->build_browse_client_response($search_data, true);
			$initial_search['queryKey'] = $initial_search_query_key;

			$search_type = $initial_search['search_type'] ?? 'study';
			$result = isset($initial_search['result']) && is_array($initial_search['result'])
				? $initial_search['result']
				: array();
			$parsed = catalog_browse_parse_request_query();
			$ssr_ps = isset($parsed['ps']) ? (int) $parsed['ps'] : 15;

			if ($search_type === 'study') {
				$ssr_search_type = 'study';
				$ssr_rows = catalog_browse_merge_featured_rows($initial_search);
				$ssr_found = isset($result['found']) ? (int) $result['found'] : 0;
				$ssr_page = isset($result['page']) ? (int) $result['page'] : 1;
			} elseif ($search_type === 'variable') {
				$ssr_search_type = 'variable';
				$ssr_rows = isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : array();
				$ssr_found = isset($result['found']) ? (int) $result['found'] : 0;
				$ssr_page = isset($result['page']) ? (int) $result['page'] : 1;
			}
		} catch (RuntimeException $e) {
			$initial_search = null;
		}

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
			'ssr_repo' => $active_repo_id,
		);

		$content = $controller->load->view('catalog_vue/index', $data, true);

		// Lean public shell: Bootstrap/FA for nav only — no Google Fonts or classic catalog CSS.
		$controller->template->set_template('public_vue');
		$controller->template->write('title', t('data_catalog'), true);
		$controller->template->write('content', $content, true);
		$controller->template->render();
	}
}
