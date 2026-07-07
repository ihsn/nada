<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin Menu API
 *
 * REST API for managing menu items.
 * Base URL: /api/admin/menu
 * Auth: session or API key; ACL via menu/* (see constructor).
 */
class Menu extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->model('Menu_model');
		$this->_apply_menu_acl();
	}

	private function _apply_menu_acl()
	{
		$method = strtolower((string) $this->router->fetch_method());
		if ($method === '' || $method === 'index') {
			return;
		}
		if ($method === 'index_get') {
			$this->require_access('menu', 'view');
			return;
		}
		if (preg_match('/delete/', $method)) {
			$this->require_access('menu', 'delete');
			return;
		}
		if ($method === 'publish_post') {
			$this->require_access('menu', 'publish');
			return;
		}
		if (preg_match('/_(post|put)$/', $method)) {
			$this->require_access('menu', 'edit');
		}
	}

	/**
	 * GET /api/admin/menu — return all menus as nested tree
	 */
	public function index_get()
	{
		try {
			$menus = $this->Menu_model->get_menu_tree_array();
			$this->response(array('status' => 'success', 'menus' => $menus), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/{id}/delete — POST alias for menu_delete
	 */
	public function menu_delete_post($id)
	{
		return $this->menu_delete($id);
	}

	/**
	 * DELETE /api/admin/menu/{id} — delete a menu item
	 * Re-parents children to pid=0 first, then deletes the item.
	 */
	public function menu_delete($id)
	{
		try {
			$id = (int) $id;
			if ($id <= 0) {
				$this->response(array('status' => 'error', 'message' => 'Invalid ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			// Re-parent any children to top level (pid=0)
			$this->db->where('pid', $id);
			$children = $this->db->get('menus')->result_array();
			foreach ($children as $child) {
				$this->Menu_model->update($child['id'], array('pid' => 0, 'weight' => $child['weight']));
			}

			// Delete the item
			$this->Menu_model->delete($id);

			$this->response(array('status' => 'success'), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/{id}/publish — toggle publish state
	 */
	public function publish_post($id)
	{
		try {
			$id = (int) $id;
			if ($id <= 0) {
				$this->response(array('status' => 'error', 'message' => 'Invalid ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$body = json_decode($this->input->raw_input_stream, true);
			$published = isset($body['published']) ? (int) $body['published'] : 0;

			$this->Menu_model->update($id, array('published' => $published));

			$this->response(array('status' => 'success'), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/reorder — bulk reorder/reparent menu items
	 * Body: JSON array of { id, pid, weight }
	 */
	public function reorder_post()
	{
		try {
			$data = json_decode($this->input->raw_input_stream, true);

			if (!is_array($data)) {
				$this->response(array('status' => 'error', 'message' => 'Invalid payload'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			foreach ($data as $item) {
				if (!isset($item['id']) || !is_numeric($item['id'])) {
					continue;
				}
				$this->Menu_model->update((int) $item['id'], array(
					'pid'    => isset($item['pid'])    ? (int) $item['pid']    : 0,
					'weight' => isset($item['weight']) ? (int) $item['weight'] : 0,
				));
			}

			$this->response(array('status' => 'success'), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
