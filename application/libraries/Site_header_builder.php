<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public site header: settings, presets, sanitizing and rendering.
 *
 * The header is one HTML template plus settings. The template is either a built-in preset
 * (application/views/site_header/presets/<id>.php) or HTML written by an admin. Components are placed only with
 * tokens such as {{brand}}, {{menu+login}}, {{login}} and {{language}}; the backend renders them for the current
 * visitor (signed in or out) and language.
 *
 * Settings are stored as JSON in the configurations table:
 *   site_header        what the public site shows
 *   site_header_draft  what the admin is editing; only the preview page (Site_header_preview) shows it
 *
 * Safety:
 *  - admin HTML is cleaned with an allow-list (wp_kses) when saved and again when shown;
 *  - tokens are replaced after cleaning, so cleaning never touches generated markup;
 *  - CSS is filtered (no @import, expressions, escapes, or url() to other sites);
 *  - any error, a missing setting or $config['site_header_force_default'] = TRUE shows the default header.
 *
 * Used by themes/<theme>/header.php:  echo $this->site_header_builder->render($menus);
 */
class Site_header_builder {

	const VERSION = 1;
	const MAX_HTML = 20000;
	const MAX_CSS = 20000;
	const UPLOAD_DIR = 'files/site';

	/** Tokens that output plain text or a URL; the only ones allowed inside attributes */
	private static $scalar_tokens = array('home_url', 'base_url', 'logo_url', 'bg_image_url', 'site_title', 'site_subtitle');

