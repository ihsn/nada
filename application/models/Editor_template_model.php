<?php

/**
 * File-registry form templates (catalog metadata editor + data deposit).
 *
 * Catalog cores: config/editor_templates.php.
 * Deposit cores: config/deposit_templates.php.
 * Same ME JSON shape and loader; separate files, UIDs, and override dirs.
 *
 * Site overrides (same basename):
 *   {userdata_path}/templates/editor/{filename}
 *   {userdata_path}/templates/deposit/{filename}
 */
class Editor_template_model extends ci_model {

	private $core_templates = array();
	private $editor_template_defaults = array();
	private $editor_template_custom_path = '';
	private $ci;

	private static $registry_meta_keys = array(
		'editor_template_path',
		'editor_template_custom_path',
		'editor_template_defaults',
		'editor_template_meta_keys',
		'deposit_template_path',
		'deposit_template_defaults',
		'deposit_template_meta_keys',
	);

	public function __construct()
	{
		parent::__construct();
		$this->ci =& get_instance();
		$this->init_core_templates();
	}

	function init_core_templates()
	{
		$editor = $this->load_registry_file(APPPATH.'config/editor_templates.php');
		$this->apply_registry($editor, 'catalog');

		$deposit_path = APPPATH.'config/deposit_templates.php';
		if (is_file($deposit_path)) {
			$deposit = $this->load_registry_file($deposit_path);
			$this->apply_registry($deposit, 'deposit');
		}
	}

	/**
	 * Load a registry PHP file that assigns $config.
	 *
	 * @param string $path
	 * @return array
	 */
	private function load_registry_file($path)
	{
		if (!is_file($path)) {
			throw new Exception('Template registry not found: '.$path);
		}

		$config = array();
		require $path;

		if (!isset($config) || !is_array($config) || count($config) === 0) {
			throw new Exception('Template registry not loaded: '.$path);
		}

		return $config;
	}

	/**
	 * Merge one registry file into the in-memory list and defaults map.
	 *
	 * @param array  $config
	 * @param string $context catalog|deposit
	 * @return void
	 */
	private function apply_registry($config, $context)
	{
		$meta_keys = self::$registry_meta_keys;
		if ($context === 'catalog' && isset($config['editor_template_meta_keys']) && is_array($config['editor_template_meta_keys'])) {
			$meta_keys = array_values(array_unique(array_merge($meta_keys, $config['editor_template_meta_keys'])));
		}
		if ($context === 'deposit' && isset($config['deposit_template_meta_keys']) && is_array($config['deposit_template_meta_keys'])) {
			$meta_keys = array_values(array_unique(array_merge($meta_keys, $config['deposit_template_meta_keys'])));
		}

		if ($context === 'catalog') {
			if (isset($config['editor_template_custom_path'])) {
				$this->editor_template_custom_path = (string) $config['editor_template_custom_path'];
			}
			if (isset($config['editor_template_defaults']) && is_array($config['editor_template_defaults'])) {
				$this->editor_template_defaults['catalog'] = $this->catalog_defaults_map($config['editor_template_defaults']);
			}
		}

		if ($context === 'deposit') {
			if (isset($config['deposit_template_defaults']) && is_array($config['deposit_template_defaults'])) {
				$this->editor_template_defaults['deposit'] = $config['deposit_template_defaults'];
			} elseif (isset($config['editor_template_defaults']['deposit']) && is_array($config['editor_template_defaults']['deposit'])) {
				$this->editor_template_defaults['deposit'] = $config['editor_template_defaults']['deposit'];
			}
		}

		foreach ($config as $key => $templates) {
			if (in_array($key, $meta_keys, true)) {
				continue;
			}

			if (!is_array($templates)) {
				continue;
			}

			foreach ($templates as $template) {
				if (!is_array($template) || empty($template['uid'])) {
					continue;
				}

				// Prefer `file` (APPPATH-relative). Legacy key was `template` under views/.
				$relative_file = '';
				if (!empty($template['file'])) {
					$relative_file = $template['file'];
				} elseif (!empty($template['template'])) {
					$relative_file = (strpos($template['template'], 'templates/') === 0)
						? $template['template']
						: 'views/'.$template['template'];
				}

				$resolved_path = $this->resolve_core_template_path($relative_file);
				$template_ref = $resolved_path ? $relative_file : '';

				$this->core_templates[] = array(
					'uid' => $template['uid'],
					'template_type' => 'core',
					'name' => isset($template['name']) ? $template['name'] : $template['uid'],
					'data_type' => $key,
					'context' => isset($template['context']) ? $template['context'] : $context,
					'lang' => isset($template['lang']) ? $template['lang'] : 'en',
					'version' => isset($template['version']) ? $template['version'] : null,
					'description' => isset($template['description']) ? $template['description'] : null,
					'file' => $relative_file,
					'template' => $template_ref,
				);
			}
		}
	}

	/**
	 * Accept flat type=>uid maps or the older nested ['catalog'=>...].
	 *
	 * @param array $defaults
	 * @return array
	 */
	private function catalog_defaults_map($defaults)
	{
		if (isset($defaults['catalog']) && is_array($defaults['catalog'])) {
			$defaults = $defaults['catalog'];
		}
		unset($defaults['deposit']);
		return $defaults;
	}

