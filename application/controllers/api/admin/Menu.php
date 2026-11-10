<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin Menu API
 *
 * REST API for managing menu items.
 * Base URL: /api/admin/menu
 * Auth: session or API key; each endpoint checks its menu/* privilege.
 */
class Menu extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper('url');
		$this->load->model('Menu_model');
	}

	/**
	 * GET /api/admin/menu — return all menus as nested tree
	 */
	public function index_get()
	{
		$this->require_access('menu', 'view');

		try {
			$menus = $this->Menu_model->get_menu_tree_array();
			$this->response(array('status' => 'success', 'menus' => $menus), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/menu/item/{id} — single menu item (includes body)
	 */
	public function item_get($id = null)
	{
		$this->require_access('menu', 'view');

		try {
			$id = (int) $id;
			if ($id <= 0) {
				$this->response(array('status' => 'error', 'message' => 'Invalid ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$row = $this->Menu_model->select_single($id);
			if (!$row) {
				$this->response(array('status' => 'error', 'message' => 'Not found'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$this->response(array('status' => 'success', 'item' => $this->_format_item($row)), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/menu/parents — top-level items for parent selector
	 * Optional query: exclude_id
	 */
	public function parents_get()
	{
		$this->require_access('menu', 'view');

		try {
			$exclude = $this->get('exclude_id');
			$exclude = is_numeric($exclude) ? (int) $exclude : NULL;
			$parents = $this->Menu_model->get_top_level_items($exclude);
			$this->response(array('status' => 'success', 'parents' => $parents), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu — create menu item (page or link)
	 */
	public function index_post()
	{
		$this->require_access('menu', 'create');

		try {
			$input = $this->raw_json_input();
			if (!is_array($input)) {
				throw new Exception('JSON body required');
			}

			$options = $this->_normalize_payload($input, NULL);
			$ok = $this->Menu_model->insert($options);
			if ($ok !== TRUE) {
				throw new Exception('Insert failed');
			}

			$id = (int) $this->db->insert_id();
			$row = $this->Menu_model->select_single($id);
			$this->response(array(
				'status' => 'success',
				'item'   => $this->_format_item($row),
			), REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/item/{id} — update menu item
	 */
	public function item_post($id = null)
	{
		$this->require_access('menu', 'edit');
		return $this->_update_item($id);
	}

	/**
	 * PUT /api/admin/menu/item/{id} — update menu item
	 */
	public function item_put($id = null)
	{
		$this->require_access('menu', 'edit');
		return $this->_update_item($id);
	}

	private function _update_item($id)
	{
		try {
			$id = (int) $id;
			if ($id <= 0) {
				$this->response(array('status' => 'error', 'message' => 'Invalid ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$existing = $this->Menu_model->select_single($id);
			if (!$existing) {
				$this->response(array('status' => 'error', 'message' => 'Not found'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$input = $this->raw_json_input();
			if (!is_array($input)) {
				throw new Exception('JSON body required');
			}

			$options = $this->_normalize_payload($input, $id);
			$ok = $this->Menu_model->update($id, $options);
			if ($ok !== TRUE) {
				throw new Exception('Update failed');
			}

			$row = $this->Menu_model->select_single($id);
			$this->response(array(
				'status' => 'success',
				'item'   => $this->_format_item($row),
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/menu_delete/{id} — POST alias for menu_delete
	 */
	public function menu_delete_post($id = null)
	{
		$this->require_access('menu', 'delete');
		return $this->_delete_item($id);
	}

	/**
	 * DELETE /api/admin/menu/menu/{id} — delete a menu item
	 */
	public function menu_delete($id = null)
	{
		$this->require_access('menu', 'delete');
		return $this->_delete_item($id);
	}

	/**
	 * Re-parents children to pid=0 first, then deletes the item.
	 */
	private function _delete_item($id)
	{
		try {
			$id = (int) $id;
			if ($id <= 0) {
				$this->response(array('status' => 'error', 'message' => 'Invalid ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$this->db->where('pid', $id);
			$children = $this->db->get('menus')->result_array();
			foreach ($children as $child) {
				$this->Menu_model->update($child['id'], array('pid' => 0, 'weight' => $child['weight']));
			}

			$this->Menu_model->delete($id);

			$this->response(array('status' => 'success'), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/{id}/publish — toggle publish state
	 */
	public function publish_post($id = null)
	{
		$this->require_access('menu', 'publish');

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
	 * POST /api/admin/menu/batch_delete
	 * Body: { "ids": [1, 2, 3] }
	 * Re-parents children of each deleted item to pid=0 (same as single delete).
	 */
	public function batch_delete_post()
	{
		$this->require_access('menu', 'delete');

		try {
			$input = $this->raw_json_input();
			$ids   = $this->_parse_id_list(is_array($input) ? $input : array());
			if ($ids === array()) {
				$this->response(array('status' => 'error', 'message' => 'No valid IDs provided'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$deleted = 0;
			foreach ($ids as $id) {
				$this->db->where('pid', $id);
				$children = $this->db->get('menus')->result_array();
				foreach ($children as $child) {
					$this->Menu_model->update($child['id'], array('pid' => 0, 'weight' => $child['weight']));
				}
				$this->Menu_model->delete($id);
				$deleted++;
			}

			$this->response(array(
				'status'  => 'success',
				'deleted' => $deleted,
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/menu/batch_publish
	 * Body: { "ids": [1, 2, 3], "published": 0|1 }
	 */
	public function batch_publish_post()
	{
		$this->require_access('menu', 'publish');

		try {
			$input = $this->raw_json_input();
			if (!is_array($input)) {
				throw new Exception('JSON body required');
			}
			$ids = $this->_parse_id_list($input);
			if ($ids === array()) {
				$this->response(array('status' => 'error', 'message' => 'No valid IDs provided'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$published = isset($input['published']) ? (int) $input['published'] : 0;
			$published = $published ? 1 : 0;

			$updated = 0;
			foreach ($ids as $id) {
				$this->Menu_model->update($id, array('published' => $published));
				$updated++;
			}

			$this->response(array(
				'status'    => 'success',
				'updated'   => $updated,
				'published' => $published,
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * @param array $input
	 * @return list<int>
	 */
	private function _parse_id_list(array $input)
	{
		$raw = array();
		if (isset($input['ids']) && is_array($input['ids'])) {
			$raw = $input['ids'];
		} elseif (array_keys($input) === range(0, count($input) - 1)) {
			// Allow a bare JSON array of ids
			$raw = $input;
		}

		$ids = array();
		foreach ($raw as $value) {
			if (is_numeric($value) && (int) $value > 0) {
				$ids[] = (int) $value;
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * POST /api/admin/menu/reorder — bulk reorder/reparent menu items
	 * Body: JSON array of { id, pid, weight }
	 */
	public function reorder_post()
	{
		$this->require_access('menu', 'edit');

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

	/**
	 * Normalize and validate create/update payload.
	 *
	 * @param array    $input
	 * @param int|null $id Existing id for updates (null = create)
	 * @return array
	 * @throws Exception
	 */
	private function _normalize_payload(array $input, $id)
	{
		$title = isset($input['title']) ? trim((string) $input['title']) : '';
		$url   = isset($input['url']) ? trim((string) $input['url']) : '';
		if ($title === '') {
			throw new Exception('Title is required');
		}
		if ($url === '') {
			throw new Exception('URL is required');
		}
		if (strlen($title) > 255 || strlen($url) > 255) {
			throw new Exception('Title and URL must be at most 255 characters');
		}

		$linktype = isset($input['linktype']) ? (int) $input['linktype'] : 0;
		if ($linktype !== 0 && $linktype !== 1) {
			$linktype = 0;
		}

		// Pages: require a clean slug. Do not silently rewrite invalid input (e.g. https://…).
		if ($linktype === 0) {
			if ($url === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $url)) {
				throw new Exception('Page URL must use only lowercase letters, numbers, and hyphens (no spaces, slashes, or special characters)');
			}
		}

		if ($this->Menu_model->url_exists($url, $id) > 0) {
			throw new Exception('URL already exists');
		}

		$pid = isset($input['pid']) && is_numeric($input['pid']) ? (int) $input['pid'] : 0;
		if ($pid < 0) {
			$pid = 0;
		}
		if ($id !== NULL && $pid === (int) $id) {
			throw new Exception('Item cannot be its own parent');
		}

		$published = isset($input['published']) ? (int) $input['published'] : 1;
		$published = $published ? 1 : 0;

		$target = isset($input['target']) ? (int) $input['target'] : 0;
		$target = $target ? 1 : 0;

		$weight = isset($input['weight']) && is_numeric($input['weight']) ? (int) $input['weight'] : 0;
		if ($weight < 0) {
			$weight = 0;
		}

		$body = '';
		if ($linktype === 0 && array_key_exists('body', $input)) {
			$body = (string) $input['body'];
		}

		return array(
			'title'     => $title,
			'url'       => $url,
			'body'      => $body,
			'pid'       => $pid,
			'published' => $published,
			'target'    => $target,
			'weight'    => $weight,
			'linktype'  => $linktype,
		);
	}

	private function _format_item($row)
	{
		if (!is_array($row)) {
			return $row;
		}
		return array(
			'id'        => isset($row['id']) ? (int) $row['id'] : null,
			'url'       => isset($row['url']) ? $row['url'] : '',
			'title'     => isset($row['title']) ? $row['title'] : '',
			'body'      => isset($row['body']) ? $row['body'] : '',
			'published' => isset($row['published']) ? (int) $row['published'] : 0,
			'target'    => isset($row['target']) ? (int) $row['target'] : 0,
			'linktype'  => isset($row['linktype']) ? (int) $row['linktype'] : 0,
			'weight'    => isset($row['weight']) ? (int) $row['weight'] : 0,
			'pid'       => isset($row['pid']) ? (int) $row['pid'] : 0,
			'changed'   => isset($row['changed']) ? (int) $row['changed'] : null,
		);
	}
}
