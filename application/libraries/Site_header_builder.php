<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public site header: settings, layouts, sanitizing and rendering.
 *
 * Source (Admin > Settings > Site layout > Header):
 *   theme    — theme PHP files (header_theme.php, or header.local.php when present)
 *   builder  — layout builder (rows / presets)
 *   custom   — admin HTML with tokens such as {{brand}}, {{menu+login}}, {{login}}, {{language}}
 *
 * Builder and custom produce one HTML template with tokens; tokens are filled in for the current visitor
 * (signed in or out) and language. Rows use generated CSS (.sh-row-top, .sh-row-main, .sh-row-bottom).
 *
 * Settings are stored as JSON in the site_layout table (part 'header'), for the admin editor and the preview only:
 *   published  the published settings
 *   draft      what the admin is editing; only the preview page (site_layout_preview/header) shows it
 *
 * Public pages read only the published file, {catalog_root}/cache/site_header/published.json, written by
 * Publish for builder/custom headers: the compiled template (CSS + markup with {{tokens}}) and the few
 * settings the tokens need. They never read or clean the settings. No file means the theme file header.
 * Publish writes the file before it stores the settings and fails if it cannot, so visitors never see
 * an older header than the one published. When FILE_FORMAT changes, rebuild_published_file() rewrites it.
 *
 * Safety:
 *  - admin HTML is cleaned with an allow-list (wp_kses) when saved and again when shown;
 *  - tokens are filled in after cleaning, and admin text in builder rows is escaped;
 *  - CSS is filtered (no @import, expressions, escapes, or url() to other sites);
 *  - any error, a missing setting or $config['site_header_force_default'] = TRUE uses the theme file header.
 *
 * Used by themes/<theme>/header.php (theme file, local override, or echo $builder->render($menus)).
 */
class Site_header_builder {

	const VERSION = 2;
	const MAX_HTML = 20000;
	const MAX_CSS = 20000;
	const UPLOAD_DIR = 'files/site';
	const MOBILE_MAX = '767.98px';
	/** Published file under catalog_root (datafiles by default): cache/site_header/published.json */
	const CACHE_SUBDIR = 'cache/site_header';
	const CACHE_FILE = 'published.json';
	/** Bump when compile_managed() or the file layout changes; the admin then asks to publish again */
	const FILE_FORMAT = 1;

	public static $row_ids = array('top', 'main', 'bottom');
	public static $slots = array('left', 'center', 'right');

	/** Items that may appear only once in the header */
	private static $single_items = array('brand', 'menu', 'login', 'language', 'search');

	/** Tokens that output plain text or a URL; the only ones allowed inside attributes */
	private static $scalar_tokens = array('home_url', 'base_url', 'logo_url', 'bg_image_url', 'site_title', 'site_subtitle');

	private $ci;
	private $presets = null;
	private $file = null;
	private $preview = false;
	private $preview_guest = false;
	private $menus = null;
	private $menu_count = 0;
	private $login_count = 0;
	private $lang_count = 0;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	// =============================================================================================
	// Settings
	// =============================================================================================

	public function defaults()
	{
		$presets = $this->presets();
		$classic = isset($presets['classic']) ? $presets['classic']['settings'] : array();

		$settings = array(
			'version'            => self::VERSION,
			'source'             => 'theme',     // theme | builder | custom — what the public site uses
			'mode'               => 'builder',   // builder | custom (kept in sync with source when managed)
			'preset'             => 'classic',   // the layout last picked (informational)
			'brand'              => 'text',      // text | logo | logo_text
			'subtitle'           => '',          // empty = the translated default ("Data Catalog")
			'show_subtitle'      => true,
			'show_separator'     => true,        // line between the logo and the site title
			'logo'               => '',          // files/site/logo-<hash>.<ext>; empty = theme images/logo.png
			'logo_alt'           => '',
			'logo_height'        => 50,
			'logo_height_mobile' => 40,
			'logo_max_width'     => 280,
			'show_login'         => true,
			'show_language'      => true,
			'background_scope'   => 'header',    // header: one background for all rows | rows: each row has its own
			'header_style'       => $this->style_defaults(),
			'rows'               => array(),
			'html'               => '',
			'css'                => '',
			'search'             => $this->search_defaults(),
		);
		foreach (self::$row_ids as $id) {
			$settings['rows'][$id] = $this->row_defaults($id);
		}
		if ($classic) {
			$settings['background_scope'] = $classic['background_scope'];
			$settings['rows'] = $classic['rows'];
		}
		return $settings;
	}

	public function style_defaults()
	{
		return array(
			'bg_color'    => '',
			'text_color'  => '',
			'bg_image'    => '',
			'bg_size'     => 'cover',    // cover | contain | auto
			'bg_position' => 'center',   // center | top | bottom | left | right
		);
	}

	/** Options for the optional header search box ({{search}}) */
	public function search_defaults()
	{
		return array(
			'placeholder' => '',          // empty = translated default ("Keywords")
			'width'       => 'medium',    // compact | medium | wide
			'submit'      => 'icon',      // icon | button
		);
	}

	public function row_defaults($id)
	{
		return array(
			'enabled' => $id === 'main',
			'left'    => array(),
			'center'  => array(),
			'right'   => array(),
			'style'   => array_merge($this->style_defaults(), array(
				'min_height'           => 0,      // px; 0 = as tall as the content
				'min_height_mobile'    => 0,
				'padding_y'            => 8,      // px above and below the content
				'padding_y_mobile'     => 8,
				'full_width'           => false,
				'border_bottom'        => false,
				'border_bottom_width'  => 1,      // px; used when border_bottom is on
				'border_bottom_color'  => '',     // empty = theme light gray
				'text_size'            => 'normal', // normal | small
				'hide_mobile'          => false,
			)),
		);
	}

