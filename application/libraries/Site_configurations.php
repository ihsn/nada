<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Loads the configurations from the database
 *
 **/
class Site_configurations{

	private $ci;

	/**
	 * __construct
	 *
	 * @return void
	 **/
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('configurations_model');

		//load settings from db
		$settings = $this->ci->configurations_model->load();

		//list of settings stored in db in JSON format
		$json_formatted = array('admin_allowed_ip', 'admin_allowed_hosts', 'supported_languages');

		//update the config array with values from DB
		if ($settings)
		{
			foreach ($settings as $setting)
			{
				//setting is stored in DB using JSON array format
				if (in_array($setting['name'], $json_formatted))
				{
					//check if JSON is valid
					if (json_decode($setting['value']) !== FALSE)
					{
						$this->ci->config->set_item($setting['name'], json_decode($setting['value'], TRUE));
					}
				}
				else
				{
					$this->ci->config->set_item($setting['name'], $setting['value']);
				}
			}
		}

		// Build language_codes and normalize supported_languages
		$lang_data = $this->ci->config->item('supported_languages');
		if (is_array($lang_data) && !empty($lang_data))
		{
			$first = $lang_data[0];
			// New format: each entry has 'folder' (object or array)
			if ((is_array($first) && isset($first['folder'])) ||
				(is_object($first) && isset($first->folder)))
			{
				$language_codes = array();
				$folder_names   = array();
				foreach ($lang_data as $entry)
				{
					$entry  = is_array($entry) ? $entry : (array) $entry;
					$folder = isset($entry['folder']) ? $entry['folder'] : null;
					if (!$folder) continue;
					$folder_names[] = $folder;
					$language_codes[$folder] = array(
						'name'          => $folder,
						'language_file' => $folder,
						'display'       => isset($entry['display'])   ? $entry['display']   : ucfirst($folder),
						'code'          => isset($entry['code'])      ? $entry['code']      : '',
						'direction'     => isset($entry['direction']) ? $entry['direction'] : 'ltr',
					);
				}
				$this->ci->config->set_item('language_codes', $language_codes);
				$this->ci->config->set_item('supported_languages', $folder_names);
			}
			// Legacy format: array of folder name strings
			elseif (is_string($first))
			{
				$language_codes = array();
				foreach ($lang_data as $folder)
				{
					if (!is_string($folder)) continue;
					$language_codes[$folder] = array(
						'name'          => $folder,
						'language_file' => $folder,
						'display'       => ucfirst($folder),
						'code'          => '',
						'direction'     => 'ltr',
					);
				}
				$this->ci->config->set_item('language_codes', $language_codes);
				$this->ci->config->set_item('supported_languages', array_values($lang_data));
			}
		}
	}
}