	/**
	 * Override directory: configured path, or {userdata_path}/templates/editor.
	 *
	 * @return string
	 */
	function get_override_directory()
	{
		$configured = trim(str_replace('\\', '/', $this->editor_template_custom_path));
		if ($configured !== '') {
			return rtrim($configured, '/');
		}

		$userdata = $this->ci->config->item('userdata_path');
		if ($userdata === null || $userdata === false || trim((string) $userdata) === '') {
			return '';
		}

		return rtrim(str_replace('\\', '/', (string) $userdata), '/').'/templates/editor';
	}

	/**
	 * Override directory for a registered file.
	 * Deposit templates: {userdata_path}/templates/deposit.
	 * Editor templates: get_override_directory().
	 *
	 * @param string $relative_file Path relative to APPPATH
	 * @return string
	 */
	function get_override_directory_for_file($relative_file)
	{
		$relative_file = ltrim(str_replace('\\', '/', (string) $relative_file), '/');
		if (strpos($relative_file, 'templates/deposit/') === 0) {
			$userdata = $this->ci->config->item('userdata_path');
			if ($userdata === null || $userdata === false || trim((string) $userdata) === '') {
				return '';
			}
			return rtrim(str_replace('\\', '/', (string) $userdata), '/').'/templates/deposit';
		}

		return $this->get_override_directory();
	}

	/**
	 * Resolve a registered template file.
	 * Prefers {override_dir}/{basename}, then APPPATH/{file}.
	 *
	 * @param string $relative_file Path relative to APPPATH
	 * @return string|null Absolute or cwd-relative path if found
	 */
	function resolve_core_template_path($relative_file)
	{
		if ($relative_file === '' || $relative_file === null) {
			return null;
		}

		$relative_file = ltrim(str_replace('\\', '/', $relative_file), '/');
		if ($relative_file === '' || strpos($relative_file, '..') !== false) {
			return null;
		}

		$basename = basename($relative_file);
		if ($basename === '' || $basename === '.' || $basename === '..') {
			return null;
		}

		$override_dir = $this->get_override_directory_for_file($relative_file);
		if ($override_dir !== '') {
			$custom_path = $override_dir.'/'.$basename;
			if (is_file($custom_path)) {
				return $custom_path;
			}
		}

		$core_path = APPPATH.$relative_file;
		if (is_file($core_path)) {
			return $core_path;
		}

		return null;
	}

	/**
	 * Default template UID for a context (catalog|deposit) and data type.
	 * Deposit types without a deposit-owned default fall back to the catalog core.
	 *
	 * @param string $data_type
	 * @param string $context
	 * @return string|null
	 */
	function get_default_template_uid($data_type, $context = 'catalog')
	{
		if (isset($this->editor_template_defaults[$context][$data_type])) {
			return $this->editor_template_defaults[$context][$data_type];
		}

		$cores = $this->get_core_templates_by_type($data_type, $context);
		if (!empty($cores[0]['uid'])) {
			return $cores[0]['uid'];
		}

		if ($context === 'deposit') {
			$cores = $this->get_core_templates_by_type($data_type, 'catalog');
			if (!empty($cores[0]['uid'])) {
				return $cores[0]['uid'];
			}
		}

		return null;
	}

	/**
	 * Load default core template JSON for a context + data type (file registry).
	 *
	 * @param string $data_type
	 * @param string $context catalog|deposit
	 * @return array|null Full template row including decoded `template` JSON
	 */
	function get_default_core_template($data_type, $context = 'catalog')
	{
		$uid = $this->get_default_template_uid($data_type, $context);
		if (!$uid) {
			return null;
		}
		return $this->get_template_by_uid($uid);
	}

	function get_core_template_by_uid($uid)
	{
		foreach ($this->core_templates as $template) {
			if ($template['uid'] == $uid) {
				return $template;
			}
		}
	}

	function get_core_templates_by_type($type, $context = 'catalog')
	{
		$templates_ = array();
		foreach ($this->core_templates as $template) {
			if ($type != $template['data_type']) {
				continue;
			}
			$row_context = isset($template['context']) ? $template['context'] : 'catalog';
			if ($row_context !== $context) {
				continue;
			}
			$templates_[] = $template;
		}
		return $templates_;
	}

	function get_template_by_uid($uid)
	{
		$template = $this->get_core_template_by_uid($uid);
		if ($template) {
			$template['template'] = $this->get_core_template_json($template['uid']);
			return $template;
		}

		return null;
	}

	function get_core_template_json($uid)
	{
		foreach ($this->core_templates as $template) {
			if ($template['uid'] != $uid) {
				continue;
			}

			$relative = !empty($template['file']) ? $template['file'] : $template['template'];
			$template_path = $this->resolve_core_template_path($relative);

			if (!$template_path) {
				throw new Exception('Template not found: '.$relative);
			}

			return json_decode(file_get_contents($template_path), true);
		}

		return null;
	}

}