	/**
	 * Validate and clean settings.
	 *
	 * @param mixed $input  array (or JSON string) from the admin or the database
	 * @param array $errors filled with field path => error code for values that were refused
	 * @return array complete settings; refused values fall back to their defaults
	 */
	public function normalize($input, &$errors = array())
	{
		$errors = array();
		if (is_string($input)) {
			$input = json_decode($input, true);
		}
		if (!is_array($input)) {
			$input = array();
		}
		if ($input && !isset($input['rows']) && (!isset($input['version']) || (int) $input['version'] < 2)) {
			$input = $this->upgrade_v1($input);
		}

		$d = $this->defaults();
		$out = $d;

		$out['source'] = $this->normalize_source($input, $d['source']);
		$out['mode'] = ($out['source'] === 'custom') ? 'custom' : 'builder';
		if ($out['source'] === 'theme' && isset($input['mode']) && in_array($this->str($input['mode']), array('builder', 'custom'), true)) {
			// Remember the last managed editor while source is still the theme file
			$out['mode'] = $this->str($input['mode']);
		}
		$out['brand'] = $this->pick($input, 'brand', array('text', 'logo', 'logo_text'), $d['brand']);
		$out['background_scope'] = $this->pick($input, 'background_scope', array('header', 'rows'), $d['background_scope']);
		$out['preset'] = (isset($input['preset']) && preg_match('/^[a-z0-9_]{1,40}$/', $this->str($input['preset']))) ? $this->str($input['preset']) : '';

		foreach (array('show_subtitle', 'show_separator', 'show_login', 'show_language') as $key) {
			if (array_key_exists($key, $input)) {
				$out[$key] = filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
			}
		}

		$out['subtitle'] = $this->plain_text(isset($input['subtitle']) ? $input['subtitle'] : '', 200);
		$out['logo_alt'] = $this->plain_text(isset($input['logo_alt']) ? $input['logo_alt'] : '', 200);
		$out['logo_height'] = $this->int_in($input, 'logo_height', 16, 200, $d['logo_height']);
		$out['logo_height_mobile'] = $this->int_in($input, 'logo_height_mobile', 16, 200, $d['logo_height_mobile']);
		$out['logo_max_width'] = $this->int_in($input, 'logo_max_width', 40, 800, $d['logo_max_width']);

		$logo = isset($input['logo']) ? trim($this->str($input['logo'])) : '';
		if ($logo === '' || $this->is_upload_path($logo)) {
			$out['logo'] = $logo;
		} else {
			$errors['logo'] = 'INVALID_IMAGE';
		}

		$out['header_style'] = $this->normalize_style(
			isset($input['header_style']) ? $input['header_style'] : array(),
			$this->style_defaults(), 'header_style', $errors
		);

		// rows; single items (brand, menu, login, language, search) are kept where they first appear
		$used = array();
		$in_rows = (isset($input['rows']) && is_array($input['rows'])) ? $input['rows'] : array();
		foreach (self::$row_ids as $id) {
			$in = isset($in_rows[$id]) && is_array($in_rows[$id]) ? $in_rows[$id] : null;
			$out['rows'][$id] = $in === null ? $this->row_defaults($id) : $this->normalize_row($id, $in, $used, $errors);
		}
		if ($in_rows === array() && !isset($input['rows'])) {
			$out['rows'] = $d['rows'];
			$out['background_scope'] = isset($input['background_scope']) ? $out['background_scope'] : $d['background_scope'];
			$out['preset'] = $out['preset'] !== '' ? $out['preset'] : $d['preset'];
		}
		// login or language placed on their own are not repeated inside the menu
		foreach (self::$row_ids as $id) {
			foreach (self::$slots as $slot) {
				foreach ($out['rows'][$id][$slot] as $i => $item) {
					if ($item['type'] === 'menu') {
						$out['rows'][$id][$slot][$i]['login'] = $item['login'] && empty($used['login']);
						$out['rows'][$id][$slot][$i]['language'] = $item['language'] && empty($used['language']);
					}
				}
			}
		}

		$html = isset($input['html']) ? $this->str($input['html']) : '';
		if (strlen($html) > self::MAX_HTML) {
			$errors['html'] = 'HTML_TOO_LONG';
			$html = '';
		}
		$out['html'] = $this->sanitize_html($html);

		$css = isset($input['css']) ? $this->str($input['css']) : '';
		if (strlen($css) > self::MAX_CSS) {
			$errors['css'] = 'CSS_TOO_LONG';
			$css = '';
		}
		$out['css'] = $this->sanitize_css($css);

		// Search box options: top-level (custom HTML), overridden by a search item in the rows
		$out['search'] = $this->normalize_search_options(
			isset($input['search']) && is_array($input['search']) ? $input['search'] : $d['search']
		);
		foreach (self::$row_ids as $rid) {
			foreach (self::$slots as $slot) {
				foreach ($out['rows'][$rid][$slot] as $item) {
					if ($item['type'] === 'search') {
						$out['search'] = $this->normalize_search_options($item);
						break 3;
					}
				}
			}
		}

		return $out;
	}

	private function normalize_search_options(array $in)
	{
		$d = $this->search_defaults();
		return array(
			'placeholder' => $this->plain_text(isset($in['placeholder']) ? $in['placeholder'] : '', 120),
			'width'       => $this->pick($in, 'width', array('compact', 'medium', 'wide'), $d['width']),
			'submit'      => $this->pick($in, 'submit', array('icon', 'button'), $d['submit']),
		);
	}

