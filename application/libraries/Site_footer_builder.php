<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public site footer: settings, sanitizing and rendering.
 *
 * Source (Admin > Settings > Site layout > Footer):
 *   theme   — theme PHP files (footer_theme.php, or footer.local.php when present)
 *   custom  — admin HTML with tokens such as {{site_title}}, {{year}}, {{menu}}
 *
 * Settings are stored as JSON in the site_layout table (part 'footer'), for the admin editor and the preview only:
 *   published  the published settings
 *   draft      what the admin is editing; only the preview page (site_layout_preview/footer) shows it
 *
 * Public pages read only the published file, {catalog_root}/cache/site_footer/published.json, written by
 * Publish for custom footers (the compiled template: CSS + markup with {{tokens}}). They never read or
 * clean the settings. No file means the theme file footer. Publish writes the file before it stores the
 * settings and fails if it cannot. When FILE_FORMAT changes, rebuild_published_file() rewrites it.
 *
 * Safety:
 *  - admin HTML is cleaned with an allow-list (wp_kses) when saved and again when shown;
 *  - CSS is filtered (no @import, expressions, escapes, or url() to other sites);
 *  - any error, a missing setting or $config['site_footer_force_default'] = TRUE uses the theme file footer.
 *
 * Used by themes/<theme>/footer.php (theme file, local override, or echo $builder->render($menus)).
 */
class Site_footer_builder {

	const VERSION = 1;
	const MAX_HTML = 20000;
	const MAX_CSS = 20000;
	/** Published file under catalog_root (datafiles by default): cache/site_footer/published.json */
	const CACHE_SUBDIR = 'cache/site_footer';
	const CACHE_FILE = 'published.json';
	/** Bump when compile_managed() or the file layout changes; the admin then asks to publish again */
	const FILE_FORMAT = 1;

	private static $scalar_tokens = array('home_url', 'base_url', 'site_title', 'year');

	private $ci;
	private $file = null;
	private $preview = false;
	private $menus = null;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	public function defaults()
	{
		return array(
			'version' => self::VERSION,
			'source'  => 'theme', // theme | custom
			'html'    => $this->default_html(),
			'css'     => '',
		);
	}

	/** Starter custom HTML (mirrors the stock black bar) */
	public function default_html()
	{
		return '<div class="container">'
			. '<div class="row align-items-center py-3">'
			. '<div class="col-12 col-md-6 text-center text-md-left">'
			. '<a class="sf-title" href="{{home_url}}">{{site_title}}</a>'
			. '</div>'
			. '<div class="col-12 col-md-6 text-center text-md-right">'
			. '<small class="sf-copy">&copy; {{year}} {{site_title}}. All Rights Reserved.</small>'
			. '</div>'
			. '</div>'
			. '</div>';
	}

	public function normalize($input, &$errors = array())
	{
		$errors = array();
		if (is_string($input)) {
			$decoded = json_decode($input, true);
			$input = is_array($decoded) ? $decoded : array();
		}
		if (!is_array($input)) {
			$input = array();
		}

		$d = $this->defaults();
		$out = array(
			'version' => self::VERSION,
			'source'  => $this->normalize_source($input, $d['source']),
		);

		$html = isset($input['html']) ? $this->str($input['html']) : $d['html'];
		if (strlen($html) > self::MAX_HTML) {
			$errors['html'] = 'HTML_TOO_LONG';
			$html = '';
		}
		$out['html'] = $this->sanitize_html($html);
		if ($out['html'] === '' && $out['source'] === 'custom') {
			$out['html'] = $this->sanitize_html($d['html']);
		}

		$css = isset($input['css']) ? $this->str($input['css']) : '';
		if (strlen($css) > self::MAX_CSS) {
			$errors['css'] = 'CSS_TOO_LONG';
			$css = '';
		}
		$out['css'] = $this->sanitize_css($css);

		return $out;
	}

	private function normalize_source(array $input, $default)
	{
		if (isset($input['source']) && in_array($this->str($input['source']), array('theme', 'custom'), true)) {
			return $this->str($input['source']);
		}
		if ($input && (isset($input['html']) || isset($input['version']) || isset($input['css']))) {
			return 'custom';
		}
		return $default;
	}

	public function published()
	{
		$raw = $this->raw_setting('published');
		return $raw === '' ? $this->defaults() : $this->normalize($raw);
	}

	/**
	 * True when the footer should come from theme PHP.
	 * Public pages: when there is no published file. Preview: from the draft's source.
	 */
	public function use_theme_footer()
	{
		if ($this->ci->config->item('site_footer_force_default')) {
			return true;
		}
		if ($this->preview) {
			$s = $this->current_settings();
			return !isset($s['source']) || $s['source'] === 'theme';
		}
		return $this->published_file_data() === null;
	}

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

