<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The three Site layout parts (header, footer, styles): load, save, publish, discard, reset and republish.
 *
 * Used by api/admin/site_layout and the package import. Each part has its own builder
 * (Site_header_builder, Site_footer_builder, Site_styles_builder) with the same methods for this:
 * normalize, defaults, published, draft, to_json, published_file, published_file_payload,
 * published_file_in_sync, rebuild_published_file, cache_status.
 *
 * Settings are stored in table site_layout (part, state = published | draft). Public pages read only
 * the published file {catalog_root}/cache/site_<part>/published.json. Publish writes the file, then
 * stores the settings, and puts the old file and row back when either step fails.
 */
class Site_layout_parts {

	public static $parts = array('header', 'footer', 'styles');

	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('Site_layout_model');
		$this->ci->load->library('site_header_builder');
		$this->ci->load->library('site_footer_builder');
		$this->ci->load->library('site_styles_builder');
		$this->ci->load->library('site_layout_images');
	}

	public function is_part($part)
	{
		return in_array($part, self::$parts, true);
	}

	/** @throws Site_layout_error UNKNOWN_PART when $part is not header, footer or styles */
	public function check_part($part)
	{
		if (!$this->is_part($part)) {
			throw new Site_layout_error('UNKNOWN_PART', 404);
		}
		return $part;
	}

	public function builder($part)
	{
		$name = 'site_' . $this->check_part($part) . '_builder';
		return $this->ci->$name;
	}

	/** Settings format version this site writes for a part */
	public function version($part)
	{
		$class = 'Site_' . $this->check_part($part) . '_builder';
		return $class::VERSION;
	}

	/** "header,footer" => known parts in a fixed order; empty => all */
	public function parts_param($value)
	{
		$value = is_string($value) ? trim($value) : '';
		if ($value === '') {
			return self::$parts;
		}
		$asked = array_map('trim', explode(',', strtolower($value)));
		return array_values(array_intersect(self::$parts, $asked));
	}

	// =============================================================================================
	// State shown on the admin page
	// =============================================================================================

	public function state($part)
	{
		$builder = $this->builder($part);
		$state = array(
			'status'        => 'success',
			'part'          => $part,
			'published'     => $builder->published(),
			'draft'         => $builder->draft(),
			'is_default'    => $this->ci->Site_layout_model->get($part, 'published') === '',
			'force_default' => (bool) $this->ci->config->item('site_' . $part . '_force_default'),
			'defaults'      => $builder->defaults(),
			'cache'         => $builder->cache_status(),
			'file_in_sync'  => $builder->published_file_in_sync(),
		);
		$extra = $part . '_state';
		return array_merge($state, $this->$extra($state));
	}

	private function header_state(array $state)
	{
		$builder = $this->ci->site_header_builder;
		$presets = array();
		foreach ($builder->presets() as $preset) {
			$presets[] = array(
				'id'          => $preset['id'],
				'title'       => $preset['title'],
				'description' => $preset['description'],
				'settings'    => $preset['settings'],
			);
		}
		$languages = $this->ci->config->item('supported_languages');
		$theme = $builder->theme_name();
		$local = $builder->local_header_path() !== '';

		return array(
			'published'         => $this->with_image_urls($state['published']),
			'draft'             => $state['draft'] !== null ? $this->with_image_urls($state['draft']) : null,
			'presets'           => $presets,
			'tokens'            => $builder->token_docs(),
			'languages_enabled' => is_array($languages) ? count($languages) : 1,
			'theme_logo_url'    => $builder->logo_url(array('logo' => '')),
			'theme_name'        => $theme,
			'has_local_header'  => $local,
			'theme_header_file' => 'themes/' . $theme . '/' . ($local ? 'header.local.php' : 'header_theme.php'),
			'upload'            => $builder->upload_status(),
			'limits'            => array(
				'html'       => Site_header_builder::MAX_HTML,
				'css'        => Site_header_builder::MAX_CSS,
				'logo'       => Site_layout_images::$limits['logo'],
				'background' => Site_layout_images::$limits['background'],
			),
		);
	}

	private function footer_state(array $state)
	{
		$builder = $this->ci->site_footer_builder;
		$theme = $builder->theme_name();
		$local = $builder->local_footer_path() !== '';

		return array(
			'tokens'            => $builder->token_docs(),
			'theme_name'        => $theme,
			'has_local_footer'  => $local,
			'theme_footer_file' => 'themes/' . $theme . '/' . ($local ? 'footer.local.php' : 'footer_theme.php'),
			'limits'            => array(
				'html' => Site_footer_builder::MAX_HTML,
				'css'  => Site_footer_builder::MAX_CSS,
			),
		);
	}

	private function styles_state(array $state)
	{
		return array(
			'example_css' => $this->ci->site_styles_builder->example_css(),
			'limits'      => array('css' => Site_styles_builder::MAX_CSS),
		);
	}

	/** Header settings with full URLs next to the image paths, for the editor */
	private function with_image_urls(array $settings)
	{
		$url = function ($path) {
			return $path !== '' ? base_url($path) : '';
		};
		$settings['logo_url'] = $url($settings['logo']);
		$settings['header_style']['bg_image_url'] = $url($settings['header_style']['bg_image']);
		foreach ($settings['rows'] as $id => $row) {
			$settings['rows'][$id]['style']['bg_image_url'] = $url($row['style']['bg_image']);
		}
		return $settings;
	}

	// =============================================================================================
	// Changes
	// =============================================================================================

	/**
	 * Clean settings sent by the editor. Refuses invalid values instead of fixing them.
	 *
	 * @return array [settings, warnings] warnings: HTML_CLEANED, CSS_CLEANED
	 * @throws Site_layout_error SETTINGS_REQUIRED, VALIDATION_FAILED
	 */
	public function validate($part, $input)
	{
		if (!is_array($input)) {
			throw new Site_layout_error('SETTINGS_REQUIRED', 400);
		}
		$builder = $this->builder($part);
		$errors = array();
		$settings = $builder->normalize($input, $errors);
		if ($errors) {
			throw new Site_layout_error('VALIDATION_FAILED', 422, $errors);
		}

		if ($part === 'header') {
			foreach ($builder->image_paths($settings) as $path) {
				if (!is_file(FCPATH . $path)) {
					throw new Site_layout_error('VALIDATION_FAILED', 422, array('image' => 'IMAGE_NOT_FOUND'));
				}
			}
		}

		// tell the admin when cleaning removed something from their HTML or CSS
		$warnings = array();
		foreach (array('html' => 'HTML_CLEANED', 'css' => 'CSS_CLEANED') as $key => $code) {
			if (isset($input[$key]) && $this->squash($input[$key]) !== $this->squash(isset($settings[$key]) ? $settings[$key] : '')) {
				$warnings[] = $code;
			}
		}
		return array('settings' => $settings, 'warnings' => $warnings);
	}

	/** Save cleaned settings (array) or their JSON as the 'published' or 'draft' state */
	public function store($part, $state, $settings, $user_id)
	{
		$json = is_array($settings) ? $this->builder($part)->to_json($settings) : (string) $settings;
		$this->ci->Site_layout_model->save($part, $state, $json, $user_id);
	}

	/**
	 * Publish the settings, or the stored draft when $settings is null, then delete the draft.
	 *
	 * @throws Site_layout_error NO_DRAFT, CACHE_WRITE_FAILED
	 */
	public function publish($part, $settings, $user_id)
	{
		$builder = $this->builder($part);
		if ($settings === null) {
			$settings = $builder->draft();
			if ($settings === null) {
				throw new Site_layout_error('NO_DRAFT', 422);
			}
		}

		$model = $this->ci->Site_layout_model;
		$previous = $model->get_row($part, 'published');
		$ok = $builder->published_file()->publish(
			$builder->published_file_payload($settings, $builder->to_json($settings)),
			function () use ($part, $settings, $user_id) {
				$this->store($part, 'published', $settings, $user_id);
			},
			function () use ($model, $part, $previous) {
				$model->restore($part, 'published', $previous);
			}
		);
		if (!$ok) {
			throw new Site_layout_error('CACHE_WRITE_FAILED', 500);
		}

		$model->delete($part, 'draft');
		$this->after_change($part);
	}

	/** Delete the draft */
	public function discard($part)
	{
		$this->ci->Site_layout_model->delete($this->check_part($part), 'draft');
		$this->after_change($part);
	}

	/**
	 * Delete the draft and the published settings: the theme's own header, footer or CSS is shown.
	 *
	 * @throws Site_layout_error CACHE_WRITE_FAILED
	 */
	public function reset($part)
	{
		// the file goes first: if it cannot be removed, visitors would keep seeing it
		if (!$this->builder($part)->published_file()->delete()) {
			throw new Site_layout_error('CACHE_WRITE_FAILED', 500);
		}
		$this->ci->Site_layout_model->delete($part);
	}

	/**
	 * Write the published file again from the published settings (lost file, restored database, upgrade).
	 *
	 * @throws Site_layout_error CACHE_WRITE_FAILED
	 */
	public function republish($part)
	{
		if (!$this->builder($part)->rebuild_published_file()) {
			throw new Site_layout_error('CACHE_WRITE_FAILED', 500);
		}
	}

	private function after_change($part)
	{
		if ($part === 'header') {
			$this->remove_unused_uploads();
		}
	}

	/** Delete uploaded header images that neither the published header nor the draft uses */
	private function remove_unused_uploads()
	{
		$dir = rtrim(FCPATH, '/\\') . '/' . Site_layout_images::UPLOAD_DIR;
		if (!is_dir($dir)) {
			return;
		}
		$builder = $this->ci->site_header_builder;
		$keep = array();
		foreach (array($builder->published(), $builder->draft()) as $s) {
			if (is_array($s)) {
				foreach ($builder->image_paths($s) as $path) {
					$keep[] = basename($path);
				}
			}
		}
		$files = array_merge(glob($dir . '/logo-*.*') ?: array(), glob($dir . '/background-*.*') ?: array());
		foreach ($files as $file) {
			if (!in_array(basename($file), $keep, true)) {
				@unlink($file);
			}
		}
	}

	private function squash($s)
	{
		return preg_replace('/\s+/', '', is_scalar($s) ? (string) $s : '');
	}
}

/**
 * An error the Site layout API returns as {status: failed, message: <code>, errors: {...}} with this HTTP status.
 */
class Site_layout_error extends Exception {

	public $http;
	public $errors;

	public function __construct($code, $http = 400, array $errors = array())
	{
		parent::__construct($code);
		$this->http = $http;
		$this->errors = $errors;
	}
}
