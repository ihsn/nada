<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public site-wide CSS (Admin > Settings > Site layout > Styles).
 *
 * Settings are stored as JSON in the site_layout table (part 'styles'), for the admin editor and the preview only:
 *   published  the published settings
 *   draft      what the admin is editing; only the preview page shows it
 *
 * Public pages read only the published file, {catalog_root}/cache/site_styles/published.json, written by
 * Publish when there is CSS. Theme heads call render_head() to inject a <style data-ss-style> tag after
 * theme CSS. Publish writes the file before it stores the settings and fails if it cannot.
 *
 * Safety: CSS is filtered (no @import, expressions, escapes, or url() to other sites).
 * No file / $config['site_styles_force_default'] = TRUE → inject nothing.
 */
class Site_styles_builder {

	const VERSION = 1;
	const MAX_CSS = 20000;
	/** Published file under catalog_root (datafiles by default): cache/site_styles/published.json */
	const CACHE_SUBDIR = 'cache/site_styles';
	const CACHE_FILE = 'published.json';
	/** Bump when the file layout changes; the admin then asks to publish again */
	const FILE_FORMAT = 1;

	private $ci;
	private $file = null;
	private $preview = false;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	public function defaults()
	{
		return array(
			'version' => self::VERSION,
			'css'     => '',
		);
	}

	/** Starter snippet shown in the admin editor when nothing is published. Not auto-injected. */
	public function example_css()
	{
		return ":root {\n"
			. "  --action-color: hsl(204, 100%, 37%);\n"
			. "  --action-color--hover: #009FDA;\n"
			. "  --color-link: var(--action-color);\n"
			. "  --color-link-hover: var(--action-color--hover);\n"
			. "  --color-main: #333333;\n"
			. "}\n"
			. "\n"
			. "body,\n"
			. "p,\n"
			. "h1,\n"
			. "h2,\n"
			. "h3,\n"
			. "h4,\n"
			. "h5,\n"
			. "h6 {\n"
			. "  color: var(--color-main);\n"
			. "}\n"
			. "\n"
			. "a,\n"
			. "a.link {\n"
			. "  color: var(--color-link);\n"
			. "}\n"
			. "\n"
			. "a:hover,\n"
			. "a.link:hover {\n"
			. "  color: var(--color-link-hover);\n"
			. "  text-decoration: none;\n"
			. "}\n";
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

		$css = isset($input['css']) ? $this->str($input['css']) : '';
		if (strlen($css) > self::MAX_CSS) {
			$errors['css'] = 'CSS_TOO_LONG';
			$css = '';
		}

		return array(
			'version' => self::VERSION,
			'css'     => $this->sanitize_css($css),
		);
	}

	public function published()
	{
		$raw = $this->raw_setting('published');
		if ($raw === '') {
			return null;
		}
		return $this->normalize($raw);
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
		return $this->ci->Site_layout_model->get('styles', $state);
	}

	public function to_json(array $settings)
	{
		return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function set_preview($on)
	{
		$this->preview = (bool) $on;
	}

	public function is_preview()
	{
		return $this->preview;
	}

	public function current_settings()
	{
		if ($this->preview) {
			$draft = $this->draft();
			if ($draft !== null) {
				return $draft;
			}
		}
		$published = $this->published();
		return $published !== null ? $published : $this->defaults();
	}

	/** CSS for the current (draft or published) settings, sanitized. */
	public function current_css()
	{
		if ($this->ci->config->item('site_styles_force_default')) {
			return '';
		}
		$s = $this->current_settings();
		return isset($s['css']) ? (string) $s['css'] : '';
	}

	/**
	 * Markup for theme <head>. Empty string when nothing to inject.
	 * Public pages read only the published file; the preview uses the draft.
	 */
	public function render_head()
	{
		try {
			$css = $this->css_for_head();
			if ($css === '') {
				return '';
			}
			return '<style data-ss-style>' . "\n" . $css . "\n" . '</style>' . "\n";
		}
		catch (Throwable $e) {
			log_message('error', 'Site styles: ' . $e->getMessage());
			return '';
		}
	}

	/** Public pages: the published file only. Preview: the draft. */
	private function css_for_head()
	{
		if ($this->ci->config->item('site_styles_force_default')) {
			return '';
		}

		if ($this->preview) {
			return $this->sanitize_css($this->current_css());
		}

		$data = $this->published_file()->read();
		if (!is_array($data) || !isset($data['css']) || !is_string($data['css'])) {
			return '';
		}
		return trim($data['css']);
	}

	// ---------------------------------------------------------------------------------------------
	// Published file (the only thing public pages read)
	// ---------------------------------------------------------------------------------------------

	/** {catalog_root}/cache/site_styles/published.json */
	public function published_file()
	{
		if ($this->file === null) {
			require_once APPPATH . 'libraries/Site_layout_file.php';
			$this->file = new Site_layout_file(self::CACHE_SUBDIR, self::CACHE_FILE, array('published.css'), 'Site styles');
		}
		return $this->file;
	}

	/**
	 * What Publish writes for these settings, or null when there is no CSS (no file).
	 *
	 * @param array  $s    normalized settings
	 * @param string $json the settings exactly as stored in configurations.site_styles
	 */
	public function published_file_payload(array $s, $json)
	{
		$css = $this->sanitize_css(isset($s['css']) ? $s['css'] : '');
		if ($css === '') {
			return null;
		}
		return array(
			'format' => self::FILE_FORMAT,
			'hash'   => sha1((string) $json),
			'css'    => $css,
		);
	}

	/** Whether the published file matches the published settings (admin only) */
	public function published_file_in_sync()
	{
		$raw = $this->raw_setting('published');
		$s = $raw === '' ? null : $this->normalize($raw);
		$data = $this->published_file()->read();
		if ($s === null || $this->sanitize_css($s['css']) === '') {
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