	public function local_footer_path()
	{
		$path = FCPATH . 'themes/' . $this->theme_name() . '/footer.local.php';
		return is_file($path) ? $path : '';
	}

	public function theme_footer_path()
	{
		$local = $this->local_footer_path();
		if ($local !== '') {
			return $local;
		}
		$path = FCPATH . 'themes/' . $this->theme_name() . '/footer_theme.php';
		return is_file($path) ? $path : '';
	}

	public function draft()
	{
		$raw = $this->raw_setting('draft');
		return $raw === '' ? null : $this->normalize($raw);
	}

	/** Stored settings JSON for 'published' or 'draft' (table site_layout), or '' */
	private function raw_setting($state)
	{
		$this->ci->load->model('Site_layout_model');
		return $this->ci->Site_layout_model->get('footer', $state);
	}

	public function to_json(array $settings)
	{
		return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function token_docs()
	{
		return array(
			array('token' => '{{site_title}}', 'description' => 'Website title (text)'),
			array('token' => '{{year}}', 'description' => 'Current year'),
			array('token' => '{{menu}}', 'description' => 'Top-level site menu links'),
			array('token' => '{{home_url}}', 'description' => 'Home page address'),
			array('token' => '{{base_url}}', 'description' => 'Site base address, for images in files/'),
		);
	}

	public function set_preview($on)
	{
		$this->preview = (bool) $on;
	}

	public function is_preview()
	{
		return $this->preview;
	}

	/**
	 * The footer HTML for the current request.
	 *
	 * Public pages use only the published file (see published_file()); without it they get the theme
	 * file footer. The preview builds the draft in memory.
	 */
	public function render($menus = null)
	{
		$this->menus = is_array($menus) ? $menus : null;

		try {
			if ($this->preview) {
				return $this->render_settings($this->current_settings());
			}
			$file = $this->ci->config->item('site_footer_force_default') ? null : $this->published_file_data();
			if ($file !== null) {
				return $this->replace_tokens($file['template'], array());
			}
		}
		catch (Throwable $e) {
			log_message('error', 'Site footer: ' . $e->getMessage());
		}

		try {
			return $this->render_settings($this->defaults());
		}
		catch (Throwable $e) {
			log_message('error', 'Site footer (default): ' . $e->getMessage());
			return '';
		}
	}

	/** Settings for the admin and the preview (draft first); public pages do not use them */
	private function current_settings()
	{
		if ($this->ci->config->item('site_footer_force_default')) {
			return $this->defaults();
		}
		if ($this->preview) {
			$draft = $this->draft();
			return $draft !== null ? $draft : $this->published();
		}
		return $this->published();
	}

	/** Render the given (normalized) settings in memory: theme file, or custom HTML */
	public function render_settings(array $s)
	{
		$source = isset($s['source']) ? $s['source'] : 'theme';
		if ($source === 'theme' || $this->ci->config->item('site_footer_force_default')) {
			$html = $this->render_theme_footer($this->menus);
			if ($html !== '') {
				return $html;
			}
			$s = $this->defaults();
			$s['source'] = 'custom';
		}

		return $this->replace_tokens($this->compile_managed($s), $s);
	}

	/** Custom HTML with tokens still in place (CSS baked in): what Publish stores in the published file */
	public function compile_managed(array $s)
	{
		$template = $this->sanitize_html($s['html']);
		$css = $this->base_css() . $this->sanitize_css($s['css']);

		return '<style data-sf-style>' . $css . '</style>' . "\n"
			. '<footer class="site-footer sf-custom">' . "\n" . $template . "\n" . '</footer>';
	}

	// ---------------------------------------------------------------------------------------------
	// Published file (the only thing public pages read)
	// ---------------------------------------------------------------------------------------------

	/** {catalog_root}/cache/site_footer/published.json */
	public function published_file()
	{
		if ($this->file === null) {
			require_once APPPATH . 'libraries/Site_layout_file.php';
			$this->file = new Site_layout_file(self::CACHE_SUBDIR, self::CACHE_FILE, array('published.html'), 'Site footer');
		}
		return $this->file;
	}

	/** The published file for public pages, or null to use the theme file footer */
	private function published_file_data()
	{
		$data = $this->published_file()->read();
		if (!is_array($data) || !isset($data['template']) || !is_string($data['template']) || $data['template'] === '') {
			return null;
		}
		return $data;
	}

	/**
	 * What Publish writes for these settings, or null when the source is the theme file (no file).
	 *
	 * @param array  $s    normalized settings
	 * @param string $json the settings exactly as stored in configurations.site_footer
	 */
	public function published_file_payload(array $s, $json)
	{
		if (!isset($s['source']) || $s['source'] !== 'custom') {
			return null;
		}
		return array(
			'format'   => self::FILE_FORMAT,
			'hash'     => sha1((string) $json),
			'source'   => 'custom',
			'template' => $this->compile_managed($s),
		);
	}

	/** Whether the published file matches the published settings (admin only) */
	public function published_file_in_sync()
	{
		$raw = $this->raw_setting('published');
		$s = $raw === '' ? null : $this->normalize($raw);
		$data = $this->published_file()->read();
		if ($s === null || $s['source'] !== 'custom') {
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

	public function render_theme_footer($menus = null)
	{
		$path = $this->theme_footer_path();
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

		$include = function ($__sf_file) use ($data, $menus) {
			include $__sf_file;
		};
		$include = $include->bindTo($this->ci, $this->ci);

		ob_start();
		try {
			$include($path);
		}
		catch (Throwable $e) {
			ob_end_clean();
			log_message('error', 'Site footer theme file: ' . $e->getMessage());
			return '';
		}
		return (string) ob_get_clean();
	}

	private function replace_tokens($html, array $s)
	{
		$token = '\{\{\s*([a-z_]+)\s*\}\}';
		$tag = '<[a-zA-Z\/!](?:[^>"\']|"[^"]*"|\'[^\']*\')*>';
		$self = $this;

		return preg_replace_callback('/(' . $tag . ')|' . $token . '/', function ($m) use ($s, $token, $self) {
			if ($m[1] !== '') {
				return preg_replace_callback('/' . $token . '/', function ($t) use ($s, $self) {
					return $self->scalar_token($t[1], $s);
				}, $m[1]);
			}
			return $self->token($m[2], $s);
		}, $html);
	}

	public function scalar_token($name, array $s)
	{
		switch ($name) {
			case 'home_url':
				return html_escape(site_url());
			case 'base_url':
				return html_escape(base_url());
			case 'site_title':
				return html_escape((string) $this->ci->config->item('website_title'));
			case 'year':
				return html_escape((string) date('Y'));
		}
		return '';
	}

	public function token($name, array $s)
	{
		if (in_array($name, self::$scalar_tokens, true)) {
			return $this->scalar_token($name, $s);
		}
		if ($name === 'menu') {
			return $this->menu();
		}
		return '';
	}

	private function menu()
	{
		if ($this->menus === null) {
			$this->ci->load->model('Menu_model');
			$this->menus = $this->ci->Menu_model->get_published_menu_tree();
		}
		$items = is_array($this->menus) ? $this->menus : array();
		if (!$items) {
			return '';
		}
		return $this->ci->load->view('site_footer/components/menu', array('items' => $items), true);
	}

	private function base_css()
	{
		return '.site-footer{display:block;width:100%;background:#000;color:#fff}'
			. '.site-footer a{color:inherit}'
			. '.site-footer .sf-title{color:#fff;text-decoration:none}'
			. '.site-footer .sf-title:hover{text-decoration:underline}'
			. '.site-footer .sf-copy{opacity:.85}'
			. '.site-footer .sf-menu{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:.5rem 1rem;justify-content:flex-end}'
			. '.site-footer .sf-menu a{color:inherit;text-decoration:none}'
			. '.site-footer .sf-menu a:hover{text-decoration:underline}'
			. '@media (max-width:767.98px){.site-footer .sf-menu{justify-content:center;margin-top:.5rem}}';
	}

	public function sanitize_html($html)
	{
		$html = (string) $html;
		if (trim($html) === '') {
			return '';
		}

		$this->ci->load->helper('kses');
		$html = preg_replace('#<(script|style|iframe|object|embed|template|noscript|textarea|select|svg|math|title)\b[^>]*>.*?</\1\s*>#is', '', $html);

		$global = array(
			'class' => true, 'id' => true, 'title' => true, 'role' => true, 'style' => true, 'lang' => true, 'dir' => true,
			'aria-label' => true, 'aria-hidden' => true, 'aria-current' => true,
		);
		$tags = array(
			'div', 'span', 'p', 'nav', 'footer', 'section', 'ul', 'ol', 'li', 'small', 'strong', 'b', 'em', 'i', 'u',
			'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		);
		$allowed = array();
		foreach ($tags as $tag) {
			$allowed[$tag] = $global;
		}
		$allowed['a'] = array_merge($global, array('href' => true, 'target' => true, 'rel' => true));
		$allowed['img'] = array_merge($global, array('src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true));

		return trim(wp_kses($html, $allowed, array('http', 'https', 'mailto', 'tel')));
	}

	/** String value of a setting from JSON; arrays, objects and null become '' */
	private function str($value)
	{
		return is_scalar($value) ? (string) $value : '';
	}

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
}