	private $ci;
	private $presets = null;
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
		return array(
			'version'        => self::VERSION,
			'mode'           => 'preset',      // preset | custom
			'preset'         => 'classic',
			'brand'          => 'text',        // text | logo | logo_text
			'subtitle'       => '',            // empty = the translated default ("Data Catalog")
			'show_subtitle'  => true,
			'show_separator' => true,          // line between the logo and the site title
			'logo'           => '',            // files/site/logo-<hash>.<ext>; empty = the theme's logo
			'logo_alt'       => '',
			'logo_height'    => 50,
			'show_login'     => true,
			'show_language'  => true,
			'bg_color'       => '',
			'text_color'     => '',
			'bg_image'       => '',
			'bg_size'        => 'cover',       // cover | contain | auto
			'bg_position'    => 'center',      // center | top | bottom | left | right
			'html'           => '',
			'css'            => '',
		);
	}

	/**
	 * Validate and clean settings.
	 *
	 * @param mixed $input  array (or JSON string) from the admin or the database
	 * @param array $errors filled with field => error code for values that were refused
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

		$d = $this->defaults();
		$out = $d;

		$out['mode'] = $this->pick($input, 'mode', array('preset', 'custom'), $d['mode']);
		$out['brand'] = $this->pick($input, 'brand', array('text', 'logo', 'logo_text'), $d['brand']);
		$out['bg_size'] = $this->pick($input, 'bg_size', array('cover', 'contain', 'auto'), $d['bg_size']);
		$out['bg_position'] = $this->pick($input, 'bg_position', array('center', 'top', 'bottom', 'left', 'right'), $d['bg_position']);

		if (isset($input['preset'])) {
			$presets = $this->presets();
			if (isset($presets[(string) $input['preset']])) {
				$out['preset'] = (string) $input['preset'];
			} else {
				$errors['preset'] = 'PRESET_NOT_FOUND';
			}
		}

		foreach (array('show_subtitle', 'show_separator', 'show_login', 'show_language') as $key) {
			if (array_key_exists($key, $input)) {
				$out[$key] = filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
			}
		}

		$out['subtitle'] = $this->plain_text(isset($input['subtitle']) ? $input['subtitle'] : '', 200);
		$out['logo_alt'] = $this->plain_text(isset($input['logo_alt']) ? $input['logo_alt'] : '', 200);

		if (isset($input['logo_height'])) {
			$h = (int) $input['logo_height'];
			$out['logo_height'] = max(16, min(200, $h ?: $d['logo_height']));
		}

		foreach (array('bg_color', 'text_color') as $key) {
			$v = isset($input[$key]) ? trim((string) $input[$key]) : '';
			if ($v === '' || preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $v)) {
				$out[$key] = strtolower($v);
			} else {
				$errors[$key] = 'INVALID_COLOR';
			}
		}

		foreach (array('logo', 'bg_image') as $key) {
			$v = isset($input[$key]) ? trim((string) $input[$key]) : '';
			if ($v === '' || $this->is_upload_path($v)) {
				$out[$key] = $v;
			} else {
				$errors[$key] = 'INVALID_IMAGE';
			}
		}

		$html = isset($input['html']) ? (string) $input['html'] : '';
		if (strlen($html) > self::MAX_HTML) {
			$errors['html'] = 'HTML_TOO_LONG';
			$html = '';
		}
		$out['html'] = $this->sanitize_html($html);

		$css = isset($input['css']) ? (string) $input['css'] : '';
		if (strlen($css) > self::MAX_CSS) {
			$errors['css'] = 'CSS_TOO_LONG';
			$css = '';
		}
		$out['css'] = $this->sanitize_css($css);

		return $out;
	}

	/** Published settings (what visitors see) */
	public function published()
	{
		return $this->stored('site_header');
	}

	/** Draft settings, or null when there is no draft */
	public function draft()
	{
		$raw = $this->raw_setting('site_header_draft');
		return $raw === '' ? null : $this->normalize($raw);
	}

	private function stored($key)
	{
		$raw = $this->raw_setting($key);
		return $raw === '' ? $this->defaults() : $this->normalize($raw);
	}

	private function raw_setting($key)
	{
		$value = $this->ci->config->item($key);
		if (is_array($value)) {
			return json_encode($value);
		}
		return trim((string) $value);
	}

	public function to_json(array $settings)
	{
		return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// =============================================================================================
	// Presets
	// =============================================================================================

	/** @return array id => [id, title, description, html, css, background_selector, thumbnail] in display order */
	public function presets()
	{
		if ($this->presets !== null) {
			return $this->presets;
		}

		$order = array('classic', 'utility_bar', 'centered', 'stacked', 'utility_stacked', 'hero');
		$dir = APPPATH . 'views/site_header/presets/';
		$this->presets = array();
		foreach ($order as $id) {
			$file = $dir . $id . '.php';
			if (!is_file($file)) {
				continue;
			}
			$preset = include $file;
			if (!is_array($preset) || !isset($preset['html'])) {
				continue;
			}
			$this->presets[$id] = array_merge(array(
				'id'                  => $id,
				'title'               => $id,
				'description'         => '',
				'css'                 => '',
				'background_selector' => '.site-header',
				'color_selector'      => '.site-header',
				'thumbnail'           => '',
			), $preset, array('id' => $id));
		}
		return $this->presets;
	}

	/** Tokens with a short description, for the admin editor */
	public function token_docs()
	{
		return array(
			array('token' => '{{brand}}', 'description' => 'Logo and/or site title, as chosen under Brand'),
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
			array('token' => '{{home_url}}', 'description' => 'Home page address'),
			array('token' => '{{base_url}}', 'description' => 'Site base address, for images in files/'),
			array('token' => '{{logo_url}}', 'description' => 'Address of the logo image'),
			array('token' => '{{bg_image_url}}', 'description' => 'Address of the background image'),
		);
	}

	// =============================================================================================
	// Rendering
	// =============================================================================================

	/** Show the draft instead of the published header (preview page only) */
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
	 * @param array|null $menus published menu tree, when the layout already loaded it
	 */
	public function render($menus = null)
	{
		$this->menus = is_array($menus) ? $menus : null;

		try {
			return $this->render_settings($this->current_settings());
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

	/** Render the given (normalized) settings */
	public function render_settings(array $s)
	{
		$this->menu_count = $this->login_count = $this->lang_count = 0;
		$presets = $this->presets();
		$preset = isset($presets[$s['preset']]) ? $presets[$s['preset']] : reset($presets);

		if ($s['mode'] === 'custom') {
			// cleaned again: the database may hold HTML saved before a rule was added
			$template = $this->sanitize_html($s['html']);
			$bg_selector = '.site-header';
			$color_selector = '.site-header';
			$preset_css = '';
			$class = 'sh-custom';
		} else {
			$template = $preset['html'];
			$bg_selector = $preset['background_selector'];
			$color_selector = $preset['color_selector'];
			$preset_css = $preset['css'];
			$class = 'sh-preset-' . $preset['id'];
		}

		if ($s['bg_image'] !== '') {
			$class .= ' sh-has-bg-image';
		}
		if ($s['bg_color'] !== '') {
			$class .= ' sh-has-bg-color';
		}

		$body = $this->replace_tokens($template, $s);
		$css = $this->base_css() . $preset_css . $this->settings_css($s, $bg_selector, $color_selector) . $this->sanitize_css($s['css']);

		return '<style>' . $css . '</style>' . "\n"
			. '<header class="site-header ' . $class . '">' . "\n" . $body . "\n" . '</header>';
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
		if (!in_array($name, self::$scalar_tokens, true)) {
			return '';
		}
		switch ($name) {
			case 'home_url':
				return html_escape(site_url());
			case 'base_url':
				return html_escape(base_url());
			case 'logo_url':
				return html_escape($this->logo_url($s));
			case 'bg_image_url':
				return $s['bg_image'] !== '' ? html_escape(base_url($s['bg_image'])) : '';
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

	/** Uploaded logo, else the theme's logo, else '' */
	public function logo_url(array $s)
	{
		if ($s['logo'] !== '') {
			return base_url($s['logo']);
		}
		$theme = isset($this->ci->template) ? $this->ci->template->theme() : 'nada52';
		foreach (array('logo.svg', 'logo.png') as $file) {
			if (is_file(FCPATH . 'themes/' . $theme . '/images/' . $file)) {
				return base_url('themes/' . $theme . '/images/' . $file);
			}
		}
		return '';
	}

	// ---------------------------------------------------------------------------------------------
	// CSS
	// ---------------------------------------------------------------------------------------------

	private function base_css()
	{
		return '.site-header .navbar-light .no-logo .navbar-brand--sitename{border:0!important;margin-left:0}'
			. '.site-header .navbar-light .no-logo .nada-site-title{font-size:18px}'
			. '.site-header .navbar-brand.sh-no-separator .navbar-brand--sitename{border-left:0}'
			. '.site-header .navbar-brand--logo{display:flex;align-items:center}'
			. '.site-header .navbar-brand--logo img{width:auto;max-width:100%}'
			. '.site-header .sh-logo-separator{display:inline-block;align-self:stretch;width:1px;min-height:1.5em;margin:0 .8rem;background:var(--wbg-lightbluegray,#ccd5df)}'
			. '.site-header .sh-optional:not(:has(.sh-c)){display:none}'
			. '.site-header .sh-login-inline .nav-link,.site-header .sh-language-inline a{white-space:nowrap}';
	}

	private function settings_css(array $s, $bg_selector, $color_selector)
	{
		$css = '.site-header .navbar-brand--logo img{max-height:' . (int) $s['logo_height'] . 'px}';

		$bg = '';
		if ($s['bg_color'] !== '') {
			$bg .= 'background-color:' . $s['bg_color'] . ';';
		}
		if ($s['bg_image'] !== '') {
			$url = str_replace(array('"', '\\', "\n", "\r"), '', base_url($s['bg_image']));
			$bg .= 'background-image:url("' . $url . '");background-size:' . $s['bg_size'] . ';background-position:' . $s['bg_position'] . ';background-repeat:no-repeat;';
		}
		if ($bg !== '') {
			$css .= $bg_selector . '{' . $bg . '}';
		}

		if ($s['text_color'] !== '') {
			$c = $s['text_color'];
			$sel = array();
			foreach (array('', ' .nada-site-title', ' .nada-site-subtitle', ' .nav-link', ' .navbar-light .navbar-nav .nav-link', ' .lang-container a') as $suffix) {
				$sel[] = $color_selector . $suffix;
			}
			$css .= implode(',', $sel) . '{color:' . $c . '}';
			if ($this->is_light($c)) {
				$css .= $color_selector . ' .navbar-toggler{border-color:rgba(255,255,255,.5)}'
					. $color_selector . ' .navbar-toggler-icon{filter:invert(1)}';
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
			'data-toggle' => true, 'data-target' => true,
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

	private function pick(array $input, $key, array $allowed, $default)
	{
		return (isset($input[$key]) && in_array((string) $input[$key], $allowed, true)) ? (string) $input[$key] : $default;
	}

	private function plain_text($value, $max)
	{
		$value = trim(strip_tags((string) $value));
		$value = preg_replace('/\s+/u', ' ', $value);
		return mb_substr($value, 0, $max);
	}
}
