<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin Codelists API
 *
 * REST API for managing codelists, items, item translations, groups, group items, group translations.
 * Base URL: /api/admin/codelists
 * Auth: session or API key (is_authenticated_or_die).
 */
class Codelists extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');
		$this->load->model('Codelist_group_model');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}
		return parent::_auth_override_check();
	}

	/**
	 * GET /api/admin/codelists — list all codelists (with optional item_count, group_count).
	 */
	public function index_get()
	{
		try {
			$with_counts = $this->get('with_counts') !== '0' && $this->get('with_counts') !== false;
			$rows = $this->Codelist_model->get_all_codelists($with_counts);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelists' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists — create codelist. Body: { "name": "...", "description": "..." }
	 */
	public function index_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name, optional description');
			}
			$id = $this->Codelist_model->create_codelist($input);
			$row = $this->Codelist_model->get_codelist_by_id($id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/by_name/{name} — one codelist by name with items and groups (nested).
	 */
	public function by_name_get($name)
	{
		try {
			$codelist = $this->Codelist_model->get_codelist_by_name($name);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$id = (int) $codelist['id'];
			$codelist['items']  = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/item/{id} — one codelist with items and groups (nested).
	 */
	public function item_get($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$codelist['items']       = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups']      = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$codelist['has_defaults'] = $this->_seed_path($codelist['name']) !== null;
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/item_restore/{id} — restore a codelist from its seed file.
	 * Deletes all existing items and groups, re-inserts from seed, preserves translations.
	 */
	public function item_restore_post($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$path = $this->_seed_path($codelist['name']);
			if (!$path) {
				$this->set_response(['status' => 'error', 'message' => 'No seed file found for this codelist.'], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$seed = json_decode(file_get_contents($path), true);
			if (!$seed || !isset($seed['items'])) {
				$this->set_response(['status' => 'error', 'message' => 'Invalid seed file.'], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			// 1. Save existing item translations keyed by code
			$item_translations = array();
			$existing_items = $this->Codelist_item_model->get_items_by_codelist($id, true);
			foreach ($existing_items as $item) {
				if (!empty($item['translations'])) {
					$item_translations[$item['code']] = $item['translations'];
				}
			}

			// 2. Save existing group translations keyed by group name
			$group_translations = array();
			$existing_groups = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			foreach ($existing_groups as $group) {
				if (!empty($group['translations'])) {
					$group_translations[$group['name']] = $group['translations'];
				}
			}

			// 3. Delete all groups (cascades group_items and group_translations)
			$this->db->where('codelist_id', $id)->delete('codelist_group');

			// 4. Delete all items (cascades item_translations)
			$this->db->where('codelist_id', $id)->delete('codelist_item');

			// 5. Re-insert items and restore translations
			$code_to_id = array();
			foreach ($seed['items'] as $item_data) {
				$this->db->insert('codelist_item', array(
					'codelist_id' => $id,
					'code'        => $item_data['code'],
					'title'       => isset($item_data['title']) ? $item_data['title'] : '',
					'sort_order'  => isset($item_data['sort_order']) ? (int) $item_data['sort_order'] : 0,
				));
				$new_id = $this->db->insert_id();
				$code_to_id[$item_data['code']] = $new_id;

				if (isset($item_translations[$item_data['code']])) {
					foreach ($item_translations[$item_data['code']] as $lang => $title) {
						$this->db->replace('codelist_item_translation', array(
							'codelist_item_id' => $new_id,
							'lang'             => $lang,
							'title'            => $title,
						));
					}
				}
			}

			// 6. Re-insert groups, link items, restore group translations
			$groups = isset($seed['groups']) ? $seed['groups'] : array();
			foreach ($groups as $group_data) {
				$this->db->insert('codelist_group', array(
					'codelist_id' => $id,
					'name'        => $group_data['name'],
					'sort_order'  => isset($group_data['sort_order']) ? (int) $group_data['sort_order'] : 0,
				));
				$group_id = $this->db->insert_id();

				if (!empty($group_data['item_codes'])) {
					foreach ($group_data['item_codes'] as $code) {
						if (isset($code_to_id[$code])) {
							$this->db->insert('codelist_group_item', array(
								'codelist_group_id' => $group_id,
								'codelist_item_id'  => $code_to_id[$code],
								'sort_order'        => 0,
							));
						}
					}
				}

				if (isset($group_translations[$group_data['name']])) {
					foreach ($group_translations[$group_data['name']] as $lang => $title) {
						$this->db->replace('codelist_group_translation', array(
							'codelist_group_id' => $group_id,
							'lang'              => $lang,
							'title'             => $title,
						));
					}
				}
			}

			$codelist                 = $this->Codelist_model->get_codelist_by_id($id);
			$codelist['items']        = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups']       = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$codelist['has_defaults'] = true;

			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Returns the absolute path to the seed file for $name, or null if none exists.
	 */
	private function _seed_path($name)
	{
		$name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
		$path = APPPATH . 'data/codelists/' . $name . '.json';
		return file_exists($path) ? $path : null;
	}

	/**
	 * PUT /api/admin/codelists/item/{id}
	 */
	public function item_put($id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name and/or description');
			}
			$this->Codelist_model->update_codelist($id, $input);
			$row = $this->Codelist_model->get_codelist_by_id((int) $id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Codelist not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/item_delete/{id} — POST alias for DELETE */
	public function item_delete_post($id) { return $this->item_delete($id); }

	/**
	 * DELETE /api/admin/codelists/item/{id}
	 */
	public function item_delete($id)
	{
		try {
			$this->Codelist_model->delete_codelist($id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Codelist not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * GET /api/admin/codelists/item_items/{codelist_id} — list items.
	 */
	public function item_items_get($codelist_id)
	{
		try {
			$items = $this->Codelist_item_model->get_items_by_codelist((int) $codelist_id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['items' => $items],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/item_items/{codelist_id} — create item. Body: { "code", "title", "parent_id?", "sort_order?" }
	 */
	public function item_items_post($codelist_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['code'])) {
				throw new Exception('JSON body required: code, optional title, parent_id, sort_order');
			}
			$id = $this->Codelist_item_model->create_item((int) $codelist_id, $input);
			$row = $this->Codelist_item_model->get_item_by_id($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/items/{item_id}
	 */
	public function items_get($item_id)
	{
		try {
			$row = $this->Codelist_item_model->get_item_by_id((int) $item_id, true);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/codelists/items/{item_id}
	 */
	public function items_put($item_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: code and/or title, parent_id, sort_order');
			}
			$this->Codelist_item_model->update_item((int) $item_id, $input);
			$row = $this->Codelist_item_model->get_item_by_id((int) $item_id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/items_delete/{item_id} — POST alias for DELETE */
	public function items_delete_post($item_id) { return $this->items_delete($item_id); }

	/**
	 * DELETE /api/admin/codelists/items/{item_id}
	 */
	public function items_delete($item_id)
	{
		try {
			$this->Codelist_item_model->delete_item((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $item_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * GET /api/admin/codelists/items_translations/{item_id}
	 */
	public function items_translations_get($item_id)
	{
		try {
			$item = $this->Codelist_item_model->get_item_by_id((int) $item_id, false);
			if (!$item) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$translations = $this->Codelist_item_model->get_item_translations((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/items_translations/{item_id} — body: { "lang", "title" }
	 */
	public function items_translations_post($item_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['lang']) || !isset($input['title'])) {
				throw new Exception('JSON body required: lang, title');
			}
			$this->Codelist_item_model->set_item_translation((int) $item_id, $input['lang'], $input['title']);
			$translations = $this->Codelist_item_model->get_item_translations((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/items_translation_delete/{item_id}/{lang} — POST alias for DELETE */
	public function items_translation_delete_post($item_id, $lang) { return $this->items_translation_delete($item_id, $lang); }

	/**
	 * DELETE /api/admin/codelists/items_translation/{item_id}/{lang}
	 */
	public function items_translation_delete($item_id, $lang)
	{
		try {
			$this->Codelist_item_model->delete_item_translation((int) $item_id, $lang);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'lang' => $lang],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/item_groups/{codelist_id}
	 */
	public function item_groups_get($codelist_id)
	{
		try {
			$groups = $this->Codelist_group_model->get_groups_by_codelist((int) $codelist_id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['groups' => $groups],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/item_groups/{codelist_id} — create group. Body: { "name", "sort_order?" }
	 */
	public function item_groups_post($codelist_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['name'])) {
				throw new Exception('JSON body required: name, optional sort_order');
			}
			$id = $this->Codelist_group_model->create_group((int) $codelist_id, $input);
			$row = $this->Codelist_group_model->get_group_by_id($id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/groups/{group_id}
	 */
	public function groups_get($group_id)
	{
		try {
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/codelists/groups/{group_id}
	 */
	public function groups_put($group_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name and/or sort_order');
			}
			$this->Codelist_group_model->update_group((int) $group_id, $input);
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/groups_delete/{group_id} — POST alias for DELETE */
	public function groups_delete_post($group_id) { return $this->groups_delete($group_id); }

	/**
	 * DELETE /api/admin/codelists/groups/{group_id}
	 */
	public function groups_delete($group_id)
	{
		try {
			$this->Codelist_group_model->delete_group((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $group_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * POST /api/admin/codelists/groups_items/{group_id} — add item. Body: { "codelist_item_id", "sort_order?" }
	 */
	public function groups_items_post($group_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['codelist_item_id'])) {
				throw new Exception('JSON body required: codelist_item_id, optional sort_order');
			}
			$sort = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;
			$this->Codelist_group_model->add_group_item((int) $group_id, (int) $input['codelist_item_id'], $sort);
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/** POST /api/admin/codelists/groups_items_remove_delete/{group_id}/{item_id} — POST alias for DELETE */
	public function groups_items_remove_delete_post($group_id, $item_id) { return $this->groups_items_remove_delete($group_id, $item_id); }

	/**
	 * DELETE /api/admin/codelists/groups_items_remove/{group_id}/{item_id}
	 */
	public function groups_items_remove_delete($group_id, $item_id)
	{
		try {
			$this->Codelist_group_model->remove_group_item((int) $group_id, (int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'codelist_item_id' => (int) $item_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/groups_translations/{group_id}
	 */
	public function groups_translations_get($group_id)
	{
		try {
			$group = $this->Codelist_group_model->get_group_by_id((int) $group_id, false, false);
			if (!$group) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$translations = $this->Codelist_group_model->get_group_translations((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/groups_translations/{group_id} — body: { "lang", "title" }
	 */
	public function groups_translations_post($group_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['lang']) || !isset($input['title'])) {
				throw new Exception('JSON body required: lang, title');
			}
			$this->Codelist_group_model->set_group_translation((int) $group_id, $input['lang'], $input['title']);
			$translations = $this->Codelist_group_model->get_group_translations((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/groups_translation_delete/{group_id}/{lang} — POST alias for DELETE */
	public function groups_translation_delete_post($group_id, $lang) { return $this->groups_translation_delete($group_id, $lang); }

	/**
	 * DELETE /api/admin/codelists/groups_translation/{group_id}/{lang}
	 */
	public function groups_translation_delete($group_id, $lang)
	{
		try {
			$this->Codelist_group_model->delete_group_translation((int) $group_id, $lang);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'lang' => $lang],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
