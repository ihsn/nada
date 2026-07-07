<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Codelists — page controller for managing codelists, items, and groups.
 * Vue 3 + Vite app is loaded in the view.
 */
class Codelists extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->template->set_template('admin5');
		$this->lang->load('general');
		$this->acl_manager->has_access_or_die('codelist', 'view');
	}

	/**
	 * Index: render Vue 3 app mount and config.
	 */
	public function index()
	{
		$this->load->helper('vite_helper');
		$supported = $this->config->item('supported_languages');
		$language_codes = $this->config->item('language_codes');
		if (!is_array($supported)) {
			$supported = [];
		}
		if (!is_array($language_codes)) {
			$language_codes = [];
		}
		$enabled_languages = [];
		foreach ($supported as $folder) {
			$info = isset($language_codes[$folder]) ? $language_codes[$folder] : [];
			$info = is_array($info) ? $info : (array) $info;
			$code = isset($info['code']) && trim($info['code']) !== '' ? trim($info['code']) : $folder;
			$display = isset($info['display']) ? $info['display'] : ucfirst($folder);
			$enabled_languages[] = ['code' => $code, 'display' => $display];
		}
		$primary_folder = $this->config->item('language');
		if ($primary_folder && trim($primary_folder) !== '') {
			$info = isset($language_codes[$primary_folder]) ? $language_codes[$primary_folder] : [];
			$info = is_array($info) ? $info : (array) $info;
			$code = isset($info['code']) && trim($info['code']) !== '' ? trim($info['code']) : $primary_folder;
			$display = isset($info['display']) ? $info['display'] : ucfirst($primary_folder);
			$primary_entry = ['code' => $code, 'display' => $display];
			$enabled_languages = array_values(array_filter($enabled_languages, function ($e) use ($code) {
				return ($e['code'] ?? '') !== $code;
			}));
			array_unshift($enabled_languages, $primary_entry);
		}

		$view_data = [
			'api_base_url'       => site_url('api/admin/codelists/'),
			'site_url'           => site_url(),
			'base_url'           => base_url(),
			'csrf_token'         => $this->security->get_csrf_hash(),
			'assets_base'        => base_url('frontend/dist/'),
			'translations'       => $this->lang->language,
			'enabled_languages'  => $enabled_languages,
		];

		$page = [
			'title'           => 'Codelists',
			'content'         => $this->load->view('admin/codelists/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		];
		$this->load->view('layouts/admin_vue', $page);
	}
}