	private function normalize_row($id, array $in, array &$used, array &$errors)
	{
		$row = $this->row_defaults($id);
		if (array_key_exists('enabled', $in)) {
			$row['enabled'] = filter_var($in['enabled'], FILTER_VALIDATE_BOOLEAN);
		}
		foreach (self::$slots as $slot) {
			$row[$slot] = $this->normalize_items(isset($in[$slot]) ? $in[$slot] : array(), $used, $errors, "rows.$id.$slot");
		}

		$style_in = isset($in['style']) && is_array($in['style']) ? $in['style'] : array();
		$style = $this->normalize_style($style_in, $row['style'], "rows.$id.style", $errors);
		$style['min_height'] = $this->int_in($style_in, 'min_height', 0, 400, $row['style']['min_height']);
		$style['min_height_mobile'] = $this->int_in($style_in, 'min_height_mobile', 0, 400, $row['style']['min_height_mobile']);
		$style['padding_y'] = $this->int_in($style_in, 'padding_y', 0, 80, $row['style']['padding_y']);
		$style['padding_y_mobile'] = $this->int_in($style_in, 'padding_y_mobile', 0, 80, $row['style']['padding_y_mobile']);
		foreach (array('full_width', 'border_bottom', 'hide_mobile') as $key) {
			if (array_key_exists($key, $style_in)) {
				$style[$key] = filter_var($style_in[$key], FILTER_VALIDATE_BOOLEAN);
			}
		}
		$style['border_bottom_width'] = $this->int_in($style_in, 'border_bottom_width', 1, 8, $row['style']['border_bottom_width']);
		$bc = isset($style_in['border_bottom_color']) ? trim($this->str($style_in['border_bottom_color'])) : '';
		if ($bc === '' || preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $bc)) {
			$style['border_bottom_color'] = strtolower($bc);
		} else {
			$errors["rows.$id.style.border_bottom_color"] = 'INVALID_COLOR';
			$style['border_bottom_color'] = $row['style']['border_bottom_color'];
		}
		$style['text_size'] = $this->pick($style_in, 'text_size', array('normal', 'small'), $row['style']['text_size']);
		$row['style'] = $style;
		return $row;
	}

	private function normalize_items($items, array &$used, array &$errors, $path)
	{
		$out = array();
		if (!is_array($items)) {
			return $out;
		}
		foreach (array_slice(array_values($items), 0, 8) as $i => $item) {
			if (!is_array($item) || !isset($item['type'])) {
				continue;
			}
			$type = $this->str($item['type']);
			if (in_array($type, self::$single_items, true)) {
				if (!empty($used[$type])) {
					continue;
				}
				$used[$type] = true;
				if ($type === 'menu') {
					$out[] = array(
						'type'     => 'menu',
						'login'    => !empty($item['login']) && filter_var($item['login'], FILTER_VALIDATE_BOOLEAN),
						'language' => !empty($item['language']) && filter_var($item['language'], FILTER_VALIDATE_BOOLEAN),
					);
				} elseif ($type === 'search') {
					$out[] = array_merge(array('type' => 'search'), $this->normalize_search_options($item));
				} else {
					$out[] = array('type' => $type);
				}
			} elseif ($type === 'text') {
				$text = $this->plain_text(isset($item['text']) ? $item['text'] : '', 120);
				$url = isset($item['url']) ? trim($this->str($item['url'])) : '';
				if ($url !== '' && !$this->is_safe_link($url)) {
					$errors["$path.$i.url"] = 'INVALID_URL';
					$url = '';
				}
				$url = mb_substr($url, 0, 500);
				if ($text !== '') {
					$out[] = array('type' => 'text', 'text' => $text, 'url' => $url);
				}
			}
		}
		return $out;
	}

	private function normalize_style($in, array $base, $path, array &$errors)
	{
		$in = is_array($in) ? $in : array();
		$out = $base;
		foreach (array('bg_color', 'text_color') as $key) {
			$v = isset($in[$key]) ? trim($this->str($in[$key])) : '';
			if ($v === '' || preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $v)) {
				$out[$key] = strtolower($v);
			} else {
				$errors["$path.$key"] = 'INVALID_COLOR';
			}
		}
		$v = isset($in['bg_image']) ? trim($this->str($in['bg_image'])) : '';
		if ($v === '' || $this->is_upload_path($v)) {
			$out['bg_image'] = $v;
		} else {
			$errors["$path.bg_image"] = 'INVALID_IMAGE';
		}
		$out['bg_size'] = $this->pick($in, 'bg_size', array('cover', 'contain', 'auto'), $base['bg_size']);
		$out['bg_position'] = $this->pick($in, 'bg_position', array('center', 'top', 'bottom', 'left', 'right'), $base['bg_position']);
		return $out;
	}

	/** Settings saved before rows existed (version 1: a preset plus header colours) */
	private function upgrade_v1(array $in)
	{
		$map = array('classic' => 'classic', 'utility_bar' => 'utility_bar', 'centered' => 'centered',
			'stacked' => 'menu_bar', 'utility_stacked' => 'three_rows', 'hero' => 'banner');
		$preset_key = isset($in['preset']) ? $this->str($in['preset']) : '';
		$preset_id = isset($map[$preset_key]) ? $map[$preset_key] : 'classic';
		$presets = $this->presets();
		$preset = isset($presets[$preset_id]) ? $presets[$preset_id]['settings'] : array();

		$style = array();
		foreach (array('bg_color', 'text_color', 'bg_image', 'bg_size', 'bg_position') as $key) {
			if (isset($in[$key])) {
				$style[$key] = $in[$key];
			}
		}

		$out = $in;
		$out['version'] = self::VERSION;
		$out['mode'] = (isset($in['mode']) && $in['mode'] === 'custom') ? 'custom' : 'builder';
		$out['preset'] = $preset_id;
		$out['rows'] = isset($preset['rows']) ? $preset['rows'] : array();
		$out['background_scope'] = isset($preset['background_scope']) ? $preset['background_scope'] : 'header';
		if ($preset_id === 'banner' && isset($out['rows']['main'])) {
			$out['rows']['main']['style'] = array_merge($out['rows']['main']['style'], $style);
		} else {
			$out['background_scope'] = 'header';
			$out['header_style'] = $style;
		}
		return $out;
	}

	/**
	 * Resolve source from saved JSON.
	 * Empty / new → theme. Older saved headers without source keep builder/custom from mode.
	 */
	private function normalize_source(array $input, $default)
	{
		if (isset($input['source']) && in_array($this->str($input['source']), array('theme', 'builder', 'custom'), true)) {
			return $this->str($input['source']);
		}
		if (isset($input['mode']) && $this->str($input['mode']) === 'custom') {
			return 'custom';
		}
		// Non-empty legacy payload (had mode/rows/version) was the managed header
		if ($input && (isset($input['mode']) || isset($input['rows']) || isset($input['version']) || isset($input['html']))) {
			return 'builder';
		}
		return $default;
	}

	/** Published settings (what visitors see) */
	public function published()
	{
		$raw = $this->raw_setting('published');
		return $raw === '' ? $this->defaults() : $this->normalize($raw);
	}

	/**
	 * True when the header should come from theme PHP (not the builder).
	 * Public pages: when there is no published file. Preview: from the draft's source.
	 */
	public function use_theme_header()
	{
		if ($this->ci->config->item('site_header_force_default')) {
			return true;
		}
		if ($this->preview) {
			$s = $this->current_settings();
			return !isset($s['source']) || $s['source'] === 'theme';
		}
		return $this->published_file_data() === null;
	}

	/** Active public theme folder name */
	public function theme_name()
	{
		if (isset($this->ci->template) && is_object($this->ci->template) && method_exists($this->ci->template, 'theme')) {
			$name = (string) $this->ci->template->theme();
			if ($name !== '') {
				return $name;
			}
		}
		return 'nada52';
	}

	/** Absolute path to header.local.php when present */
	public function local_header_path()
	{
		$path = FCPATH . 'themes/' . $this->theme_name() . '/header.local.php';
		return is_file($path) ? $path : '';
	}

	/** Absolute path used for the theme-file source (local override or header_theme.php) */
	public function theme_header_path()
	{
		$local = $this->local_header_path();
		if ($local !== '') {
			return $local;
		}
		$path = FCPATH . 'themes/' . $this->theme_name() . '/header_theme.php';
		return is_file($path) ? $path : '';
	}

	/** Draft settings, or null when there is no draft */
	public function draft()
	{
		$raw = $this->raw_setting('draft');
		return $raw === '' ? null : $this->normalize($raw);
	}

	/** Stored settings JSON for 'published' or 'draft' (table site_layout), or '' */
	private function raw_setting($state)
	{
		$this->ci->load->model('Site_layout_model');
		return $this->ci->Site_layout_model->get('header', $state);
	}

	public function to_json(array $settings)
	{
		return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	/** Uploaded images the settings use (files/site/...) */
	public function image_paths(array $s)
	{
		$paths = array($s['logo'], $s['header_style']['bg_image']);
		foreach ($s['rows'] as $row) {
			$paths[] = $row['style']['bg_image'];
		}
		return array_values(array_filter($paths, 'strlen'));
	}

	// =============================================================================================
	// Layouts
	// =============================================================================================

	/** @return array id => [id, title, description, settings: background_scope, rows (complete), header_style?] */
	public function presets()
	{
		if ($this->presets !== null) {
			return $this->presets;
		}
		$this->presets = array();

		$this->ci->config->load('site_header_presets', TRUE, TRUE);
		$config = $this->ci->config->item('site_header_presets', 'site_header_presets');
		if (!is_array($config)) {
			return $this->presets;
		}

		foreach ($config as $id => $preset) {
			$rows = array();
			$used = array();
			$errors = array();
			$in_rows = isset($preset['settings']['rows']) ? $preset['settings']['rows'] : array();
			foreach (self::$row_ids as $row_id) {
				$rows[$row_id] = isset($in_rows[$row_id])
					? $this->normalize_row($row_id, $in_rows[$row_id], $used, $errors)
					: $this->row_defaults($row_id);
			}
			$settings = array(
				'background_scope' => isset($preset['settings']['background_scope']) && $preset['settings']['background_scope'] === 'rows' ? 'rows' : 'header',
				'rows'             => $rows,
			);
			if (isset($preset['settings']['header_style'])) {
				$settings['header_style'] = $this->normalize_style($preset['settings']['header_style'], $this->style_defaults(), 'header_style', $errors);
			}
			$this->presets[$id] = array(
				'id'          => $id,
				'title'       => isset($preset['title']) ? $preset['title'] : $id,
				'description' => isset($preset['description']) ? $preset['description'] : '',
				'settings'    => $settings,
			);
		}
		return $this->presets;
	}

	/** Tokens with a short description, for the admin editor */
	public function token_docs()
	{
		return array(
			array('token' => '{{brand}}', 'description' => 'Logo and/or site title, as set under Brand and logo'),
			array('token' => '{{logo+text}}', 'description' => 'Logo, separator, site title and subtitle'),
			array('token' => '{{logo}}', 'description' => 'Logo only, linked to the home page'),
			array('token' => '{{logo_separator}}', 'description' => 'Vertical line between a logo and text'),
			array('token' => '{{site_title}}', 'description' => 'Website title (text)'),
			array('token' => '{{site_subtitle}}', 'description' => 'Subtitle (text)'),
			array('token' => '{{menu}}', 'description' => 'Site menu with the mobile menu button'),
			array('token' => '{{menu+login}}', 'description' => 'Site menu with login/user menu inside'),
			array('token' => '{{menu+login+language}}', 'description' => 'Site menu with login and language inside'),
			array('token' => '{{login}}', 'description' => 'Login link, or the signed-in user\'s menu'),
			array('token' => '{{language}}', 'description' => 'Language links (only when 2+ languages are enabled)'),
			array('token' => '{{search}}', 'description' => 'Catalog keyword search box (hidden on the home page and catalog search)'),
			array('token' => '{{home_url}}', 'description' => 'Home page address'),
			array('token' => '{{base_url}}', 'description' => 'Site base address, for images in files/'),
			array('token' => '{{logo_url}}', 'description' => 'Address of the logo image'),
			array('token' => '{{bg_image_url}}', 'description' => 'Address of the header background image'),
		);
	}

	// =============================================================================================
	// Rendering
	// =============================================================================================

	/** Show the draft instead of the published header (preview page and preview API only) */
	public function set_preview($on, $as_guest = false)
	{
		$this->preview = (bool) $on;
		$this->preview_guest = $this->preview && (bool) $as_guest;
	}

	public function is_preview()
	{
		return $this->preview;
	}

	/**
	 * The header HTML for the current request.
	 *
	 * Public pages use only the published file (see published_file()); without it they get the theme
	 * file header. The preview builds the draft in memory.
	 *
	 * @param array|null $menus published menu tree, when the layout already loaded it
	 */
	public function render($menus = null)
	{
		$this->menus = is_array($menus) ? $menus : null;

		try {
			if ($this->preview) {
				return $this->render_settings($this->current_settings());
			}
			$file = $this->ci->config->item('site_header_force_default') ? null : $this->published_file_data();
			if ($file !== null) {
				return $this->fill($file['template'], $this->runtime_settings($file['runtime']));
			}
		}
		catch (Throwable $e) {
			log_message('error', 'Site header: ' . $e->getMessage());
		}

		try {
			return $this->render_settings($this->defaults());
		}
		catch (Throwable $e) {
			log_message('error', 'Site header (default): ' . $e->getMessage());
			return '';
		}
	}

	/** Settings for the admin and the preview (draft first); public pages do not use them */
	private function current_settings()
	{
		if ($this->ci->config->item('site_header_force_default')) {
			return $this->defaults();
		}
		if ($this->preview) {
			$draft = $this->draft();
			return $draft !== null ? $draft : $this->published();
		}
		return $this->published();
	}

	/** Render the given (normalized) settings in memory: theme file, or builder/custom HTML */
	public function render_settings(array $s)
	{
		$source = isset($s['source']) ? $s['source'] : 'theme';
		if ($source === 'theme' || $this->ci->config->item('site_header_force_default')) {
			$html = $this->render_theme_header($this->menus);
			if ($html !== '') {
				return $html;
			}
			// No theme PHP file: fall through to the classic builder layout
			$s = $this->defaults();
			$s['source'] = 'builder';
			$s['mode'] = 'builder';
		}

		return $this->render_managed($s);
	}

	/** Builder or custom HTML only (no theme-file branch) */
	private function render_managed(array $s)
	{
		return $this->fill($this->compile_managed($s), $s);
	}

	/** Fill the tokens of a compiled template for the current visitor and language */
	private function fill($template, array $s)
	{
		$this->menu_count = $this->login_count = $this->lang_count = 0;
		return $this->replace_tokens($template, $s);
	}

	/**
	 * Compile builder/custom settings to HTML with tokens still in place (CSS baked in).
	 * This is what Publish stores in the published file.
	 */
	public function compile_managed(array $s)
	{
		$source = isset($s['source']) ? $s['source'] : (isset($s['mode']) ? $s['mode'] : 'builder');
		if ($source === 'custom' || (isset($s['mode']) && $s['mode'] === 'custom')) {
			// cleaned again: the database may hold HTML saved before a rule was added
			$template = $this->sanitize_html($s['html']);
			$class = 'sh-custom';
		} else {
			$template = $this->builder_template($s);
			$class = 'sh-builder' . ($s['preset'] !== '' ? ' sh-layout-' . $s['preset'] : '');
		}
		$class .= ' sh-bg-' . $s['background_scope'];

		$css = $this->base_css() . $this->settings_css($s) . $this->sanitize_css($s['css']);

		return '<style data-sh-style>' . $css . '</style>' . "\n"
			. '<header class="site-header ' . $class . '">' . "\n" . $template . "\n" . '</header>';
	}

	// ---------------------------------------------------------------------------------------------
	// Published file (the only thing public pages read)
	// ---------------------------------------------------------------------------------------------

	/** {catalog_root}/cache/site_header/published.json */
	public function published_file()
	{
		if ($this->file === null) {
			require_once APPPATH . 'libraries/Site_layout_file.php';
			$this->file = new Site_layout_file(self::CACHE_SUBDIR, self::CACHE_FILE, array('published.html'), 'Site header');
		}
		return $this->file;
	}

	/** The published file for public pages, or null to use the theme file header */
	private function published_file_data()
	{
		$data = $this->published_file()->read();
		if (!is_array($data) || !isset($data['template']) || !is_string($data['template']) || $data['template'] === '') {
			return null;
		}
		if (!isset($data['runtime']) || !is_array($data['runtime'])) {
			$data['runtime'] = array();
		}
		return $data;
	}

	/**
	 * What Publish writes for these settings, or null when the source is the theme file (no file).
	 *
	 * @param array  $s    normalized settings
	 * @param string $json the settings exactly as stored in configurations.site_header
	 */
	public function published_file_payload(array $s, $json)
	{
		$source = isset($s['source']) ? $s['source'] : 'theme';
		if ($source !== 'builder' && $source !== 'custom') {
			return null;
		}
		return array(
			'format'   => self::FILE_FORMAT,
			'hash'     => sha1((string) $json),
			'source'   => $source,
			'runtime'  => $this->runtime_values($s),
			'template' => $this->compile_managed($s),
		);
	}

	/**
	 * Whether the published file matches the published settings. Admin only: public pages never
	 * compare, they use whatever file is there.
	 */
	public function published_file_in_sync()
	{
		$raw = $this->raw_setting('published');
		$s = $raw === '' ? null : $this->normalize($raw);
		$data = $this->published_file()->read();
		if ($s === null || !in_array($s['source'], array('builder', 'custom'), true)) {
			return $data === null;
		}
		return is_array($data)
			&& isset($data['hash'], $data['format'])
			&& $data['hash'] === sha1($raw)
			&& (int) $data['format'] === self::FILE_FORMAT;
	}

	/** Write (or remove) the published file from the published settings, e.g. after an upgrade */
	public function rebuild_published_file()
	{
		$raw = $this->raw_setting('published');
		$payload = $raw === '' ? null : $this->published_file_payload($this->normalize($raw), $raw);
		$file = $this->published_file();
		if ($payload === null) {
			return $file->delete();
		}
		$tmp = $file->stage($payload);
		return $tmp !== false && $file->commit($tmp);
	}

	/**
	 * Whether the published file can be written.
	 *
	 * @return array{ok:bool,code:string,path:string,catalog_root:string}
	 */
	public function cache_status()
	{
		return $this->published_file()->status();
	}

	/** The settings the tokens read on each page: brand, logo, subtitle, login, languages, search */
	private function runtime_defaults()
	{
		return array(
			'brand'          => 'text',
			'subtitle'       => '',
			'show_subtitle'  => true,
			'show_separator' => true,
			'logo'           => '',
			'logo_alt'       => '',
			'show_login'     => true,
			'show_language'  => true,
			'search'         => $this->search_defaults(),
			'header_style'   => array('bg_image' => ''),
		);
	}

	private function runtime_values(array $s)
	{
		$out = array();
		foreach ($this->runtime_defaults() as $key => $default) {
			$out[$key] = isset($s[$key]) ? $s[$key] : $default;
		}
		$out['header_style'] = array(
			'bg_image' => isset($s['header_style']['bg_image']) ? (string) $s['header_style']['bg_image'] : '',
		);
		return $out;
	}

	/** Runtime values from the published file, with defaults for anything missing */
	private function runtime_settings(array $runtime)
	{
		$s = array_merge($this->runtime_defaults(), $runtime);
		$s['search'] = array_merge($this->search_defaults(), is_array($s['search']) ? $s['search'] : array());
		$s['header_style'] = array_merge(array('bg_image' => ''), is_array($s['header_style']) ? $s['header_style'] : array());
		return $s;
	}

	/**
	 * Whether logo/background uploads can be stored under files/site/.
	 *
	 * @return array{ok:bool,code:string,path:string}
	 */
	public function upload_status()
	{
		$dir = rtrim(str_replace('\\', '/', FCPATH), '/') . '/' . self::UPLOAD_DIR;
		if (!$this->path_is_writable($dir)) {
			return array(
				'ok'   => false,
				'code' => 'UPLOAD_NOT_WRITABLE',
				'path' => $dir,
			);
		}
		return array(
			'ok'   => true,
			'code' => '',
			'path' => $dir,
		);
	}

	/** True when $dir exists and is writable, or can be created under a writable parent */
	private function path_is_writable($dir)
	{
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		if (is_dir($dir)) {
			return is_writable($dir);
		}
		$check = $dir;
		while ($check !== '' && $check !== '/' && !preg_match('#^[A-Za-z]:$#', $check) && !is_dir($check)) {
			$parent = dirname($check);
			if ($parent === $check) {
				break;
			}
			$check = $parent;
		}
		return is_dir($check) && is_writable($check);
	}

	/**
	 * Output of the theme header PHP (header.local.php or header_theme.php).
	 * Used for source=theme and for the admin preview of that source.
	 */
	public function render_theme_header($menus = null)
	{
		$path = $this->theme_header_path();
		if ($path === '') {
			return '';
		}

		if ($menus === null) {
			$menus = $this->menus;
		}
		if ($menus === null) {
			$this->ci->load->model('Menu_model');
			$menus = $this->ci->Menu_model->get_published_menu_tree();
			$this->menus = $menus;
		}
		$data = array('menus' => is_array($menus) ? $menus : array());

		$include = function ($__sh_file) use ($data, $menus) {
			include $__sh_file;
		};
		$include = $include->bindTo($this->ci, $this->ci);

		ob_start();
		try {
			$include($path);
		}
		catch (Throwable $e) {
			ob_end_clean();
			log_message('error', 'Site header theme file: ' . $e->getMessage());
			return '';
		}
		return (string) ob_get_clean();
	}

	/**
	 * The builder rows as HTML with tokens (also what "Copy to custom HTML" gives the admin).
	 * Admin text is escaped, including braces, so it can never become a token.
	 */
	public function builder_template(array $s)
	{
		$out = array();
		foreach (self::$row_ids as $id) {
			$row = $s['rows'][$id];
			if (!$row['enabled']) {
				continue;
			}
			$filled = array();
			foreach (self::$slots as $slot) {
				$filled[$slot] = count($row[$slot]) > 0;
			}
			if (!in_array(true, $filled, true)) {
				continue;
			}

			$has_menu = false;
			$slots_html = array();
			$slot_list = $filled['center'] ? self::$slots : array('left', 'right');
			foreach ($slot_list as $slot) {
				$items = array();
				foreach ($row[$slot] as $item) {
					$has_menu = $has_menu || $item['type'] === 'menu';
					$items[] = $this->item_template($item);
				}
				$slots_html[] = '            <div class="sh-slot sh-slot-' . $slot . '">' . implode('', $items) . '</div>';
			}

			$row_class = 'sh-row sh-row-' . $id . ' sh-optional';
			if ($row['style']['hide_mobile']) {
				$row_class .= ' sh-hide-mobile';
			}
			if ($row['style']['border_bottom']) {
				$row_class .= ' sh-border';
			}
			if ($row['style']['text_size'] === 'small') {
				$row_class .= ' sh-small';
			}
			$inner_class = 'navbar navbar-expand-md navbar-light wb-navbar sh-row-inner';
			if ($filled['center']) {
				$inner_class .= ' sh-has-center';
				if (!$filled['left'] && !$filled['right']) {
					$inner_class .= ' sh-only-center';
				}
			}
			$tag = $has_menu ? 'nav' : 'div';
			$container = $row['style']['full_width'] ? 'container-fluid' : 'container';

			$out[] = '<div class="' . $row_class . '" data-sh-row="' . $id . '">' . "\n"
				. '    <div class="' . $container . '">' . "\n"
				. '        <' . $tag . ' class="' . $inner_class . '">' . "\n"
				. implode("\n", $slots_html) . "\n"
				. '        </' . $tag . '>' . "\n"
				. '    </div>' . "\n"
				. '</div>';
		}
		return implode("\n", $out);
	}

	private function item_template(array $item)
	{
		switch ($item['type']) {
			case 'brand':
				return '{{brand}}';
			case 'login':
				return '{{login}}';
			case 'language':
				return '{{language}}';
			case 'menu':
				return '{{menu' . ($item['login'] ? '+login' : '') . ($item['language'] ? '+language' : '') . '}}';
			case 'search':
				return '{{search}}';
			case 'text':
				$text = str_replace(array('{', '}'), array('&#123;', '&#125;'), html_escape($item['text']));
				if ($item['url'] === '') {
					return '<span class="sh-text sh-c">' . $text . '</span>';
				}
				$url = str_replace(array('{', '}'), array('%7B', '%7D'), html_escape($this->link_url($item['url'])));
				return '<a class="sh-text sh-c" href="' . $url . '">' . $text . '</a>';
		}
		return '';
	}

	private function replace_tokens($html, array $s)
	{
		$token = '\{\{\s*([a-z_]+(?:\+[a-z_]+)*)\s*\}\}';
		$tag = '<[a-zA-Z\/!](?:[^>"\']|"[^"]*"|\'[^\']*\')*>';
		$self = $this;

		return preg_replace_callback('/(' . $tag . ')|' . $token . '/', function ($m) use ($s, $token, $self) {
			if ($m[1] !== '') {
				// inside a tag: only text/URL tokens, escaped for an attribute
				return preg_replace_callback('/' . $token . '/', function ($t) use ($s, $self) {
					return $self->scalar_token($t[1], $s);
				}, $m[1]);
			}
			return $self->token($m[2], $s);
		}, $html);
	}

	/** Text or URL token, escaped; empty for anything else */
	public function scalar_token($name, array $s)
	{
		switch ($name) {
			case 'home_url':
				return html_escape(site_url());
			case 'base_url':
				return html_escape(base_url());
			case 'logo_url':
				return html_escape($this->logo_url($s));
			case 'bg_image_url':
				return $s['header_style']['bg_image'] !== '' ? html_escape(base_url($s['header_style']['bg_image'])) : '';
			case 'site_title':
				return $this->site_title();
			case 'site_subtitle':
				return $this->subtitle($s);
		}
		return '';
	}

	/** Rendered component for a token in text; unknown tokens render as nothing */
	public function token($name, array $s)
	{
		if (in_array($name, self::$scalar_tokens, true)) {
			return $this->scalar_token($name, $s);
		}

		switch ($name) {
			case 'brand':
				return $this->brand($s, $s['brand']);
			case 'logo+text':
			case 'text+logo':
				return $this->brand($s, 'logo_text');
			case 'logo':
				return $this->brand($s, 'logo_only');
			case 'logo_separator':
				return '<span class="sh-logo-separator sh-c" aria-hidden="true"></span>';
			case 'login':
				return $this->login($s, 'inline');
			case 'language':
				return $this->language($s, 'inline');
			case 'search':
				return $this->search($s);
		}

		$parts = explode('+', $name);
		if ($parts[0] === 'menu' && !array_diff($parts, array('menu', 'login', 'language'))) {
			return $this->menu($s, in_array('login', $parts, true), in_array('language', $parts, true));
		}

		return '';
	}

	// ---------------------------------------------------------------------------------------------
	// Components
	// ---------------------------------------------------------------------------------------------

	private function brand(array $s, $mode)
	{
		$logo_url = $this->logo_url($s);
		if ($mode !== 'text' && $logo_url === '') {
			if ($mode === 'logo_only') {
				return '';
			}
			$mode = 'text';
		}

		return $this->view('brand', array(
			'mode'           => $mode,
			'home_url'       => html_escape(site_url()),
			'title'          => $this->site_title(),
			'subtitle'       => $s['show_subtitle'] ? $this->subtitle($s) : '',
			'logo_url'       => html_escape($logo_url),
			'logo_alt'       => $s['logo_alt'] !== '' ? html_escape($s['logo_alt']) : $this->site_title(),
			'show_separator' => $s['show_separator'],
		));
	}

	private function menu(array $s, $with_login, $with_language)
	{
		$this->menu_count++;
		$menus = $this->menus;
		if ($menus === null) {
			$this->ci->load->model('Menu_model');
			$menus = $this->menus = $this->ci->Menu_model->get_published_menu_tree();
		}

		return $this->view('menu', array(
			'id'            => $this->menu_count === 1 ? 'containerNavbar' : 'containerNavbar-' . $this->menu_count,
			'menus'         => is_array($menus) ? $menus : array(),
			'login_html'    => $with_login ? $this->login($s, 'li') : '',
			'language_html' => $with_language ? $this->language($s, 'li') : '',
		));
	}

	private function login(array $s, $variant)
	{
		if (!$s['show_login']) {
			return '';
		}
		$this->login_count++;

		$user = $this->preview_guest ? '' : (string) $this->ci->session->userdata('username');
		$can_admin = false;
		if ($user !== '' && isset($this->ci->ion_auth)) {
			$can_admin = (bool) $this->ci->ion_auth->can_access_site_admin();
		}

		return $this->view('login', array(
			'variant'   => $variant,
			'id'        => $this->login_count === 1 ? 'dropdownMenuLink' : 'dropdownMenuLink-' . $this->login_count,
			'user'      => $user,
			'can_admin' => $can_admin,
		));
	}

	/**
	 * Catalog keyword search form for the header.
	 * Hidden on the home page and catalog browse/search (they already have a primary search).
	 * Still shown in the admin preview.
	 */
	private function search(array $s)
	{
		if (!$this->preview && $this->should_hide_header_search()) {
			return '';
		}

		$this->ci->load->helper(array('url', 'catalog'));
		$opts = isset($s['search']) && is_array($s['search'])
			? array_merge($this->search_defaults(), $s['search'])
			: $this->search_defaults();

		$placeholder = $opts['placeholder'] !== ''
			? $opts['placeholder']
			: (function_exists('t') ? t('keywords') : 'Keywords');

		$ai = function_exists('nada_ai_search_enabled') && nada_ai_search_enabled();

		return $this->view('search', array(
			'action'      => html_escape(catalog_search_url()),
			'placeholder' => html_escape($placeholder, false),
			'width'       => $opts['width'],
			'submit'      => $opts['submit'],
			'use_rank'    => $ai,
			'query'       => html_escape((string) $this->ci->input->get('sk'), false),
		));
	}

	/** True on home or catalog browse/search (not study detail pages under /catalog/{id}/…) */
	private function should_hide_header_search()
	{
		$seg1 = $this->ci->uri->segment(1);
		$seg2 = $this->ci->uri->segment(2);

		if ($seg1 === 'catalog') {
			// Study pages: /catalog/{numeric_id}/…
			if ($seg2 !== false && $seg2 !== null && $seg2 !== '' && ctype_digit((string) $seg2)) {
				return false;
			}
			return true;
		}

		$this->ci->load->helper('url');
		$home = function_exists('site_home') ? site_home() : 'home';
		if ($seg1 === false || $seg1 === null || $seg1 === '' || $seg1 === 'home') {
			return true;
		}
		if ($seg1 === $home && isset($this->ci->router) && $this->ci->router->class === 'page') {
			return true;
		}
		return false;
	}

	private function language(array $s, $variant)
	{
		if (!$s['show_language']) {
			return '';
		}
		$languages = $this->ci->config->item('supported_languages');
		if (!is_array($languages) || count($languages) < 2) {
			return '';
		}
		$this->lang_count++;

		$current = (string) $this->ci->config->item('language');
		$items = array();
		foreach ($languages as $language) {
			$language = (string) $language;
			$items[] = array(
				'label'  => strtoupper(t(strtolower($language))),
				'url'    => site_url('switch_language/' . rawurlencode($language)),
				'active' => $language === $current,
			);
		}

		return $this->view('language', array(
			'variant' => $variant,
			'id'      => 'sh-language-' . $this->lang_count,
			'items'   => $items,
			'current' => strtoupper(t(strtolower($current))),
		));
	}

	private function view($name, array $vars)
	{
		return $this->ci->load->view('site_header/components/' . $name, $vars, true);
	}

	private function site_title()
	{
		return html_escape((string) $this->ci->config->item('website_title'), false);
	}

	private function subtitle(array $s)
	{
		$text = $s['subtitle'] !== '' ? t($s['subtitle']) : tt('site_header_default_subtitle', 'Data Catalog');
		return html_escape($text, false);
	}

	/** Uploaded logo, else the theme's logo.png, else '' */
	public function logo_url(array $s)
	{
		if (!empty($s['logo'])) {
			return base_url($s['logo']);
		}
		$theme = isset($this->ci->template) ? $this->ci->template->theme() : 'nada52';
		$path = 'themes/' . $theme . '/images/logo.png';
		if (is_file(FCPATH . $path)) {
			return base_url($path);
		}
		return '';
	}

	// ---------------------------------------------------------------------------------------------
	// CSS
	// ---------------------------------------------------------------------------------------------

	private function base_css()
	{
		$m = '@media (max-width:' . self::MOBILE_MAX . ')';
		return '.site-header .navbar-light .no-logo .navbar-brand--sitename{border:0!important;margin-left:0}'
			. '.site-header .navbar-light .no-logo .nada-site-title{font-size:18px}'
			. '.site-header .navbar-brand.sh-no-separator .navbar-brand--sitename{border-left:0}'
			. '.site-header .navbar-brand--logo{display:flex;align-items:center}'
			. '.site-header .navbar-brand--logo img,.site-header .sh-logo img{width:auto;object-fit:contain;object-position:left center}'
			. '.site-header .sh-logo-separator{display:inline-block;align-self:stretch;width:1px;min-height:1.5em;margin:0 .8rem;background:var(--wbg-lightbluegray,#ccd5df)}'
			. '.site-header .sh-optional:not(:has(.sh-c)){display:none}'
			. '.site-header .sh-login-inline .nav-link,.site-header .sh-language-inline a,.site-header .sh-text{white-space:nowrap}'
			// search box
			. '.site-header .sh-search{display:flex;align-items:center;margin:0;min-width:0}'
			. '.site-header .sh-search-box{display:flex;align-items:center;width:100%;border:1px solid var(--wbg-lightbluegray,#ccd5df);border-radius:4px;background:#fff;overflow:hidden}'
			. '.site-header .sh-search-input{flex:1 1 auto;min-width:0;border:0;padding:.35rem .6rem;font-size:.95rem;background:transparent;outline:none}'
			. '.site-header .sh-search-submit{flex:0 0 auto;border:0;background:transparent;color:inherit;padding:.35rem .65rem;cursor:pointer;line-height:1}'
			. '.site-header .sh-search-submit--button{background:var(--wbg-lightbluegray,#eef2f6);font-size:.85rem;white-space:nowrap}'
			. '.site-header .sh-search--compact{width:160px;max-width:100%}'
			. '.site-header .sh-search--medium{width:220px;max-width:100%}'
			. '.site-header .sh-search--wide{width:300px;max-width:100%}'
			// rows and slots
			. '.site-header .sh-row-inner{flex-wrap:wrap;justify-content:flex-start}'
			. '.site-header .sh-slot{display:flex;align-items:center;gap:.5rem}'
			. '.site-header .sh-slot:empty{min-height:1px}'
			. '.site-header .sh-slot-left{flex:0 1 auto}'
			. '.site-header .sh-slot-right{flex:1 1 auto;justify-content:flex-end}'
			. '.site-header .sh-has-center .sh-slot-left,.site-header .sh-has-center .sh-slot-right{flex:1 1 0}'
			. '.site-header .sh-slot-center{flex:0 1 auto;justify-content:center;min-width:0}'
			. '.site-header .sh-slot-center .navbar-nav{flex-wrap:wrap;justify-content:center}'
			. '.site-header .sh-slot .navbar-brand{margin-right:0}'
			. '.site-header .sh-slot-left .navbar-nav.ml-auto{margin-left:0!important}'
			. '.site-header .sh-slot-center .navbar-nav.ml-auto{margin-left:auto!important;margin-right:auto!important}'
			. '.site-header .sh-row-top .navbar-nav,.site-header .sh-row-bottom .navbar-nav{margin-top:0}'
			. '.site-header .sh-text{color:inherit}'
			. '.site-header .sh-small{font-size:.85rem}'
			. '.site-header .sh-small .nav-link{padding:.25rem .5rem}'
			. '.site-header .sh-small .lang-container{padding-left:0}'
			. '.site-header .sh-border{border-bottom:1px solid var(--wbg-lightbluegray,#e3e8ee)}' // overridden per row when width/colour are set
			. $m . '{'
			. '.site-header .sh-hide-mobile{display:none}'
			. '.site-header .sh-row-inner{justify-content:space-between}'
			. '.site-header .sh-only-center{justify-content:center}'
			. '.site-header .sh-slot{display:contents}'
			. '.site-header .sh-row-inner .navbar-brand{white-space:normal;min-width:0;flex:0 1 auto}'
			. '.site-header .navbar-brand--sitename{min-width:0}'
			. '.site-header .nada-site-title{overflow-wrap:anywhere}'
			. '.site-header .sh-row-inner .sh-slot>.navbar-toggler{order:98;margin-left:auto}'
			. '.site-header .sh-only-center .sh-slot>.navbar-toggler{margin-right:auto}'
			. '.site-header .sh-row-inner .sh-slot>.navbar-collapse{order:99;flex-basis:100%}'
			. '.site-header .sh-search{width:100%;max-width:100%;order:97;flex-basis:100%;margin-top:.35rem}'
			. '.site-header .sh-search--compact,.site-header .sh-search--medium,.site-header .sh-search--wide{width:100%}'
			. '}';
	}

	private function settings_css(array $s)
	{
		$m = '@media (max-width:' . self::MOBILE_MAX . ')';
		$logo = '.site-header .navbar-brand--logo img,.site-header .sh-logo img';
		$css = $logo . '{height:' . (int) $s['logo_height'] . 'px;max-width:' . (int) $s['logo_max_width'] . 'px}'
			. $m . '{' . $logo . '{height:' . (int) $s['logo_height_mobile'] . 'px;max-width:min(' . (int) $s['logo_max_width'] . 'px,45vw)}}';

		// one background for the whole header (also custom HTML)
		if ($s['background_scope'] === 'header' || $s['mode'] === 'custom') {
			$css .= $this->style_css($s['header_style'], '.site-header');
		}

		// row sizes always; row backgrounds when each row has its own
		$mobile = '';
		foreach (self::$row_ids as $id) {
			$st = $s['rows'][$id]['style'];
			$sel = '.site-header .sh-row-' . $id;
			$css .= $sel . ' .sh-row-inner{padding-top:' . $st['padding_y'] . 'px;padding-bottom:' . $st['padding_y'] . 'px'
				. ($st['min_height'] ? ';min-height:' . $st['min_height'] . 'px' : '') . '}';
			$mobile .= $sel . ' .sh-row-inner{padding-top:' . $st['padding_y_mobile'] . 'px;padding-bottom:' . $st['padding_y_mobile'] . 'px'
				. ';min-height:' . ($st['min_height_mobile'] ? $st['min_height_mobile'] . 'px' : '0') . '}';
			if ($s['background_scope'] === 'rows') {
				$css .= $this->style_css($st, $sel);
			}
			if (!empty($st['border_bottom'])) {
				$width = max(1, min(8, (int) (isset($st['border_bottom_width']) ? $st['border_bottom_width'] : 1)));
				$color = !empty($st['border_bottom_color'])
					? $st['border_bottom_color']
					: 'var(--wbg-lightbluegray,#e3e8ee)';
				$css .= $sel . '.sh-border{border-bottom:' . $width . 'px solid ' . $color . '}';
			}
		}
		$css .= $m . '{' . $mobile . '}';
		return $css;
	}

	/** Background and text colour for one area */
	private function style_css(array $st, $sel)
	{
		$css = '';
		$bg = '';
		if ($st['bg_color'] !== '') {
			$bg .= 'background-color:' . $st['bg_color'] . ';';
		}
		if ($st['bg_image'] !== '') {
			$url = str_replace(array('"', '\\', "\n", "\r"), '', base_url($st['bg_image']));
			$bg .= 'background-image:url("' . $url . '");background-size:' . $st['bg_size'] . ';background-position:' . $st['bg_position'] . ';background-repeat:no-repeat;';
		}
		if ($bg !== '') {
			$css .= $sel . '{' . $bg . '}';
		}

		if ($st['text_color'] !== '') {
			$c = $st['text_color'];
			$list = array();
			foreach (array('', ' .nada-site-title', ' .nada-site-subtitle', ' .nav-link', ' .navbar-nav .nav-link', ' .navbar-light .navbar-nav .nav-link', ' .lang-container a', ' .sh-text') as $suffix) {
				$list[] = $sel . $suffix;
			}
			$css .= implode(',', $list) . '{color:' . $c . '}';
			if ($this->is_light($c)) {
				$css .= $sel . ' .navbar-toggler{border-color:rgba(255,255,255,.5)}'
					. $sel . ' .navbar-toggler-icon{filter:invert(1)}';
			}
		}
		return $css;
	}

	private function is_light($hex)
	{
		$hex = ltrim($hex, '#');
		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.6;
	}

	// =============================================================================================
	// Sanitizing
	// =============================================================================================

	/** Admin HTML reduced to an allow-list of tags and attributes */
	public function sanitize_html($html)
	{
		$html = (string) $html;
		if (trim($html) === '') {
			return '';
		}

		$this->ci->load->helper('kses');

		// elements whose content must go too (kses would keep their text)
		$html = preg_replace('#<(script|style|iframe|object|embed|template|noscript|textarea|select|svg|math|title)\b[^>]*>.*?</\1\s*>#is', '', $html);

		$global = array(
			'class' => true, 'id' => true, 'title' => true, 'role' => true, 'style' => true, 'lang' => true, 'dir' => true,
			'aria-label' => true, 'aria-hidden' => true, 'aria-expanded' => true, 'aria-controls' => true,
			'aria-haspopup' => true, 'aria-labelledby' => true, 'aria-current' => true,
			'data-toggle' => true, 'data-target' => true, 'data-sh-row' => true,
		);
		$tags = array(
			'div', 'span', 'p', 'nav', 'header', 'section', 'ul', 'ol', 'li', 'small', 'strong', 'b', 'em', 'i', 'u',
			'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'button',
		);
		$allowed = array();
		foreach ($tags as $tag) {
			$allowed[$tag] = $global;
		}
		$allowed['a'] = array_merge($global, array('href' => true, 'target' => true, 'rel' => true));
		$allowed['img'] = array_merge($global, array('src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true));
		$allowed['button']['type'] = true;

		$clean = wp_kses($html, $allowed, array('http', 'https', 'mailto', 'tel'));

		// Bootstrap behaviours: only collapse and dropdown, aimed at an element id
		$clean = preg_replace_callback('/\sdata-toggle\s*=\s*("[^"]*"|\'[^\']*\')/i', function ($m) {
			$v = strtolower(trim(substr($m[1], 1, -1)));
			return in_array($v, array('collapse', 'dropdown'), true) ? ' data-toggle="' . $v . '"' : '';
		}, $clean);
		$clean = preg_replace_callback('/\sdata-target\s*=\s*("[^"]*"|\'[^\']*\')/i', function ($m) {
			$v = trim(substr($m[1], 1, -1));
			return preg_match('/^#[A-Za-z][\w-]*$/', $v) ? ' data-target="' . $v . '"' : '';
		}, $clean);
		$clean = preg_replace_callback('/\sdata-sh-row\s*=\s*("[^"]*"|\'[^\']*\')/i', function ($m) {
			$v = trim(substr($m[1], 1, -1));
			return in_array($v, self::$row_ids, true) ? ' data-sh-row="' . $v . '"' : '';
		}, $clean);

		return trim($clean);
	}

	/** Admin CSS without imports, scripts, escapes or addresses on other sites */
	public function sanitize_css($css)
	{
		$css = str_replace("\0", '', (string) $css);
		if (trim($css) === '') {
			return '';
		}

		$css = preg_replace('#/\*.*?(\*/|$)#s', '', $css);
		$css = str_replace(array('\\', '<'), '', $css);

		$patterns = array(
			'/@(import|charset|namespace)\b[^;{]*;?/i',
			'/expression\s*\(/i',
			'/(java|vb)script\s*:/i',
			'/behaviou?r\s*:/i',
			'/-moz-binding/i',
			'/(-webkit-)?image-set\s*\(/i',
		);
		do {
			$before = $css;
			$css = preg_replace($patterns, '', $css);
		} while ($css !== $before);

		$self = $this;
		$css = preg_replace_callback('/url\s*\(\s*(["\']?)(.*?)\1\s*\)/i', function ($m) use ($self) {
			return $self->css_url($m[2]);
		}, $css);

		return trim($css);
	}

	/** url(...) kept only for this site's files and data: images */
	public function css_url($url)
	{
		$url = trim($url);
		$safe = false;
		if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#i', $url)) {
			$safe = true;
		} elseif (stripos($url, base_url()) === 0) {
			$safe = true;
		} elseif ($url !== '' && strpos($url, '//') !== 0 && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
			$safe = true;
		}
		if (!$safe) {
			return 'none';
		}
		return 'url("' . str_replace(array('"', "'", '(', ')', ' ', "\n", "\r"), '', $url) . '")';
	}

	// =============================================================================================
	// Helpers
	// =============================================================================================

	public function is_upload_path($path)
	{
		return (bool) preg_match('#^' . preg_quote(self::UPLOAD_DIR, '#') . '/[A-Za-z0-9][A-Za-z0-9._-]*\.(png|jpe?g|gif|webp|svg)$#i', $path)
			&& strpos($path, '..') === false;
	}

	/** Links in text items: http(s), mailto, tel, or a path on this site */
	private function is_safe_link($url)
	{
		if (preg_match('#^https?://#i', $url)) {
			return filter_var($url, FILTER_VALIDATE_URL) !== false;
		}
		if (preg_match('#^(mailto|tel):[^\s<>"\']+$#i', $url)) {
			return true;
		}
		if (strpos($url, '//') === 0 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
			return false;
		}
		return (bool) preg_match('#^[A-Za-z0-9/_\-.~?=&%\#+]+$#', $url);
	}

	/** A site path ("catalog", "/page/about") becomes a full address; other links are kept */
	private function link_url($url)
	{
		if (preg_match('#^(https?://|mailto:|tel:)#i', $url)) {
			return $url;
		}
		return site_url(ltrim($url, '/'));
	}

	private function pick(array $input, $key, array $allowed, $default)
	{
		$value = isset($input[$key]) ? $this->str($input[$key]) : '';
		return in_array($value, $allowed, true) ? $value : $default;
	}

	private function int_in(array $input, $key, $min, $max, $default)
	{
		if (!isset($input[$key]) || !is_numeric($input[$key])) {
			return $default;
		}
		return max($min, min($max, (int) $input[$key]));
	}

	/** String value of a setting from JSON; arrays, objects and null become '' */
	private function str($value)
	{
		return is_scalar($value) ? (string) $value : '';
	}

	private function plain_text($value, $max)
	{
		$value = trim(strip_tags($this->str($value)));
		$value = preg_replace('/\s+/u', ' ', $value);
		return mb_substr($value, 0, $max);
	}
}
