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
		$controller->load->library('catalog_browse_service');
		$controller->catalog_browse_service->set_active_repo(
			$active_repo_id ? $active_repo_id : 'central'
		);

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
		);

		$content = $controller->load->view('catalog_vue/index', $data, true);
		$controller->template->write('title', t('data_catalog'), true);
		$controller->template->write('content', $content, true);
		$controller->template->render();
	}
}
